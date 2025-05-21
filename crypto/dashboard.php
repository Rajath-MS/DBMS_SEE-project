<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection setup
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Get WalletID
$userID = $_SESSION['user_id'];
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
$types = str_repeat('s', count($cryptoSymbols));
$stmt->bind_param($types, ...$cryptoSymbols);
$stmt->execute();
$result = $stmt->get_result();

$cryptos = [];
while ($row = $result->fetch_assoc()) {
    $cryptos[$row['Symbol']] = [
        'CryptoID' => $row['CryptoID'],
        'Name' => $row['Name'],
        'MarketPrice' => 0
    ];
}
$stmt->close();

// Cache settings
$cacheKey = 'coingecko_price_data';
$cacheExpiry = 5 * 60; // 5 minutes in seconds

// Check for cached API data and validate its structure
$marketData = null;
if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheKey]['timestamp'])) {
    $cacheTime = $_SESSION[$cacheKey]['timestamp'];
    $cachedData = $_SESSION[$cacheKey]['data'];
    // Validate that cached data is an array of coin objects with all required fields
    if ((time() - $cacheTime) < $cacheExpiry && is_array($cachedData)) {
        $isValid = true;
        foreach ($cachedData as $coin) {
            if (
                !is_array($coin) ||
                !isset($coin['symbol']) ||
                !isset($coin['current_price']) ||
                !isset($coin['price_change_percentage_24h']) ||
                !isset($coin['price_change_percentage_7d']) ||
                !isset($coin['price_change_percentage_30d']) ||
                !isset($coin['price_change_percentage_1y']) ||
                !isset($coin['market_cap']) ||
                !isset($coin['total_volume']) ||
                !isset($coin['circulating_supply']) ||
                !isset($coin['ath'])
            ) {
                $isValid = false;
                error_log("Invalid cached coin data at " . date('Y-m-d H:i:s') . ": " . json_encode($coin) . "\n", 3, 'api_errors.log');
                break;
            }
        }
        if ($isValid) {
            $marketData = $cachedData;
        } else {
            // Invalidate the cache if the data is not in the expected format
            unset($_SESSION[$cacheKey]);
            error_log("Invalid cached data at " . date('Y-m-d H:i:s') . ": " . json_encode($cachedData) . "\n", 3, 'api_errors.log');
        }
    }
}

// Fetch current market data from CoinGecko API if no valid cache
$coinGeckoIds = [
    'BTC' => 'bitcoin',
    'ETH' => 'ethereum',
    'DOGE' => 'dogecoin'
];

