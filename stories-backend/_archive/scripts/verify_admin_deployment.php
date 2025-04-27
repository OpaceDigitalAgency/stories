<?php
/**
 * Verification script for JavaScript-free admin interface
 * This script checks that all components are properly deployed and JavaScript is blocked
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to check directory/file existence
function check_path($path, $type = 'file') {
    $exists = $type === 'dir' ? is_dir($path) : file_exists($path);
    $icon = $exists ? '✅' : '❌';
    echo "$icon Checking $path... " . ($exists ? 'Found' : 'Missing') . "\n";
    return $exists;
}

// Function to check security headers
function check_headers($url) {
    $headers = get_headers($url, 1);
    $required_headers = [
        'Content-Security-Policy' => 'script-src \'none\'',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff'
    ];
    
    foreach ($required_headers as $header => $value) {
        $found = false;
        foreach ($headers as $h => $v) {
            if (strcasecmp($h, $header) === 0) {
                $found = true;
                $icon = stripos($v, $value) !== false ? '✅' : '❌';
                echo "$icon Checking $header... " . ($icon === '✅' ? 'Correct' : 'Incorrect value') . "\n";
                break;
            }
        }
        if (!$found) {
            echo "❌ Checking $header... Missing\n";
        }
    }
}

echo "\nVerifying JavaScript-free Admin Interface Deployment\n";
echo "================================================\n\n";

echo "1. Checking Directory Structure\n";
echo "--------------------------\n";
check_path('admin', 'dir');
check_path('admin/includes', 'dir');
check_path('admin/assets/css', 'dir');
check_path('admin/content', 'dir');
check_path('admin/uploads', 'dir');

echo "\n2. Checking Core Files\n";
echo "------------------\n";
check_path('admin/includes/auth.php');
check_path('admin/includes/config.php');
check_path('admin/assets/css/main.css');
check_path('admin/.htaccess');

echo "\n3. Checking Content Management Pages\n";
echo "-------------------------------\n";
check_path('admin/content/stories.php');
check_path('admin/content/blog-posts.php');
check_path('admin/content/authors.php');
check_path('admin/content/tags.php');
check_path('admin/content/games.php');
check_path('admin/content/directory-items.php');
check_path('admin/content/ai-tools.php');
check_path('admin/content/media.php');

echo "\n4. Checking Security Headers\n";
echo "-----------------------\n";
check_headers('https://api.storiesfromtheweb.org/admin/login.php');

echo "\n5. Checking Database Connection\n";
echo "--------------------------\n";
try {
    require_once 'admin/includes/config.php';
    echo "✅ Database connection successful\n";
    
    // Check if admin user exists
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    echo ($adminCount > 0 ? '✅' : '❌') . " Admin user exists: " . 
         ($adminCount > 0 ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\nVerification Complete!\n";
echo "===================\n\n";

if (php_sapi_name() === 'cli') {
    echo "To complete deployment:\n";
    echo "1. Visit /admin/login.php in your browser\n";
    echo "2. Check browser console for any JavaScript errors\n";
    echo "3. Test login functionality\n";
    echo "4. Verify all CRUD operations\n";
} else {
    echo '<div style="font-family: monospace; white-space: pre;">';
    echo "To complete deployment:\n";
    echo "1. Check browser console - no JavaScript should be loading\n";
    echo "2. Test login functionality\n";
    echo "3. Verify all CRUD operations\n";
    echo "4. Test file uploads in media section\n";
    echo '</div>';
}