<?php
// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include enhanced table component
require_once '../includes/enhanced-table-component.php';

// Check if viewing/editing a specific subscriber
if (isset($_GET['id'])) {
    $subscriberId = (int)$_GET['id'];

    // Get subscriber details
    $stmt = $db->prepare("SELECT * FROM subscribers WHERE id = ?");
    $stmt->execute([$subscriberId]);
    $subscriber = $stmt->fetch();

    if ($subscriber) {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $status = $_POST['status'] ?? 'active';

            try {
                $stmt = $db->prepare("UPDATE subscribers SET email = ?, status = ? WHERE id = ?");
                $stmt->execute([$email, $status, $subscriberId]);
                header("Location: subscribers.php");
                exit;
            } catch (PDOException $e) {
                $error = "Error updating subscriber: " . $e->getMessage();
            }
        }

        $pageTitle = 'Edit Subscriber';
        $currentPage = 'subscribers';

        // Include header
        require_once '../includes/header.php';
        ?>
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title">Edit Subscriber</h1>
                        <p class="page-description">
                            <a href="subscribers.php" class="text-primary">← Back to Subscribers</a>
                        </p>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Main content column -->
                    <div class="col-md-8">
                        <div class="content-section mb-4">
                            <div class="section-header">
                                <h2 class="section-title">Subscriber Details</h2>
                            </div>
                            <div class="section-body">
                                <form method="POST">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($subscriber['email']); ?>" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" <?php echo ($subscriber['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($subscriber['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <!-- Sticky action bar -->
                                    <div class="sticky-action-bar">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                        <a href="subscribers.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar column -->
                    <div class="col-md-4">
                        <div class="content-section mb-4">
                            <div class="section-header">
                                <h2 class="section-title">Metadata</h2>
                            </div>
                            <div class="section-body">
                                <div class="metadata-list">
                                    <?php if (isset($subscriber['created_at'])): ?>
                                    <div class="metadata-item">
                                        <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($subscriber['created_at'])); ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (isset($subscriber['updated_at'])): ?>
                                    <div class="metadata-item">
                                        <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($subscriber['updated_at'])); ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="metadata-item">
                                        <strong>ID:</strong> <?php echo $subscriber['id']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .sticky-action-bar {
                        position: sticky;
                        bottom: 0;
                        background-color: #fff;
                        padding: 15px;
                        margin: 20px -15px -15px;
                        border-top: 1px solid #ddd;
                        text-align: right;
                        z-index: 100;
                    }

                    .metadata-list {
                        background-color: var(--gray-50, #f8f9fa);
                        border-radius: var(--radius-md, 0.375rem);
                        padding: 1rem;
                    }

                    .metadata-item {
                        margin-bottom: 0.5rem;
                        padding-bottom: 0.5rem;
                        border-bottom: 1px solid var(--gray-200, #e9ecef);
                    }

                    .metadata-item:last-child {
                        margin-bottom: 0;
                        padding-bottom: 0;
                        border-bottom: none;
                    }
                </style>
            </div>
        </div>
        <?php
    } else {
        header("Location: subscribers.php");
        exit;
    }
} else {
    // List view
    $pageTitle = 'Premium Subscribers';
    $currentPage = 'subscribers';

    // Include header
    require_once '../includes/header.php';

    // Add custom CSS
    echo '<style>
        .badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
    </style>';
    ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php
            try {
                // Get all subscribers
                $stmt = $db->query("SELECT * FROM subscribers ORDER BY created_at DESC");
                $subscribers = $stmt->fetchAll();

                // Define columns for enhanced table
                $columns = [
                    'email' => 'Email',
                    'status' => 'Status',
                    'created_at' => 'Created',
                    'updated_at' => 'Updated'
                ];

                // Custom formatters
                $formatters = [
                    'status' => function($value) {
                        $status = $value ?? 'active';
                        return $status === 'active' ?
                            '<span class="badge bg-success">Active</span>' :
                            '<span class="badge bg-warning">Inactive</span>';
                    },
                    'created_at' => function($value) {
                        return date('M d, Y H:i', strtotime($value));
                    },
                    'updated_at' => function($value) {
                        return date('M d, Y H:i', strtotime($value));
                    }
                ];

                // Render enhanced table
                renderEnhancedTable(
                    $subscribers,
                    $columns,
                    'subscriber',
                    'subscribers-table',
                    [
                        'showCheckboxes' => true,
                        'showActions' => true,
                        'actions' => ['view', 'edit', 'delete'],
                        'htmlFields' => ['status'],
                        'formatters' => $formatters,
                        'bulkActions' => ['delete', 'activate', 'deactivate']
                    ]
                );

            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error loading subscribers: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Include footer
require_once '../includes/footer.php';
?>
