<?php
/**
 * Debug Tag Inconsistency
 *
 * Check why book validation and data enrichment show different results for tags
 */

require_once '../includes/db-connect.php';
require_once '../includes/tag-functions.php';

echo "<h1>🔍 Debug Tag Inconsistency</h1>";

// Test specific books mentioned in the issue
$testBooks = [
    'A Hen in the Wardrobe',
    'Demon Dentist'
];

foreach ($testBooks as $bookTitle) {
    echo "<h2>📚 Testing: " . htmlspecialchars($bookTitle) . "</h2>";

    // Get book ID
    $stmt = $db->prepare("SELECT id FROM directory_items WHERE title LIKE ?");
    $stmt->execute(['%' . $bookTitle . '%']);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        echo "<p>❌ Book not found</p>";
        continue;
    }

    $bookId = $book['id'];
    echo "<p><strong>Book ID:</strong> {$bookId}</p>";

    // Method 1: Get ALL tags (like data enrichment modal)
    $stmt = $db->prepare("
        SELECT t.id, t.name
        FROM tags t
        JOIN directory_item_tags dit ON t.id = dit.tag_id
        WHERE dit.directory_item_id = ?
        ORDER BY t.name ASC
    ");
    $stmt->execute([$bookId]);
    $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>🏷️ ALL Tags (Data Enrichment Method):</h3>";
    if ($allTags) {
        echo "<ul>";
        foreach ($allTags as $tag) {
            echo "<li>" . htmlspecialchars($tag['name']) . " (ID: {$tag['id']})</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total:</strong> " . count($allTags) . " tags</p>";
    } else {
        echo "<p>❌ No tags found</p>";
    }

    // Method 2: Get genre tags only (like book validation)
    $genreTags = getGenreTagsForDirectoryItem($db, $bookId);

    echo "<h3>🎭 Genre Tags Only (Book Validation Method):</h3>";
    if ($genreTags) {
        echo "<ul>";
        foreach ($genreTags as $tag) {
            echo "<li>" . htmlspecialchars($tag['name']) . " (ID: {$tag['id']})</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total:</strong> " . count($genreTags) . " genre tags</p>";
    } else {
        echo "<p>❌ No genre tags found (this is why book validation shows 'missing')</p>";
    }

    // Method 3: Get age range tags only
    $ageRangeTags = getAgeRangeTagsForDirectoryItem($db, $bookId);

    echo "<h3>📅 Age Range Tags Only:</h3>";
    if ($ageRangeTags) {
        echo "<ul>";
        foreach ($ageRangeTags as $tag) {
            echo "<li>" . htmlspecialchars($tag['name']) . " (ID: {$tag['id']})</li>";
        }
        echo "</ul>";
        echo "<p><strong>Total:</strong> " . count($ageRangeTags) . " age range tags</p>";
    } else {
        echo "<p>❌ No age range tags found</p>";
    }

    // Show the filtering logic
    echo "<h3>🔍 Tag Analysis:</h3>";
    if ($allTags) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Tag Name</th><th>Is Age-Related?</th><th>Reason</th></tr>";

        foreach ($allTags as $tag) {
            $name = strtolower($tag['name']);
            $isAgeRelated = false;
            $reason = "Genre tag";

            // Apply the same filtering logic as getGenreTagsForDirectoryItem
            if (preg_match('/^\d+-\d+$/', $name)) {
                $isAgeRelated = true;
                $reason = "Matches pattern: digits-digits";
            } elseif (preg_match('/^\d+\+$/', $name)) {
                $isAgeRelated = true;
                $reason = "Matches pattern: digits+";
            } elseif (strpos($name, 'years') !== false) {
                $isAgeRelated = true;
                $reason = "Contains 'years'";
            } elseif (strpos($name, 'age') !== false) {
                $isAgeRelated = true;
                $reason = "Contains 'age'";
            } elseif (in_array($name, ['teen', 'young adult', 'adult', 'coming of age', '12+', '13+', '14+', '16+'])) {
                $isAgeRelated = true;
                $reason = "In hardcoded age list";
            }

            echo "<tr>";
            echo "<td>" . htmlspecialchars($tag['name']) . "</td>";
            echo "<td style='color: " . ($isAgeRelated ? 'red' : 'green') . ";'>" . ($isAgeRelated ? 'YES' : 'NO') . "</td>";
            echo "<td>" . htmlspecialchars($reason) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr style='margin: 30px 0;'>";
}

echo "<h2>💡 Summary</h2>";
echo "<p>The inconsistency occurs because:</p>";
echo "<ul>";
echo "<li><strong>Book validation page</strong> uses <code>getGenreTagsForDirectoryItem()</code> which filters OUT age-related tags</li>";
echo "<li><strong>Data enrichment modal</strong> uses <code>getCurrentBookData()</code> which shows ALL tags</li>";
echo "<li>If a book only has age-related tags, book validation will show 'missing genres' even though the book has tags</li>";
echo "</ul>";

echo "<h2>🔧 Applied Fix</h2>";
echo "<p><strong>✅ FIXED:</strong> Updated both systems to be consistent:</p>";
echo "<ol>";
echo "<li><strong>Book validation page:</strong> Now checks for ANY tags (not just genre tags) and displays both genres and age tags separately</li>";
echo "<li><strong>Data enrichment modal:</strong> Now filters tags to show only genre tags (consistent with the 'Genres' field label)</li>";
echo "<li><strong>Display:</strong> Book validation now shows 'Genres: ...' and 'Age: ...' separately for clarity</li>";
echo "</ol>";

echo "<h2>🧪 Test the Fix</h2>";
echo "<p>To verify the fix:</p>";
echo "<ol>";
echo "<li>Refresh the book validation page - both books should now show consistent tag status</li>";
echo "<li>Open data enrichment modal for both books - should show only genre tags in the 'Genres' field</li>";
echo "<li>The 'missing fields' count should now be consistent between both systems</li>";
echo "</ol>";

echo "<h2>📝 Technical Changes Made</h2>";
echo "<ul>";
echo "<li><code>book-validation.php</code>: Updated to check for ANY tags and display genres/age separately</li>";
echo "<li><code>data-enrichment-ajax.php</code>: Updated to filter out age-related tags from the 'tags' field</li>";
echo "<li>Both systems now use the same filtering logic for consistency</li>";
echo "</ul>";
?>
