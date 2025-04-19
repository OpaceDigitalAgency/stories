<?php
/**
 * auto_case_fix_global.php
 * 
 * Recursively renames directories/files and updates code references for PSR-4/PSR-0 capitalization across the entire project.
 * Usage: php auto_case_fix_global.php [target_dir]
 * Place this script in your project root and run it from there.
 */

$target = $argv[1] ?? __DIR__;
$fixes = [
    'core' => 'Core',
    'endpoints' => 'Endpoints',
    'utils' => 'Utils',
    'middleware' => 'Middleware',
    // Add more as needed
];

$changed = [];

// 1. Recursively rename directories/files
function renameDirsFiles($dir, $fixes, &$changed) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            foreach ($fixes as $wrong => $correct) {
                if (strtolower($item) === strtolower($wrong) && $item !== $correct) {
                    $newPath = $dir . '/' . $correct;
                    if (!is_dir($newPath)) {
                        if (rename($path, $newPath)) {
                            $changed[] = "Renamed directory: $path → $newPath";
                            $path = $newPath;
                        } else {
                            $changed[] = "Failed to rename directory: $path → $newPath";
                        }
                    }
                }
            }
            renameDirsFiles($path, $fixes, $changed);
        } else {
            foreach ($fixes as $wrong => $correct) {
                if (strtolower($item) === strtolower($wrong . '.php') && $item !== $correct . '.php') {
                    $newPath = $dir . '/' . $correct . '.php';
                    if (!is_file($newPath)) {
                        if (rename($path, $newPath)) {
                            $changed[] = "Renamed file: $path → $newPath";
                        } else {
                            $changed[] = "Failed to rename file: $path → $newPath";
                        }
                    }
                }
            }
        }
    }
}

// 2. Recursively update code references in all PHP files
function updateReferencesGlobal($dir, $fixes, &$changed) {
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

renameDirsFiles($target, $fixes, $changed);
updateReferencesGlobal($target, $fixes, $changed);

echo "<pre>";
echo "Target directory: $target\n";
if (empty($changed)) {
    echo "No changes made. All directories/files and code references are already correct.\n";
} else {
    echo "Changes made:\n";
    foreach ($changed as $c) echo $c . "\n";
}
echo "</pre>";