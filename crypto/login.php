<?php
session_start();
// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db_connect.php';

$error = '';

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $sql = "SELECT UserID, Name, PasswordHash FROM Users WHERE Email = ?";
        $stmt = $conn->prepare("SELECT UserID, PasswordHash FROM users WHERE Email = ?");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password (assuming passwords are hashed)
            if (password_verify($password, $user['PasswordHash'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['user_name'] = $user['Name'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CryptoTrack</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container {
            display: flex;
            min-height: 100vh;
            background: var(--color-bg);
        }
        
        .auth-branding {
            flex: 1;
            background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .auth-branding::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="none" width="100" height="100"/><path fill="rgba(255,255,255,0.1)" d="M30,20 L70,20 L50,60 Z"/></svg>');
            background-size: 100px 100px;
            opacity: 0.1;
        }
        
        .auth-branding h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            z-index: 1;
        }
        
        .auth-branding p {
            font-size: 1.1rem;
            max-width: 400px;
            text-align: center;
            margin-bottom: 2rem;
            z-index: 1;
        }
        
        .crypto-icons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
            z-index: 1;
        }
        
        .crypto-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .auth-form {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .auth-card {
            background: var(--color-card-bg);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .auth-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .auth-header h2 {
            font-size: 1.8rem;
            color: var(--color-text);
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            color: var(--color-text-light);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-text);
            font-weight: 500;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: 0.5rem;
            border: 1px solid var(--color-border);
            background-color: var(--color-input-bg);
            color: var(--color-text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-with-icon input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), 0.2);
            outline: none;
        }
        
        .input-with-icon .icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-light);
        }
        
        .auth-btn {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: none;
            background: var(--color-primary);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .auth-btn:hover {
            background: rgb(var(--color-primary-rgb));
            transform: translateY(-2px);
        }
        
        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .auth-links a {
            color: var(--color-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: var(--color-text-light);
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: var(--color-border);
        }
        
        .auth-divider span {
            padding: 0 1rem;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-input-bg);
            border: 1px solid var(--color-border);
            color: var(--color-text);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            background-color: var(--color-bg-light);
            transform: translateY(-2px);
        }
        
        .error-message {
            background-color: rgba(255, 0, 0, 0.1);
            color: #ff3333;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .auth-branding {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-branding">
            <h1><i class="fas fa-coins"></i> CryptoTrack</h1>
            <p>Your all-in-one cryptocurrency portfolio tracker. Monitor, analyze, and optimize your crypto investments.</p>
            <div class="crypto-icons">
                <div class="crypto-icon"><i class="fab fa-bitcoin"></i></div>
                <div class="crypto-icon"><i class="fab fa-ethereum"></i></div>
                <div class="crypto-icon"><i class="fas fa-chart-line"></i></div>
                <div class="crypto-icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        
        <div class="auth-form">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Welcome Back</h2>
                    <p>Log in to your CryptoTrack account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope icon"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock icon"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="auth-btn">
                        <i class="fas fa-sign-in-alt"></i> Log In
                    </button>
                    
                    <div class="auth-links">
                        <a href="forgot-password.php">Forgot Password?</a>
                        <a href="register.php">Create Account</a>
                    </div>
                </form>
                
                <div class="auth-divider">
                    <span>OR</span>
                </div>
                
                <div class="social-login">
                    <button class="social-btn">
                        <i class="fab fa-google"></i>
                    </button>
                    <button class="social-btn">
                        <i class="fab fa-facebook-f"></i>
                    </button>
                    <button class="social-btn">
                        <i class="fab fa-apple"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>