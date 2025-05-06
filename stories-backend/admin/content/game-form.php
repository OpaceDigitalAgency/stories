<?php

// Page variables
$pageTitle = 'Game Form';
$currentPage = 'game-form';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

// Initialize variables
$game = null;
$error = null;

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
            error_log("Database connection error in game-form.php: " . $e->getMessage());
        }
    }

    // Get game if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $game = $stmt->fetch();

        if (!$game) {
            header("Location: games.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("Game form error: " . $e->getMessage());
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
                <h1 class="page-title"><?php echo $game ? 'Edit' : 'Add'; ?> Game</h1>
                <p class="page-description">
                    <a href="games.php" class="text-primary">← Back to Games</a>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Game Information</h2>
                <p class="text-muted">Fields marked with <span class="required">*</span> are required</p>
            </div>
            <div class="section-body">
                <form method="POST" action="save-game.php" class="content-form">
                    <?php if ($game): ?>
                        <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($game['title'] ?? ''); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($game['slug'] ?? ''); ?>">
                        <small>URL-friendly version of the title. Will be auto-generated if left empty.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5"><?php
                            echo htmlspecialchars($game['description'] ?? '');
                        ?></textarea>
                    </div>

                    <?php
                    // Render the image upload component for game image
                    renderImageUploadComponent(
                        'cover_url',
                        $game['cover_url'] ?? '',
                        'Game Image',
                        'game',
                        $game['id'] ?? null
                    );

                    // Add AI image generator button
                    echo '<div class="mt-2">';
                    renderAiImageGenerator(
                        'game',
                        [
                            'title' => $game['title'] ?? '',
                            'description' => $game['description'] ?? '',
                            'genre' => $game['genre'] ?? '',
                            'platform' => $game['platform'] ?? ''
                        ],
                        'cover_url',
                        'cover_url-preview'
                    );
                    echo '</div>';
                    ?>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" value="1" class="form-check-input"
                                   <?php echo (isset($game['featured']) && $game['featured'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="featured">Featured</label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="is_published" name="is_published" value="1" class="form-check-input"
                                   <?php echo (!isset($game['is_published']) || $game['is_published'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Published</label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="published_at">Published at</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-control"
                               value="<?php echo isset($game['published_at']) ? date('Y-m-d\TH:i', strtotime($game['published_at'])) : date('Y-m-d\TH:i'); ?>">
                        <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $game ? 'Update' : 'Add'; ?> Game</button>
                        <a href="games.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($game): ?>
            <div class="content-section mb-4">
                <div class="section-header">
                    <h2 class="section-title">Metadata</h2>
                </div>
                <div class="section-body">
                    <div class="metadata-list">
                        <div class="metadata-item">
                            <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($game['created_at'])); ?>
                        </div>
                        <div class="metadata-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($game['updated_at'])); ?>
                        </div>
                        <div class="metadata-item">
                            <strong>ID:</strong> <?php echo $game['id']; ?>
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

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input {
            margin-top: 0;
        }

        .text-muted {
            color: var(--gray-600);
            font-size: 0.875rem;
        }
    </style>

    <script>
        // Auto-generate slug from title
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');

            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function() {
                    // Only auto-generate if slug is empty or hasn't been manually edited
                    if (!slugInput.value || slugInput._autoGenerated) {
                        const slug = titleInput.value
                            .toLowerCase()
                            .replace(/[^\w\s-]/g, '') // Remove special characters
                            .replace(/\s+/g, '-')     // Replace spaces with hyphens
                            .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen

                        slugInput.value = slug;
                        slugInput._autoGenerated = true;
                    }
                });

                // Mark when user manually edits the slug
                slugInput.addEventListener('input', function() {
                    slugInput._autoGenerated = false;
                });
            }
        });
    </script>

    <!-- Include image upload script -->
    <script src="../assets/js/image-upload.js"></script>

<?php require_once '../includes/footer.php'; ?>
