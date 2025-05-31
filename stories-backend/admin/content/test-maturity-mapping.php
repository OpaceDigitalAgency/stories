<?php
// Test maturity rating mapping to identify 18+ years issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include auth check first
require_once '../includes/auth-check.php';

try {
    // Try different possible paths for db-connect.php
    $dbPaths = [
        '../../../config/db-connect.php',
        '../../config/db-connect.php',
        '../../../db-connect.php',
        '../../db-connect.php',
        '../../../includes/db_connect.php',  // Correct path
        '../../includes/db_connect.php'
    ];
    
    $dbConnected = false;
    foreach ($dbPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $dbConnected = true;
            echo "<p>✅ Database connected using: $path</p>";
            break;
        }
    }
    
    if (!$dbConnected) {
        throw new Exception("Could not find db-connect.php in any expected location. Tried: " . implode(', ', $dbPaths));
    }
    
    require_once 'book-import-validate/ajax/data-enrichment-ajax.php';
    echo "<p>✅ Required files loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error loading files: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h1>🔍 Maturity Rating Mapping Test</h1>";
echo "<p>This test will help identify why Google Books maturity_rating 'NOT_MATURE' is being mapped to '18+ years'</p>";

// Test the maturity mapping function directly
echo "<h2>Test 1: Direct Function Call</h2>";

try {
    echo "<h3>Testing NOT_MATURE mapping:</h3>";
    $result = mapMaturityToAgeRangeFromTable('NOT_MATURE');
    echo "<p><strong>Result:</strong> " . ($result ? htmlspecialchars($result) : 'null') . "</p>";
    
    if ($result === '18+ years') {
        echo "<p style='color: red;'>❌ PROBLEM FOUND: NOT_MATURE is mapping to '18+ years'!</p>";
    } else {
        echo "<p style='color: green;'>✅ GOOD: NOT_MATURE is not mapping to '18+ years'</p>";
    }
    
    echo "<h3>Testing MATURE mapping:</h3>";
    $result2 = mapMaturityToAgeRangeFromTable('MATURE');
    echo "<p><strong>Result:</strong> " . ($result2 ? htmlspecialchars($result2) : 'null') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test the database table contents
echo "<h2>Test 2: Database Table Contents</h2>";

try {
    $stmt = $db->query("SELECT * FROM standard_reading_levels ORDER BY sort_order");
    $levels = $stmt->fetchAll();
    
    echo "<h3>standard_reading_levels table contents:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Age Group</th><th>Sort Order</th></tr>";
    
    foreach ($levels as $level) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($level['id']) . "</td>";
        echo "<td>" . htmlspecialchars($level['age_group']) . "</td>";
        echo "<td>" . htmlspecialchars($level['sort_order']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR reading database: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test the specific query used in the function
echo "<h2>Test 3: Specific Query Test</h2>";

try {
    echo "<h3>Testing the exact query for NOT_MATURE fallback:</h3>";
    $stmt = $db->prepare("SELECT age_group FROM standard_reading_levels WHERE age_group IN ('7-8 years', '8-9 years', '9-10 years') ORDER BY sort_order ASC LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<p><strong>Query result:</strong> " . ($result ? htmlspecialchars($result['age_group']) : 'null') . "</p>";
    
    if ($result && $result['age_group'] === '18+ years') {
        echo "<p style='color: red;'>❌ PROBLEM: The fallback query is returning '18+ years' for children's books!</p>";
    } else {
        echo "<p style='color: green;'>✅ GOOD: Fallback query returns appropriate children's age range</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>🎯 Instructions</h2>";
echo "<p>1. Open browser console and filter by 'AGE_TEST' to see detailed debugging</p>";
echo "<p>2. Check the results above to identify where the '18+ years' mapping is coming from</p>";
echo "<p>3. If the direct function test shows the problem, the issue is in the database or query logic</p>";
echo "<p>4. If the database contents look wrong, we need to fix the standard_reading_levels table</p>";

?>
