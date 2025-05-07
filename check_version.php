<?php
// Script to check the current version of story-form.php on the server
$url = 'https://api.storiesfromtheweb.org/admin/content/story-form.php';
$content = file_get_contents($url);

// Check for the duplicate previewButton declaration
$pattern1 = '/const previewButton = document\.getElementById\(\'preview-story\'\);/';
$matches1 = [];
preg_match_all($pattern1, $content, $matches1);

// Check for our fix (setupPreviewModal function)
$pattern2 = '/function setupPreviewModal\(\)/';
$matches2 = [];
preg_match($pattern2, $content, $matches2);

echo "Number of 'previewButton' declarations: " . count($matches1[0]) . "\n";
echo "setupPreviewModal function found: " . (count($matches2) > 0 ? "Yes" : "No") . "\n";

// Check for boolean field fixes
$pattern3 = '/form-check form-switch/';
$matches3 = [];
preg_match($pattern3, $content, $matches3);

echo "form-switch class found: " . (count($matches3) > 0 ? "Yes" : "No") . "\n";

// Look for the specific error line
$lines = explode("\n", $content);
$errorLineNumber = 0;
$errorLine = '';

foreach ($lines as $i => $line) {
    if (strpos($line, 'previewButton') !== false && strpos($line, 'getElementById') !== false) {
        echo "previewButton declaration found at line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