if (!$marketData) {
    try {
        $apiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=bitcoin,ethereum,dogecoin&order=market_cap_desc&per_page=3&page=1&sparkline=false&price_change_percentage=24h,7d,30d,1y';
        // Use stream context to capture HTTP status and headers
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
        
        $marketData = json_decode($response, true);
        
        // Log the raw response for debugging
        error_log("CoinGecko API Response at " . date('Y-m-d H:i:s') . ": " . $response . "\n", 3, 'api_responses.log');
        
        // Validate the response structure
        if (!is_array($marketData)) {
            throw new Exception('Invalid API response: Not an array');
        }
        
        // Check if the response contains an error (e.g., rate limit)
        if (isset($marketData['status']) && isset($marketData['status']['error_code'])) {
            throw new Exception('API error: ' . $marketData['status']['error_message']);
        }
        
        // Validate that $marketData is a list of coin objects with all required fields
        $isValid = true;
        foreach ($marketData as $coin) {
            if (
                !is_array($coin) ||
                !isset($coin['symbol']) ||
                !isset($coin['current_price']) ||
                !isset($coin['price_change_percentage_24h']) ||
                !isset($coin['price_change_percentage_7d']) ||
                !isset($coin['price_change_percentage_30d']) ||
                !isset($coin['price_change_percentage_1y']) ||
                !isset($coin['market_cap']) ||
                !isset($coin['total_volume']) ||
                !isset($coin['circulating_supply']) ||
                !isset($coin['ath'])
            ) {
                $isValid = false;
                error_log("Invalid API coin data at " . date('Y-m-d H:i:s') . ": " . json_encode($coin) . "\n", 3, 'api_errors.log');
                break;
            }
        }
        
        if (!$isValid) {
            throw new Exception('Invalid API response: Missing required fields');
        }
        
        // Store in session cache
        $_SESSION[$cacheKey] = [
            'data' => $marketData,
            'timestamp' => time()
        ];
        
        // Update database with new values
        foreach ($cryptos as $symbol => &$cryptoData) {
            foreach ($marketData as $coin) {
                if (isset($coin['symbol']) && $coin['symbol'] === strtolower($symbol)) {
                    $cryptoData['MarketPrice'] = $coin['current_price'];
                    // Check if the new columns exist before updating
                    $sql = "SHOW COLUMNS FROM Cryptocurrencies LIKE 'PriceChange24h'";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        $sql = "UPDATE Cryptocurrencies SET
                                MarketPrice = ?,
                                PriceChange24h = ?,
                                PriceChange7d = ?,
                                PriceChange30d = ?,
                                PriceChange1y = ?,
                                MarketCap = ?,
                                TotalVolume = ?,
                                CirculatingSupply = ?,
                                AllTimeHigh = ?
                                WHERE Symbol = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param(
                            'dddddddddd',
                            $coin['current_price'],
                            $coin['price_change_percentage_24h'],
                            $coin['price_change_percentage_7d'],
                            $coin['price_change_percentage_30d'],
                            $coin['price_change_percentage_1y'],
                            $coin['market_cap'],
                            $coin['total_volume'],
                            $coin['circulating_supply'],
                            $coin['ath'],
                            $symbol
                        );
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // If new columns don't exist, update only MarketPrice
                        $sql = "UPDATE Cryptocurrencies SET MarketPrice = ? WHERE Symbol = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('ds', $coin['current_price'], $symbol);
                        $stmt->execute();
                        $stmt->close();
                    }
                    break;
                }
            }
        }
        unset($cryptoData);
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("CoinGecko API Error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n", 3, 'api_errors.log');
        // Extend cache expiry if data exists and is valid
        if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheKey]['data'])) {
            $cachedData = $_SESSION[$cacheKey]['data'];
            $isValid = true;
            foreach ($cachedData as $coin) {
                if (
                    !is_array($coin) ||
                    !isset($coin['symbol']) ||
                    !isset($coin['current_price']) ||
                    !isset($coin['price_change_percentage_24h']) ||
                    !isset($coin['price_change_percentage_7d']) ||
                    !isset($coin['price_change_percentage_30d']) ||
                    !isset($coin['price_change_percentage_1y']) ||
                    !isset($coin['market_cap']) ||
                    !isset($coin['total_volume']) ||
                    !isset($coin['circulating_supply']) ||
                    !isset($coin['ath'])
                ) {
                    $isValid = false;
                    error_log("Invalid cached coin data in catch block at " . date('Y-m-d H:i:s') . ": " . json_encode($coin) . "\n", 3, 'api_errors.log');
                    break;
                }
            }
            if ($isValid) {
                $_SESSION[$cacheKey]['timestamp'] = time() - ($cacheExpiry - 300); // Extend by another 5 minutes
                $marketData = $cachedData;
            } else {
                unset($_SESSION[$cacheKey]);
            }
        }
        // Fallback to database prices (only fetch columns we know exist)
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
        foreach ($marketData as $coin) {
            if (isset($coin['symbol']) && $coin['symbol'] === strtolower($symbol)) {
                $cryptoData['MarketPrice'] = $coin['current_price'];
                break;
            }
        }
    }
    unset($cryptoData);
}

// Fetch amounts of these cryptos in user's wallet
$cryptoIDs = array_column($cryptos, 'CryptoID');
$holdings = [];
if (count($cryptoIDs) > 0) {
    $placeholders = implode(',', array_fill(0, count($cryptoIDs), '?'));
    $types = str_repeat('i', count($cryptoIDs) + 1);
    $sql = "SELECT CryptoID, amount FROM walletcryptos WHERE WalletID = ? AND CryptoID IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$walletID], $cryptoIDs);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $holdings[$row['CryptoID']] = $row['amount'];
    }
    $stmt->close();
}

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

// Prepare data for script.js
$labels = [];
$data = [];
$colors = [];
$displayCryptos = ['BTC', 'ETH', 'DOGE'];
$colorMap = [
    'BTC' => '#f7931a', // Bitcoin
    'ETH' => '#627eea', // Ethereum
    'DOGE' => '#c3a634' // Dogecoin
];

foreach ($displayCryptos as $symbol) {
    if (isset($percentages[$symbol]) && $percentages[$symbol] > 0) {
        $labels[] = $portfolio[$symbol]['name'];
        $data[] = $percentages[$symbol];
        $colors[] = $colorMap[$symbol];
    }
}

