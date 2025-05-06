<?php
/**
 * Admin Data API Endpoints
 *
 * This file provides API endpoints for the admin-direct-data.js script to fetch data
 * when the normal admin pages are not loading data correctly.
 */

// Set headers for API responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the endpoint from the query string
$endpoint = $_GET['endpoint'] ?? '';

// Include database connection
$db_path = dirname(dirname(__DIR__)) . '/admin/includes/db-connect.php';
require_once $db_path;

// Check if database connection is available
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Route the request
switch ($endpoint) {
    case 'ai-tools':
        getAiTools();
        break;
    case 'directory-items':
        getDirectoryItems();
        break;
    case 'games':
        getGames();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found: ' . $endpoint]);
        break;
}

/**
 * Get AI Tools
 */
function getAiTools() {
    global $db;

    try {
        // Check if ai_tools table exists
        $stmt = $db->query("SHOW TABLES LIKE 'ai_tools'");
        if ($stmt->rowCount() === 0) {
            // Return empty array if table doesn't exist
            echo json_encode([]);
            return;
        }

        // Get AI tools
        $query = "
            SELECT a.id,
                   a.title as name,
                   a.description,
                   a.tool_url as url,
                   a.pricing_type,
                   a.price_info,
                   a.features,
                   a.image,
                   a.rating,
                   a.featured,
                   a.is_published as isPublished,
                   a.slug,
                   c.name as category
            FROM ai_tools a
            LEFT JOIN ai_tool_categories c ON a.category_id = c.id
            ORDER BY a.created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $ai_tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($ai_tools);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Get Directory Items
 */
function getDirectoryItems() {
    global $db;

    try {
        // Check if directory_items table exists
        $stmt = $db->query("SHOW TABLES LIKE 'directory_items'");
        if ($stmt->rowCount() === 0) {
            // Return empty array if table doesn't exist
            echo json_encode([]);
            return;
        }

        // Get directory items
        $query = "
            SELECT d.id,
                   d.title as name,
                   d.description,
                   d.website_url as url,
                   d.contact_email,
                   d.contact_phone,
                   d.address,
                   d.image,
                   d.featured,
                   d.is_published as isPublished,
                   d.slug,
                   c.name as category
            FROM directory_items d
            LEFT JOIN directory_categories c ON d.category_id = c.id
            ORDER BY d.created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $directory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($directory_items);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Get Games
 */
function getGames() {
    global $db;

    try {
        // Check if games table exists
        $stmt = $db->query("SHOW TABLES LIKE 'games'");
        if ($stmt->rowCount() === 0) {
            // Return empty array if table doesn't exist
            echo json_encode([]);
            return;
        }

        // Get games
        $query = "
            SELECT id,
                   title as name,
                   description,
                   slug,
                   image,
                   featured,
                   is_published as isPublished
            FROM games
            ORDER BY created_at DESC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($games);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
