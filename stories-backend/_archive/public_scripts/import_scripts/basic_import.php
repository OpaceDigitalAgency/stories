<?php
/**
 * Basic WordPress Import Script
 * 
 * A simplified version with minimal dependencies
 */

// Basic error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Basic WordPress Import</title>
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
    </style>
</head>
<body>
    <h1>Basic WordPress Import</h1>
    
    <form method="post">
        <button type="submit" name="action" value="import" class="button">Start Import</button>
    </form>
    
    <div class="log">
<?php
// Only process if form submitted
if (!isset($_POST['action'])) {
    echo "<p class='info'>Click the button above to begin</p>";
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

// Find WordPress export directory
$wpDir = null;
$possiblePaths = [
    __DIR__ . '/../_wp-migration/wp-md/custom/childrens-story',
    __DIR__ . '/../_wp-migration/wp-md',
    __DIR__ . '/../_wp migration/wp-md/custom/childrens-story',
    __DIR__ . '/../_wp migration/wp-md'
];

foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $wpDir = $path;
        echo "<p class='success'>Found WordPress export directory: $wpDir</p>";
        break;
    }
}

if (!$wpDir) {
    echo "<p class='error'>WordPress export directory not found</p>";
    exit;
}

// Find story directories
$storyDirs = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getFilename() === 'index.md') {
        $storyDirs[] = dirname($file->getPathname());
    }
}

echo "<p>Found " . count($storyDirs) . " stories to process</p>";

// Stats
$stats = [
    'created' => 0,
    'errors' => 0
];

// Process each story
foreach ($storyDirs as $storyDir) {
    $mdFile = "$storyDir/index.md";
    
    if (!file_exists($mdFile)) {
        echo "<p class='error'>Markdown file not found: $mdFile</p>";
        continue;
    }
    
    // Read markdown file
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
    $authorName = null;
    $authorAge = null;
    $authorLocation = null;
    
    if (preg_match('/by\s+([^,]+)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,.]+))?/i', $title, $matches)) {
        $authorName = trim($matches[1]);
        $authorAge = isset($matches[2]) ? trim($matches[2]) : null;
        $authorLocation = isset($matches[3]) ? trim($matches[3]) : null;
    }
    
    echo "<p>Author: $authorName, Age: $authorAge, Location: $authorLocation</p>";
    
    // Create or get author
    if ($authorName) {
        $authorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorName));
        
        // Check if author exists
        $stmt = $db->prepare("SELECT id FROM authors WHERE slug = ?");
        $stmt->execute([$authorSlug]);
        $author = $stmt->fetch();
        
        if ($author) {
            $authorId = $author['id'];
            echo "<p class='info'>Author already exists: $authorName (ID: $authorId)</p>";
            
            // Update author
            $stmt = $db->prepare("UPDATE authors SET age = ?, location = ? WHERE id = ?");
            $stmt->execute([$authorAge, $authorLocation, $authorId]);
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
                $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $uniqueFilename,
                    $coverUrl,
                    'image/png', // Simplified
                    filesize($destination)
                ]);
                echo "<p class='success'>Added to media library</p>";
            }
        }
    }
    
    // Extract excerpt
    $paragraphs = preg_split('/\n\s*\n/', $markdownContent);
    $excerpt = trim($paragraphs[0]);
    
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
            '5 minutes', // Simplified
            '7-12' // Default
        ]);
        
        $storyId = $db->lastInsertId();
        echo "<p class='success'>Created story with ID: $storyId</p>";
        
        // Associate with author
        if ($authorId) {
            $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
            $stmt->execute([$storyId, $authorId]);
            echo "<p class='success'>Associated with author</p>";
        }
        
        // Add tags
        $tags = ['children\'s story'];
        
        foreach ($tags as $tagName) {
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
        }
        
        $stats['created']++;
    } catch (Exception $e) {
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
echo "<p>Check the <a href='/admin/stories'>Stories Admin</a> to verify the imported content.</p>";
?>
    </div>
</body>
</html>