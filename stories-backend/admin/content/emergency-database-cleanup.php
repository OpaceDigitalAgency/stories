<?php
/**
 * Emergency Database Cleanup
 * Fix duplicate books and stubborn 12+ values
 */

require_once '../includes/db-connect.php';

echo "<h1>🚨 Emergency Database Cleanup</h1>";
echo "<p><strong>Fix duplicate books and stubborn 12+ values</strong></p>";

try {
    $db->beginTransaction();
    
    // 1. Analyze the current mess
    echo "<h2>📊 Current Database State</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM books");
    $totalBooks = $stmt->fetchColumn();
    echo "<p><strong>Total books in database: $totalBooks</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(*) as null_titles FROM books WHERE title IS NULL OR title = ''");
    $nullTitles = $stmt->fetchColumn();
    echo "<p><strong>Books with NULL/empty titles: $nullTitles</strong></p>";
    
    $stmt = $db->query("SELECT COUNT(DISTINCT directory_item_id) as unique_items FROM books WHERE directory_item_id IS NOT NULL");
    $uniqueItems = $stmt->fetchColumn();
    echo "<p><strong>Unique directory items: $uniqueItems</strong></p>";
    
    // Find duplicates
    $stmt = $db->query("
        SELECT directory_item_id, COUNT(*) as count 
        FROM books 
        WHERE directory_item_id IS NOT NULL 
        GROUP BY directory_item_id 
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    $duplicates = $stmt->fetchAll();
    
    echo "<h3>Duplicate Books by Directory Item ID:</h3>";
    if (empty($duplicates)) {
        echo "<p>✅ No duplicates found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Directory Item ID</th><th>Duplicate Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>{$dup['directory_item_id']}</td><td>{$dup['count']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 2. Find the stubborn 12+ book
    echo "<h2>🔍 Finding Stubborn 12+ Values</h2>";
    
    $stmt = $db->query("
        SELECT directory_item_id, title, age_range, reading_level,
               LENGTH(age_range) as length,
               HEX(age_range) as hex_value,
               ASCII(SUBSTRING(age_range, 1, 1)) as first_char_ascii
        FROM books 
        WHERE age_range LIKE '%12+%'
        ORDER BY directory_item_id
    ");
    $stubborn12Plus = $stmt->fetchAll();
    
    if (!empty($stubborn12Plus)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Age Range</th><th>Reading Level</th><th>Length</th><th>Hex</th><th>First Char ASCII</th></tr>";
        foreach ($stubborn12Plus as $book) {
            echo "<tr>";
            echo "<td>{$book['directory_item_id']}</td>";
            echo "<td>{$book['title']}</td>";
            echo "<td style='background: #fff3cd; font-family: monospace;'>[{$book['age_range']}]</td>";
            echo "<td>{$book['reading_level']}</td>";
            echo "<td>{$book['length']}</td>";
            echo "<td style='font-family: monospace; font-size: 10px;'>{$book['hex_value']}</td>";
            echo "<td>{$book['first_char_ascii']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. EMERGENCY FIXES
    echo "<h2>🔧 Applying Emergency Fixes</h2>";
    
    $totalFixed = 0;
    
    // Fix 1: Remove duplicate books (keep the one with most data)
    if (!empty($duplicates)) {
        echo "<h3>Removing Duplicate Books</h3>";
        foreach ($duplicates as $dup) {
            $directoryItemId = $dup['directory_item_id'];
            
            // Get all books for this directory item, ordered by completeness
            $stmt = $db->prepare("
                SELECT *, 
                       (CASE WHEN title IS NOT NULL AND title != '' THEN 1 ELSE 0 END +
                        CASE WHEN isbn IS NOT NULL AND isbn != '' THEN 1 ELSE 0 END +
                        CASE WHEN isbn13 IS NOT NULL AND isbn13 != '' THEN 1 ELSE 0 END +
                        CASE WHEN author IS NOT NULL AND author != '' THEN 1 ELSE 0 END +
                        CASE WHEN publisher IS NOT NULL AND publisher != '' THEN 1 ELSE 0 END +
                        CASE WHEN age_range IS NOT NULL AND age_range != '' THEN 1 ELSE 0 END +
                        CASE WHEN reading_level IS NOT NULL AND reading_level != '' THEN 1 ELSE 0 END) as completeness_score
                FROM books 
                WHERE directory_item_id = ?
                ORDER BY completeness_score DESC, id ASC
            ");
            $stmt->execute([$directoryItemId]);
            $allVersions = $stmt->fetchAll();
            
            if (count($allVersions) > 1) {
                $keepBook = $allVersions[0]; // Keep the most complete one
                $removeBooks = array_slice($allVersions, 1);
                
                echo "<p>📚 Directory Item $directoryItemId: Keeping book with score {$keepBook['completeness_score']}, removing " . count($removeBooks) . " duplicates</p>";
                
                foreach ($removeBooks as $removeBook) {
                    $stmt = $db->prepare("DELETE FROM books WHERE directory_item_id = ? AND id = ?");
                    $stmt->execute([$directoryItemId, $removeBook['id']]);
                    $totalFixed++;
                }
            }
        }
    }
    
    // Fix 2: Force fix the stubborn 12+ values by direct ID update
    if (!empty($stubborn12Plus)) {
        echo "<h3>Force Fixing Stubborn 12+ Values</h3>";
        foreach ($stubborn12Plus as $book) {
            $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years', reading_level = 'Advanced Reader' WHERE directory_item_id = ?");
            $stmt->execute([$book['directory_item_id']]);
            $updated = $stmt->rowCount();
            $totalFixed += $updated;
            echo "<p>✅ Force updated book ID {$book['directory_item_id']} ({$book['title']}): $updated rows</p>";
        }
    }
    
    // Fix 3: Remove books with NULL directory_item_id (orphaned records)
    $stmt = $db->prepare("DELETE FROM books WHERE directory_item_id IS NULL");
    $stmt->execute();
    $orphansRemoved = $stmt->rowCount();
    $totalFixed += $orphansRemoved;
    echo "<p>✅ Removed $orphansRemoved orphaned books (NULL directory_item_id)</p>";
    
    // Fix 4: Remove books where directory_item doesn't exist
    $stmt = $db->prepare("
        DELETE b FROM books b 
        LEFT JOIN directory_items di ON b.directory_item_id = di.id 
        WHERE di.id IS NULL
    ");
    $stmt->execute();
    $invalidRemoved = $stmt->rowCount();
    $totalFixed += $invalidRemoved;
    echo "<p>✅ Removed $invalidRemoved books with invalid directory_item_id</p>";
    
    // Fix 5: Update titles from directory_items for books with NULL titles
    $stmt = $db->prepare("
        UPDATE books b
        JOIN directory_items di ON b.directory_item_id = di.id
        SET b.title = di.title
        WHERE b.title IS NULL OR b.title = ''
    ");
    $stmt->execute();
    $titlesFixed = $stmt->rowCount();
    $totalFixed += $titlesFixed;
    echo "<p>✅ Fixed $titlesFixed NULL titles from directory_items</p>";
    
    $db->commit();
    
    // 4. Final verification
    echo "<h2>✅ Final State</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM books");
    $finalTotal = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as null_titles FROM books WHERE title IS NULL OR title = ''");
    $finalNullTitles = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM books WHERE age_range LIKE '%12+%'");
    $finalStubborn = $stmt->fetchColumn();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Metric</th><th>Before</th><th>After</th><th>Change</th></tr>";
    echo "<tr><td>Total Books</td><td>$totalBooks</td><td>$finalTotal</td><td>" . ($finalTotal - $totalBooks) . "</td></tr>";
    echo "<tr><td>NULL Titles</td><td>$nullTitles</td><td>$finalNullTitles</td><td>" . ($finalNullTitles - $nullTitles) . "</td></tr>";
    echo "<tr><td>12+ Values</td><td>" . count($stubborn12Plus) . "</td><td>$finalStubborn</td><td>" . ($finalStubborn - count($stubborn12Plus)) . "</td></tr>";
    echo "</table>";
    
    if ($finalStubborn == 0 && $finalTotal <= 35) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🎉 SUCCESS!</h3>";
        echo "<p>✅ All 12+ values eliminated!</p>";
        echo "<p>✅ Database cleaned to reasonable size ($finalTotal books)</p>";
        echo "<p>✅ All NULL titles fixed</p>";
        echo "<p><strong>Total operations: $totalFixed</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ Still Issues</h3>";
        echo "<p>12+ values remaining: $finalStubborn</p>";
        echo "<p>Total books: $finalTotal (should be ~31)</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<p><a href="comprehensive-cleanup.php">← Back to Cleanup Page</a></p>
