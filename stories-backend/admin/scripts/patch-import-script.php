<?php
/**
 * Patch Import Script
 * 
 * This script patches the direct_import.php file to include our age tag handling patch.
 */

// Set execution time limit to 5 minutes
set_time_limit(300);

// Start output buffering
ob_start();

// Function to output messages
function output($message, $isHtml = false) {
    if ($isHtml) {
        echo $message . "<br>\n";
    } else {
        echo $message . "\n";
    }
    ob_flush();
    flush();
}

// Check if running in web or CLI
$isWeb = php_sapi_name() !== 'cli';

// Header for web output
if ($isWeb) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Patch Import Script</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .warning {
            color: orange;
        }
        .info {
            color: blue;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Patch Import Script</h1>
';
}

output("Starting import script patching...", $isWeb);
output("", $isWeb);

// Path to the import script
$importScriptPath = '../../public/direct_import.php';

// Check if the import script exists
if (!file_exists($importScriptPath)) {
    output("Error: Import script not found at $importScriptPath", $isWeb);
    exit;
}

// Read the import script
$importScript = file_get_contents($importScriptPath);

// Check if the patch has already been applied
if (strpos($importScript, 'import-process-patch.php') !== false) {
    output("Patch has already been applied to the import script", $isWeb);
    exit;
}

// Create a backup of the import script
$backupPath = $importScriptPath . '.bak.' . date('YmdHis');
if (file_put_contents($backupPath, $importScript)) {
    output("Created backup of import script at $backupPath", $isWeb);
} else {
    output("Error: Failed to create backup of import script", $isWeb);
    exit;
}

// Add the patch include after the existing includes
$patchInclude = "// Include age tag handling patch\nrequire_once 'import-process-patch.php';\n";
$importScript = preg_replace('/(require_once \'process_book_functions\.php\';)/', "$1\n$patchInclude", $importScript);

// Modify the processBook function to use our patch
$importScript = preg_replace(
    '/(\/\/ Process tags.*?)(\$stmt = \$db->prepare\("INSERT INTO tags)/s',
    "$1// Filter out age-related tags\n        \$tags = filterAgeTags(\$tags);\n\n        // Extract age range from tags\n        \$extractedAgeRange = extractAgeRange(\$originalTags);\n        if (\$extractedAgeRange && empty(\$ageRange)) {\n            \$ageRange = \$extractedAgeRange;\n            echo \"<p class='info'>Extracted age range from tags: '\$ageRange'</p>\";\n            flushOutput();\n        }\n\n        $2",
    $importScript
);

// Save the modified import script
if (file_put_contents($importScriptPath, $importScript)) {
    output("Successfully patched import script", $isWeb);
} else {
    output("Error: Failed to save modified import script", $isWeb);
}

output("", $isWeb);
output("Import script patching completed!", $isWeb);

// Footer for web output
if ($isWeb) {
    echo '
</body>
</html>';
}
?>
