<?php
/**
 * Tag Functions
 *
 * Helper functions for working with tags in the admin interface
 */

/**
 * Get genre tags for a directory item
 *
 * @param PDO $db Database connection
 * @param int $directoryItemId Directory item ID
 * @return array Array of tag objects with id and name
 */
function getGenreTagsForDirectoryItem($db, $directoryItemId) {
    $tags = [];

    try {
        // Check if directory_item_tags table exists
        if ($db->query("SHOW TABLES LIKE 'directory_item_tags'")->rowCount() > 0) {
            $stmt = $db->prepare("
                SELECT t.id, t.name
                FROM tags t
                JOIN directory_item_tags dit ON t.id = dit.tag_id
                WHERE dit.directory_item_id = ?
                ORDER BY t.name ASC
            ");
            $stmt->execute([$directoryItemId]);
            $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filter out age-related tags
            $tags = array_filter($allTags, function($tag) {
                $name = strtolower($tag['name']);
                // Filter out age range tags
                return !(
                    preg_match('/^\d+-\d+$/', $name) ||
                    preg_match('/^\d+\+$/', $name) ||
                    strpos($name, 'years') !== false ||
                    strpos($name, 'age') !== false ||
                    $name === 'teen' ||
                    $name === 'young adult' ||
                    $name === 'adult' ||
                    $name === 'coming of age' ||
                    $name === '12+' ||
                    $name === '13+' ||
                    $name === '14+' ||
                    $name === '16+'
                );
            });

            // Reset array keys
            $tags = array_values($tags);
        }
    } catch (Exception $e) {
        error_log("Error getting genre tags: " . $e->getMessage());
    }

    return $tags;
}

/**
 * Get age range tags for a directory item
 *
 * @param PDO $db Database connection
 * @param int $directoryItemId Directory item ID
 * @return array Array of tag objects with id and name
 */
function getAgeRangeTagsForDirectoryItem($db, $directoryItemId) {
    $tags = [];

    try {
        // Check if directory_item_tags table exists
        if ($db->query("SHOW TABLES LIKE 'directory_item_tags'")->rowCount() > 0) {
            $stmt = $db->prepare("
                SELECT t.id, t.name
                FROM tags t
                JOIN directory_item_tags dit ON t.id = dit.tag_id
                WHERE dit.directory_item_id = ?
                ORDER BY t.name ASC
            ");
            $stmt->execute([$directoryItemId]);
            $allTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filter to only include age-related tags
            $tags = array_filter($allTags, function($tag) {
                $name = strtolower($tag['name']);
                // Include only age range tags
                return (
                    preg_match('/^\d+-\d+$/', $name) ||
                    preg_match('/^\d+\+$/', $name) ||
                    strpos($name, 'years') !== false ||
                    strpos($name, 'age') !== false ||
                    $name === 'teen' ||
                    $name === 'young adult' ||
                    $name === 'adult' ||
                    $name === 'coming of age' ||
                    $name === '12+' ||
                    $name === '13+' ||
                    $name === '14+' ||
                    $name === '16+'
                );
            });

            // Reset array keys
            $tags = array_values($tags);
        }
    } catch (Exception $e) {
        error_log("Error getting age range tags: " . $e->getMessage());
    }

    return $tags;
}

/**
 * Format tags for display
 *
 * @param array $tags Array of tag objects
 * @return string Comma-separated list of tag names
 */
function formatTagsForDisplay($tags) {
    if (empty($tags)) {
        return '';
    }

    // Clean up tag names (remove ** prefix if present)
    $tagNames = array_map(function($tag) {
        $tagName = $tag['name'];
        if (strpos($tagName, '**') === 0) {
            $tagName = substr($tagName, 2);
        }
        return $tagName;
    }, $tags);

    return implode(', ', $tagNames);
}

/**
 * Add a tag to a directory item
 *
 * @param PDO $db Database connection
 * @param int $directoryItemId Directory item ID
 * @param int $tagId Tag ID
 * @return bool True if successful, false otherwise
 */
function addTagToDirectoryItem($db, $directoryItemId, $tagId) {
    try {
        // Check if tag is already associated with the directory item
        $stmt = $db->prepare("SELECT * FROM directory_item_tags WHERE directory_item_id = ? AND tag_id = ?");
        $stmt->execute([$directoryItemId, $tagId]);
        $existingRelation = $stmt->fetch();

        if (!$existingRelation) {
            // Associate tag with directory item
            $stmt = $db->prepare("INSERT INTO directory_item_tags (directory_item_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$directoryItemId, $tagId]);
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("Error adding tag to directory item: " . $e->getMessage());
        return false;
    }
}
