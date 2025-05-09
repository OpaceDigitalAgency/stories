<?php

// Page variables
$pageTitle = 'AI Tool Details';
$currentPage = 'view-ai-tool';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid AI tool ID.";
    header("Location: ai-tools.php");
    exit;
}

$toolId = (int)$_GET['id'];

try {
    // Ensure we have a database connection
    if (!isset($db) || !$db) {
        // Try to connect to the database directly
        try {
            $db = new PDO(
                'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                'stories_user',
                '$tw1cac3*sOt',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            $errorMessage = "Database connection error: " . $e->getMessage();
            error_log("Database connection error in view-ai-tool.php: " . $e->getMessage());
        }
    }

    // Get AI tool details
    $stmt = $db->prepare("
        SELECT a.*, c.name as category_name 
        FROM ai_tools a 
        LEFT JOIN ai_tool_categories c ON a.category_id = c.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$toolId]);
    $tool = $stmt->fetch();

    if (!$tool) {
        $_SESSION['error'] = "AI tool not found.";
        header("Location: ai-tools.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("View AI tool error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading AI tool. Please try again.";
    header("Location: ai-tools.php");
    exit;
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($tool['title']); ?></h1>
                <p class="page-description">
                    <a href="ai-tools.php" class="text-primary">← Back to AI Tools</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="ai-tool-form.php?id=<?php echo $tool['id']; ?>" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit
                </a>
                <form method="POST" action="delete-ai-tool.php" onsubmit="return confirm('Are you sure you want to delete this AI tool?');">
                    <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($tool['title']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="d-flex gap-3 mb-3">
                        <div>
                            <strong>Category:</strong> 
                            <?php echo htmlspecialchars($tool['category_name'] ?? 'None'); ?>
                        </div>
                        <div>
                            <strong>Rating:</strong> 
                            <span class="rating-display">
                                <?php echo number_format($tool['rating'], 1); ?> / 5
                            </span>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3 mb-3">
                        <div>
                            <strong>Pricing:</strong> 
                            <?php echo ucfirst($tool['pricing_type']); ?>
                            <?php if (!empty($tool['price_info'])): ?>
                                (<?php echo htmlspecialchars($tool['price_info']); ?>)
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong>Featured:</strong> 
                            <?php echo $tool['featured'] ? 'Yes' : 'No'; ?>
                        </div>
                        <div>
                            <strong>Published:</strong> 
                            <?php echo $tool['is_published'] ? 'Yes' : 'No'; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($tool['tool_url'])): ?>
                    <div class="mb-3">
                        <strong>Tool URL:</strong> 
                        <a href="<?php echo htmlspecialchars($tool['tool_url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($tool['tool_url']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($tool['created_at'])): ?>
                    <div class="mb-3">
                        <strong>Created:</strong> 
                        <?php echo date('M j, Y', strtotime($tool['created_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($tool['updated_at'])): ?>
                    <div class="mb-3">
                        <strong>Updated:</strong> 
                        <?php echo date('M j, Y', strtotime($tool['updated_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($tool['published_at']) && !empty($tool['published_at'])): ?>
                    <div class="mb-3">
                        <strong>Published Date:</strong> 
                        <?php echo date('M j, Y', strtotime($tool['published_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'title', 'description', 'category_id', 'category_name', 'tool_url', 
                                  'pricing_type', 'price_info', 'features', 'rating', 'featured', 'is_published', 
                                  'slug', 'published_at', 'created_at', 'updated_at'];
                    foreach ($tool as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' . 
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>
                
                <?php if (!empty($tool['description'])): ?>
                <div class="content-preview mb-4">
                    <h3 class="mb-3">Description</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php 
                        // Check if description might be HTML
                        if (strpos($tool['description'], '<') !== false && strpos($tool['description'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $tool['description']; 
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($tool['description']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($tool['features'])): ?>
                <div class="content-preview">
                    <h3 class="mb-3">Features</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php 
                        // Check if features might be HTML
                        if (strpos($tool['features'], '<') !== false && strpos($tool['features'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $tool['features']; 
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($tool['features']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="ai-tools.php" class="btn btn-secondary">
                Back to AI Tools
            </a>
            <form method="GET" action="ai-tool-form.php">
                <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit
                </button>
            </form>
        </div>
    </div>
</div>

<style>
        .content-body {
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        
        .bg-light {
            background-color: var(--gray-50);
        }
        
        .border {
            border: 1px solid var(--border-color);
        }
        
        .rounded {
            border-radius: var(--radius-md);
        }
        
        .p-4 {
            padding: 1.5rem;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .gap-3 {
            gap: 1rem;
        }
        
        .rating-display {
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>

<?php require_once '../includes/footer.php'; ?>
