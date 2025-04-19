<?php
/**
 * auto_case_fix.php (web-accessible version)
 * 
 * Renames directories/files in /api/v1/ to PSR-4/PSR-0 capitalization and updates code references.
 * Usage: Access via browser or run via CLI. Optionally specify ?target=/path/to/dir
 */

$target = $_GET['target'] ?? ($_SERVER['argv'][1] ?? dirname(__DIR__) . '/api/v1');
$fixes = [
    'core' => 'Core',
    'endpoints' => 'Endpoints',
    'utils' => 'Utils',
    'middleware' => 'Middleware',
    // Add more as needed
];

$base = $target;
$changed = [];

// 1. Rename directories/files
foreach ($fixes as $wrong => $correct) {
    $wrongPath = $base . '/' . $wrong;
    $correctPath = $base . '/' . $correct;
    if (is_dir($wrongPath) && !is_dir($correctPath)) {
        if (rename($wrongPath, $correctPath)) {
            $changed[] = "Renamed directory: $wrong → $correct";
        } else {
            $changed[] = "Failed to rename directory: $wrong → $correct";
        }
    }
    // Optionally, handle files (not just directories)
    if (is_file($wrongPath . '.php') && !is_file($correctPath . '.php')) {
        if (rename($wrongPath . '.php', $correctPath . '.php')) {
            $changed[] = "Renamed file: $wrong.php → $correct.php";
        } else {
            $changed[] = "Failed to rename file: $wrong.php → $correct.php";
        }
    }
}

// 2. Recursively update code references
function updateReferences($dir, $fixes, &$changed) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (strtolower($file->getExtension()) !== 'php') continue;
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;
        foreach ($fixes as $wrong => $correct) {
            // Replace in namespaces, use, and new statements
            $content = preg_replace("/(namespace|use|new)\s+([^;]*?)\b$wrong\b/i", "$1 $2$correct", $content);
            // Replace in string references (e.g., 'core/', 'endpoints/')
            $content = str_replace("$wrong/", "$correct/", $content);
        }
        if ($content !== $original) {
            file_put_contents($path, $content);
            $changed[] = "Updated references in: $path";
        }
    }
}

updateReferences($base, $fixes, $changed);

echo "<pre>";
echo "Target directory: $base\n";
if (empty($changed)) {
    echo "No changes made. All directories/files and code references are already correct.\n";
} else {
    echo "Changes made:\n";
    foreach ($changed as $c) echo $c . "\n";
}
echo "</pre>";