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
$pass = 'Rajath@813733652065';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$userID = $_SESSION['user_id'];
$sql = "SELECT UserName, Email FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Initialize variables for settings
$language = 'English'; // Default
$emailNotifications = 0; // Default: off
$pushNotifications = 0; // Default: off

// Fetch user settings (assuming a Settings table exists)
$sql = "SELECT Language,EmailNotifications, PushNotifications FROM Settings WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $language = $row['Language'] ?? 'English';
    $emailNotifications = $row['EmailNotifications'] ?? 0;
    $pushNotifications = $row['PushNotifications'] ?? 0;
}
$stmt->close();

// Handle form submission
$successMessage = '';
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newLanguage = $_POST['language'] ?? 'English';
    $new  = isset($_POST[' ']) ? 1 : 0;
    $newEmailNotifications = isset($_POST['emailNotifications']) ? 1 : 0;
    $newPushNotifications = isset($_POST['pushNotifications']) ? 1 : 0;

    // Validate input
    if (empty($newUsername) || empty($newEmail)) {
        $errorMessage = "Username and email are required.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format.";
    } else {
        // Update Users table
        $sql = "UPDATE Users SET UserName = ?, Email = ? WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $newUsername, $newEmail, $userID);
        $stmt->execute();
        $stmt->close();

        // Update or insert into Settings table
       $sql = "INSERT INTO Settings (UserID, Language, EmailNotifications, PushNotifications) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        Language = ?, EmailNotifications = ?, PushNotifications = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isiisii", 
    $userID, 
    $newLanguage, 
    $newEmailNotifications, 
    $newPushNotifications, 
    $newLanguage, 
    $newEmailNotifications, 
    $newPushNotifications
);
$stmt->execute();
$stmt->close();


        $successMessage = "Settings updated successfully.";
        // Update variables for display
        $user['UserName'] = $newUsername;
        $user['Email'] = $newEmail;
        $language = $newLanguage;
        $emailNotifications = $newEmailNotifications;
        $pushNotifications = $newPushNotifications;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings - CryptoTrack</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <style>
        .settings-container {
            margin-top: 30px;
        }

        .settings-container .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .settings-container .section-header h2 {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-form {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .settings-form .form-group {
            margin-bottom: 20px;
        }

        .settings-form label {
            display: block;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .settings-form input[type="text"],
        .settings-form input[type="email"],
        .settings-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #212529;
            box-sizing: border-box;
        }

        .settings-form select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23495057' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }

        .settings-form .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-form input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #6b5b95;
        }

        .settings-form .btn-primary {
            background-color: #6b5b95;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .settings-form .btn-primary:hover {
            background-color: #5a4a7a;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive styling */
        @media (max-width: 768px) {
            .settings-form {
                padding: 15px;
            }

            .settings-form .form-group {
                margin-bottom: 15px;
            }

            .settings-form label {
                font-size: 0.85rem;
            }

            .settings-form input[type="text"],
            .settings-form input[type="email"],
            .settings-form select {
                font-size: 0.85rem;
            }

            .settings-form .btn-primary {
                width: 100%;
                padding: 12px;
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
                        <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
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
                    <li class="active">
                        <a href="#" aria-current="page"><i class="fas fa-cog"></i> Settings</a>
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
                        <a href="buy.php" style="text-decoration: none;"><i class="fas fa-plus"></i> Add Funds</a>
                    </button>
                </div>
            </header>

            <!-- Settings Form -->
            <section class="settings-container">
                <div class="section-header">
                    <h2><i class="fas fa-cog"></i> Settings</h2>
                </div>
                <div class="settings-form">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="settings.php">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['UserName']); ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" name="language">
                                <option value="English" <?php echo $language === 'English' ? 'selected' : ''; ?>>English</option>
                                <option value="Spanish" <?php echo $language === 'Spanish' ? 'selected' : ''; ?>>Spanish</option>
                                <option value="French" <?php echo $language === 'French' ? 'selected' : ''; ?>>French</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notifications</label>
                            <div class="checkbox-group">
                                <input type="checkbox" id="emailNotifications" name="emailNotifications" <?php echo $emailNotifications ? 'checked' : ''; ?> />
                                <label for="emailNotifications">Email Notifications</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="pushNotifications" name="pushNotifications" <?php echo $pushNotifications ? 'checked' : ''; ?> />
                                <label for="pushNotifications">Push Notifications</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>