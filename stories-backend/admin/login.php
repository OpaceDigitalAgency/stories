<?php
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stories Admin</title>
    <link rel="stylesheet" href="assets/css/modern-admin.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .login-card {
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
        }

        .site-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .site-logo img {
            max-width: 180px;
            height: auto;
        }

        .login-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--gray-900);
        }

        .login-subtitle {
            text-align: center;
            color: var(--gray-600);
            margin-bottom: 2rem;
            font-size: var(--font-size-sm);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-input {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: var(--font-size-base);
            line-height: 1.5;
            color: var(--text-color);
            background-color: white;
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            transition: border-color var(--transition-fast) ease-in-out, box-shadow var(--transition-fast) ease-in-out;
        }

        .form-input:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        .login-button {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: var(--font-size-base);
            font-weight: 600;
            text-align: center;
            color: white;
            background-color: var(--primary-color);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background-color var(--transition-fast) ease-in-out;
        }

        .login-button:hover {
            background-color: var(--primary-hover);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
    </style>
</head>
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
</body>
</html>