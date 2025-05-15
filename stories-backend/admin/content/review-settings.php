<?php
/**
 * Review Settings
 * 
 * This page allows administrators to configure review-related settings.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include header
$pageTitle = "Review Settings";
require_once '../includes/header.php';

// Include database connection
require_once '../includes/db-connect.php';

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Update OpenAI API key
        if (isset($_POST['openai_api_key'])) {
            $apiKey = trim($_POST['openai_api_key']);
            
            // Check if the setting exists
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_name = 'openai_api_key'");
            $checkStmt->execute();
            $settingExists = $checkStmt->fetchColumn() > 0;
            
            if ($settingExists) {
                // Update existing setting
                $stmt = $db->prepare("
                    UPDATE settings
                    SET setting_value = ?, updated_at = NOW()
                    WHERE setting_name = 'openai_api_key'
                ");
                $stmt->execute([$apiKey]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_name, setting_value, setting_group, is_public)
                    VALUES ('openai_api_key', ?, 'ai', 0)
                ");
                $stmt->execute([$apiKey]);
            }
        }
        
        // Update AI default model
        if (isset($_POST['ai_default_model'])) {
            $defaultModel = trim($_POST['ai_default_model']);
            
            // Check if the setting exists
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_name = 'ai_default_model'");
            $checkStmt->execute();
            $settingExists = $checkStmt->fetchColumn() > 0;
            
            if ($settingExists) {
                // Update existing setting
                $stmt = $db->prepare("
                    UPDATE settings
                    SET setting_value = ?, updated_at = NOW()
                    WHERE setting_name = 'ai_default_model'
                ");
                $stmt->execute([$defaultModel]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_name, setting_value, setting_group, is_public)
                    VALUES ('ai_default_model', ?, 'ai', 0)
                ");
                $stmt->execute([$defaultModel]);
            }
        }
        
        // Update enable AI analysis setting
        if (isset($_POST['enable_ai_analysis'])) {
            $enableAiAnalysis = $_POST['enable_ai_analysis'] ? 1 : 0;
            
            // Check if the setting exists
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_name = 'enable_ai_analysis'");
            $checkStmt->execute();
            $settingExists = $checkStmt->fetchColumn() > 0;
            
            if ($settingExists) {
                // Update existing setting
                $stmt = $db->prepare("
                    UPDATE settings
                    SET setting_value = ?, updated_at = NOW()
                    WHERE setting_name = 'enable_ai_analysis'
                ");
                $stmt->execute([$enableAiAnalysis]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_name, setting_value, setting_group, is_public)
                    VALUES ('enable_ai_analysis', ?, 'reviews', 0)
                ");
                $stmt->execute([$enableAiAnalysis]);
            }
        }
        
        // Update reviews per page setting
        if (isset($_POST['reviews_per_page'])) {
            $reviewsPerPage = (int)$_POST['reviews_per_page'];
            
            // Check if the setting exists
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_name = 'reviews_per_page'");
            $checkStmt->execute();
            $settingExists = $checkStmt->fetchColumn() > 0;
            
            if ($settingExists) {
                // Update existing setting
                $stmt = $db->prepare("
                    UPDATE settings
                    SET setting_value = ?, updated_at = NOW()
                    WHERE setting_name = 'reviews_per_page'
                ");
                $stmt->execute([$reviewsPerPage]);
            } else {
                // Insert new setting
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_name, setting_value, setting_group, is_public)
                    VALUES ('reviews_per_page', ?, 'reviews', 1)
                ");
                $stmt->execute([$reviewsPerPage]);
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $successMessage = 'Review settings updated successfully.';
    } catch (Exception $e) {
        // Rollback transaction
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        $errorMessage = 'Error updating review settings: ' . $e->getMessage();
    }
}

// Get current settings
try {
    // Get OpenAI API key
    $apiKeyStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'openai_api_key'");
    $apiKeyStmt->execute();
    $apiKey = $apiKeyStmt->fetchColumn();
    
    // Get AI default model
    $defaultModelStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'ai_default_model'");
    $defaultModelStmt->execute();
    $defaultModel = $defaultModelStmt->fetchColumn();
    
    // Get enable AI analysis setting
    $enableAiAnalysisStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'enable_ai_analysis'");
    $enableAiAnalysisStmt->execute();
    $enableAiAnalysis = $enableAiAnalysisStmt->fetchColumn();
    
    // Get reviews per page setting
    $reviewsPerPageStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'reviews_per_page'");
    $reviewsPerPageStmt->execute();
    $reviewsPerPage = $reviewsPerPageStmt->fetchColumn();
} catch (Exception $e) {
    $errorMessage = 'Error retrieving review settings: ' . $e->getMessage();
    $apiKey = '';
    $defaultModel = 'gpt-4-turbo';
    $enableAiAnalysis = 1;
    $reviewsPerPage = 10;
}

// Display the form
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Review System Settings</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <h4>AI Analysis Settings</h4>
                        <div class="form-group">
                            <label for="openai_api_key">OpenAI API Key</label>
                            <input type="password" class="form-control" id="openai_api_key" name="openai_api_key" value="<?php echo htmlspecialchars($apiKey); ?>" autocomplete="off">
                            <small class="form-text text-muted">Your OpenAI API key is used for AI-powered review analysis. Keep this key secure.</small>
                        </div>
                        
                        <div class="form-group mt-3">
                            <label for="ai_default_model">Default AI Model</label>
                            <select class="form-control" id="ai_default_model" name="ai_default_model">
                                <option value="gpt-4-turbo" <?php echo $defaultModel === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo (Recommended)</option>
                                <option value="gpt-4o" <?php echo $defaultModel === 'gpt-4o' ? 'selected' : ''; ?>>GPT-4o</option>
                                <option value="gpt-3.5-turbo" <?php echo $defaultModel === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo (Faster, less accurate)</option>
                            </select>
                            <small class="form-text text-muted">Select the AI model to use for review analysis. GPT-4 models provide better analysis but cost more.</small>
                        </div>
                        
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="enable_ai_analysis" name="enable_ai_analysis" value="1" <?php echo $enableAiAnalysis ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable_ai_analysis">Enable AI Analysis</label>
                            <small class="form-text text-muted d-block">When enabled, reviews will be analyzed by AI to identify age-related content and generate summaries.</small>
                        </div>
                        
                        <h4 class="mt-4">Display Settings</h4>
                        <div class="form-group">
                            <label for="reviews_per_page">Reviews Per Page</label>
                            <input type="number" class="form-control" id="reviews_per_page" name="reviews_per_page" value="<?php echo (int)$reviewsPerPage; ?>" min="1" max="100">
                            <small class="form-text text-muted">Number of reviews to display per page on the frontend.</small>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
