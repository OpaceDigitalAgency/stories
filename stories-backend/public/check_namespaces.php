<?php
/**
 * Namespace Check Tool
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');

// Base directory for API
$baseDir = dirname(__DIR__) . '/api/v1';

// Expected namespaces
$expectedNamespaces = [
    'Core',
    'Middleware',
    'Endpoints',
    'Utils',
    'Config'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Namespace Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        pre {
            background: #fff;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Namespace Check</h1>
    
    <div class="box">
        <h2>PHP Files</h2>
        <?php
        $issues = [];
        
        // Recursively get all PHP files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($baseDir . '/', '', $file->getPathname());
                echo "<h3>$relativePath</h3>";
                
                // Get the expected namespace based on directory
                $dir = explode('/', $relativePath)[0];
                $expectedDir = null;
                foreach ($expectedNamespaces as $namespace) {
                    if (strcasecmp($dir, $namespace) === 0) {
                        $expectedDir = $namespace;
                        break;
                    }
                }
                
                if ($expectedDir) {
                    // Check namespace declaration
                    $content = file_get_contents($file->getPathname());
                    if (preg_match('/namespace\s+StoriesAPI\\\\([^;\\\\]+)/', $content, $matches)) {
                        $declaredNamespace = $matches[1];
                        if ($declaredNamespace !== $expectedDir) {
                            echo "<p class='error'>Incorrect namespace: StoriesAPI\\$declaredNamespace (should be StoriesAPI\\$expectedDir)</p>";
                            if (isset($_POST['fix']) && $_POST['fix'] === 'true') {
                                $newContent = preg_replace(
                                    '/namespace\s+StoriesAPI\\\\'.$declaredNamespace.'/',
                                    'namespace StoriesAPI\\'.$expectedDir,
                                    $content
                                );
                                file_put_contents($file->getPathname(), $newContent);
                                echo "<p class='success'>Fixed: Updated to StoriesAPI\\$expectedDir</p>";
                            } else {
                                $issues[] = $relativePath;
                            }
                        } else {
                            echo "<p class='success'>Correct namespace: StoriesAPI\\$declaredNamespace</p>";
                        }
                    } else {
                        echo "<p class='error'>No namespace declaration found</p>";
                    }
                }
            }
        }
        ?>
    </div>
    
    <?php if (!empty($issues) && !isset($_POST['fix'])): ?>
    <div class="box">
        <h2>Found Issues</h2>
        <p>The following files have incorrect namespaces:</p>
        <ul>
            <?php foreach ($issues as $file): ?>
                <li><?php echo htmlspecialchars($file); ?></li>
            <?php endforeach; ?>
        </ul>
        <form method="post">
            <input type="hidden" name="fix" value="true">
            <button type="submit">Fix Namespace Issues</button>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if (empty($issues)): ?>
    <div class="box">
        <h2>All Good!</h2>
        <p class="success">All namespaces are correctly capitalized.</p>
    </div>
    <?php endif; ?>
    
    <div class="box">
        <h2>Next Steps</h2>
        <ol>
            <li>Review any namespace changes</li>
            <li>Test affected classes</li>
            <li>Clear any caches</li>
            <li>Deploy changes to production</li>
        </ol>
    </div>
</body>
</html>