// Get portfolio performance (mock data)
$portfolioChange = 320.45;
$portfolioChangePercent = 3.8;
$isPositive = $portfolioChange >= 0;

// Fetch recent transactions
$sql = "SELECT t.TransactionID, c.Symbol, c.Name, t.Amount, t.Type, t.Timestamp
        FROM Transactions t
        JOIN Cryptocurrencies c ON t.CryptoID = c.CryptoID
        WHERE t.WalletID = ?
        ORDER BY t.Timestamp DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $walletID);
$stmt->execute();
$result = $stmt->get_result();
$recentTransactions = [];
while ($row = $result->fetch_assoc()) {
    $recentTransactions[] = $row;
}
$stmt->close();

// Get Bitcoin market data for initial display
$bitcoinData = [
    'current_price' => isset($cryptos['BTC']['MarketPrice']) ? $cryptos['BTC']['MarketPrice'] : 0,
    'price_change_percentage_24h' => 0,
    'price_change_percentage_7d' => 0,
    'price_change_percentage_30d' => 0,
    'price_change_percentage_1y' => 0,
    'market_cap' => 0,
    'total_volume' => 0,
    'circulating_supply' => 0,
    'ath' => 0
];

// Try to get data from cache or API first
if ($marketData) {
    foreach ($marketData as $coin) {
        if (isset($coin['symbol']) && $coin['symbol'] === 'btc') {
            $bitcoinData = [
                'current_price' => isset($coin['current_price']) ? $coin['current_price'] : 0,
                'price_change_percentage_24h' => round(
                    isset($coin['price_change_percentage_24h']) ? $coin['price_change_percentage_24h'] : 0,
                    2
                ),
                'price_change_percentage_7d' => round(
                    isset($coin['price_change_percentage_7d']) ? $coin['price_change_percentage_7d'] : 0,
                    2
                ),
                'price_change_percentage_30d' => round(
                    isset($coin['price_change_percentage_30d']) ? $coin['price_change_percentage_30d'] : 0,
                    2
                ),
                'price_change_percentage_1y' => round(
                    isset($coin['price_change_percentage_1y']) ? $coin['price_change_percentage_1y'] : 0,
                    2
                ),
                'market_cap' => isset($coin['market_cap']) ? $coin['market_cap'] : 0,
                'total_volume' => isset($coin['total_volume']) ? $coin['total_volume'] : 0,
                'circulating_supply' => isset($coin['circulating_supply']) ? $coin['circulating_supply'] : 0,
                'ath' => isset($coin['ath']) ? $coin['ath'] : 0
            ];
            break;
        }
    }
} else {
    // Fallback to database for Bitcoin data
    // First, fetch the basic fields
    $sql = "SELECT MarketPrice FROM Cryptocurrencies WHERE Symbol = 'BTC'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $bitcoinData['current_price'] = $row['MarketPrice'];
    }
    $stmt->close();

    // Check if additional columns exist and fetch them
    $sql = "SHOW COLUMNS FROM Cryptocurrencies LIKE 'PriceChange24h'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $sql = "SELECT PriceChange24h, PriceChange7d, PriceChange30d, PriceChange1y, MarketCap, TotalVolume, CirculatingSupply, AllTimeHigh
                FROM Cryptocurrencies WHERE Symbol = 'BTC'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $bitcoinData['price_change_percentage_24h'] = $row['PriceChange24h'];
            $bitcoinData['price_change_percentage_7d'] = $row['PriceChange7d'];
            $bitcoinData['price_change_percentage_30d'] = $row['PriceChange30d'];
            $bitcoinData['price_change_percentage_1y'] = $row['PriceChange1y'];
            $bitcoinData['market_cap'] = $row['MarketCap'];
            $bitcoinData['total_volume'] = $row['TotalVolume'];
            $bitcoinData['circulating_supply'] = $row['CirculatingSupply'];
            $bitcoinData['ath'] = $row['AllTimeHigh'];
        }
        $stmt->close();
    }
}

$conn->close();

