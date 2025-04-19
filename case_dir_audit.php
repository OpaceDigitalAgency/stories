<?php
/**
 * case_dir_audit.php
 * 
 * Lists all directories and files in a given path and flags any that do not match the expected PSR-4/PSR-0 capitalization.
 * Usage: php case_dir_audit.php /path/to/your/project/api/v1
 */

$root = $argv[1] ?? __DIR__;
$expected = [
    'Core',
    'Endpoints',
    'Middleware',
    'Utils',
    'Config', // Add other expected PSR-4/PSR-0 directories as needed
];

echo "Scanning: $root\n\n";

$dirContents = scandir($root);
foreach ($dirContents as $item) {
    if ($item === '.' || $item === '..') continue;
    if (is_dir($root . '/' . $item)) {
        if (!in_array($item, $expected, true)) {
            echo "Directory '$item' does not match expected PSR-4/PSR-0 capitalization.\n";
        } else {
            echo "Directory '$item' is correct.\n";
        }
    }
}

echo "\nAll files and directories:\n";
foreach ($dirContents as $item) {
    if ($item === '.' || $item === '..') continue;
    echo $item . (is_dir($root . '/' . $item) ? " [DIR]" : "") . "\n";
}