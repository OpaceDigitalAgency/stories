<?php
/**
 * Get Debug Log
 * 
 * This script returns the contents of the debug log file for a specific source.
 */

// Include necessary files
require_once '../includes/auth.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied";
    exit;
}

// Get the source ID
$sourceId = isset($_GET['source_id']) ? (int)$_GET['source_id'] : 0;

// Define the log file path
$logFile = __DIR__ . '/../../services/ReviewFetcher/debug/scrape-log.txt';

// Check if the log file exists
if (!file_exists($logFile)) {
    echo "No log file found.";
    exit;
}

// Read the log file
$logContent = file_get_contents($logFile);

// Return the log content
echo htmlspecialchars($logContent);
