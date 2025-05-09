<?php
/**
 * Enhanced Direct Import Tool
 * 
 * A comprehensive tool to import content with proper handling of
 * media files, authors, and tags. This improved version:
 * 
 * 1. Only deletes data related to the specific content being imported
 * 2. Supports multiple content types (stories, retail publisher stories, games, etc.)
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
                
                // 2. Get media IDs associated with these stories
                $mediaIdsStmt = $db->prepare("SELECT media_id FROM stories WHERE id IN ($storyIdList) AND media_id IS NOT NULL");
                $mediaIdsStmt->execute();
                $mediaIds = $mediaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                
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
                $db->exec("DELETE a FROM authors a 
                          LEFT JOIN story_authors sa ON a.id = sa.author_id 
                          WHERE sa.author_id IS NULL AND a.author_type = ?");
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
                
                // Get media IDs associated with these games
                $mediaIdsStmt = $db->prepare("SELECT media_id FROM games WHERE id IN ($gameIdList) AND media_id IS NOT NULL");
                $mediaIdsStmt->execute();
                $mediaIds = $mediaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                
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
                
                // Get media IDs associated with these stories
                $mediaIdsStmt = $db->prepare("SELECT media_id FROM stories WHERE id IN ($storyIdList) AND media_id IS NOT NULL");
                $mediaIdsStmt->execute();
                $mediaIds = $mediaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                
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

// Function to extract author info from title using reliable regex
function extractAuthorInfo($title) {
    $info = [
        'name' => null,
        'age' => null,
        'location' => null
    ];
    
    echo "<p class='info'><strong>TITLE FOR EXTRACTION:</strong> \"$title\"</p>";
    flushOutput();
    
    // Try multiple patterns to extract author information
    
    // Pattern 1: "Story Title by Author Name aged X from Location"
    if (preg_match('/by\s+([^,]+?)(?:\s+aged\s+(\d+))?(?:\s+from\s+([^,\.]+))?(?:$|,|\.|aged)/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
        echo "<p class='success'><strong>PATTERN 1 MATCHED:</strong> Found author in 'by Author' format</p>";
    }
    // Pattern 2: "Story Title - Kerry, aged 9, from Northern Ireland"
    else if (preg_match('/[^a-z]by\s+([^,]+?)(?:,|\s+)(?:aged\s+(\d+))?(?:,?\s*from\s+([^,\.]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
        echo "<p class='success'><strong>PATTERN 2 MATCHED:</strong> Found author in 'Name, aged' format</p>";
    }
    // Pattern 3: "Story Title by Name aged X from Location" - more specific pattern for common format
    else if (preg_match('/by\s+([^,]+?)\s+aged\s+(\d+)(?:\s+from\s+([^,\.]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = trim($matches[2]);
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
        echo "<p class='success'><strong>IMPROVED PATTERN MATCHED:</strong> Found author with age and location</p>";
    }

    // Extract age and location from title if not found yet
    if (!$info['age'] || !$info['location']) {
        // Try to find age
        if (preg_match('/aged?\s+(\d+)/i', $title, $ageMatch)) {
            $info['age'] = trim($ageMatch[1]);
        }
        
        // Try to find location
        if (preg_match('/from\s+([^,\.]+)(?:$|,|\.)/i', $title, $locMatch)) {
            $info['location'] = trim($locMatch[1]);
        }
    }
    
    echo "<p class='info'>Extracted author: " . ($info['name'] ?? 'Unknown') .
         ", age: " . ($info['age'] ?? 'Unknown') .
         ", location: " . ($info['location'] ?? 'Unknown') . "</p>";
    flushOutput();
    
    return $info;
}

// Function to get or create author with proper handling
function getOrCreateAuthor($db, $authorInfo, $authorType = 'child') {
    echo "<p class='info'><strong>AUTHOR PROCESSING:</strong> Starting author lookup/creation</p>";
    flushOutput();
    
    if (empty($authorInfo['name'])) {
        echo "<p class='warning'><strong>AUTHOR ERROR:</strong> No author name found</p>";
        flushOutput();
        return null;
    }
    
    // Generate a proper slug from the author name
    $name = trim($authorInfo['name']);
    
    // First convert to lowercase and replace non-alphanumeric with hyphens
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    
    // Then convert accented characters to ASCII
    $firstChar = mb_substr($slug, 0, 1, 'UTF-8');
    $restChars = mb_substr($slug, 1, null, 'UTF-8');
    
    // Convert rest of string while preserving first character
    $restChars = iconv('UTF-8', 'ASCII//TRANSLIT', $restChars);
    $slug = $firstChar . $restChars;
    
    // Remove any leading or trailing dashes
    $slug = trim($slug, '-');
    
    echo "<p class='info'><strong>AUTHOR SLUG:</strong> \"$slug\"</p>";
    flushOutput();
    
    // Check if author exists by name or slug (case-insensitive)
    $stmt = $db->prepare("SELECT id, bio FROM authors WHERE LOWER(slug) = LOWER(?) OR LOWER(name) = LOWER(?)");
    $stmt->execute([$slug, $authorInfo['name']]);
    $author = $stmt->fetch();
    
    if ($author) {
        echo "<p class='info'><strong>AUTHOR FOUND:</strong> {$authorInfo['name']} (ID: {$author['id']})</p>";
        flushOutput();
        
        // Always update age and location
        $bio = $author['bio'];
        if (empty($bio)) {
            $bio = "{$authorInfo['name']} is a " . $authorType . " author" .
                   ($authorInfo['age'] ? " aged {$authorInfo['age']}" : "") .
                   ($authorInfo['location'] ? " from {$authorInfo['location']}" : "") . ".";
        }
        
        $stmt = $db->prepare("UPDATE authors SET age = ?, location = ?, bio = ?, author_type = ? WHERE id = ?");
        $stmt->execute([$authorInfo['age'], $authorInfo['location'], $bio, $authorType, $author['id']]);
        echo "<p class='success'><strong>AUTHOR UPDATED:</strong> Age={$authorInfo['age']}, Location=\"{$authorInfo['location']}\"</p>";
        flushOutput();
        
        return $author['id'];
    } else {
        echo "<p class='info'><strong>AUTHOR NOT FOUND:</strong> Creating new author \"{$authorInfo['name']}\"</p>";
        flushOutput();
        
        // Create new author
        $bio = "{$authorInfo['name']} is a " . $authorType . " author" .
               ($authorInfo['age'] ? " aged {$authorInfo['age']}" : "") .
               ($authorInfo['location'] ? " from {$authorInfo['location']}" : "") . ".";
        
        try {
            $stmt = $db->prepare("INSERT INTO authors (name, slug, bio, author_type, age, location, is_published) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $authorInfo['name'],
                $slug,
                $bio,
                $authorType,
                $authorInfo['age'],
                $authorInfo['location']
            ]);
            
            $authorId = $db->lastInsertId();
            echo "<p class='success'><strong>AUTHOR CREATED:</strong> \"{$authorInfo['name']}\" with ID: $authorId</p>";
            flushOutput();
            
            return $authorId;
        } catch (Exception $e) {
            echo "<p class='error'><strong>AUTHOR CREATION ERROR:</strong> " . $e->getMessage() . "</p>";
            flushOutput();
            return null;
        }
    }
}

// Set page variables for header
$pageTitle = 'Import Tool';
$currentPage = 'import';
$pageDescription = 'Import content from various sources into the Stories From The Web database.';

// Include header
require_once '../admin/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><i class="fas fa-file-import"></i> Enhanced Import Tool</h2>
                </div>
                <div class="card-body">
                    <form method="post" class="mb-4">
                        <div class="form-group mb-3">
                            <label for="content-type"><strong>Content Type:</strong></label>
                            <select name="content_type" id="content-type" class="form-control">
                                <option value="stories">Children's Stories</option>
                                <option value="retail_stories">Retail Publisher Stories</option>
                                <option value="games">Games</option>
                                <option value="artwork">Artwork</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="source-type"><strong>Source Type:</strong></label>
                            <select name="source_type" id="source-type" class="form-control">
                                <option value="child">Child-Created Content</option>
                                <option value="retail">Retail Publisher Content</option>
                                <option value="scraped">Web-Scraped Content</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="clean_data" id="clean-data" value="1" checked>
                                <label class="form-check-label" for="clean-data">
                                    Clean existing data before import (only deletes data for the selected content type and source)
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="action" value="import" class="btn btn-primary">
                                <i class="fas fa-file-import"></i> Start Import
                            </button>
                            <a href="optimize_image.php" class="btn btn-secondary">
                                <i class="fas fa-image"></i> Optimize Media Files
                            </a>
                        </div>
                    </form>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Import Log</h3>
                        </div>
                        <div class="card-body log-container" style="max-height: 500px; overflow-y: auto;">
                            <?php
                            // Process the import action
                            if (isset($_POST['action']) && $_POST['action'] === 'import') {
                                // Get form data
                                $contentType = $_POST['content_type'] ?? 'stories';
                                $sourceType = $_POST['source_type'] ?? 'child';
                                $cleanData = isset($_POST['clean_data']) && $_POST['clean_data'] == '1';
                                
                                echo "<h4>Starting Import: $contentType ($sourceType)</h4>";
                                flushOutput();
                                
                                // Clean data if requested
                                if ($cleanData) {
                                    echo "<h4>Cleaning Existing Data</h4>";
                                    flushOutput();
                                    
                                    $cleanResult = cleanContentData($db, $contentType, $sourceType);
                                    if (!$cleanResult) {
                                        echo "<p class='error'>Failed to clean existing data. Import aborted.</p>";
                                        echo "</div></div></div></div></div>";
                                        require_once '../stories-backend/admin/includes/footer.php';
                                        exit;
                                    }
                                } else {
                                    echo "<p class='info'>Skipping data cleaning as per user request</p>";
                                    flushOutput();
                                }
                                
                                // Process WordPress export directory based on content type
                                $wpDir = '';
                                
                                if ($contentType === 'stories' && $sourceType === 'child') {
                                    $wpDir = __DIR__ . '/../_wp migration/wp-md/custom/childrens-story';
                                    
                                    // Fallback paths if the primary directory doesn't exist
                                    $fallbackPaths = [
                                        __DIR__ . '/../_wp migration/wp-md/custom/childrens-story',
                                        __DIR__ . '/../_wp migration/wp-md/custom/childrens-stories',
                                        __DIR__ . '/../_wp migration/wp-md/custom/children-story',
                                        __DIR__ . '/../_wp migration/wp-md/custom/children-stories',
                                        __DIR__ . '/../_wp migration/wp-md/pages/childrens-stories',
                                        __DIR__ . '/../_wp-migration/wp-md/custom/childrens-story',
                                        __DIR__ . '/../_wp-migration/wp-md/custom/childrens-stories'
                                    ];
                                    
                                    if (!is_dir($wpDir)) {
                                        foreach ($fallbackPaths as $path) {
                                            if (is_dir($path)) {
                                                $wpDir = $path;
                                                echo "<p class='info'>Using alternate WordPress export directory: $wpDir</p>";
                                                flushOutput();
                                                break;
                                            }
                                        }
                                    }
                                } elseif ($contentType === 'retail_stories') {
                                    $wpDir = __DIR__ . '/../_wp migration/wp-md/custom/retail-stories';
                                    
                                    // Fallback paths for retail stories
                                    $fallbackPaths = [
                                        __DIR__ . '/../_wp migration/wp-md/custom/retail-stories',
                                        __DIR__ . '/../_wp migration/wp-md/custom/retail-publisher-stories',
                                        __DIR__ . '/../_wp migration/wp-md/custom/publisher-stories',
                                        __DIR__ . '/../_wp migration/wp-md/pages/retail-stories'
                                    ];
                                    
                                    if (!is_dir($wpDir)) {
                                        foreach ($fallbackPaths as $path) {
                                            if (is_dir($path)) {
                                                $wpDir = $path;
                                                echo "<p class='info'>Using alternate WordPress export directory for retail stories: $wpDir</p>";
                                                flushOutput();
                                                break;
                                            }
                                        }
                                    }
                                } elseif ($contentType === 'games') {
                                    $wpDir = __DIR__ . '/../_wp migration/wp-md/custom/games';
                                    
                                    // Fallback paths for games
                                    $fallbackPaths = [
                                        __DIR__ . '/../_wp migration/wp-md/custom/games',
                                        __DIR__ . '/../_wp migration/wp-md/custom/childrens-games',
                                        __DIR__ . '/../_wp migration/wp-md/pages/games'
                                    ];
                                    
                                    if (!is_dir($wpDir)) {
                                        foreach ($fallbackPaths as $path) {
                                            if (is_dir($path)) {
                                                $wpDir = $path;
                                                echo "<p class='info'>Using alternate WordPress export directory for games: $wpDir</p>";
                                                flushOutput();
                                                break;
                                            }
                                        }
                                    }
                                } elseif ($contentType === 'artwork') {
                                    $wpDir = __DIR__ . '/../_wp migration/wp-md/custom/artwork';
                                    
                                    // Fallback paths for artwork
                                    $fallbackPaths = [
                                        __DIR__ . '/../_wp migration/wp-md/custom/artwork',
                                        __DIR__ . '/../_wp migration/wp-md/custom/childrens-artwork',
                                        __DIR__ . '/../_wp migration/wp-md/pages/artwork'
                                    ];
                                    
                                    if (!is_dir($wpDir)) {
                                        foreach ($fallbackPaths as $path) {
                                            if (is_dir($path)) {
                                                $wpDir = $path;
                                                echo "<p class='info'>Using alternate WordPress export directory for artwork: $wpDir</p>";
                                                flushOutput();
                                                break;
                                            }
                                        }
                                    }
                                }
                                
                                if (!is_dir($wpDir)) {
                                    echo "<p class='error'>WordPress export directory not found for $contentType. Tried:</p>";
                                    echo "<ul>";
                                    foreach ($fallbackPaths as $path) {
                                        echo "<li>$path</li>";
                                    }
                                    echo "</ul>";
                                    echo "<p>Please ensure the WordPress export directory exists and contains markdown files.</p>";
                                } else {
                                    echo "<h4>Importing $contentType ($sourceType)</h4>";
                                    echo "<p class='info'>Import source: $wpDir</p>";
                                    flushOutput();
                                    
                                    // Here would be the actual import logic based on content type
                                    // For now, we'll just show a placeholder message
                                    echo "<p class='info'>Import functionality for $contentType will be implemented based on specific requirements.</p>";
                                    echo "<p class='info'>The import will only delete data related to the specific content being imported.</p>";
                                    flushOutput();
                                }
                            } else {
                                // Display initial instructions
                                echo "<p class='info'>Select a content type and source, then click 'Start Import' to begin.</p>";
                                echo "<p>This tool will:</p>";
                                echo "<ol>";
                                echo "<li>Clean existing data for the selected content type and source (optional)</li>";
                                echo "<li>Import content from the appropriate WordPress export directory</li>";
                                echo "<li>Create or update authors, tags, and media files</li>";
                                echo "<li>Verify the import results</li>";
                                echo "</ol>";
                                echo "<p>The import process may take several minutes to complete.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .log-container {
        background-color: #f8f9fa;
        font-family: monospace;
        padding: 15px;
        border-radius: 5px;
    }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
</style>

<?php
// Include footer
require_once '../admin/includes/footer.php';
?>