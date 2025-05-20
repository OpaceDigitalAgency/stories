<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Page variables
$pageTitle = 'Login';
$currentPage = 'login';

/**
 * Login Page
 *
 * This page handles user authentication.
 */

// Include database connection
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

require_once '../simple_auth.php';

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if already logged in
if (SimpleAuth::check()) {
    // Redirect to dashboard
    header("Location: ./dashboard.php");
    exit;
}

// Handle login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($user = SimpleAuth::login($email, $password)) {
        header("Location: ./dashboard.php");
        exit;
    } else {
        $error = 'Invalid email or password';
        error_log("Login failed for email: $email");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Stories From The Web Admin</title>
    <link rel="icon" type="image/png" href="../public/favicon.png">
    <link rel="shortcut icon" type="image/png" href="../public/favicon.png">
    <link rel="stylesheet" href="assets/css/enhanced-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .login-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
        }
        .site-logo {
            margin-bottom: 20px;
        }
        .site-logo img {
            max-width: 150px;
            height: auto;
        }
        .login-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .login-subtitle {
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #444;
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            border-color: #4361ee;
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        .login-button {
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s;
        }
        .login-button:hover {
            background-color: #3a56d4;
        }
        .login-footer {
            margin-top: 25px;
            font-size: 12px;
            color: #888;
        }
        .error {
            background-color: #fee;
            color: #e53e3e;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="site-logo">
                <div class="logo" style="display: inline-block; width: 50px; height: 50px; background-color: #4361ee; color: white; border-radius: 8px; line-height: 50px; font-size: 24px; font-weight: bold;">S</div>
            </div>

            <h1 class="login-title">Admin Login</h1>
            <p class="login-subtitle">Enter your credentials to access the admin panel</p>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input"
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="login-button">Sign In</button>
            </form>

            <div class="login-footer">
                Stories from the Web Admin Panel &copy; <?php echo date('Y'); ?>
            </div>
        </div>
    </div>


