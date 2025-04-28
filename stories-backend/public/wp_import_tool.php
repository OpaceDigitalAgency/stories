<?php
/**
 * WordPress Import Tool
 * 
 * A comprehensive tool to import WordPress content with proper handling of
 * media files, authors, and tags.
 */

// Basic error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
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
            margin-bottom: 10px;
            cursor: pointer;
            border: none;
            font-size: 16px;
            font-weight: bold;
        }
        .button.danger { background: #e04a4a; }
        .button.primary { background: #4a6ee0; }
        .button.secondary { background: #6c757d; }
        .stats {
            display: flex;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-right: 15px;
            margin-bottom: 15px;
            min-width: 120px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #4a6ee0;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <h1>WordPress Import Tool</h1>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="action" value="clean_import" class="button danger">Clean & Import All Content</button>
            <button type="submit" name="action" value="import_only" class="button primary">Import Without Cleaning</button>
            <button type="submit" name="action" value="fix_media" class="button secondary">Fix Media Files</button>
            <button type="submit" name="action" value="fix_duplicates" class="button secondary">Fix Duplicate Entries</button>
        </form>
    </div>
    
    <div class="stats">
        <?php
        // Show database stats
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
            
            $tables = [
                'Stories' => 'stories',
                'Authors' => 'authors',
                'Tags' => 'tags',
                'Media' => 'media',
                'Story-Author Links' => 'story_authors',
                'Story-Tag Links' => 'story_tags'
            ];
            
            foreach ($tables as $label => $table) {
                $stmt = $db->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                
                echo "<div class='stat-box'>";
                echo "<div class='stat-number'>$count</div>";
                echo "<div class='stat-label'>$label</div>";
                echo "</div>";
            }
            
            // Count child stories
            $stmt = $db->query("SELECT COUNT(*) FROM stories WHERE source_type = 'child'");
            $childCount = $stmt->fetchColumn();
            
            echo "<div class='stat-box'>";
            echo "<div class='stat-number'>$childCount</div>";
            echo "<div class='stat-label'>Child Stories</div>";
            echo "</div>";
            
        } catch (PDOException $e) {
            // Silently fail
        }
        ?>
    </div>
    
    <div class="log">
<?php
// Only process if form submitted
if (!isset($_POST['action'])) {
    echo "<p class='info'>Select an action above to begin</p>";
    echo "<p>This tool provides the following functions:</p>";
    echo "<ul>";
    echo "<li><strong>Clean & Import All Content</strong> - Removes all existing child stories and imports fresh content</li>";
    echo "<li><strong>Import Without Cleaning</strong> - Adds or updates content without removing existing data</li>";
    echo "<li><strong>Fix Media Files</strong> - Repairs issues with media files and story cover images</li>";
    echo "<li><strong>Fix Duplicate Entries</strong> - Removes duplicate stories, authors, and tags</li>";
    echo "</ul>";
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
?>
    </div>
</body>
</html>
// Find WordPress export directory
$wpDir = null;
$possiblePaths = [
    __DIR__ . '/../_wp-migration/wp-md/custom/childrens-story',
    __DIR__ . '/../_wp-migration/wp-md',
    __DIR__ . '/../_wp migration/wp-md/custom/childrens-story',
    __DIR__ . '/../_wp migration/wp-md'
];

echo "<p>Find WordPress export directory: \$wpDir = null; \$possiblePaths = [";
foreach ($possiblePaths as $path) {
    echo " '__DIR__ . \"" . str_replace(__DIR__, '', $path) . "\"',";
}
echo " ];</p>";

foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $wpDir = $path;
        echo "<p>Found WordPress export directory: \$wpDir = \"$wpDir\";</p>";
        break;
    }
}

if (!$wpDir) {
    echo "<p class='error'>WordPress export directory not found</p>";
    exit;
}

// Debug info
echo "<p>break; } } if (\$wpDir) { echo \"</p>";

// Action: Clean & Import
if ($_POST['action'] === 'clean_import') {
    echo "<h2>Cleaning Database</h2>";
    
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
        exit;
    }
    
    // Continue with import
    $_POST['action'] = 'import_only';
}

// Action: Import Only or after Clean
if ($_POST['action'] === 'import_only') {
    echo "<h2>Importing Children's Stories</h2>";
    
    // Find story directories
    $storyDirs = [];
    
    if (strpos($wpDir, 'childrens-story') !== false) {
        // If we're already in the childrens-story directory
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'index.md') {
                $storyDirs[] = dirname($file->getPathname());
            }
        }
    } else {
        // Look for the childrens-story directory
        $childrenDir = $wpDir . '/custom/childrens-story';
        if (is_dir($childrenDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($childrenDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === 'index.md') {
                    $storyDirs[] = dirname($file->getPathname());
                }
            }
        } else {
            echo "<p class='warning'>Children's story directory not found, searching all directories</p>";
            
            // Search all directories
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === 'index.md') {
                    // Check if this looks like a story
                    $content = file_get_contents($file->getPathname());
                    if (strpos($content, 'Summary') !== false || 
                        strpos($content, 'Story') !== false || 
                        strpos($content, 'aged') !== false) {
                        $storyDirs[] = dirname($file->getPathname());
                    }
                }
            }
        }
    }
    
    echo "<p>Found " . count($storyDirs) . " stories to process</p>";
    
    // Stats
    $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];
    
    // Process each story
    foreach ($storyDirs as $storyDir) {
        $mdFile = "$storyDir/index.md";
        
        if (!file_exists($mdFile)) {
            echo "<p class='error'>Markdown file not found: $mdFile</p>";
            continue;
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
            if (preg_match('/by\s+([^,]+?)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,.]+))?/i', $title, $matches)) {
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
            
            // Extract author info from content if not found in title
            if (!$authorName || !$authorAge || !$authorLocation) {
                if (preg_match('/\*\*Name:\*\*\s*([^\n]+)/i', $markdownContent, $nameMatch)) {
                    $authorName = trim($nameMatch[1]);
                }
                if (preg_match('/\*\*Age:\*\*\s*([^\n]+)/i', $markdownContent, $ageMatch)) {
                    $authorAge = trim($ageMatch[1]);
                }
                if (preg_match('/\*\*Location:\*\*\s*([^\n]+)/i', $markdownContent, $locMatch)) {
                    $authorLocation = trim($locMatch[1]);
                }
            }
            
            echo "<p class='info'>Author: $authorName, Age: $authorAge, Location: $authorLocation</p>";
            
            // Create or get author
            $authorId = null;
            if ($authorName) {
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
                echo "<p class='warning'>Could not extract author info</p>";
            }
            
            // Process cover image
            $imagesDir = "$storyDir/images";
            $coverUrl = '/images/default-cover.svg'; // Default
            $mediaId = null;
            
            if (is_dir($imagesDir)) {
                $images = glob("$imagesDir/*.*");
                if (!empty($images)) {
                    $coverImage = basename($images[0]);
                    
                    // Check if we have a cover image from front matter
                    if (isset($data['coverImage'])) {
                        foreach ($images as $img) {
                            if (basename($img) === $data['coverImage']) {
                                $coverImage = $data['coverImage'];
                                break;
                            }
                        }
                    }
                    
                    // Copy image to uploads directory
                    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                        echo "<p class='info'>Created uploads directory</p>";
                    }
                    
                    $uniqueFilename = uniqid() . '-' . $coverImage;
                    $destination = $uploadDir . $uniqueFilename;
                    
                    // Find the image file
                    $imageFile = null;
                    foreach ($images as $img) {
                        if (basename($img) === $coverImage) {
                            $imageFile = $img;
                            break;
                        }
                    }
                    
                    if (!$imageFile) {
                        $imageFile = $images[0]; // Fallback to first image
                    }
                    
                    if (copy($imageFile, $destination)) {
                        $coverUrl = '/uploads/' . $uniqueFilename;
                        echo "<p class='success'>Copied image to: $destination</p>";
                        chmod($destination, 0644);
                        
                        // Get proper MIME type
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->file($destination);
                        $fileSize = filesize($destination);
                        
                        // Create alt text
                        $altText = "Illustration for story: " . $title;
                        
                        // Add to media library
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
                    } else {
                        echo "<p class='error'>Failed to copy image: $imageFile</p>";
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
            
            // Check if story exists by title (more reliable than slug)
            $titleStmt = $db->prepare("SELECT id, slug FROM stories WHERE title LIKE ?");
            $titleStmt->execute(["%" . substr($title, 0, 30) . "%"]);
            $existingStory = $titleStmt->fetch();
            
            if (!$existingStory) {
                // Fallback to slug check
                $slugStmt = $db->prepare("SELECT id FROM stories WHERE slug = ?");
                $slugStmt->execute([$slug]);
                $existingStory = $slugStmt->fetch();
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
            
            if ($existingStory) {
                // Update existing story
                $stmt = $db->prepare("
                    UPDATE stories SET 
                        content = ?, 
                        excerpt = ?, 
                        cover_url = ?,
                        estimated_reading_time = ?, 
                        age_group = ?
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
                $stats['updated']++;
                
                // Make sure author is associated
                if ($authorId) {
                    $checkStmt = $db->prepare("SELECT * FROM story_authors WHERE story_id = ? AND author_id = ?");
                    $checkStmt->execute([$existingStory['id'], $authorId]);
                    if (!$checkStmt->fetch()) {
                        $linkStmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                        $linkStmt->execute([$existingStory['id'], $authorId]);
                        echo "<p class='success'>Associated story with author ID: $authorId</p>";
                    }
                }
                
                // Add tags to the story
                foreach ($tags as $tagName) {
                    try {
                        $tagSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tagName));
                        
                        // Check if tag exists
                        $tagStmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
                        $tagStmt->execute([$tagSlug]);
                        $tag = $tagStmt->fetch();
                        
                        if (!$tag) {
                            // Create tag
                            $createTagStmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                            $createTagStmt->execute([$tagName, $tagSlug]);
                            $tagId = $db->lastInsertId();
                            echo "<p class='success'>Created tag: $tagName</p>";
                        } else {
                            $tagId = $tag['id'];
                        }
                        
                        // Associate tag with story if not already associated
                        $checkTagStmt = $db->prepare("SELECT * FROM story_tags WHERE story_id = ? AND tag_id = ?");
                        $checkTagStmt->execute([$existingStory['id'], $tagId]);
                        if (!$checkTagStmt->fetch()) {
                            $linkTagStmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                            $linkTagStmt->execute([$existingStory['id'], $tagId]);
                            echo "<p class='success'>Added tag '$tagName' to story</p>";
                        }
                    } catch (Exception $e) {
                        echo "<p class='error'>Error processing tag '$tagName': " . $e->getMessage() . "</p>";
                    }
                }
            } else {
                // Generate a unique slug to avoid duplicates
                $baseSlug = $slug;
                $counter = 1;
                $slugStmt = $db->prepare("SELECT id FROM stories WHERE slug = ?");
                $slugStmt->execute([$slug]);
                while ($slugStmt->fetch()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                    $slugStmt->execute([$slug]);
                }
                
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
                $stats['created']++;
                
                // Associate with author
                if ($authorId) {
                    $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                    $stmt->execute([$storyId, $authorId]);
                    echo "<p class='success'>Associated with author</p>";
                }
                
                // Add tags to the story
                foreach ($tags as $tagName) {
                    try {
                        $tagSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tagName));
                        
                        // Check if tag exists
                        $tagStmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
                        $tagStmt->execute([$tagSlug]);
                        $tag = $tagStmt->fetch();
                        
                        if (!$tag) {
                            // Create tag
                            $createTagStmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                            $createTagStmt->execute([$tagName, $tagSlug]);
                            $tagId = $db->lastInsertId();
                            echo "<p class='success'>Created tag: $tagName</p>";
                        } else {
                            $tagId = $tag['id'];
                        }
                        
                        // Associate tag with story
                        $linkTagStmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                        $linkTagStmt->execute([$storyId, $tagId]);
                        echo "<p class='success'>Added tag '$tagName' to story</p>";
                    } catch (Exception $e) {
                        echo "<p class='error'>Error processing tag '$tagName': " . $e->getMessage() . "</p>";
                    }
                }
            }
            
            // Commit the transaction
            $db->commit();
            echo "<p class='success'>Story transaction committed successfully</p>";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($db->inTransaction()) {
                $db->rollBack();
                echo "<p class='error'>Transaction rolled back</p>";
            }
            echo "<p class='error'>Error processing story: " . $e->getMessage() . "</p>";
            $stats['errors']++;
        }
        
        // Force output
        ob_flush();
        flush();
    }
    
    echo "<h2>Import Complete!</h2>";
    echo "<p>Created: {$stats['created']} stories</p>";
    echo "<p>Updated: {$stats['updated']} stories</p>";
    echo "<p>Errors: {$stats['errors']} stories</p>";
}

