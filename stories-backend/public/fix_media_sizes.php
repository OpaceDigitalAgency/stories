<?php
/**
 * Media File Size Optimizer
 * 
 * This script scans all media entries in the database and optimizes any images
 * that are too large (>300KB). It creates optimized versions and updates the
 * database records to point to these optimized versions.
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
        return null;
    }
}

// Function to create an optimized version of an image
function createOptimizedVersion($originalUrl, $filename) {
    // Create uploads directory if it doesn't exist
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p class='info'>Created optimized uploads directory</p>";
        flushOutput();
    }
    
    // Generate unique filename
    $uniqueFilename = uniqid() . '-optimized-' . $filename;
    $destination = $uploadDir . $uniqueFilename;
    
    // Create absolute URL
    $relativeUrl = '/uploads/optimized/' . $uniqueFilename;
    $absoluteUrl = 'https://' . $_SERVER['HTTP_HOST'] . $relativeUrl;
    
    // Download the original file if it's a URL
    $tempFile = null;
    if (strpos($originalUrl, 'http') === 0) {
        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        if (copy($originalUrl, $tempFile)) {
            $sourceFile = $tempFile;
            echo "<p class='info'>Downloaded original file from URL</p>";
        } else {
            echo "<p class='error'>Failed to download original file from URL: $originalUrl</p>";
            flushOutput();
            return null;
        }
    } else {
        // If it's a local path
        $sourceFile = $originalUrl;
        if (!file_exists($sourceFile)) {
            echo "<p class='error'>Original file not found: $sourceFile</p>";
            flushOutput();
            return null;
        }
    }
    
    // Check original file size
    $originalSize = filesize($sourceFile);
    echo "<p class='info'>Original file size: " . round($originalSize / 1024) . " KB</p>";
    flushOutput();
    
    // Skip if already small enough
    if ($originalSize < 300 * 1024) {
        echo "<p class='info'>File is already small enough, copying without optimization</p>";
        flushOutput();
        copy($sourceFile, $destination);
        chmod($destination, 0644);
        
        // Clean up temp file if needed
        if ($tempFile) {
            unlink($tempFile);
        }
        
        return [
            'path' => $absoluteUrl,
            'size' => $originalSize
        ];
    }
    
    // Try to use ImageMagick first (much better compression)
    $optimized = false;
    if (extension_loaded('imagick')) {
        try {
            echo "<p class='info'>Using ImageMagick for better compression</p>";
            flushOutput();
            
            $imagick = new Imagick($sourceFile);
            
            // Strip metadata to reduce size
            $imagick->stripImage();
            
            // Get original dimensions
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            echo "<p class='info'>Original dimensions: {$width}x{$height}</p>";
            flushOutput();
            
            // Resize to max 300px width for better performance
            if ($width > 300) {
                $newWidth = 300;
                $newHeight = ($height / $width) * 300;
                $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
                echo "<p class='info'>Resized to {$newWidth}x{$newHeight}</p>";
                flushOutput();
            }
            
            // Set quality based on image format
            if ($imagick->getImageFormat() == 'JPEG') {
                $imagick->setImageCompressionQuality(60); // Very aggressive compression
                $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
                echo "<p class='info'>Applied JPEG compression (60% quality)</p>";
            } else if ($imagick->getImageFormat() == 'PNG') {
                // For PNG, optimize compression
                $imagick->setImageCompressionQuality(95);
                $imagick->setOption('png:compression-level', 9);
                $imagick->setOption('png:compression-strategy', 1);
                $imagick->setOption('png:exclude-chunk', 'all');
                echo "<p class='info'>Applied maximum PNG compression</p>";
            }
            
            // Write the optimized image
            $imagick->writeImage($destination);
            $imagick->destroy();
            
            $optimized = true;
            echo "<p class='success'>Image optimized with ImageMagick</p>";
            flushOutput();
        } catch (Exception $e) {
            echo "<p class='warning'>ImageMagick optimization failed: " . $e->getMessage() . "</p>";
            echo "<p class='info'>Falling back to GD</p>";
            flushOutput();
        }
    }
    
    // Fall back to GD if ImageMagick failed or isn't available
    if (!$optimized && extension_loaded('gd')) {
        try {
            echo "<p class='info'>Using GD for image optimization</p>";
            flushOutput();
            
            // Get image info
            list($width, $height, $type) = getimagesize($sourceFile);
            
            // Only process if it's a supported image type
            if ($type === IMAGETYPE_JPEG || $type === IMAGETYPE_PNG) {
                // Create image resource
                if ($type === IMAGETYPE_JPEG) {
                    $source = imagecreatefromjpeg($sourceFile);
                } else {
                    $source = imagecreatefrompng($sourceFile);
                }
                
                if ($source) {
                    // Calculate new dimensions (max 300px width for better performance)
                    $maxWidth = 300;
                    $newWidth = $width;
                    $newHeight = $height;
                    
                    if ($width > $maxWidth) {
                        $newWidth = $maxWidth;
                        $newHeight = ($height / $width) * $maxWidth;
                    }
                    
                    // Create resized image with proper alpha channel support for PNGs
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    
                    // Preserve transparency for PNG images
                    if ($type === IMAGETYPE_PNG) {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                    }
                    
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    // Save resized image with higher compression
                    if ($type === IMAGETYPE_JPEG) {
                        // Use 60% quality for better compression
                        imagejpeg($resized, $destination, 60);
                    } else {
                        // For PNG, use maximum compression (9)
                        imagepng($resized, $destination, 9);
                    }
                    
                    imagedestroy($resized);
                    imagedestroy($source);
                    
                    $optimized = true;
                    echo "<p class='success'>Image optimized and resized to {$newWidth}x{$newHeight} with GD</p>";
                    flushOutput();
                }
            } else {
                // Just copy the file if it's not a supported type
                copy($sourceFile, $destination);
                echo "<p class='info'>Unsupported image type, copied without optimization</p>";
                flushOutput();
            }
        } catch (Exception $e) {
            // If optimization fails, just copy the file
            copy($sourceFile, $destination);
            echo "<p class='warning'>GD optimization failed: " . $e->getMessage() . "</p>";
            echo "<p class='info'>Copied original file instead</p>";
            flushOutput();
        }
    } else if (!$optimized) {
        // If neither ImageMagick nor GD is available, just copy the file
        copy($sourceFile, $destination);
        echo "<p class='info'>No image libraries available, copied without optimization</p>";
        flushOutput();
    }
    
    // Set proper permissions
    chmod($destination, 0644);
    system("chmod -R 644 " . escapeshellarg($destination));
    system("chown -R www-data:www-data " . escapeshellarg($destination) . " 2>/dev/null");
    
    // Clean up temp file if needed
    if ($tempFile) {
        unlink($tempFile);
    }
    
    // Check final file size
    $finalSize = filesize($destination);
    $reduction = round(($originalSize - $finalSize) / $originalSize * 100);
    echo "<p class='success'>Final file size: " . round($finalSize / 1024) . " KB (reduced by {$reduction}%)</p>";
    flushOutput();
    
    return [
        'path' => $absoluteUrl,
        'size' => $finalSize
    ];
}

// Function to optimize all media files
function optimizeAllMediaFiles($db) {
    // Get all media entries
    $stmt = $db->query("SELECT * FROM media");
    $media = $stmt->fetchAll();
    
    echo "<h2>Found " . count($media) . " media files to check</h2>";
    flushOutput();
    
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
        if (strpos($item['file_path'], 'default-cover') !== false) {
            echo "<p class='info'>Skipping default cover image</p>";
            flushOutput();
            $stats['skipped']++;
            continue;
        }
        
        // Try to get the file size
        $fileSize = 0;
        if (strpos($item['file_path'], 'http') === 0) {
            // For URLs, try to get the file size from headers
            $headers = get_headers($item['file_path'], 1);
            if (isset($headers['Content-Length'])) {
                $fileSize = $headers['Content-Length'];
            }
        } else if (file_exists($item['file_path'])) {
            $fileSize = filesize($item['file_path']);
        }
        
        $stats['total_size_before'] += $fileSize;
        
        // Check if file is large (>300KB) or if size couldn't be determined
        if ($fileSize > 300 * 1024 || $fileSize === 0) {
            echo "<p class='info'>File size: " . round($fileSize / 1024) . " KB - Needs optimization</p>";
            flushOutput();
            
            // Create optimized version
            $result = createOptimizedVersion($item['file_path'], $item['filename']);
            
            if ($result) {
                // Update database record
                $updateStmt = $db->prepare("UPDATE media SET file_path = ?, file_size = ? WHERE id = ?");
                $updateStmt->execute([$result['path'], $result['size'], $item['id']]);
                
                echo "<p class='success'>Updated database record for media ID {$item['id']}</p>";
                echo "<p class='info'>New URL: " . htmlspecialchars($result['path']) . "</p>";
                flushOutput();
                
                $stats['optimized']++;
                $stats['total_size_after'] += $result['size'];
            } else {
                echo "<p class='error'>Failed to optimize media ID {$item['id']}</p>";
                flushOutput();
                $stats['failed']++;
                $stats['total_size_after'] += $fileSize; // No change
            }
        } else {
            echo "<p class='success'>File size: " . round($fileSize / 1024) . " KB - Already optimized</p>";
            flushOutput();
            $stats['skipped']++;
            $stats['total_size_after'] += $fileSize;
        }
    }
    
    return $stats;
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
    </style>
</head>
<body>
    <h1>Media File Size Optimizer</h1>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="action" value="optimize" class="button">Optimize All Media Files</button>
        </form>
    </div>
    
    <div class="log">
<?php
// Process the optimization action
if (isset($_POST['action']) && $_POST['action'] === 'optimize') {
    // Connect to database
    $db = connectToDatabase();
    if (!$db) {
        echo "<p class='error'>Failed to connect to database</p>";
        exit;
    }
    
    // Optimize all media files
    $stats = optimizeAllMediaFiles($db);
    
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
} else {
    // Display initial instructions
    echo "<p class='info'>Click the 'Optimize All Media Files' button to start the optimization process.</p>";
    echo "<p>This tool will:</p>";
    echo "<ol>";
    echo "<li>Scan all media entries in the database</li>";
    echo "<li>Check if each file is too large (>300KB)</li>";
    echo "<li>Create an optimized version using ImageMagick or GD</li>";
    echo "<li>Update the database record to point to the optimized version</li>";
    echo "<li>Provide detailed statistics of the optimization process</li>";
    echo "</ol>";
    echo "<p>The optimization process may take several minutes to complete.</p>";
}
?>
    </div>
</body>
</html>