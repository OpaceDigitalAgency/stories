<?php
/**
 * Case Sensitivity Fix Interface
 * 
 * This file provides a web interface to check and fix case sensitivity issues
 * by enforcing proper directory structure and namespace declarations.
 */

require_once __DIR__ . '/api/v1/autoload.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');
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
        $baseDir = __DIR__ . '/api/v1';
        $expectedDirs = [
            'Core',
            'Middleware',
            'Endpoints',
            'Utils',
            'Config'
        ];
        
        $issues = [];
        
        // Check directory structure
        foreach ($expectedDirs as $dir) {
            $path = $baseDir . '/' . $dir;
            $lowercasePath = $baseDir . '/' . strtolower($dir);
            
            echo "<h3>$dir Directory</h3>";
            
            if (!is_dir($path)) {
                echo "<p class='error'>❌ Missing directory: $path</p>";
                $issues[] = [
                    'type' => 'directory',
                    'message' => "Missing directory: $path",
                    'fix' => function() use ($path) {
                        mkdir($path, 0755, true);
                    }
                ];
            } elseif (is_dir($lowercasePath) && $lowercasePath !== $path) {
                echo "<p class='error'>❌ Found lowercase directory: $lowercasePath</p>";
                $issues[] = [
                    'type' => 'directory',
                    'message' => "Lowercase directory: $lowercasePath",
                    'fix' => function() use ($lowercasePath, $path) {
                        rename($lowercasePath, $path);
                    }
                ];
            } else {
                echo "<p class='success'>✓ Directory structure correct</p>";
            }
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Namespace Declarations</h2>
        <?php
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                echo "<h3>" . basename($file->getPathname()) . "</h3>";
                
                try {
                    Autoloader::validateFile($file->getPathname());
                    echo "<p class='success'>✓ Namespace declaration correct</p>";
                } catch (\Exception $e) {
                    echo "<p class='error'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
                    $issues[] = [
                        'type' => 'namespace',
                        'file' => $file->getPathname(),
                        'message' => $e->getMessage()
                    ];
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
                if ($issue['type'] === 'directory' && isset($issue['fix'])) {
                    $issue['fix']();
                    echo "<span class='success'>✓ Fixed: " . htmlspecialchars($issue['message']) . "</span>";
                } elseif ($issue['type'] === 'namespace' && isset($issue['file'])) {
                    // For namespace issues, we need to parse and fix the file
                    $content = file_get_contents($issue['file']);
                    $newContent = preg_replace(
                        '/namespace\s+StoriesAPI\\\\([^;]+);/',
                        function($matches) use ($expectedDirs) {
                            $parts = explode('\\', $matches[1]);
                            $parts[0] = array_filter($expectedDirs, function($dir) use ($parts) {
                                return strcasecmp($dir, $parts[0]) === 0;
                            })[0] ?? $parts[0];
                            return 'namespace StoriesAPI\\' . implode('\\', $parts) . ';';
                        },
                        $content
                    );
                    file_put_contents($issue['file'], $newContent);
                    echo "<span class='success'>✓ Fixed namespace in: " . htmlspecialchars(basename($issue['file'])) . "</span>";
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