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
// Function to extract clean, meaningful excerpt
function extractExcerpt($title, $markdownContent) {
    // Strip out "by ... aged ... from ..." metadata from title
    $cleanTitle = preg_replace('/by\s+[^,]+(?:,?\s+aged\s+\d+)?(?:,?\s+from\s+[^,.]+)?/i', '', $title);
    $cleanTitle = trim($cleanTitle);
    
    // First try to get from Summary section
    if (preg_match('/Summary\s*\n(.*?)(?:\n\n|\n#|\n\*\*|$)/s', $markdownContent, $summaryMatch)) {
        $summary = trim($summaryMatch[1]);
        
        // Extract just the first sentence
        if (preg_match('/^(.*?[.!?])(?:\s|$)/s', $summary, $sentenceMatch)) {
            return trim($sentenceMatch[1]);
        } else {
            return $summary;
        }
    }
    
    // If no summary or empty excerpt, use first paragraph
    $paragraphs = preg_split('/\n\s*\n/', $markdownContent);
    $firstPara = trim($paragraphs[0]);
    
    // Remove any metadata like Name/Age/Location
    $firstPara = preg_replace('/^(?:Name|Age|Location):\s+.*$/m', '', $firstPara);
    
    // Extract just the first sentence
    if (preg_match('/^(.*?[.!?])(?:\s|$)/s', $firstPara, $sentenceMatch)) {
        return trim($sentenceMatch[1]);
    } else {
        return substr(strip_tags($firstPara), 0, 150) . '...';
    }
}

// Function to extract tags from content
function extractTags($frontMatter, $markdownContent) {
    $tags = [];
    
    // Try to extract tags from front matter
    if (preg_match('/tags:\s*\[(.*?)\]/i', $frontMatter, $matches) ||
        preg_match('/tags:\s*(.+)$/im', $frontMatter, $matches)) {
        $tagString = $matches[1];
        $tagArray = explode(',', $tagString);
        foreach ($tagArray as $tag) {
            $tag = trim($tag, " \t\n\r\0\x0B\"'");
            if (!empty($tag)) {
                $tags[] = $tag;
            }
        }
    }
    
    // If no tags found, extract from content
    if (empty($tags)) {
        // Extract keywords from title and content
        $content = strtolower($markdownContent);
        
        // Common children's story themes
        $commonThemes = [
            'adventure', 'animals', 'friendship', 'family', 'magic', 'school',
            'fantasy', 'nature', 'space', 'dinosaurs', 'dragons', 'fairy', 'princess',
            'superhero', 'monster', 'robot', 'pirate', 'ghost', 'mystery', 'sports',
            'ocean', 'jungle', 'farm', 'zoo', 'circus', 'holiday', 'seasons',
            'winter', 'summer', 'spring', 'autumn', 'fall', 'christmas', 'halloween',
            'birthday', 'bedtime', 'dreams', 'imagination', 'learning', 'growing up'
        ];
        
        foreach ($commonThemes as $theme) {
            if (stripos($content, $theme) !== false) {
                $tags[] = $theme;
                
                // Limit to 5 tags
                if (count($tags) >= 5) {
                    break;
                }
            }
        }
    }
    
    // Ensure we have at least some tags
    if (empty($tags)) {
        $tags = ['children story', 'kids literature'];
    }
    
    // Normalize tags
    $normalizedTags = [];
    foreach ($tags as $tag) {
        // Convert to lowercase and remove special characters
        $normalizedTag = strtolower(trim($tag));
        $normalizedTag = preg_replace('/[^a-z0-9\s-]/', '', $normalizedTag);
        $normalizedTag = preg_replace('/\s+/', ' ', $normalizedTag);
        
        if (!empty($normalizedTag) && !in_array($normalizedTag, $normalizedTags)) {
            $normalizedTags[] = $normalizedTag;
        }
    }
    
    return $normalizedTags;
}

