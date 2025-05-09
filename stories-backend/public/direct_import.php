<?php
/**
 * Enhanced Direct Import Tool
 * 
 * A comprehensive tool to import content with proper handling of
 * media files, authors, and tags. This improved version:
 * 
 * 1. Only deletes data related to the specific content being imported
 * 2. Supports multiple content types (stories, retail publisher stories, games, books, etc.)
 * 3. Uses the admin header/footer template for consistent UX
 * 4. Provides better error handling and reporting
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer to ensure real-time progress display
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Function to clean data for specific content type
function cleanContentData($db, $contentType, $sourceType = null) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Default to 'child' if no source type provided
        $sourceType = $sourceType ?: 'child';
        
        // Initialize counters for reporting
        $deletedAssociations = 0;
        $deletedItems = 0;
        $deletedMedia = 0;
        
        if ($contentType === 'stories') {
            // 1. Get IDs of stories to be deleted
            $storyIdsStmt = $db->prepare("SELECT id FROM stories WHERE source_type = ?");
            $storyIdsStmt->execute([$sourceType]);
            $storyIds = $storyIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($storyIds)) {
                $storyIdList = implode(',', $storyIds);
                
                // 2. Check if media_id column exists in stories table
                $checkColumnStmt = $db->query("SHOW COLUMNS FROM stories LIKE 'media_id'");
                $mediaIds = [];
                
                if ($checkColumnStmt->rowCount() > 0) {
                    // Get media IDs associated with these stories
                    $mediaIdsStmt = $db->prepare("SELECT media_id FROM stories WHERE id IN ($storyIdList) AND media_id IS NOT NULL");
                    $mediaIdsStmt->execute();
                    $mediaIds = $mediaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    echo "<p class='info'>No media_id column found in stories table, skipping media cleanup</p>";
                    flushOutput();
                }
                
                // 3. Delete story_tags associations for these stories
                $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN ($storyIdList)");
                $stmt->execute();
                $deletedAssociations += $stmt->rowCount();
                echo "<p class='info'>Deleted " . $stmt->rowCount() . " story-tag associations</p>";
                flushOutput();
                
                // 4. Delete story_authors associations for these stories
                $stmt = $db->prepare("DELETE FROM story_authors WHERE story_id IN ($storyIdList)");
                $stmt->execute();
                $deletedAssociations += $stmt->rowCount();
                echo "<p class='info'>Deleted " . $stmt->rowCount() . " story-author associations</p>";
                flushOutput();
                
                // 5. Delete the stories
                $stmt = $db->prepare("DELETE FROM stories WHERE id IN ($storyIdList)");
                $stmt->execute();
                $deletedItems = $stmt->rowCount();
                echo "<p class='info'>Deleted $deletedItems existing $sourceType stories</p>";
                flushOutput();
                
                // 6. Delete unused authors (those without any stories)
                $stmt = $db->prepare("DELETE a FROM authors a
                          LEFT JOIN story_authors sa ON a.id = sa.author_id
                          WHERE sa.author_id IS NULL AND a.author_type = ?");
                $stmt->execute([$sourceType]);
                echo "<p class='info'>Deleted unused $sourceType authors</p>";
                flushOutput();
                
                // 7. Delete media files associated with these stories
                if (!empty($mediaIds)) {
                    $mediaIdList = implode(',', $mediaIds);
                    $stmt = $db->prepare("DELETE FROM media WHERE id IN ($mediaIdList)");
                    $stmt->execute();
                    $deletedMedia = $stmt->rowCount();
                    echo "<p class='info'>Deleted $deletedMedia media files associated with these stories</p>";
                    flushOutput();
                }
            } else {
                echo "<p class='info'>No existing $sourceType stories found to clean</p>";
                flushOutput();
            }
        } elseif ($contentType === 'games') {
            // Similar logic for games
            $gameIdsStmt = $db->prepare("SELECT id FROM games WHERE source_type = ?");
            $gameIdsStmt->execute([$sourceType]);
            $gameIds = $gameIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($gameIds)) {
                $gameIdList = implode(',', $gameIds);
                
                // Check if media_id column exists in games table
                $checkColumnStmt = $db->query("SHOW COLUMNS FROM games LIKE 'media_id'");
                $mediaIds = [];
                
                if ($checkColumnStmt->rowCount() > 0) {
                    // Get media IDs associated with these games
                    $mediaIdsStmt = $db->prepare("SELECT media_id FROM games WHERE id IN ($gameIdList) AND media_id IS NOT NULL");
                    $mediaIdsStmt->execute();
                    $mediaIds = $mediaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    echo "<p class='info'>No media_id column found in games table, skipping media cleanup</p>";
                    flushOutput();
                }
                
                // Delete game_tags associations
                $stmt = $db->prepare("DELETE FROM game_tags WHERE game_id IN ($gameIdList)");
                $stmt->execute();
                $deletedAssociations += $stmt->rowCount();
                echo "<p class='info'>Deleted " . $stmt->rowCount() . " game-tag associations</p>";
                flushOutput();
                
                // Delete the games
                $stmt = $db->prepare("DELETE FROM games WHERE id IN ($gameIdList)");
                $stmt->execute();
                $deletedItems = $stmt->rowCount();
                echo "<p class='info'>Deleted $deletedItems existing $sourceType games</p>";
                flushOutput();
                
                // Delete media files associated with these games
                if (!empty($mediaIds)) {
                    $mediaIdList = implode(',', $mediaIds);
                    $stmt = $db->prepare("DELETE FROM media WHERE id IN ($mediaIdList)");
                    $stmt->execute();
                    $deletedMedia = $stmt->rowCount();
                    echo "<p class='info'>Deleted $deletedMedia media files associated with these games</p>";
                    flushOutput();
                }
            } else {
                echo "<p class='info'>No existing $sourceType games found to clean</p>";
                flushOutput();
            }
        } elseif ($contentType === 'retail_stories') {
            // Logic for retail publisher stories
            $storyIdsStmt = $db->prepare("SELECT id FROM stories WHERE source_type = ?");
            $storyIdsStmt->execute(['retail']);
            $storyIds = $storyIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($storyIds)) {
                $storyIdList = implode(',', $storyIds);
                
                // Check if media_id column exists in stories table
                $checkColumnStmt = $db->query("SHOW COLUMNS FROM stories LIKE 'media_id'");
                $mediaIds = [];
                
                if ($checkColumnStmt->rowCount() > 0) {
                    // Get media IDs associated with these stories
                    $mediaIdsStmt = $db->prepare("SELECT media_id FROM stories WHERE id IN ($storyIdList) AND media_id IS NOT NULL");
                    $mediaIdsStmt->execute();
                    $mediaIds = $mediaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    echo "<p class='info'>No media_id column found in stories table, skipping media cleanup</p>";
                    flushOutput();
                }
                
                // Delete story_tags associations
                $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN ($storyIdList)");
                $stmt->execute();
                $deletedAssociations += $stmt->rowCount();
                echo "<p class='info'>Deleted " . $stmt->rowCount() . " story-tag associations</p>";
                flushOutput();
                
                // Delete story_authors associations
                $stmt = $db->prepare("DELETE FROM story_authors WHERE story_id IN ($storyIdList)");
                $stmt->execute();
                $deletedAssociations += $stmt->rowCount();
                echo "<p class='info'>Deleted " . $stmt->rowCount() . " story-author associations</p>";
                flushOutput();
                
                // Delete the stories
                $stmt = $db->prepare("DELETE FROM stories WHERE id IN ($storyIdList)");
                $stmt->execute();
                $deletedItems = $stmt->rowCount();
                echo "<p class='info'>Deleted $deletedItems existing retail stories</p>";
                flushOutput();
                
                // Delete media files associated with these stories
                if (!empty($mediaIds)) {
                    $mediaIdList = implode(',', $mediaIds);
                    $stmt = $db->prepare("DELETE FROM media WHERE id IN ($mediaIdList)");
                    $stmt->execute();
                    $deletedMedia = $stmt->rowCount();
                    echo "<p class='info'>Deleted $deletedMedia media files associated with these stories</p>";
                    flushOutput();
                }
            } else {
                echo "<p class='info'>No existing retail stories found to clean</p>";
                flushOutput();
            }
        } elseif ($contentType === 'books') {
            // Logic for books (as directory items)
            try {
                // Get IDs of directory items that are books
                $dirItemIdsStmt = $db->prepare("SELECT id FROM directory_items WHERE type = 'book'");
                $dirItemIdsStmt->execute();
                $dirItemIds = $dirItemIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($dirItemIds)) {
                    $dirItemIdList = implode(',', $dirItemIds);
                    
                    // Delete book entries
                    $stmt = $db->prepare("DELETE FROM books WHERE directory_item_id IN ($dirItemIdList)");
                    $stmt->execute();
                    $deletedAssociations += $stmt->rowCount();
                    echo "<p class='info'>Deleted " . $stmt->rowCount() . " book entries</p>";
                    flushOutput();
                    
                    // Delete directory items
                    $stmt = $db->prepare("DELETE FROM directory_items WHERE id IN ($dirItemIdList)");
                    $stmt->execute();
                    $deletedItems = $stmt->rowCount();
                    echo "<p class='info'>Deleted $deletedItems existing book directory items</p>";
                    flushOutput();
                } else {
                    echo "<p class='info'>No existing book entries found to clean</p>";
                    flushOutput();
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error cleaning book data: " . $e->getMessage() . "</p>";
                flushOutput();
                throw $e; // Re-throw to be caught by the outer try-catch
            }
        }
        
        // Commit transaction
        $db->commit();
        echo "<p class='success'>Database cleaned successfully: removed $deletedItems items, $deletedAssociations associations, and $deletedMedia media files</p>";
        flushOutput();
        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<p class='error'>Clean operation failed: " . $e->getMessage() . "</p>";
        flushOutput();
        return false;
    }
}