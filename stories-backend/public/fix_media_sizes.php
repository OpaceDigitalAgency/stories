<?php
/**
 * Media File Size Optimizer
 * 
 * This script scans all media entries in the database and optimizes images
 * by creating multiple size variants and updating the database records.
 * It uses the modular image optimization library for consistent behavior.
 */

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Include the image optimization library
require_once __DIR__ . '/../includes/image_optimizer.php';

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
        return null;
    }
}

// Function to check if the media table has the required columns
function checkMediaTableColumns($db) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
        $columnsExist = $stmt->rowCount() > 0;
        
        if (!$columnsExist) {
            echo "<p class='warning'>Media table is missing required columns for multiple image sizes</p>";
            echo "<p class='info'>Please run the <a href='update_media_schema.php'>update_media_schema.php</a> script first</p>";
            flushOutput();
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking media table columns: " . $e->getMessage() . "</p>";
        flushOutput();
        return false;
    }
}

// Function to optimize all media files
function optimizeAllMediaFiles($db) {
    global $OPTIMIZED_DIR;
    
    // Check if the media table has the required columns
    if (!checkMediaTableColumns($db)) {
        return false;
    }
    
    // Get all media entries
    $stmt = $db->query("SELECT * FROM media");
    $media = $stmt->fetchAll();
    
    echo "<h2>Found " . count($media) . " media files to check</h2>";
    flushOutput();
    
    // Create optimized directory if it doesn't exist
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $OPTIMIZED_DIR . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p class='info'>Created optimized uploads directory: $uploadDir</p>";
        flushOutput();
    }
    
    $stats = [
        'total' => count($media),
        'optimized' => 0,
        'skipped' => 0,
        'failed' => 0,
        'total_size_before' => 0,
        'total_size_after' => 0
    ];
    
    foreach ($media as $item) {
        echo "<h3>Processing: " . htmlspecialchars($item['filename']) . " (ID: {$item['id']})</h3>";
        flushOutput();
        
        // Skip if it's the default image
        if (strpos($item['file_path'], 'default-cover') !== false || strpos($item['file_path'], 'default-avatar') !== false) {
            echo "<p class='info'>Skipping default image</p>";
            flushOutput();
            $stats['skipped']++;
            continue;
        }
        
        // Get file size and path
        $filePath = $item['file_path'];
        $fileSize = 0;
        $sourceFile = null;
        
        // Handle URL or local path
        if (strpos($filePath, 'http') === 0) {
            // For URLs, try to get the file size from headers
            $headers = @get_headers($filePath, 1);
            if (isset($headers['Content-Length'])) {
                $fileSize = $headers['Content-Length'];
            }
            
            // Download the file to a temporary location
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            if (@copy($filePath, $tempFile)) {
                $sourceFile = $tempFile;
                echo "<p class='info'>Downloaded file from URL: $filePath</p>";
                flushOutput();
                
                // Update file size from the actual file
                $fileSize = filesize($sourceFile);
            } else {
                echo "<p class='error'>Failed to download file from URL: $filePath</p>";
                flushOutput();
                $stats['failed']++;
                continue;
            }
        } else {
            // For local paths, check if the file exists
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
            if (file_exists($fullPath)) {
                $sourceFile = $fullPath;
                $fileSize = filesize($sourceFile);
            } else {
                echo "<p class='error'>File not found: $fullPath</p>";
                flushOutput();
                $stats['failed']++;
                continue;
            }
        }
        
        echo "<p class='info'>File size: " . round($fileSize / 1024) . " KB</p>";
        flushOutput();
        
        $stats['total_size_before'] += $fileSize;
        
        // Create image variants using our library
        $variants = createImageVariants($sourceFile, $uploadDir, [
            'convert_format' => 'jpg',
            'include_original' => true
        ]);
        
        // Clean up temporary file if needed
        if (strpos($filePath, 'http') === 0 && $sourceFile) {
            @unlink($sourceFile);
        }
        
        if ($variants) {
            // Update the database with all variant URLs
            $updateStmt = $db->prepare("
                UPDATE media 
                SET file_path = :medium_url,
                    file_size = :medium_size,
                    thumbnail_url = :thumbnail_url,
                    small_url = :small_url,
                    medium_url = :medium_url,
                    large_url = :large_url
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':medium_url' => $variants['medium']['url'] ?? $variants['original']['url'],
                ':medium_size' => $variants['medium']['size'] ?? $variants['original']['size'],
                ':thumbnail_url' => $variants['thumbnail']['url'] ?? '',
                ':small_url' => $variants['small']['url'] ?? '',
                ':medium_url' => $variants['medium']['url'] ?? '',
                ':large_url' => $variants['large']['url'] ?? '',
                ':id' => $item['id']
            ]);
            
            echo "<p class='success'>Updated database record for media ID {$item['id']}</p>";
            echo "<p class='info'>Primary URL: " . htmlspecialchars($variants['medium']['url'] ?? $variants['original']['url']) . "</p>";
            
            // Calculate size reduction
            $newSize = $variants['medium']['size'] ?? $variants['original']['size'] ?? 0;
            $reduction = $fileSize > 0 ? round(($fileSize - $newSize) / $fileSize * 100) : 0;
            echo "<p class='success'>Size reduction: {$reduction}%</p>";
            flushOutput();
            
            $stats['optimized']++;
            $stats['total_size_after'] += $newSize;
        } else {
            echo "<p class='error'>Failed to create image variants for media ID {$item['id']}</p>";
            flushOutput();
            $stats['failed']++;
            $stats['total_size_after'] += $fileSize; // No change
        }
    }
    
    return $stats;
}