// Function to process story tags
function processStoryTags($db, $storyId, $tags) {
    try {
        // First delete existing tags for this story
        $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id = ?");
        $stmt->execute([$storyId]);
        
        // Process each tag
        foreach ($tags as $tagName) {
            // Check if tag exists
            $stmt = $db->prepare("SELECT id FROM tags WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([trim($tagName)]);
            $tag = $stmt->fetch();
            
            if ($tag) {
                $tagId = $tag['id'];
            } else {
                // Create new tag
                $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($tagName)));
                $slug = trim($slug, '-');
                
                $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt->execute([trim($tagName), $slug]);
                $tagId = $db->lastInsertId();
            }
            
            // Associate tag with story
            $stmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$storyId, $tagId]);
        }
        
        echo "<p class='success'>Processed " . count($tags) . " tags for story ID: $storyId</p>";
        flushOutput();
        return true;
    } catch (Exception $e) {
        echo "<p class='error'>Error processing tags: " . $e->getMessage() . "</p>";
        flushOutput();
        return false;
    }
}

// Function to find existing story by title or slug
function findExistingStory($db, $title, $slug) {
    $stmt = $db->prepare("SELECT id, title FROM stories WHERE LOWER(slug) = LOWER(?) OR LOWER(title) = LOWER(?)");
    $stmt->execute([$slug, $title]);
    return $stmt->fetch();
}

// Function to generate unique slug
function generateUniqueSlug($db, $title) {
    // Remove "by Author" part
    $title = preg_replace('/\s+by\s+[^,]+(?:,?\s+aged\s+\d+)?(?:,?\s+from\s+[^,.]+)?/i', '', $title);
    $title = trim($title);
    
    // Generate base slug
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $slug = trim($slug, '-');
    
    // Check if slug exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM stories WHERE slug = ?");
    $stmt->execute([$slug]);
    $result = $stmt->fetch();
    
    // If slug exists, append a number
    if ($result['count'] > 0) {
        $i = 1;
        $newSlug = $slug;
        do {
            $newSlug = $slug . '-' . $i++;
            $stmt->execute([$newSlug]);
            $result = $stmt->fetch();
        } while ($result['count'] > 0);
        $slug = $newSlug;
    }
    
    return $slug;
}

// Function to determine age group based on author age
function getAgeGroup($age) {
    if (!$age || !is_numeric($age)) {
        return '6-8'; // Default age group
    }
    
    $age = (int)$age;
    
    if ($age <= 5) {
        return '3-5';
    } elseif ($age <= 8) {
        return '6-8';
    } elseif ($age <= 12) {
        return '9-12';
    } else {
        return '13+';
    }
}

// Function to calculate reading time
function getReadingTime($content) {
    // Average reading speed: 200 words per minute for children's content
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200);
    return max(1, $minutes); // Minimum 1 minute
}

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

