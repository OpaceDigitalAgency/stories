<?php
// Start session
session_start();

// Output session information
echo "<h1>Session Test</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Status: " . session_status() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n\nCookies:\n";
print_r($_COOKIE);
echo "</pre>";

// Set a test session variable
$_SESSION['test_value'] = 'This is a test value set at ' . date('Y-m-d H:i:s');
echo "<p>Set a test session value. Refresh the page to see if it persists.</p>";
?>
