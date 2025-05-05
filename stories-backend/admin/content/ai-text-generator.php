<?php
/**
 * AI Text Generator Test Page
 * 
 * This page provides a testing interface for the AI text generation functionality.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'AI Text Generator';
$currentPage = 'ai-settings';
$pageDescription = 'Test AI text generation with different prompts and settings';

// Add page actions
$pageActions = '
<a href="ai-settings.php" class="btn btn-primary">
    <i class="fas fa-cog"></i> Back to AI Settings
</a>';

// Check if OpenAI is configured
try {
    $stmt = $db->prepare("SELECT config FROM ai_providers WHERE name = 'openai'");
    $stmt->execute();
    $config = $stmt->fetch();
    
    if (!$config) {
        $_SESSION['error'] = 'OpenAI provider not found. Please configure it in AI Settings.';
    } else {
        $openaiConfig = json_decode($config['config'], true);
        if (empty($openaiConfig['api_key'])) {
            $_SESSION['error'] = 'OpenAI API key not configured. Please set it in AI Settings.';
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error checking OpenAI configuration: ' . $e->getMessage();
}

// Get available prompt templates
$promptTemplates = [];
try {
    $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE content_type = 'general' AND is_active = 1");
    $stmt->execute();
    $promptTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching prompt templates: " . $e->getMessage());
}

// Handle text generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    try {
        // Make API request
        $data = [
            'prompt' => $_POST['prompt'],
            'max_tokens' => (int)$_POST['max_tokens'],
            'temperature' => (float)$_POST['temperature'],
            'model' => $_POST['model']
        ];

        $response = file_get_contents('https://api.storiesfromtheweb.org/api/v1/ai/text.php', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ]));

        $result = json_decode($response, true);

        if ($result['success']) {
            $_SESSION['success'] = 'Text generated successfully!';
            $_SESSION['generated_text'] = $result['data']['text'];
            $_SESSION['tokens_used'] = $result['data']['tokens'];
        } else {
            $_SESSION['error'] = 'Failed to generate text: ' . ($result['error'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error generating text: ' . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get available models
$availableModels = [];
if (!empty($openaiConfig['api_key'])) {
    try {
        $url = "https://api.openai.com/v1/models";
        $headers = [
            "Authorization: Bearer " . $openaiConfig['api_key']
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err) {
            $models = json_decode($response, true);
            if (isset($models['data'])) {
                foreach ($models['data'] as $model) {
                    $modelId = $model['id'];
                    
                    // Filter for text models
                    if (strpos($modelId, 'gpt-4') === 0 || 
                        strpos($modelId, 'gpt-3.5') === 0 ||
                        strpos($modelId, 'o3') === 0 ||
                        strpos($modelId, 'o4') === 0) {
                        
                        $availableModels[$modelId] = $modelId;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching models: " . $e->getMessage());
    }
}

// If no models found or error occurred, use default list
if (empty($availableModels)) {
    $availableModels = [
        'gpt-4.1' => 'GPT-4.1 (Latest)',
        'gpt-4o' => 'GPT-4o (Balanced)',
        'o4-mini' => 'o4-mini (Fast)',
        'o3' => 'o3 (Powerful)',
        'o3-mini' => 'o3-mini (Balanced)',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Economical)'
    ];
}
?>

<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-font" aria-hidden="true"></i> 
            AI Text Generator
        </h2>
        <p class="section-description">
            Test the AI text generation system with different prompts and settings
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
        <!-- Generation Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Generate Text</h3>
            </div>
            <div class="card-body">
                <form method="post" class="generation-form">
                    <?php if (!empty($promptTemplates)): ?>
                    <div class="form-group">
                        <label for="template">Prompt Template</label>
                        <select id="template" class="form-control">
                            <option value="">-- Select a template --</option>
                            <?php foreach ($promptTemplates as $template): ?>
                                <option value="<?php echo htmlspecialchars($template['id']); ?>" 
                                        data-template="<?php echo htmlspecialchars($template['prompt_template']); ?>">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Select a template to use as a starting point
                        </small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="prompt">Prompt</label>
                        <textarea 
                            id="prompt" 
                            name="prompt" 
                            class="form-control" 
                            rows="5" 
                            placeholder="Enter your prompt here..."
                            required
                        ></textarea>
                        <small class="form-text text-muted">
                            Be specific and detailed in your prompt for better results
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="model">Model</label>
                            <select id="model" name="model" class="form-control">
                                <?php foreach ($availableModels as $id => $name): ?>
                                    <option value="<?php echo htmlspecialchars($id); ?>" 
                                        <?php echo ($openaiConfig['text_model'] ?? '') === $id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="max_tokens">Max Tokens</label>
                            <input 
                                type="number" 
                                id="max_tokens" 
                                name="max_tokens" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($openaiConfig['max_tokens'] ?? '2000'); ?>"
                                min="1"
                                max="4000"
                                required
                            >
                            <small class="form-text text-muted">
                                Maximum number of tokens to generate (1-4000)
                            </small>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="temperature">Temperature</label>
                            <input 
                                type="number" 
                                id="temperature" 
                                name="temperature" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($openaiConfig['temperature'] ?? '0.7'); ?>"
                                min="0"
                                max="2"
                                step="0.1"
                                required
                            >
                            <small class="form-text text-muted">
                                Controls randomness (0-2). Lower values are more deterministic.
                            </small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="generate" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Generate Text
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Generated Text -->
        <?php if (isset($_SESSION['generated_text'])): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Generated Text</h3>
                    <span class="badge badge-info">
                        <?php echo number_format($_SESSION['tokens_used'] ?? 0); ?> tokens used
                    </span>
                </div>
                <div class="card-body">
                    <div class="generated-text">
                        <?php 
                        $text = $_SESSION['generated_text'];
                        unset($_SESSION['generated_text']);
                        unset($_SESSION['tokens_used']);
                        
                        // Format the text with Markdown
                        $formattedText = nl2br(htmlspecialchars($text));
                        
                        // Convert Markdown-style headings
                        $formattedText = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $formattedText);
                        $formattedText = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $formattedText);
                        $formattedText = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $formattedText);
                        
                        // Convert Markdown-style lists
                        $formattedText = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $formattedText);
                        $formattedText = preg_replace('/^(\d+)\. (.*?)$/m', '<li>$2</li>', $formattedText);
                        
                        // Convert Markdown-style bold and italic
                        $formattedText = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $formattedText);
                        $formattedText = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $formattedText);
                        
                        echo $formattedText;
                        ?>
                    </div>
                    <div class="text-actions mt-3">
                        <button class="btn btn-sm btn-primary" id="copyText">
                            <i class="fas fa-copy"></i> Copy to Clipboard
                        </button>
                        <button class="btn btn-sm btn-secondary" id="downloadText">
                            <i class="fas fa-download"></i> Download as Text File
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.generation-form {
    max-width: 800px;
}

.generated-text {
    background: #f9f9f9;
    padding: 1.5rem;
    border-radius: var(--radius-1);
    border: 1px solid #e0e0e0;
    white-space: pre-wrap;
    font-family: var(--font-family);
    line-height: 1.6;
}

.text-actions {
    display: flex;
    gap: 0.5rem;
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

<script>
$(document).ready(function() {
    // Handle template selection
    $('#template').change(function() {
        const templateText = $(this).find('option:selected').data('template');
        if (templateText) {
            $('#prompt').val(templateText);
        }
    });
    
    // Handle copy to clipboard
    $('#copyText').click(function() {
        const text = $('.generated-text').text();
        navigator.clipboard.writeText(text).then(function() {
            alert('Text copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
            alert('Failed to copy text. Please try again.');
        });
    });
    
    // Handle download as text file
    $('#downloadText').click(function() {
        const text = $('.generated-text').text();
        const blob = new Blob([text], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = 'generated-text.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
