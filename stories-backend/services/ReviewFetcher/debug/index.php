<?php
/**
 * Debug Logs Directory Index
 * 
 * This file provides direct access to debug logs and HTML files.
 */

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Get the requested file
$requestedFile = isset($_GET['file']) ? basename($_GET['file']) : '';
$filePath = __DIR__ . '/' . $requestedFile;

// Check if a specific file is requested
if (!empty($requestedFile) && file_exists($filePath) && is_file($filePath)) {
    // Get file extension
    $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    
    // Set appropriate content type
    if ($extension === 'html') {
        header('Content-Type: text/html');
    } elseif ($extension === 'log' || $extension === 'txt') {
        header('Content-Type: text/plain');
    } else {
        header('Content-Type: application/octet-stream');
    }
    
    // Output the file
    readfile($filePath);
    exit;
}

// Otherwise, show a directory listing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Logs Directory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item:hover {
            background-color: #f9f9f9;
        }
        .file-name {
            flex: 1;
        }
        .file-meta {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-right: 20px;
        }
        .file-actions a {
            display: inline-block;
            padding: 5px 10px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin-left: 5px;
        }
        .file-actions a:hover {
            background-color: #2980b9;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 5px 10px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .back-link:hover {
            background-color: #1a252f;
        }
        .empty-message {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Logs Directory</h1>
        <a href="/admin/content/debug-logs.php" class="back-link">Back to Admin Debug Logs</a>
        
        <?php
        // Get all files in the directory
        $htmlFiles = glob(__DIR__ . '/*.html');
        $logFiles = glob(__DIR__ . '/*.log');
        $txtFiles = glob(__DIR__ . '/*.txt');
        
        // Combine and sort files by modification time (newest first)
        $allFiles = array_merge($htmlFiles, $logFiles, $txtFiles);
        usort($allFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        if (empty($allFiles)) {
            echo '<div class="empty-message">No debug files found.</div>';
        } else {
            echo '<ul class="file-list">';
            
            foreach ($allFiles as $file) {
                $fileName = basename($file);
                $fileSize = filesize($file);
                $fileDate = date('Y-m-d H:i:s', filemtime($file));
                $fileUrl = '?file=' . urlencode($fileName);
                
                echo '<li class="file-item">';
                echo '<div class="file-name">' . htmlspecialchars($fileName) . '</div>';
                echo '<div class="file-meta">';
                echo 'Size: ' . number_format($fileSize / 1024, 2) . ' KB | ';
                echo 'Modified: ' . $fileDate;
                echo '</div>';
                echo '<div class="file-actions">';
                echo '<a href="' . $fileUrl . '" target="_blank">View</a>';
                echo '</div>';
                echo '</li>';
            }
            
            echo '</ul>';
        }
        ?>
    </div>
</body>
</html>
