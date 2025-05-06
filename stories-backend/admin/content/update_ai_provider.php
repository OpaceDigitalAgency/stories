<?php
/**
 * Update AI Provider
 * 
 * This script updates the OpenAI provider configuration with the API key.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'Update AI Provider';
$currentPage = 'ai-settings';
$pageDescription = 'Update the OpenAI provider configuration';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the API key from the form
        $apiKey = $_POST['api_key'] ?? '';
        
        if (empty($apiKey)) {
            throw new Exception('API key is required');
        }
        
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
        
        $_SESSION['success'] = 'OpenAI provider updated successfully';
        header('Location: ai-settings.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating OpenAI provider: ' . $e->getMessage();
    }
}

// Include header
include_once '../includes/admin-header.php';
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
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update OpenAI Provider</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="api_key">OpenAI API Key</label>
                            <input type="text" class="form-control" id="api_key" name="api_key" required>
                            <small class="form-text text-muted">
                                Enter your OpenAI API key. You can get one from 
                                <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a>
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Provider</button>
                        <a href="ai-settings.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/admin-footer.php';
?>
