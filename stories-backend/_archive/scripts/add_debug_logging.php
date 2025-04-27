<?php
/**
 * Add Debug Logging to StoriesController
 * 
 * This script adds detailed logging to the StoriesController for debugging purposes.
 */

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>Add Debug Logging to StoriesController</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .back-link { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Debug Logging to StoriesController</h1>
', true);
}

output("Add Debug Logging to StoriesController");
output("====================================");
output("");

// Path to StoriesController.php
$storiesControllerPath = __DIR__ . '/api/v1/Endpoints/StoriesController.php';

if (!file_exists($storiesControllerPath)) {
    if ($isWeb) output("<div class='error'>StoriesController.php not found: $storiesControllerPath</div>", true);
    else output("Error: StoriesController.php not found: $storiesControllerPath");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("StoriesController file: $storiesControllerPath");
output("");

// Backup the StoriesController file
$backupFile = $storiesControllerPath . '.bak.' . date('YmdHis');
if (!copy($storiesControllerPath, $backupFile)) {
    if ($isWeb) output("<div class='error'>Failed to create backup file</div>", true);
    else output("Error: Failed to create backup file");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("Backup created: $backupFile");
output("");

// Read the StoriesController file
$content = file_get_contents($storiesControllerPath);

// Add debug logging to the constructor
if (preg_match('/public\s+function\s+__construct\s*\(\s*\$config\s*\)\s*\{/i', $content)) {
    output("Found constructor in StoriesController");
    
    // Add debug logging to the constructor
    $newConstructor = 'public function __construct($config) {
        // Debug logging
        error_log("StoriesController: Constructor called");
        error_log("StoriesController: Config: " . json_encode($config));
        
        // Original constructor code';
    
    $content = preg_replace('/public\s+function\s+__construct\s*\(\s*\$config\s*\)\s*\{/i', $newConstructor, $content);
}

// Add debug logging to the index method
if (preg_match('/public\s+function\s+index\s*\(\s*\)\s*\{/i', $content)) {
    output("Found index method in StoriesController");
    
    // Add debug logging to the index method
    $newIndexMethod = 'public function index() {
        // Debug logging
        error_log("StoriesController: index method called");
        error_log("StoriesController: Request params: " . json_encode($this->params));
        error_log("StoriesController: Request data: " . json_encode($this->request));
        
        // Original index method code';
    
    $content = preg_replace('/public\s+function\s+index\s*\(\s*\)\s*\{/i', $newIndexMethod, $content);
}

// Add debug logging to the show method
if (preg_match('/public\s+function\s+show\s*\(\s*\)\s*\{/i', $content)) {
    output("Found show method in StoriesController");
    
    // Add debug logging to the show method
    $newShowMethod = 'public function show() {
        // Debug logging
        error_log("StoriesController: show method called");
        error_log("StoriesController: Request params: " . json_encode($this->params));
        error_log("StoriesController: Request data: " . json_encode($this->request));
        
        // Original show method code';
    
    $content = preg_replace('/public\s+function\s+show\s*\(\s*\)\s*\{/i', $newShowMethod, $content);
}

// Add debug logging to the update method
if (preg_match('/public\s+function\s+update\s*\(\s*\)\s*\{/i', $content)) {
    output("Found update method in StoriesController");
    
    // Add debug logging to the update method
    $newUpdateMethod = 'public function update() {
        // Debug logging
        error_log("StoriesController: update method called");
        error_log("StoriesController: Request params: " . json_encode($this->params));
        error_log("StoriesController: Request data: " . json_encode($this->request));
        
        // Original update method code';
    
    $content = preg_replace('/public\s+function\s+update\s*\(\s*\)\s*\{/i', $newUpdateMethod, $content);
} else {
    output("Update method not found in StoriesController");
    
    // Add update method with debug logging
    $updateMethod = '
    /**
     * Update a story
     */
    public function update() {
        // Debug logging
        error_log("StoriesController: update method called");
        error_log("StoriesController: Request params: " . json_encode($this->params));
        error_log("StoriesController: Request data: " . json_encode($this->request));
        
        // Get the story ID from the URL
        $id = $this->params[\'id\'] ?? null;
        
        if (!$id) {
            error_log("StoriesController: No story ID provided");
            $this->badRequest(\'Story ID is required\');
            return;
        }
        
        // Get the request data
        $data = $this->request;
        
        // Validate required fields
        if (empty($data)) {
            error_log("StoriesController: No data provided");
            $this->badRequest(\'No data provided\');
            return;
        }
        
        try {
            error_log("StoriesController: Updating story with ID: $id");
            error_log("StoriesController: Update data: " . json_encode($data));
            
            // Update the story in the database
            $query = "UPDATE stories SET ";
            $params = [];
            $updateFields = [];
            
            // Only update fields that are provided
            if (isset($data[\'title\'])) {
                $updateFields[] = "title = ?";
                $params[] = $data[\'title\'];
            }
            
            if (isset($data[\'excerpt\'])) {
                $updateFields[] = "excerpt = ?";
                $params[] = $data[\'excerpt\'];
            }
            
            if (isset($data[\'content\'])) {
                $updateFields[] = "content = ?";
                $params[] = $data[\'content\'];
            }
            
            if (isset($data[\'featured\'])) {
                $updateFields[] = "featured = ?";
                $params[] = (int)$data[\'featured\'];
            }
            
            if (isset($data[\'published\'])) {
                $updateFields[] = "published = ?";
                $params[] = (int)$data[\'published\'];
            }
            
            if (isset($data[\'author_id\'])) {
                $updateFields[] = "author_id = ?";
                $params[] = $data[\'author_id\'];
            }
            
            // Add updated_at timestamp
            $updateFields[] = "updated_at = NOW()";
            
            // If no fields to update, return error
            if (empty($updateFields)) {
                error_log("StoriesController: No valid fields to update");
                $this->badRequest(\'No valid fields to update\');
                return;
            }
            
            // Complete the query
            $query .= implode(\', \', $updateFields);
            $query .= " WHERE id = ?";
            $params[] = $id;
            
            error_log("StoriesController: Update query: $query");
            error_log("StoriesController: Update params: " . json_encode($params));
            
            // Execute the query
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            // Check if the story was updated
            if ($stmt->rowCount() === 0) {
                error_log("StoriesController: Story not found or no changes made");
                $this->notFound(\'Story not found or no changes made\');
                return;
            }
            
            error_log("StoriesController: Story updated successfully");
            
            // Get the updated story
            $stmt = $this->db->prepare("SELECT * FROM stories WHERE id = ?");
            $stmt->execute([$id]);
            $story = $stmt->fetch();
            
            error_log("StoriesController: Updated story: " . json_encode($story));
            
            // Return success response
            error_log("StoriesController: Sending success response");
            Response::sendSuccess([
                \'data\' => [
                    \'id\' => $story[\'id\'],
                    \'attributes\' => $story
                ]
            ]);
        } catch (\PDOException $e) {
            // Log the error
            error_log("StoriesController: Error updating story: " . $e->getMessage());
            
            // Return error response
            $this->serverError(\'Error updating story\');
        }
    }';
    
    // Add the update method to the class
    $content = preg_replace('/(class\s+StoriesController.*?\{.*?)(}\s*$)/s', '$1' . $updateMethod . '$2', $content);
}

// Add debug logging to the create method
if (preg_match('/public\s+function\s+create\s*\(\s*\)\s*\{/i', $content)) {
    output("Found create method in StoriesController");
    
    // Add debug logging to the create method
    $newCreateMethod = 'public function create() {
        // Debug logging
        error_log("StoriesController: create method called");
        error_log("StoriesController: Request params: " . json_encode($this->params));
        error_log("StoriesController: Request data: " . json_encode($this->request));
        
        // Original create method code';
    
    $content = preg_replace('/public\s+function\s+create\s*\(\s*\)\s*\{/i', $newCreateMethod, $content);
}

// Add debug logging to the delete method
if (preg_match('/public\s+function\s+delete\s*\(\s*\)\s*\{/i', $content)) {
    output("Found delete method in StoriesController");
    
    // Add debug logging to the delete method
    $newDeleteMethod = 'public function delete() {
        // Debug logging
        error_log("StoriesController: delete method called");
        error_log("StoriesController: Request params: " . json_encode($this->params));
        error_log("StoriesController: Request data: " . json_encode($this->request));
        
        // Original delete method code';
    
    $content = preg_replace('/public\s+function\s+delete\s*\(\s*\)\s*\{/i', $newDeleteMethod, $content);
}

// Write the modified content back to the file
if (file_put_contents($storiesControllerPath, $content)) {
    if ($isWeb) output("<div class='success'>Successfully added debug logging to StoriesController</div>", true);
    else output("Successfully added debug logging to StoriesController");
} else {
    if ($isWeb) output("<div class='error'>Failed to write modified content to file</div>", true);
    else output("Error: Failed to write modified content to file");
}

// Create a restore script
$restoreScript = '<?php
// Restore the original StoriesController
$backupFile = "' . $backupFile . '";
$storiesControllerPath = "' . $storiesControllerPath . '";

if (file_exists($backupFile)) {
    if (copy($backupFile, $storiesControllerPath)) {
        echo "Successfully restored original StoriesController";
    } else {
        echo "Failed to restore original StoriesController";
    }
} else {
    echo "Backup file not found: " . $backupFile;
}
';

$restoreScriptPath = __DIR__ . '/restore_stories_controller.php';
file_put_contents($restoreScriptPath, $restoreScript);

output("");
output("A restore script has been created: restore_stories_controller.php");
output("Run this script to restore the original StoriesController when you're done debugging");

output("");
output("Next Steps:");
output("1. Try accessing the admin interface again");
output("2. Check the server logs for detailed debug information");
output("3. If issues persist, try running the debug_api_calls.php script to directly test the API endpoints");

if ($isWeb) {
    output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
    output('</div></body></html>', true);
}