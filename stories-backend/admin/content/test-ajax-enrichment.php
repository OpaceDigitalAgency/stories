<?php
/**
 * Test the AJAX enrichment files and functions
 */

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

echo "<h1>Testing AJAX Enrichment Files</h1>";

// Also test if the function file exists
echo "<h2>Function File Check:</h2>";
$functionFile = 'book-import-validate/functions/data-enrichment-functions.php';
if (file_exists($functionFile)) {
    echo "<p>✅ Function file exists: $functionFile</p>";

    // Try to include it
    try {
        require_once $functionFile;
        echo "<p>✅ Function file included successfully</p>";

        if (function_exists('validateISBNOnGoodreads')) {
            echo "<p>✅ validateISBNOnGoodreads function exists</p>";
        } else {
            echo "<p>❌ validateISBNOnGoodreads function NOT found</p>";
        }

        if (function_exists('getEnrichedBookData')) {
            echo "<p>✅ getEnrichedBookData function exists</p>";
        } else {
            echo "<p>❌ getEnrichedBookData function NOT found</p>";
        }

    } catch (Exception $e) {
        echo "<p>❌ Error including function file: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Function file NOT found: $functionFile</p>";
}

// Test AJAX file directly
echo "<h2>AJAX File Check:</h2>";
$ajaxFile = 'book-import-validate/ajax/data-enrichment-ajax.php';
if (file_exists($ajaxFile)) {
    echo "<p>✅ AJAX file exists: $ajaxFile</p>";

    // Test by simulating a POST request
    $_POST['action'] = 'test';

    echo "<p>Testing AJAX file with action=test...</p>";

    // Capture output
    ob_start();
    try {
        include $ajaxFile;
        $output = ob_get_contents();
        ob_end_clean();

        echo "<p>✅ AJAX file executed successfully</p>";
        echo "<p><strong>Output:</strong></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";

    } catch (Exception $e) {
        ob_end_clean();
        echo "<p>❌ Error executing AJAX file: " . $e->getMessage() . "</p>";
    }

} else {
    echo "<p>❌ AJAX file NOT found: $ajaxFile</p>";
}
