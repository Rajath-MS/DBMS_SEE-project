<?php
session_start();
// Removed the redirect for logged in users to allow anyone to access the registration page

require_once 'db_connect.php';

$error = '';
$success = '';

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $check_sql = "SELECT UserID FROM Users WHERE Email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already exists. Please use a different email or login.";
        } else {
            // Hash password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_sql = "INSERT INTO Users (UserName, Email, Password, RegisterDate) VALUES (?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;
                
                // Create a wallet for the user
                $wallet_sql = "INSERT INTO Wallets (UserID, RegisterDate) VALUES (?, NOW())";
                $wallet_stmt = $conn->prepare($wallet_sql);
                $wallet_stmt->bind_param("i", $user_id);
                $wallet_stmt->execute();
                
                $success = "Registration successful! You can now login.";
                
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = "Registration failed. Please try again later. " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CryptoTrack</title>
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
            max-width: 450px;
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
            justify-content: center;
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
        
        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--color-text-light);
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.2rem;
        }
        
        .requirement i {
            font-size: 0.7rem;
        }
        
        .error-message {
            background-color: rgba(255, 0, 0, 0.1);
            color: #ff3333;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .success-message {
            background-color: rgba(0, 255, 0, 0.1);
            color: #33cc33;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .user-notice {
            background-color: rgba(255, 165, 0, 0.1);
            color: #ff9900;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            <p>Join thousands of investors tracking and managing their cryptocurrency portfolios in one place.</p>
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
                    <h2>Create Account</h2>
                    <p>Sign up for your CryptoTrack account</p>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-notice">
                    <i class="fas fa-info-circle"></i> 
                    You're currently logged in. Creating a new account will not affect your current session.
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user icon"></i>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
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
                            <input type="password" id="password" name="password" placeholder="Create password" required>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement">
                                <i class="fas fa-circle"></i> At least 8 characters
                            </div>
                            <div class="requirement">
                                <i class="fas fa-circle"></i> Contains numbers and letters
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="auth-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                    
                    <div class="auth-links">
                        <span>Already have an account? <a href="login.php">Log In</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Password validation script
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const requirements = document.querySelectorAll('.requirement i');
            
            passwordInput.addEventListener('input', validatePassword);
            
            function validatePassword() {
                const value = passwordInput.value;
                
                // Check length
                if (value.length >= 8) {
                    requirements[0].className = 'fas fa-check-circle';
                    requirements[0].style.color = '#33cc33';
                } else {
                    requirements[0].className = 'fas fa-circle';
                    requirements[0].style.color = '';
                }
                
                // Check for numbers and letters
                if (/\d/.test(value) && /[a-zA-Z]/.test(value)) {
                    requirements[1].className = 'fas fa-check-circle';
                    requirements[1].style.color = '#33cc33';
                } else {
                    requirements[1].className = 'fas fa-circle';
                    requirements[1].style.color = '';
                }
            }
        });
    </script>
</body>
</html>