// Function to update story cover URLs
function updateStoryCoverUrls($db) {
    echo "<h2>Updating story cover URLs to use optimized images</h2>";
    flushOutput();
    
    // Get all stories with cover URLs
    $stmt = $db->query("SELECT id, title, cover_url FROM stories WHERE cover_url IS NOT NULL");
    $stories = $stmt->fetchAll();
    
    echo "<p>Found " . count($stories) . " stories with cover images</p>";
    flushOutput();
    
    $updatedCount = 0;
    
    foreach ($stories as $story) {
        $coverUrl = $story['cover_url'];
        
        // Skip default images
        if (strpos($coverUrl, 'default-cover') !== false) {
            continue;
        }
        
        echo "<p>Processing story: " . htmlspecialchars($story['title']) . " (ID: {$story['id']})</p>";
        flushOutput();
        
        // Extract media ID from URL if possible
        if (preg_match('/\/media\/(\d+)\//', $coverUrl, $matches)) {
            $mediaId = $matches[1];
            
            // Get the media record
            $mediaStmt = $db->prepare("SELECT * FROM media WHERE id = ?");
            $mediaStmt->execute([$mediaId]);
            $media = $mediaStmt->fetch();
            
            if ($media && !empty($media['medium_url'])) {
                // Update the story to use the medium-sized image
                $updateStmt = $db->prepare("UPDATE stories SET cover_url = ? WHERE id = ?");
                $updateStmt->execute([$media['medium_url'], $story['id']]);
                
                echo "<p class='success'>Updated cover URL for story {$story['id']}</p>";
                echo "<p class='info'>Old URL: " . htmlspecialchars($coverUrl) . "</p>";
                echo "<p class='info'>New URL: " . htmlspecialchars($media['medium_url']) . "</p>";
                flushOutput();
                
                $updatedCount++;
            }
        } else {
            // Try to find a matching media record by filename
            $filename = basename(parse_url($coverUrl, PHP_URL_PATH));
            
            $mediaStmt = $db->prepare("SELECT * FROM media WHERE filename LIKE ? AND medium_url IS NOT NULL");
            $mediaStmt->execute(['%' . $filename . '%']);
            $media = $mediaStmt->fetch();
            
            if ($media) {
                // Update the story to use the medium-sized image
                $updateStmt = $db->prepare("UPDATE stories SET cover_url = ? WHERE id = ?");
                $updateStmt->execute([$media['medium_url'], $story['id']]);
                
                echo "<p class='success'>Updated cover URL for story {$story['id']} by filename match</p>";
                echo "<p class='info'>Old URL: " . htmlspecialchars($coverUrl) . "</p>";
                echo "<p class='info'>New URL: " . htmlspecialchars($media['medium_url']) . "</p>";
                flushOutput();
                
                $updatedCount++;
            }
        }
    }
    
    echo "<p class='success'>Updated $updatedCount story cover URLs</p>";
    flushOutput();
    
    return $updatedCount;
}

