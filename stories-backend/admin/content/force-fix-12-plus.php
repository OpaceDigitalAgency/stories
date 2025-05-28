<?php
/**
 * Force fix for 12+ values - aggressive approach
 */

require_once '../includes/db-connect.php';

echo "<h1>🔧 Force Fix 12+ Values</h1>";
echo "<p><strong>Aggressive fix to catch ALL 12+ variations</strong></p>";

try {
    $db->beginTransaction();
    
    // 1. Find ALL books with 12+ in age_range (any variation)
    echo "<h2>🔍 Finding 12+ Books</h2>";
    
    $stmt = $db->query("
        SELECT directory_item_id, age_range, reading_level, 
               LENGTH(age_range) as length,
               HEX(age_range) as hex_value
        FROM books 
        WHERE age_range LIKE '%12+%'
        ORDER BY directory_item_id
    ");
    $books12Plus = $stmt->fetchAll();
    
    echo "<h3>Books with 12+ in age_range:</h3>";
    if (empty($books12Plus)) {
        echo "<p>❌ No books found with 12+ in age_range!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Book ID</th><th>Age Range</th><th>Reading Level</th><th>Length</th><th>Hex Value</th></tr>";
        foreach ($books12Plus as $book) {
            echo "<tr>";
            echo "<td>{$book['directory_item_id']}</td>";
            echo "<td style='background: #fff3cd; font-family: monospace;'>[{$book['age_range']}]</td>";
            echo "<td>{$book['reading_level']}</td>";
            echo "<td>{$book['length']}</td>";
            echo "<td style='font-family: monospace; font-size: 10px;'>{$book['hex_value']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Apply multiple fix approaches
    echo "<h2>🔄 Applying Multiple Fix Approaches</h2>";
    
    $totalFixed = 0;
    
    // Approach 1: LIKE pattern
    $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years' WHERE age_range LIKE '%12+%'");
    $stmt->execute();
    $fixed1 = $stmt->rowCount();
    $totalFixed += $fixed1;
    echo "<p>✅ Approach 1 (LIKE '%12+%'): <strong>$fixed1 books</strong></p>";
    
    // Approach 2: TRIM and exact match
    $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years' WHERE TRIM(age_range) = '12+'");
    $stmt->execute();
    $fixed2 = $stmt->rowCount();
    $totalFixed += $fixed2;
    echo "<p>✅ Approach 2 (TRIM = '12+'): <strong>$fixed2 books</strong></p>";
    
    // Approach 3: REPLACE to remove spaces then match
    $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years' WHERE REPLACE(age_range, ' ', '') = '12+'");
    $stmt->execute();
    $fixed3 = $stmt->rowCount();
    $totalFixed += $fixed3;
    echo "<p>✅ Approach 3 (REPLACE spaces): <strong>$fixed3 books</strong></p>";
    
    // Approach 4: Regular expression (if MySQL supports it)
    try {
        $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years' WHERE age_range REGEXP '^[[:space:]]*12\\+[[:space:]]*$'");
        $stmt->execute();
        $fixed4 = $stmt->rowCount();
        $totalFixed += $fixed4;
        echo "<p>✅ Approach 4 (REGEXP): <strong>$fixed4 books</strong></p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Approach 4 (REGEXP) not supported: " . $e->getMessage() . "</p>";
    }
    
    // 3. Now fix the reading levels for books with 11-14 years
    $stmt = $db->prepare("UPDATE books SET reading_level = 'Advanced Reader' WHERE age_range = '11-14 years' AND (reading_level IS NULL OR reading_level = '' OR reading_level = 'Unknown')");
    $stmt->execute();
    $readingFixed = $stmt->rowCount();
    echo "<p>✅ Set reading level for 11-14 years books: <strong>$readingFixed books</strong></p>";
    
    // 4. Check if we got them all
    echo "<h2>🔍 Verification</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM books WHERE age_range LIKE '%12+%'");
    $remaining = $stmt->fetchColumn();
    
    if ($remaining == 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h3>🎉 SUCCESS!</h3>";
        echo "<p>✅ All 12+ values have been converted!</p>";
        echo "<p><strong>Total books fixed: $totalFixed</strong></p>";
        echo "<p><strong>Reading levels updated: $readingFixed</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>⚠️ Still $remaining books with 12+ values</h3>";
        echo "<p>Let's try a direct update by ID...</p>";
        echo "</div>";
        
        // Get the specific IDs and update them directly
        $stmt = $db->query("SELECT directory_item_id FROM books WHERE age_range LIKE '%12+%'");
        $remainingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($remainingIds)) {
            $placeholders = str_repeat('?,', count($remainingIds) - 1) . '?';
            $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years', reading_level = 'Advanced Reader' WHERE directory_item_id IN ($placeholders)");
            $stmt->execute($remainingIds);
            $directFixed = $stmt->rowCount();
            echo "<p>✅ Direct update by ID: <strong>$directFixed books</strong></p>";
        }
    }
    
    // 5. Final verification
    $stmt = $db->query("SELECT COUNT(*) FROM books WHERE age_range LIKE '%12+%'");
    $finalRemaining = $stmt->fetchColumn();
    
    echo "<h3>Final Check: $finalRemaining books still have 12+ values</h3>";
    
    $db->commit();
    
    // Show current state
    echo "<h2>📊 Current State After Fix</h2>";
    $stmt = $db->query("
        SELECT age_range, COUNT(*) as count 
        FROM books 
        WHERE age_range IS NOT NULL AND age_range != ''
        GROUP BY age_range 
        ORDER BY count DESC
    ");
    $currentState = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Age Range</th><th>Count</th></tr>";
    foreach ($currentState as $range) {
        $highlight = (strpos($range['age_range'], '12+') !== false) ? 'background: #f8d7da;' : '';
        echo "<tr style='$highlight'><td>{$range['age_range']}</td><td>{$range['count']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<p><a href="comprehensive-cleanup.php">← Back to Cleanup Page</a></p>
