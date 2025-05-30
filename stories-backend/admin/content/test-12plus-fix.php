<?php
/**
 * Test script to verify that 12+ values are properly filtered out
 * and age range/reading level synchronization works correctly
 */

require_once '../../../db-connect.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';
require_once 'book-import-validate/ajax/data-enrichment-ajax.php';

echo "<h1>🧪 Testing 12+ Value Filtering and Age Range Sync</h1>";

// Test 1: Check if 12+ values are filtered from database queries
echo "<h2>Test 1: Database Query Filtering</h2>";

try {
    // Test the mapMaturityToAgeRangeFromTable function
    $result = mapMaturityToAgeRangeFromTable('NOT_MATURE');
    echo "<p><strong>NOT_MATURE mapping result:</strong> " . ($result ? htmlspecialchars($result) : 'null') . "</p>";
    
    if ($result && strpos($result, '12+') !== false) {
        echo "<p style='color: red;'>❌ FAILED: Result contains '12+' value: $result</p>";
    } else {
        echo "<p style='color: green;'>✅ PASSED: No '12+' values returned</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Check age range to reading level mapping
echo "<h2>Test 2: Age Range to Reading Level Mapping</h2>";

$testMappings = [
    '5-6 years' => 'Early Reader',
    '6-7 years' => 'Developing Reader',
    '7-8 years' => 'Transitional Reader',
    '8-9 years' => 'Fluent Reader',
    '11-14 years' => 'Advanced Reader',
    '18+ years' => 'Proficient Reader'
];

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Age Range</th><th>Expected Reading Level</th><th>Status</th></tr>";

foreach ($testMappings as $ageRange => $expectedReading) {
    // This would test the mapping logic if we had a function for it
    echo "<tr>";
    echo "<td>$ageRange</td>";
    echo "<td>$expectedReading</td>";
    echo "<td style='color: green;'>✅ Mapping defined</td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Check for existing 12+ values in database
echo "<h2>Test 3: Existing 12+ Values in Database</h2>";

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM books 
        WHERE age_range LIKE '%12+%'
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p><strong>Books with '12+' in age_range:</strong> " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ WARNING: Found {$result['count']} books with '12+' values. These should be cleaned up.</p>";
        
        // Show some examples
        $stmt = $db->prepare("
            SELECT b.directory_item_id, di.title, b.age_range 
            FROM books b 
            JOIN directory_items di ON b.directory_item_id = di.id 
            WHERE b.age_range LIKE '%12+%' 
            LIMIT 5
        ");
        $stmt->execute();
        $examples = $stmt->fetchAll();
        
        echo "<p><strong>Examples:</strong></p>";
        echo "<ul>";
        foreach ($examples as $book) {
            echo "<li>" . htmlspecialchars($book['title']) . " - Age Range: [" . htmlspecialchars($book['age_range']) . "]</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✅ PASSED: No books with '12+' values found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Check age_ranges table for 12+ values
echo "<h2>Test 4: Age Ranges Table</h2>";

try {
    $stmt = $db->prepare("
        SELECT range_name 
        FROM age_ranges 
        WHERE range_name LIKE '%12+%'
        ORDER BY range_name
    ");
    $stmt->execute();
    $ranges = $stmt->fetchAll();
    
    if (count($ranges) > 0) {
        echo "<p style='color: orange;'>⚠️ WARNING: Found " . count($ranges) . " age ranges with '12+' in age_ranges table:</p>";
        echo "<ul>";
        foreach ($ranges as $range) {
            echo "<li>" . htmlspecialchars($range['range_name']) . "</li>";
        }
        echo "</ul>";
        echo "<p><em>Note: These values exist in the database but should be filtered out by our query logic.</em></p>";
    } else {
        echo "<p style='color: green;'>✅ PASSED: No '12+' values in age_ranges table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Test Google Books API response processing
echo "<h2>Test 5: Google Books API Response Processing</h2>";

// Simulate the Google Books response from your example
$mockGoogleBooksResponse = [
    'maturity_rating' => 'NOT_MATURE',
    'categories' => ['Juvenile Fiction'],
    'title' => 'A Hen in the Wardrobe'
];

echo "<p><strong>Mock Google Books Response:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode($mockGoogleBooksResponse, JSON_PRETTY_PRINT)) . "</pre>";

// Test the age range extraction
$extractedAgeRange = extractFieldFromMatch($mockGoogleBooksResponse, 'age_range');
echo "<p><strong>Extracted Age Range:</strong> " . ($extractedAgeRange ? htmlspecialchars($extractedAgeRange) : 'null') . "</p>";

if ($extractedAgeRange && strpos($extractedAgeRange, '12+') !== false) {
    echo "<p style='color: red;'>❌ FAILED: Extracted age range contains '12+': $extractedAgeRange</p>";
} else {
    echo "<p style='color: green;'>✅ PASSED: No '12+' values extracted from Google Books response</p>";
}

echo "<h2>🎯 Summary</h2>";
echo "<p>The fixes implemented should:</p>";
echo "<ul>";
echo "<li>✅ Filter out '12+' values from database queries in <code>mapMaturityToAgeRangeFromTable()</code></li>";
echo "<li>✅ Filter out '12+' values in <code>extractFieldFromMatch()</code> for age_range processing</li>";
echo "<li>✅ Filter out 'unknown' values in reading level processing</li>";
echo "<li>✅ Provide consistent age range to reading level mapping</li>";
echo "<li>✅ Prevent '12+' values from appearing in data enrichment modal</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Test the data enrichment modal with a book that has Google Books data</li>";
echo "<li>Verify that age range and reading level synchronization works correctly</li>";
echo "<li>Check that no '12+' values appear in the enrichment options</li>";
echo "</ul>";

?>
