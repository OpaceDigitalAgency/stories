<?php
/**
 * Fix Subscribers Files
 * 
 * This script renames the fixed subscriber files to replace the original files.
 * It should be run on the server to fix the 500 error.
 */

// Define the files to fix
$filesToFix = [
    'admin/content/subscribers.php' => 'admin/content/subscribers-fixed.php',
    'api/v1/subscribers.php' => 'api/v1/subscribers-fixed.php'
];

// Function to log messages
function logMessage($message, $isError = false) {
    $style = $isError ? 'color: red; font-weight: bold;' : 'color: green;';
    echo "<div style='$style'>$message</div>";
}

// Check if we're running on the server
$isServer = file_exists('/home/stories/api.storiesfromtheweb.org');
if (!$isServer) {
    logMessage("This script should be run on the server.", true);
    exit;
}

// Fix each file
foreach ($filesToFix as $originalFile => $fixedFile) {
    $originalPath = __DIR__ . '/' . $originalFile;
    $fixedPath = __DIR__ . '/' . $fixedFile;
    $backupPath = $originalPath . '.bak';
    
    // Check if files exist
    if (!file_exists($fixedPath)) {
        logMessage("Fixed file not found: $fixedFile", true);
        continue;
    }
    
    // Create backup if original exists
    if (file_exists($originalPath)) {
        if (copy($originalPath, $backupPath)) {
            logMessage("Created backup: $originalFile.bak");
        } else {
            logMessage("Failed to create backup for: $originalFile", true);
            continue;
        }
    }
    
    // Copy fixed file to original location
    if (copy($fixedPath, $originalPath)) {
        logMessage("Successfully replaced: $originalFile");
    } else {
        logMessage("Failed to replace: $originalFile", true);
    }
}

logMessage("Fix completed. Please check the subscribers page now.");
?>
