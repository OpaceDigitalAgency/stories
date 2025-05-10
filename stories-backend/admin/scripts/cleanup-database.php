<?php
/**
 * Database Cleanup Script
 *
 * This script cleans up the database by:
 * 1. Removing age-related tags from the tags table
 * 2. Removing duplicate tags
 * 3. Removing "**" prefix from tag names
 * 4. Removing "**" prefix from author names
 * 5. Creating a publishers table and migrating data
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
    <title>Database Cleanup</title>
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
    <h1>Database Cleanup</h1>
';
}

output("Starting database cleanup...", $isWeb);
output("", $isWeb);

// We'll perform each operation independently without a transaction
// This way, if one operation fails, the others can still proceed
$hasActiveTransaction = false;
output("Starting operations (no transaction)", $isWeb);

// 1. Clean up tags table
output("=== Cleaning up tags table ===", $isWeb);

// 1.1 Remove age-related tags
try {
    $agePatterns = [
        '0-3', '3-5', '4-6', '5-7', '6-8', '7-9', '7-10', '8-10', '8-12', '9-12', '10-12',
        '10+', '12+', '13+', '14+', '16+', 'teen', 'young adult', 'adult'
    ];

    $placeholders = implode(',', array_fill(0, count($agePatterns), '?'));

    // First, get the IDs of age-related tags
    $stmt = $db->prepare("SELECT id, name FROM tags WHERE LOWER(name) IN ($placeholders) OR
                          LOWER(name) LIKE '%years%' OR
                          LOWER(name) LIKE '%age%' OR
                          LOWER(name) REGEXP '^[0-9]+-[0-9]+$' OR
                          LOWER(name) REGEXP '^[0-9]+\\\\+$'");

    // Bind all the age patterns
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
        $idPlaceholders = implode(',', array_fill(0, count($ageTagIds), '?'));

        // Check for directory_item_tags table
        if ($db->query("SHOW TABLES LIKE 'directory_item_tags'")->rowCount() > 0) {
            $deleteAssocStmt = $db->prepare("DELETE FROM directory_item_tags WHERE tag_id IN ($idPlaceholders)");
            foreach ($ageTagIds as $index => $id) {
                $deleteAssocStmt->bindValue($index + 1, $id);
            }
            $deleteAssocStmt->execute();
            output("Deleted " . $deleteAssocStmt->rowCount() . " directory item tag associations", $isWeb);
        } else if ($db->query("SHOW TABLES LIKE 'item_tags'")->rowCount() > 0) {
            // Fallback to item_tags if directory_item_tags doesn't exist
            $deleteAssocStmt = $db->prepare("DELETE FROM item_tags WHERE tag_id IN ($idPlaceholders)");
            foreach ($ageTagIds as $index => $id) {
                $deleteAssocStmt->bindValue($index + 1, $id);
            }
            $deleteAssocStmt->execute();
            output("Deleted " . $deleteAssocStmt->rowCount() . " item tag associations", $isWeb);
        } else {
            output("No item_tags or directory_item_tags table found, skipping tag associations", $isWeb);
        }

        // Delete story tag associations if the table exists
        if ($db->query("SHOW TABLES LIKE 'story_tags'")->rowCount() > 0) {
            $deleteStoryTagsStmt = $db->prepare("DELETE FROM story_tags WHERE tag_id IN ($idPlaceholders)");
            foreach ($ageTagIds as $index => $id) {
                $deleteStoryTagsStmt->bindValue($index + 1, $id);
            }
            $deleteStoryTagsStmt->execute();
            output("Deleted " . $deleteStoryTagsStmt->rowCount() . " story tag associations", $isWeb);
        } else {
            output("story_tags table does not exist, skipping", $isWeb);
        }

        // Delete post tag associations if they exist
        if ($db->query("SHOW TABLES LIKE 'post_tags'")->rowCount() > 0) {
            $deletePostTagsStmt = $db->prepare("DELETE FROM post_tags WHERE tag_id IN ($idPlaceholders)");
            foreach ($ageTagIds as $index => $id) {
                $deletePostTagsStmt->bindValue($index + 1, $id);
            }
            $deletePostTagsStmt->execute();
            output("Deleted " . $deletePostTagsStmt->rowCount() . " post tag associations", $isWeb);
        } else {
            output("post_tags table does not exist, skipping", $isWeb);
        }

        // Now delete the tags
        $deleteTagsStmt = $db->prepare("DELETE FROM tags WHERE id IN ($idPlaceholders)");
        foreach ($ageTagIds as $index => $id) {
            $deleteTagsStmt->bindValue($index + 1, $id);
        }
        $deleteTagsStmt->execute();
        output("Deleted " . $deleteTagsStmt->rowCount() . " age-related tags", $isWeb);
    } else {
        output("No age-related tags found", $isWeb);
    }
} catch (PDOException $e) {
    output("Error removing age-related tags: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

// 1.2 Remove "**" prefix from tag names
try {
    $stmt = $db->prepare("UPDATE tags SET name = TRIM(SUBSTRING(name, 3)) WHERE name LIKE '**%'");
    $stmt->execute();
    output("Removed '**' prefix from " . $stmt->rowCount() . " tags", $isWeb);
} catch (PDOException $e) {
    output("Error removing '**' prefix from tags: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

// 1.3 Find and merge duplicate tags
try {
    // Get all tags
    $stmt = $db->query("SELECT id, name, LOWER(TRIM(name)) as normalized_name FROM tags ORDER BY name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group tags by normalized name
    $tagGroups = [];
    foreach ($tags as $tag) {
        $normalizedName = $tag['normalized_name'];
        if (!isset($tagGroups[$normalizedName])) {
            $tagGroups[$normalizedName] = [];
        }
        $tagGroups[$normalizedName][] = $tag;
    }

    // Find groups with more than one tag (duplicates)
    $duplicateGroups = array_filter($tagGroups, function($group) {
        return count($group) > 1;
    });

    if (count($duplicateGroups) > 0) {
        output("Found " . count($duplicateGroups) . " duplicate tag groups:", $isWeb);

        foreach ($duplicateGroups as $normalizedName => $group) {
            output("  - Group '" . $normalizedName . "' has " . count($group) . " tags:", $isWeb);

            // Keep the first tag (usually the one with the lowest ID)
            $keepTag = $group[0];
            $keepId = $keepTag['id'];

            output("    - Keeping: " . $keepTag['name'] . " (ID: " . $keepId . ")", $isWeb);

            // Process the rest (duplicates to merge)
            $duplicateIds = [];
            for ($i = 1; $i < count($group); $i++) {
                $duplicateIds[] = $group[$i]['id'];
                output("    - Merging: " . $group[$i]['name'] . " (ID: " . $group[$i]['id'] . ")", $isWeb);
            }

            if (count($duplicateIds) > 0) {
                $idPlaceholders = implode(',', array_fill(0, count($duplicateIds), '?'));

                // Update directory_item_tags or item_tags associations
                if ($db->query("SHOW TABLES LIKE 'directory_item_tags'")->rowCount() > 0) {
                    $updateItemTagsStmt = $db->prepare("UPDATE directory_item_tags SET tag_id = ? WHERE tag_id IN ($idPlaceholders)");
                    $updateItemTagsStmt->bindValue(1, $keepId);
                    foreach ($duplicateIds as $index => $id) {
                        $updateItemTagsStmt->bindValue($index + 2, $id);
                    }
                    $updateItemTagsStmt->execute();
                    output("    - Updated " . $updateItemTagsStmt->rowCount() . " directory item tag associations", $isWeb);
                } else if ($db->query("SHOW TABLES LIKE 'item_tags'")->rowCount() > 0) {
                    $updateItemTagsStmt = $db->prepare("UPDATE item_tags SET tag_id = ? WHERE tag_id IN ($idPlaceholders)");
                    $updateItemTagsStmt->bindValue(1, $keepId);
                    foreach ($duplicateIds as $index => $id) {
                        $updateItemTagsStmt->bindValue($index + 2, $id);
                    }
                    $updateItemTagsStmt->execute();
                    output("    - Updated " . $updateItemTagsStmt->rowCount() . " item tag associations", $isWeb);
                } else {
                    output("    - No item_tags or directory_item_tags table found, skipping tag associations", $isWeb);
                }

                // Update story_tags associations
                $updateStoryTagsStmt = $db->prepare("UPDATE story_tags SET tag_id = ? WHERE tag_id IN ($idPlaceholders)");
                $updateStoryTagsStmt->bindValue(1, $keepId);
                foreach ($duplicateIds as $index => $id) {
                    $updateStoryTagsStmt->bindValue($index + 2, $id);
                }
                $updateStoryTagsStmt->execute();
                output("    - Updated " . $updateStoryTagsStmt->rowCount() . " story tag associations", $isWeb);

                // Update post_tags associations if they exist
                if ($db->query("SHOW TABLES LIKE 'post_tags'")->rowCount() > 0) {
                    $updatePostTagsStmt = $db->prepare("UPDATE post_tags SET tag_id = ? WHERE tag_id IN ($idPlaceholders)");
                    $updatePostTagsStmt->bindValue(1, $keepId);
                    foreach ($duplicateIds as $index => $id) {
                        $updatePostTagsStmt->bindValue($index + 2, $id);
                    }
                    $updatePostTagsStmt->execute();
                    output("    - Updated " . $updatePostTagsStmt->rowCount() . " post tag associations", $isWeb);
                }

                // Delete the duplicate tags
                $deleteTagsStmt = $db->prepare("DELETE FROM tags WHERE id IN ($idPlaceholders)");
                foreach ($duplicateIds as $index => $id) {
                    $deleteTagsStmt->bindValue($index + 1, $id);
                }
                $deleteTagsStmt->execute();
                output("    - Deleted " . $deleteTagsStmt->rowCount() . " duplicate tags", $isWeb);
            }
        }
    } else {
        output("No duplicate tags found", $isWeb);
    }
} catch (PDOException $e) {
    output("Error merging duplicate tags: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

output("", $isWeb);

// 2. Clean up authors table
output("=== Cleaning up authors table ===", $isWeb);

// 2.1 Remove "**" prefix from author names
try {
    $stmt = $db->prepare("UPDATE authors SET name = TRIM(SUBSTRING(name, 3)) WHERE name LIKE '**%'");
    $stmt->execute();
    output("Removed '**' prefix from " . $stmt->rowCount() . " authors", $isWeb);
} catch (PDOException $e) {
    output("Error removing '**' prefix from authors: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

output("", $isWeb);

// 3. Create publishers table and migrate data
output("=== Creating publishers table and migrating data ===", $isWeb);

// 3.1 Create publishers table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS publishers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        website VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (slug)
    )");
    output("Publishers table created or already exists", $isWeb);
} catch (PDOException $e) {
    output("Error creating publishers table: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

// 3.2 Migrate publisher data from books table
try {
    // Get unique publishers from books table
    $stmt = $db->query("SELECT DISTINCT publisher FROM books WHERE publisher IS NOT NULL AND publisher != ''");
    $publishers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($publishers) > 0) {
        output("Found " . count($publishers) . " unique publishers to migrate", $isWeb);

        // Insert each publisher into the publishers table
        $insertStmt = $db->prepare("INSERT IGNORE INTO publishers (name, slug) VALUES (?, ?)");
        $count = 0;

        foreach ($publishers as $publisher) {
            // Generate slug
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $publisher));
            $slug = trim($slug, '-');

            $insertStmt->execute([$publisher, $slug]);
            if ($insertStmt->rowCount() > 0) {
                $count++;
            }
        }

        output("Migrated $count publishers to the publishers table", $isWeb);
    } else {
        output("No publishers found to migrate", $isWeb);
    }
} catch (PDOException $e) {
    output("Error migrating publisher data: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

// 3.3 Add publisher_id column to books table if it doesn't exist
try {
    // Check if publisher_id column exists
    $stmt = $db->query("SHOW COLUMNS FROM books LIKE 'publisher_id'");
    if ($stmt->rowCount() == 0) {
        // Add publisher_id column
        $db->exec("ALTER TABLE books ADD COLUMN publisher_id INT");
        output("Added publisher_id column to books table", $isWeb);

        // Update publisher_id based on publisher name
        $updateStmt = $db->prepare("
            UPDATE books b
            JOIN publishers p ON b.publisher = p.name
            SET b.publisher_id = p.id
            WHERE b.publisher IS NOT NULL AND b.publisher != ''
        ");
        $updateStmt->execute();
        output("Updated publisher_id for " . $updateStmt->rowCount() . " books", $isWeb);
    } else {
        output("publisher_id column already exists in books table", $isWeb);
    }
} catch (PDOException $e) {
    output("Error adding publisher_id column: " . $e->getMessage(), $isWeb);
    output("Continuing with next operation...", $isWeb);
}

output("", $isWeb);

// All operations completed
output("All operations completed successfully", $isWeb);

output("", $isWeb);
output("Database cleanup completed successfully!", $isWeb);

// Footer for web output
if ($isWeb) {
    echo '
</body>
</html>';
}
?>
