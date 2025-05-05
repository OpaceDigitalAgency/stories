<?php
/**
 * AI Image Generation API Endpoint
 * 
 * This endpoint handles image generation requests using the configured AI provider.
 * 
 * Request method: POST
 * Request format: JSON
 * 
 * Required parameters:
 * - prompt: The text description of the image to generate
 * 
 * Optional parameters:
 * - size: Image size (default: 1024x1024)
 * - style: Image style (default: natural)
 * - variations: Number of variations to generate (default: 1)
 * - quality: Image quality (default: standard)
 * 
 * Response format: JSON
 * {
 *   "success": true|false,
 *   "data": {
 *     "url": "https://example.com/image.jpg",
 *     "variations": ["https://example.com/variation1.jpg", ...]
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
if (!$data || !isset($data['prompt']) || empty($data['prompt'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request. Prompt is required.'
    ]);
    exit;
}

// Include database connection
require_once '../../includes/db-connect.php';

try {
    // Get OpenAI provider configuration
    $stmt = $db->prepare("SELECT id, config FROM ai_providers WHERE name = 'openai' AND is_active = 1");
    $stmt->execute();
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$provider) {
        throw new Exception('OpenAI provider not configured or not active.');
    }
    
    $providerId = $provider['id'];
    $config = json_decode($provider['config'], true);
    
    if (empty($config['api_key'])) {
        throw new Exception('OpenAI API key not configured.');
    }
    
    // Prepare request to OpenAI API
    $apiKey = $config['api_key'];
    $organization = $config['organization'] ?? null;
    $model = $config['model'] ?? 'gpt-image-1';
    
    // Set up request parameters
    $size = $data['size'] ?? '1024x1024';
    $style = $data['style'] ?? 'natural';
    $variations = min(max((int)($data['variations'] ?? 1), 1), 4); // Limit to 1-4 variations
    $quality = $data['quality'] ?? 'standard';
    
    // Prepare OpenAI API request
    $openaiData = [
        'model' => $model,
        'prompt' => $data['prompt'],
        'n' => $variations,
        'size' => $size,
        'quality' => $quality,
        'style' => $style,
        'response_format' => 'url'
    ];
    
    // Set up cURL request
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($openaiData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            $organization ? 'OpenAI-Organization: ' . $organization : null
        ]
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('API request failed: ' . $error);
    }
    
    if ($statusCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
        throw new Exception('API error: ' . $errorMessage);
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (!isset($result['data']) || !is_array($result['data'])) {
        throw new Exception('Invalid response from OpenAI API');
    }
    
    // Extract image URLs
    $urls = array_map(function($image) {
        return $image['url'];
    }, $result['data']);
    
    if (empty($urls)) {
        throw new Exception('No images generated');
    }
    
    // Record generation in database
    $stmt = $db->prepare("
        INSERT INTO ai_generations (provider_id, type, prompt, result_url, metadata, status)
        VALUES (?, 'image', ?, ?, ?, 'completed')
    ");
    
    $metadata = json_encode([
        'model' => $model,
        'size' => $size,
        'style' => $style,
        'variations' => $variations,
        'quality' => $quality
    ]);
    
    $stmt->execute([$providerId, $data['prompt'], $urls[0], $metadata]);
    $generationId = $db->lastInsertId();
    
    // Record usage
    $cost = calculateImageGenerationCost($model, $size, $quality, $variations);
    $stmt = $db->prepare("
        INSERT INTO ai_usage (provider_id, type, cost)
        VALUES (?, 'image', ?)
    ");
    $stmt->execute([$providerId, $cost]);
    
    // Prepare response
    $responseData = [
        'success' => true,
        'data' => [
            'url' => $urls[0]
        ]
    ];
    
    // Add variations if more than one image was generated
    if (count($urls) > 1) {
        $responseData['data']['variations'] = array_slice($urls, 1);
    }
    
    // Return success response
    echo json_encode($responseData);
    
} catch (Exception $e) {
    // Log error
    error_log('AI Image Generation Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Calculate the cost of image generation based on model, size, quality, and variations
 * 
 * @param string $model The model used for generation
 * @param string $size The size of the generated image
 * @param string $quality The quality of the generated image
 * @param int $variations The number of variations generated
 * @return float The cost in USD
 */
function calculateImageGenerationCost($model, $size, $quality, $variations) {
    // Base costs for different models and sizes
    $baseCosts = [
        'gpt-image-1' => [
            '1024x1024' => 0.04,
            '1024x1792' => 0.08,
            '1792x1024' => 0.08
        ],
        'dall-e-3' => [
            '1024x1024' => 0.04,
            '1024x1792' => 0.08,
            '1792x1024' => 0.08
        ],
        'dall-e-2' => [
            '1024x1024' => 0.02,
            '512x512' => 0.018,
            '256x256' => 0.016
        ]
    ];
    
    // Quality multiplier (HD costs more)
    $qualityMultiplier = ($quality === 'hd') ? 2 : 1;
    
    // Get base cost for model and size
    $baseCost = $baseCosts[$model][$size] ?? 0.04;
    
    // Calculate total cost
    return $baseCost * $qualityMultiplier * $variations;
}
