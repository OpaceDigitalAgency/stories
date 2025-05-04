<?php

// Page variables
$pageTitle = 'Tag Form';
$currentPage = 'tag-form';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

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
            error_log("Database connection error in tag-form.php: " . $e->getMessage());
        }
    }

    // Get tag if editing
    $tag = null;
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM tags WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $tag = $stmt->fetch();
        
        if (!$tag) {
            header("Location: tags.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("Tag form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Check for error messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo $tag ? 'Edit' : 'Add'; ?> Tag</h1>
                <p class="page-description">
                    <a href="tags.php" class="text-primary">← Back to Tags</a>
                </p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Tag Information</h2>
                <p class="text-muted">Fields marked with <span class="required">*</span> are required</p>
            </div>
            <div class="section-body">
                <form method="POST" action="save-tag.php" class="content-form">
                    <?php if ($tag): ?>
                        <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="name">Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($tag['name'] ?? ''); ?>"
                               onkeyup="generateSlug(this.value)">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                        <input type="text" id="slug" name="slug" class="form-control" required
                               value="<?php echo htmlspecialchars($tag['slug'] ?? ''); ?>">
                        <small>Use lowercase letters, numbers, and hyphens only. No spaces. Will be auto-generated from name.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php 
                            echo htmlspecialchars($tag['description'] ?? ''); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Tag</button>
                        <a href="tags.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($tag): ?>
            <div class="content-section mb-4">
                <div class="section-header">
                    <h2 class="section-title">Metadata</h2>
                </div>
                <div class="section-body">
                    <div class="metadata-list">
                        <?php if (isset($tag['created_at'])): ?>
                        <div class="metadata-item">
                            <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($tag['created_at'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($tag['updated_at'])): ?>
                        <div class="metadata-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($tag['updated_at'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="metadata-item">
                            <strong>ID:</strong> <?php echo $tag['id']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
        .metadata-list {
            background-color: var(--gray-50);
            border-radius: var(--radius-md);
            padding: 1rem;
        }
        
        .metadata-item {
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .metadata-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .text-muted {
            color: var(--gray-600);
            font-size: 0.875rem;
        }
    </style>
    
    <script>
        function generateSlug(name) {
            const slug = name
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('slug').value = slug;
        }
    </script>

<?php require_once '../includes/footer.php'; ?>
