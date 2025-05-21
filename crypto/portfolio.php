<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Database connection setup
$host = 'localhost';
$db = 'crypto_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Fetch user data
$sql = "SELECT UserName FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
// Using user 1
$userID = 1;

// Get WalletID
$stmt = $conn->prepare("SELECT WalletID FROM Wallets WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($walletID);
if (!$stmt->fetch()) {
    die("Wallet not found for user.");
}
$stmt->close();

// Cryptos to display
$cryptoSymbols = ['BTC', 'ETH', 'DOGE'];

// Fetch crypto IDs and names from database
$placeholders = implode(',', array_fill(0, count($cryptoSymbols), '?'));
$sql = "SELECT CryptoID, Symbol, Name FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
$stmt = $conn->prepare($sql);

// Bind parameters dynamically
$types = str_repeat('s', count($cryptoSymbols));
$stmt->bind_param($types, ...$cryptoSymbols);
$stmt->execute();
$result = $stmt->get_result();

$cryptos = [];
while ($row = $result->fetch_assoc()) {
    $cryptos[$row['Symbol']] = [
        'CryptoID' => $row['CryptoID'],
        'Name' => $row['Name'],
        'MarketPrice' => 0 // Will be filled with API data
    ];
}
$stmt->close();

// Cache settings
$cacheKey = 'coingecko_simple_price_data';
$cacheExpiry = 5 * 60; // 5 minutes in seconds

// Check for cached API data and validate its structure
$priceData = null;
if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheKey]['timestamp'])) {
    $cacheTime = $_SESSION[$cacheKey]['timestamp'];
    $cachedData = $_SESSION[$cacheKey]['data'];
    // Validate that cached data is an array with prices for all expected coins
    if ((time() - $cacheTime) < $cacheExpiry && is_array($cachedData)) {
        $isValid = true;
        foreach ($cryptoSymbols as $symbol) {
            $coinGeckoId = strtolower($symbol) === 'btc' ? 'bitcoin' : (strtolower($symbol) === 'eth' ? 'ethereum' : 'dogecoin');
            if (!isset($cachedData[$coinGeckoId]) || !isset($cachedData[$coinGeckoId]['usd']) || !is_numeric($cachedData[$coinGeckoId]['usd'])) {
                $isValid = false;
                error_log("Invalid cached price data at " . date('Y-m-d H:i:s') . ": " . json_encode($cachedData) . "\n", 3, 'api_errors.log');
                break;
            }
        }
        if ($isValid) {
            $priceData = $cachedData;
        } else {
            unset($_SESSION[$cacheKey]);
            error_log("Invalid cached data at " . date('Y-m-d H:i:s') . ": " . json_encode($cachedData) . "\n", 3, 'api_errors.log');
        }
    }
}

// Fetch current prices from CoinGecko API if no valid cache
$coinGeckoIds = [
    'BTC' => 'bitcoin',
    'ETH' => 'ethereum',
    'DOGE' => 'dogecoin'
];

$apiError = false;
if (!$priceData) {
    try {
        $apiUrl = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,dogecoin&vs_currencies=usd';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: CryptoTrack/1.0\r\n",
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($apiUrl, false, $context);

        // Get HTTP status code
        $httpCode = 0;
        if (isset($http_response_header[0])) {
            preg_match('/\d{3}/', $http_response_header[0], $matches);
            $httpCode = isset($matches[0]) ? (int)$matches[0] : 0;
        }

        if ($response === false || $httpCode >= 400) {
            throw new Exception("API request failed with HTTP code $httpCode");
        }

        $priceData = json_decode($response, true);

        // Log the raw response for debugging
        error_log("CoinGecko API Response at " . date('Y-m-d H:i:s') . ": " . $response . "\n", 3, 'api_responses.log');

        // Validate the response structure
        if (!is_array($priceData)) {
            throw new Exception('Invalid API response: Not an array');
        }

        // Validate that $priceData contains prices for all expected coins
        $isValid = true;
        foreach ($cryptoSymbols as $symbol) {
            $coinGeckoId = $coinGeckoIds[$symbol];
            if (!isset($priceData[$coinGeckoId]) || !isset($priceData[$coinGeckoId]['usd']) || !is_numeric($priceData[$coinGeckoId]['usd'])) {
                $isValid = false;
                error_log("Invalid API price data at " . date('Y-m-d H:i:s') . ": " . json_encode($priceData) . "\n", 3, 'api_errors.log');
                break;
            }
        }

        if (!$isValid) {
            throw new Exception('Invalid API response: Missing required price data');
        }

        // Store in session cache
        $_SESSION[$cacheKey] = [
            'data' => $priceData,
            'timestamp' => time()
        ];

        // Update database with new prices
        foreach ($cryptos as $symbol => &$cryptoData) {
            if (isset($coinGeckoIds[$symbol]) && isset($priceData[$coinGeckoIds[$symbol]])) {
                $cryptoData['MarketPrice'] = $priceData[$coinGeckoIds[$symbol]]['usd'];
                $sql = "UPDATE Cryptocurrencies SET MarketPrice = ? WHERE Symbol = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ds', $cryptoData['MarketPrice'], $symbol);
                $stmt->execute();
                $stmt->close();
            }
        }
        unset($cryptoData);

    } catch (Exception $e) {
        $apiError = true;
        error_log("CoinGecko API Error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n", 3, 'api_errors.log');
        // Fallback to database prices
        $symbols = array_keys($cryptos);
        $placeholders = implode(',', array_fill(0, count($symbols), '?'));
        $sql = "SELECT Symbol, MarketPrice FROM Cryptocurrencies WHERE Symbol IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($symbols)), ...$symbols);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $cryptos[$row['Symbol']]['MarketPrice'] = $row['MarketPrice'];
        }
        $stmt->close();
    }
} else {
    // Use cached data (already validated)
    foreach ($cryptos as $symbol => &$cryptoData) {
        if (isset($coinGeckoIds[$symbol]) && isset($priceData[$coinGeckoIds[$symbol]])) {
            $cryptoData['MarketPrice'] = $priceData[$coinGeckoIds[$symbol]]['usd'];
        }
    }
    unset($cryptoData);
}

