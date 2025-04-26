<?php
/**
 * Direct Fix for Story Form
 * 
 * This script directly modifies the story form template to use checkboxes
 * for boolean fields instead of text inputs.
 */

// Define the path to the story form template
// You may need to adjust this path based on your actual directory structure
$storyFormPath = 'admin/templates/story-form.php';
$storyFormBackupPath = 'admin/templates/story-form.php.bak';

// Check if the file exists
if (!file_exists($storyFormPath)) {
    echo "Error: Story form template not found at $storyFormPath";
    
    // Try to find the file
    $possiblePaths = [
        'admin/templates/story-form.php',
        'admin/templates/story_form.php',
        'admin/forms/story-form.php',
        'admin/forms/story_form.php',
        'templates/story-form.php',
        'templates/story_form.php',
        'forms/story-form.php',
        'forms/story_form.php',
        'admin/views/story-form.php',
        'admin/views/story_form.php',
        'views/story-form.php',
        'views/story_form.php',
        'admin/content/story-form.php',
        'admin/content/story_form.php',
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $storyFormPath = $path;
            $storyFormBackupPath = $path . '.bak';
            echo "<p>Found story form at: $path</p>";
            break;
        }
    }
    
    if (!file_exists($storyFormPath)) {
        echo "<p>Could not find story form template. Please enter the correct path:</p>";
        echo "<form method='post'>";
        echo "<input type='text' name='custom_path' placeholder='Path to story form template'>";
        echo "<button type='submit'>Submit</button>";
        echo "</form>";
        exit;
    }
}

// If a custom path was provided
if (isset($_POST['custom_path']) && !empty($_POST['custom_path'])) {
    $customPath = $_POST['custom_path'];
    if (file_exists($customPath)) {
        $storyFormPath = $customPath;
        $storyFormBackupPath = $customPath . '.bak';
        echo "<p>Using custom path: $customPath</p>";
    } else {
        echo "<p>Error: File not found at $customPath</p>";
        exit;
    }
}

// Create a backup of the original file
if (!file_exists($storyFormBackupPath)) {
    copy($storyFormPath, $storyFormBackupPath);
    echo "<p>Created backup at $storyFormBackupPath</p>";
}

// Read the file content
$content = file_get_contents($storyFormPath);
$originalContent = $content;

// Define the boolean fields to convert
$booleanFields = [
    'is_published' => 'Published',
    'published' => 'Published',
    'is_featured' => 'Featured',
    'featured' => 'Featured',
    'is_sponsored' => 'Sponsored',
    'sponsored' => 'Sponsored',
    'is_ai_enhanced' => 'AI Enhanced',
    'ai_enhanced' => 'AI Enhanced',
    'is_self_published' => 'Self Published',
    'self_published' => 'Self Published',
    'needs_moderation' => 'Needs Moderation'
];

// Process each boolean field
$modified = false;
foreach ($booleanFields as $field => $label) {
    // Look for text input pattern
    $textInputPattern = '/<input[^>]*type=["\']text["\'][^>]*name=["\']' . $field . '["\'][^>]*>/i';
    $textInputPattern2 = '/<input[^>]*name=["\']' . $field . '["\'][^>]*type=["\']text["\'][^>]*>/i';
    $numberInputPattern = '/<input[^>]*type=["\']number["\'][^>]*name=["\']' . $field . '["\'][^>]*>/i';
    $numberInputPattern2 = '/<input[^>]*name=["\']' . $field . '["\'][^>]*type=["\']number["\'][^>]*>/i';
    
    // Create checkbox replacement
    $checkboxReplacement = '<input type="checkbox" id="' . $field . '" name="' . $field . '" value="1" <?php echo (isset($item["' . $field . '"]) && $item["' . $field . '"]) ? "checked" : ""; ?> class="form-check-input">';
    
    // Replace text inputs with checkboxes
    if (preg_match($textInputPattern, $content) || preg_match($textInputPattern2, $content) || 
        preg_match($numberInputPattern, $content) || preg_match($numberInputPattern2, $content)) {
        $content = preg_replace([$textInputPattern, $textInputPattern2, $numberInputPattern, $numberInputPattern2], $checkboxReplacement, $content);
        $modified = true;
        echo "<p>Converted $field to checkbox</p>";
    }
    
    // Also look for and update labels
    $labelPattern = '/<label[^>]*for=["\']' . $field . '["\'][^>]*>.*?<\/label>/i';
    $labelReplacement = '<label for="' . $field . '" class="form-check-label">' . $label . '</label>';
    
    if (preg_match($labelPattern, $content)) {
        $content = preg_replace($labelPattern, $labelReplacement, $content);
        echo "<p>Updated label for $field</p>";
    }
}

