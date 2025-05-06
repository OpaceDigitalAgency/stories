<?php
/**
 * Test OpenAI API Key
 *
 * This script tests the OpenAI API key by making a simple request to the OpenAI API.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'Test OpenAI API Key';
$currentPage = 'ai-settings';
$pageDescription = 'Test your OpenAI API key to ensure it works correctly';

// Initialize variables
$apiKey = '';
$testResult = null;
$testError = null;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the API key from the form or database
        if (!empty($_POST['api_key'])) {
            $apiKey = $_POST['api_key'];
        } else {
            // Get API key from database
            $stmt = $db->prepare("SELECT config FROM ai_providers WHERE name = 'openai'");
            $stmt->execute();
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $config = json_decode($provider['config'], true);
                $apiKey = $config['api_key'] ?? '';
            }
        }

        if (empty($apiKey)) {
            throw new Exception('No API key provided. Please enter an API key or configure it in AI Settings.');
        }

        // Test the API key with a simple request to the models endpoint
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false // For development only
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('API request failed: ' . $error);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
            throw new Exception('API error: ' . $errorMessage . ' (HTTP code: ' . $httpCode . ')');
        }

        // Parse response
        $result = json_decode($response, true);

        if (!isset($result['data']) || !is_array($result['data'])) {
            throw new Exception('Invalid response from OpenAI API');
        }

        // Count available models
        $modelCount = count($result['data']);

        // Get a few model names for display
        $modelNames = array_slice(array_map(function($model) {
            return $model['id'];
        }, $result['data']), 0, 5);

        $testResult = [
            'success' => true,
            'message' => 'API key is valid! Found ' . $modelCount . ' available models.',
            'models' => $modelNames
        ];

        // If using a form-provided key, ask if user wants to save it
        if (!empty($_POST['api_key']) && !empty($_POST['save_key']) && $_POST['save_key'] === 'yes') {
            // Check if OpenAI provider exists
            $stmt = $db->prepare("SELECT * FROM ai_providers WHERE name = 'openai'");
            $stmt->execute();
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                // Update existing provider
                $config = json_decode($provider['config'], true) ?? [];
                $config['api_key'] = $apiKey;

                $stmt = $db->prepare("UPDATE ai_providers SET config = ? WHERE id = ?");
                $stmt->execute([json_encode($config), $provider['id']]);
            } else {
                // Create new provider
                $config = [
                    'api_key' => $apiKey,
                    'model' => 'gpt-image-1',
                    'text_model' => 'gpt-4o',
                    'max_tokens' => 2000,
                    'temperature' => 0.7
                ];

                $stmt = $db->prepare("INSERT INTO ai_providers (name, type, config, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute(['openai', 'image', json_encode($config), 1]);
            }

            $testResult['message'] .= ' API key has been saved to the database.';
        }

    } catch (Exception $e) {
        $testError = $e->getMessage();
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="ai-settings.php">AI Settings</a></li>
                        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Test OpenAI API Key</h3>
                </div>
                <div class="card-body">
                    <p>
                        This tool tests your OpenAI API key by making a simple request to the OpenAI API.
                        You can either enter an API key below or test the one already configured in your settings.
                    </p>

                    <?php if ($testResult): ?>
                        <div class="alert alert-success">
                            <h5><i class="icon fas fa-check"></i> Success!</h5>
                            <p><?php echo $testResult['message']; ?></p>
                            <?php if (!empty($testResult['models'])): ?>
                                <p>Sample available models:</p>
                                <ul>
                                    <?php foreach ($testResult['models'] as $model): ?>
                                        <li><?php echo htmlspecialchars($model); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($testError): ?>
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <p><?php echo $testError; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-group">
                            <label for="api_key">OpenAI API Key</label>
                            <input type="text" class="form-control" id="api_key" name="api_key" placeholder="Enter your OpenAI API key (or leave empty to test the configured key)">
                            <small class="form-text text-muted">
                                You can get an API key from <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a>
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="save_key" name="save_key" value="yes">
                                <label class="custom-control-label" for="save_key">Save this API key to the database if test is successful</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Test API Key</button>
                        <a href="ai-settings.php" class="btn btn-secondary">Back to AI Settings</a>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Troubleshooting</h3>
                </div>
                <div class="card-body">
                    <h5>Common Issues:</h5>
                    <ul>
                        <li><strong>Invalid API key:</strong> Make sure you've copied the entire API key correctly.</li>
                        <li><strong>API key not active:</strong> Check if your API key is active in the OpenAI dashboard.</li>
                        <li><strong>Rate limiting:</strong> You might be hitting rate limits if you've made too many requests.</li>
                        <li><strong>Billing issues:</strong> Ensure your OpenAI account has valid payment information.</li>
                    </ul>

                    <h5>Next Steps:</h5>
                    <ul>
                        <li>If the test is successful, try generating an image with the <a href="ai-image-generator.php">AI Image Generator</a>.</li>
                        <li>If you're still having issues, check the <a href="/check_ai_api_config.php" target="_blank">AI API Configuration Check</a> for more detailed diagnostics.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
