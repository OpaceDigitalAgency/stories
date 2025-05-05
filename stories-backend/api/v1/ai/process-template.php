<?php
/**
 * Process Prompt Template API Endpoint
 * 
 * This endpoint processes a prompt template with provided variables.
 * 
 * Request method: POST
 * Request format: JSON
 * 
 * Required parameters:
 * - template_id: ID of the prompt template to use
 * - variables: Object containing variables to replace in the template
 * 
 * Response format: JSON
 * {
 *   "success": true|false,
 *   "data": {
 *     "processed_prompt": "Processed prompt text"
 *   },
 *   "error": "Error message if success is false"
 * }
 */

// Set content type to JSON
header('Content-Type: application/json');

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate request data
if (!$data || !isset($data['template_id']) || !isset($data['variables']) || !is_array($data['variables'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request. template_id and variables are required.'
    ]);
    exit;
}

// Include database connection
require_once '../../includes/db-connect.php';

try {
    // Get the prompt template
    $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE id = ?");
    $stmt->execute([$data['template_id']]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Prompt template not found');
    }
    
    // Process the template
    $processedPrompt = processTemplate($template['prompt_template'], $data['variables']);
    
    // Return the processed prompt
    echo json_encode([
        'success' => true,
        'data' => [
            'processed_prompt' => $processedPrompt
        ]
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log('Process Template Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Process a template by replacing variables
 * 
 * @param string $template The template string with placeholders
 * @param array $variables The variables to replace in the template
 * @return string The processed template
 */
function processTemplate($template, $variables) {
    // Replace simple variables {{variable}}
    $processedTemplate = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($variables) {
        $key = trim($matches[1]);
        return $variables[$key] ?? '';
    }, $template);
    
    // Process conditional blocks {{#if variable}}content{{/if}}
    $processedTemplate = preg_replace_callback('/\{\{#if ([^}]+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($variables) {
        $key = trim($matches[1]);
        $content = $matches[2];
        
        if (isset($variables[$key]) && !empty($variables[$key])) {
            return $content;
        }
        
        return '';
    }, $processedTemplate);
    
    return $processedTemplate;
}
