<?php
/**
 * Simple, direct age range fix - no fancy logic, just direct updates
 */

require_once '../includes/db-connect.php';

echo "<h1>🔧 Simple Age Range Fix</h1>";
echo "<p><strong>Direct SQL updates to fix age range and reading level issues</strong></p>";

try {
    $db->beginTransaction();
    
    // 1. Show current state
    echo "<h2>📊 Current State</h2>";
    
    $stmt = $db->query("
        SELECT 
            COALESCE(age_range, 'NULL') as age_range,
            COUNT(*) as count 
        FROM books 
        GROUP BY age_range 
        ORDER BY count DESC
    ");
    $currentAges = $stmt->fetchAll();
    
    echo "<h3>Current Age Ranges:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Age Range</th><th>Count</th></tr>";
    foreach ($currentAges as $age) {
        echo "<tr><td>{$age['age_range']}</td><td>{$age['count']}</td></tr>";
    }
    echo "</table>";
    
    $stmt = $db->query("
        SELECT 
            COALESCE(reading_level, 'NULL') as reading_level,
            COUNT(*) as count 
        FROM books 
        GROUP BY reading_level 
        ORDER BY count DESC
    ");
    $currentReading = $stmt->fetchAll();
    
    echo "<h3>Current Reading Levels:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Reading Level</th><th>Count</th></tr>";
    foreach ($currentReading as $reading) {
        echo "<tr><td>{$reading['reading_level']}</td><td>{$reading['count']}</td></tr>";
    }
    echo "</table>";
    
    // 2. Apply direct fixes
    echo "<h2>🔄 Applying Direct Fixes</h2>";
    
    $totalUpdates = 0;
    
    // Fix 1: Convert 12+ to 11-14 years (handle any whitespace)
    $stmt = $db->prepare("UPDATE books SET age_range = '11-14 years' WHERE TRIM(age_range) = '12+'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    $totalUpdates += $updated;
    echo "<p>✅ Fixed 12+ → 11-14 years: <strong>$updated books</strong></p>";
    
    // Fix 2: Set reading level for books that now have 11-14 years age range but no reading level
    $stmt = $db->prepare("UPDATE books SET reading_level = 'Advanced Reader' WHERE age_range = '11-14 years' AND (reading_level IS NULL OR reading_level = '')");
    $stmt->execute();
    $updated = $stmt->rowCount();
    $totalUpdates += $updated;
    echo "<p>✅ Set reading level for 11-14 years books: <strong>$updated books</strong></p>";
    
    // Fix 3: Standardize reading levels that have age suffixes
    $readingLevelFixes = [
        'Transitional Reader (7-8 years)' => 'Transitional Reader',
        'Fluent Reader (8-11 years)' => 'Fluent Reader',
        'Advanced Reader (11-14 years)' => 'Advanced Reader',
        'Beginning Reader (4-5 years)' => 'Beginning Reader',
        'Early Reader (5-6 years)' => 'Early Reader',
        'Developing Reader (6-7 years)' => 'Developing Reader'
    ];
    
    foreach ($readingLevelFixes as $old => $new) {
        $stmt = $db->prepare("UPDATE books SET reading_level = ? WHERE reading_level = ?");
        $stmt->execute([$new, $old]);
        $updated = $stmt->rowCount();
        $totalUpdates += $updated;
        if ($updated > 0) {
            echo "<p>✅ Standardized '$old' → '$new': <strong>$updated books</strong></p>";
        }
    }
    
    // Fix 4: Set NULL age ranges to 'Unknown'
    $stmt = $db->prepare("UPDATE books SET age_range = 'Unknown' WHERE age_range IS NULL OR age_range = ''");
    $stmt->execute();
    $updated = $stmt->rowCount();
    $totalUpdates += $updated;
    echo "<p>✅ Set NULL age ranges to 'Unknown': <strong>$updated books</strong></p>";
    
    // Fix 5: Set NULL reading levels to 'Unknown'
    $stmt = $db->prepare("UPDATE books SET reading_level = 'Unknown' WHERE reading_level IS NULL OR reading_level = ''");
    $stmt->execute();
    $updated = $stmt->rowCount();
    $totalUpdates += $updated;
    echo "<p>✅ Set NULL reading levels to 'Unknown': <strong>$updated books</strong></p>";
    
    // Fix 6: Synchronize specific age ranges with reading levels
    $ageSyncs = [
        '7-8 years' => 'Transitional Reader',
        '8-9 years' => 'Fluent Reader',
        '9-10 years' => 'Fluent Reader',
        '10-11 years' => 'Fluent Reader',
        '11-14 years' => 'Advanced Reader'
    ];
    
    foreach ($ageSyncs as $ageRange => $readingLevel) {
        $stmt = $db->prepare("UPDATE books SET reading_level = ? WHERE age_range = ? AND reading_level != ?");
        $stmt->execute([$readingLevel, $ageRange, $readingLevel]);
        $updated = $stmt->rowCount();
        $totalUpdates += $updated;
        if ($updated > 0) {
            echo "<p>🔗 Synced '$ageRange' with '$readingLevel': <strong>$updated books</strong></p>";
        }
    }
    
    $db->commit();
    
    // 3. Show final state
    echo "<h2>✅ Final State</h2>";
    
    $stmt = $db->query("
        SELECT 
            COALESCE(age_range, 'NULL') as age_range,
            COUNT(*) as count 
        FROM books 
        GROUP BY age_range 
        ORDER BY count DESC
    ");
    $finalAges = $stmt->fetchAll();
    
    echo "<h3>Final Age Ranges:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Age Range</th><th>Count</th></tr>";
    foreach ($finalAges as $age) {
        echo "<tr><td>{$age['age_range']}</td><td>{$age['count']}</td></tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Success!</h3>";
    echo "<p><strong>Total updates made: $totalUpdates</strong></p>";
    echo "<p>✅ All 12+ values converted to 11-14 years</p>";
    echo "<p>✅ All reading levels standardized</p>";
    echo "<p>✅ All NULL values set to 'Unknown'</p>";
    echo "<p>✅ Age ranges synchronized with reading levels</p>";
    echo "</div>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<p><a href="comprehensive-cleanup.php">← Back to Cleanup Page</a></p>
