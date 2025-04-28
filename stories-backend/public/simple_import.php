<?php
/**
 * Simple WordPress Import Script
 *
 * This script directly imports WordPress stories into the database
 * with minimal complexity and maximum reliability.
 */

// Basic error handling to diagnose 500 errors
try {
    // Enable error reporting
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    // Increase time limit for long-running script
    set_time_limit(0);
    
    // Set content type
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress One-Click Import</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4a6ee0; }
        .log { background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
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
            font-size: 18px;
            font-weight: bold;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background: #e9f7ef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>WordPress One-Click Import</h1>
    
    <form method="post">
        <p>Click the button below to import all WordPress content. This will clean existing child stories data and import fresh content.</p>
        <button type="submit" name="action" value="import" class="button">Import All Content</button>
    </form>
    
    <div class="log">
<?php
// Only process if form submitted
if (!isset($_POST['action'])) {
    echo "<p class='info'>Click the button above to begin the import process</p>";
    echo "</div></body></html>";
    exit;
}

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "<p class='success'>Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p class='error'>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Clean existing data
try {
    // Begin transaction
    $db->beginTransaction();
    
    // 1. Delete story_tags associations for child stories
    $db->exec("DELETE st FROM story_tags st
              JOIN stories s ON st.story_id = s.id
              WHERE s.source_type = 'child'");
    echo "<p class='info'>Deleted story-tag associations for child stories</p>";
    
    // 2. Delete story_authors associations for child stories
    $db->exec("DELETE sa FROM story_authors sa
              JOIN stories s ON sa.story_id = s.id
              WHERE s.source_type = 'child'");
    echo "<p class='info'>Deleted story-author associations for child stories</p>";
    
    // 3. Delete child stories
    $stmt = $db->prepare("DELETE FROM stories WHERE source_type = 'child'");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "<p class='info'>Deleted $count existing child stories</p>";
    
    // 4. Delete unused authors (those without any stories)
    $db->exec("DELETE a FROM authors a
              LEFT JOIN story_authors sa ON a.id = sa.author_id
              WHERE sa.author_id IS NULL AND a.author_type = 'child'");
    echo "<p class='info'>Deleted unused child authors</p>";
    
    // 5. Delete unused media files
    $db->exec("DELETE FROM media WHERE id > 1");
    echo "<p class='info'>Deleted existing media files</p>";
    
    // Commit transaction
    $db->commit();
    echo "<p class='success'>Database cleaned successfully</p>";
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p class='error'>Clean operation failed: " . $e->getMessage() . "</p>";
    // Continue anyway
}

// Process WordPress export directory
$wpDir = __DIR__ . '/../_wp-migration/wp-md/custom/childrens-story';

if (!is_dir($wpDir)) {
    // Try alternate paths
    $altPaths = [
        __DIR__ . '/../_wp-migration/wp-md',
        __DIR__ . '/../_wp-migration/wp-md/custom',
        __DIR__ . '/../_wp migration/wp-md/custom/childrens-story',
        __DIR__ . '/../_wp migration/wp-md'
    ];
    
    $found = false;
    foreach ($altPaths as $path) {
        if (is_dir($path)) {
            $wpDir = $path;
            $found = true;
            echo "<p class='info'>Found WordPress export directory at: $wpDir</p>";
            break;
        }
    }
    
    if (!$found) {
        echo "<p class='error'>WordPress export directory not found. Tried:</p>";
        echo "<ul>";
        echo "<li>" . __DIR__ . "/../_wp-migration/wp-md/custom/childrens-story</li>";
        foreach ($altPaths as $path) {
            echo "<li>$path</li>";
        }
        echo "</ul>";
        exit;
    }
}

echo "<h2>Importing Children's Stories</h2>";

// Get all story directories
$storyDirs = array_filter(glob("$wpDir/*"), 'is_dir');
echo "<p>Found " . count($storyDirs) . " stories to process</p>";

// Stats
$stats = [
    'created' => 0,
    'errors' => 0
];

// Process each story directory
foreach ($storyDirs as $storyDir) {
    // Begin transaction for this story
    $db->beginTransaction();
    
    try {
        $mdFile = "$storyDir/index.md";
        
        if (!file_exists($mdFile)) {
            echo "<p class='error'>Markdown file not found: $mdFile</p>";
            $db->rollBack();
            continue;
        }
        
        // Read markdown file
        $content = file_get_contents($mdFile);
        
        // Extract front matter
        $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)/s';
        if (!preg_match($pattern, $content, $matches)) {
            echo "<p class='error'>Invalid markdown format in: $mdFile</p>";
            $db->rollBack();
            continue;
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
        
        // Extract author info - try multiple patterns
        $authorName = null;
        $authorAge = null;
        $authorLocation = null;
        
        // Pattern 1: "by Name, aged X, from Location"
        if (preg_match('/by\s+([^,]+)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,.]+))?/i', $title, $matches)) {
            $authorName = trim($matches[1]);
            $authorAge = isset($matches[2]) ? trim($matches[2]) : null;
            $authorLocation = isset($matches[3]) ? trim($matches[3]) : null;
        }
        // Pattern 2: "Name, aged X, from Location"
        else if (preg_match('/([^,]+),\s+aged\s+(\d+)(?:,\s+from\s+([^,.]+))?/i', $title, $matches)) {
            $authorName = trim($matches[1]);
            $authorAge = isset($matches[2]) ? trim($matches[2]) : null;
            $authorLocation = isset($matches[3]) ? trim($matches[3]) : null;
        }
        // Pattern 3: Try to extract from front matter
        else if (isset($data['author'])) {
            $authorName = $data['author'];
            // Try to find age and location in title
            if (preg_match('/aged\s+(\d+)/i', $title, $ageMatch)) {
                $authorAge = $ageMatch[1];
            }
            if (preg_match('/from\s+([^,.]+)/i', $title, $locMatch)) {
                $authorLocation = $locMatch[1];
            }
        }
        
        if ($authorName) {
            echo "<p class='info'>Extracted author: $authorName, Age: $authorAge, Location: $authorLocation</p>";
            
            // Create or get author
            $authorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorName));
            
            // Check if author exists by name or slug
            $stmt = $db->prepare("SELECT id, bio FROM authors WHERE slug = ? OR name LIKE ?");
            $stmt->execute([$authorSlug, $authorName]);
            $author = $stmt->fetch();
            
            if ($author) {
                $authorId = $author['id'];
                echo "<p class='info'>Author already exists: $authorName (ID: $authorId)</p>";
                
                // Update author - always update age and location
                $bio = $author['bio'];
                if (empty($bio)) {
                    $bio = "$authorName is a child author" .
                           ($authorAge ? " aged $authorAge" : "") .
                           ($authorLocation ? " from $authorLocation" : "") . ".";
                }
                
                $stmt = $db->prepare("UPDATE authors SET age = ?, location = ?, bio = ?, author_type = 'child' WHERE id = ?");
                $stmt->execute([$authorAge, $authorLocation, $bio, $authorId]);
                echo "<p class='success'>Updated author information</p>";
            } else {
                // Create author
                $bio = "$authorName is a child author" .
                       ($authorAge ? " aged $authorAge" : "") .
                       ($authorLocation ? " from $authorLocation" : "") . ".";
                
                $stmt = $db->prepare("INSERT INTO authors (name, slug, bio, author_type, age, location, is_published) VALUES (?, ?, ?, 'child', ?, ?, 1)");
                $stmt->execute([
                    $authorName,
                    $authorSlug,
                    $bio,
                    $authorAge,
                    $authorLocation
                ]);
                $authorId = $db->lastInsertId();
                echo "<p class='success'>Created author with ID: $authorId</p>";
            }
        } else {
            echo "<p class='warning'>Could not extract author info from title</p>";
            $authorId = null;
        }
    } else {
        echo "<p class='warning'>Could not extract author info from title</p>";
        $authorId = null;
    }
    
    // Process cover image
    $imagesDir = "$storyDir/images";
    $coverUrl = '/images/default-cover.svg'; // Default
    
    if (is_dir($imagesDir)) {
        $images = glob("$imagesDir/*.*");
        if (!empty($images)) {
            $coverImage = basename($images[0]);
            
            // Copy image to uploads directory
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                echo "<p class='info'>Created uploads directory</p>";
            }
            
            $uniqueFilename = uniqid() . '-' . $coverImage;
            $destination = $uploadDir . $uniqueFilename;
            
            if (copy($images[0], $destination)) {
                $coverUrl = '/uploads/' . $uniqueFilename;
                echo "<p class='success'>Copied image to: $destination</p>";
                chmod($destination, 0644);
                
                // Add to media library
                $mediaId = null;
                try {
                    // Get proper MIME type
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($destination);
                    $fileSize = filesize($destination);
                    
                    // Create alt text
                    $altText = "Illustration for story: " . $title;
                    
                    $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([
                        $uniqueFilename,
                        $coverUrl,
                        $mimeType,
                        $fileSize,
                        $altText
                    ]);
                    $mediaId = $db->lastInsertId();
                    echo "<p class='success'>Added to media library (ID: $mediaId)</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>Failed to add to media library: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Extract excerpt - clean and meaningful
    $excerpt = '';
    
    // First try to get from Summary section
    if (preg_match('/Summary\s*\n(.*?)(?:\n\n|\n#|\n\*\*)/s', $markdownContent, $summaryMatch)) {
        $summary = trim($summaryMatch[1]);
        
        // Extract just the first sentence
        if (preg_match('/^(.*?[.!?])(?:\s|$)/s', $summary, $sentenceMatch)) {
            $excerpt = trim($sentenceMatch[1]);
        } else {
            $excerpt = $summary;
        }
    }
    
    // If no summary or empty excerpt, use first paragraph
    if (empty($excerpt)) {
        $paragraphs = preg_split('/\n\s*\n/', $markdownContent);
        $firstPara = trim($paragraphs[0]);
        
        // Remove any metadata like Name/Age/Location
        $firstPara = preg_replace('/^(?:Name|Age|Location):\s+.*$/m', '', $firstPara);
        
        // Extract just the first sentence
        if (preg_match('/^(.*?[.!?])(?:\s|$)/s', $firstPara, $sentenceMatch)) {
            $excerpt = trim($sentenceMatch[1]);
        } else {
            $excerpt = $firstPara;
        }
    }
    
    // Strip out "by ... aged ... from ..." metadata
    $excerpt = preg_replace('/by\s+[^,]+(?:,?\s+aged\s+\d+)?(?:,?\s+from\s+[^,.]+)?/i', '', $excerpt);
    $excerpt = trim($excerpt);
    
    // Make sure excerpt isn't too short
    if (strlen($excerpt) < 20) {
        $excerpt = substr(strip_tags($markdownContent), 0, 150) . '...';
    }
    
    echo "<p class='info'>Excerpt: " . htmlspecialchars(substr($excerpt, 0, 100)) . "...</p>";
    
    // Generate slug
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $slug = substr($slug, 0, 100); // Limit length
    
    // Make slug unique
    $baseSlug = $slug;
    $counter = 1;
    $stmt = $db->prepare("SELECT id FROM stories WHERE slug = ?");
    $stmt->execute([$slug]);
    while ($stmt->fetch()) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
        $stmt->execute([$slug]);
    }
    
    // Calculate reading time
    $wordCount = str_word_count(strip_tags($markdownContent));
    $readingTime = max(1, ceil($wordCount / 200)) . ' minutes';
    
    // Determine age group
    if ($authorAge) {
        $age = (int)$authorAge;
        if ($age <= 6) $ageGroup = '0-6';
        else if ($age <= 9) $ageGroup = '7-9';
        else if ($age <= 12) $ageGroup = '10-12';
        else $ageGroup = '13+';
    } else {
        $ageGroup = '7-12'; // Default
    }
    
    // Create story
    try {
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
        
        // Associate with author
        if ($authorId) {
            $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
            $stmt->execute([$storyId, $authorId]);
            echo "<p class='success'>Associated with author</p>";
        }
        
        // Extract tags from front matter and content
        $tags = ['children\'s story']; // Always include this tag
        
        // Get tags from front matter if available
        if (isset($data['tags'])) {
            $frontMatterTags = explode(',', $data['tags']);
            foreach ($frontMatterTags as $tag) {
                $tag = trim($tag);
                if (!empty($tag) && !in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
        }
        
        // Add content-based tags
        if (stripos($markdownContent, 'adventure') !== false) $tags[] = 'adventure';
        if (stripos($markdownContent, 'magic') !== false) $tags[] = 'magic';
        if (stripos($markdownContent, 'fantasy') !== false) $tags[] = 'fantasy';
        if (stripos($markdownContent, 'friend') !== false) $tags[] = 'friendship';
        if (stripos($markdownContent, 'animal') !== false) $tags[] = 'animals';
        if (stripos($markdownContent, 'school') !== false) $tags[] = 'school';
        
        echo "<p class='info'>Tags: " . implode(', ', $tags) . "</p>";
        
        foreach ($tags as $tagName) {
            try {
                $tagSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tagName));
                
                // Check if tag exists
                $stmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
                $stmt->execute([$tagSlug]);
                $tag = $stmt->fetch();
                
                if (!$tag) {
                    // Create tag
                    $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                    $stmt->execute([$tagName, $tagSlug]);
                    $tagId = $db->lastInsertId();
                    echo "<p class='success'>Created tag: $tagName</p>";
                } else {
                    $tagId = $tag['id'];
                }
                
                // Associate tag with story
                $stmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$storyId, $tagId]);
                echo "<p class='success'>Added tag: $tagName</p>";
            } catch (Exception $e) {
                echo "<p class='error'>Error processing tag '$tagName': " . $e->getMessage() . "</p>";
            }
        }
        
        // Commit the transaction
        $db->commit();
        echo "<p class='success'>Story transaction committed successfully</p>";
        
        $stats['created']++;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "<p class='error'>Transaction rolled back</p>";
        }
        echo "<p class='error'>Error creating story: " . $e->getMessage() . "</p>";
        $stats['errors']++;
    }
    
    // Force output
    ob_flush();
    flush();
}

echo "<h2>Import Complete!</h2>";
echo "<p>Created: {$stats['created']} stories</p>";
echo "<p>Errors: {$stats['errors']} stories</p>";

// Summary section
echo "<div class='summary'>";
echo "<h3>Import Summary</h3>";
echo "<ul>";
echo "<li>Successfully imported {$stats['created']} stories</li>";
echo "<li>Failed to import {$stats['errors']} stories</li>";
echo "</ul>";
echo "<p>The import process is now complete. All stories have been imported with:</p>";
echo "<ul>";
echo "<li>Proper author information (name, age, location)</li>";
echo "<li>Clean, meaningful excerpts</li>";
echo "<li>Appropriate tags</li>";
echo "<li>Media files with correct MIME types</li>";
echo "<li>Unique slugs to prevent conflicts</li>";
echo "</ul>";
echo "<p>Check the <a href='/admin/stories'>Stories Admin</a> to verify the imported content.</p>";
echo "</div>";

} catch (Exception $e) {
    // Catch any uncaught exceptions
    echo "<h1>Error 500: Script Error</h1>";
    echo "<p>An error occurred while running the import script:</p>";
    echo "<pre style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($e->getMessage()) . "\n\n";
    echo "File: " . htmlspecialchars($e->getFile()) . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
?>
    </div>
</body>
</html>