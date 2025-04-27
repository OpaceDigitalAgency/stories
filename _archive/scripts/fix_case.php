<?php
/**
 * Case Sensitivity Fix Interface
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// HTML header
header('Content-Type: text/html; charset=utf-8');

try {
    // Base directory for API
    $baseDir = __DIR__ . '/api/v1';
    
    // Expected directory structure
    $expectedDirs = [
        'Core' => true,
        'Middleware' => true,
        'Endpoints' => true,
        'Utils' => true,
        'Config' => true
    ];
    
    // HTML output
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Case Sensitivity Fix</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 1200px;
                margin: 20px auto;
                padding: 20px;
                line-height: 1.6;
            }
            .box {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            pre {
                background: #fff;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 0;
            }
            .button:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <h1>Case Sensitivity Fix</h1>
        
        <div class="box">
            <h2>Directory Structure</h2>
            <?php
            $issues = [];
            
            // Check directory structure
            foreach ($expectedDirs as $dir => $required) {
                $path = $baseDir . '/' . $dir;
                $lowercasePath = $baseDir . '/' . strtolower($dir);
                
                echo "<h3>$dir Directory</h3>";
                
                if (!is_dir($path)) {
                    if (is_dir($lowercasePath)) {
                        echo "<p class='error'>❌ Directory needs capitalization: $lowercasePath</p>";
                        $issues[] = [
                            'type' => 'directory',
                            'message' => "Directory needs capitalization: $lowercasePath → $path",
                            'fix' => function() use ($lowercasePath, $path) {
                                if (!is_dir(dirname($path))) {
                                    mkdir(dirname($path), 0755, true);
                                }
                                rename($lowercasePath, $path);
                            }
                        ];
                    } else {
                        echo "<p class='error'>❌ Missing directory: $path</p>";
                        $issues[] = [
                            'type' => 'directory',
                            'message' => "Missing directory: $path",
                            'fix' => function() use ($path) {
                                if (!is_dir(dirname($path))) {
                                    mkdir(dirname($path), 0755, true);
                                }
                                mkdir($path, 0755);
                            }
                        ];
                    }
                } else {
                    echo "<p class='success'>✓ Directory structure correct</p>";
                }
            }
            ?>
        </div>
        
        <div class="box">
            <h2>PHP Files</h2>
            <?php
            if (is_dir($baseDir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($baseDir)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $relativePath = str_replace($baseDir . '/', '', $file->getPathname());
                        echo "<h3>$relativePath</h3>";
                        
                        // Check namespace declaration
                        $content = file_get_contents($file->getPathname());
                        if (preg_match('/namespace\s+StoriesAPI\\\\([^;]+);/', $content, $matches)) {
                            $namespacePart = $matches[1];
                            $parts = explode('\\', $namespacePart);
                            
                            if (isset($parts[0])) {
                                $topNamespace = $parts[0];
                                $expectedDir = null;
                                
                                // Find case-sensitive match
                                foreach (array_keys($expectedDirs) as $dir) {
                                    if (strcasecmp($topNamespace, $dir) === 0) {
                                        $expectedDir = $dir;
                                        break;
                                    }
                                }
                                
                                if ($expectedDir && $topNamespace !== $expectedDir) {
                                    echo "<p class='error'>❌ Incorrect namespace: StoriesAPI\\$topNamespace (should be StoriesAPI\\$expectedDir)</p>";
                                    $issues[] = [
                                        'type' => 'namespace',
                                        'file' => $file->getPathname(),
                                        'message' => "Fix namespace in $relativePath: StoriesAPI\\$topNamespace → StoriesAPI\\$expectedDir",
                                        'fix' => function() use ($file, $content, $topNamespace, $expectedDir) {
                                            $newContent = str_replace(
                                                "namespace StoriesAPI\\$topNamespace;",
                                                "namespace StoriesAPI\\$expectedDir;",
                                                $content
                                            );
                                            file_put_contents($file->getPathname(), $newContent);
                                        }
                                    ];
                                } else {
                                    echo "<p class='success'>✓ Namespace correct</p>";
                                }
                            }
                        }
                    }
                }
            }
            ?>
        </div>
        
        <?php if (!empty($issues)): ?>
        <div class="box">
            <h2>Issues Found</h2>
            <form method="post" action="">
                <?php foreach ($issues as $i => $issue): ?>
                    <div>
                        <input type="checkbox" name="fixes[]" value="<?php echo $i; ?>" id="fix_<?php echo $i; ?>">
                        <label for="fix_<?php echo $i; ?>"><?php echo htmlspecialchars($issue['message']); ?></label>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="button">Fix Selected Issues</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php
        // Handle fixes
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fixes'])) {
            echo "<div class='box'>";
            echo "<h2>Applying Fixes</h2>";
            
            foreach ($_POST['fixes'] as $index) {
                $issue = $issues[$index];
                echo "<p>";
                
                try {
                    if (isset($issue['fix'])) {
                        $issue['fix']();
                        echo "<span class='success'>✓ Fixed: " . htmlspecialchars($issue['message']) . "</span>";
                    }
                } catch (\Exception $e) {
                    echo "<span class='error'>❌ Failed to fix: " . htmlspecialchars($e->getMessage()) . "</span>";
                }
                
                echo "</p>";
            }
            
            echo "<p><a href='' class='button'>Refresh Page</a></p>";
            echo "</div>";
        }
        ?>
        
        <div class="box">
            <h2>Next Steps</h2>
            <ol>
                <li>Fix any remaining issues shown above</li>
                <li>Update your code to use proper capitalization</li>
                <li>Test your application thoroughly</li>
                <li>Deploy the changes to production</li>
            </ol>
        </div>
    </body>
    </html>
    <?php
} catch (\Exception $e) {
    // Log error
    error_log("Error in fix_case.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Show error page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                line-height: 1.6;
            }
            .error {
                background: #fee;
                color: #c00;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
            }
            pre {
                background: #fff;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <h1>Error</h1>
        <div class="error">
            <h2>An error occurred:</h2>
            <pre><?php echo htmlspecialchars($e->getMessage()); ?></pre>
            <?php if (ini_get('display_errors')): ?>
                <h3>Stack Trace:</h3>
                <pre><?php echo htmlspecialchars($e->getTraceAsString()); ?></pre>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>