<?php
/**
 * Media Fix Tool
 * 
 * This tool diagnoses and fixes issues with the media.php page.
 */

// Start session
session_start();

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
    echo "<p>Database connection successful!</p>";
} catch (PDOException $e) {
    echo "<p>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check for required directories
$directories = [
    'uploads' => __DIR__ . '/../../uploads',
    'optimized' => __DIR__ . '/../../uploads/optimized',
];

echo "<h2>Directory Check</h2>";
echo "<ul>";
foreach ($directories as $name => $path) {
    if (!file_exists($path)) {
        echo "<li>Creating $name directory at: $path</li>";
        mkdir($path, 0755, true);
    } else {
        echo "<li>$name directory exists at: $path</li>";
    }
    
    // Check if directory is writable
    if (is_writable($path)) {
        echo "<li class='success'>$name directory is writable</li>";
    } else {
        echo "<li class='error'>$name directory is not writable</li>";
        chmod($path, 0755);
        echo "<li>Attempted to fix permissions on $name directory</li>";
    }
}
echo "</ul>";

// Check for required files
$files = [
    'image_optimizer.php' => __DIR__ . '/../../includes/image_optimizer.php',
    'image_config.php' => __DIR__ . '/../../includes/image_config.php',
];

echo "<h2>File Check</h2>";
echo "<ul>";
foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<li class='success'>$name exists at: $path</li>";
    } else {
        echo "<li class='error'>$name does not exist at: $path</li>";
    }
}
echo "</ul>";

// Check for required PHP extensions
$extensions = [
    'gd' => extension_loaded('gd'),
    'imagick' => extension_loaded('imagick'),
];

echo "<h2>PHP Extension Check</h2>";
echo "<ul>";
foreach ($extensions as $name => $loaded) {
    if ($loaded) {
        echo "<li class='success'>$name extension is loaded</li>";
    } else {
        echo "<li class='warning'>$name extension is not loaded (not required but recommended)</li>";
    }
}
echo "</ul>";

// Check media table structure
echo "<h2>Database Table Check</h2>";
try {
    // Check if media table exists
    $stmt = $db->query("SHOW TABLES LIKE 'media'");
    if ($stmt->rowCount() === 0) {
        echo "<p class='error'>media table does not exist</p>";
        
        // Create media table
        $db->exec("CREATE TABLE IF NOT EXISTS media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            alt_text VARCHAR(255),
            thumbnail_url VARCHAR(255),
            small_url VARCHAR(255),
            medium_url VARCHAR(255),
            large_url VARCHAR(255),
            width INT,
            height INT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        
        echo "<p class='success'>Created media table</p>";
    } else {
        echo "<p class='success'>media table exists</p>";
        
        // Check if media table has all required columns
        $columns = [];
        $stmt = $db->query("DESCRIBE media");
        while ($row = $stmt->fetch()) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = [
            'id', 'filename', 'file_path', 'file_type', 'file_size', 'alt_text',
            'thumbnail_url', 'small_url', 'medium_url', 'large_url', 'width', 'height',
            'created_at', 'updated_at'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "<p class='error'>media table is missing columns: " . implode(', ', $missingColumns) . "</p>";
            
            // Add missing columns
            foreach ($missingColumns as $column) {
                try {
                    if ($column === 'width' || $column === 'height') {
                        $db->exec("ALTER TABLE media ADD COLUMN $column INT");
                    } elseif ($column === 'file_size') {
                        $db->exec("ALTER TABLE media ADD COLUMN $column INT NOT NULL DEFAULT 0");
                    } elseif ($column === 'created_at' || $column === 'updated_at') {
                        $db->exec("ALTER TABLE media ADD COLUMN $column DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                    } else {
                        $db->exec("ALTER TABLE media ADD COLUMN $column VARCHAR(255)");
                    }
                    echo "<p class='success'>Added column $column to media table</p>";
                } catch (Exception $e) {
                    echo "<p class='error'>Error adding column $column: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p class='success'>media table has all required columns</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking media table: " . $e->getMessage() . "</p>";
}

// Test image optimization
echo "<h2>Image Optimization Test</h2>";

// Create a test image
$testImage = __DIR__ . '/../../uploads/test_image.png';
if (!file_exists($testImage)) {
    // Create a simple test image
    $image = imagecreatetruecolor(100, 100);
    $background = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    imagefilledrectangle($image, 0, 0, 100, 100, $background);
    imagestring($image, 5, 10, 40, 'Test Image', $text_color);
    imagepng($image, $testImage);
    imagedestroy($image);
    
    echo "<p>Created test image at: $testImage</p>";
}

// Try to optimize the test image
$optimizedDir = __DIR__ . '/../../uploads/optimized';
if (!is_dir($optimizedDir)) {
    mkdir($optimizedDir, 0755, true);
}

// Include image optimizer
require_once __DIR__ . '/../../includes/image_optimizer.php';

// Test optimization
try {
    $variants = createImageVariants($testImage, $optimizedDir);
    
    if ($variants) {
        echo "<p class='success'>Successfully created image variants</p>";
        echo "<pre>";
        print_r($variants);
        echo "</pre>";
    } else {
        echo "<p class='error'>Failed to create image variants</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error optimizing test image: " . $e->getMessage() . "</p>";
}

// Add a link to the media page
echo "<h2>Actions</h2>";
echo "<p><a href='content/media.php' class='btn'>Go to Media Page</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Media Fix Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .btn { 
            display: inline-block; 
            padding: 10px 15px; 
            background: #4361ee; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h1>Media Fix Tool</h1>
    <p>This tool diagnoses and fixes issues with the media.php page.</p>
</body>
</html>