// Format numbers for display
function formatNumber($num, $decimals = 2) {
    if ($num === null || $num === 0) return 'N/A';
    if ($num >= 1e12) return '$' . number_format($num / 1e12, $decimals) . 'T';
    if ($num >= 1e9) return '$' . number_format($num / 1e9, $decimals) . 'B';
    if ($num >= 1e6) return '$' . number_format($num / 1e6, $decimals) . 'M';
    if ($num >= 1e3) return '$' . number_format($num / 1e3, $decimals) . 'K';
    return '$' . number_format($num, $decimals);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
    /* Existing styles remain unchanged */

    /* Dark mode styles */
    body.dark-mode {
        background-color: #1a1a1a;
        color: #e0e0e0;
    }

    body.dark-mode .sidebar {
        background-color: #2c2c2c;
    }

    body.dark-mode .main-content {
        background-color: #1a1a1a;
    }

    body.dark-mode .settings-form,
    body.dark-mode .value-card,
    body.dark-mode .distribution-chart,
    body.dark-mode .transactions-container,
    body.dark-mode .crypto-table-container {
        background-color: #2c2c2c;
        color: #e0e0e0;
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
    }

    body.dark-mode .section-header h2,
    body.dark-mode .settings-form label,
    body.dark-mode .transactions-table th,
    body.dark-mode .crypto-table th {
        color: #e0e0e0;
    }

    body.dark-mode .transactions-table td,
    body.dark-mode .crypto-table td {
        color: #e0e0e0;
        border-bottom: 1px solid #444;
    }

    body.dark-mode .value-card {
        background-color: #3a3a3a;
    }

    body.dark-mode .btn-primary {
        background-color: #7e6ba8;
    }

    body.dark-mode .btn-primary:hover {
        background-color: #6b5b95;
    }

    body.dark-mode .btn-outline {
        border-color: #555;
        color: #e0e0e0;
    }

    body.dark-mode .btn-outline:hover {
        background-color: #444;
    }

    body.dark-mode .alert-success {
        background-color: #2e7d32;
        color: #d4edda;
        border-color: #4caf50;
    }

    body.dark-mode .alert-error {
        background-color: #c62828;
        color: #ffcdd2;
        border-color: #ef5350;
    }
</style>
    <style>
        /* Additional styling for Recent Transactions to match dashboard UI */
        .recent-transactions {
            margin-top: 30px;
        }

        .recent-transactions .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-transactions .section-header h2 {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-transactions .transactions-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-x: auto;
        }

        .recent-transactions .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .recent-transactions .transactions-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .recent-transactions .transactions-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .recent-transactions .transactions-table tr:last-child td {
            border-bottom: none;
        }

        .recent-transactions .crypto-symbol {
            font-weight: 600;
            color: #5dade2;
        }

        .recent-transactions .positive {
            color: #28a745;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recent-transactions .negative {
            color: #dc3545;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recent-transactions .neutral {
            color: #17a2b8;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recent-transactions .btn-outline {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background-color: transparent;
            color: #555;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .recent-transactions .btn-outline:hover {
            background-color: #f5f7fa;
        }

        .recent-transactions .empty-message {
            text-align: center;
            margin-top: 40px;
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }

        /* Portfolio Summary Styling */
        .portfolio-summary {
            margin-top: 30px;
        }

        .portfolio-value {
            padding: 20px;
        }

        .value-card {
            background-color: #6b5b95;
            color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .value-card h3 {
            font-size: 1rem;
            margin: 0 0 10px;
            opacity: 0.8;
        }

        .value-card .value {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }

        .value-card .change {
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .portfolio-distribution {
            margin-top: 20px;
        }

        .distribution-chart {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-x: auto;
            text-align: center;
        }

        #portfolioDistributionChart {
            width: 100%;
            height: 180px;
            position: relative;
        }

        .distribution-legend {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .color-dot.btc { background-color: #f7931a; } /* Bitcoin */
        .color-dot.eth { background-color: #627eea; } /* Ethereum */
        .color-dot.other { background-color: #c3a634; } /* Dogecoin */

        /* Additional styling for price changes */
        .price-changes {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .price-change-item {
            font-size: 0.9rem;
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
                    <li class="active">
                        <a href="#" aria-current="page"><i class="fas fa-chart-line"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="portfolio.php"><i class="fas fa-wallet"></i> Portfolio</a>
                    </li>
                    <li>
                        <a href="buy.php"><i class="fas fa-shopping-cart"></i> Buy Crypto</a>
                    </li>
                    <li>
                        <a href="sell.php"><i class="fas fa-exchange-alt"></i> Sell Crypto</a>
                    </li>
                    <li>
                        <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
                    </li>
                    <li>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    </li>
                    <li>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="user.png" alt="Profile Picture" />
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
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search for cryptocurrencies..." aria-label="Search cryptocurrencies" />
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn btn-primary">
                        <a href="buy.php" style="text-decoration: none;color:white;"><i class="fas fa-plus"></i> Add Funds</a>
                    </button>
                </div>
            </header>

            <!-- Portfolio & Charts -->
            <div class="portfolio-chart-container">
                <section class="portfolio-summary">
                    <div class="section-header">
                        <h2>Your Portfolio</h2>
                        <button class="btn btn-outline btn-sm btn-refresh" aria-label="Refresh portfolio">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    
                    <div class="portfolio-value">
                        <div class="value-card">
                            <h3>Total Value</h3>
                            <p class="value">$<?php echo isset($totalValue) ? number_format($totalValue, 2) : '0.00'; ?></p>
                            <p class="change <?php echo $isPositive ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-caret-<?php echo $isPositive ? 'up' : 'down'; ?>"></i> 
                                $<?php echo number_format($portfolioChange, 2); ?> (<?php echo $portfolioChangePercent; ?>%)
                            </p>
                        </div>
                    </div>
                    <div class="portfolio-distribution">
                        <h3>Distribution</h3>
                        <div class="distribution-chart" id="portfolioDistribution">
                            <?php if ($totalValue <= 0): ?>
                                <div style="width: 100%; height: 180px; display: flex; align-items: center; justify-content: center; color: #888; font-size: 0.9rem;">
                                    No cryptocurrencies in your portfolio
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="distribution-legend">
                            <?php if ($totalValue > 0): ?>
                                <?php 
                                $colorClasses = ['btc', 'eth', 'other'];
                                
                                for ($i = 0; $i < count($displayCryptos); $i++) {
                                    $symbol = $displayCryptos[$i];
                                    if (isset($percentages[$symbol]) && $percentages[$symbol] > 0) {
                                        echo '<div class="legend-item">
                                            <span class="color-dot ' . $colorClasses[$i] . '"></span>
                                            <span>' . htmlspecialchars($portfolio[$symbol]['name']) . '</span>
                                            <span>' . $percentages[$symbol] . '%</span>
                                        </div>';
                                    }
                                }
                                ?>
                            <?php else: ?>
                                <p>No cryptocurrencies in your portfolio</p>
                            <?php endif; ?>
                        </div>
                        <!-- Hidden element to pass portfolio data to script.js -->
                        <div id="portfolioData"
                             style="display: none;"
                             data-labels='<?php echo json_encode($labels); ?>'
                             data-data='<?php echo json_encode($data); ?>'
                             data-colors='<?php echo json_encode($colors); ?>'></div>
                    </div>
                </section>

                <section class="chart-section">
                    <div class="section-header">
                        <div class="chart-selector">
                            <select id="cryptoSelector" aria-label="Select cryptocurrency">
                                <option value="">Loading cryptocurrencies...</option>
                            </select>
                            <p class="error-message" id="selectorError" style="display: none"></p>
                        </div>
                        <div class="time-filter">
                            <button class="active">24h</button>
                            <button>7d</button>
                            <button>30d</button>
                            <button>1y</button>
                        </div>
                    </div>
                    <div class="crypto-price-info">
                        <div class="price-header">
                            <img src="/assets/images/default-crypto.png" alt="Cryptocurrency logo" class="crypto-icon" />
                            <div>
                                <h3>Bitcoin</h3>
                                <span class="symbol">BTC</span>
                            </div>
                        </div>
                        <div class="price-value">
                            <h2><?php echo formatNumber($bitcoinData['current_price'], 2); ?></h2>
                            <div class="price-changes">
                                <div class="price-change-item <?php echo $bitcoinData['price_change_percentage_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-caret-<?php echo $bitcoinData['price_change_percentage_24h'] >= 0 ? 'up' : 'down'; ?>"></i> 
                                    24h: <?php echo abs($bitcoinData['price_change_percentage_24h']); ?>%
                                </div>
                                <div class="price-change-item <?php echo $bitcoinData['price_change_percentage_7d'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-caret-<?php echo $bitcoinData['price_change_percentage_7d'] >= 0 ? 'up' : 'down'; ?>"></i> 
                                    7d: <?php echo abs($bitcoinData['price_change_percentage_7d']); ?>%
                                </div>
                                <div class="price-change-item <?php echo $bitcoinData['price_change_percentage_30d'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-caret-<?php echo $bitcoinData['price_change_percentage_30d'] >= 0 ? 'up' : 'down'; ?>"></i> 
                                    30d: <?php echo abs($bitcoinData['price_change_percentage_30d']); ?>%
                                </div>
                                <div class="price-change-item <?php echo $bitcoinData['price_change_percentage_1y'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-caret-<?php echo $bitcoinData['price_change_percentage_1y'] >= 0 ? 'up' : 'down'; ?>"></i> 
                                    1y: <?php echo abs($bitcoinData['price_change_percentage_1y']); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="price-chart-container">
                        <canvas id="priceChart"></canvas>
                    </div>
                    <div class="price-stats">
                        <div class="stat">
                            <span class="label">Market Cap</span>
                            <span class="value"><?php echo formatNumber($bitcoinData['market_cap']); ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Volume (24h)</span>
                            <span class="value"><?php echo formatNumber($bitcoinData['total_volume']); ?></span>
                        </div>
                        <div class="stat">
                            <span class="label">Circulating Supply</span>
                            <span class="value"><?php echo number_format($bitcoinData['circulating_supply'] / 1e6, 2); ?>M BTC</span>
                        </div>
                        <div class="stat">
                            <span class="label">All-time High</span>
                            <span class="value"><?php echo formatNumber($bitcoinData['ath'], 2); ?></span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Top Cryptocurrencies -->
            <section class="top-cryptos">
                <div class="section-header">
                    <h2>Top Cryptocurrencies</h2>
                    <button class="btn btn-outline btn-sm">View All</button>
                </div>
                <div class="crypto-table-container">
                    <table class="crypto-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>24h %</th>
                                <th>7d %</th>
                                <th>Market Cap</th>
                                <th>Volume (24h)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="cryptoTableBody">
                            <tr>
                                <td colspan="8" class="loading-data">
                                    <div class="loading-spinner"></div>
                                    <p>Loading cryptocurrency data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Transactions -->
            <section class="recent-transactions">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Transactions</h2>
                    <a href="transactions.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="transactions-container">
                    <?php if (count($recentTransactions) === 0): ?>
                    <p class="empty-message">No recent transactions found.</p>
                    <?php else: ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Cryptocurrency</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $t): ?>
                            <tr>
                                <td data-label="Transaction ID"><?php echo htmlspecialchars($t['TransactionID']); ?></td>
                                <td data-label="Cryptocurrency">
                                    <span class="crypto-symbol"><?php echo htmlspecialchars($t['Symbol']); ?></span>
                                    <span> - <?php echo htmlspecialchars($t['Name']); ?></span>
                                </td>
                                <td data-label="Amount"><?php echo number_format($t['Amount'], 8); ?></td>
                                <td data-label="Type">
                                    <?php
                                    $typeClass = strtolower(trim($t['Type']));
                                    $class = '';
                                    $icon = '';
                                    if ($typeClass == 'buy') {
                                        $class = 'positive';
                                        $icon = '<i class="fas fa-arrow-down"></i>';
                                    } elseif ($typeClass == 'sell') {
                                        $class = 'negative';
                                        $icon = '<i class="fas fa-arrow-up"></i>';
                                    } else {
                                        $class = 'neutral';
                                        $icon = '<i class="fas fa-exchange-alt"></i>';
                                    }
                                    ?>
                                    <span class="<?php echo $class; ?>">
                                        <?php echo $icon . ' ' . ucfirst($t['Type']); ?>
                                    </span>
                                </td>
                                <td data-label="Date & Time">
                                    <?php
                                    $transactionTime = strtotime($t['Timestamp']);
                                    $currentTime = time();
                                    $timeDiff = abs($currentTime - $transactionTime); // Use abs to avoid negative values
                                    $timeAgo = '';
                                    if ($timeDiff < 60) {
                                        $timeAgo = $timeDiff . ' seconds ago';
                                    } elseif ($timeDiff < 3600) {
                                        $timeAgo = floor($timeDiff / 60) . ' mins ago';
                                    } elseif ($timeDiff < 86400) {
                                        $timeAgo = floor($timeDiff / 3600) . ' hours ago';
                                    } else {
                                        $timeAgo = floor($timeDiff / 86400) . ' days ago';
                                    }
                                    echo htmlspecialchars($timeAgo);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal for Buy/Sell Actions -->
    <div class="modal" id="actionModal" style="display: none">
        <div class="modal-content">
            <h2 id="modalTitle">Action</h2>
            <p id="modalMessage">Please confirm your action.</p>
            <div class="modal-actions">
                <button class="btn btn-primary" id="modalConfirm">Confirm</button>
                <button class="btn btn-outline" id="modalCancel">Cancel</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
