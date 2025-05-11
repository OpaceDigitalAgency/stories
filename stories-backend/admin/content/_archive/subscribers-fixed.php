<?php
/**
 * Subscribers Admin Page
 * Allows administrators to view and manage premium feature subscribers
 */

// Set page variables for header
$pageTitle = 'Premium Subscribers';
$currentPage = 'subscribers';
$pageDescription = 'Manage users who have subscribed to premium feature notifications';

// Include header (which includes auth check and DB connection)
require_once '../includes/header.php';

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
    }
}

// Check if subscribers table exists, create if not
try {
    if (isset($db) && $db) {
        $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
        if ($stmt->rowCount() === 0) {
            $db->exec("CREATE TABLE subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                name VARCHAR(255),
                feature VARCHAR(100) NOT NULL,
                message TEXT,
                is_contacted TINYINT(1) DEFAULT 0,
                admin_notes TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $infoMessage = "Subscribers table created successfully. You can now start collecting subscriber information.";
        }
    }
} catch (PDOException $e) {
    $errorMessage = "Error checking/creating subscribers table: " . $e->getMessage();
}

// Handle contact status update
if (isset($_POST['update_contact_status']) && isset($db) && $db) {
    $subscriberId = (int)$_POST['subscriber_id'];
    $isContacted = isset($_POST['is_contacted']) ? 1 : 0;
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    try {
        $stmt = $db->prepare("UPDATE subscribers SET is_contacted = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$isContacted, $adminNotes, $subscriberId]);
        
        $successMessage = "Subscriber status updated successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error updating subscriber: " . $e->getMessage();
    }
}

// Get subscribers list
$subscribers = [];
$features = [];

if (isset($db) && $db) {
    try {
        // Get filter parameters
        $feature = $_GET['feature'] ?? '';
        $contactStatus = isset($_GET['contact_status']) ? (int)$_GET['contact_status'] : -1;
        
        // Build query
        $query = "SELECT * FROM subscribers WHERE 1=1";
        $params = [];
        
        if (!empty($feature)) {
            $query .= " AND feature = ?";
            $params[] = $feature;
        }
        
        if ($contactStatus !== -1) {
            $query .= " AND is_contacted = ?";
            $params[] = $contactStatus;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $subscribers = $stmt->fetchAll();
        
        // Get distinct features for filter
        $featuresStmt = $db->query("SELECT DISTINCT feature FROM subscribers ORDER BY feature");
        $features = $featuresStmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="page-title">Premium Subscribers</h1>
                <p class="text-muted">Manage users who have subscribed to premium feature notifications</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="../dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($infoMessage)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $infoMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Filter Subscribers</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="feature" class="form-label">Feature</label>
                        <select name="feature" id="feature" class="form-select">
                            <option value="">All Features</option>
                            <?php foreach ($features as $featureOption): ?>
                                <option value="<?php echo htmlspecialchars($featureOption); ?>" <?php echo $feature === $featureOption ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($featureOption)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="contact_status" class="form-label">Contact Status</label>
                        <select name="contact_status" id="contact_status" class="form-select">
                            <option value="-1" <?php echo $contactStatus === -1 ? 'selected' : ''; ?>>All</option>
                            <option value="0" <?php echo $contactStatus === 0 ? 'selected' : ''; ?>>Not Contacted</option>
                            <option value="1" <?php echo $contactStatus === 1 ? 'selected' : ''; ?>>Contacted</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="subscribers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Subscribers List</h5>
                <span class="badge bg-primary"><?php echo count($subscribers); ?> subscribers</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Feature</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No subscribers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscribers as $subscriber): ?>
                                    <tr>
                                        <td><?php echo $subscriber['id']; ?></td>
                                        <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                        <td><?php echo htmlspecialchars($subscriber['name'] ?? 'Not provided'); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars(ucfirst($subscriber['feature'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($subscriber['created_at'])); ?></td>
                                        <td>
                                            <?php if ($subscriber['is_contacted']): ?>
                                                <span class="badge bg-success">Contacted</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Contacted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#subscriberModal<?php echo $subscriber['id']; ?>">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal for each subscriber -->
                                    <div class="modal fade" id="subscriberModal<?php echo $subscriber['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Subscriber</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                        <input type="hidden" name="update_contact_status" value="1">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($subscriber['email']); ?>" readonly>
                                                        </div>
                                                        
                                                        <?php if (!empty($subscriber['name'])): ?>
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($subscriber['name']); ?>" readonly>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Feature</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($subscriber['feature'])); ?>" readonly>
                                                        </div>
                                                        
                                                        <?php if (!empty($subscriber['message'])): ?>
                                                        <div class="mb-3">
                                                            <label class="form-label">Message</label>
                                                            <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($subscriber['message']); ?></textarea>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Subscription Date</label>
                                                            <input type="text" class="form-control" value="<?php echo date('F d, Y H:i', strtotime($subscriber['created_at'])); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="mb-3 form-check">
                                                            <input type="checkbox" class="form-check-input" id="isContacted<?php echo $subscriber['id']; ?>" name="is_contacted" <?php echo $subscriber['is_contacted'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="isContacted<?php echo $subscriber['id']; ?>">Mark as contacted</label>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="adminNotes<?php echo $subscriber['id']; ?>" class="form-label">Admin Notes</label>
                                                            <textarea class="form-control" id="adminNotes<?php echo $subscriber['id']; ?>" name="admin_notes" rows="3"><?php echo htmlspecialchars($subscriber['admin_notes'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
