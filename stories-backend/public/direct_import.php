<?php
/**
 * Direct WordPress Import Tool
 * 
 * A comprehensive tool to import WordPress content with proper handling of
 * media files, authors, and tags.
 * 
 * Features:
 * - One-click import with cleaning option
 * - Robust database transactions for each story
 * - Accurate author extraction and handling
 * - Clean, meaningful excerpts
 * - Proper slug and story matching
 * - Better tag generation and linking
 * - Bullet-proof media uploads
 * - Real-time debug and error handling
 */

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

// Database connection function with error handling
function connectToDatabase() {
    try {
        $db = new PDO(
            'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
            'stories_user',
            '$tw1cac3*sOt',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        echo "<p class='success'>Database connection successful</p>";
        flushOutput();
        return $db;
    } catch (PDOException $e) {
        echo "<p class='error'>Database connection failed: " . $e->getMessage() . "</p>";
        flushOutput();
        exit;
    }
}

// Function to clean all child-story data
function cleanChildStoryData($db) {
    try {
        // Begin transaction
        $db->beginTransaction();

        // 1. Delete story_tags associations for child stories
        $db->exec("DELETE st FROM story_tags st 
                  JOIN stories s ON st.story_id = s.id 
                  WHERE s.source_type = 'child'");
        echo "<p class='info'>Deleted story-tag associations for child stories</p>";
        flushOutput();
        
        // 2. Delete story_authors associations for child stories
        $db->exec("DELETE sa FROM story_authors sa 
                  JOIN stories s ON sa.story_id = s.id 
                  WHERE s.source_type = 'child'");
        echo "<p class='info'>Deleted story-author associations for child stories</p>";
        flushOutput();
        
        // 3. Delete child stories
        $stmt = $db->prepare("DELETE FROM stories WHERE source_type = 'child'");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "<p class='info'>Deleted $count existing child stories</p>";
        flushOutput();
        
        // 4. Delete unused authors (those without any stories)
        $db->exec("DELETE a FROM authors a 
                  LEFT JOIN story_authors sa ON a.id = sa.author_id 
                  WHERE sa.author_id IS NULL AND a.author_type = 'child'");
        echo "<p class='info'>Deleted unused child authors</p>";
        flushOutput();
        
        // 5. Delete unused media files
        $db->exec("DELETE FROM media WHERE id > 1");
        echo "<p class='info'>Deleted existing media files</p>";
        flushOutput();
        
        // Commit transaction
        $db->commit();
        echo "<p class='success'>Database cleaned successfully</p>";
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
    
    // Pattern 1: "by <Name>, aged <Age>, from <Location>"
    if (preg_match('/by\s+([^,]+?)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,.]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
    }
    // Pattern 2: "Name, aged X, from Location"
    else if (preg_match('/([^,]+),\s+aged\s+(\d+)(?:,\s+from\s+([^,.]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
    }
    
    echo "<p class='info'>Extracted author: " . ($info['name'] ?? 'Unknown') .
         ", age: " . ($info['age'] ?? 'Unknown') .
         ", location: " . ($info['location'] ?? 'Unknown') . "</p>";
    flushOutput();
    
    return $info;
}

// Function to get or create author with proper handling
function getOrCreateAuthor($db, $authorInfo) {
    if (empty($authorInfo['name'])) {
        echo "<p class='warning'>No author name found</p>";
        flushOutput();
        return null;
    }
    
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorInfo['name']));
    
    // Check if author exists by name or slug (case-insensitive)
    $stmt = $db->prepare("SELECT id, bio FROM authors WHERE LOWER(slug) = LOWER(?) OR LOWER(name) = LOWER(?)");
    $stmt->execute([$slug, $authorInfo['name']]);
    $author = $stmt->fetch();
    
    if ($author) {
        echo "<p class='info'>Author already exists: {$authorInfo['name']} (ID: {$author['id']})</p>";
        flushOutput();
        
        // Always update age and location
        $bio = $author['bio'];
        if (empty($bio)) {
            $bio = "{$authorInfo['name']} is a child author" . 
                   ($authorInfo['age'] ? " aged {$authorInfo['age']}" : "") . 
                   ($authorInfo['location'] ? " from {$authorInfo['location']}" : "") . ".";
        }
        
        $stmt = $db->prepare("UPDATE authors SET age = ?, location = ?, bio = ?, author_type = 'child' WHERE id = ?");
        $stmt->execute([$authorInfo['age'], $authorInfo['location'], $bio, $author['id']]);
        echo "<p class='success'>Updated author information</p>";
        flushOutput();
        
        return $author['id'];
    } else {
        // Create new author
        $bio = "{$authorInfo['name']} is a child author" . 
               ($authorInfo['age'] ? " aged {$authorInfo['age']}" : "") . 
               ($authorInfo['location'] ? " from {$authorInfo['location']}" : "") . ".";
        
        $stmt = $db->prepare("INSERT INTO authors (name, slug, bio, author_type, age, location, is_published) VALUES (?, ?, ?, 'child', ?, ?, 1)");
        $stmt->execute([
            $authorInfo['name'],
            $slug,
            $bio,
            $authorInfo['age'],
            $authorInfo['location']
        ]);
        
        $authorId = $db->lastInsertId();
        echo "<p class='success'>Created author with ID: $authorId</p>";
        flushOutput();
        
        return $authorId;
    }
}

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
// Function to handle media upload with proper error handling
function handleMediaUpload($db, $storyDir, $title) {
    $imagesDir = "$storyDir/images";
    $coverUrl = '/images/default-cover.svg'; // Default
    $mediaId = null;
    
    if (is_dir($imagesDir)) {
        $images = glob("$imagesDir/*.*");
        if (!empty($images)) {
            $coverImage = basename($images[0]);
            
            // Use absolute server path for uploads
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                echo "<p class='info'>Created uploads directory</p>";
                flushOutput();
            }
            
            // Generate unique filename to avoid collisions
            $uniqueFilename = uniqid() . '-' . $coverImage;
            $destination = $uploadDir . $uniqueFilename;
            
            // Ensure the uploads directory is web-accessible
            $publicUrl = '/uploads/' . $uniqueFilename;
            $fullPublicUrl = 'https://' . $_SERVER['HTTP_HOST'] . $publicUrl;
            
            if (copy($images[0], $destination)) {
                // Set proper permissions - ensure web server can read the file
                chmod($destination, 0644);
                
                // Verify the file exists and is readable
                if (file_exists($destination) && is_readable($destination)) {
                    echo "<p class='success'>Copied image to: $destination</p>";
                    echo "<p class='info'>Public URL: $fullPublicUrl</p>";
                    $coverUrl = $publicUrl;
                } else {
                    echo "<p class='warning'>File copied but may not be readable: $destination</p>";
                    echo "<p class='info'>Setting permissions again...</p>";
                    chmod($destination, 0644);
                    $coverUrl = $publicUrl;
                }
                flushOutput();
                
                // Get proper MIME type
                $fileSize = filesize($destination);
                
                // Try to use fileinfo extension if available
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $destination);
                    finfo_close($finfo);
                } else {
                    // Fallback: determine MIME type based on extension
                    $extension = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
                    $mimeTypes = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'svg' => 'image/svg+xml',
                        'pdf' => 'application/pdf'
                    ];
                    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
                    echo "<p class='info'>Using fallback MIME type detection: $mimeType</p>";
                    flushOutput();
                }
                
                // Create alt text
                $altText = "Illustration for story: " . $title;
                
                // Add to media library
                try {
                    // Store both the relative path and the full URL for better compatibility
                    $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([
                        $uniqueFilename,
                        $publicUrl, // Use the public URL path that starts with /uploads/
                        $mimeType,
                        $fileSize,
                        $altText
                    ]);
                    $mediaId = $db->lastInsertId();
                    echo "<p class='success'>Added to media library (ID: $mediaId)</p>";
                    flushOutput();
                } catch (Exception $e) {
                    echo "<p class='error'>Failed to add to media library: " . $e->getMessage() . "</p>";
                    flushOutput();
                }
            } else {
                echo "<p class='error'>Failed to copy image: $images[0]</p>";
                flushOutput();
            }
        }
    }
    
    return [
        'cover_url' => $coverUrl,
        'media_id' => $mediaId
    ];
}

// Function to extract tags from front-matter and content
function extractTags($frontMatter, $markdownContent) {
    $tags = ['children\'s story']; // Always include this tag
    
    // Parse front-matter for tags or categories
    $lines = explode("\n", $frontMatter);
    foreach ($lines as $line) {
        if (preg_match('/^(tags|categories):\s*(.*)$/i', $line, $matches)) {
            $tagList = $matches[2];
            $frontMatterTags = explode(',', $tagList);
            foreach ($frontMatterTags as $tag) {
                $tag = trim($tag, " \t\n\r\0\x0B\"'[]");
                if (!empty($tag) && !in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
        }
    }
    
    // Add content-based tags if we don't have enough
    if (count($tags) < 3) {
        $keywords = [
            'adventure', 'animals', 'fantasy', 'friendship', 'magic', 
            'school', 'family', 'nature', 'space', 'dinosaurs', 
            'robots', 'monsters', 'fairy tale', 'mystery'
        ];
        
        $contentLower = strtolower($markdownContent);
        foreach ($keywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false && !in_array($keyword, $tags)) {
                $tags[] = $keyword;
                if (count($tags) >= 5) break; // Limit to 5 tags total
            }
        }
    }
    
    return $tags;
}
// Function to create or update tags and link to story
function processStoryTags($db, $storyId, $tags) {
    foreach ($tags as $tagName) {
        try {
            $tagSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tagName));
            
            // Check if tag exists (case-insensitive)
            $tagStmt = $db->prepare("SELECT id FROM tags WHERE LOWER(slug) = LOWER(?)");
            $tagStmt->execute([$tagSlug]);
            $tag = $tagStmt->fetch();
            
            if (!$tag) {
                // Create tag
                $createTagStmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $createTagStmt->execute([$tagName, $tagSlug]);
                $tagId = $db->lastInsertId();
                echo "<p class='success'>Created tag: $tagName</p>";
                flushOutput();
            } else {
                $tagId = $tag['id'];
            }
            
            // Associate tag with story if not already associated
            $checkTagStmt = $db->prepare("SELECT * FROM story_tags WHERE story_id = ? AND tag_id = ?");
            $checkTagStmt->execute([$storyId, $tagId]);
            if (!$checkTagStmt->fetch()) {
                $linkTagStmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                $linkTagStmt->execute([$storyId, $tagId]);
                echo "<p class='success'>Added tag '$tagName' to story</p>";
                flushOutput();
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error processing tag '$tagName': " . $e->getMessage() . "</p>";
            flushOutput();
            // Continue with other tags
        }
    }
}

// Function to check if story exists by title or slug (case-insensitive)
function findExistingStory($db, $title, $slug) {
    // First try by title (more reliable)
    $titleStmt = $db->prepare("SELECT id, slug FROM stories WHERE LOWER(title) = LOWER(?) OR title LIKE ?");
    $titleStmt->execute([trim($title), "%" . substr(trim($title), 0, 30) . "%"]);
    $existingStory = $titleStmt->fetch();
    
    if (!$existingStory) {
        // Fallback to slug check
        $slugStmt = $db->prepare("SELECT id, slug FROM stories WHERE LOWER(slug) = LOWER(?)");
        $slugStmt->execute([$slug]);
        $existingStory = $slugStmt->fetch();
    }
    
    return $existingStory;
}

// Function to generate a unique slug
function generateUniqueSlug($db, $title) {
    $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $baseSlug = trim($baseSlug, '-');
    $slug = $baseSlug;
    $counter = 1;
    
    $slugStmt = $db->prepare("SELECT id FROM stories WHERE LOWER(slug) = LOWER(?)");
    $slugStmt->execute([$slug]);
    
    while ($slugStmt->fetch()) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
        $slugStmt->execute([$slug]);
    }
    
    return $slug;
}

