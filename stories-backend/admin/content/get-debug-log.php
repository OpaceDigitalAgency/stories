<?php
/**
 * Get Debug Log
 *
 * This script returns the contents of the debug log file for a specific source.
 */

// No authentication required for this script
// This is a direct access script

// Get the source ID
$sourceId = isset($_GET['source_id']) ? (int)$_GET['source_id'] : 0;

// Define the log file path
$logFile = __DIR__ . '/../../services/ReviewFetcher/debug/scrape-log.txt';

// Check if the log file exists
if (!file_exists($logFile)) {
    echo "No log file found.";
    exit;
}

// Read the last 100 lines of the log file
$lines = file($logFile);
$lines = array_slice($lines, -100);

// Output the lines
foreach ($lines as $line) {
    echo htmlspecialchars($line);
}