// Main HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Media File Size Optimizer</title>
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
        .stats {
            background: #e9f7ef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .stats-item {
            margin-bottom: 10px;
        }
        .libraries {
            background: #e9f7ef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Media File Size Optimizer</h1>
    
    <div class="libraries">
        <h2>Image Processing Libraries</h2>
        <?php
        $libraries = getAvailableImageLibraries();
        if ($libraries['imagick']) {
            echo "<p class='success'>✓ ImageMagick is available (best quality)</p>";
        } else {
            echo "<p class='warning'>✗ ImageMagick is not available</p>";
        }
        
        if ($libraries['gd']) {
            echo "<p class='success'>✓ GD is available</p>";
        } else {
            echo "<p class='warning'>✗ GD is not available</p>";
        }
        
        if (!$libraries['imagick'] && !$libraries['gd']) {
            echo "<p class='error'>No image processing libraries available. Images will be copied without optimization.</p>";
        }
        ?>
    </div>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="action" value="optimize" class="button">Optimize All Media Files</button>
            <button type="submit" name="action" value="update_stories" class="button" style="background: #2196F3;">Update Story Cover URLs</button>
        </form>
    </div>
    
    <div class="log">
<?php
// Process the action
if (isset($_POST['action'])) {
    // Connect to database
    $db = connectToDatabase();
    if (!$db) {
        echo "<p class='error'>Failed to connect to database</p>";
        exit;
    }
    
    if ($_POST['action'] === 'optimize') {
        // Optimize all media files
        $stats = optimizeAllMediaFiles($db);
        
        if ($stats) {
            // Display summary
            echo "<div class='stats'>";
            echo "<h2>Optimization Summary</h2>";
            echo "<div class='stats-item'><strong>Total files processed:</strong> {$stats['total']}</div>";
            echo "<div class='stats-item'><strong>Files optimized:</strong> {$stats['optimized']}</div>";
            echo "<div class='stats-item'><strong>Files skipped (already optimized):</strong> {$stats['skipped']}</div>";
            echo "<div class='stats-item'><strong>Files failed:</strong> {$stats['failed']}</div>";
            echo "<div class='stats-item'><strong>Total size before:</strong> " . round($stats['total_size_before'] / (1024 * 1024), 2) . " MB</div>";
            echo "<div class='stats-item'><strong>Total size after:</strong> " . round($stats['total_size_after'] / (1024 * 1024), 2) . " MB</div>";
            
            $totalReduction = $stats['total_size_before'] > 0 ? 
                round(($stats['total_size_before'] - $stats['total_size_after']) / $stats['total_size_before'] * 100, 2) : 0;
            
            echo "<div class='stats-item'><strong>Total size reduction:</strong> {$totalReduction}%</div>";
            echo "</div>";
            
            echo "<p class='info'>Now you can update story cover URLs to use the optimized images:</p>";
            echo "<form method='post'><button type='submit' name='action' value='update_stories' class='button' style='background: #2196F3;'>Update Story Cover URLs</button></form>";
        }
    } else if ($_POST['action'] === 'update_stories') {
        // Update story cover URLs
        $updatedCount = updateStoryCoverUrls($db);
        
        echo "<div class='stats'>";
        echo "<h2>Story Update Summary</h2>";
        echo "<div class='stats-item'><strong>Stories updated:</strong> {$updatedCount}</div>";
        echo "</div>";
    }
} else {
    // Display initial instructions
    echo "<p class='info'>Click the 'Optimize All Media Files' button to start the optimization process.</p>";
    echo "<p>This tool will:</p>";
    echo "<ol>";
    echo "<li>Scan all media entries in the database</li>";
    echo "<li>Create multiple size variants of each image (thumbnail, small, medium, large)</li>";
    echo "<li>Convert images to JPG format for better compression</li>";
    echo "<li>Update the database records with all variant URLs</li>";
    echo "<li>Provide detailed statistics of the optimization process</li>";
    echo "</ol>";
    echo "<p>After optimizing media files, you can update story cover URLs to use the optimized images.</p>";
    echo "<p>The optimization process may take several minutes to complete.</p>";
}
?>
    </div>
</body>
</html>