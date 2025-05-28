<?php
/**
 * Debug script to see exact age range values including whitespace
 */

require_once '../includes/db-connect.php';

echo "<h1>🔍 Debug Age Range Values</h1>";

try {
    // Get all unique age range values with their exact characters
    $stmt = $db->query("
        SELECT 
            age_range,
            COUNT(*) as count,
            LENGTH(age_range) as length,
            ASCII(SUBSTRING(age_range, 1, 1)) as first_char_ascii,
            ASCII(SUBSTRING(age_range, -1, 1)) as last_char_ascii
        FROM books 
        WHERE age_range IS NOT NULL AND age_range != '' 
        GROUP BY age_range 
        ORDER BY count DESC
    ");
    $ranges = $stmt->fetchAll();
    
    echo "<h2>📊 Exact Age Range Values (with character analysis)</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Age Range</th><th>Count</th><th>Length</th><th>First Char ASCII</th><th>Last Char ASCII</th><th>Hex Dump</th></tr>";
    
    foreach ($ranges as $range) {
        $hexDump = bin2hex($range['age_range']);
        $firstChar = $range['first_char_ascii'] == 32 ? 'SPACE' : chr($range['first_char_ascii']);
        $lastChar = $range['last_char_ascii'] == 32 ? 'SPACE' : chr($range['last_char_ascii']);
        
        echo "<tr>";
        echo "<td style='background: #f8f9fa; font-family: monospace;'>[{$range['age_range']}]</td>";
        echo "<td>{$range['count']}</td>";
        echo "<td>{$range['length']}</td>";
        echo "<td>{$firstChar} ({$range['first_char_ascii']})</td>";
        echo "<td>{$lastChar} ({$range['last_char_ascii']})</td>";
        echo "<td style='font-family: monospace; font-size: 10px;'>{$hexDump}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show specific books with the problematic 12+ value
    echo "<h2>📚 Books with ' 12+' value</h2>";
    $stmt = $db->prepare("
        SELECT b.directory_item_id, di.title, b.age_range, b.reading_level
        FROM books b
        JOIN directory_items di ON b.directory_item_id = di.id
        WHERE b.age_range LIKE '%12+%'
        ORDER BY di.title
    ");
    $stmt->execute();
    $books = $stmt->fetchAll();
    
    if ($books) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Book ID</th><th>Title</th><th>Age Range</th><th>Reading Level</th></tr>";
        foreach ($books as $book) {
            echo "<tr>";
            echo "<td>{$book['directory_item_id']}</td>";
            echo "<td>" . htmlspecialchars($book['title']) . "</td>";
            echo "<td style='background: #fff3cd; font-family: monospace;'>[{$book['age_range']}]</td>";
            echo "<td>" . htmlspecialchars($book['reading_level']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No books found with 12+ in age range.</p>";
    }
    
    // Test the exact update query
    echo "<h2>🧪 Test Update Query</h2>";
    
    // Try different variations
    $testValues = ['12+', ' 12+', '12+ ', ' 12+ '];
    
    foreach ($testValues as $testValue) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE age_range = ?");
        $stmt->execute([$testValue]);
        $count = $stmt->fetchColumn();
        
        $hexDump = bin2hex($testValue);
        echo "<p>Testing exact match for '[{$testValue}]' (hex: {$hexDump}): <strong>{$count} books</strong></p>";
    }
    
    // Try TRIM approach
    $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE TRIM(age_range) = ?");
    $stmt->execute(['12+']);
    $trimCount = $stmt->fetchColumn();
    echo "<p>Testing TRIM(age_range) = '12+': <strong>{$trimCount} books</strong></p>";
    
    // Try LIKE approach
    $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE age_range LIKE '%12+%'");
    $stmt->execute();
    $likeCount = $stmt->fetchColumn();
    echo "<p>Testing age_range LIKE '%12+%': <strong>{$likeCount} books</strong></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<p><a href="comprehensive-cleanup.php">← Back to Cleanup Page</a></p>
