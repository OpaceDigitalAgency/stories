<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if (!$data || !isset($data['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit();
}

try {
    // Include necessary files
    require_once '../../lib/ai/core/Config.php';
    require_once '../../lib/ai/core/Provider.php';
    require_once '../../lib/ai/core/Response.php';
    require_once '../../lib/ai/providers/OpenAIProvider.php';
    require_once '../../lib/ai/services/ImageService.php';

    // Initialize image service
    $imageService = new \Stories\Lib\AI\Services\ImageService();

    // Extract request data
    $prompt = $data['prompt'];
    $options = [
        'size' => $data['size'] ?? '1024x1024',
        'quality' => $data['quality'] ?? 'standard',
        'variations' => $data['variations'] ?? 1
    ];

    // Rate limiting check
    $config = \Stories\Lib\AI\Core\Config::getInstance();
    $rateLimit = $config->get('general.rate_limit', 60); // Requests per minute
    
    if (!checkRateLimit($rateLimit)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit();
    }

    // Generate image
    $response = $imageService->generateImage($prompt, $options);

    // Return response
    http_response_code($response->isSuccess() ? 200 : 500);
    echo json_encode($response->toArray());

} catch (Exception $e) {
    error_log("Image generation API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Check if request is within rate limit
 * 
 * @param int $limit Requests per minute limit
 * @return bool Within limit status
 */
function checkRateLimit(int $limit): bool {
    try {
        $db = new PDO(
            "mysql:host=localhost;dbname=stories_db;charset=utf8mb4",
            "stories_user",
            '$tw1cac3+sOt'
        );

        // Clean up old requests
        $db->exec("DELETE FROM ai_rate_limit WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)");

        // Count recent requests
        $stmt = $db->prepare("SELECT COUNT(*) FROM ai_rate_limit WHERE ip_address = ?");
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);
        $count = $stmt->fetchColumn();

        if ($count >= $limit) {
            return false;
        }

        // Log new request
        $stmt = $db->prepare("INSERT INTO ai_rate_limit (ip_address) VALUES (?)");
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);

        return true;

    } catch (PDOException $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return true; // Allow request if rate limiting fails
    }
}