<?php
/**
 * AI Image Generator Test Page
 *
 * This page provides a testing interface for the AI image generation functionality.
 */

// Set page variables
$pageTitle = 'AI Image Generator';
$currentPage = 'ai-settings';
$pageDescription = 'Test AI image generation with different prompts and settings';

// Add page actions
$pageActions = '
<a href="ai-settings.php" class="btn btn-primary">
    <i class="fas fa-cog"></i> Back to AI Settings
</a>';

// Include necessary files
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

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

// Handle image generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    try {
        // Make API request
        $data = [
            'prompt' => $_POST['prompt'],
            'size' => $_POST['size'],
            'style' => $_POST['style'],
            'variations' => (int)$_POST['variations']
        ];

        // Use cURL instead of file_get_contents for better error handling
        $ch = curl_init('https://api.storiesfromtheweb.org/api/v1/ai/image.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false, // For development only
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('API request failed: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('API returned error code: ' . $httpCode . '. Response: ' . $response);
        }

        $result = json_decode($response, true);

        if ($result['success']) {
            $_SESSION['success'] = 'Image generated successfully!';
            $_SESSION['generated_images'] = $result['data'];
        } else {
            $_SESSION['error'] = 'Failed to generate image: ' . ($result['error'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error generating image: ' . $e->getMessage();
    }

    // Store the result in session and redirect to prevent form resubmission
    $_SESSION['redirect_from_post'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-magic" aria-hidden="true"></i>
            AI Image Generator
        </h2>
        <p class="section-description">
            Test the AI image generation system with different prompts and settings
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
                <h3>Generate Image</h3>
            </div>
            <div class="card-body">
                <form method="post" class="generation-form">
                    <div class="form-group">
                        <label for="prompt">Image Description</label>
                        <textarea
                            id="prompt"
                            name="prompt"
                            class="form-control"
                            rows="3"
                            placeholder="Describe the image you want to generate..."
                            required
                        ></textarea>
                        <small class="form-text text-muted">
                            Be specific and detailed in your description for better results
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="size">Image Size</label>
                            <select id="size" name="size" class="form-control">
                                <option value="1024x1024" selected>Square (1024x1024)</option>
                                <option value="1024x1792">Portrait (1024x1792)</option>
                                <option value="1792x1024">Landscape (1792x1024)</option>
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="style">Style</label>
                            <select id="style" name="style" class="form-control">
                                <option value="natural" selected>Natural</option>
                                <option value="vivid">Vivid</option>
                                <option value="artistic">Artistic</option>
                                <option value="professional">Professional</option>
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="variations">Variations</label>
                            <select id="variations" name="variations" class="form-control">
                                <option value="1" selected>1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="generate" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Generate Image
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Generated Images -->
        <?php if (isset($_SESSION['generated_images'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Generated Images</h3>
                </div>
                <div class="card-body">
                    <div class="generated-images">
                        <?php
                        $images = $_SESSION['generated_images'];
                        unset($_SESSION['generated_images']);
                        ?>

                        <!-- Main Image -->
                        <div class="image-item">
                            <img src="<?php echo htmlspecialchars($images['url']); ?>"
                                 alt="Generated image"
                                 class="generated-image">
                            <div class="image-actions">
                                <a href="<?php echo htmlspecialchars($images['url']); ?>"
                                   class="btn btn-sm btn-primary"
                                   target="_blank">
                                    <i class="fas fa-external-link-alt"></i> View Full Size
                                </a>
                            </div>
                        </div>

                        <!-- Variations -->
                        <?php if (isset($images['variations'])): ?>
                            <?php foreach ($images['variations'] as $url): ?>
                                <div class="image-item">
                                    <img src="<?php echo htmlspecialchars($url); ?>"
                                         alt="Image variation"
                                         class="generated-image">
                                    <div class="image-actions">
                                        <a href="<?php echo htmlspecialchars($url); ?>"
                                           class="btn btn-sm btn-primary"
                                           target="_blank">
                                            <i class="fas fa-external-link-alt"></i> View Full Size
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

.generated-images {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.image-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.generated-image {
    width: 100%;
    height: auto;
    border-radius: var(--radius-1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.image-actions {
    display: flex;
    justify-content: center;
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

<?php
// Include header after all potential redirects
require_once '../includes/header.php';
require_once '../includes/footer.php';
?>