// Direct replacements for specific patterns
$directReplacements = [
    // Replace text input for is_published with checkbox
    '/<input[^>]*name=["\']is_published["\'][^>]*value=["\']1["\'][^>]*>/' => 
    '<input type="checkbox" id="is_published" name="is_published" value="1" <?php echo (isset($item["is_published"]) && $item["is_published"]) ? "checked" : ""; ?> class="form-check-input">',
    
    // Replace text input for needs_moderation with checkbox
    '/<input[^>]*name=["\']needs_moderation["\'][^>]*value=["\']0["\'][^>]*>/' => 
    '<input type="checkbox" id="needs_moderation" name="needs_moderation" value="1" <?php echo (isset($item["needs_moderation"]) && $item["needs_moderation"]) ? "checked" : ""; ?> class="form-check-input">',
    
    // Replace text input for is_self_published with checkbox
    '/<input[^>]*name=["\']is_self_published["\'][^>]*value=["\']1["\'][^>]*>/' => 
    '<input type="checkbox" id="is_self_published" name="is_self_published" value="1" <?php echo (isset($item["is_self_published"]) && $item["is_self_published"]) ? "checked" : ""; ?> class="form-check-input">',
    
    // Replace text input for is_ai_enhanced with checkbox
    '/<input[^>]*name=["\']is_ai_enhanced["\'][^>]*value=["\']0["\'][^>]*>/' => 
    '<input type="checkbox" id="is_ai_enhanced" name="is_ai_enhanced" value="1" <?php echo (isset($item["is_ai_enhanced"]) && $item["is_ai_enhanced"]) ? "checked" : ""; ?> class="form-check-input">'
];

foreach ($directReplacements as $pattern => $replacement) {
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $replacement, $content);
        $modified = true;
        echo "<p>Applied direct replacement</p>";
    }
}

// Save the modified content if changes were made
if ($modified) {
    file_put_contents($storyFormPath, $content);
    echo "<p>Successfully updated $storyFormPath</p>";
    
    // Also update the controller file to handle checkbox values
    $controllerPaths = [
        'admin/controllers/StoryController.php',
        'admin/controllers/story_controller.php',
        'controllers/StoryController.php',
        'controllers/story_controller.php',
        'admin/StoryController.php',
        'admin/story_controller.php'
    ];
    
    $controllerFound = false;
    foreach ($controllerPaths as $controllerPath) {
        if (file_exists($controllerPath)) {
            $controllerContent = file_get_contents($controllerPath);
            $controllerBackupPath = $controllerPath . '.bak';
            
            // Create backup of controller
            if (!file_exists($controllerBackupPath)) {
                copy($controllerPath, $controllerBackupPath);
                echo "<p>Created backup of controller at $controllerBackupPath</p>";
            }
            
            $controllerModified = false;
            
            // Update controller to handle checkbox values
            foreach ($booleanFields as $field => $label) {
                $pattern = '/\$item\[\'' . $field . '\'\]\s*=\s*\$_POST\[\'' . $field . '\'\];/i';
                $replacement = '$item[\'' . $field . '\'] = isset($_POST[\'' . $field . '\']) ? 1 : 0;';
                
                if (preg_match($pattern, $controllerContent)) {
                    $controllerContent = preg_replace($pattern, $replacement, $controllerContent);
                    $controllerModified = true;
                    echo "<p>Updated controller handling for $field</p>";
                }
            }
            
            if ($controllerModified) {
                file_put_contents($controllerPath, $controllerContent);
                echo "<p>Successfully updated controller at $controllerPath</p>";
            } else {
                echo "<p>No changes needed in controller at $controllerPath</p>";
            }
            
            $controllerFound = true;
            break;
        }
    }
    
    if (!$controllerFound) {
        echo "<p>Warning: Could not find story controller to update</p>";
    }
    
    echo "<p>All updates completed successfully!</p>";
} else {
    echo "<p>No changes were needed in $storyFormPath</p>";
}

// Provide links to navigate
echo "<p><a href='javascript:history.back()'>Go Back</a> | <a href='index.php'>Admin Home</a></p>";

// Provide a restore option
echo "<form method='post'>";
echo "<input type='hidden' name='restore_backup' value='1'>";
echo "<button type='submit'>Restore from Backup</button>";
echo "</form>";

// Handle restore from backup
if (isset($_POST['restore_backup']) && file_exists($storyFormBackupPath)) {
    copy($storyFormBackupPath, $storyFormPath);
    echo "<p>Restored from backup</p>";
}