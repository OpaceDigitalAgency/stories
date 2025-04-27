<?php
/**
 * CREATE PURE HTML ADMIN
 * 
 * This script creates a JavaScript-free admin interface using:
 * - Pure HTML forms with direct POST submissions
 * - CSS-only navigation and UI components
 * - Simple session-based authentication
 * - Server-side processing
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to output messages
function output($message, $type = 'info') {
    $class = match($type) {
        'success' => 'success',
        'error' => 'error',
        'warning' => 'warning',
        default => 'info'
    };
    echo "<div class='$class'>$message</div>\n";
}

// Find admin directory
$adminDir = __DIR__ . '/admin';
if (!is_dir($adminDir)) {
    mkdir($adminDir, 0755, true);
    output("Created admin directory", 'success');
}

// Create directory structure
$directories = [
    '/includes',
    '/assets/css',
    '/content'
];

foreach ($directories as $dir) {
    $path = $adminDir . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        output("Created directory: $dir", 'success');
    }
}

// Create auth.php
$authContent = <<<'PHP'
<?php
session_start();

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Session timeout after 2 hours
        if (time() - $_SESSION['last_activity'] > 7200) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}
PHP;

file_put_contents($adminDir . '/includes/auth.php', $authContent);
output("Created auth.php", 'success');

// Create main.css
$cssContent = <<<'CSS'
/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    background: #f8f9fa;
    color: #333;
}

/* Layout */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Navigation */
.nav {
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.nav-list {
    list-style: none;
    display: flex;
    padding: 0 20px;
}

.nav-item {
    position: relative;
}

.nav-link {
    display: block;
    padding: 15px 20px;
    color: #333;
    text-decoration: none;
}

.nav-link:hover {
    background: #f8f9fa;
}

/* Dropdown using CSS only */
.dropdown {
    position: relative;
}

.dropdown-content {
    display: none;
    position: absolute;
    background: #fff;
    min-width: 200px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1;
}

.dropdown:hover .dropdown-content {
    display: block;
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
}

.form-submit {
    background: #4a6cf7;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.form-submit:hover {
    background: #3451b2;
}

/* Messages */
.success, .error, .warning, .info {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Tables */
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.table th {
    background: #f8f9fa;
    font-weight: bold;
}

/* Cards */
.card {
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    padding: 20px;
}

.card-title {
    font-size: 1.25rem;
    margin-bottom: 15px;
    color: #333;
}

/* Tabs using CSS only */
.tabs {
    margin-bottom: 20px;
}

.tab-list {
    list-style: none;
    display: flex;
    border-bottom: 1px solid #dee2e6;
}

.tab-item {
    margin-bottom: -1px;
}

.tab-link {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    border: 1px solid transparent;
}

.tab-link:target,
.tab-link:hover {
    border-color: #dee2e6 #dee2e6 #fff;
    background: #fff;
}

.tab-content {
    display: none;
    padding: 20px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-top: none;
}

.tab-content:target {
    display: block;
}
CSS;

file_put_contents($adminDir . '/assets/css/main.css', $cssContent);
output("Created main.css", 'success');

// Create login.php
$loginContent = <<<'HTML'
<?php
require_once 'includes/auth.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $auth = new Auth($db);
        if ($auth->login($_POST['username'], $_POST['password'])) {
            header('Location: /admin/index.php');
            exit;
        }
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
    <link rel="stylesheet" href="/admin/assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="card" style="max-width: 400px; margin: 100px auto;">
            <h1 class="card-title">Login</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/admin/login.php">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <button type="submit" class="form-submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
HTML;

file_put_contents($adminDir . '/login.php', $loginContent);
output("Created login.php", 'success');

// Create .htaccess to block JavaScript
$htaccessContent = <<<'HTACCESS'
# Block all JavaScript files
<FilesMatch "\.js$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block inline JavaScript execution
<IfModule mod_headers.c>
    Header set Content-Security-Policy "script-src 'none';"
</IfModule>

# Protect sensitive files
<FilesMatch "^(auth|config|functions)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Enable session protection
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
</IfModule>
HTACCESS;

file_put_contents($adminDir . '/.htaccess', $htaccessContent);
output("Created .htaccess with security rules", 'success');

output("\nPure HTML admin interface has been created successfully!", 'success');
output("Next steps:");
output("1. Update database connection in includes/config.php");
output("2. Create content management pages in content/");
output("3. Test the login system");
