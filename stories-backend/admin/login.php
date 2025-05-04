<?php

// Include header
include 'includes/header.php';


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
    header("Location: dashboard.php");
    exit;
}

// Handle login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($user = SimpleAuth::login($email, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Invalid email or password';
        error_log("Login failed for email: $email");
    }
}
?>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="site-logo">
                <img src="/stories_from_the_web_transparent.png" alt="Stories from the Web">
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

// Include footer
include 'includes/footer.php';
