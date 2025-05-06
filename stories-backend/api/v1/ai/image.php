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

// Include CORS fix
require_once 'cors-fix.php';

// Set content type to JSON
header('Content-Type: application/json');

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

// Try to include database connection
$dbConnected = false;

// Check multiple possible paths for db-connect.php
$possiblePaths = [
    '../../includes/db-connect.php',
    '../../../includes/db-connect.php',
    '../../../../includes/db-connect.php',
    '../includes/db-connect.php'
];

$dbConnectPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $dbConnectPath = $path;
        break;
    }
}

if ($dbConnectPath) {
    require_once $dbConnectPath;
    $dbConnected = true;
} else {
    // Try a direct include to the known location
    try {
        require_once '../../../includes/db-connect.php';
        $dbConnected = true;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection file not found. Please check server configuration.'
        ]);
        exit;
    }
}

try {
    // Initialize variables
    $providerId = null;
    $apiKey = null;
    $organization = null;
    $model = 'gpt-image-1';

    // Try to get OpenAI provider configuration from database
    if ($dbConnected && isset($db)) {
        try {
            $stmt = $db->prepare("SELECT id, config FROM ai_providers WHERE name = 'openai' AND is_active = 1");
            $stmt->execute();
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $providerId = $provider['id'];
                $config = json_decode($provider['config'], true);

                // Log provider config for debugging (without API key)
                $logConfig = $config;
                if (isset($logConfig['api_key'])) {
                    $logConfig['api_key'] = substr($logConfig['api_key'], 0, 3) . '...' . substr($logConfig['api_key'], -3);
                }
                error_log("OpenAI provider config from DB: " . json_encode($logConfig));

                if (!empty($config['api_key'])) {
                    $apiKey = $config['api_key'];
                    $organization = $config['organization'] ?? null;
                    $model = $config['model'] ?? 'gpt-image-1';
                }
            }
        } catch (Exception $dbEx) {
            error_log("Error fetching OpenAI provider from database: " . $dbEx->getMessage());
            // Continue with fallback
        }
    }

    // If we couldn't get the API key from the database, check for environment variable
    if (empty($apiKey)) {
        $apiKey = getenv('OPENAI_API_KEY');
        error_log("Using OPENAI_API_KEY from environment: " . (empty($apiKey) ? 'Not found' : 'Found'));
    }

    // If we still don't have an API key, throw an exception
    if (empty($apiKey)) {
        error_log("OpenAI API key not configured");
        throw new Exception('OpenAI API key not configured. Please go to AI Settings to add your API key or set the OPENAI_API_KEY environment variable.');
    }

    // Set up request parameters
    $size = $data['size'] ?? '1024x1024';
    $variations = min(max((int)($data['variations'] ?? 1), 1), 4); // Limit to 1-4 variations

    // Map quality values to those supported by the API
    $qualityMap = [
        'standard' => 'medium',
        'hd' => 'high',
        'high' => 'high',
        'medium' => 'medium',
        'low' => 'low',
        'auto' => 'auto'
    ];
    $requestedQuality = $data['quality'] ?? 'medium';
    $quality = $qualityMap[$requestedQuality] ?? 'medium';

    // Log quality mapping for debugging
    error_log("Requested quality: $requestedQuality, Mapped to: $quality");

    // 'style' parameter removed as it's no longer supported by the API

    // Log any style parameter for debugging
    if (isset($data['style'])) {
        error_log("Style parameter '" . $data['style'] . "' was provided but will be ignored as it's no longer supported by the OpenAI API");
    }

    // Prepare OpenAI API request
    $openaiData = [
        'model' => $model,
        'prompt' => $data['prompt'],
        'n' => $variations,
        'size' => $size,
        'quality' => $quality
        // 'style' parameter removed as it's no longer supported
        // 'response_format' parameter removed as it's no longer supported
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
        ],
        CURLOPT_SSL_VERIFYPEER => false, // For development only
        CURLOPT_VERBOSE => true // Enable verbose output
    ]);

    // Create a file handle for the verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    // Get verbose information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);

    // Log the request and response for debugging
    error_log("OpenAI API Request: " . json_encode($openaiData));
    error_log("OpenAI API Response Status: " . $statusCode);
    error_log("OpenAI API Response: " . $response);
    if ($error) {
        error_log("cURL Error: " . $error);
    }
    error_log("Verbose Log: " . $verboseLog);

    curl_close($ch);

    if ($error) {
        throw new Exception('API request failed: ' . $error . ' - ' . $verboseLog);
    }

    if ($statusCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
        throw new Exception('API error: ' . $errorMessage . ' - ' . $verboseLog);
    }

    // Parse response
    $result = json_decode($response, true);

    // Log the full response for debugging
    error_log("Parsed OpenAI API Response: " . json_encode($result));

    // Handle different response formats from OpenAI API
    if (isset($result['data']) && is_array($result['data'])) {
        // Standard format
        $urls = array_map(function($image) {
            return $image['url'] ?? $image['b64_json'] ?? null;
        }, $result['data']);
    } elseif (isset($result['url'])) {
        // Single URL format
        $urls = [$result['url']];
    } elseif (isset($result['urls']) && is_array($result['urls'])) {
        // Array of URLs format
        $urls = $result['urls'];
    } elseif (isset($result['images']) && is_array($result['images'])) {
        // Array of images format
        $urls = array_map(function($image) {
            return $image['url'] ?? $image['b64_json'] ?? null;
        }, $result['images']);
    } else {
        error_log("Unexpected OpenAI API response format: " . json_encode($result));
        throw new Exception('Invalid response format from OpenAI API');
    }

    if (empty($urls)) {
        throw new Exception('No images generated');
    }

    // Record generation in database if database is connected
    if ($dbConnected && isset($db) && $providerId) {
        try {
            $metadata = json_encode([
                'model' => $model,
                'size' => $size,
                'variations' => $variations,
                'quality' => $quality
                // 'style' parameter removed as it's no longer supported
            ]);

            $stmt = $db->prepare("
                INSERT INTO ai_generations (provider_id, type, prompt, result_url, metadata, status)
                VALUES (?, 'image', ?, ?, ?, 'completed')
            ");
            $stmt->execute([$providerId, $data['prompt'], $urls[0], $metadata]);
            $generationId = $db->lastInsertId();

            // Record usage
            $cost = calculateImageGenerationCost($model, $size, $quality, $variations);
            $stmt = $db->prepare("
                INSERT INTO ai_usage (provider_id, type, cost)
                VALUES (?, 'image', ?)
            ");
            $stmt->execute([$providerId, $cost]);
        } catch (Exception $dbEx) {
            // Log error but continue with response
            error_log("Error recording generation in database: " . $dbEx->getMessage());
        }
    } else {
        error_log("Skipping database recording - database not connected or provider ID not available");
    }

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