// Action: Fix Media Files
if ($_POST['action'] === 'fix_media') {
    echo "<h2>Fixing Media Files</h2>";
    
    // Get all media files from database
    $stmt = $db->query("SELECT * FROM media");
    $mediaFiles = $stmt->fetchAll();
    echo "<p>Found " . count($mediaFiles) . " media files in database</p>";
    
    // Get all stories that reference media files
    $stmt = $db->query("SELECT id, title, cover_url FROM stories WHERE cover_url IS NOT NULL");
    $stories = $stmt->fetchAll();
    echo "<p>Found " . count($stories) . " stories with cover images</p>";
    
    // Get all files in uploads directory
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    $physicalFiles = scandir($uploadDir);
    $physicalFiles = array_diff($physicalFiles, ['.', '..']);
    echo "<p>Found " . count($physicalFiles) . " files in uploads directory</p>";
    
    // Fix 1: Update file paths in media table
    echo "<h3>Fixing file paths in media table</h3>";
    $fixedPaths = 0;
    
    foreach ($mediaFiles as $media) {
        $filePath = $media['file_path'];
        $filename = $media['filename'];
        
        // Check if file exists
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        $fileExists = file_exists($fullPath);
        
        echo "<p>Checking: $filename (ID: {$media['id']}) - Path: $filePath - " . ($fileExists ? "Exists" : "Missing") . "</p>";
        
        if (!$fileExists) {
            // Try to find the file in uploads directory
            foreach ($physicalFiles as $physicalFile) {
                if (strpos($physicalFile, $filename) !== false || strpos($filename, $physicalFile) !== false) {
                    $newPath = '/uploads/' . $physicalFile;
                    $fullNewPath = $_SERVER['DOCUMENT_ROOT'] . $newPath;
                    
                    if (file_exists($fullNewPath)) {
                        // Update the file path in the database
                        $updateStmt = $db->prepare("UPDATE media SET file_path = ? WHERE id = ?");
                        $updateStmt->execute([$newPath, $media['id']]);
                        
                        echo "<p class='success'>Updated path for {$media['id']}: $filePath -> $newPath</p>";
                        $fixedPaths++;
                        break;
                    }
                }
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedPaths file paths</p>";
    
    // Fix 2: Update MIME types
    echo "<h3>Fixing MIME types</h3>";
    $fixedMimeTypes = 0;
    
    foreach ($mediaFiles as $media) {
        $filePath = $media['file_path'];
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        
        if (file_exists($fullPath)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fullPath);
            
            if ($mimeType != $media['file_type']) {
                $updateStmt = $db->prepare("UPDATE media SET file_type = ? WHERE id = ?");
                $updateStmt->execute([$mimeType, $media['id']]);
                
                echo "<p class='success'>Updated MIME type for {$media['id']}: {$media['file_type']} -> $mimeType</p>";
                $fixedMimeTypes++;
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedMimeTypes MIME types</p>";
    
    // Fix 3: Update file sizes
    echo "<h3>Fixing file sizes</h3>";
    $fixedFileSizes = 0;
    
    foreach ($mediaFiles as $media) {
        $filePath = $media['file_path'];
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        
        if (file_exists($fullPath)) {
            $fileSize = filesize($fullPath);
            
            if ($fileSize != $media['file_size']) {
                $updateStmt = $db->prepare("UPDATE media SET file_size = ? WHERE id = ?");
                $updateStmt->execute([$fileSize, $media['id']]);
                
                echo "<p class='success'>Updated file size for {$media['id']}: {$media['file_size']} -> $fileSize</p>";
                $fixedFileSizes++;
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedFileSizes file sizes</p>";
    
    // Fix 4: Update story cover URLs
    echo "<h3>Fixing story cover URLs</h3>";
    $fixedCoverUrls = 0;
    
    foreach ($stories as $story) {
        $coverUrl = $story['cover_url'];
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $coverUrl;
        
        if (!file_exists($fullPath) && $coverUrl != '/images/default-cover.svg') {
            // Try to find a matching media file
            $filename = basename($coverUrl);
            $stmt = $db->prepare("SELECT * FROM media WHERE filename LIKE ? OR file_path LIKE ?");
            $stmt->execute(["%$filename%", "%$filename%"]);
            $media = $stmt->fetch();
            
            if ($media) {
                // Update the story cover URL
                $updateStmt = $db->prepare("UPDATE stories SET cover_url = ? WHERE id = ?");
                $updateStmt->execute([$media['file_path'], $story['id']]);
                
                echo "<p class='success'>Updated cover URL for story {$story['id']} ({$story['title']}): $coverUrl -> {$media['file_path']}</p>";
                $fixedCoverUrls++;
            } else {
                // Set to default cover
                $updateStmt = $db->prepare("UPDATE stories SET cover_url = '/images/default-cover.svg' WHERE id = ?");
                $updateStmt->execute([$story['id']]);
                
                echo "<p class='warning'>Set default cover for story {$story['id']} ({$story['title']}): $coverUrl -> /images/default-cover.svg</p>";
                $fixedCoverUrls++;
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedCoverUrls story cover URLs</p>";
    
    // Fix 5: Add missing alt text
    echo "<h3>Adding missing alt text</h3>";
    $fixedAltTexts = 0;
    
    $stmt = $db->query("SELECT * FROM media WHERE alt_text IS NULL OR alt_text = ''");
    $mediaWithoutAlt = $stmt->fetchAll();
    
    foreach ($mediaWithoutAlt as $media) {
        // Generate alt text from filename
        $filename = $media['filename'];
        $altText = str_replace(['-', '_', '.png', '.jpg', '.jpeg', '.gif'], ' ', $filename);
        $altText = ucfirst(trim($altText));
        
        $updateStmt = $db->prepare("UPDATE media SET alt_text = ? WHERE id = ?");
        $updateStmt->execute([$altText, $media['id']]);
        
        echo "<p class='success'>Added alt text for {$media['id']}: $altText</p>";
        $fixedAltTexts++;
    }
    
    echo "<p class='success'>Added $fixedAltTexts alt texts</p>";
    
    echo "<h2>Media Fix Complete!</h2>";
}

// Action: Fix Duplicate Entries
if ($_POST['action'] === 'fix_duplicates') {
    echo "<h2>Fixing Duplicate Entries</h2>";
    
    // Fix 1: Find duplicate stories
    echo "<h3>Fixing duplicate stories</h3>";
    $fixedStories = 0;
    
    // Find stories with similar titles
    $stmt = $db->query("
        SELECT s1.id, s1.title, s1.slug, COUNT(*) as count
        FROM stories s1
        JOIN stories s2 ON s1.title LIKE CONCAT('%', SUBSTRING(s2.title, 1, 30), '%')
            AND s1.id != s2.id
            AND s1.source_type = 'child'
            AND s2.source_type = 'child'
        GROUP BY s1.id
        HAVING count > 0
    ");
    
    $duplicateStories = $stmt->fetchAll();
    echo "<p>Found " . count($duplicateStories) . " potential duplicate stories</p>";
    
    foreach ($duplicateStories as $story) {
        echo "<p>Potential duplicate: {$story['title']} (ID: {$story['id']})</p>";
        
        // Find similar stories
        $similarStmt = $db->prepare("
            SELECT id, title, slug, created_at
            FROM stories
            WHERE title LIKE ? AND id != ? AND source_type = 'child'
            ORDER BY created_at DESC
        ");
        $similarStmt->execute(["%" . substr($story['title'], 0, 30) . "%", $story['id']]);
        $similarStories = $similarStmt->fetchAll();
        
        if (count($similarStories) > 0) {
            echo "<p>Similar stories:</p>";
            echo "<ul>";
            foreach ($similarStories as $similar) {
                echo "<li>{$similar['title']} (ID: {$similar['id']})</li>";
            }
            echo "</ul>";
            
            // Keep the newest story, delete the others
            $keepId = $story['id'];
            $deleteIds = [];
            
            foreach ($similarStories as $similar) {
                $deleteIds[] = $similar['id'];
            }
            
            if (!empty($deleteIds)) {
                // Delete story_tags associations
                $deleteTagsStmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN (" . implode(',', $deleteIds) . ")");
                $deleteTagsStmt->execute();
                
                // Delete story_authors associations
                $deleteAuthorsStmt = $db->prepare("DELETE FROM story_authors WHERE story_id IN (" . implode(',', $deleteIds) . ")");
                $deleteAuthorsStmt->execute();
                
                // Delete stories
                $deleteStoriesStmt = $db->prepare("DELETE FROM stories WHERE id IN (" . implode(',', $deleteIds) . ")");
                $deleteStoriesStmt->execute();
                
                echo "<p class='success'>Deleted " . count($deleteIds) . " duplicate stories, keeping ID: $keepId</p>";
                $fixedStories += count($deleteIds);
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedStories duplicate stories</p>";
    
    // Fix 2: Find duplicate authors
    echo "<h3>Fixing duplicate authors</h3>";
    $fixedAuthors = 0;
    
    // Find authors with similar names
    $stmt = $db->query("
        SELECT a1.id, a1.name, a1.slug, COUNT(*) as count
        FROM authors a1
        JOIN authors a2 ON a1.name LIKE CONCAT('%', SUBSTRING(a2.name, 1, 10), '%')
            AND a1.id != a2.id
            AND a1.author_type = 'child'
            AND a2.author_type = 'child'
        GROUP BY a1.id
        HAVING count > 0
    ");
    
    $duplicateAuthors = $stmt->fetchAll();
    echo "<p>Found " . count($duplicateAuthors) . " potential duplicate authors</p>";
    
    foreach ($duplicateAuthors as $author) {
        echo "<p>Potential duplicate: {$author['name']} (ID: {$author['id']})</p>";
        
        // Find similar authors
        $similarStmt = $db->prepare("
            SELECT id, name, slug
            FROM authors
            WHERE name LIKE ? AND id != ? AND author_type = 'child'
        ");
        $similarStmt->execute(["%" . substr($author['name'], 0, 10) . "%", $author['id']]);
        $similarAuthors = $similarStmt->fetchAll();
        
        if (count($similarAuthors) > 0) {
            echo "<p>Similar authors:</p>";
            echo "<ul>";
            foreach ($similarAuthors as $similar) {
                echo "<li>{$similar['name']} (ID: {$similar['id']})</li>";
            }
            echo "</ul>";
            
            // Keep the first author, merge the others
            $keepId = $author['id'];
            $mergeIds = [];
            
            foreach ($similarAuthors as $similar) {
                $mergeIds[] = $similar['id'];
            }
            
            if (!empty($mergeIds)) {
                // Update story_authors associations
                foreach ($mergeIds as $mergeId) {
                    $updateStmt = $db->prepare("
                        UPDATE story_authors
                        SET author_id = ?
                        WHERE author_id = ?
                    ");
                    $updateStmt->execute([$keepId, $mergeId]);
                }
                
                // Delete authors
                $deleteAuthorsStmt = $db->prepare("DELETE FROM authors WHERE id IN (" . implode(',', $mergeIds) . ")");
                $deleteAuthorsStmt->execute();
                
                echo "<p class='success'>Merged " . count($mergeIds) . " duplicate authors into ID: $keepId</p>";
                $fixedAuthors += count($mergeIds);
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedAuthors duplicate authors</p>";
    
    // Fix 3: Find duplicate tags
    echo "<h3>Fixing duplicate tags</h3>";
    $fixedTags = 0;
    
    // Find tags with similar names
    $stmt = $db->query("
        SELECT t1.id, t1.name, t1.slug, COUNT(*) as count
        FROM tags t1
        JOIN tags t2 ON t1.name LIKE CONCAT('%', t2.name, '%')
            AND t1.id != t2.id
        GROUP BY t1.id
        HAVING count > 0
    ");
    
    $duplicateTags = $stmt->fetchAll();
    echo "<p>Found " . count($duplicateTags) . " potential duplicate tags</p>";
    
    foreach ($duplicateTags as $tag) {
        echo "<p>Potential duplicate: {$tag['name']} (ID: {$tag['id']})</p>";
        
        // Find similar tags
        $similarStmt = $db->prepare("
            SELECT id, name, slug
            FROM tags
            WHERE name LIKE ? AND id != ?
        ");
        $similarStmt->execute(["%" . $tag['name'] . "%", $tag['id']]);
        $similarTags = $similarStmt->fetchAll();
        
        if (count($similarTags) > 0) {
            echo "<p>Similar tags:</p>";
            echo "<ul>";
            foreach ($similarTags as $similar) {
                echo "<li>{$similar['name']} (ID: {$similar['id']})</li>";
            }
            echo "</ul>";
            
            // Keep the first tag, merge the others
            $keepId = $tag['id'];
            $mergeIds = [];
            
            foreach ($similarTags as $similar) {
                $mergeIds[] = $similar['id'];
            }
            
            if (!empty($mergeIds)) {
                // Update story_tags associations
                foreach ($mergeIds as $mergeId) {
                    // First, check for existing associations
                    $checkStmt = $db->prepare("
                        SELECT story_id
                        FROM story_tags
                        WHERE tag_id = ?
                    ");
                    $checkStmt->execute([$mergeId]);
                    $storyIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($storyIds as $storyId) {
                        // Check if the story is already associated with the keep tag
                        $existsStmt = $db->prepare("
                            SELECT 1
                            FROM story_tags
                            WHERE story_id = ? AND tag_id = ?
                        ");
                        $existsStmt->execute([$storyId, $keepId]);
                        
                        if (!$existsStmt->fetch()) {
                            // Add the association
                            $insertStmt = $db->prepare("
                                INSERT INTO story_tags (story_id, tag_id)
                                VALUES (?, ?)
                            ");
                            $insertStmt->execute([$storyId, $keepId]);
                        }
                    }
                    
                    // Delete the old associations
                    $deleteAssocStmt = $db->prepare("
                        DELETE FROM story_tags
                        WHERE tag_id = ?
                    ");
                    $deleteAssocStmt->execute([$mergeId]);
                }
                
                // Delete tags
                $deleteTagsStmt = $db->prepare("DELETE FROM tags WHERE id IN (" . implode(',', $mergeIds) . ")");
                $deleteTagsStmt->execute();
                
                echo "<p class='success'>Merged " . count($mergeIds) . " duplicate tags into ID: $keepId</p>";
                $fixedTags += count($mergeIds);
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedTags duplicate tags</p>";
    
    echo "<h2>Duplicate Fix Complete!</h2>";
}