// Function to process a story directory and import it
function processStory($db, $storyDir) {
    $mdFile = "$storyDir/index.md";
    
    if (!file_exists($mdFile)) {
        echo "<p class='error'>Markdown file not found: $mdFile</p>";
        flushOutput();
        return false;
    }
    
    // Begin transaction for this story
    $db->beginTransaction();
    
    try {
        // Read markdown file
        $content = file_get_contents($mdFile);
        
        // Extract front matter
        $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)/s';
        if (!preg_match($pattern, $content, $matches)) {
            echo "<p class='error'>Invalid markdown format in: $mdFile</p>";
            flushOutput();
            $db->rollBack();
            return false;
        }
        
        $frontMatter = $matches[1];
        $markdownContent = $matches[2];
        
        // Parse front matter
        $data = [];
        $lines = explode("\n", $frontMatter);
        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $parts)) {
                $key = $parts[1];
                $value = trim($parts[2], '"\'');
                $data[$key] = $value;
            }
        }
        
        $title = isset($data['title']) ? $data['title'] : basename($storyDir);
        echo "<h3>Importing: $title</h3>";
        flushOutput();
        
        // Extract author info
        $authorInfo = extractAuthorInfo($title);
        $authorAge = isset($authorInfo['age']) && $authorInfo['age'] ? $authorInfo['age'] : 'unknown';
        $authorLocation = isset($authorInfo['location']) && $authorInfo['location'] ? $authorInfo['location'] : 'unknown';
        echo "<p class='info'><strong>Author extraction result:</strong> Name=\"{$authorInfo['name']}\", Age={$authorAge}, Location=\"{$authorLocation}\"</p>";
        flushOutput();
        
        $authorId = getOrCreateAuthor($db, $authorInfo, 'child');
        if ($authorId) {
            echo "<p class='success'><strong>Author ID:</strong> $authorId</p>";
        } else {
            echo "<p class='error'><strong>Failed to get or create author</strong></p>";
        }
        flushOutput();
        
        // Process cover image
        // Use default cover image if no image is found
        $defaultCoverUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/images/default-cover.svg';
        $coverUrl = $defaultCoverUrl; // Default
        
        // Check for images in the story directory
        $imagesDir = "$storyDir/images";
        if (is_dir($imagesDir)) {
            $images = glob("$imagesDir/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            if (!empty($images)) {
                // Use the first image as cover
                $coverUrl = '/uploads/' . basename($images[0]);
                
                // Copy image to uploads directory
                $uploadsDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                copy($images[0], $uploadsDir . '/' . basename($images[0]));
                echo "<p class='success'>Used image: " . basename($images[0]) . " as cover</p>";
                flushOutput();
            }
        }
        
        // Extract clean excerpt
        $excerpt = extractExcerpt($title, $markdownContent);
        echo "<p class='info'>Excerpt: " . htmlspecialchars(substr($excerpt, 0, 100)) . "...</p>";
        flushOutput();
        
        // Generate slug
        $slug = generateUniqueSlug($db, $title);
        
        // Calculate reading time
        $readingTime = getReadingTime($markdownContent);
        
        // Determine age group
        $ageGroup = getAgeGroup($authorInfo['age']);
        
        // Extract tags
        $tags = extractTags($frontMatter, $markdownContent);
        echo "<p class='info'>Tags: " . implode(', ', $tags) . "</p>";
        flushOutput();
        
        // Check if story exists
        $existingStory = findExistingStory($db, $title, $slug);
        
        if ($existingStory) {
            // Update existing story
            $stmt = $db->prepare("
                UPDATE stories SET
                    content = ?,
                    excerpt = ?,
                    cover_url = ?,
                    estimated_reading_time = ?,
                    age_group = ?,
                    source_type = 'child',
                    allow_reviews = 0
                WHERE id = ?
            ");
            
            $stmt->execute([
                $markdownContent,
                $excerpt,
                $coverUrl,
                $readingTime,
                $ageGroup,
                $existingStory['id']
            ]);
            
            echo "<p class='success'>Updated story: $title (ID: {$existingStory['id']})</p>";
            flushOutput();
            
            // Make sure author is associated
            if ($authorId) {
                try {
                    $checkStmt = $db->prepare("SELECT * FROM story_authors WHERE story_id = ? AND author_id = ?");
                    $checkStmt->execute([$existingStory['id'], $authorId]);
                    if (!$checkStmt->fetch()) {
                        $linkStmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                        $linkStmt->execute([$existingStory['id'], $authorId]);
                        echo "<p class='success'><strong>STORY-AUTHOR LINK CREATED:</strong> Story ID {$existingStory['id']} linked to Author ID $authorId</p>";
                        flushOutput();
                    } else {
                        echo "<p class='info'><strong>STORY-AUTHOR LINK EXISTS:</strong> Story already associated with author ID $authorId</p>";
                        flushOutput();
                    }
                } catch (Exception $e) {
                    echo "<p class='error'><strong>STORY-AUTHOR LINK ERROR:</strong> " . $e->getMessage() . "</p>";
                    flushOutput();
                }
            } else {
                echo "<p class='warning'><strong>STORY-AUTHOR LINK SKIPPED:</strong> No author ID available</p>";
                flushOutput();
            }
            
            // Process tags
            processStoryTags($db, $existingStory['id'], $tags);
            
            $storyId = $existingStory['id'];
        } else {
            // Insert new story
            $stmt = $db->prepare("
                INSERT INTO stories (
                    title, slug, content, excerpt, cover_url,
                    is_published, source_type, allow_reviews,
                    estimated_reading_time, age_group
                ) VALUES (?, ?, ?, ?, ?, 1, 'child', 0, ?, ?)
            ");
            
            $stmt->execute([
                $title,
                $slug,
                $markdownContent,
                $excerpt,
                $coverUrl,
                $readingTime,
                $ageGroup
            ]);
            
            $storyId = $db->lastInsertId();
            echo "<p class='success'>Created story with ID: $storyId</p>";
            flushOutput();
            
            // Associate with author
            if ($authorId) {
                try {
                    $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                    $stmt->execute([$storyId, $authorId]);
                    echo "<p class='success'><strong>STORY-AUTHOR LINK CREATED:</strong> Story ID $storyId linked to Author ID $authorId</p>";
                    flushOutput();
                } catch (Exception $e) {
                    echo "<p class='error'><strong>STORY-AUTHOR LINK ERROR:</strong> " . $e->getMessage() . "</p>";
                    flushOutput();
                }
            } else {
                echo "<p class='warning'><strong>STORY-AUTHOR LINK SKIPPED:</strong> No author ID available</p>";
                flushOutput();
            }
            
            // Process tags
            processStoryTags($db, $storyId, $tags);
        }
        
        // Commit the transaction
        $db->commit();
        echo "<p class='success'>Story transaction committed successfully</p>";
        flushOutput();
        
        return [
            'success' => true,
            'action' => $existingStory ? 'updated' : 'created',
            'id' => $storyId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "<p class='error'>Transaction rolled back</p>";
            flushOutput();
        }
        echo "<p class='error'>Error processing story: " . $e->getMessage() . "</p>";
        flushOutput();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Function to process a book
function processBook($db, $bookDir) {
    $mdFile = "$bookDir/index.md";
    
    if (!file_exists($mdFile)) {
        echo "<p class='error'>Markdown file not found: $mdFile</p>";
        flushOutput();
        return false;
    }
    
    // Begin transaction for this book
    $db->beginTransaction();
    
    try {
        // Read markdown file
        $content = file_get_contents($mdFile);
        
        // Extract front matter
        $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)/s';
        if (!preg_match($pattern, $content, $matches)) {
            echo "<p class='error'>Invalid markdown format in: $mdFile</p>";
            flushOutput();
            $db->rollBack();
            return false;
        }
        
        $frontMatter = $matches[1];
        $markdownContent = $matches[2];
        
        // Parse front matter
        $data = [];
        $lines = explode("\n", $frontMatter);
        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $parts)) {
                $key = $parts[1];
                $value = trim($parts[2], '"\'');
                $data[$key] = $value;
            }
        }
        
        $title = isset($data['title']) ? $data['title'] : basename($bookDir);
        echo "<h3>Processing Book: $title</h3>";
        flushOutput();
        
        // Extract book metadata
        $author = isset($data['author']) ? $data['author'] : '';
        $publisher = isset($data['publisher']) ? $data['publisher'] : '';
        $isbn = isset($data['isbn']) ? $data['isbn'] : '';
        $isbn13 = isset($data['isbn13']) ? $data['isbn13'] : '';
        $publicationDate = isset($data['publication_date']) ? $data['publication_date'] : null;
        $pageCount = isset($data['page_count']) ? intval($data['page_count']) : null;
        $ageRange = isset($data['age_range']) ? $data['age_range'] : '';
        $readingLevel = isset($data['reading_level']) ? $data['reading_level'] : '';
        
        // Process cover image
        $coverImageUrl = '';
        $imagesDir = "$bookDir/images";
        if (is_dir($imagesDir)) {
            $images = glob("$imagesDir/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            if (!empty($images)) {
                $coverImageUrl = '/uploads/books/' . basename($images[0]);
                
                // Copy image to uploads directory
                $uploadsDir = __DIR__ . '/../uploads/books';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                copy($images[0], $uploadsDir . '/' . basename($images[0]));
                echo "<p class='success'>Used image: " . basename($images[0]) . " as cover</p>";
                flushOutput();
            }
        }
        
        // Extract description from content
        $description = '';
        if (preg_match('/Summary\s*\n(.*?)(?:\n\n|\n#|\n\*\*|$)/s', $markdownContent, $summaryMatch)) {
            $description = trim($summaryMatch[1]);
        } else {
            // Use first paragraph as description
            $paragraphs = preg_split('/\n\s*\n/', $markdownContent);
            $description = trim($paragraphs[0]);
        }
        
        // Generate purchase links
        $purchaseLinks = [];
        if (isset($data['amazon_url'])) {
            $purchaseLinks['amazon'] = $data['amazon_url'];
        }
        if (isset($data['goodreads_url'])) {
            $purchaseLinks['goodreads'] = $data['goodreads_url'];
        }
        if (isset($data['publisher_url'])) {
            $purchaseLinks['publisher'] = $data['publisher_url'];
        }
        
        // Determine URL for the book
        $url = '';
        if (isset($data['url'])) {
            $url = $data['url'];
        } elseif (!empty($purchaseLinks['amazon'])) {
            $url = $purchaseLinks['amazon'];
        } elseif (!empty($purchaseLinks['publisher'])) {
            $url = $purchaseLinks['publisher'];
        } else {
            // Generate a search URL if no specific URL is available
            $encodedTitle = urlencode($title);
            $encodedAuthor = urlencode($author);
            $url = "https://www.google.com/search?q=$encodedTitle+by+$encodedAuthor+book";
        }
        
        // Determine category
        $category = isset($data['category']) ? $data['category'] : 'general';
        
        // Check if book already exists
        $stmt = $db->prepare("
            SELECT b.directory_item_id, d.name
            FROM books b
            JOIN directory_items d ON b.directory_item_id = d.id
            WHERE d.name = ?
        ");
        $stmt->execute([$title]);
        $existingBook = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingBook) {
            // Update existing book
            $directoryItemId = $existingBook['directory_item_id'];
            
            // Update directory item
            $stmt = $db->prepare("
                UPDATE directory_items
                SET description = ?, url = ?, category = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $description,
                $url,
                $category,
                $directoryItemId
            ]);
            
            // Update book
            $stmt = $db->prepare("
                UPDATE books
                SET isbn = ?, isbn13 = ?, author = ?, publisher = ?,
                    publication_date = ?, page_count = ?, age_range = ?,
                    reading_level = ?, cover_image_url = ?, purchase_links = ?,
                    metadata = ?
                WHERE directory_item_id = ?
            ");
            $stmt->execute([
                $isbn,
                $isbn13,
                $author,
                $publisher,
                $publicationDate,
                $pageCount,
                $ageRange,
                $readingLevel,
                $coverImageUrl,
                json_encode($purchaseLinks),
                json_encode($data),
                $directoryItemId
            ]);
            
            echo "<p class='success'>Updated existing book: $title (ID: $directoryItemId)</p>";
            flushOutput();
        } else {
            // Insert into directory_items
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("
                INSERT INTO directory_items (
                    name, description, url, category, type, created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'book', ?, ?)
            ");
            
            $stmt->execute([
                $title,
                $description,
                $url,
                $category,
                $now,
                $now
            ]);
            
            $directoryItemId = $db->lastInsertId();
            
            // Insert into books
            $stmt = $db->prepare("
                INSERT INTO books (
                    directory_item_id, isbn, isbn13, author, publisher,
                    publication_date, page_count, age_range, reading_level,
                    cover_image_url, purchase_links, metadata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $directoryItemId,
                $isbn,
                $isbn13,
                $author,
                $publisher,
                $publicationDate,
                $pageCount,
                $ageRange,
                $readingLevel,
                $coverImageUrl,
                json_encode($purchaseLinks),
                json_encode($data)
            ]);
            
            echo "<p class='success'>Created new book: $title (ID: $directoryItemId)</p>";
            flushOutput();
        }
        
        // Commit the transaction
        $db->commit();
        echo "<p class='success'>Book transaction committed successfully</p>";
        flushOutput();
        
        return [
            'success' => true,
            'action' => $existingBook ? 'updated' : 'created',
            'id' => $directoryItemId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "<p class='error'>Transaction rolled back</p>";
            flushOutput();
        }
        echo "<p class='error'>Error processing book: " . $e->getMessage() . "</p>";
        flushOutput();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Set page variables for header
$pageTitle = 'Import Tool';
$currentPage = 'import';
$pageDescription = '';

// Include header
require_once '../admin/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><i class="fas fa-file-import"></i></h2>
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
                                <option value="books">Recommended Books</option>
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
                            <a href="../docs/import-documentation.php" class="btn btn-info" target="_blank">
                                <i class="fas fa-book"></i> Documentation
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
                                        require_once '../admin/includes/footer.php';
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
                                } elseif ($contentType === 'books') {
                                    $wpDir = __DIR__ . '/../_wp migration/wp-md/custom/book';
                                    
                                    // Fallback paths for books
                                    $fallbackPaths = [
                                        __DIR__ . '/../_wp migration/wp-md/custom/book',
                                        __DIR__ . '/../_wp migration/wp-md/custom/books',
                                        __DIR__ . '/../_wp migration/wp-md/pages/books'
                                    ];
                                    
                                    if (!is_dir($wpDir)) {
                                        foreach ($fallbackPaths as $path) {
                                            if (is_dir($path)) {
                                                $wpDir = $path;
                                                echo "<p class='info'>Using alternate WordPress export directory for books: $wpDir</p>";
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
                                    
                                    // Process WordPress export directory
                                    if ($contentType === 'stories' && $sourceType === 'child') {
                                        // Get all story directories
                                        $storyDirs = [];
                                        try {
                                            $storyDirs = array_filter(glob("$wpDir/*"), 'is_dir');
                                            echo "<p>Found " . count($storyDirs) . " potential story directories</p>";
                                            flushOutput();
                                            
                                            // If no directories found, try recursive search
                                            if (count($storyDirs) === 0) {
                                                echo "<p class='info'>No story directories found at top level, searching recursively...</p>";
                                                flushOutput();
                                                
                                                $iterator = new RecursiveIteratorIterator(
                                                    new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
                                                );
                                                
                                                foreach ($iterator as $file) {
                                                    if ($file->isFile() && $file->getFilename() === 'index.md') {
                                                        $storyDirs[] = dirname($file->getPathname());
                                                    }
                                                }
                                                
                                                echo "<p>Found " . count($storyDirs) . " story directories through recursive search</p>";
                                                flushOutput();
                                            }
                                        } catch (Exception $e) {
                                            echo "<p class='error'>Error scanning directories: " . $e->getMessage() . "</p>";
                                            flushOutput();
                                        }
                                        
                                        if (count($storyDirs) === 0) {
                                            echo "<p class='error'>No story directories found. Import aborted.</p>";
                                            flushOutput();
                                        } else {
                                            // Stats
                                            $stats = [
                                                'created' => 0,
                                                'updated' => 0,
                                                'skipped' => 0,
                                                'errors' => 0
                                            ];
                                            
                                            // Process each story
                                            foreach ($storyDirs as $storyDir) {
                                                try {
                                                    $result = processStory($db, $storyDir);
                                                    
                                                    if ($result && $result['success']) {
                                                        $stats[$result['action']]++;
                                                    } else {
                                                        $stats['errors']++;
                                                    }
                                                } catch (Exception $e) {
                                                    echo "<p class='error'>Unexpected error processing story directory '$storyDir': " . $e->getMessage() . "</p>";
                                                    flushOutput();
                                                    $stats['errors']++;
                                                    // Continue with next story
                                                    continue;
                                                }
                                                
                                                // Add a small delay to prevent server overload
                                                usleep(100000); // 0.1 second
                                            }
                                            
                                            // Display summary
                                            echo "<h3>Import Complete!</h3>";
                                            echo "<p class='success'>Summary:</p>";
                                            echo "<ul>";
                                            echo "<li>Created: {$stats['created']} stories</li>";
                                            echo "<li>Updated: {$stats['updated']} stories</li>";
                                            echo "<li>Skipped: {$stats['skipped']} stories</li>";
                                            echo "<li>Errors: {$stats['errors']} stories</li>";
                                            echo "</ul>";
                                            
                                            echo "<p>Check the <a href='/admin/stories'>Stories Admin</a> to verify the imported content.</p>";
                                            flushOutput();
                                        }
                                    } elseif ($contentType === 'books') {
                                        // Get all book directories
                                        $bookDirs = [];
                                        try {
                                            $bookDirs = array_filter(glob("$wpDir/*"), 'is_dir');
                                            echo "<p>Found " . count($bookDirs) . " potential book directories</p>";
                                            flushOutput();
                                            
                                            // If no directories found, try recursive search
                                            if (count($bookDirs) === 0) {
                                                echo "<p class='info'>No book directories found at top level, searching recursively...</p>";
                                                flushOutput();
                                                
                                                $iterator = new RecursiveIteratorIterator(
                                                    new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
                                                );
                                                
                                                foreach ($iterator as $file) {
                                                    if ($file->isFile() && $file->getFilename() === 'index.md') {
                                                        $bookDirs[] = dirname($file->getPathname());
                                                    }
                                                }
                                                
                                                echo "<p>Found " . count($bookDirs) . " book directories through recursive search</p>";
                                                flushOutput();
                                            }
                                        } catch (Exception $e) {
                                            echo "<p class='error'>Error scanning directories: " . $e->getMessage() . "</p>";
                                            flushOutput();
                                        }
                                        
                                        if (count($bookDirs) === 0) {
                                            echo "<p class='error'>No book directories found. Import aborted.</p>";
                                            flushOutput();
                                        } else {
                                            // Stats
                                            $stats = [
                                                'created' => 0,
                                                'updated' => 0,
                                                'skipped' => 0,
                                                'errors' => 0
                                            ];
                                            
                                            // Process each book
                                            foreach ($bookDirs as $bookDir) {
                                                try {
                                                    $result = processBook($db, $bookDir);
                                                    
                                                    if ($result && $result['success']) {
                                                        $stats[$result['action']]++;
                                                    } else {
                                                        $stats['errors']++;
                                                    }
                                                } catch (Exception $e) {
                                                    echo "<p class='error'>Unexpected error processing book directory '$bookDir': " . $e->getMessage() . "</p>";
                                                    flushOutput();
                                                    $stats['errors']++;
                                                    // Continue with next book
                                                    continue;
                                                }
                                                
                                                // Add a small delay to prevent server overload
                                                usleep(100000); // 0.1 second
                                            }
                                            
                                            // Display summary
                                            echo "<h3>Import Complete!</h3>";
                                            echo "<p class='success'>Summary:</p>";
                                            echo "<ul>";
                                            echo "<li>Created: {$stats['created']} books</li>";
                                            echo "<li>Updated: {$stats['updated']} books</li>";
                                            echo "<li>Skipped: {$stats['skipped']} books</li>";
                                            echo "<li>Errors: {$stats['errors']} books</li>";
                                            echo "</ul>";
                                            
                                            echo "<p>Check the <a href='/admin/directory'>Directory Admin</a> to verify the imported content.</p>";
                                            flushOutput();
                                        }
                                    } else {
                                        // For other content types, show placeholder message
                                        echo "<p class='info'>Import functionality for $contentType will be implemented based on specific requirements.</p>";
                                        echo "<p class='info'>The import will only delete data related to the specific content being imported.</p>";
                                        flushOutput();
                                    }
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