// Fetch amounts of these cryptos in user's wallet
$cryptoIDs = [];
foreach ($cryptos as $symbol => $data) {
    $cryptoIDs[] = $data['CryptoID'];
}

if (count($cryptoIDs) > 0) {
    $placeholders = implode(',', array_fill(0, count($cryptoIDs), '?'));
    $types = str_repeat('i', count($cryptoIDs) + 1);
    $sql = "SELECT CryptoID, amount FROM walletcryptos WHERE walletid = ? AND CryptoID IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$walletID], $cryptoIDs);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $holdings = [];
    while ($row = $result->fetch_assoc()) {
        $holdings[$row['CryptoID']] = $row['amount'];
    }
    $stmt->close();
} else {
    $holdings = [];
}

$conn->close();

// Prepare portfolio data with values
$portfolio = [];
$totalValue = 0;

foreach ($cryptos as $symbol => $data) {
    $cryptoID = $data['CryptoID'];
    $amount = isset($holdings[$cryptoID]) ? $holdings[$cryptoID] : 0;
    $value = $amount * $data['MarketPrice'];
    $portfolio[$symbol] = [
        'name' => $data['Name'],
        'amount' => $amount,
        'price' => $data['MarketPrice'],
        'value' => $value
    ];
    $totalValue += $value;
}

// Calculate % distribution for pie chart
$percentages = [];
foreach ($portfolio as $symbol => $data) {
    $percentages[$symbol] = $totalValue > 0 ? round(($data['value'] / $totalValue) * 100, 2) : 0;
}

