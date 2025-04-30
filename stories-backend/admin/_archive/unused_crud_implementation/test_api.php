<?php
/**
 * API Connection Test Script
 * 
 * This script tests the connection to the API server and displays the results.
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/ApiClient.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize API client
$apiClient = new ApiClient(API_URL);

// Display configuration
echo "<h1>API Connection Test</h1>";
echo "<h2>Configuration</h2>";
echo "<p><strong>Environment:</strong> " . ENVIRONMENT . "</p>";
echo "<p><strong>API URL:</strong> " . API_URL . "</p>";

// Test API connection
echo "<h2>Connection Test</h2>";
echo "<p>Testing connection to API server...</p>";

// Try to get a list of stories
$response = $apiClient->get('stories', ['pageSize' => 1]);

if ($response) {
    echo '<div style="background-color: #dff0d8; color: #3c763d; padding: 15px; border-radius: 4px; margin-bottom: 20px;">';
    echo '<h3 style="margin-top: 0;">Connection Successful!</h3>';
    echo '<p>Successfully connected to the API server.</p>';
    echo '<pre>' . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . '</pre>';
    echo '</div>';
} else {
    echo '<div style="background-color: #f2dede; color: #a94442; padding: 15px; border-radius: 4px; margin-bottom: 20px;">';
    echo '<h3 style="margin-top: 0;">Connection Failed!</h3>';
    
    $error = $apiClient->getFormattedError();
    if ($error) {
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($error) . '</p>';
    } else {
        echo '<p>Failed to connect to the API server. No specific error was returned.</p>';
    }
    
    $lastError = $apiClient->getLastError();
    if ($lastError) {
        echo '<h4>Error Details:</h4>';
        echo '<pre>' . htmlspecialchars(json_encode($lastError, JSON_PRETTY_PRINT)) . '</pre>';
    }
    
    echo '</div>';
}

// Display PHP info for debugging
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>cURL Enabled: " . (function_exists('curl_version') ? 'Yes' : 'No') . "</p>";
if (function_exists('curl_version')) {
    $curlInfo = curl_version();
    echo "<p>cURL Version: " . $curlInfo['version'] . "</p>";
    echo "<p>SSL Version: " . $curlInfo['ssl_version'] . "</p>";
}
?>

<p><a href="index.php">Back to Admin Dashboard</a></p>