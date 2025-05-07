<?php
/**
 * Handler for getting AI tool details for preview
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'AI Tool ID is required'
    ]);
    exit;
}

$toolId = intval($_GET['id']);

try {
    // Get AI tool details
    $stmt = $db->prepare("
        SELECT a.*, 
               c.name as category_name
        FROM ai_tools a
        LEFT JOIN ai_tool_categories c ON a.category_id = c.id
        WHERE a.id = ?
    ");
    $stmt->execute([$toolId]);
    $tool = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tool) {
        echo json_encode([
            'success' => false,
            'message' => 'AI Tool not found'
        ]);
        exit;
    }

    // Format the cover URL
    if (!empty($tool['cover_url'])) {
        // If it's a relative URL, make it absolute
        if (strpos($tool['cover_url'], 'http') !== 0) {
            $tool['cover_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($tool['cover_url'], '/');
        }
    }

    // Return the AI tool data
    echo json_encode([
        'success' => true,
        'tool' => $tool
    ]);

} catch (Exception $e) {
    error_log('Error in get-ai-tool.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching AI tool data'
    ]);
}
