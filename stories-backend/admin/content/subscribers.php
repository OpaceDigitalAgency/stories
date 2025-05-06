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
        $pageTitle = $subscriber['email'];
        $currentPage = 'subscribers';
        
        // Include header
        require_once '../includes/header.php';
        ?>
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title"><?php echo htmlspecialchars($subscriber['email']); ?></h1>
                        <p class="page-description">
                            <a href="subscribers.php" class="text-primary">← Back to Subscribers</a>
                        </p>
                    </div>
                </div>

                <div class="content-section mb-4">
                    <div class="section-header">
                        <h2 class="section-title">Details</h2>
                    </div>
                    <div class="section-body">
                        <form method="POST" action="subscribers.php">
                            <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($subscriber['email']); ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo $subscriber['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $subscriber['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
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
    .subscriber-details {
        margin-bottom: 1rem;
    }
    .detail-item {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }
    .detail-item:last-child {
        border-bottom: none;
    }
    .detail-item strong {
        display: block;
        margin-bottom: 0.25rem;
        color: #495057;
    }
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
