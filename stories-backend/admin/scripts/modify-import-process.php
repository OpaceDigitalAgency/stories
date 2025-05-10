<?php
/**
 * Modify Import Process Script
 * 
 * This script modifies the import process to properly handle age ranges:
 * 1. Creates a function to filter out age-related tags
 * 2. Creates a patch file to be included in the import process
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
    <title>Modify Import Process</title>
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
    <h1>Modify Import Process</h1>
';
}

output("Starting import process modification...", $isWeb);
output("", $isWeb);

// Create the patch file
$patchFilePath = '../../public/import-process-patch.php';
$patchContent = '<?php
/**
 * Import Process Patch
 * 
 * This file contains functions to patch the import process to properly handle age ranges.
 */

/**
 * Filter out age-related tags from a list of tags
 * 
 * @param array $tags List of tags to filter
 * @return array Filtered list of tags
 */
function filterAgeTags($tags) {
    // Define age-related patterns
    $agePatterns = [
        "/^0-3$/i", "/^3-5$/i", "/^4-6$/i", "/^5-7$/i", "/^6-8$/i", "/^7-9$/i", "/^7-10$/i", 
        "/^8-10$/i", "/^8-12$/i", "/^9-12$/i", "/^10-12$/i", "/^10\\+$/i", "/^12\\+$/i", 
        "/^13\\+$/i", "/^14\\+$/i", "/^16\\+$/i", "/^teen$/i", "/^young adult$/i", "/^adult$/i",
        "/^\\d+-\\d+$/i", "/^\\d+\\+$/i", "/years/i", "/age/i"
    ];
    
    // Filter out age-related tags
    $filteredTags = [];
    foreach ($tags as $tag) {
        $isAgeTag = false;
        foreach ($agePatterns as $pattern) {
            if (preg_match($pattern, $tag)) {
                $isAgeTag = true;
                break;
            }
        }
        
        if (!$isAgeTag) {
            $filteredTags[] = $tag;
        }
    }
    
    return $filteredTags;
}

/**
 * Extract age range from tags
 * 
 * @param array $tags List of tags to extract age range from
 * @return string|null Extracted age range or null if not found
 */
function extractAgeRange($tags) {
    // Define age-related patterns with priorities
    $agePatterns = [
        "/^(0-3)$/i" => "0-3",
        "/^(3-5)$/i" => "3-5",
        "/^(4-6)$/i" => "4-6",
        "/^(5-7)$/i" => "5-7",
        "/^(6-8)$/i" => "6-8",
        "/^(7-9)$/i" => "7-9",
        "/^(7-10)$/i" => "7-10",
        "/^(8-10)$/i" => "8-10",
        "/^(8-12)$/i" => "8-12",
        "/^(9-12)$/i" => "9-12",
        "/^(10-12)$/i" => "10-12",
        "/^(10\\+)$/i" => "10+",
        "/^(12\\+)$/i" => "12+",
        "/^(13\\+)$/i" => "13+",
        "/^(14\\+)$/i" => "14+",
        "/^(16\\+)$/i" => "16+",
        "/^(teen)$/i" => "teen",
        "/^(young adult)$/i" => "young adult",
        "/^(adult)$/i" => "adult",
        "/^(\\d+-\\d+)$/i" => "$1",
        "/^(\\d+\\+)$/i" => "$1"
    ];
    
    // Check each tag against the patterns
    foreach ($tags as $tag) {
        foreach ($agePatterns as $pattern => $replacement) {
            if (preg_match($pattern, $tag, $matches)) {
                if (strpos($replacement, "$") !== false) {
                    return preg_replace($pattern, $replacement, $tag);
                } else {
                    return $replacement;
                }
            }
        }
    }
    
    return null;
}
';

// Write the patch file
if (file_put_contents($patchFilePath, $patchContent)) {
    output("Created patch file: $patchFilePath", $isWeb);
} else {
    output("Error: Failed to create patch file", $isWeb);
}

output("", $isWeb);
output("Import process modification completed!", $isWeb);
output("", $isWeb);
output("To use this patch, include the following line at the top of your import script:", $isWeb);
output("require_once 'import-process-patch.php';", $isWeb);
output("", $isWeb);
output("Then, modify your tag processing code to use the filterAgeTags() function:", $isWeb);
output('$filteredTags = filterAgeTags($tags);', $isWeb);
output("", $isWeb);
output("And extract age range from tags:", $isWeb);
output('$ageRange = extractAgeRange($tags);', $isWeb);
output('if ($ageRange) {', $isWeb);
output('    // Set the age_range field in the books table', $isWeb);
output('    $book[\'age_range\'] = $ageRange;', $isWeb);
output('}', $isWeb);

// Footer for web output
if ($isWeb) {
    echo '
</body>
</html>';
}
?>
