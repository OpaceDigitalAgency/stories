<?php
/**
 * Simple Discovery Test - No Authentication Required
 */

// Include the discovery engine directly
require_once '../admin/content/book-discovery/BookDiscoveryEngine.php';

// Mock database connection for testing
$db = null;

try {
    // Test URL
    $url = 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/';
    
    echo "<h1>Discovery Test</h1>";
    echo "<p>Testing URL: " . htmlspecialchars($url) . "</p>";
    
    // Create discovery engine
    $discoveryEngine = new BookDiscoveryEngine($db);
    
    echo "<p>Discovery engine created successfully.</p>";
    
    // Discover books
    echo "<p>Starting discovery...</p>";
    $books = $discoveryEngine->discoverFromURL($url);
    
    echo "<h2>Results:</h2>";
    echo "<p>Found " . count($books) . " books</p>";
    
    if (!empty($books)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Title</th><th>Author</th><th>Age Range</th><th>Year</th></tr>";
        
        foreach ($books as $book) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($book['title'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($book['author'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($book['age_range'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($book['year'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Raw Data:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($books, true)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>