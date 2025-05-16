<?php
/**
 * Check Fetchers
 * 
 * This script checks if all the required fetcher files exist and are accessible.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../includes/db-connect.php';

// Define the fetcher files to check
$fetcherFiles = [
    'ReviewFetcherInterface.php',
    'AbstractReviewFetcher.php',
    'ReviewFetcherFactory.php',
    'GoogleBooksReviewFetcher.php',
    'OpenLibraryReviewFetcher.php',
    'AmazonReviewFetcher.php',
    'GoodreadsReviewFetcher.php',
    'KirkusReviewsFetcher.php',
    'SLJReviewFetcher.php',
    'StoriesReviewFetcher.php'
];

// Base directory for fetchers
$fetcherDir = '../../services/ReviewFetcher/';

// Check if files exist
echo "<h1>Checking Review Fetcher Files</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>File</th><th>Status</th><th>Size</th><th>Last Modified</th></tr>";

foreach ($fetcherFiles as $file) {
    $filePath = $fetcherDir . $file;
    $exists = file_exists($filePath);
    $size = $exists ? filesize($filePath) : 'N/A';
    $lastModified = $exists ? date('Y-m-d H:i:s', filemtime($filePath)) : 'N/A';
    
    echo "<tr>";
    echo "<td>{$file}</td>";
    echo "<td>" . ($exists ? "<span style='color:green'>Exists</span>" : "<span style='color:red'>Missing</span>") . "</td>";
    echo "<td>{$size} bytes</td>";
    echo "<td>{$lastModified}</td>";
    echo "</tr>";
}

echo "</table>";

// Check class definitions
echo "<h1>Checking Class Definitions</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>File</th><th>Class Name</th><th>Status</th></tr>";

foreach ($fetcherFiles as $file) {
    $filePath = $fetcherDir . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $matches);
        $className = isset($matches[1]) ? $matches[1] : 'Not found';
        
        // Check if the class name matches the file name (without .php)
        $expectedClassName = str_replace('.php', '', $file);
        $classNameMatches = ($className === $expectedClassName);
        
        echo "<tr>";
        echo "<td>{$file}</td>";
        echo "<td>{$className}</td>";
        echo "<td>" . ($classNameMatches ? "<span style='color:green'>Matches</span>" : "<span style='color:red'>Mismatch</span>") . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Check ReviewFetcherFactory
echo "<h1>Checking ReviewFetcherFactory</h1>";

if (file_exists($fetcherDir . 'ReviewFetcherFactory.php')) {
    $content = file_get_contents($fetcherDir . 'ReviewFetcherFactory.php');
    
    echo "<h2>Case Statements</h2>";
    echo "<pre>";
    
    // Extract the switch statement
    if (preg_match('/switch\s*\(.*?\)\s*{(.*?)}/s', $content, $matches)) {
        $switchContent = $matches[1];
        
        // Extract case statements
        preg_match_all('/case\s+\'(.*?)\':(.*?)break;/s', $switchContent, $caseMatches, PREG_SET_ORDER);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Source Name</th><th>Fetcher Class</th></tr>";
        
        foreach ($caseMatches as $caseMatch) {
            $sourceName = $caseMatch[1];
            $caseContent = $caseMatch[2];
            
            // Extract fetcher class
            preg_match('/new\s+(\w+)/s', $caseContent, $fetcherMatch);
            $fetcherClass = isset($fetcherMatch[1]) ? $fetcherMatch[1] : 'Not found';
            
            echo "<tr>";
            echo "<td>{$sourceName}</td>";
            echo "<td>{$fetcherClass}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "Switch statement not found in ReviewFetcherFactory.php";
    }
    
    echo "</pre>";
} else {
    echo "<p>ReviewFetcherFactory.php not found</p>";
}

// Include footer
require_once '../includes/footer.php';
