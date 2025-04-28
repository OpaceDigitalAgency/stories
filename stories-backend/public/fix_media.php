<?php
/**
 * Media Fix Script
 * 
 * This script fixes issues with media files in the database
 */

// Basic error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Media Fix Tool</title>
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
    <h1>Media Fix Tool</h1>
    
    <form method="post">
        <button type="submit" name="action" value="fix" class="button">Fix Media Files</button>
    </form>
    
    <div class="log">
<?php
// Only process if form submitted
if (!isset($_POST['action'])) {
    echo "<p class='info'>Click the button above to fix media files</p>";
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
echo "<h2>Fixing file paths in media table</h2>";
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
echo "<h2>Fixing MIME types</h2>";
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
echo "<h2>Fixing file sizes</h2>";
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
echo "<h2>Fixing story cover URLs</h2>";
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
echo "<h2>Adding missing alt text</h2>";
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

// Fix 6: Copy missing files from WordPress export
echo "<h2>Copying missing files from WordPress export</h2>";
$copiedFiles = 0;

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

if ($wpDir) {
    // Find all image files in WordPress export
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $wpImages = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && strpos($file->getPathname(), '/images/') !== false) {
            $wpImages[] = $file->getPathname();
        }
    }
    
    echo "<p>Found " . count($wpImages) . " images in WordPress export</p>";
    
    // Check each media file
    foreach ($mediaFiles as $media) {
        $filePath = $media['file_path'];
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        
        if (!file_exists($fullPath)) {
            $filename = basename($filePath);
            
            // Try to find a matching file in WordPress export
            foreach ($wpImages as $wpImage) {
                $wpFilename = basename($wpImage);
                
                if (strpos($wpFilename, $filename) !== false || strpos($filename, $wpFilename) !== false) {
                    // Copy the file
                    if (copy($wpImage, $fullPath)) {
                        chmod($fullPath, 0644);
                        echo "<p class='success'>Copied file from WordPress export: $wpImage -> $fullPath</p>";
                        $copiedFiles++;
                        break;
                    }
                }
            }
        }
    }
}

echo "<p class='success'>Copied $copiedFiles files from WordPress export</p>";

// Summary
echo "<h2>Summary</h2>";
echo "<p>Fixed $fixedPaths file paths</p>";
echo "<p>Fixed $fixedMimeTypes MIME types</p>";
echo "<p>Fixed $fixedFileSizes file sizes</p>";
echo "<p>Fixed $fixedCoverUrls story cover URLs</p>";
echo "<p>Added $fixedAltTexts alt texts</p>";
echo "<p>Copied $copiedFiles files from WordPress export</p>";

echo "<p class='success'>Media fix complete!</p>";
?>
    </div>
</body>
</html>