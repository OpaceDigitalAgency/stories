<?php
/**
 * Debug script to check what age ranges and reading levels are being served
 */

// Include database connection
require_once '../db-connect.php';

echo "<h1>🔍 Age Range & Reading Level Debug</h1>";
echo "<p>This script shows what values are currently being served by the form.</p>";

// Check what the current file contains
echo "<h2>1. Current File Content Check</h2>";

// Standard age ranges based on UK education system - COMPLETE LIST
$ageRangeList = [
    '0-12 months',
    '12-24 months', 
    '2-3 years',
    '3-4 years',
    '4-5 years',
    '5-6 years',
    '6-7 years',
    '7-8 years',
    '8-9 years',
    '9-10 years',
    '10-11 years',
    '11-14 years',
    '14-16 years',
    '16-18 years',
    '18+ years',
    'Unknown'
];

// Standard reading levels based on UK education system - COMPLETE LIST
$readingLevelList = [
    'Pre-literacy (Sensory)',
    'Pre-literacy (Naming)',
    'Pre-literacy (Mimicry)',
    'Early Pre-reader',
    'Beginning Reader',
    'Early Reader',
    'Developing Reader',
    'Transitional Reader',
    'Fluent Reader',
    'Advanced Reader',
    'Proficient Reader'
];

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ Standard Age Ranges (" . count($ageRangeList) . " total):</h3>";
echo "<ul>";
foreach ($ageRangeList as $range) {
    echo "<li>" . htmlspecialchars($range) . "</li>";
}
echo "</ul>";
echo "</div>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ Standard Reading Levels (" . count($readingLevelList) . " total):</h3>";
echo "<ul>";
foreach ($readingLevelList as $level) {
    echo "<li>" . htmlspecialchars($level) . "</li>";
}
echo "</ul>";
echo "</div>";

// Check what's in the database
echo "<h2>2. Database Content Check</h2>";

try {
    // Get unique age ranges from books table (old method)
    $ageRangeStmt = $db->query("SELECT DISTINCT age_range FROM books WHERE age_range IS NOT NULL AND age_range != '' ORDER BY age_range");
    $dbAgeRanges = $ageRangeStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>⚠️ Database Age Ranges (" . count($dbAgeRanges) . " total):</h3>";
    echo "<ul>";
    foreach ($dbAgeRanges as $range) {
        echo "<li>" . htmlspecialchars($range) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Get unique reading levels from books table (old method)
    $readingLevelStmt = $db->query("SELECT DISTINCT reading_level FROM books WHERE reading_level IS NOT NULL AND reading_level != '' ORDER BY reading_level");
    $dbReadingLevels = $readingLevelStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>⚠️ Database Reading Levels (" . count($dbReadingLevels) . " total):</h3>";
    echo "<ul>";
    foreach ($dbReadingLevels as $level) {
        echo "<li>" . htmlspecialchars($level) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check file modification time
echo "<h2>3. File Status Check</h2>";
$formFile = __DIR__ . '/directory-item-form.php';
if (file_exists($formFile)) {
    $modTime = filemtime($formFile);
    echo "<p><strong>File last modified:</strong> " . date('Y-m-d H:i:s', $modTime) . "</p>";
    echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Time difference:</strong> " . (time() - $modTime) . " seconds ago</p>";
} else {
    echo "<p style='color: red;'>❌ directory-item-form.php not found!</p>";
}

// Test the actual dropdown generation
echo "<h2>4. Live Dropdown Test</h2>";
echo "<p>This shows what the actual form dropdowns should contain:</p>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>Age Range Dropdown:</h4>";
echo "<select style='width: 100%; padding: 5px;'>";
echo "<option value=''>Select Age Range</option>";
foreach ($ageRangeList as $ageRange) {
    echo "<option value=\"" . htmlspecialchars($ageRange) . "\">" . htmlspecialchars($ageRange) . "</option>";
}
echo "</select>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>Reading Level Dropdown:</h4>";
echo "<select style='width: 100%; padding: 5px;'>";
echo "<option value=''>Select Reading Level</option>";
foreach ($readingLevelList as $readingLevel) {
    echo "<option value=\"" . htmlspecialchars($readingLevel) . "\">" . htmlspecialchars($readingLevel) . "</option>";
}
echo "</select>";
echo "</div>";

// Check server info
echo "<h2>5. Server Information</h2>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";

// Check if there are any PHP errors
echo "<h2>6. PHP Error Check</h2>";
if (function_exists('error_get_last')) {
    $lastError = error_get_last();
    if ($lastError) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>❌ Last PHP Error:</h4>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($lastError['message']) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($lastError['file']) . "</p>";
        echo "<p><strong>Line:</strong> " . $lastError['line'] . "</p>";
        echo "</div>";
    } else {
        echo "<p style='color: green;'>✅ No PHP errors detected</p>";
    }
}

echo "<hr>";
echo "<h2>🎯 Diagnosis</h2>";
echo "<p>If you see the <strong>standard age ranges</strong> above (0-12 months, 12-24 months, etc.) but the form is still showing database values (9-10 years, 8-9 years, etc.), then:</p>";
echo "<ol>";
echo "<li><strong>Cache Issue:</strong> The server might be serving a cached version</li>";
echo "<li><strong>Deployment Issue:</strong> The changes might not have been deployed to the live server</li>";
echo "<li><strong>File Issue:</strong> The wrong file might be being served</li>";
echo "</ol>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Check if this debug script shows the correct standard values</li>";
echo "<li>Compare with what you see in the actual form</li>";
echo "<li>If they differ, there's a caching/deployment issue</li>";
echo "</ul>";

echo "<p><a href='directory-item-form.php'>🔗 Go to Directory Item Form</a></p>";
echo "<p><a href='test-age-reading-sync.php'>🔗 Go to Sync Test Page</a></p>";
?>
