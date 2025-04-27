<?php
/**
 * Fix Admin Boolean Fields
 * 
 * This script updates the admin interface to use proper HTML checkboxes
 * for boolean fields instead of text inputs.
 */

// Include necessary files
require_once 'config/database.php';
require_once 'helpers/auth.php';

// Ensure only authorized users can access this script
if (!isAdminUser()) {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. Admin privileges required.";
    exit;
}

// Fields that should be converted to checkboxes
$booleanFields = [
    'is_published' => 'Published',
    'is_featured' => 'Featured',
    'is_sponsored' => 'Sponsored',
    'is_ai_enhanced' => 'AI Enhanced',
    'is_self_published' => 'Self Published',
    'needs_moderation' => 'Needs Moderation'
];

// Remove duplicate fields
$duplicateFields = [
    'published' => 'is_published' // Keep is_published, remove published
];

// Function to update form templates
function updateFormTemplates($directory) {
    global $booleanFields, $duplicateFields;
    
    $files = glob($directory . '/*.php');
    foreach ($files as $file) {
        if (is_file($file)) {
            $content = file_get_contents($file);
            $modified = false;
            
            // Replace text inputs with checkboxes for boolean fields
            foreach ($booleanFields as $field => $label) {
                $pattern = '/<input[^>]*name=["\']' . $field . '["\'][^>]*>/i';
                $replacement = '<input type="checkbox" name="' . $field . '" id="' . $field . '" value="1" <?php echo $item["' . $field . '"] ? "checked" : ""; ?> class="form-check-input">';
                
                // Also replace the label if it exists
                $labelPattern = '/<label[^>]*for=["\']' . $field . '["\'][^>]*>.*?<\/label>/i';
                $labelReplacement = '<label for="' . $field . '" class="form-check-label">' . $label . '</label>';
                
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                    $content = preg_replace($labelPattern, $labelReplacement, $content);
                    $modified = true;
                }
            }
            
            // Remove duplicate fields
            foreach ($duplicateFields as $duplicate => $keep) {
                $duplicatePattern = '/<div[^>]*class=["\']form-group["\'][^>]*>.*?<label[^>]*for=["\']' . $duplicate . '["\'][^>]*>.*?<\/label>.*?<input[^>]*name=["\']' . $duplicate . '["\'][^>]*>.*?<\/div>/is';
                if (preg_match($duplicatePattern, $content)) {
                    $content = preg_replace($duplicatePattern, '', $content);
                    $modified = true;
                }
            }
            
            // Save changes if modified
            if ($modified) {
                file_put_contents($file, $content);
                echo "Updated: " . basename($file) . "<br>";
            }
        }
    }
}

// Update form templates in admin directory
updateFormTemplates('admin/templates');
updateFormTemplates('admin/forms');

// Update database handling for boolean fields
function updateDatabaseHandlers() {
    global $booleanFields;
    
    $files = glob('admin/controllers/*.php');
    foreach ($files as $file) {
        if (is_file($file)) {
            $content = file_get_contents($file);
            $modified = false;
            
            // Update save/update methods to handle checkbox values
            foreach ($booleanFields as $field => $label) {
                $pattern = '/\$item\[\'' . $field . '\'\]\s*=\s*\$_POST\[\'' . $field . '\'\];/i';
                $replacement = '$item[\'' . $field . '\'] = isset($_POST[\'' . $field . '\']) ? 1 : 0;';
                
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                    $modified = true;
                }
            }
            
            // Save changes if modified
            if ($modified) {
                file_put_contents($file, $content);
                echo "Updated controller: " . basename($file) . "<br>";
            }
        }
    }
}

// Update database handlers
updateDatabaseHandlers();

echo "<h2>Admin Interface Update Complete</h2>";
echo "<p>Boolean fields have been converted to checkboxes and duplicate fields have been removed.</p>";
echo "<p><a href='admin/index.php'>Return to Admin Dashboard</a></p>";