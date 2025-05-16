<?php
/**
 * Scrape Log Viewer
 * 
 * This file provides direct access to the scrape log.
 */

// Set content type
header('Content-Type: text/plain; charset=utf-8');

// Define the log file path
$logFile = __DIR__ . '/scrape-log.txt';

// Check if the file exists
if (file_exists($logFile)) {
    // Output the file content
    readfile($logFile);
} else {
    echo "Scrape log file not found.";
}
