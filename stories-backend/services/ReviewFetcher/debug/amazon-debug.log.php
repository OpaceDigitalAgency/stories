<?php
/**
 * Amazon Debug Log Viewer
 * 
 * This file provides direct access to the Amazon debug log.
 */

// Set content type
header('Content-Type: text/plain; charset=utf-8');

// Define the log file path
$logFile = __DIR__ . '/amazon-debug.log';

// Check if the file exists
if (file_exists($logFile)) {
    // Output the file content
    readfile($logFile);
} else {
    echo "Amazon debug log file not found.";
}
