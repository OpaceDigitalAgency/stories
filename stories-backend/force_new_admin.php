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

// 0. Create required directories
$dirs = [
    __DIR__ . '/admin',
    __DIR__ . '/admin/includes',
    __DIR__ . '/admin/assets/css',
    __DIR__ . '/admin/content',
    __DIR__ . '/admin/logs'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        output("Created directory: $dir", 'success');
    }
}

// 1. Create or update config.php
$configPath = __DIR__ . '/admin/includes/config.php';

// Default database credentials for the Stories platform
$config = <<<'EOT'
<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'stories_fromtheweb';
$db_user = 'stories_fromtheweb';
$db_pass = 'stories_fromtheweb';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Site configuration
define('SITE_URL', 'https://api.storiesfromtheweb.org');
define('ADMIN_EMAIL', 'admin@storiesfromtheweb.org');
define('SESSION_LIFETIME', 7200); // 2 hours

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => 'api.storiesfromtheweb.org',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}
EOT;

safe_write_file($configPath, $config);

// 2. Force .htaccess rules
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

# Protect session
<IfModule mod_headers.c>
    Header edit Set-Cookie ^(.*)$ $1;HttpOnly;Secure;SameSite=Strict
</IfModule>
EOT;

safe_write_file(__DIR__ . '/admin/.htaccess', $htaccess);

// 3. Replace index.php with login redirect
$index = <<<'EOT'
<?php
header("Location: login.php");
exit;
EOT;

safe_write_file(__DIR__ . '/admin/index.php', $index);

// 4. Create login.php
$login = file_get_contents(__DIR__ . '/admin/login.php');
safe_write_file(__DIR__ . '/admin/login.php', $login);

// 5. Create dashboard.php
$dashboard = file_get_contents(__DIR__ . '/admin/dashboard.php');
safe_write_file(__DIR__ . '/admin/dashboard.php', $dashboard);

// 6. Create logout.php
$logout = file_get_contents(__DIR__ . '/admin/logout.php');
safe_write_file(__DIR__ . '/admin/logout.php', $logout);

// 7. Create main.css
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
    background: #f8f9fa;
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
    text-decoration: none;
    display: inline-block;
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

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Tables */
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    background: white;
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
EOT;

safe_write_file(__DIR__ . '/admin/assets/css/main.css', $css);

output("\nForced new admin interface!", 'success');
output("Please:\n1. Clear your browser cache\n2. Visit /admin/login.php\n3. Verify JavaScript is blocked\n4. Test login functionality", 'info');