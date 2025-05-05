<?php
/**
 * AI Text Generation API Endpoint
 * 
 * This endpoint handles text generation requests using the configured AI provider.
 * 
 * Request method: POST
 * Request format: JSON
 * 
 * Required parameters:
 * - prompt: The text prompt for generation
 * 
 * Optional parameters:
 * - max_tokens: Maximum number of tokens to generate (default: 2000)
 * - temperature: Randomness of the generation (0-2, default: 0.7)
 * - model: The model to use (default: from provider config)
 * 
 * Response format: JSON
 * {
 *   "success": true|false,
 *   "data": {
 *     "text": "Generated text",
 *     "tokens": 123
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
    
    // Set up request parameters
    $maxTokens = min(max((int)($data['max_tokens'] ?? $config['max_tokens'] ?? 2000), 1), 4000);
    $temperature = min(max((float)($data['temperature'] ?? $config['temperature'] ?? 0.7), 0), 2);
    $model = $data['model'] ?? $config['text_model'] ?? 'gpt-4o';
    
    // Prepare OpenAI API request
    $openaiData = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $data['prompt']
            ]
        ],
        'max_tokens' => $maxTokens,
        'temperature' => $temperature
    ];
    
    // Set up cURL request
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
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
    
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid response from OpenAI API');
    }
    
    // Extract generated text
    $generatedText = $result['choices'][0]['message']['content'];
    $tokensUsed = $result['usage']['total_tokens'] ?? 0;
    
    // Record generation in database
    $stmt = $db->prepare("
        INSERT INTO ai_generations (provider_id, type, prompt, metadata, status)
        VALUES (?, 'text', ?, ?, 'completed')
    ");
    
    $metadata = json_encode([
        'model' => $model,
        'max_tokens' => $maxTokens,
        'temperature' => $temperature,
        'tokens_used' => $tokensUsed
    ]);
    
    $stmt->execute([$providerId, $data['prompt'], $metadata]);
    $generationId = $db->lastInsertId();
    
    // Record usage
    $cost = calculateTextGenerationCost($model, $tokensUsed);
    $stmt = $db->prepare("
        INSERT INTO ai_usage (provider_id, type, cost, tokens)
        VALUES (?, 'text', ?, ?)
    ");
    $stmt->execute([$providerId, $cost, $tokensUsed]);
    
    // Prepare response
    $responseData = [
        'success' => true,
        'data' => [
            'text' => $generatedText,
            'tokens' => $tokensUsed
        ]
    ];
    
    // Return success response
    echo json_encode($responseData);
    
} catch (Exception $e) {
    // Log error
    error_log('AI Text Generation Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Calculate the cost of text generation based on model and tokens used
 * 
 * @param string $model The model used for generation
 * @param int $tokens The number of tokens used
 * @return float The cost in USD
 */
function calculateTextGenerationCost($model, $tokens) {
    // Cost per 1000 tokens for different models
    $costPer1000Tokens = [
        'gpt-4.1' => 0.01,
        'gpt-4o' => 0.005,
        'o4-mini' => 0.0015,
        'o3' => 0.003,
        'o3-mini' => 0.0015
    ];
    
    // Get cost per 1000 tokens for the model
    $costPer1000 = $costPer1000Tokens[$model] ?? 0.005;
    
    // Calculate total cost
    return ($tokens / 1000) * $costPer1000;
}
