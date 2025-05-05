<?php
/**
 * AI Settings Admin Page
 * 
 * This page provides an interface for managing AI configuration,
 * including API keys, models, and provider settings.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Initialize variables
$openai = null;
$openaiConfig = [];
$usage = ['total_generations' => 0, 'total_cost' => 0];
$availableModels = [];

// Function to fetch available models from OpenAI
function fetchAvailableModels($apiKey) {
    $url = "https://api.openai.com/v1/models";
    $headers = [
        "Authorization: Bearer $apiKey"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception("Failed to fetch models: $err");
    }

    $models = json_decode($response, true);
    if (!isset($models['data'])) {
        throw new Exception("Invalid response from OpenAI API");
    }

    // Filter and categorize models
    $categorizedModels = [
        'image' => [
            'gpt-image-1' => 'GPT Image 1 (Latest)',
            'dall-e-3' => 'DALL·E 3 (Legacy)',
            'dall-e-2' => 'DALL·E 2 (Legacy)'
        ],
        'text' => [
            'gpt-4.1' => 'GPT-4.1 (Latest)',
            'gpt-4o' => 'GPT-4o',
            'o4-mini' => 'o4-mini (Fast)',
            'o3' => 'o3 (Powerful)',
            'o3-mini' => 'o3-mini (Balanced)'
        ]
    ];

    return $categorizedModels;
}

try {
    // Check if ai_providers table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_providers'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("Required table 'ai_providers' does not exist. Please run setup_ai_tables.php first.");
    }

    // Check if OpenAI provider exists, create if not
    $stmt = $db->prepare("SELECT COUNT(*) FROM ai_providers WHERE name = 'openai'");
    $stmt->execute();
    if ($stmt->fetchColumn() === 0) {
        $stmt = $db->prepare("
            INSERT INTO ai_providers (name, type, config, is_active) 
            VALUES ('openai', 'image', ?, true)
        ");
        $defaultConfig = [
            'model' => 'gpt-image-1',
            'max_tokens' => 2000,
            'temperature' => 0.7
        ];
        $stmt->execute([json_encode($defaultConfig)]);
    }

    // Get OpenAI settings
    $stmt = $db->prepare("SELECT * FROM ai_providers WHERE name = 'openai'");
    $stmt->execute();
    $openai = $stmt->fetch();
    $openaiConfig = json_decode($openai['config'], true) ?? [];

    // Fetch available models if we have an API key
    if (!empty($openaiConfig['api_key'])) {
        try {
            $availableModels = fetchAvailableModels($openaiConfig['api_key']);
        } catch (Exception $e) {
            error_log("Error fetching models: " . $e->getMessage());
            // Don't throw - we'll use hardcoded model list
        }
    }
    
    // Get usage statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_generations,
            COALESCE(SUM(cost), 0) as total_cost
        FROM ai_usage 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $usage = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("AI Settings error: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading settings: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_openai'])) {
            // Update OpenAI settings
            $stmt = $db->prepare("
                UPDATE ai_providers 
                SET config = ? 
                WHERE name = 'openai'
            ");
            
            $config = [
                'api_key' => $_POST['openai_api_key'],
                'organization' => $_POST['openai_organization'],
                'model' => $_POST['openai_model'],
                'max_tokens' => (int)$_POST['openai_max_tokens'],
                'temperature' => (float)$_POST['openai_temperature']
            ];
            
            $stmt->execute([json_encode($config)]);
            $_SESSION['success'] = 'OpenAI settings updated successfully';
        }
    } catch (Exception $e) {
        error_log("Error updating settings: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating settings: ' . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Set page variables for header
$pageTitle = 'AI Settings';
$currentPage = 'ai-settings';
$pageDescription = 'Configure AI providers, models, and view usage statistics';
$pageActions = '
<div class="d-flex gap-2">
    <a href="ai-image-generator.php" class="btn btn-success">
        <i class="fas fa-image"></i> Test Image Generator
    </a>
    <button onclick="window.location.reload()" class="btn btn-secondary">
        <i class="fas fa-sync"></i> Refresh Models
    </button>
</div>';

// Include header
require_once '../includes/header.php';

// Display any errors prominently
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error</h4>';
    echo '<p>' . htmlspecialchars($_SESSION['error']) . '</p>';
    echo '</div>';
    unset($_SESSION['error']);
}

// Display success messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success" role="alert">';
    echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success']);
    echo '</div>';
    unset($_SESSION['success']);
}
?>

<div class="content-section">
    <div class="section-body">
        <!-- Usage Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Usage Overview (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h4>Total Generations</h4>
                        <p class="h2"><?php echo number_format($usage['total_generations']); ?></p>
                    </div>
                    <div class="col">
                        <h4>Total Cost</h4>
                        <p class="h2">$<?php echo number_format($usage['total_cost'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- OpenAI Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>OpenAI Settings</h3>
            </div>
            <div class="card-body">
                <form method="post" class="settings-form">
                    <div class="form-group">
                        <label for="openai_api_key">API Key</label>
                        <input 
                            type="password" 
                            id="openai_api_key" 
                            name="openai_api_key" 
                            class="form-control"
                            value="<?php echo htmlspecialchars($openaiConfig['api_key'] ?? ''); ?>"
                            required
                        >
                        <small class="form-text text-muted">
                            Enter your OpenAI API key to enable AI features
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="openai_organization">Organization ID (Optional)</label>
                        <input 
                            type="text" 
                            id="openai_organization" 
                            name="openai_organization" 
                            class="form-control"
                            value="<?php echo htmlspecialchars($openaiConfig['organization'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="openai_model">Image Generation Model</label>
                        <select id="openai_model" name="openai_model" class="form-control" required>
                            <?php foreach ($availableModels['image'] as $id => $name): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>" 
                                    <?php echo ($openaiConfig['model'] ?? '') === $id ? 'selected' : ''; ?>
                                    <?php echo $id === 'gpt-image-1' ? '' : 'disabled'; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                    <?php echo $id === 'gpt-image-1' ? '' : ' (Not Recommended)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Only GPT Image 1 is recommended for image generation
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="openai_max_tokens">Max Tokens</label>
                            <input 
                                type="number" 
                                id="openai_max_tokens" 
                                name="openai_max_tokens" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($openaiConfig['max_tokens'] ?? '2000'); ?>"
                                min="1"
                                max="4000"
                                required
                            >
                        </div>

                        <div class="form-group col-md-6">
                            <label for="openai_temperature">Temperature</label>
                            <input 
                                type="number" 
                                id="openai_temperature" 
                                name="openai_temperature" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($openaiConfig['temperature'] ?? '0.7'); ?>"
                                min="0"
                                max="2"
                                step="0.1"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_openai" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save OpenAI Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Usage Details -->
        <div class="card">
            <div class="card-header">
                <h3>Usage Details</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Provider</th>
                                <th>Type</th>
                                <th>Generations</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $db->query("
                                    SELECT 
                                        DATE(u.created_at) as date,
                                        p.name as provider,
                                        u.type,
                                        COUNT(*) as generations,
                                        SUM(u.cost) as cost
                                    FROM ai_usage u
                                    JOIN ai_providers p ON u.provider_id = p.id
                                    WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                    GROUP BY DATE(u.created_at), p.name, u.type
                                    ORDER BY date DESC
                                    LIMIT 10
                                ");
                                
                                while ($row = $stmt->fetch()): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['provider']); ?></td>
                                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                                        <td><?php echo number_format($row['generations']); ?></td>
                                        <td>$<?php echo number_format($row['cost'], 2); ?></td>
                                    </tr>
                                <?php endwhile;
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-center text-muted">No usage data available</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>