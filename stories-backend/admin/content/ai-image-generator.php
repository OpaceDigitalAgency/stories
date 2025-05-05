<?php
/**
 * AI Image Generator Admin Page
 * 
 * This page provides an interface for generating AI images using the OpenAI integration.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'AI Image Generator';
$currentPage = 'ai-image-generator';
$pageDescription = 'Generate AI-powered images using OpenAI\'s DALL-E 3';

// Check if OpenAI is configured
$config = \Stories\Lib\AI\Core\Config::getInstance();
$openaiConfig = $config->getProviderConfig('openai');
$isConfigured = !empty($openaiConfig['api_key']);

?>

<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-robot" aria-hidden="true"></i> 
            AI Image Generator
        </h2>
        <p class="section-description">
            Generate high-quality images using AI. Images will be automatically optimized and saved to your media library.
        </p>
    </div>

    <?php if (!$isConfigured): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <strong>OpenAI API not configured!</strong>
        <p>Please set up your OpenAI API key in the configuration to use this feature.</p>
    </div>
    <?php else: ?>
    <div class="section-body">
        <div id="imageGenerator"></div>

        <div class="usage-info mt-4">
            <h3>Usage Information</h3>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h4>Today's Usage</h4>
                            <p id="todayUsage">Loading...</p>
                        </div>
                        <div class="col">
                            <h4>Monthly Usage</h4>
                            <p id="monthlyUsage">Loading...</p>
                        </div>
                        <div class="col">
                            <h4>Cost</h4>
                            <p id="totalCost">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="generation-history mt-4">
            <h3>Recent Generations</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Prompt</th>
                            <th>Image</th>
                            <th>Size</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody id="generationHistory">
                        <tr>
                            <td colspan="5" class="text-center">Loading history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script type="module">
        // Import and initialize the ImageGenerator component
        import ImageGenerator from '../../../src/components/ai/ImageGenerator.astro';

        // Mount the component
        const container = document.getElementById('imageGenerator');
        if (container) {
            new ImageGenerator({
                target: container,
                props: {
                    onGenerate: (result) => {
                        // Refresh usage info and history after successful generation
                        loadUsageInfo();
                        loadGenerationHistory();
                    }
                }
            });
        }

        // Load usage information
        async function loadUsageInfo() {
            try {
                const response = await fetch('/api/ai/usage.php');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('todayUsage').textContent = `${data.today} generations`;
                    document.getElementById('monthlyUsage').textContent = `${data.monthly} generations`;
                    document.getElementById('totalCost').textContent = `$${data.cost.toFixed(2)}`;
                }
            } catch (error) {
                console.error('Failed to load usage info:', error);
            }
        }

        // Load generation history
        async function loadGenerationHistory() {
            try {
                const response = await fetch('/api/ai/history.php');
                const data = await response.json();

                if (data.success) {
                    const tbody = document.getElementById('generationHistory');
                    tbody.innerHTML = data.generations.map(gen => `
                        <tr>
                            <td>${new Date(gen.created_at).toLocaleString()}</td>
                            <td>${gen.prompt}</td>
                            <td>
                                <img src="${gen.result_url}" alt="${gen.prompt}" 
                                     style="max-width: 100px; height: auto;">
                            </td>
                            <td>${gen.metadata.size}</td>
                            <td>$${gen.metadata.cost.toFixed(2)}</td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Failed to load generation history:', error);
            }
        }

        // Initial load
        loadUsageInfo();
        loadGenerationHistory();
    </script>

    <style>
        .usage-info .card {
            background: var(--surface-2);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-2);
        }

        .usage-info h4 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .generation-history img {
            border-radius: var(--radius-1);
        }
    </style>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>