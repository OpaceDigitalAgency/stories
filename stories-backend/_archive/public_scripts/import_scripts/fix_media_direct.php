<?php
/**
 * Direct Media Fix Script
 * 
 * This script directly updates the database to use smaller versions of images
 * that already exist in your uploads folders. It doesn't require any image
 * processing libraries and will work immediately.
 */

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
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
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "<p class='success'>Database connection successful</p>";
    flushOutput();
} catch (PDOException $e) {
    echo "<p class='error'>Database connection failed: " . $e->getMessage() . "</p>";
    flushOutput();
    exit;
}

// Function to get the size of a remote file
function getRemoteFileSize($url) {
    $headers = get_headers($url, 1);
    if (isset($headers['Content-Length'])) {
        return (int)$headers['Content-Length'];
    }
    return 0;
}

// Function to extract base name from filename
function getBaseName($filename) {
    // Remove any unique IDs at the beginning (common pattern in your files)
    if (preg_match('/^[a-f0-9]+-(.+)$/', $filename, $matches)) {
        $filename = $matches[1];
    }
    
    // Remove size indicators if present
    $filename = preg_replace('/-\d+x\d+(\.[a-zA-Z]+)$/', '$1', $filename);
    
    // Remove extension
    return pathinfo($filename, PATHINFO_FILENAME);
}

// Function to find the best sized version of an image
function findBestSizedVersion($originalFilename, $maxWidth = 640) {
    $baseName = getBaseName($originalFilename);
    echo "<p class='info'>Looking for sized versions of: $baseName</p>";
    flushOutput();
    
    // Define size preferences in order (smaller to larger)
    $preferredSizes = [
        '50x50', '110x110', '150x150', '180x77', '240x240', '300x300', 
        '440x330', '640x640'
    ];
    
    // Define directories to search
    $searchDirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/2023/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/2024/'
    ];
    
    // First try to find exact matches for preferred sizes
    foreach ($preferredSizes as $size) {
        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) continue;
            
            // Look for files with this size in the name
            $files = glob($dir . '*' . $baseName . '*' . $size . '*');
            
            if (!empty($files)) {
                $file = $files[0];
                $fileSize = filesize($file);
                echo "<p class='success'>Found $size version: " . basename($file) . " (" . round($fileSize/1024) . " KB)</p>";
                flushOutput();
                
                // Create URL
                $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
                return [
                    'path' => 'https://' . $_SERVER['HTTP_HOST'] . $relativePath,
                    'size' => $fileSize
                ];
            }
        }
    }
    
    // If no preferred size found, look for any file with the base name that's smaller than 1MB
    foreach ($searchDirs as $dir) {
        if (!is_dir($dir)) continue;
        
        $files = glob($dir . '*' . $baseName . '*');
        
        if (!empty($files)) {
            // Sort files by size
            usort($files, function($a, $b) {
                return filesize($a) - filesize($b);
            });
            
            // Find first file smaller than 1MB
            foreach ($files as $file) {
                $fileSize = filesize($file);
                if ($fileSize < 1024 * 1024) {
                    echo "<p class='success'>Found smaller version: " . basename($file) . " (" . round($fileSize/1024) . " KB)</p>";
                    flushOutput();
                    
                    // Create URL
                    $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
                    return [
                        'path' => 'https://' . $_SERVER['HTTP_HOST'] . $relativePath,
                        'size' => $fileSize
                    ];
                }
            }
            
            // If no file smaller than 1MB, use the smallest one
            $file = $files[0];
            $fileSize = filesize($file);
            echo "<p class='warning'>No small version found, using smallest available: " . basename($file) . " (" . round($fileSize/1024) . " KB)</p>";
            flushOutput();
            
            // Create URL
            $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
            return [
                'path' => 'https://' . $_SERVER['HTTP_HOST'] . $relativePath,
                'size' => $fileSize
            ];
        }
    }
    
    // No suitable file found
    echo "<p class='error'>No sized version found for: $baseName</p>";
    flushOutput();
    return null;
}

// Main HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Media Fix Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #4a6ee0; }
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
    <h1>Direct Media Fix Tool</h1>
    <p>This tool will find smaller versions of your images and update the database to use them.</p>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="action" value="fix" class="button">Fix Media Files</button>
        </form>
    </div>
    
    <div class="log">
