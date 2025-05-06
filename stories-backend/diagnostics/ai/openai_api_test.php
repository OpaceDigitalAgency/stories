<?php
/**
 * OpenAI API Test Tool
 *
 * This diagnostic tool tests the connection to the OpenAI API and displays detailed results.
 * It helps diagnose issues with AI image generation and other OpenAI API features.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = 'OpenAI API Test';

// Function to mask API key
function maskApiKey($key) {
    if (empty($key)) return 'Not set';
    $length = strlen($key);
    if ($length <= 8) return '********';
    return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
}

// Include database connection
require_once '../../includes/db-connect.php';

// Get OpenAI provider configuration
$provider = null;
$config = [];
$apiKey = '';
$organization = '';
$model = '';

try {
    $stmt = $db->prepare("SELECT id, config FROM ai_providers WHERE name = 'openai' AND is_active = 1");
    $stmt->execute();
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($provider) {
        $config = json_decode($provider['config'], true);
        $apiKey = $config['api_key'] ?? '';
        $organization = $config['organization'] ?? '';
        $model = $config['model'] ?? 'gpt-image-1';
    }
} catch (Exception $e) {
    $error = 'Error fetching OpenAI configuration: ' . $e->getMessage();
}

// Test OpenAI API connection
$apiTestResult = null;
$imageTestResult = null;

if (!empty($apiKey)) {
    // Test models endpoint
    try {
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                !empty($organization) ? 'OpenAI-Organization: ' . $organization : null
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_VERBOSE => true,
            CURLOPT_SSL_VERIFYPEER => false // For development only
        ]);

        // Create a file handle for the verbose output
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // Get verbose information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);

        curl_close($ch);

        $apiTestResult = [
            'success' => $statusCode === 200,
            'status_code' => $statusCode,
            'error' => $error ?: null,
            'verbose_log' => $verboseLog,
            'response' => $response
        ];
    } catch (Exception $e) {
        $apiTestResult = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }

    // Test image generation endpoint
    try {
        $data = [
            'model' => $model,
            'prompt' => 'A simple test image of a blue circle on a white background',
            'n' => 1,
            'size' => '1024x1024'
            // 'response_format' parameter removed as it's no longer supported
        ];

        $ch = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                !empty($organization) ? 'OpenAI-Organization: ' . $organization : null
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_VERBOSE => true,
            CURLOPT_SSL_VERIFYPEER => false // For development only
        ]);

        // Create a file handle for the verbose output
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // Get verbose information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);

        curl_close($ch);

        $imageTestResult = [
            'success' => $statusCode === 200,
            'status_code' => $statusCode,
            'error' => $error ?: null,
            'verbose_log' => $verboseLog,
            'response' => $response
        ];
    } catch (Exception $e) {
        $imageTestResult = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Stories from the Web</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            overflow: auto;
            max-height: 300px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .test-image-container {
            text-align: center;
            margin: 1rem 0;
        }
        .test-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .back-link {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="../../diagnostic-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Diagnostic Dashboard
            </a>
        </div>

        <h1><?php echo $pageTitle; ?></h1>
        <p class="lead">This tool tests the connection to the OpenAI API and diagnoses any issues with AI image generation.</p>

        <!-- Configuration Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>OpenAI Configuration</h3>
            </div>
            <div class="card-body">
                <?php if ($provider): ?>
                    <table class="table">
                        <tr>
                            <th>Provider ID</th>
                            <td><?php echo htmlspecialchars($provider['id']); ?></td>
                        </tr>
                        <tr>
                            <th>API Key</th>
                            <td><?php echo maskApiKey($apiKey); ?></td>
                        </tr>
                        <tr>
                            <th>Organization</th>
                            <td><?php echo !empty($organization) ? htmlspecialchars($organization) : '<em>Not set</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>Default Model</th>
                            <td><?php echo !empty($model) ? htmlspecialchars($model) : '<em>Not set</em>'; ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        OpenAI provider not found or not active. Please configure it in
                        <a href="../../admin/content/ai-settings.php">AI Settings</a>.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- API Test Results -->
        <?php if ($apiTestResult): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Models API Test</h3>
                </div>
                <div class="card-body">
                    <?php if ($apiTestResult['success']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Connection to OpenAI Models API successful!
                        </div>

                        <h4>Response Preview</h4>
                        <pre class="code-block"><?php
                            $responseData = json_decode($apiTestResult['response'], true);
                            echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        ?></pre>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Connection to OpenAI Models API failed!
                        </div>

                        <h4>Error Details</h4>
                        <table class="table">
                            <tr>
                                <th>Status Code</th>
                                <td><?php echo htmlspecialchars($apiTestResult['status_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Error</th>
                                <td><?php echo htmlspecialchars($apiTestResult['error'] ?? 'None'); ?></td>
                            </tr>
                        </table>

                        <?php if (!empty($apiTestResult['response'])): ?>
                            <h4>Response</h4>
                            <pre class="code-block"><?php
                                $responseData = json_decode($apiTestResult['response'], true);
                                echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            ?></pre>
                        <?php endif; ?>

                        <h4>Verbose Log</h4>
                        <pre class="code-block"><?php echo htmlspecialchars($apiTestResult['verbose_log'] ?? 'No log available'); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Image Generation Test Results -->
        <?php if ($imageTestResult): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Image Generation API Test</h3>
                </div>
                <div class="card-body">
                    <?php if ($imageTestResult['success']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Connection to OpenAI Image Generation API successful!
                        </div>

                        <?php
                        $responseData = json_decode($imageTestResult['response'], true);
                        $imageUrl = null;

                        if (isset($responseData['data'][0]['url'])) {
                            $imageUrl = $responseData['data'][0]['url'];
                        } elseif (isset($responseData['url'])) {
                            $imageUrl = $responseData['url'];
                        }

                        if ($imageUrl):
                        ?>
                            <h4>Generated Test Image</h4>
                            <div class="test-image-container">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Test image" class="test-image">
                            </div>
                        <?php endif; ?>

                        <h4>Response Preview</h4>
                        <pre class="code-block"><?php
                            echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        ?></pre>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Connection to OpenAI Image Generation API failed!
                        </div>

                        <h4>Error Details</h4>
                        <table class="table">
                            <tr>
                                <th>Status Code</th>
                                <td><?php echo htmlspecialchars($imageTestResult['status_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Error</th>
                                <td><?php echo htmlspecialchars($imageTestResult['error'] ?? 'None'); ?></td>
                            </tr>
                        </table>

                        <?php if (!empty($imageTestResult['response'])): ?>
                            <h4>Response</h4>
                            <pre class="code-block"><?php
                                $responseData = json_decode($imageTestResult['response'], true);
                                echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            ?></pre>
                        <?php endif; ?>

                        <h4>Verbose Log</h4>
                        <pre class="code-block"><?php echo htmlspecialchars($imageTestResult['verbose_log'] ?? 'No log available'); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="card">
            <div class="card-header">
                <h3>System Information</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>PHP Version</th>
                        <td><?php echo htmlspecialchars(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>cURL Version</th>
                        <td><?php echo htmlspecialchars(curl_version()['version'] ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th>SSL Version</th>
                        <td><?php echo htmlspecialchars(curl_version()['ssl_version'] ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th>Server Software</th>
                        <td><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th>Database Driver</th>
                        <td><?php echo htmlspecialchars($db->getAttribute(PDO::ATTR_DRIVER_NAME) ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th>Database Version</th>
                        <td><?php echo htmlspecialchars($db->getAttribute(PDO::ATTR_SERVER_VERSION) ?? 'Unknown'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
