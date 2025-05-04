<?php
/**
 * Direct fix for author-delete.php on the server
 * 
 * This script directly modifies the author-delete.php file on the server
 * to fix the "headers already sent" error by ensuring session_start() is
 * called before any output.
 */

// Define the server path to the author-delete.php file
$filePath = '/home/stories/api.storiesfromtheweb.org/admin/content/author-delete.php';

// Check if the file exists
if (!file_exists($filePath)) {
    echo "Error: File not found at $filePath\n";
    exit(1);
}

// Read the file content
$content = file_get_contents($filePath);
if ($content === false) {
    echo "Error: Could not read file at $filePath\n";
    exit(1);
}

// Create a backup of the original file
$backupPath = $filePath . '.bak.' . date('Y-m-d-H-i-s');
if (file_put_contents($backupPath, $content) === false) {
    echo "Error: Could not create backup at $backupPath\n";
    exit(1);
}

echo "Created backup at $backupPath\n";

// Replace the session_start() conditional with a direct call at the beginning
$pattern = '/^<\?php.*?session_start\(\);.*?}/s';
$replacement = '<?php
/**
 * Author Delete Page
 * 
 * This page displays a confirmation form for deleting an author.
 * It handles the case where an author has associated stories by providing
 * options to either delete all stories or reassign them to another author.
 */

// Start session before any output
session_start();';

$newContent = preg_replace($pattern, $replacement, $content);

// Write the modified content back to the file
if (file_put_contents($filePath, $newContent) === false) {
    echo "Error: Could not write to $filePath\n";
    exit(1);
}

echo "Successfully fixed author-delete.php at $filePath\n";
