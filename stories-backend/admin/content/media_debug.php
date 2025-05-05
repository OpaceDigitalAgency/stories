<?php
/**
 * Media Debug Page
 * 
 * This is a debug version of the media.php page to help identify the issue.
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any errors
ob_start();

try {
    echo "<h1>Media Debug Page</h1>";
    echo "<p>This page will help identify the issue with the media.php page.</p>";
    
    echo "<h2>Step 1: Check Auth Include</h2>";
    try {
        echo "<p>Attempting to include auth-check.php...</p>";
        require_once '../includes/auth-check.php';
        echo "<p class='success'>✅ Successfully included auth-check.php</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error including auth-check.php: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 2: Check Database Connection</h2>";
    try {
        echo "<p>Attempting to include db-connect.php...</p>";
        require_once '../includes/db-connect.php';
        echo "<p class='success'>✅ Successfully included db-connect.php</p>";
        
        // Test database connection
        if (isset($db) && $db instanceof PDO) {
            echo "<p class='success'>✅ Database connection successful</p>";
            
            // Check if media table exists
            $stmt = $db->query("SHOW TABLES LIKE 'media'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ Media table exists</p>";
                
                // Check media table structure
                $stmt = $db->query("DESCRIBE media");
                $columns = [];
                while ($row = $stmt->fetch()) {
                    $columns[] = $row['Field'];
                }
                
                echo "<p>Media table columns: " . implode(', ', $columns) . "</p>";
                
                // Check for required columns
                $requiredColumns = [
                    'id', 'filename', 'file_path', 'file_type', 'file_size', 'alt_text',
                    'thumbnail_url', 'small_url', 'medium_url', 'large_url', 'width', 'height',
                    'created_at', 'updated_at'
                ];
                
                $missingColumns = array_diff($requiredColumns, $columns);
                
                if (empty($missingColumns)) {
                    echo "<p class='success'>✅ Media table has all required columns</p>";
                } else {
                    echo "<p class='error'>❌ Media table is missing columns: " . implode(', ', $missingColumns) . "</p>";
                }
            } else {
                echo "<p class='error'>❌ Media table does not exist</p>";
            }
        } else {
            echo "<p class='error'>❌ Database connection failed</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error with database connection: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 3: Check Image Optimizer</h2>";
    try {
        $optimizerPath = __DIR__ . '/../../includes/image_optimizer.php';
        echo "<p>Looking for image_optimizer.php at: " . $optimizerPath . "</p>";
        
        if (file_exists($optimizerPath)) {
            echo "<p class='success'>✅ image_optimizer.php exists</p>";
            
            // Check if we can include it
            try {
                require_once $optimizerPath;
                echo "<p class='success'>✅ Successfully included image_optimizer.php</p>";
                
                // Check if createImageVariants function exists
                if (function_exists('createImageVariants')) {
                    echo "<p class='success'>✅ createImageVariants function exists</p>";
                } else {
                    echo "<p class='error'>❌ createImageVariants function does not exist</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error including image_optimizer.php: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='error'>❌ image_optimizer.php does not exist</p>";
        }
        
        // Check for image_config.php
        $configPath = __DIR__ . '/../../includes/image_config.php';
        echo "<p>Looking for image_config.php at: " . $configPath . "</p>";
        
        if (file_exists($configPath)) {
            echo "<p class='success'>✅ image_config.php exists</p>";
            
            // Check if we can include it
            try {
                require_once $configPath;
                echo "<p class='success'>✅ Successfully included image_config.php</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error including image_config.php: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='error'>❌ image_config.php does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking image optimizer: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 4: Check Upload Directory</h2>";
    try {
        $uploadDir = __DIR__ . '/../../uploads/';
        echo "<p>Checking upload directory at: " . $uploadDir . "</p>";
        
        if (file_exists($uploadDir)) {
            echo "<p class='success'>✅ Upload directory exists</p>";
            
            if (is_writable($uploadDir)) {
                echo "<p class='success'>✅ Upload directory is writable</p>";
            } else {
                echo "<p class='error'>❌ Upload directory is not writable</p>";
            }
        } else {
            echo "<p class='error'>❌ Upload directory does not exist</p>";
        }
        
        // Check optimized directory
        $optimizedDir = __DIR__ . '/../../uploads/optimized/';
        echo "<p>Checking optimized directory at: " . $optimizedDir . "</p>";
        
        if (file_exists($optimizedDir)) {
            echo "<p class='success'>✅ Optimized directory exists</p>";
            
            if (is_writable($optimizedDir)) {
                echo "<p class='success'>✅ Optimized directory is writable</p>";
            } else {
                echo "<p class='error'>❌ Optimized directory is not writable</p>";
            }
        } else {
            echo "<p class='error'>❌ Optimized directory does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking upload directory: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 5: Check PHP Extensions</h2>";
    try {
        echo "<p>Checking required PHP extensions...</p>";
        
        $requiredExtensions = ['gd', 'imagick', 'pdo_mysql'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<p class='success'>✅ $ext extension is loaded</p>";
            } else {
                echo "<p class='error'>❌ $ext extension is not loaded</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking PHP extensions: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 6: Check Header and Footer</h2>";
    try {
        echo "<p>Checking header.php and footer.php...</p>";
        
        $headerPath = __DIR__ . '/../includes/header.php';
        $footerPath = __DIR__ . '/../includes/footer.php';
        
        if (file_exists($headerPath)) {
            echo "<p class='success'>✅ header.php exists</p>";
        } else {
            echo "<p class='error'>❌ header.php does not exist</p>";
        }
        
        if (file_exists($footerPath)) {
            echo "<p class='success'>✅ footer.php exists</p>";
        } else {
            echo "<p class='error'>❌ footer.php does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking header and footer: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Conclusion</h2>";
    echo "<p>Check the above results to identify the issue with the media.php page.</p>";
    echo "<p>Once you've fixed the issue, try accessing the <a href='media.php'>media.php</a> page again.</p>";
    
} catch (Exception $e) {
    echo "<h1>Fatal Error</h1>";
    echo "<p class='error'>❌ " . $e->getMessage() . "</p>";
}

// Get the output buffer
$output = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Debug Page</title>
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
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #F44336;
            font-weight: bold;
        }
        .warning {
            color: #FF9800;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php echo $output; ?>
    </div>
</body>
</html>
