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

// Include OpenAI models helper
require_once '../../includes/openai-models.php';

// Initialize variables
$openai = null;
$openaiConfig = [];
$usage = ['total_generations' => 0, 'total_cost' => 0];
$availableModels = [];
$promptTemplates = [];
$refreshModels = isset($_GET['refresh_models']) && $_GET['refresh_models'] === '1';

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

    // Get available models from API response
    $availableModels = [];
    foreach ($models['data'] as $model) {
        $modelId = $model['id'];

        // Filter for relevant models
        if (strpos($modelId, 'gpt-4') === 0 ||
            strpos($modelId, 'gpt-3.5') === 0 ||
            strpos($modelId, 'dall-e') === 0 ||
            strpos($modelId, 'gpt-image') === 0 ||
            strpos($modelId, 'o3') === 0 ||
            strpos($modelId, 'o4') === 0) {

            // Categorize models
            if (strpos($modelId, 'dall-e') === 0 || strpos($modelId, 'gpt-image') === 0) {
                $availableModels['image'][$modelId] = $modelId;
            } else {
                $availableModels['text'][$modelId] = $modelId;
            }
        }
    }

    // If no models found, use default list
    // Always use our default list - API model fetching is unreliable
    $availableModels = [
        'image' => [
            'gpt-image-1' => 'GPT Image 1 (Latest)',
            'dall-e-3' => 'DALL·E 3 (Legacy)',
            'dall-e-2' => 'DALL·E 2 (Legacy)'
        ],
        'text' => [
            'gpt-4.1' => 'GPT-4.1 (Latest)',
            'gpt-4o' => 'GPT-4o (Balanced)',
            'o4-mini' => 'o4-mini (Fast)',
            'o3' => 'o3 (Powerful)',
            'o3-mini' => 'o3-mini (Balanced)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Economical)'
        ]
    ];

    return $availableModels;
}

