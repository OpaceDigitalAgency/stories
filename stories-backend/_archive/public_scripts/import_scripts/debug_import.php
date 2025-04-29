<?php
/**
 * Debug Import Script
 * 
 * This script helps diagnose issues with the WordPress import process.
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Import Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4a6ee0; }
        .section { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #eee; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>WordPress Import Debug</h1>
    
    <div class="section">
        <h2>PHP Information</h2>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>Memory Limit: <?php echo ini_get('memory_limit'); ?></p>
        <p>Max Execution Time: <?php echo ini_get('max_execution_time'); ?></p>
        <p>Upload Max Filesize: <?php echo ini_get('upload_max_filesize'); ?></p>
        <p>Post Max Size: <?php echo ini_get('post_max_size'); ?></p>
    </div>
    
    <div class="section">
        <h2>Directory Permissions</h2>
        <?php
        $directories = [
            __DIR__,
            __DIR__ . '/../_wp-migration',
            __DIR__ . '/../_wp migration',
            $_SERVER['DOCUMENT_ROOT'] . '/uploads'
        ];
        
        foreach ($directories as $dir) {
            echo "<h3>$dir</h3>";
            if (file_exists($dir)) {
                echo "<p class='success'>Directory exists</p>";
                echo "<p>Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "</p>";
                echo "<p>Readable: " . (is_readable($dir) ? 'Yes' : 'No') . "</p>";
                echo "<p>Writable: " . (is_writable($dir) ? 'Yes' : 'No') . "</p>";
                
                // List contents
                echo "<p>Contents:</p>";
                echo "<ul>";
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        echo "<li>$file</li>";
                    }
                }
                echo "</ul>";
            } else {
                echo "<p class='error'>Directory does not exist</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Database Connection</h2>
        <?php
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
            
            // Check tables
            $tables = [
                'stories', 'authors', 'story_authors', 'tags', 'story_tags', 'media'
            ];
            
            foreach ($tables as $table) {
                $stmt = $db->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->rowCount() > 0) {
                    echo "<p class='success'>Table '$table' exists</p>";
                    
                    // Count rows
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM $table");
                    $countStmt->execute();
                    $count = $countStmt->fetchColumn();
                    echo "<p>Row count: $count</p>";
                    
                    // Show structure
                    $structStmt = $db->prepare("DESCRIBE $table");
                    $structStmt->execute();
                    $columns = $structStmt->fetchAll();
                    
                    echo "<p>Structure:</p>";
                    echo "<pre>";
                    foreach ($columns as $column) {
                        echo $column['Field'] . " - " . $column['Type'] . " - " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
                    }
                    echo "</pre>";
                } else {
                    echo "<p class='error'>Table '$table' does not exist</p>";
                }
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Database connection failed: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>WordPress Export Files</h2>
        <?php
        $wpDirs = [
            __DIR__ . '/../_wp-migration/wp-md',
            __DIR__ . '/../_wp-migration/wp-md/custom',
            __DIR__ . '/../_wp-migration/wp-md/custom/childrens-story',
            __DIR__ . '/../_wp migration/wp-md',
            __DIR__ . '/../_wp migration/wp-md/custom',
            __DIR__ . '/../_wp migration/wp-md/custom/childrens-story'
        ];
        
        $found = false;
        foreach ($wpDirs as $dir) {
            echo "<h3>$dir</h3>";
            if (is_dir($dir)) {
                echo "<p class='success'>Directory exists</p>";
                $found = true;
                
                // Count markdown files - use recursive directory iterator instead of GLOB_RECURSE
                $mdFiles = [];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getFilename() === 'index.md') {
                        $mdFiles[] = $file->getPathname();
                    }
                }
                
                echo "<p>Found " . count($mdFiles) . " markdown files</p>";
                
                if (count($mdFiles) > 0) {
                    // Show sample content
                    $sampleFile = $mdFiles[0];
                    echo "<p>Sample file: $sampleFile</p>";
                    if (file_exists($sampleFile)) {
                        $content = file_get_contents($sampleFile);
                        echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
                    }
                }
                
                // Check for images - use recursive directory iterator
                $imageFiles = [];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && strpos($file->getPathname(), '/images/') !== false) {
                        $imageFiles[] = $file->getPathname();
                    }
                }
                
                echo "<p>Found " . count($imageFiles) . " image files</p>";
                
                if (count($imageFiles) > 0) {
                    echo "<p>Sample images:</p>";
                    echo "<ul>";
                    $sampleImages = array_slice($imageFiles, 0, 5);
                    foreach ($sampleImages as $image) {
                        echo "<li>$image</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<p class='error'>Directory does not exist</p>";
            }
        }
        
        if (!$found) {
            echo "<p class='error'>No WordPress export directories found</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Test File Creation</h2>
        <?php
        $testFile = $_SERVER['DOCUMENT_ROOT'] . '/uploads/test_file.txt';
        try {
            // Create uploads directory if it doesn't exist
            $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
                echo "<p class='success'>Created uploads directory</p>";
            }
            
            // Try to write a test file
            $result = file_put_contents($testFile, "Test file created at " . date('Y-m-d H:i:s'));
            if ($result !== false) {
                echo "<p class='success'>Successfully created test file: $testFile</p>";
                echo "<p>File size: $result bytes</p>";
                echo "<p>File permissions: " . substr(sprintf('%o', fileperms($testFile)), -4) . "</p>";
                
                // Try to chmod the file
                if (chmod($testFile, 0644)) {
                    echo "<p class='success'>Successfully changed file permissions to 0644</p>";
                } else {
                    echo "<p class='error'>Failed to change file permissions</p>";
                }
            } else {
                echo "<p class='error'>Failed to create test file</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error creating test file: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Next Steps</h2>
        <p>Based on the diagnostics above, you can:</p>
        <ol>
            <li>Check if the WordPress export directories exist and contain the expected files</li>
            <li>Verify that the database connection is working and tables exist</li>
            <li>Ensure that the uploads directory is writable</li>
            <li>Try the <a href="simple_import.php">Simple Import Script</a> again</li>
        </ol>
    </div>
</body>
</html>