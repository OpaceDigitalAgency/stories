<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Reset the opcache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "OPCache reset result: " . ($result ? "Success" : "Failed");
} else {
    echo "OPCache function not available";
}

// Display PHP info
echo "<br>PHP Version: " . phpversion();
echo "<br>OPCache Enabled: " . (function_exists('opcache_get_status') && opcache_get_status() ? "Yes" : "No");
?>