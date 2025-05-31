<?php
/**
 * Test AJAX Endpoint
 * Simple test to verify the book-discovery-ajax.php endpoint is working
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../../admin/includes/auth-check.php';

echo "<h1>Testing AJAX Endpoint</h1>";

// Test URL
$testUrl = 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/';

echo "<h2>Testing discover_all action</h2>";
echo "<p>URL: $testUrl</p>";

// Simulate POST data
$_POST['action'] = 'discover_all';
$_POST['url'] = $testUrl;
$_POST['age_filter'] = '';

// Capture output
ob_start();

try {
    include 'book-discovery-ajax.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "<h3>Raw Output:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Try to decode JSON
    $decoded = json_decode($output, true);
    if ($decoded) {
        echo "<h3>Decoded JSON:</h3>";
        echo "<pre>" . print_r($decoded, true) . "</pre>";
    } else {
        echo "<h3>JSON Decode Error:</h3>";
        echo "<p>Could not decode JSON response</p>";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>