// Get portfolio performance (mock data since we don't have historical data)
$portfolioChange = 320.45;
$portfolioChangePercent = 3.8;
$isPositive = $portfolioChange >= 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CryptoTrack - Portfolio</title>
    <link rel="stylesheet" href="styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
        /* Additional styles to match dashboard */
        .portfolio-overview {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        .portfolio-overview h2 {
            color: #333;
            margin-bottom: 24px;
            font-size: 1.5rem;
        }
        .portfolio-value-card {
            background: linear-gradient(135deg, #42b883, #3498db);
            color: white;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .portfolio-value-card h3 {
            font-size: 1.2rem;
            margin: 0 0 8px 0;
            opacity: 0.9;
        }
        .portfolio-value-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 16px 0;
        }
        .portfolio-value-card .change {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 1rem;
        }
        .positive {
            color: #c8f7e6;
        }
        .negative {
            color: #fed7d7;
        }
        .portfolio-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .portfolio-assets {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }
        .portfolio-distribution {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }
        .portfolio-assets h3, .portfolio-distribution h3 {
            color: #333;
            margin-bottom: 16px;
            font-size: 1.2rem;
        }
        .crypto-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .crypto-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #edf2f7;
            color: #718096;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .crypto-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #edf2f7;
        }
        .crypto-table tr:last-child td {
            border-bottom: none;
        }
        .crypto-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .crypto-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3182ce;
        }
        .crypto-name .name {
            font-weight: 600;
            color: #2d3748;
        }
        .crypto-name .symbol {
            color: #718096;
            font-size: 0.875rem;
        }
        .distribution-chart {
            height: 250px;
            position: relative;
        }
        .distribution-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 16px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .btc {
            background-color: #f7931a;
        }
        .eth {
          background-color: #627eea;
        }
        .doge {
            background-color: #c3a634; 
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .portfolio-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <a href="dashboard.php" style="text-decoration: none">
            <h1><i class="fas fa-coins"></i> CryptoTrack</h1>
          </a>
        </div>
        <nav class="sidebar-nav">
          <ul>
            <li>
              <a href="dashboard.php">
                <i class="fas fa-chart-line"></i> Dashboard
              </a>
            </li>
            <li class="active">
              <a href="#" aria-current="page">
                <i class="fas fa-wallet"></i> Portfolio
              </a>
            </li>
            <li>
              <a href="buy.php">
                <i class="fas fa-shopping-cart"></i> Buy Crypto
              </a>
            </li>
            <li>
              <a href="sell.php">
                <i class="fas fa-exchange-alt"></i> Sell Crypto
              </a>
            </li>
            <li>
              <a href="transactions.php">
                <i class="fas fa-history"></i> Transactions
              </a>
            </li>
            <li>
              <a href="settings.php">
                <i class="fas fa-cog"></i> Settings
              </a>
            </li>
          </ul>
        </nav>
        <div class="sidebar-footer">
          <div class="user-profile">
            <img
              src="user.png"
              alt="Profile Picture"
            />
            <div class="user-info">
              <h4><?php echo htmlspecialchars($user['UserName']); ?></h4>
              <p>Premium User</p>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="main-content">
        <header class="main-header">
          <button class="sidebar-toggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
          </button>
          <div class="header-actions">
            <a href="buy.php" class="btn btn-primary" style="text-decoration: none;">
              <i class="fas fa-plus"></i> Buy Crypto
            </a>
          </div>
        </header>

        <!-- Portfolio Overview -->
        <section class="portfolio-overview">
          <h2><i class="fas fa-wallet"></i> Portfolio Overview</h2>
          
          <?php if ($apiError): ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Unable to fetch current crypto prices. Displaying last known prices.
          </div>
          <?php endif; ?>
          
          <div class="portfolio-value-card">
            <h3>Total Portfolio Value</h3>
            <p class="value">$<?= number_format($totalValue, 2) ?></p>
            <p class="change <?= $isPositive ? 'positive' : 'negative' ?>">
              <i class="fas fa-<?= $isPositive ? 'caret-up' : 'caret-down' ?>"></i>
              $<?= number_format(abs($portfolioChange), 2) ?> (<?= $portfolioChangePercent ?>%)
            </p>
          </div>
          
          <div class="portfolio-details">
            <div class="portfolio-assets">
              <h3>Your Assets</h3>
              <table class="crypto-table">
                <thead>
                  <tr>
                    <th>Asset</th>
                    <th>Amount</th>
                    <th>Price</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($portfolio as $symbol => $data): ?>
                    <tr>
                      <td>
                        <div class="crypto-name">
                          <div class="crypto-icon">
                            <?php
                            // Use the correct icon for each cryptocurrency
                            $icon = 'coins'; // Default
                            if (strtolower($symbol) === 'btc') {
                                $icon = 'bitcoin';
                            } elseif (strtolower($symbol) === 'eth') {
                                $icon = 'ethereum';
                            }
                            ?>
                            <i class="fab fa-<?= $icon ?>"></i>
                          </div>
                          <div>
                            <div class="name"><?= htmlspecialchars($data['name']) ?></div>
                            <div class="symbol"><?= htmlspecialchars($symbol) ?></div>
                          </div>
                        </div>
                      </td>
                      <td><?= number_format($data['amount'], 8) ?></td>
                      <td>$<?= number_format($data['price'], 2) ?></td>
                      <td>$<?= number_format($data['value'], 2) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <div class="portfolio-distribution">
              <h3>Distribution</h3>
              <div class="distribution-chart">
                <canvas id="distributionChart"></canvas>
              </div>
              <div class="distribution-legend">
                <?php foreach ($percentages as $symbol => $percent): ?>
                  <div class="legend-item">
                    <span class="color-dot <?= strtolower($symbol) ?>"></span>
                    <span><?= htmlspecialchars($symbol) ?></span>
                    <span style="margin-left: auto;"><?= $percent ?>%</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
      // Create the distribution chart
      const ctx = document.getElementById('distributionChart').getContext('2d');
      
      const portfolioData = {
        labels: <?= json_encode(array_keys($percentages)) ?>,
        datasets: [{
          data: <?= json_encode(array_values($percentages)) ?>,
          backgroundColor: [
            '#f7931a', // BTC
            '#627eea', // ETH (colors corrected to match CSS)
            '#c3a634', // DOGE
          ],
          borderWidth: 0,
          hoverOffset: 15
        }]
      };
      
      const config = {
        type: 'doughnut',
        data: portfolioData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `${context.label}: ${context.raw}%`;
                }
              }
            }
          }
        }
      };
      
      new Chart(ctx, config);
      
      // Toggle sidebar on mobile
      document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show-sidebar');
      });
    </script>
</body>
</html>