<?php
// Process the fix action
if (isset($_POST['action']) && $_POST['action'] === 'fix') {
    // Get all media entries
    $stmt = $db->query("SELECT * FROM media");
    $media = $stmt->fetchAll();
    
    echo "<h2>Found " . count($media) . " media files to check</h2>";
    flushOutput();
    
    $stats = [
        'total' => count($media),
        'fixed' => 0,
        'skipped' => 0,
        'failed' => 0,
        'total_size_before' => 0,
        'total_size_after' => 0
    ];
    
    foreach ($media as $item) {
        echo "<h3>Processing: " . htmlspecialchars($item['filename']) . " (ID: {$item['id']})</h3>";
        flushOutput();
        
        // Skip if it's a default image
        if (strpos($item['file_path'], 'default-') !== false) {
            echo "<p class='info'>Skipping default image</p>";
            flushOutput();
            $stats['skipped']++;
            continue;
        }
        
        // Get current file size
        $currentSize = 0;
        if (strpos($item['file_path'], 'http') === 0) {
            $currentSize = getRemoteFileSize($item['file_path']);
        } else if (file_exists($item['file_path'])) {
            $currentSize = filesize($item['file_path']);
        }
        
        echo "<p class='info'>Current file size: " . round($currentSize / 1024) . " KB</p>";
        flushOutput();
        
        $stats['total_size_before'] += $currentSize;
        
        // Check if file is large (>300KB)
        if ($currentSize > 300 * 1024 || $currentSize === 0) {
            // Find a better sized version
            $result = findBestSizedVersion($item['filename']);
            
            if ($result) {
                // Update database record
                $updateStmt = $db->prepare("UPDATE media SET file_path = ?, file_size = ? WHERE id = ?");
                $updateStmt->execute([$result['path'], $result['size'], $item['id']]);
                
                echo "<p class='success'>Updated database record for media ID {$item['id']}</p>";
                echo "<p class='info'>New URL: " . htmlspecialchars($result['path']) . "</p>";
                echo "<p class='info'>Size reduction: " . round(($currentSize - $result['size']) / $currentSize * 100) . "%</p>";
                flushOutput();
                
                $stats['fixed']++;
                $stats['total_size_after'] += $result['size'];
            } else {
                echo "<p class='error'>Failed to find a better version for media ID {$item['id']}</p>";
                flushOutput();
                $stats['failed']++;
                $stats['total_size_after'] += $currentSize; // No change
            }
        } else {
            echo "<p class='success'>File is already small enough (" . round($currentSize / 1024) . " KB)</p>";
            flushOutput();
            $stats['skipped']++;
            $stats['total_size_after'] += $currentSize;
        }
    }
    
    // Display summary
    echo "<div class='stats'>";
    echo "<h2>Fix Summary</h2>";
    echo "<div class='stats-item'><strong>Total files processed:</strong> {$stats['total']}</div>";
    echo "<div class='stats-item'><strong>Files fixed:</strong> {$stats['fixed']}</div>";
    echo "<div class='stats-item'><strong>Files skipped (already optimized):</strong> {$stats['skipped']}</div>";
    echo "<div class='stats-item'><strong>Files failed:</strong> {$stats['failed']}</div>";
    echo "<div class='stats-item'><strong>Total size before:</strong> " . round($stats['total_size_before'] / (1024 * 1024), 2) . " MB</div>";
    echo "<div class='stats-item'><strong>Total size after:</strong> " . round($stats['total_size_after'] / (1024 * 1024), 2) . " MB</div>";
    
    $totalReduction = $stats['total_size_before'] > 0 ? 
        round(($stats['total_size_before'] - $stats['total_size_after']) / $stats['total_size_before'] * 100, 2) : 0;
    
    echo "<div class='stats-item'><strong>Total size reduction:</strong> {$totalReduction}%</div>";
    echo "</div>";
    
    // Fix story cover URLs too
    echo "<h2>Fixing story cover URLs</h2>";
    $fixedCovers = 0;
    
    $stmt = $db->query("SELECT id, title, cover_url FROM stories WHERE cover_url IS NOT NULL");
    $stories = $stmt->fetchAll();
    
    foreach ($stories as $story) {
        $coverUrl = $story['cover_url'];
        
        // Skip if it's a default image
        if (strpos($coverUrl, 'default-') !== false) {
            continue;
        }
        
        // Get current file size
        $currentSize = 0;
        if (strpos($coverUrl, 'http') === 0) {
            $currentSize = getRemoteFileSize($coverUrl);
        } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . $coverUrl)) {
            $currentSize = filesize($_SERVER['DOCUMENT_ROOT'] . $coverUrl);
        }
        
        // Check if file is large (>300KB)
        if ($currentSize > 300 * 1024 || $currentSize === 0) {
            // Extract filename
            $filename = basename(parse_url($coverUrl, PHP_URL_PATH));
            
            // Find a better sized version
            $result = findBestSizedVersion($filename);
            
            if ($result) {
                // Update database record
                $updateStmt = $db->prepare("UPDATE stories SET cover_url = ? WHERE id = ?");
                $updateStmt->execute([$result['path'], $story['id']]);
                
                echo "<p class='success'>Updated cover URL for story {$story['id']} ({$story['title']})</p>";
                echo "<p class='info'>Old URL: " . htmlspecialchars($coverUrl) . "</p>";
                echo "<p class='info'>New URL: " . htmlspecialchars($result['path']) . "</p>";
                flushOutput();
                
                $fixedCovers++;
            }
        }
    }
    
    echo "<p class='success'>Fixed $fixedCovers story cover URLs</p>";
    
} else {
    // Display initial instructions
    echo "<p class='info'>Click the 'Fix Media Files' button to start the process.</p>";
    echo "<p>This tool will:</p>";
    echo "<ol>";
    echo "<li>Scan all media entries in the database</li>";
    echo "<li>Find existing smaller versions of each image in your uploads directories</li>";
    echo "<li>Update the database to use these smaller versions</li>";
    echo "<li>Also fix story cover URLs to use smaller images</li>";
    echo "</ol>";
    echo "<p>This approach doesn't require any image processing libraries and will work immediately.</p>";
}
?>
    </div>
</body>
</html>