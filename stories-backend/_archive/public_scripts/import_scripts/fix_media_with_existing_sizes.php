<?php
/**
 * Media File Size Optimizer (Using Existing Sized Images)
 * 
 * This script scans all media entries in the database and updates them to use
 * appropriately sized versions that already exist in the filesystem.
 * It doesn't require ImageMagick or GD extensions.
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

// Function to find an appropriately sized version of an image
function findSizedVersion($originalFilename, $maxSize = 640) {
    // Extract base name without extension
    $pathInfo = pathinfo($originalFilename);
    $baseName = $pathInfo['filename'];
    $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
    
    // Common size patterns to look for
    $sizesToCheck = [
        '50x50', '110x110', '150x150', '180x77', '240x240', '300x300', 
        '440x330', '640x640', '640x800', '640x999'
    ];
    
    // Sort sizes by dimension (ascending)
    usort($sizesToCheck, function($a, $b) {
        $aDim = explode('x', $a);
        $bDim = explode('x', $b);
        $aMax = max((int)$aDim[0], (int)$aDim[1]);
        $bMax = max((int)$bDim[0], (int)$bDim[1]);
        return $aMax - $bMax;
    });
    
    // Find the upload directories to search in
    $searchDirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/2023/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/2024/',
        dirname($_SERVER['DOCUMENT_ROOT'] . '/uploads/') . '/uploads/'
    ];
    
    // First try to find exact size matches
    foreach ($sizesToCheck as $size) {
        // Skip sizes larger than our max
        $sizeDim = explode('x', $size);
        $sizeMax = max((int)$sizeDim[0], (int)$sizeDim[1]);
        if ($sizeMax > $maxSize) continue;
        
        // Check for files with this size in the name
        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) continue;
            
            // Look for files matching the pattern
            $pattern = $dir . '*' . $baseName . '*' . $size . '*' . $extension;
            $files = glob($pattern);
            
            if (!empty($files)) {
                echo "<p class='info'>Found sized version: " . basename($files[0]) . " ($size)</p>";
                flushOutput();
                return $files[0];
            }
            
            // Try alternative pattern (size might be before the name)
            $pattern = $dir . '*' . $size . '*' . $baseName . '*' . $extension;
            $files = glob($pattern);
            
            if (!empty($files)) {
                echo "<p class='info'>Found sized version: " . basename($files[0]) . " ($size)</p>";
                flushOutput();
                return $files[0];
            }
        }
    }
    
    // If no exact match found, try to find any file with similar name
    foreach ($searchDirs as $dir) {
        if (!is_dir($dir)) continue;
        
        $pattern = $dir . '*' . $baseName . '*' . $extension;
        $files = glob($pattern);
        
        if (!empty($files)) {
            // Sort files by size (ascending)
            usort($files, function($a, $b) {
                return filesize($a) - filesize($b);
            });
            
            // Find the first file smaller than 1MB
            foreach ($files as $file) {
                if (filesize($file) < 1024 * 1024) {
                    echo "<p class='info'>Found smaller version: " . basename($file) . " (" . round(filesize($file)/1024) . " KB)</p>";
                    flushOutput();
                    return $file;
                }
            }
            
            // If no small file found, return the smallest one
            echo "<p class='warning'>No small version found, using smallest available: " . basename($files[0]) . " (" . round(filesize($files[0])/1024) . " KB)</p>";
            flushOutput();
            return $files[0];
        }
    }
    
    // No suitable file found
    echo "<p class='error'>No sized version found for: " . $baseName . $extension . "</p>";
    flushOutput();
    return null;
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
        $originalPath = $item['file_path'];
        
        if (strpos($originalPath, 'http') === 0) {
            // For URLs, try to get the file size from headers
            $headers = get_headers($originalPath, 1);
            if (isset($headers['Content-Length'])) {
                $fileSize = $headers['Content-Length'];
            }
        } else if (file_exists($originalPath)) {
            $fileSize = filesize($originalPath);
        }
        
        $stats['total_size_before'] += $fileSize;
        
        // Check if file is large (>300KB)
        if ($fileSize > 300 * 1024 || $fileSize === 0) {
            echo "<p class='info'>File size: " . round($fileSize / 1024) . " KB - Needs optimization</p>";
            flushOutput();
            
            // Extract filename from path or URL
            $filename = basename(parse_url($originalPath, PHP_URL_PATH));
            
            // Find a sized version
            $sizedVersion = findSizedVersion($filename);
            
            if ($sizedVersion) {
                // Create a URL for the sized version
                $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $sizedVersion);
                $newUrl = 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;
                
                // Update database record
                $updateStmt = $db->prepare("UPDATE media SET file_path = ?, file_size = ? WHERE id = ?");
                $updateStmt->execute([$newUrl, filesize($sizedVersion), $item['id']]);
                
                echo "<p class='success'>Updated database record for media ID {$item['id']}</p>";
                echo "<p class='info'>New URL: " . htmlspecialchars($newUrl) . "</p>";
                flushOutput();
                
                $stats['optimized']++;
                $stats['total_size_after'] += filesize($sizedVersion);
            } else {
                echo "<p class='error'>Failed to find sized version for media ID {$item['id']}</p>";
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
    <title>Media File Size Optimizer (Using Existing Sized Images)</title>
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
    <h1>Media File Size Optimizer (Using Existing Sized Images)</h1>
    
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
    echo "<li>Find an existing smaller version of the image in the uploads directories</li>";
    echo "<li>Update the database record to point to the smaller version</li>";
    echo "<li>Provide detailed statistics of the optimization process</li>";
    echo "</ol>";
    echo "<p>The optimization process may take several minutes to complete.</p>";
}
?>
    </div>
</body>
</html>