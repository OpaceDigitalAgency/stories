<?php
/**
 * Force New Admin Interface
 * This script directly replaces the old admin interface files with the new ones
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to output messages
function output($message, $type = 'info') {
    $colors = [
        'info' => "\033[0m",
        'success' => "\033[32m",
        'error' => "\033[31m",
        'warning' => "\033[33m"
    ];
    
    $reset = "\033[0m";
    echo $colors[$type] . $message . $reset . "\n";
}

// Function to safely write file
function safe_write_file($path, $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Create backup
    if (file_exists($path)) {
        $backup = $path . '.bak.' . date('YmdHis');
        copy($path, $backup);
        output("Created backup: $backup", 'info');
    }
    
    if (file_put_contents($path, $content)) {
        output("Updated: $path", 'success');
        return true;
    } else {
        output("Failed to write: $path", 'error');
        return false;
    }
}

// 1. Force .htaccess rules
$htaccess = <<<'EOT'
# Block all JavaScript files
<FilesMatch "\.js$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block inline JavaScript execution
<IfModule mod_headers.c>
    Header set Content-Security-Policy "script-src 'none';"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
</IfModule>

# Protect sensitive files
<FilesMatch "^(auth|config|functions)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Force login page
DirectoryIndex login.php

# Redirect old paths
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^index\.php$ /admin/login.php [L,R=301]
    RewriteRule ^dashboard\.php$ /admin/login.php [L,R=301]
</IfModule>
EOT;

safe_write_file(__DIR__ . '/admin/.htaccess', $htaccess);

// 2. Replace index.php with login redirect
$index = <<<'EOT'
<?php
header("Location: login.php");
exit;
EOT;

safe_write_file(__DIR__ . '/admin/index.php', $index);

// 3. Create login.php if it doesn't exist
$login = <<<'EOT'
<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Handle login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/config.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['last_activity'] = time();
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stories Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .site-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .site-logo img {
            max-width: 200px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="site-logo">
            <img src="/stories_from_the_web_transparent.png" alt="Stories from the Web">
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="form-submit" style="width: 100%;">Login</button>
        </form>
    </div>
</body>
</html>
EOT;

safe_write_file(__DIR__ . '/admin/login.php', $login);

// 4. Create main.css
$css = <<<'EOT'
/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    color: #333;
}

/* Forms */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-submit {
    background: #4a6cf7;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.form-submit:hover {
    background: #3451b2;
}

/* Messages */
.error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.success {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
}
EOT;

safe_write_file(__DIR__ . '/admin/assets/css/main.css', $css);

output("\nForced new admin interface!", 'success');
output("Please:\n1. Clear your browser cache\n2. Visit /admin/login.php\n3. Verify JavaScript is blocked\n4. Test login functionality", 'info');