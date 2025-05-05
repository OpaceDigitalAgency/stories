<?php
/**
 * AI Prompt Templates API Endpoint
 * 
 * This endpoint handles CRUD operations for AI prompt templates.
 * 
 * GET: Retrieve prompt templates
 * POST: Create a new prompt template
 * PUT: Update an existing prompt template
 * DELETE: Delete a prompt template
 * 
 * Response format: JSON
 */

// Set content type to JSON
header('Content-Type: application/json');

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once '../../includes/db-connect.php';

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        getPromptTemplates();
        break;
    case 'POST':
        createPromptTemplate();
        break;
    case 'PUT':
        updatePromptTemplate();
        break;
    case 'DELETE':
        deletePromptTemplate();
        break;
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        break;
}

/**
 * Get prompt templates
 */
function getPromptTemplates() {
    global $db;
    
    try {
        // Check if a specific ID is requested
        $id = $_GET['id'] ?? null;
        $contentType = $_GET['content_type'] ?? null;
        
        if ($id) {
            // Get a specific template by ID
            $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Prompt template not found'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $template
            ]);
        } elseif ($contentType) {
            // Get templates for a specific content type
            $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE content_type = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$contentType]);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $templates
            ]);
        } else {
            // Get all templates
            $stmt = $db->query("SELECT * FROM ai_prompt_templates ORDER BY content_type, name");
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $templates
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to retrieve prompt templates: ' . $e->getMessage()
        ]);
    }
}

/**
 * Create a new prompt template
 */
function createPromptTemplate() {
    global $db;
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    // Validate request data
    if (!$data || !isset($data['name']) || !isset($data['content_type']) || !isset($data['prompt_template'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request. Name, content_type, and prompt_template are required.'
        ]);
        return;
    }
    
    try {
        // Check if a template with the same name and content type already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM ai_prompt_templates WHERE name = ? AND content_type = ?");
        $stmt->execute([$data['name'], $data['content_type']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'A prompt template with this name and content type already exists'
            ]);
            return;
        }
        
        // Insert new template
        $stmt = $db->prepare("
            INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['content_type'],
            $data['description'] ?? null,
            $data['prompt_template'],
            $data['is_active'] ?? true
        ]);
        
        $id = $db->lastInsertId();
        
        // Get the newly created template
        $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE id = ?");
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $template
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create prompt template: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update an existing prompt template
 */
function updatePromptTemplate() {
    global $db;
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    // Validate request data
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request. ID is required.'
        ]);
        return;
    }
    
    try {
        // Check if the template exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM ai_prompt_templates WHERE id = ?");
        $stmt->execute([$data['id']]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Prompt template not found'
            ]);
            return;
        }
        
        // Update the template
        $fields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        
        if (isset($data['content_type'])) {
            $fields[] = 'content_type = ?';
            $params[] = $data['content_type'];
        }
        
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }
        
        if (isset($data['prompt_template'])) {
            $fields[] = 'prompt_template = ?';
            $params[] = $data['prompt_template'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = $data['is_active'];
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No fields to update'
            ]);
            return;
        }
        
        $sql = "UPDATE ai_prompt_templates SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $data['id'];
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Get the updated template
        $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE id = ?");
        $stmt->execute([$data['id']]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $template
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update prompt template: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete a prompt template
 */
function deletePromptTemplate() {
    global $db;
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    // Validate request data
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request. ID is required.'
        ]);
        return;
    }
    
    try {
        // Check if the template exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM ai_prompt_templates WHERE id = ?");
        $stmt->execute([$data['id']]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Prompt template not found'
            ]);
            return;
        }
        
        // Delete the template
        $stmt = $db->prepare("DELETE FROM ai_prompt_templates WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Prompt template deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete prompt template: ' . $e->getMessage()
        ]);
    }
}
