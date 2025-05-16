<?php
/**
 * Direct Log View
 * 
 * This script provides direct access to log files without any headers or footers.
 */

// No authentication required for this script
// No headers or footers

// Define the debug directory
$debugDir = dirname(dirname(dirname(__FILE__))) . '/services/ReviewFetcher/debug';

// Get the file name from the query string
$fileName = isset($_GET['file']) ? basename($_GET['file']) : '';
$filePath = $debugDir . '/' . $fileName;

// Check if the file exists
if (!empty($fileName) && file_exists($filePath) && is_file($filePath)) {
    // Get file extension
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Set appropriate content type
    if ($extension === 'html') {
        header('Content-Type: text/html; charset=utf-8');
    } elseif ($extension === 'log' || $extension === 'txt') {
        header('Content-Type: text/plain; charset=utf-8');
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
    }
    
    // Output the file content
    readfile($filePath);
    exit;
} else {
    // Set content type
    header('Content-Type: text/plain; charset=utf-8');
    
    // Show error message
    echo "File not found or invalid file name.";
    exit;
}
