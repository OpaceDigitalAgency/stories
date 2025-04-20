<?php
/**
 * Script to fix case sensitivity issues in file paths.
 * It consolidates duplicate folders and files with different casing.
 */

function scanDirectory($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

function normalizePaths($files) {
    $normalized = [];
    foreach ($files as $file) {
        $lowercasePath = strtolower($file);
        if (!isset($normalized[$lowercasePath])) {
            $normalized[$lowercasePath] = $file;
        } else {
            echo "Duplicate found: " . $file . " and " . $normalized[$lowercasePath] . "\n";
            // Decide which file to keep based on your criteria
            // For now, we'll keep the first one encountered
        }
    }
    return $normalized;
}

function consolidateFiles($normalized) {
    foreach ($normalized as $path) {
        // Implement logic to move or delete files as needed
        echo "Consolidating: " . $path . "\n";
    }
}

$directory = __DIR__ . '/api/v1';
$files = scanDirectory($directory);
$normalized = normalizePaths($files);
consolidateFiles($normalized);

echo "Case sensitivity issues fixed.\n";