try {
    // Check if required tables exist
    $requiredTables = ['ai_providers', 'ai_generations', 'ai_usage', 'ai_prompt_templates'];
    $missingTables = [];

    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        throw new Exception("Required tables do not exist: " . implode(', ', $missingTables) . ". Please run setup_ai_tables.php first.");
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
            'text_model' => 'gpt-4o',
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

    // Ensure text_model is set
    if (!isset($openaiConfig['text_model'])) {
        $openaiConfig['text_model'] = 'gpt-4o';
    }

    // Fetch models from OpenAI API or use cached models
    $availableModels = fetchOpenAIModels($openaiConfig['api_key'] ?? '', $refreshModels);

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

    // Get prompt templates
    $stmt = $db->query("SELECT * FROM ai_prompt_templates ORDER BY content_type, name");
    $promptTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                'text_model' => $_POST['openai_text_model'],
                'max_tokens' => (int)$_POST['openai_max_tokens'],
                'temperature' => (float)$_POST['openai_temperature']
            ];

            $stmt->execute([json_encode($config)]);
            $_SESSION['success'] = 'OpenAI settings updated successfully';
        } elseif (isset($_POST['add_template'])) {
            // Add new prompt template
            $stmt = $db->prepare("
                INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_POST['template_name'],
                $_POST['template_content_type'],
                $_POST['template_description'],
                $_POST['template_prompt'],
                isset($_POST['template_active']) ? 1 : 0
            ]);

            $_SESSION['success'] = 'Prompt template added successfully';
        } elseif (isset($_POST['update_template'])) {
            // Update existing prompt template
            $stmt = $db->prepare("
                UPDATE ai_prompt_templates
                SET name = ?, content_type = ?, description = ?, prompt_template = ?, is_active = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['template_name'],
                $_POST['template_content_type'],
                $_POST['template_description'],
                $_POST['template_prompt'],
                isset($_POST['template_active']) ? 1 : 0,
                $_POST['template_id']
            ]);

            $_SESSION['success'] = 'Prompt template updated successfully';
        } elseif (isset($_POST['delete_template'])) {
            // Delete prompt template
            $stmt = $db->prepare("DELETE FROM ai_prompt_templates WHERE id = ?");
            $stmt->execute([$_POST['template_id']]);

            $_SESSION['success'] = 'Prompt template deleted successfully';
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
    <a href="ai-text-generator.php" class="btn btn-info">
        <i class="fas fa-font"></i> Test Text Generator
    </a>
    <a href="ai-settings.php?refresh_models=1" class="btn btn-secondary">
        <i class="fas fa-sync"></i> Refresh Models
    </a>
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

// Display model refresh notification
if ($refreshModels) {
    echo '<div class="alert alert-info" role="alert">';
    echo '<i class="fas fa-sync-alt"></i> OpenAI models have been refreshed from the API.';
    echo '</div>';
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
                <?php
                // Display last refresh time
                $cachedModels = getCachedModels();
                if ($cachedModels) {
                    $lastRefresh = date('F j, Y, g:i a', $cachedModels['timestamp']);
                    echo '<div class="mb-3 text-muted"><small><i class="fas fa-clock"></i> Models last refreshed: ' . $lastRefresh . '</small></div>';
                }
                ?>
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

                    <div class="form-group">
                        <label for="openai_text_model">Text Generation Model</label>
                        <select id="openai_text_model" name="openai_text_model" class="form-control" required>
                            <?php foreach ($availableModels['text'] as $id => $name): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>"
                                    <?php echo ($openaiConfig['text_model'] ?? '') === $id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Select the model to use for text generation
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

        <!-- Prompt Templates -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>AI Prompt Templates</h3>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTemplateModal">
                    <i class="fas fa-plus"></i> Add Template
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Prompt templates are used to generate dynamic prompts for AI image and text generation.
                    They can include variables that will be replaced with actual content when used.
                </p>

                <ul class="nav nav-tabs" id="templateTabs" role="tablist">
                    <?php
                    $contentTypes = [
                        'story' => 'Stories',
                        'blog_post' => 'Blog Posts',
                        'author' => 'Authors',
                        'game' => 'Games',
                        'ai_tool' => 'AI Tools',
                        'directory' => 'Directory',
                        'general' => 'General'
                    ];

                    $first = true;
                    foreach ($contentTypes as $type => $label): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $first ? 'active' : ''; ?>"
                               id="<?php echo $type; ?>-tab"
                               data-toggle="tab"
                               href="#<?php echo $type; ?>-templates"
                               role="tab">
                                <?php echo $label; ?>
                            </a>
                        </li>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content mt-3" id="templateTabContent">
                    <?php
                    $first = true;
                    foreach ($contentTypes as $type => $label):
                        $typeTemplates = array_filter($promptTemplates, function($template) use ($type) {
                            return $template['content_type'] === $type;
                        });
                    ?>
                        <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>"
                             id="<?php echo $type; ?>-templates"
                             role="tabpanel">

                            <?php if (empty($typeTemplates)): ?>
                                <div class="alert alert-info">
                                    No templates defined for <?php echo $label; ?>.
                                    <button type="button" class="btn btn-sm btn-primary"
                                            data-toggle="modal"
                                            data-target="#addTemplateModal"
                                            data-type="<?php echo $type; ?>">
                                        Add Template
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($typeTemplates as $template): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($template['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($template['description'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $template['is_active'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-info view-template"
                                                                    data-id="<?php echo $template['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($template['name']); ?>"
                                                                    data-type="<?php echo htmlspecialchars($template['content_type']); ?>"
                                                                    data-description="<?php echo htmlspecialchars($template['description'] ?? ''); ?>"
                                                                    data-template="<?php echo htmlspecialchars($template['prompt_template']); ?>"
                                                                    data-active="<?php echo $template['is_active'] ? '1' : '0'; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-primary edit-template"
                                                                    data-id="<?php echo $template['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($template['name']); ?>"
                                                                    data-type="<?php echo htmlspecialchars($template['content_type']); ?>"
                                                                    data-description="<?php echo htmlspecialchars($template['description'] ?? ''); ?>"
                                                                    data-template="<?php echo htmlspecialchars($template['prompt_template']); ?>"
                                                                    data-active="<?php echo $template['is_active'] ? '1' : '0'; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger delete-template"
                                                                    data-id="<?php echo $template['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($template['name']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
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

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTemplateModalLabel">Add Prompt Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="template_name">Template Name</label>
                        <input type="text" class="form-control" id="template_name" name="template_name" required>
                    </div>

                    <div class="form-group">
                        <label for="template_content_type">Content Type</label>
                        <select class="form-control" id="template_content_type" name="template_content_type" required>
                            <?php foreach ($contentTypes as $type => $label): ?>
                                <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="template_description">Description</label>
                        <input type="text" class="form-control" id="template_description" name="template_description">
                    </div>

                    <div class="form-group">
                        <label for="template_prompt">Prompt Template</label>
                        <textarea class="form-control" id="template_prompt" name="template_prompt" rows="5" required></textarea>
                        <small class="form-text text-muted">
                            Use {{variable}} syntax for variables. Example: Create an image of {{title}} with {{description}}.
                            <br>
                            You can also use conditional blocks: {{#if variable}}content{{/if}}
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="template_active" name="template_active" checked>
                            <label class="custom-control-label" for="template_active">Active</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Available Variables</label>
                        <div class="card">
                            <div class="card-body">
                                <div class="variables-list">
                                    <div class="variable-type" data-type="story">
                                        <strong>Story Variables:</strong>
                                        <code>{{title}}</code>, <code>{{summary}}</code>, <code>{{story}}</code>, <code>{{age_group}}</code>
                                    </div>
                                    <div class="variable-type" data-type="blog_post">
                                        <strong>Blog Post Variables:</strong>
                                        <code>{{title}}</code>, <code>{{excerpt}}</code>, <code>{{content}}</code>
                                    </div>
                                    <div class="variable-type" data-type="author">
                                        <strong>Author Variables:</strong>
                                        <code>{{name}}</code>, <code>{{bio}}</code>, <code>{{author_type}}</code>, <code>{{age}}</code>, <code>{{location}}</code>
                                    </div>
                                    <div class="variable-type" data-type="game">
                                        <strong>Game Variables:</strong>
                                        <code>{{title}}</code>, <code>{{description}}</code>, <code>{{genre}}</code>, <code>{{platform}}</code>
                                    </div>
                                    <div class="variable-type" data-type="ai_tool">
                                        <strong>AI Tool Variables:</strong>
                                        <code>{{title}}</code>, <code>{{description}}</code>, <code>{{features}}</code>
                                    </div>
                                    <div class="variable-type" data-type="directory">
                                        <strong>Directory Variables:</strong>
                                        <code>{{title}}</code>, <code>{{description}}</code>, <code>{{address}}</code>
                                    </div>
                                    <div class="variable-type" data-type="general">
                                        <strong>General Variables:</strong>
                                        <code>{{description}}</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_template" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTemplateModalLabel">Edit Prompt Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <input type="hidden" id="edit_template_id" name="template_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_template_name">Template Name</label>
                        <input type="text" class="form-control" id="edit_template_name" name="template_name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_template_content_type">Content Type</label>
                        <select class="form-control" id="edit_template_content_type" name="template_content_type" required>
                            <?php foreach ($contentTypes as $type => $label): ?>
                                <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_template_description">Description</label>
                        <input type="text" class="form-control" id="edit_template_description" name="template_description">
                    </div>

                    <div class="form-group">
                        <label for="edit_template_prompt">Prompt Template</label>
                        <textarea class="form-control" id="edit_template_prompt" name="template_prompt" rows="5" required></textarea>
                        <small class="form-text text-muted">
                            Use {{variable}} syntax for variables. Example: Create an image of {{title}} with {{description}}.
                            <br>
                            You can also use conditional blocks: {{#if variable}}content{{/if}}
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_template_active" name="template_active">
                            <label class="custom-control-label" for="edit_template_active">Active</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Available Variables</label>
                        <div class="card">
                            <div class="card-body">
                                <div class="variables-list">
                                    <div class="variable-type" data-type="story">
                                        <strong>Story Variables:</strong>
                                        <code>{{title}}</code>, <code>{{summary}}</code>, <code>{{story}}</code>, <code>{{age_group}}</code>
                                    </div>
                                    <div class="variable-type" data-type="blog_post">
                                        <strong>Blog Post Variables:</strong>
                                        <code>{{title}}</code>, <code>{{excerpt}}</code>, <code>{{content}}</code>
                                    </div>
                                    <div class="variable-type" data-type="author">
                                        <strong>Author Variables:</strong>
                                        <code>{{name}}</code>, <code>{{bio}}</code>, <code>{{author_type}}</code>, <code>{{age}}</code>, <code>{{location}}</code>
                                    </div>
                                    <div class="variable-type" data-type="game">
                                        <strong>Game Variables:</strong>
                                        <code>{{title}}</code>, <code>{{description}}</code>, <code>{{genre}}</code>, <code>{{platform}}</code>
                                    </div>
                                    <div class="variable-type" data-type="ai_tool">
                                        <strong>AI Tool Variables:</strong>
                                        <code>{{title}}</code>, <code>{{description}}</code>, <code>{{features}}</code>
                                    </div>
                                    <div class="variable-type" data-type="directory">
                                        <strong>Directory Variables:</strong>
                                        <code>{{title}}</code>, <code>{{description}}</code>, <code>{{address}}</code>
                                    </div>
                                    <div class="variable-type" data-type="general">
                                        <strong>General Variables:</strong>
                                        <code>{{description}}</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_template" class="btn btn-primary">Update Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Template Modal -->
<div class="modal fade" id="viewTemplateModal" tabindex="-1" role="dialog" aria-labelledby="viewTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTemplateModalLabel">View Prompt Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Name</label>
                    <p id="view_template_name" class="form-control-static"></p>
                </div>

                <div class="form-group">
                    <label>Content Type</label>
                    <p id="view_template_content_type" class="form-control-static"></p>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <p id="view_template_description" class="form-control-static"></p>
                </div>

                <div class="form-group">
                    <label>Prompt Template</label>
                    <pre id="view_template_prompt" class="p-3 bg-light"></pre>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <p id="view_template_active" class="form-control-static"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary edit-from-view">Edit Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Template Modal -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" role="dialog" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTemplateModalLabel">Delete Prompt Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the template "<span id="delete_template_name"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="post" class="d-inline">
                    <input type="hidden" id="delete_template_id" name="template_id">
                    <button type="submit" name="delete_template" class="btn btn-danger">Delete Template</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide variable types based on selected content type
function toggleVariableTypes() {
    const contentType = $(this).val();
    $('.variable-type').hide();
    $(`.variable-type[data-type="${contentType}"]`).show();
}

$(document).ready(function() {
    // Fix for select elements
    $('#openai_model, #openai_text_model').each(function() {
        // Make sure the select has at least one option
        if ($(this).find('option').length === 0) {
            if ($(this).attr('id') === 'openai_model') {
                $(this).append('<option value="gpt-image-1">GPT Image 1 (Latest)</option>');
            } else {
                $(this).append('<option value="gpt-4o">GPT-4o (Balanced)</option>');
            }
        }

        // Make sure the selected option exists
        const selectedValue = $(this).val();
        if (selectedValue && $(this).find(`option[value="${selectedValue}"]`).length === 0) {
            $(this).append(`<option value="${selectedValue}" selected>${selectedValue}</option>`);
        }
    });

    // Initialize variable types visibility
    $('#template_content_type').change(toggleVariableTypes).trigger('change');
    $('#edit_template_content_type').change(toggleVariableTypes);

    // Handle edit template button
    $('.edit-template').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const description = $(this).data('description');
        const template = $(this).data('template');
        const active = $(this).data('active') === '1';

        $('#edit_template_id').val(id);
        $('#edit_template_name').val(name);
        $('#edit_template_content_type').val(type).trigger('change');
        $('#edit_template_description').val(description);
        $('#edit_template_prompt').val(template);
        $('#edit_template_active').prop('checked', active);

        $('#editTemplateModal').modal('show');
    });

    // Handle view template button
    $('.view-template').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const description = $(this).data('description');
        const template = $(this).data('template');
        const active = $(this).data('active') === '1';

        $('#view_template_name').text(name);
        $('#view_template_content_type').text($('#template_content_type option[value="' + type + '"]').text());
        $('#view_template_description').text(description || 'No description');
        $('#view_template_prompt').text(template);
        $('#view_template_active').html(
            active ?
            '<span class="badge badge-success">Active</span>' :
            '<span class="badge badge-secondary">Inactive</span>'
        );

        // Store data for edit button
        $('#viewTemplateModal').data('template-id', id);

        $('#viewTemplateModal').modal('show');
    });

    // Handle edit from view button
    $('.edit-from-view').click(function() {
        const id = $('#viewTemplateModal').data('template-id');
        $('#viewTemplateModal').modal('hide');
        $(`.edit-template[data-id="${id}"]`).click();
    });

    // Handle delete template button
    $('.delete-template').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#delete_template_id').val(id);
        $('#delete_template_name').text(name);

        $('#deleteTemplateModal').modal('show');
    });

    // Set content type when adding template from tab
    $('[data-target="#addTemplateModal"]').click(function() {
        const type = $(this).data('type');
        if (type) {
            $('#template_content_type').val(type).trigger('change');
        }
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>