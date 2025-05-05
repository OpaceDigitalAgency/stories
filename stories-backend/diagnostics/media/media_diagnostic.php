<?php
/**
 * Media Diagnostic
 * 
 * This tool diagnoses and fixes issues with media uploads and image optimization.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common functions
require_once __DIR__ . '/../includes/common.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Connect to database
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $config['user'], $config['password'], $options);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Check for required directories
$directories = [
    'uploads' => __DIR__ . '/../../../uploads',
    'optimized' => __DIR__ . '/../../../uploads/optimized',
];

$directoryResults = [];
foreach ($directories as $name => $path) {
    $directoryResults[$name] = checkDirectory($path);
}

// Check for required PHP extensions
$extensions = [
    'gd' => extension_loaded('gd'),
    'imagick' => extension_loaded('imagick'),
];

// Check media table structure
$mediaTableResult = [
    'exists' => false,
    'columns' => [],
    'missing_columns' => [],
    'error' => null
];

if ($dbConnected) {
    try {
        // Check if media table exists
        $stmt = $db->query("SHOW TABLES LIKE 'media'");
        if ($stmt->rowCount() === 0) {
            $mediaTableResult['error'] = 'media table does not exist';
        } else {
            $mediaTableResult['exists'] = true;
            
            // Check if media table has all required columns
            $columns = [];
            $stmt = $db->query("DESCRIBE media");
            while ($row = $stmt->fetch()) {
                $columns[] = $row['Field'];
            }
            
            $mediaTableResult['columns'] = $columns;
            
            $requiredColumns = [
                'id', 'filename', 'file_path', 'file_type', 'file_size', 'alt_text',
                'thumbnail_url', 'small_url', 'medium_url', 'large_url', 'width', 'height',
                'created_at', 'updated_at'
            ];
            
            $mediaTableResult['missing_columns'] = array_diff($requiredColumns, $columns);
        }
    } catch (PDOException $e) {
        $mediaTableResult['error'] = $e->getMessage();
    }
}

// Test image optimization
$testImageResult = [
    'success' => false,
    'error' => null,
    'variants' => []
];

// Create a test image
$testImage = __DIR__ . '/../../../uploads/test_image.png';
if (!file_exists($testImage)) {
    // Create a simple test image
    $image = imagecreatetruecolor(100, 100);
    $background = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    imagefilledrectangle($image, 0, 0, 100, 100, $background);
    imagestring($image, 5, 10, 40, 'Test Image', $text_color);
    imagepng($image, $testImage);
    imagedestroy($image);
}

// Try to optimize the test image
$optimizedDir = __DIR__ . '/../../../uploads/optimized';
if (!is_dir($optimizedDir)) {
    mkdir($optimizedDir, 0755, true);
}

// Include image optimizer if it exists
$imageOptimizerPath = __DIR__ . '/../../../includes/image_optimizer.php';
if (file_exists($imageOptimizerPath)) {
    require_once $imageOptimizerPath;
    
    // Test optimization
    try {
        if (function_exists('createImageVariants')) {
            $variants = createImageVariants($testImage, $optimizedDir);
            
            if ($variants) {
                $testImageResult['success'] = true;
                $testImageResult['variants'] = $variants;
            } else {
                $testImageResult['error'] = 'Failed to create image variants';
            }
        } else {
            $testImageResult['error'] = 'createImageVariants function not found';
        }
    } catch (Exception $e) {
        $testImageResult['error'] = $e->getMessage();
    }
} else {
    $testImageResult['error'] = 'Image optimizer file not found';
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Media Diagnostic</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        .warning {
            color: #FF9800;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Media Diagnostic</h1>
        <p class='lead'>This tool diagnoses and fixes issues with media uploads and image optimization.</p>";

// Display PHP information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>PHP Information</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";

echo "<h5>Required Extensions:</h5>";
echo "<ul>";
foreach ($extensions as $ext => $loaded) {
    echo "<li>" . $ext . ": " . ($loaded ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</li>";
}
echo "</ul>";

echo "</div>";
echo "</div>";

// Display directory information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Directory Permissions</h2>";
echo "</div>";
echo "<div class='card-body'>";

foreach ($directoryResults as $name => $result) {
    echo "<h5>" . ucfirst($name) . " Directory</h5>";
    echo "<p><strong>Path:</strong> " . htmlspecialchars($result['path']) . "</p>";
    
    if ($result['exists']) {
        echo "<p><strong>Status:</strong> <span class='success'>Exists</span></p>";
    } else {
        echo "<p><strong>Status:</strong> <span class='error'>Does not exist</span></p>";
    }
    
    if ($result['created']) {
        echo "<p><strong>Created:</strong> <span class='success'>Yes</span></p>";
    }
    
    if ($result['exists']) {
        echo "<p><strong>Writable:</strong> " . ($result['writable'] ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    }
    
    if ($result['error']) {
        echo "<p><strong>Error:</strong> <span class='error'>" . htmlspecialchars($result['error']) . "</span></p>";
    }
}

echo "</div>";
echo "</div>";

// Display database information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Database Connection</h2>";
echo "</div>";
echo "<div class='card-body'>";

if ($dbConnected) {
    echo "<p><strong>Status:</strong> <span class='success'>Connected</span></p>";
} else {
    echo "<p><strong>Status:</strong> <span class='error'>Not connected</span></p>";
    
    if (isset($dbError)) {
        echo "<p><strong>Error:</strong> <span class='error'>" . htmlspecialchars($dbError) . "</span></p>";
    }
}

echo "</div>";
echo "</div>";

// Display media table information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Media Table</h2>";
echo "</div>";
echo "<div class='card-body'>";

if ($mediaTableResult['exists']) {
    echo "<p><strong>Status:</strong> <span class='success'>Exists</span></p>";
    
    echo "<h5>Columns:</h5>";
    echo "<ul>";
    foreach ($mediaTableResult['columns'] as $column) {
        echo "<li>" . htmlspecialchars($column) . "</li>";
    }
    echo "</ul>";
    
    if (!empty($mediaTableResult['missing_columns'])) {
        echo "<h5>Missing Columns:</h5>";
        echo "<ul class='error'>";
        foreach ($mediaTableResult['missing_columns'] as $column) {
            echo "<li>" . htmlspecialchars($column) . "</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='fix_media_table.php' class='btn btn-warning'>Fix Media Table</a></p>";
    }
} else {
    echo "<p><strong>Status:</strong> <span class='error'>Does not exist</span></p>";
    
    if ($mediaTableResult['error']) {
        echo "<p><strong>Error:</strong> <span class='error'>" . htmlspecialchars($mediaTableResult['error']) . "</span></p>";
    }
    
    echo "<p><a href='create_media_table.php' class='btn btn-warning'>Create Media Table</a></p>";
}

echo "</div>";
echo "</div>";

// Display image optimization information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Image Optimization Test</h2>";
echo "</div>";
echo "<div class='card-body'>";

if ($testImageResult['success']) {
    echo "<p><strong>Status:</strong> <span class='success'>Working</span></p>";
    
    echo "<h5>Image Variants:</h5>";
    echo "<pre>" . htmlspecialchars(print_r($testImageResult['variants'], true)) . "</pre>";
} else {
    echo "<p><strong>Status:</strong> <span class='error'>Not working</span></p>";
    
    if ($testImageResult['error']) {
        echo "<p><strong>Error:</strong> <span class='error'>" . htmlspecialchars($testImageResult['error']) . "</span></p>";
    }
    
    echo "<p><a href='fix_image_optimizer.php' class='btn btn-warning'>Fix Image Optimizer</a></p>";
}

echo "</div>";
echo "</div>";

// Display actions
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Actions</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<a href='../../admin/content/media.php' class='btn btn-primary me-2'>";
echo "<i class='fas fa-images'></i> Go to Media Page";
echo "</a>";

echo "<a href='fix_media.php' class='btn btn-warning me-2'>";
echo "<i class='fas fa-wrench'></i> Fix Media Issues";
echo "</a>";

echo "</div>";
echo "</div>";

// HTML footer
echo "
        <div class='mt-4'>
            <a href='/diagnostic-dashboard.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left'></i> Back to Diagnostic Dashboard
            </a>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
