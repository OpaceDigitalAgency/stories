<?php
/**
 * Fix Tag Handling Script
 *
 * This script fixes the tag handling in the import process:
 * 1. Removes age-related tags from the tags table
 * 2. Ensures age ranges are properly stored in the age_range field in the books table
 * 3. Updates the directory_item_tags table to remove age-related tag associations
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set execution time limit to 5 minutes
set_time_limit(300);

// Start output buffering
ob_start();

// Function to output messages
function output($message, $isHtml = false) {
    if ($isHtml) {
        echo $message . "<br>\n";
    } else {
        echo $message . "\n";
    }
    ob_flush();
    flush();
}

// Check if running in web or CLI
$isWeb = php_sapi_name() !== 'cli';

// Header for web output
if ($isWeb) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Fix Tag Handling</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .warning {
            color: orange;
        }
        .info {
            color: blue;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Fix Tag Handling</h1>
';
}

output("Starting tag handling fix...", $isWeb);
output("", $isWeb);

// Define age-related patterns
$agePatterns = [
    '0-3', '3-5', '4-6', '5-7', '6-8', '7-9', '7-10', '8-10', '8-12', '9-12', '10-12',
    '10+', '12+', '13+', '14+', '16+', 'teen', 'young adult', 'adult'
];

// 1. Identify age-related tags
output("=== Identifying age-related tags ===", $isWeb);
try {
    $placeholders = implode(',', array_fill(0, count($agePatterns), '?'));

    $stmt = $db->prepare("SELECT id, name FROM tags WHERE LOWER(name) IN ($placeholders) OR
                          LOWER(name) LIKE '%years%' OR
                          LOWER(name) LIKE '%age%' OR
                          LOWER(name) REGEXP '^[0-9]+-[0-9]+$' OR
                          LOWER(name) REGEXP '^[0-9]+\\\\+$'");

    foreach ($agePatterns as $index => $pattern) {
        $stmt->bindValue($index + 1, strtolower($pattern));
    }

    $stmt->execute();
    $ageTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($ageTags) > 0) {
        output("Found " . count($ageTags) . " age-related tags:", $isWeb);
        foreach ($ageTags as $tag) {
            output("  - " . $tag['name'] . " (ID: " . $tag['id'] . ")", $isWeb);
        }

        // Get the IDs
        $ageTagIds = array_column($ageTags, 'id');

        // 2. Update books to set age_range from tags
        output("", $isWeb);
        output("=== Updating books with age ranges from tags ===", $isWeb);

        // For each age tag, find books that have this tag and update their age_range
        foreach ($ageTags as $tag) {
            $tagName = $tag['name'];
            $tagId = $tag['id'];

            // Find books with this tag
            $stmt = $db->prepare("
                SELECT b.directory_item_id, b.age_range
                FROM books b
                JOIN directory_item_tags dit ON b.directory_item_id = dit.item_id
                WHERE dit.tag_id = ?
            ");
            $stmt->execute([$tagId]);
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($books) > 0) {
                output("Found " . count($books) . " books with tag '" . $tagName . "'", $isWeb);

                // Update each book's age_range if it's empty
                foreach ($books as $book) {
                    if (empty($book['age_range'])) {
                        $stmt = $db->prepare("UPDATE books SET age_range = ? WHERE directory_item_id = ?");
                        $stmt->execute([$tagName, $book['directory_item_id']]);
                        output("  - Updated book with directory_item_id " . $book['directory_item_id'] . " with age_range '" . $tagName . "'", $isWeb);
                    }
                }
            }
        }

        // 3. Remove age tag associations from directory_item_tags
        output("", $isWeb);
        output("=== Removing age tag associations from directory_item_tags ===", $isWeb);

        $idPlaceholders = implode(',', array_fill(0, count($ageTagIds), '?'));

        // Check if directory_item_tags table exists
        if ($db->query("SHOW TABLES LIKE 'directory_item_tags'")->rowCount() > 0) {
            $stmt = $db->prepare("DELETE FROM directory_item_tags WHERE tag_id IN ($idPlaceholders)");
            foreach ($ageTagIds as $index => $id) {
                $stmt->bindValue($index + 1, $id);
            }
            $stmt->execute();
            output("Deleted " . $stmt->rowCount() . " age tag associations from directory_item_tags", $isWeb);
        } else {
            output("directory_item_tags table does not exist, skipping", $isWeb);
        }

        // 4. Delete the age tags
        output("", $isWeb);
        output("=== Deleting age tags ===", $isWeb);

        $stmt = $db->prepare("DELETE FROM tags WHERE id IN ($idPlaceholders)");
        foreach ($ageTagIds as $index => $id) {
            $stmt->bindValue($index + 1, $id);
        }
        $stmt->execute();
        output("Deleted " . $stmt->rowCount() . " age tags", $isWeb);
    } else {
        output("No age-related tags found", $isWeb);
    }
} catch (PDOException $e) {
    output("Error: " . $e->getMessage(), $isWeb);
}

output("", $isWeb);
output("Tag handling fix completed!", $isWeb);

// Footer for web output
if ($isWeb) {
    echo '
</body>
</html>';
}
?>