// Function to determine age group based on author age
function getAgeGroup($age) {
    if (!$age) return '7-12'; // Default
    $age = (int)$age;
    if ($age <= 6) return '0-6';
    if ($age <= 9) return '7-9';
    if ($age <= 12) return '10-12';
    return '13+';
}

// Function to estimate reading time
function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($wordCount / 200));
    return "$minutes minute" . ($minutes !== 1 ? 's' : '');
}
// Function to process a single story with transaction
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
        
        $title = $data['title'] ?? basename($storyDir);
        echo "<h3>Importing: $title</h3>";
        flushOutput();
        
        // Extract author info
        $authorInfo = extractAuthorInfo($title);
        $authorId = getOrCreateAuthor($db, $authorInfo);
        
        // Process cover image
        $mediaData = handleMediaUpload($db, $storyDir, $title);
        $coverUrl = $mediaData['cover_url'];
        
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
            // Ensure cover URL is properly formatted for the frontend
            $formattedCoverUrl = $coverUrl;
            if (!empty($coverUrl) && $coverUrl !== '/images/default-cover.svg') {
                // Make sure the URL starts with https:// for the frontend
                if (strpos($coverUrl, 'http') !== 0) {
                    $formattedCoverUrl = 'https://' . $_SERVER['HTTP_HOST'] . $coverUrl;
                    echo "<p class='info'>Formatted cover URL for frontend: $formattedCoverUrl</p>";
                    flushOutput();
                }
            }
            
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
                $formattedCoverUrl,
                $readingTime,
                $ageGroup,
                $existingStory['id']
            ]);
            
            echo "<p class='success'>Updated story: $title (ID: {$existingStory['id']})</p>";
            flushOutput();
            
            // Make sure author is associated
            if ($authorId) {
                $checkStmt = $db->prepare("SELECT * FROM story_authors WHERE story_id = ? AND author_id = ?");
                $checkStmt->execute([$existingStory['id'], $authorId]);
                if (!$checkStmt->fetch()) {
                    $linkStmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                    $linkStmt->execute([$existingStory['id'], $authorId]);
                    echo "<p class='success'>Associated story with author ID: $authorId</p>";
                    flushOutput();
                }
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
                $formattedCoverUrl,
                $readingTime,
                $ageGroup
            ]);
            
            $storyId = $db->lastInsertId();
            echo "<p class='success'>Created story with ID: $storyId</p>";
            flushOutput();
            
            // Associate with author
            if ($authorId) {
                $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                $stmt->execute([$storyId, $authorId]);
                echo "<p class='success'>Associated with author</p>";
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
// Function to verify import results
function verifyImportResults($db, $stats) {
    echo "<h2>Verifying Import Results</h2>";
    flushOutput();
    
    // Count child stories
    $stmt = $db->query("SELECT COUNT(*) FROM stories WHERE source_type = 'child'");
    $storyCount = $stmt->fetchColumn();
    echo "<p>Total child stories in database: $storyCount</p>";
    flushOutput();
    
    // Count child authors
    $stmt = $db->query("SELECT COUNT(*) FROM authors WHERE author_type = 'child'");
    $authorCount = $stmt->fetchColumn();
    echo "<p>Total child authors in database: $authorCount</p>";
    flushOutput();
    
    // Count media files
    $stmt = $db->query("SELECT COUNT(*) FROM media");
    $mediaCount = $stmt->fetchColumn();
    echo "<p>Total media files in database: $mediaCount</p>";
    flushOutput();
    
    // Count story-author links
    $stmt = $db->query("
        SELECT COUNT(*) FROM story_authors sa
        JOIN stories s ON sa.story_id = s.id
        WHERE s.source_type = 'child'
    ");
    $storyAuthorCount = $stmt->fetchColumn();
    echo "<p>Total story-author links for child stories: $storyAuthorCount</p>";
    flushOutput();
    
    // Count story-tag links
    $stmt = $db->query("
        SELECT COUNT(*) FROM story_tags st
        JOIN stories s ON st.story_id = s.id
        WHERE s.source_type = 'child'
    ");
    $storyTagCount = $stmt->fetchColumn();
    echo "<p>Total story-tag links for child stories: $storyTagCount</p>";
    flushOutput();
    
    // Verify story settings
    $stmt = $db->query("
        SELECT COUNT(*) FROM stories 
        WHERE source_type = 'child' AND allow_reviews = 0
    ");
    $correctSettingsCount = $stmt->fetchColumn();
    echo "<p>Stories with correct settings (source_type = 'child', allow_reviews = 0): $correctSettingsCount</p>";
    flushOutput();
    
    // Check for any stories with missing authors
    $stmt = $db->query("
        SELECT COUNT(*) FROM stories s
        LEFT JOIN story_authors sa ON s.id = sa.story_id
        WHERE s.source_type = 'child' AND sa.author_id IS NULL
    ");
    $missingAuthorCount = $stmt->fetchColumn();
    if ($missingAuthorCount > 0) {
        echo "<p class='warning'>Found $missingAuthorCount child stories without authors</p>";
    } else {
        echo "<p class='success'>All child stories have authors</p>";
    }
    flushOutput();
    
    // Check for any stories with missing cover images
    $stmt = $db->query("
        SELECT COUNT(*) FROM stories
        WHERE source_type = 'child' AND (cover_url IS NULL OR cover_url = '')
    ");
    $missingCoverCount = $stmt->fetchColumn();
    if ($missingCoverCount > 0) {
        echo "<p class='warning'>Found $missingCoverCount child stories without cover images</p>";
    } else {
        echo "<p class='success'>All child stories have cover images</p>";
    }
    flushOutput();
    
    return [
        'story_count' => $storyCount,
        'author_count' => $authorCount,
        'media_count' => $mediaCount,
        'story_author_count' => $storyAuthorCount,
        'story_tag_count' => $storyTagCount
    ];
}

// Main HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Import Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #4a6ee0; }
        .log { background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .button-container { margin: 20px 0; }
        .button { 
            display: inline-block; 
            padding: 15px 25px; 
            background: #4CAF50; 
            color: white; 
            border-radius: 5px; 
            text-decoration: none;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            font-size: 16px;
            font-weight: bold;
        }
        .button.danger { background: #e04a4a; }
    </style>
</head>
<body>
    <h1>WordPress Import Tool</h1>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="action" value="import" class="button">Import All Content</button>
        </form>
    </div>
    
    <div class="log">
<?php
// Process the import action
if (isset($_POST['action']) && $_POST['action'] === 'import') {
    // Connect to database
    $db = connectToDatabase();
    if (!$db) {
        echo "<p class='error'>Failed to connect to database</p>";
        exit;
    }
    
    // Clean child story data first
    echo "<h2>Cleaning Existing Data</h2>";
    flushOutput();
    
    $cleanResult = cleanChildStoryData($db);
    if (!$cleanResult) {
        echo "<p class='error'>Failed to clean existing data. Import aborted.</p>";
        echo "</div></body></html>";
        exit;
    }
    
    // Process WordPress export directory
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
    
    if (!is_dir($wpDir)) {
        echo "<p class='error'>WordPress export directory not found. Tried:</p>";
        echo "<ul>";
        foreach ($fallbackPaths as $path) {
            echo "<li>$path</li>";
        }
        echo "</ul>";
        echo "<p>Please ensure the WordPress export directory exists and contains markdown files.</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<h2>Importing Children's Stories</h2>";
    echo "<p class='info'>Import source: $wpDir</p>";
    flushOutput();
    
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
        echo "</div></body></html>";
        exit;
    }
    
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
    
    // Verify import results
    $verificationResults = verifyImportResults($db, $stats);
    
    // Display summary
    echo "<h2>Import Complete!</h2>";
    echo "<p class='success'>Summary:</p>";
    echo "<ul>";
    echo "<li>Created: {$stats['created']} stories</li>";
    echo "<li>Updated: {$stats['updated']} stories</li>";
    echo "<li>Skipped: {$stats['skipped']} stories</li>";
    echo "<li>Errors: {$stats['errors']} stories</li>";
    echo "</ul>";
    
    echo "<p>Now check the <a href='/admin/stories'>Stories Admin</a> to verify the imported content.</p>";
    flushOutput();
} else {
    // Display initial instructions
    echo "<p class='info'>Click the 'Import All Content' button to start the import process.</p>";
    echo "<p>This tool will:</p>";
    echo "<ol>";
    echo "<li>Clean all existing child story data (stories, authors, tags, media)</li>";
    echo "<li>Import all stories from the WordPress export directory</li>";
    echo "<li>Create or update authors, tags, and media files</li>";
    echo "<li>Verify the import results</li>";
    echo "</ol>";
    echo "<p>The import process may take several minutes to complete.</p>";
}
?>
    </div>
</body>
</html>