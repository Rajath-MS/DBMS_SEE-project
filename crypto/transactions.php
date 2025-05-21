<?php
// Database connection setup
$host = 'localhost';
$db = 'crypto_db';
$user = 'root';        // Change to your DB user
$pass = '';    // Change to your DB password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Hardcoded user ID = 1
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

// Fetch all transactions for this wallet
$sql = "SELECT t.TransactionID, c.Symbol, c.Name, t.Amount, t.Type, t.Timestamp
        FROM Transactions t
        JOIN Cryptocurrencies c ON t.CryptoID = c.CryptoID
        WHERE t.WalletID = ?
        ORDER BY t.Timestamp DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $walletID);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CryptoTrack - Transaction History</title>
    <link rel="stylesheet" href="styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
        /* Base styles */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .container {
            display: flex;
        }
        
        /* Sidebar styles - UPDATED to match white sidebar from buy.php */
        .sidebar {
            width: 260px;
            background-color: #ffffff;
            color: #333333;
            height: 100vh;
            position: fixed;
            transition: transform 0.3s ease;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            background-color: #ffffff;
            border-bottom: 1px solid #eaeaea;
        }
        
        .sidebar-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #333333;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .sidebar-header h1 i {
            color:#6c5ce7;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #555555;
            text-decoration: none;
            transition: background-color 0.2s;
            gap: 10px;
        }
        
        .sidebar-nav li a:hover,
        .sidebar-nav li.active a {
            background-color: #f5f7fa;
            color: #6c5ce7;
        }
        
        .sidebar-nav li.active a {
            border-left: 4px solid #6c5ce7;
            font-weight: 600;
        }
        
        .sidebar-footer {
            padding: 20px;
            position: absolute;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
            background-color: #ffffff;
            border-top: 1px solid #eaeaea;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info h4 {
            margin: 0;
            font-size: 0.9rem;
            color: #333333;
        }
        
        .user-info p {
            margin: 5px 0 0;
            font-size: 0.75rem;
            color: #777777;
        }
        
        /* Main content styles */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 260px;
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: #555;
        }
        
        .btn-primary {
            background-color: #6c5ce7;
            color: white;
            border: none;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #6c5ce7;
        }
        
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #2c3e50;
            cursor: pointer;
        }
        
        /* Transactions table styles */
        .transactions-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
            overflow-x: auto;
        }
        
        .transactions-container h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .transactions-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
            font-weight: 600;
        }
        
        .transactions-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
        }
        
        .transactions-table tr:last-child td {
            border-bottom: none;
        }
        
        .transactions-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Updated transaction type styling to match dashboard */
        .positive {
            color: #28a745;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .negative {
            color: #dc3545;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .neutral {
            color: #17a2b8;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .crypto-symbol {
            font-weight: 600;
            color: #6c5ce7;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.show-sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .transactions-container {
                padding: 15px;
            }
            
            .transactions-table th,
            .transactions-table td {
                padding: 10px;
            }
            
            /* Responsive table */
            .transactions-table thead {
                display: none;
            }
            
            .transactions-table tbody tr {
                display: block;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                margin-bottom: 10px;
                background-color: #fff;
            }
            
            .transactions-table tbody td {
                display: flex;
                justify-content: space-between;
                text-align: right;
                border-bottom: 1px solid #e9ecef;
                padding: 12px 15px;
            }
            
            .transactions-table tbody td:before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: #495057;
            }
            
            .transactions-table tbody td:last-child {
                border-bottom: none;
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
                    <h1 style="color:#6c5ce7"><i class="fas fa-coins"></i>CryptoTrack</h1>
                </a>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="portfolio.php">
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
                    <li class="active">
                        <a href="#" aria-current="page">
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
                    <img src="user.png" alt="Profile Picture" />
                    <div class="user-info">
                        <h4>Rajath M S</h4>
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
                    <button class="btn btn-outline" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                    <a href="buy.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Buy Crypto
                    </a>
                </div>
            </header>

            <!-- Transactions Section -->
            <div class="transactions-container">
                <h2><i class="fas fa-history"></i> Transaction History</h2>
                
                <?php if (count($transactions) === 0): ?>
                <p style="text-align:center; margin-top:50px; font-size:1.2rem; color:#6c757d;">No transactions found.</p>
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
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td data-label="Transaction ID"><?= htmlspecialchars($t['TransactionID']) ?></td>
                            <td data-label="Cryptocurrency">
                                <span class="crypto-symbol"><?= htmlspecialchars($t['Symbol']) ?></span>
                                <span> - <?= htmlspecialchars($t['Name']) ?></span>
                            </td>
                            <td data-label="Amount"><?= number_format($t['Amount'], 8) ?></td>
                            <td data-label="Type">
                                <?php 
                                // Map transaction types to dashboard style classes
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
                                <span class="<?= $class ?>">
                                    <?= $icon ?> <?= ucfirst($t['Type']) ?>
                                </span>
                            </td>
                            <td data-label="Date & Time"><?= htmlspecialchars($t['Timestamp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Toggle sidebar on mobile
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show-sidebar');
    });
    </script>
</body>
</html>
