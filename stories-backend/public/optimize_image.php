<?php
/**
 * Simple Image Optimization Wrapper
 * 
 * This script provides a simple interface to optimize images using the
 * modular image optimization library. It can be used to optimize a single
 * image or all images in the media table.
 */

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

// Include the image optimization library
require_once __DIR__ . '/../includes/image_optimizer.php';

// Database connection function
function connectToDatabase() {
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
        echo "<p style='color:green'>Database connection successful</p>";
        return $db;
    } catch (PDOException $e) {
        echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
        return null;
    }
}

// Function to optimize a single image
function optimizeSingleImage($imagePath, $destinationDir = null) {
    if (!file_exists($imagePath)) {
        echo "<p style='color:red'>Image not found: $imagePath</p>";
        return false;
    }
    
    if ($destinationDir === null) {
        $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
    }
    
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
        echo "<p style='color:blue'>Created destination directory: $destinationDir</p>";
    }
    
    echo "<p style='color:blue'>Optimizing image: " . basename($imagePath) . "</p>";
    
    // Create image variants
    $variants = createImageVariants($imagePath, $destinationDir, [
        'convert_format' => 'jpg',
        'include_original' => true
    ]);
    
    if ($variants) {
        echo "<p style='color:green'>Successfully created image variants:</p>";
        echo "<ul>";
        foreach ($variants as $size => $info) {
            echo "<li>$size: " . basename($info['path']) . " (" . round($info['size'] / 1024) . " KB)</li>";
        }
        echo "</ul>";
        return $variants;
    } else {
        echo "<p style='color:red'>Failed to create image variants</p>";
        return false;
    }
}

// Use the updateMediaRecord function from the image_optimizer.php library

// Function to optimize all media files
function optimizeAllMedia($db) {
    // Check if the media table has the required columns
    try {
        $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
        if ($stmt->rowCount() === 0) {
            echo "<p style='color:red'>Media table is missing required columns. Please run update_media_schema.php first.</p>";
            return false;
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking media table structure: " . $e->getMessage() . "</p>";
        return false;
    }
    
    // Get all media entries
    $stmt = $db->query("SELECT * FROM media");
    $media = $stmt->fetchAll();
    
    echo "<h2>Found " . count($media) . " media files to optimize</h2>";
    
    $stats = [
        'total' => count($media),
        'optimized' => 0,
        'skipped' => 0,
        'failed' => 0
    ];
    
    foreach ($media as $item) {
        echo "<h3>Processing: " . htmlspecialchars($item['filename']) . " (ID: {$item['id']})</h3>";
        
        // Skip default images
        if (strpos($item['file_path'], 'default-') !== false) {
            echo "<p style='color:blue'>Skipping default image</p>";
            $stats['skipped']++;
            continue;
        }
        
        // Get the file path
        $filePath = $item['file_path'];
        if (strpos($filePath, 'http') === 0) {
            // For URLs, download the file first
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            if (copy($filePath, $tempFile)) {
                echo "<p style='color:blue'>Downloaded file from URL</p>";
                $filePath = $tempFile;
            } else {
                echo "<p style='color:red'>Failed to download file from URL: $filePath</p>";
                $stats['failed']++;
                continue;
            }
        } else {
            // For local paths, make sure it's an absolute path
            if (strpos($filePath, '/') !== 0) {
                $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($filePath, '/');
            }
            
            if (!file_exists($filePath)) {
                echo "<p style='color:red'>File not found: $filePath</p>";
                $stats['failed']++;
                continue;
            }
        }
        
        // Optimize the image
        $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
        $variants = optimizeSingleImage($filePath, $destinationDir);
        
        // Clean up temp file if needed
        if (isset($tempFile) && file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        if ($variants) {
            // Update the media record
            if (updateMediaRecord($db, $item['id'], $variants)) {
                $stats['optimized']++;
            } else {
                $stats['failed']++;
            }
        } else {
            $stats['failed']++;
        }
    }
    
    echo "<h2>Optimization Summary</h2>";
    echo "<p>Total files: {$stats['total']}</p>";
    echo "<p>Optimized: {$stats['optimized']}</p>";
    echo "<p>Skipped: {$stats['skipped']}</p>";
    echo "<p>Failed: {$stats['failed']}</p>";
    
    return $stats;
}

// Main HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Optimization Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #4a6ee0; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <h1>Image Optimization Tool</h1>
    
    <div>
        <h2>Optimize a Single Image</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="image">Select an image to optimize:</label>
                <input type="file" name="image" id="image">
            </div>
            <button type="submit" name="action" value="optimize_single" class="button">Optimize Image</button>
        </form>
    </div>
    
    <div>
        <h2>Optimize All Media</h2>
        <form method="post">
            <button type="submit" name="action" value="optimize_all" class="button">Optimize All Media</button>
        </form>
    </div>
    
    <div>
        <h2>Results</h2>
        <?php
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'optimize_single' && isset($_FILES['image'])) {
                    // Handle single image optimization
                    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $tempPath = $_FILES['image']['tmp_name'];
                        $filename = $_FILES['image']['name'];
                        
                        echo "<h3>Optimizing uploaded image: $filename</h3>";
                        
                        $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
                        $variants = optimizeSingleImage($tempPath, $destinationDir);
                        
                        if ($variants) {
                            echo "<p style='color:green'>Image optimized successfully!</p>";
                            echo "<h4>Preview:</h4>";
                            
                            foreach ($variants as $size => $info) {
                                echo "<div style='margin-bottom: 20px;'>";
                                echo "<h5>$size (" . round($info['size'] / 1024) . " KB)</h5>";
                                echo "<img src='" . htmlspecialchars($info['url']) . "' style='max-width: 100%; max-height: 300px;'>";
                                echo "</div>";
                            }
                        }
                    } else {
                        echo "<p style='color:red'>Error uploading file: " . $_FILES['image']['error'] . "</p>";
                    }
                } else if ($_POST['action'] === 'optimize_all') {
                    // Handle optimizing all media
                    $db = connectToDatabase();
                    if ($db) {
                        optimizeAllMedia($db);
                    }
                }
            }
        }
        ?>
    </div>
</body>
</html>