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

                <div class="content-section mb-4">
                    <div class="section-body">
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($subscriber['email']); ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo ($subscriber['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($subscriber['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
