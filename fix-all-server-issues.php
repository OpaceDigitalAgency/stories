<?php
/**
 * Combined script to fix all server issues
 * This script should be run on the server to fix all the issues at once
 */

echo "=== Checking for duplicate files ===\n\n";
include 'check-duplicate-files.php';

echo "\n\n=== Fixing author-delete.php ===\n\n";
include 'fix-author-delete.php';

echo "\n\n=== Fixing contacts.php ===\n\n";
include 'fix-contacts.php';

echo "\n\nAll fixes complete!\n";
