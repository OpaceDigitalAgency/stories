<?php
/**
 * FINAL Age Range and Reading Level Synchronization Fix
 * This script will completely standardize all age ranges and reading levels in the books table
 */

require_once '../includes/db-connect.php';

echo "<h1>🔧 Final Age Range & Reading Level Synchronization</h1>";
echo "<p><strong>This will completely standardize all age ranges and reading levels in the books table.</strong></p>";

try {
    $db->beginTransaction();
    
    // 1. First, let's see what we're working with
    echo "<h2>📊 Current Database State</h2>";
    
    $currentAgeRanges = $db->query("
        SELECT age_range, COUNT(*) as count 
        FROM books 
        WHERE age_range IS NOT NULL AND age_range != '' 
        GROUP BY age_range 
        ORDER BY count DESC
    ")->fetchAll();
    
    echo "<h3>Current Age Ranges in Books Table:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Age Range</th><th>Book Count</th></tr>";
    foreach ($currentAgeRanges as $range) {
        echo "<tr><td>{$range['age_range']}</td><td>{$range['count']}</td></tr>";
    }
    echo "</table>";
    
    $currentReadingLevels = $db->query("
        SELECT reading_level, COUNT(*) as count 
        FROM books 
        WHERE reading_level IS NOT NULL AND reading_level != '' 
        GROUP BY reading_level 
        ORDER BY count DESC
    ")->fetchAll();
    
    echo "<h3>Current Reading Levels in Books Table:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Reading Level</th><th>Book Count</th></tr>";
    foreach ($currentReadingLevels as $level) {
        echo "<tr><td>{$level['reading_level']}</td><td>{$level['count']}</td></tr>";
    }
    echo "</table>";
    
    // 2. Define the COMPLETE standardized mappings
    echo "<h2>🎯 Applying Standardized Mappings</h2>";
    
    // Age range mappings - map ALL current values to standard values
    $ageRangeMapping = [
        // Current database values → Standard values
        '9-10 years' => '9-10 years',      // Keep (31 books)
        '8-9 years' => '8-9 years',        // Keep (16 books)  
        '7-8 years' => '7-8 years',        // Keep (12 books)
        '10-11 years' => '10-11 years',    // Keep (3 books)
        '11-14 years' => '11-14 years',    // Keep (1 book)
        'Unknown' => 'Unknown',            // Keep (3 books)
        
        // Convert legacy values
        '12+' => '11-14 years',            // Convert (3 books)
        '13+' => '11-14 years',
        '14+' => '14-16 years',
        '15+' => '14-16 years',
        '16+' => '16-18 years',
        '17+' => '16-18 years',
        '18+' => '18+ years',
        
        // Other potential values
        'Teen' => '14-16 years',
        'Young Adult' => '14-16 years',
        'Adult' => '18+ years',
        'All Ages' => '5-6 years'
    ];
    
    // Reading level mappings - map ALL current values to standard values
    $readingLevelMapping = [
        // Current values that need standardization
        'Transitional Reader (7-8 years)' => 'Transitional Reader',
        'Fluent Reader (8-11 years)' => 'Fluent Reader',
        'Advanced Reader (11-14 years)' => 'Advanced Reader',
        'Beginning Reader (4-5 years)' => 'Beginning Reader',
        'Early Reader (5-6 years)' => 'Early Reader',
        'Developing Reader (6-7 years)' => 'Developing Reader',
        'Pre-literacy (Sensory)' => 'Pre-literacy (Sensory)',
        'Pre-literacy (Naming)' => 'Pre-literacy (Naming)',
        'Pre-literacy (Mimicry)' => 'Pre-literacy (Mimicry)',
        'Early Pre-reader' => 'Early Pre-reader',
        'Proficient Reader' => 'Proficient Reader',
        
        // Standard values (keep as is)
        'Pre-literacy (Sensory)' => 'Pre-literacy (Sensory)',
        'Pre-literacy (Naming)' => 'Pre-literacy (Naming)',
        'Pre-literacy (Mimicry)' => 'Pre-literacy (Mimicry)',
        'Early Pre-reader' => 'Early Pre-reader',
        'Beginning Reader' => 'Beginning Reader',
        'Early Reader' => 'Early Reader',
        'Developing Reader' => 'Developing Reader',
        'Transitional Reader' => 'Transitional Reader',
        'Fluent Reader' => 'Fluent Reader',
        'Advanced Reader' => 'Advanced Reader',
        'Proficient Reader' => 'Proficient Reader'
    ];
    
    // 3. Apply age range mappings
    echo "<h3>🔄 Updating Age Ranges</h3>";
    $totalAgeUpdates = 0;
    
    foreach ($ageRangeMapping as $oldRange => $newRange) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE age_range = ?");
        $stmt->execute([$oldRange]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $updateStmt = $db->prepare("UPDATE books SET age_range = ? WHERE age_range = ?");
            $updateStmt->execute([$newRange, $oldRange]);
            $updated = $updateStmt->rowCount();
            $totalAgeUpdates += $updated;
            
            echo "<p>✅ Updated '$oldRange' → '$newRange' ($updated books)</p>";
        }
    }
    
    // 4. Apply reading level mappings
    echo "<h3>🔄 Updating Reading Levels</h3>";
    $totalReadingUpdates = 0;
    
    foreach ($readingLevelMapping as $oldLevel => $newLevel) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE reading_level = ?");
        $stmt->execute([$oldLevel]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $updateStmt = $db->prepare("UPDATE books SET reading_level = ? WHERE reading_level = ?");
            $updateStmt->execute([$newLevel, $oldLevel]);
            $updated = $updateStmt->rowCount();
            $totalReadingUpdates += $updated;
            
            echo "<p>✅ Updated '$oldLevel' → '$newLevel' ($updated books)</p>";
        }
    }
    
    // 5. Now synchronize age ranges and reading levels to match each other
    echo "<h3>🔗 Synchronizing Age Ranges with Reading Levels</h3>";
    
    // Age range to reading level mapping
    $ageToReadingMap = [
        '0-12 months' => 'Pre-literacy (Sensory)',
        '12-24 months' => 'Pre-literacy (Naming)',
        '2-3 years' => 'Pre-literacy (Mimicry)',
        '3-4 years' => 'Early Pre-reader',
        '4-5 years' => 'Beginning Reader',
        '5-6 years' => 'Early Reader',
        '6-7 years' => 'Developing Reader',
        '7-8 years' => 'Transitional Reader',
        '8-9 years' => 'Fluent Reader',
        '9-10 years' => 'Fluent Reader',
        '10-11 years' => 'Fluent Reader',
        '11-14 years' => 'Advanced Reader',
        '14-16 years' => 'Advanced Reader',
        '16-18 years' => 'Advanced Reader',
        '18+ years' => 'Proficient Reader'
    ];
    
    $syncUpdates = 0;
    foreach ($ageToReadingMap as $ageRange => $expectedReading) {
        // Update books that have this age range but wrong/missing reading level
        $stmt = $db->prepare("
            UPDATE books 
            SET reading_level = ? 
            WHERE age_range = ? 
            AND (reading_level IS NULL OR reading_level = '' OR reading_level != ?)
        ");
        $stmt->execute([$expectedReading, $ageRange, $expectedReading]);
        $updated = $stmt->rowCount();
        $syncUpdates += $updated;
        
        if ($updated > 0) {
            echo "<p>🔗 Synced $updated books with age range '$ageRange' to reading level '$expectedReading'</p>";
        }
    }
    
    $db->commit();
    
    // 6. Show final results
    echo "<h2>✅ Synchronization Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>Age range updates:</strong> $totalAgeUpdates books</li>";
    echo "<li><strong>Reading level updates:</strong> $totalReadingUpdates books</li>";
    echo "<li><strong>Synchronization updates:</strong> $syncUpdates books</li>";
    echo "<li><strong>Total changes:</strong> " . ($totalAgeUpdates + $totalReadingUpdates + $syncUpdates) . " updates</li>";
    echo "</ul>";
    echo "</div>";
    
    // 7. Show new state
    echo "<h3>📊 New Database State</h3>";
    
    $newAgeRanges = $db->query("
        SELECT age_range, COUNT(*) as count 
        FROM books 
        WHERE age_range IS NOT NULL AND age_range != '' 
        GROUP BY age_range 
        ORDER BY count DESC
    ")->fetchAll();
    
    echo "<h4>Updated Age Ranges:</h4>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Age Range</th><th>Book Count</th></tr>";
    foreach ($newAgeRanges as $range) {
        echo "<tr><td>{$range['age_range']}</td><td>{$range['count']}</td></tr>";
    }
    echo "</table>";
    
    echo "<p><strong>✅ All age ranges and reading levels are now standardized and synchronized!</strong></p>";
    echo "<p><a href='comprehensive-cleanup.php'>← Back to Cleanup Page</a></p>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
