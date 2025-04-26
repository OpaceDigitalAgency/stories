<?php
/**
 * Simple Fix Admin Boolean Fields
 * 
 * This script provides a simple HTML form to update the admin interface
 * to use proper HTML checkboxes for boolean fields.
 */

// Basic authentication check - can be enhanced based on your auth system
$isAuthenticated = true; // Set to false and implement your own auth check if needed

// If not authenticated, show login form
if (!$isAuthenticated) {
    echo "<h1>Authentication Required</h1>";
    echo "<p>Please log in to access this tool.</p>";
    // Add your login form here
    exit;
}

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // List of directories to search for PHP files
    $directories = [
        'admin/templates',
        'admin/forms',
        'admin/views',
        'templates',
        'forms',
        'views'
    ];
    
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
    
    $updatedFiles = [];
    $errors = [];
    
    // Process each directory
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue; // Skip if directory doesn't exist
        }
        
        $files = glob("$directory/*.php");
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            try {
                $content = file_get_contents($file);
                $originalContent = $content;
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
                    $updatedFiles[] = $file;
                }
            } catch (Exception $e) {
                $errors[] = "Error processing $file: " . $e->getMessage();
            }
        }
    }
    
    // Update controller files
    $controllerDirs = [
        'admin/controllers',
        'controllers',
        'admin'
    ];
    
    foreach ($controllerDirs as $directory) {
        if (!is_dir($directory)) {
            continue; // Skip if directory doesn't exist
        }
        
        $files = glob("$directory/*.php");
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            try {
                $content = file_get_contents($file);
                $originalContent = $content;
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
                    $updatedFiles[] = $file;
                }
            } catch (Exception $e) {
                $errors[] = "Error processing $file: " . $e->getMessage();
            }
        }
    }
    
    // Set message based on results
    if (!empty($updatedFiles)) {
        $message = "<div class='alert alert-success'>Successfully updated " . count($updatedFiles) . " files.</div>";
    } else if (empty($errors)) {
        $message = "<div class='alert alert-info'>No files needed updating.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Encountered " . count($errors) . " errors.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Admin Boolean Fields</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn:hover {
            background: #2980b9;
        }
        ul {
            list-style-type: disc;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Admin Boolean Fields</h1>
        
        <?php echo $message; ?>
        
        <p>This tool will update your admin interface to use proper HTML checkboxes for boolean fields instead of text inputs.</p>
        
        <h2>Changes to be made:</h2>
        <ul>
            <li>Convert text inputs to checkboxes for boolean fields (is_published, is_featured, etc.)</li>
            <li>Remove duplicate fields (e.g., both "Published" checkbox and "Is published" text field)</li>
            <li>Update form handlers to properly process checkbox values</li>
        </ul>
        
        <form method="post" action="">
            <p><button type="submit" name="update" class="btn">Update Admin Interface</button></p>
        </form>
        
        <p><a href="index.php" class="btn" style="background: #7f8c8d;">Back to Admin</a></p>
    </div>
</body>
</html>