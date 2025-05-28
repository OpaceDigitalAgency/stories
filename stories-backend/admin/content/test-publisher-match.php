<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include authentication
session_start();


try {
    require_once '../includes/auth-check.php';
    echo '<p>✅ Authentication loaded</p>';
    require_once '../includes/db-connect.php';
    echo '<p>✅ Database connection loaded</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Setup failed: ' . $e->getMessage() . '</p>';
    exit;
}

try {
    require_once 'book-import-validate/functions/data-enrichment-functions.php';
    echo '<p>✅ Data enrichment functions loaded</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Data enrichment functions failed: ' . $e->getMessage() . '</p>';
    exit;
}

echo '<h1>Publisher Matching Debug Test</h1>';

// Test cases that should work
$testCases = [
    'Harper Collins' => 'Should match HarperCollins Children\'s Books',
    'HarperCollins' => 'Should match HarperCollins Children\'s Books',
    'Harper' => 'Should match HarperCollins Children\'s Books',
    'Bloomsbury' => 'Should match Bloomsbury Publishing Plc',
    'Bloomsbury Publishing' => 'Should match Bloomsbury Publishing Plc'
];

echo '<h2>Database Publishers Check</h2>';
try {
    // First, let's see what publishers are actually in the database
    $stmt = $db->prepare("
        SELECT id, name, type
        FROM authors
        WHERE (type = 'publisher' OR type IS NULL)
        AND (name LIKE '%Harper%' OR name LIKE '%Collins%' OR name LIKE '%Bloomsbury%')
        ORDER BY name
    ");
    $stmt->execute();
    $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Publishers in database containing Harper, Collins, or Bloomsbury:</h3>';
    if (empty($publishers)) {
        echo '<p style="color: red;">NO PUBLISHERS FOUND! This is the problem!</p>';

        // Check if there are ANY publishers at all
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM authors WHERE type = 'publisher' OR type IS NULL");
        $stmt->execute();
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<p>Total publishers in database: ' . $totalCount['count'] . '</p>';

        // Show first 10 publishers
        $stmt = $db->prepare("SELECT id, name, type FROM authors WHERE type = 'publisher' OR type IS NULL LIMIT 10");
        $stmt->execute();
        $samplePublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo '<h4>Sample publishers in database:</h4>';
        echo '<ul>';
        foreach ($samplePublishers as $pub) {
            echo '<li>ID: ' . $pub['id'] . ' - ' . htmlspecialchars($pub['name']) . ' (type: ' . ($pub['type'] ?? 'NULL') . ')</li>';
        }
        echo '</ul>';

    } else {
        echo '<ul>';
        foreach ($publishers as $pub) {
            echo '<li>ID: ' . $pub['id'] . ' - ' . htmlspecialchars($pub['name']) . ' (type: ' . ($pub['type'] ?? 'NULL') . ')</li>';
        }
        echo '</ul>';
    }

} catch (Exception $e) {
    echo '<p style="color: red;">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<h2>Publisher Matching Tests</h2>';

foreach ($testCases as $testInput => $expectedResult) {
    echo '<h3>Testing: "' . htmlspecialchars($testInput) . '"</h3>';
    echo '<p>Expected: ' . htmlspecialchars($expectedResult) . '</p>';

    $result = findBestPublisherMatch($testInput);

    if ($result) {
        echo '<p style="color: green;">✅ Match found:</p>';
        echo '<ul>';
        echo '<li>Name: ' . htmlspecialchars($result['name']) . '</li>';
        echo '<li>Confidence: ' . $result['confidence'] . '%</li>';
        echo '<li>Match Type: ' . $result['match_type'] . '</li>';
        echo '</ul>';
    } else {
        echo '<p style="color: red;">❌ No match found</p>';
    }

    echo '<hr>';
}

echo '<h2>Enhanced Similarity Test</h2>';
echo '<p>Testing the similarity algorithm directly:</p>';

$similarityTests = [
    ['Harper Collins', 'HarperCollins Children\'s Books'],
    ['HarperCollins', 'HarperCollins Children\'s Books'],
    ['Harper', 'HarperCollins Children\'s Books'],
    ['Bloomsbury', 'Bloomsbury Publishing Plc'],
    ['Bloomsbury Publishing', 'Bloomsbury Publishing Plc']
];

foreach ($similarityTests as $test) {
    $similarity = calculateEnhancedPublisherSimilarity($test[0], $test[1]);
    echo '<p>"' . htmlspecialchars($test[0]) . '" vs "' . htmlspecialchars($test[1]) . '" = ' . $similarity . '%</p>';
}

?>
