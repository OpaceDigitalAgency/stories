<?php
/**
 * AI Settings Admin Page
 *
 * This page provides an interface for managing AI configuration,
 * including API keys, models, and provider settings.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'AI Settings';
$currentPage = 'ai-settings';
$pageDescription = 'Configure AI providers, models, and view usage statistics';

// Add page actions
$pageActions = '
<a href="ai-image-generator.php" class="btn btn-success">
    <i class="fas fa-image"></i> Test Image Generator
</a>';

// Get current settings
try {
    // Check if OpenAI provider exists, create if not
    $stmt = $db->prepare("SELECT COUNT(*) FROM ai_providers WHERE name = 'openai'");
    $stmt->execute();
    if ($stmt->fetchColumn() === 0) {
        $stmt = $db->prepare("
            INSERT INTO ai_providers (name, type, config, is_active) 
            VALUES ('openai', 'image', ?, true)
        ");
        $defaultConfig = [
            'model' => 'dall-e-3',
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
        $_SESSION['error'] = 'Error updating settings: ' . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-cog" aria-hidden="true"></i> 
            AI Settings
        </h2>
        <p class="section-description">
            Configure AI providers and monitor usage
        </p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

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
                        <label for="openai_model">Default Model</label>
                        <select id="openai_model" name="openai_model" class="form-control" required>
                            <option value="dall-e-3" <?php echo ($openaiConfig['model'] ?? '') === 'dall-e-3' ? 'selected' : ''; ?>>
                                DALL-E 3 (Best Quality)
                            </option>
                            <option value="dall-e-2" <?php echo ($openaiConfig['model'] ?? '') === 'dall-e-2' ? 'selected' : ''; ?>>
                                DALL-E 2 (Faster)
                            </option>
                        </select>
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
                            Save OpenAI Settings
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-form {
    max-width: 800px;
}

.card {
    background: var(--surface-2);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-2);
    margin-bottom: 2rem;
}

.card-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-3);
}

.card-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.card-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-actions {
    margin-top: 2rem;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
}

.table th,
.table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: var(--radius-1);
}

.alert-success {
    background: var(--success-bg);
    color: var(--success-text);
}

.alert-danger {
    background: var(--error-bg);
    color: var(--error-text);
}
</style>

<?php
require_once '../includes/footer.php';
?>