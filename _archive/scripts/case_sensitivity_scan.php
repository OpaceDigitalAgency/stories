<?php
/**
 * case_sensitivity_scan.php
 * 
 * Recursively scans PHP files for class/namespace references and checks for case mismatches
 * between code references and actual file/directory names (PSR-4/PSR-0 style).
 * 
 * Usage: php case_sensitivity_scan.php [project_root]
 */

$root = $argv[1] ?? __DIR__;
$errors = [];

function scanPhpFiles($dir) {
    $files = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

function extractReferences($file) {
    $refs = [];
    $content = file_get_contents($file);
    // Match namespace, use, and new statements
    preg_match_all('/(?:namespace|use|new)\s+([A-Za-z0-9_\\\\]+)/', $content, $matches);
    foreach ($matches[1] as $ref) {
        $refs[] = trim($ref, '\\');
    }
    return $refs;
}

function psr4Path($ref, $root) {
    // Convert namespace to path (PSR-4)
    $parts = explode('\\', $ref);
    return $root . '/' . implode('/', $parts) . '.php';
}

function checkCase($expected, $actual) {
    return strcmp($expected, $actual) === 0;
}

$phpFiles = scanPhpFiles($root);
$checked = [];
foreach ($phpFiles as $phpFile) {
    $refs = extractReferences($phpFile);
    foreach ($refs as $ref) {
        $path = psr4Path($ref, $root);
        $dir = dirname($path);
        $file = basename($path);

        // Check directory path case
        $parts = explode('/', trim(str_replace($root, '', $dir), '/'));
        $current = $root;
        foreach ($parts as $part) {
            if (!is_dir($current)) break;
            $found = false;
            foreach (scandir($current) as $item) {
                if (strtolower($item) === strtolower($part)) {
                    if (!checkCase($item, $part)) {
                        $errors[] = "Directory case mismatch: expected '$part', found '$item' in $current";
                    }
                    $current .= '/' . $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) break;
        }

        // Check file case
        if (is_dir($current)) {
            foreach (scandir($current) as $item) {
                if (strtolower($item) === strtolower($file)) {
                    if (!checkCase($item, $file)) {
                        $errors[] = "File case mismatch: expected '$file', found '$item' in $current";
                    }
                    break;
                }
            }
        }
    }
}

if (empty($errors)) {
    echo "No case mismatches found. All class references match file/directory case.\n";
} else {
    echo "Case mismatches found:\n";
    foreach ($errors as $err) {
        echo $err . "\n";
    }
    echo "\nTotal mismatches: " . count($errors) . "\n";
}