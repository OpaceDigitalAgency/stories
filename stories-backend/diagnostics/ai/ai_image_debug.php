<?php
/**
 * AI Image Generation Debug Tool
 *
 * This diagnostic tool helps debug issues with AI image generation.
 * It tests the API endpoint, CORS settings, and provides detailed error information.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = 'AI Image Generation Debug';

// Include database connection
require_once '../../includes/db-connect.php';

// Get OpenAI provider configuration
$provider = null;
$config = [];
$apiKey = '';

try {
    $stmt = $db->prepare("SELECT id, config FROM ai_providers WHERE name = 'openai' AND is_active = 1");
    $stmt->execute();
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($provider) {
        $config = json_decode($provider['config'], true);
        $apiKey = $config['api_key'] ?? '';
    }
} catch (Exception $e) {
    $error = 'Error fetching OpenAI configuration: ' . $e->getMessage();
}

// Test API endpoint
$endpointTest = null;
try {
    $ch = curl_init('https://api.storiesfromtheweb.org/api/v1/ai/debug.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false // For development only
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $endpointTest = [
        'success' => $statusCode === 200,
        'status_code' => $statusCode,
        'error' => $error ?: null,
        'response' => $response
    ];
} catch (Exception $e) {
    $endpointTest = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test CORS settings
$corsTest = null;
try {
    $ch = curl_init('https://api.storiesfromtheweb.org/api/v1/ai/image.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'OPTIONS',
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_SSL_VERIFYPEER => false // For development only
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $corsTest = [
        'success' => $statusCode === 200,
        'status_code' => $statusCode,
        'error' => $error ?: null,
        'response' => $response
    ];
} catch (Exception $e) {
    $corsTest = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Check for .htaccess file
$htaccessExists = file_exists('../../api/v1/ai/.htaccess');
$htaccessContent = $htaccessExists ? file_get_contents('../../api/v1/ai/.htaccess') : '';

// Check for CORS fix file
$corsFixExists = file_exists('../../api/v1/ai/cors-fix.php');
$corsFixContent = $corsFixExists ? file_get_contents('../../api/v1/ai/cors-fix.php') : '';

// Check image.php file
$imagePhpExists = file_exists('../../api/v1/ai/image.php');
$imagePhpIncludesCors = false;
if ($imagePhpExists) {
    $imagePhpContent = file_get_contents('../../api/v1/ai/image.php');
    $imagePhpIncludesCors = strpos($imagePhpContent, "require_once 'cors-fix.php'") !== false;
}

// Process form submission
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_image_generation'])) {
    try {
        $data = [
            'prompt' => $_POST['prompt'],
            'size' => $_POST['size'],
            'quality' => $_POST['quality'] ?? 'standard',
            'variations' => (int)$_POST['variations']
            // 'style' parameter removed as it's no longer supported by the OpenAI API
        ];

        $ch = curl_init($_POST['endpoint_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
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

        $testResult = [
            'success' => $statusCode === 200,
            'status_code' => $statusCode,
            'error' => $error ?: null,
            'verbose_log' => $verboseLog,
            'response' => $response
        ];
    } catch (Exception $e) {
        $testResult = [
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
        .file-status {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .file-exists {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .file-missing {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
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
        <p class="lead">This tool helps debug issues with AI image generation by testing the API endpoint, CORS settings, and providing detailed error information.</p>

        <!-- Configuration Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Configuration Status</h3>
            </div>
            <div class="card-body">
                <h4>OpenAI Provider</h4>
                <?php if ($provider): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        OpenAI provider is configured (ID: <?php echo htmlspecialchars($provider['id']); ?>)
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        OpenAI provider is not configured or not active
                    </div>
                <?php endif; ?>

                <h4>API Key</h4>
                <?php if (!empty($apiKey)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        API key is set (<?php echo strlen($apiKey); ?> characters)
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        API key is not set
                    </div>
                <?php endif; ?>

                <h4>Required Files</h4>
                <div class="file-status <?php echo $htaccessExists ? 'file-exists' : 'file-missing'; ?>">
                    <i class="fas <?php echo $htaccessExists ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    .htaccess file in api/v1/ai directory: <?php echo $htaccessExists ? 'Exists' : 'Missing'; ?>
                </div>

                <div class="file-status <?php echo $corsFixExists ? 'file-exists' : 'file-missing'; ?>">
                    <i class="fas <?php echo $corsFixExists ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    cors-fix.php file in api/v1/ai directory: <?php echo $corsFixExists ? 'Exists' : 'Missing'; ?>
                </div>

                <div class="file-status <?php echo $imagePhpExists ? 'file-exists' : 'file-missing'; ?>">
                    <i class="fas <?php echo $imagePhpExists ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    image.php file in api/v1/ai directory: <?php echo $imagePhpExists ? 'Exists' : 'Missing'; ?>
                </div>

                <?php if ($imagePhpExists): ?>
                <div class="file-status <?php echo $imagePhpIncludesCors ? 'file-exists' : 'file-missing'; ?>">
                    <i class="fas <?php echo $imagePhpIncludesCors ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    image.php includes cors-fix.php: <?php echo $imagePhpIncludesCors ? 'Yes' : 'No'; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- API Endpoint Test -->
        <?php if ($endpointTest): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>API Endpoint Test</h3>
                </div>
                <div class="card-body">
                    <?php if ($endpointTest['success']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            API endpoint is accessible
                        </div>

                        <h4>Response</h4>
                        <pre class="code-block"><?php
                            $responseData = json_decode($endpointTest['response'], true);
                            echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        ?></pre>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            API endpoint is not accessible
                        </div>

                        <h4>Error Details</h4>
                        <table class="table">
                            <tr>
                                <th>Status Code</th>
                                <td><?php echo htmlspecialchars($endpointTest['status_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Error</th>
                                <td><?php echo htmlspecialchars($endpointTest['error'] ?? 'None'); ?></td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- CORS Test -->
        <?php if ($corsTest): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>CORS Test</h3>
                </div>
                <div class="card-body">
                    <?php if ($corsTest['success']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            CORS is properly configured
                        </div>

                        <h4>Response Headers</h4>
                        <pre class="code-block"><?php echo htmlspecialchars($corsTest['response']); ?></pre>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            CORS is not properly configured
                        </div>

                        <h4>Error Details</h4>
                        <table class="table">
                            <tr>
                                <th>Status Code</th>
                                <td><?php echo htmlspecialchars($corsTest['status_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Error</th>
                                <td><?php echo htmlspecialchars($corsTest['error'] ?? 'None'); ?></td>
                            </tr>
                        </table>

                        <?php if (!empty($corsTest['response'])): ?>
                            <h4>Response</h4>
                            <pre class="code-block"><?php echo htmlspecialchars($corsTest['response']); ?></pre>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Manual Test Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Manual Image Generation Test</h3>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="endpoint_url" class="form-label">API Endpoint URL</label>
                        <input type="text" class="form-control" id="endpoint_url" name="endpoint_url" value="https://api.storiesfromtheweb.org/api/v1/ai/image.php" required>
                    </div>

                    <div class="mb-3">
                        <label for="prompt" class="form-label">Image Prompt</label>
                        <textarea class="form-control" id="prompt" name="prompt" rows="3" required>A simple test image of a blue circle on a white background</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="size" class="form-label">Image Size</label>
                            <select class="form-select" id="size" name="size">
                                <option value="1024x1024" selected>Square (1024x1024)</option>
                                <option value="1024x1792">Portrait (1024x1792)</option>
                                <option value="1792x1024">Landscape (1792x1024)</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="quality" class="form-label">Quality</label>
                            <select class="form-select" id="quality" name="quality">
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="low">Low</option>
                                <option value="auto">Auto</option>
                            </select>
                            <small class="form-text text-muted">Valid values: 'low', 'medium', 'high', 'auto'</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="variations" class="form-label">Variations</label>
                            <select class="form-select" id="variations" name="variations">
                                <option value="1" selected>1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="test_image_generation" class="btn btn-primary">
                        <i class="fas fa-vial"></i> Test Image Generation
                    </button>
                </form>

                <?php if ($testResult): ?>
                    <hr>
                    <h4>Test Results</h4>

                    <?php if ($testResult['success']): ?>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-check-circle"></i>
                            Image generation test successful!
                        </div>

                        <?php
                        $responseData = json_decode($testResult['response'], true);
                        $imageUrl = null;
                        $imageBase64 = null;
                        $imageType = null;

                        // Check for different response formats
                        if (isset($responseData['data']['type']) && $responseData['data']['type'] === 'base64') {
                            $imageBase64 = $responseData['data']['data'];
                            $imageType = 'base64';
                        } elseif (isset($responseData['data']['type']) && $responseData['data']['type'] === 'url') {
                            $imageUrl = $responseData['data']['data'];
                            $imageType = 'url';
                        } elseif (isset($responseData['data']['url'])) {
                            $imageUrl = $responseData['data']['url'];
                            $imageType = 'url';
                        } elseif (isset($responseData['url'])) {
                            $imageUrl = $responseData['url'];
                            $imageType = 'url';
                        } elseif (isset($responseData['data']['data'])) {
                            // Assume it's base64 if we can't determine the type
                            $imageBase64 = $responseData['data']['data'];
                            $imageType = 'base64';
                        }

                        if ($imageUrl || $imageBase64):
                        ?>
                            <div class="test-image-container">
                                <?php if ($imageType === 'url'): ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Generated test image" class="test-image">
                                <?php elseif ($imageType === 'base64'): ?>
                                    <img src="data:image/png;base64,<?php echo $imageBase64; ?>" alt="Generated test image" class="test-image">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <h5>Response</h5>
                        <pre class="code-block"><?php
                            echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        ?></pre>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-circle"></i>
                            Image generation test failed!
                        </div>

                        <h5>Error Details</h5>
                        <table class="table">
                            <tr>
                                <th>Status Code</th>
                                <td><?php echo htmlspecialchars($testResult['status_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Error</th>
                                <td><?php echo htmlspecialchars($testResult['error'] ?? 'None'); ?></td>
                            </tr>
                        </table>

                        <?php if (!empty($testResult['response'])): ?>
                            <h5>Response</h5>
                            <pre class="code-block"><?php
                                $responseData = json_decode($testResult['response'], true);
                                if ($responseData) {
                                    echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                } else {
                                    echo htmlspecialchars($testResult['response']);
                                }
                            ?></pre>
                        <?php endif; ?>

                        <h5>Verbose Log</h5>
                        <pre class="code-block"><?php echo htmlspecialchars($testResult['verbose_log'] ?? 'No log available'); ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- File Contents -->
        <div class="card">
            <div class="card-header">
                <h3>File Contents</h3>
            </div>
            <div class="card-body">
                <div class="accordion" id="fileContentsAccordion">
                    <?php if ($htaccessExists): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="htaccessHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#htaccessCollapse" aria-expanded="false" aria-controls="htaccessCollapse">
                                    .htaccess
                                </button>
                            </h2>
                            <div id="htaccessCollapse" class="accordion-collapse collapse" aria-labelledby="htaccessHeading" data-bs-parent="#fileContentsAccordion">
                                <div class="accordion-body">
                                    <pre class="code-block"><?php echo htmlspecialchars($htaccessContent); ?></pre>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($corsFixExists): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="corsFixHeading">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#corsFixCollapse" aria-expanded="false" aria-controls="corsFixCollapse">
                                    cors-fix.php
                                </button>
                            </h2>
                            <div id="corsFixCollapse" class="accordion-collapse collapse" aria-labelledby="corsFixHeading" data-bs-parent="#fileContentsAccordion">
                                <div class="accordion-body">
                                    <pre class="code-block"><?php echo htmlspecialchars($corsFixContent); ?></pre>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
