<?php
/**
 * Direct WordPress Import Script
 * 
 * This script directly imports WordPress stories into the database
 * without using the Node.js script.
 */

set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct WordPress Import</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4a6ee0; }
        .log { background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .options { margin: 20px 0; padding: 15px; background: #e9f0ff; border-radius: 5px; }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background: #4a6ee0;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            margin-right: 10px;
            cursor: pointer;
        }
        .button.danger { background: #e04a4a; }
    </style>
</head>
<body>
    <h1>Direct WordPress Import</h1>
    
    <div class="options">
        <h2>Import Options</h2>
        <form method="post">
            <p>
                <label>
                    <input type="radio" name="mode" value="update" checked>
                    <strong>Update Mode:</strong> Update existing stories and authors with new information
                </label>
            </p>
            <p>
                <label>
                    <input type="radio" name="mode" value="clean">
                    <strong>Clean Import:</strong> Delete all existing child stories and reimport (keeps other content)
                </label>
            </p>
            <p>
                <button type="submit" name="action" value="import" class="button">Start Import</button>
                <?php if (isset($_POST['mode']) && $_POST['mode'] === 'clean'): ?>
                    <button type="submit" name="action" value="confirm_clean" class="button danger">Confirm Clean Import</button>
                <?php endif; ?>
            </p>
        </form>
    </div>
    
    <div class="log">
<?php

// Only process if form submitted
if (!isset($_POST['action'])) {
    echo "<p class='info'>Select an import option above and click 'Start Import'</p>";
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

// Handle clean import mode
if ($_POST['action'] === 'confirm_clean' && $_POST['mode'] === 'clean') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // 1. Delete story_authors associations for child stories
        $db->exec("DELETE sa FROM story_authors sa
                  JOIN stories s ON sa.story_id = s.id
                  WHERE s.source_type = 'child'");
        echo "<p class='info'>Deleted story-author associations for child stories</p>";
        
        // 2. Delete child stories
        $stmt = $db->prepare("DELETE FROM stories WHERE source_type = 'child'");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "<p class='info'>Deleted $count existing child stories</p>";
        
        // Commit transaction
        $db->commit();
        echo "<p class='success'>Database cleaned successfully</p>";
    } catch (Exception $e) {
        $db->rollBack();
        echo "<p class='error'>Clean import failed: " . $e->getMessage() . "</p>";
        exit;
    }
}

// Function to clean excerpt text
function cleanExcerpt($text) {
    // Remove markdown and other formatting
    $text = strip_tags($text);
    // Remove any special characters or markdown syntax
    $text = preg_replace('/[#*_\[\]\(\)~`>]+/', '', $text);
    // Get first sentence or first 150 chars
    $sentences = preg_split('/(?<=[.!?])\s+/', $text, 2);
    $excerpt = $sentences[0];
    
    // If first sentence is too short, use more text up to 150 chars
    if (strlen($excerpt) < 100 && isset($sentences[1])) {
        $excerpt = substr($text, 0, 150);
        if (strlen($text) > 150) {
            $excerpt .= '...';
        }
    }
    
    return trim($excerpt);
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

// Function to generate tags based on content
function generateTags($content) {
    $keywords = [
        'adventure', 'animals', 'fantasy', 'friendship', 'magic', 
        'school', 'family', 'nature', 'space', 'dinosaurs', 
        'robots', 'monsters', 'fairy tale', 'mystery'
    ];
    
    $contentLower = strtolower($content);
    $tags = [];
    
    foreach ($keywords as $keyword) {
        if (strpos($contentLower, strtolower($keyword)) !== false) {
            $tags[] = $keyword;
            if (count($tags) >= 3) break;
        }
    }
    
    // Always add 'children's story' tag
    if (!in_array('children\'s story', $tags)) {
        $tags[] = 'children\'s story';
    }
    
    return implode(',', $tags);
}

// Function to extract author info from title
function extractAuthorInfo($title) {
    $info = [
        'name' => null,
        'age' => null,
        'location' => null
    ];
    
    if (preg_match('/by\s+([^,]+)(?:\s+aged\s+(\d+))?(?:\s+from\s+([^,]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
    }
    
    return $info;
}

// Function to get or create author
function getOrCreateAuthor($db, $authorInfo) {
    if (!$authorInfo['name']) {
        echo "<p class='warning'>No author name found</p>";
        return null;
    }
    
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorInfo['name']));
    
    // Check if author exists
    $stmt = $db->prepare("SELECT id FROM authors WHERE slug = ?");
    $stmt->execute([$slug]);
    $author = $stmt->fetch();
    
    if ($author) {
        echo "<p class='info'>Author already exists: {$authorInfo['name']}</p>";
        
        // Update author with age and location if not set
        $updateStmt = $db->prepare("UPDATE authors SET age = COALESCE(age, ?), location = COALESCE(location, ?) WHERE id = ?");
        $updateStmt->execute([$authorInfo['age'], $authorInfo['location'], $author['id']]);
        
        return $author['id'];
    }
    
    // Create new author
    $bio = "{$authorInfo['name']} is a child author" . 
           ($authorInfo['age'] ? ' aged ' . $authorInfo['age'] : '') . 
           ($authorInfo['location'] ? ' from ' . $authorInfo['location'] : '') . 
           '.';
    
    $stmt = $db->prepare("INSERT INTO authors (name, slug, bio, author_type, age, location, is_published) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $authorInfo['name'],
        $slug,
        $bio,
        'child',
        $authorInfo['age'],
        $authorInfo['location']
    ]);
    
    $authorId = $db->lastInsertId();
    echo "<p class='success'>Created author: {$authorInfo['name']}" . 
         ($authorInfo['age'] ? ', age: ' . $authorInfo['age'] : '') . 
         ($authorInfo['location'] ? ', location: ' . $authorInfo['location'] : '') . 
         "</p>";
    
    return $authorId;
}

// Check if story exists by slug
function storyExistsBySlug($db, $slug) {
    $stmt = $db->prepare("SELECT id FROM stories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Update existing story
function updateStory($db, $storyId, $data) {
    $stmt = $db->prepare("
        UPDATE stories SET
            excerpt = COALESCE(?, excerpt),
            estimated_reading_time = COALESCE(?, estimated_reading_time),
            age_group = COALESCE(?, age_group),
            tags = COALESCE(?, tags)
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['excerpt'],
        $data['reading_time'],
        $data['age_group'],
        $data['tags'],
        $storyId
    ]);
    
    return $stmt->rowCount();
}

// Process WordPress export directory
$wpDir = __DIR__ . '/../_wp migration/wp-md/custom/childrens-story';

if (!is_dir($wpDir)) {
    echo "<p class='error'>WordPress export directory not found: $wpDir</p>";
    exit;
}

echo "<h2>Importing Children's Stories</h2>";
echo "<p class='info'>Import mode: " . ($_POST['mode'] === 'clean' ? 'Clean Import' : 'Update Mode') . "</p>";

// Get all story directories
$storyDirs = array_filter(glob("$wpDir/*"), 'is_dir');
echo "<p>Found " . count($storyDirs) . " stories to process</p>";

// Stats
$stats = [
    'created' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0
];

foreach ($storyDirs as $storyDir) {
    $mdFile = "$storyDir/index.md";
    
    if (!file_exists($mdFile)) {
        echo "<p class='error'>Markdown file not found: $mdFile</p>";
        continue;
    }
    
    // Read and parse markdown file
    $content = file_get_contents($mdFile);
    
    // Extract front matter
    $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)/s';
    if (!preg_match($pattern, $content, $matches)) {
        echo "<p class='error'>Invalid markdown format in: $mdFile</p>";
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
    
    // Extract author info
    $authorInfo = extractAuthorInfo($title);
    $authorId = getOrCreateAuthor($db, $authorInfo);
    
    // Process cover image
    $imagesDir = "$storyDir/images";
    $coverUrl = '/images/default-cover.svg'; // Default
    
    if (is_dir($imagesDir)) {
        $images = glob("$imagesDir/*.*");
        if (!empty($images)) {
            $coverImage = basename($images[0]);
            
            // Copy image to uploads directory
            $uploadDir = __DIR__ . '/../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $destination = $uploadDir . $coverImage;
            if (copy($images[0], $destination)) {
                $coverUrl = '/uploads/' . $coverImage;
                echo "<p class='success'>Copied cover image: $coverImage</p>";
                
                // Insert into media table
                try {
                    $stmt = $db->prepare("INSERT INTO media (entity_type, type, filename, url, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute(['story', 'image/png', $coverImage, $coverUrl]);
                    echo "<p class='success'>Added image to media library</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>Failed to add to media library: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p class='error'>Failed to copy image: $coverImage</p>";
            }
        }
    }
    
    // Generate clean excerpt
    $excerpt = cleanExcerpt($markdownContent);
    
    // Convert markdown to HTML
    $html = $markdownContent; // Simple conversion - in production use a proper markdown parser
    
    // Generate slug
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    
    // Calculate reading time
    $readingTime = getReadingTime($markdownContent);
    
    // Determine age group
    $ageGroup = getAgeGroup($authorInfo['age']);
    
    // Generate tags
    $tags = generateTags($markdownContent);
    
    // Check if story already exists
    $existingStory = storyExistsBySlug($db, $slug);
    
    // Prepare story data
    $storyData = [
        'excerpt' => $excerpt,
        'reading_time' => $readingTime,
        'age_group' => $ageGroup,
        'tags' => $tags
    ];
    
    if ($existingStory && $_POST['mode'] === 'update') {
        // Update existing story
        $updated = updateStory($db, $existingStory['id'], $storyData);
        if ($updated) {
            echo "<p class='success'>Updated existing story: $title (ID: {$existingStory['id']})</p>";
            $stats['updated']++;
        } else {
            echo "<p class='info'>No changes needed for: $title</p>";
            $stats['skipped']++;
        }
        
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
    } else {
        // Insert new story
        try {
            $stmt = $db->prepare("
                INSERT INTO stories (
                    title, slug, content, excerpt, cover_url,
                    is_published, source_type, allow_reviews,
                    estimated_reading_time, age_group, tags
                ) VALUES (?, ?, ?, ?, ?, 1, 'child', 0, ?, ?, ?)
            ");
        
        $stmt->execute([
            $title,
            $slug,
            $html,
            $excerpt,
            $coverUrl,
            $readingTime,
            $ageGroup,
            $tags
        ]);
        
        $storyId = $db->lastInsertId();
        echo "<p class='success'>Created story with ID: $storyId</p>";
        
        // Associate with author
        if ($authorId) {
            $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
            $stmt->execute([$storyId, $authorId]);
            echo "<p class='success'>Associated story with author ID: $authorId</p>";
        }
            $stats['created']++;
        } catch (Exception $e) {
            echo "<p class='error'>Failed to create story: " . $e->getMessage() . "</p>";
            $stats['errors']++;
        }
    }
}

echo "<h2>Import Complete!</h2>";
echo "<p class='success'>Summary:</p>";
echo "<ul>";
echo "<li>Created: {$stats['created']} stories</li>";
echo "<li>Updated: {$stats['updated']} stories</li>";
echo "<li>Skipped: {$stats['skipped']} stories</li>";
echo "<li>Errors: {$stats['errors']} stories</li>";
echo "</ul>";
echo "<p>Now check the <a href='/admin/stories'>Stories Admin</a> to verify the imported content.</p>";
echo "<p>Then trigger the Netlify rebuild to update the frontend.</p>";
?>
    </div>
</body>
</html>