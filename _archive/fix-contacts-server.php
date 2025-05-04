<?php
/**
 * Direct fix for contacts.php on the server
 * 
 * This script directly modifies the contacts.php file on the server
 * to fix the duplicate header/footer issue.
 */

// Define the server path to the contacts.php file
$filePath = '/home/stories/api.storiesfromtheweb.org/admin/content/contacts.php';

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

// Count the number of header includes
$headerCount = substr_count($content, "require_once '../includes/header.php'") + 
              substr_count($content, "include '../includes/header.php'") + 
              substr_count($content, "include_once '../includes/header.php'");

// Count the number of footer includes
$footerCount = substr_count($content, "require_once '../includes/footer.php'") + 
              substr_count($content, "include '../includes/footer.php'") + 
              substr_count($content, "include_once '../includes/footer.php'");

echo "Found $headerCount header includes and $footerCount footer includes in $filePath\n";

// If there are multiple header or footer includes, fix them
if ($headerCount > 1 || $footerCount > 1) {
    echo "Fixing duplicate includes in $filePath\n";
    
    // Split the content into lines
    $lines = explode("\n", $content);
    
    // Track which includes we've seen
    $seenHeader = false;
    $seenFooter = false;
    
    // Process each line
    $newLines = [];
    foreach ($lines as $line) {
        // Check for header include
        if (strpos($line, '../includes/header.php') !== false) {
            if (!$seenHeader) {
                // Keep the first header include and make it require_once
                $newLines[] = "require_once '../includes/header.php';";
                $seenHeader = true;
            }
            // Skip duplicate header includes
            continue;
        }
        
        // Check for footer include
        if (strpos($line, '../includes/footer.php') !== false) {
            if (!$seenFooter) {
                // Keep the first footer include and make it require_once
                $newLines[] = "require_once '../includes/footer.php';";
                $seenFooter = true;
            }
            // Skip duplicate footer includes
            continue;
        }
        
        // Keep all other lines
        $newLines[] = $line;
    }
    
    // Rebuild the content
    $newContent = implode("\n", $newLines);
    
    // Write the file
    if (file_put_contents($filePath, $newContent) === false) {
        echo "Error: Could not write to $filePath\n";
        exit(1);
    }
    
    echo "Successfully fixed duplicate includes in $filePath\n";
} else {
    echo "No duplicate includes found in $filePath\n";
}

// Now check the bulk-contacts.php file
$bulkFilePath = '/home/stories/api.storiesfromtheweb.org/admin/content/bulk-contacts.php';

// Check if the file exists
if (!file_exists($bulkFilePath)) {
    echo "Warning: Bulk contacts file not found at $bulkFilePath\n";
} else {
    // Read the file content
    $bulkContent = file_get_contents($bulkFilePath);
    if ($bulkContent === false) {
        echo "Error: Could not read file at $bulkFilePath\n";
    } else {
        // Create a backup of the original file
        $bulkBackupPath = $bulkFilePath . '.bak.' . date('Y-m-d-H-i-s');
        if (file_put_contents($bulkBackupPath, $bulkContent) === false) {
            echo "Error: Could not create backup at $bulkBackupPath\n";
        } else {
            echo "Created backup at $bulkBackupPath\n";
            
            // Check for header/footer includes in the bulk file
            $bulkHeaderCount = substr_count($bulkContent, "require_once '../includes/header.php'") + 
                              substr_count($bulkContent, "include '../includes/header.php'") + 
                              substr_count($bulkContent, "include_once '../includes/header.php'");
            
            $bulkFooterCount = substr_count($bulkContent, "require_once '../includes/footer.php'") + 
                              substr_count($bulkContent, "include '../includes/footer.php'") + 
                              substr_count($bulkContent, "include_once '../includes/footer.php'");
            
            echo "Found $bulkHeaderCount header includes and $bulkFooterCount footer includes in $bulkFilePath\n";
            
            // If there are any header or footer includes, remove them
            if ($bulkHeaderCount > 0 || $bulkFooterCount > 0) {
                echo "Removing header/footer includes from $bulkFilePath\n";
                
                // Split the content into lines
                $bulkLines = explode("\n", $bulkContent);
                
                // Process each line
                $newBulkLines = [];
                foreach ($bulkLines as $line) {
                    // Skip header and footer includes
                    if (strpos($line, '../includes/header.php') !== false || 
                        strpos($line, '../includes/footer.php') !== false) {
                        continue;
                    }
                    
                    // Keep all other lines
                    $newBulkLines[] = $line;
                }
                
                // Add a comment to clarify this is a processing script
                $newBulkLines[0] = '<?php
/**
 * Bulk Actions for Contact Form Submissions
 * Handles bulk operations on contact form submissions
 *
 * This is a processing script that doesn\'t output any HTML.
 * It processes form submissions and redirects back to the contacts.php page.
 */';
                
                // Rebuild the content
                $newBulkContent = implode("\n", $newBulkLines);
                
                // Write the file
                if (file_put_contents($bulkFilePath, $newBulkContent) === false) {
                    echo "Error: Could not write to $bulkFilePath\n";
                } else {
                    echo "Successfully removed header/footer includes from $bulkFilePath\n";
                }
            } else {
                echo "No header/footer includes found in $bulkFilePath\n";
            }
        }
    }
}
