<?php
/**
 * AI API Debug Endpoint
 *
 * This endpoint provides debugging information for the AI API.
 * It tests the connection to the OpenAI API and returns diagnostic information.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set content type to JSON
header('Content-Type: application/json');

// Handle CORS manually in case the cors-fix.php file is missing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Try to include CORS fix
if (file_exists('cors-fix.php')) {
    require_once 'cors-fix.php';
}

// Try to include database connection
$db = null;
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

try {
    if ($dbConnectPath) {
        require_once $dbConnectPath;
        $dbConnected = true;
    } else {
        // Try a direct include to the known location
        try {
            require_once '../../../includes/db-connect.php';
            $dbConnected = true;
        } catch (Exception $e2) {
            // Silently fail and continue with diagnostics
        }
    }
} catch (Exception $e) {
    // Silently fail and continue with diagnostics
}

try {
    // Initialize variables
    $provider = false;

    // Get OpenAI provider configuration if database is connected
    if ($dbConnected && $db) {
        try {
            $stmt = $db->prepare("SELECT id, config FROM ai_providers WHERE name = 'openai' AND is_active = 1");
            $stmt->execute();
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $dbEx) {
            // Continue with diagnostics even if database query fails
        }
    }

    $diagnostics = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'curl_version' => curl_version()['version'] ?? 'Unknown',
            'ssl_version' => curl_version()['ssl_version'] ?? 'Unknown'
        ],
        'openai_provider' => [
            'found' => $provider !== false,
            'active' => $provider !== false && $provider['id'] > 0,
        ],
        'database' => [
            'connected' => $dbConnected && $db !== null,
            'driver' => ($dbConnected && $db) ? $db->getAttribute(PDO::ATTR_DRIVER_NAME) : 'Unknown',
            'version' => ($dbConnected && $db) ? $db->getAttribute(PDO::ATTR_SERVER_VERSION) : 'Unknown',
            'db_connect_path' => $dbConnectPath,
            'db_connect_paths_checked' => $possiblePaths
        ],
        'cors' => [
            'headers_set' => true,
            'headers' => [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
            ]
        ]
    ];

    if ($provider) {
        $config = json_decode($provider['config'], true);

        // Mask API key for security
        $apiKeyStatus = 'Not set';
        if (!empty($config['api_key'])) {
            $apiKeyLength = strlen($config['api_key']);
            $apiKeyStatus = 'Set (' . $apiKeyLength . ' characters)';

            // Add first and last few characters
            if ($apiKeyLength > 8) {
                $apiKeyStatus .= ' - ' . substr($config['api_key'], 0, 3) . '...' . substr($config['api_key'], -3);
            }
        }

        $diagnostics['openai_provider']['details'] = [
            'id' => $provider['id'],
            'api_key_status' => $apiKeyStatus,
            'model' => $config['model'] ?? 'Not set',
            'organization' => !empty($config['organization']) ? 'Set' : 'Not set'
        ];

        // Test connection to OpenAI API
        if (!empty($config['api_key'])) {
            $ch = curl_init('https://api.openai.com/v1/models');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config['api_key'],
                    !empty($config['organization']) ? 'OpenAI-Organization: ' . $config['organization'] : null
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false // For development only
            ]);

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $diagnostics['openai_api_test'] = [
                'success' => $statusCode === 200,
                'status_code' => $statusCode,
                'error' => $error ?: null,
                'response_preview' => $statusCode === 200 ? substr($response, 0, 100) . '...' : null
            ];
        } else {
            $diagnostics['openai_api_test'] = [
                'success' => false,
                'error' => 'API key not configured'
            ];
        }
    }

    // Add file existence checks
    $diagnostics['files'] = [
        'cors_fix_exists' => file_exists('cors-fix.php'),
        'image_php_exists' => file_exists('image.php'),
        'debug_php_exists' => file_exists('debug.php')
    ];

    // Check if the image.php endpoint is accessible
    // Use relative path to avoid CORS issues
    $host = $_SERVER['HTTP_HOST'] ?? 'api.storiesfromtheweb.org';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl = "$protocol://$host";

    $ch = curl_init("$baseUrl/api/v1/ai/image.php");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'OPTIONS',
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false, // For development only
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $diagnostics['image_endpoint_test'] = [
        'success' => $statusCode === 200,
        'status_code' => $statusCode,
        'error' => $error ?: null
    ];

    // Return diagnostics
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
