<?php
/**
 * Subscribers Admin Page
 * Allows administrators to view and manage premium feature subscribers
 */

// Set page variables for header
$pageTitle = 'Premium Subscribers';
$currentPage = 'subscribers';
$pageDescription = 'Manage users who have subscribed to premium feature notifications';

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Include common admin functions
if (file_exists('../includes/admin-functions.php')) {
    require_once '../includes/admin-functions.php';
} else {
    // Fallback path for admin functions
    require_once dirname(dirname(__FILE__)) . '/includes/admin-functions.php';
}

// Add custom CSS for subscriber details
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
    .message-content, .notes-content {
        background-color: #f8f9fa;
        padding: 0.75rem;
        border-radius: 0.25rem;
        margin-top: 0.25rem;
    }
    .table-actions {
        display: flex;
        gap: 0.5rem;
    }
    .badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
    /* Fix for Bootstrap modal issues */
    .modal-open {
        overflow: auto;
        padding-right: 0 !important;
    }
    .modal-backdrop {
        z-index: 1040;
    }
    .modal {
        z-index: 1050;
    }
    /* Make sure modals don\'t show by default */
    .modal.fade:not(.show) {
        display: none;
    }
</style>';

// Add Bootstrap JS for modals
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>';

// Add custom JavaScript for notification functionality
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle bulk action change
    const bulkActionSelect = document.getElementById("bulk-action");
    const applyButton = document.getElementById("apply-bulk-action");
    const notificationModal = new bootstrap.Modal(document.getElementById("notificationModal"));

    if (bulkActionSelect && applyButton) {
        // Override the bulk action form submission
        const bulkForm = document.getElementById("bulk-actions-form");
        if (bulkForm) {
            bulkForm.addEventListener("submit", function(e) {
                const action = bulkActionSelect.value;

                // If action is notify, show the notification modal instead
                if (action === "notify") {
                    e.preventDefault();

                    // Get selected IDs
                    const selectedIds = [];
                    document.querySelectorAll(".bulk-checkbox:checked").forEach(function(checkbox) {
                        selectedIds.push(checkbox.value);

                        // Create hidden inputs for the notification form
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = "selected_ids[]";
                        input.value = checkbox.value;
                        document.getElementById("notification-selected-ids").appendChild(input);
                    });

                    if (selectedIds.length === 0) {
                        alert("Please select at least one subscriber to notify.");
                        return;
                    }

                    // Show the notification modal
                    notificationModal.show();
                }
            });
        }
    }

    // Handle single notify buttons
    document.querySelectorAll(".notify-single-btn").forEach(function(button) {
        button.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            const email = this.getAttribute("data-email");
            const feature = this.getAttribute("data-feature");

            // Clear previous selected IDs
            document.getElementById("notification-selected-ids").innerHTML = "";

            // Add the selected ID
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "selected_ids[]";
            input.value = id;
            document.getElementById("notification-selected-ids").appendChild(input);

            // Update the message template with the subscriber info
            const messageField = document.getElementById("notification-message");
            const defaultMessage = messageField.value;

            // Show the notification modal
            notificationModal.show();
        });
    });
});
</script>';

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

        // Log successful connection
        error_log("Connected to database in subscribers.php");
    } catch (PDOException $e) {
        $errorMessage = "Database connection error: " . $e->getMessage();
        error_log("Database connection error in subscribers.php: " . $e->getMessage());
    }
}

// Check if subscribers table exists, create if not
try {
    if (isset($db) && $db) {
        $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
        if ($stmt->rowCount() === 0) {
            error_log("Creating subscribers table as it doesn't exist");
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
            error_log("Subscribers table created successfully");
        } else {
            error_log("Subscribers table already exists");
        }
    }
} catch (PDOException $e) {
    $errorMessage = "Error checking/creating subscribers table: " . $e->getMessage();
    error_log("Error checking/creating subscribers table: " . $e->getMessage());
}

// Handle contact status update
if (isset($_POST['update_contact_status'])) {
    $subscriberId = (int)$_POST['subscriber_id'];
    $isContacted = isset($_POST['is_contacted']) ? 1 : 0;
    $adminNotes = $_POST['admin_notes'] ?? '';

    try {
        error_log("Updating subscriber status - ID: {$subscriberId}, Contacted: {$isContacted}");
        $stmt = $db->prepare("UPDATE subscribers SET is_contacted = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$isContacted, $adminNotes, $subscriberId]);

        $successMessage = "Subscriber status updated successfully.";
        error_log("Subscriber status updated successfully");
    } catch (PDOException $e) {
        $errorMessage = "Error updating subscriber: " . $e->getMessage();
        error_log("Error updating subscriber: " . $e->getMessage());
    }
}

// Get subscribers list
$subscribers = [];
$features = [];

try {
    if (isset($db) && $db) {
        // Get filter parameters
        $feature = $_GET['feature'] ?? '';
        $contactStatus = isset($_GET['contact_status']) ? (int)$_GET['contact_status'] : -1;
        $search = $_GET['search'] ?? '';

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

        if (!empty($search)) {
            $query .= " AND (email LIKE ? OR name LIKE ? OR feature LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $query .= " ORDER BY created_at DESC";

        // Add pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $offset = ($page - 1) * $perPage;

        // Get total count for pagination
        $countQuery = str_replace("SELECT *", "SELECT COUNT(*)", $query);
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $perPage);

        // Add limit to the main query
        $query .= " LIMIT " . intval($offset) . ", " . intval($perPage);

        error_log("Executing subscriber query: {$query} with params: " . print_r($params, true));
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $subscribers = $stmt->fetchAll();
        error_log("Found " . count($subscribers) . " subscribers");

        // Get distinct features for filter
        $featuresStmt = $db->query("SELECT DISTINCT feature FROM subscribers ORDER BY feature");
        $features = $featuresStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Found " . count($features) . " distinct features");
    } else {
        error_log("Database connection not available for subscriber query");
    }
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    error_log("Database error in subscribers.php: " . $e->getMessage());
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page header is already included in header.php, so we don't need to repeat it here -->

        <!-- Alerts -->
        <?php if (isset($successMessage)): ?>
            <div class="success" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($infoMessage)): ?>
            <div class="info" role="alert">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($infoMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Search and filter section -->
        <div class="search-container">
            <form method="get" class="search-form">
                <div class="search-input-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search subscribers..."
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <select name="feature" class="form-select">
                        <option value="">All Features</option>
                        <?php foreach ($features as $featureOption): ?>
                            <option value="<?php echo htmlspecialchars($featureOption); ?>" <?php echo ($feature ?? '') === $featureOption ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($featureOption)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <select name="contact_status" class="form-select">
                        <option value="-1" <?php echo ($contactStatus ?? -1) === -1 ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="0" <?php echo ($contactStatus ?? -1) === 0 ? 'selected' : ''; ?>>Not Contacted</option>
                        <option value="1" <?php echo ($contactStatus ?? -1) === 1 ? 'selected' : ''; ?>>Contacted</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>

                <a href="subscribers.php" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>

        <!-- Bulk actions component -->
        <?php
        // Try multiple paths for the bulk actions component
        $bulkActionsComponentPaths = [
            '../includes/bulk-actions-component.php',
            dirname(dirname(__FILE__)) . '/includes/bulk-actions-component.php',
            'includes/bulk-actions-component.php'
        ];

        $bulkActionsIncluded = false;
        foreach ($bulkActionsComponentPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $bulkActionsIncluded = true;
                break;
            }
        }

        // If we have the enhanced function, use it, otherwise fall back to the basic one
        if (function_exists('renderEnhancedBulkActionsComponent')) {
            renderEnhancedBulkActionsComponent('subscribers', ['delete', 'mark_contacted', 'mark_not_contacted', 'notify']);
        } else if (function_exists('renderBulkActionsComponent')) {
            renderBulkActionsComponent('subscribers', ['delete', 'mark_contacted', 'mark_not_contacted', 'notify']);
        } else {
            // Fallback rendering if no component function is available
            echo '<div class="bulk-actions-container">';
            echo '<form id="bulk-actions-form" method="post" action="bulk-subscribers.php">';
            echo '<div class="bulk-actions">';
            echo '<select id="bulk-action" name="action" class="form-select">';
            echo '<option value="">Bulk Actions</option>';
            echo '<option value="delete">Delete</option>';
            echo '<option value="mark_contacted">Mark Contacted</option>';
            echo '<option value="mark_not_contacted">Mark Not Contacted</option>';
            echo '<option value="notify">Notify</option>';
            echo '</select>';
            echo '<button type="submit" id="apply-bulk-action" class="btn btn-primary">Apply</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
        }
        ?>

        <!-- Notification Modal -->
        <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Notification to Subscribers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="notification-form" method="post" action="bulk-subscribers.php">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="notify">
                            <div id="notification-selected-ids"></div>

                            <div class="form-group mb-3">
                                <label for="notification-subject" class="form-label">Email Subject</label>
                                <input type="text" class="form-control" id="notification-subject" name="notification_subject"
                                       value="Update from Stories From The Web" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="notification-message" class="form-label">Message</label>
                                <p class="text-muted small">You can use [NAME] and [FEATURE] as placeholders that will be replaced with the subscriber's information.</p>
                                <textarea class="form-control" id="notification-message" name="notification_message" rows="6" required>Hello [NAME],

We're excited to share that we have updates about [FEATURE] that you subscribed to. Stay tuned for more information coming soon!

Best regards,
The Stories From The Web Team</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Notification</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table component -->
        <?php
        // Include enhanced table component
        include_once '../includes/enhanced-table-component.php';

        // Define columns
        $columns = [
            'email' => 'Email',
            'name' => 'Name',
            'feature' => 'Feature',
            'created_at' => 'Date',
            'is_contacted' => 'Status'
        ];

        // Define custom formatters
        $customFormatters = [
            'feature' => function($subscriber, $key) {
                return '<span class="badge bg-info text-dark">' .
                       htmlspecialchars(ucfirst($subscriber[$key])) .
                       '</span>';
            },
            'created_at' => function($subscriber, $key) {
                return date('M d, Y', strtotime($subscriber[$key]));
            },
            'is_contacted' => function($subscriber, $key) {
                if ($subscriber[$key]) {
                    return '<span class="badge bg-success">Contacted</span>';
                } else {
                    return '<span class="badge bg-warning text-dark">Not Contacted</span>';
                }
            }
        ];

        // Define custom actions
        $customActions = function($subscriber) {
            $output = '';

            // Edit button
            $output .= '<button type="button" class="btn btn-sm btn-primary" ' .
                       'data-bs-toggle="modal" ' .
                       'data-bs-target="#subscriberModal' . $subscriber['id'] . '">' .
                       '<i class="fas fa-edit"></i> Edit' .
                       '</button> ';

            // View button
            $output .= '<button type="button" class="btn btn-sm btn-info" ' .
                       'data-bs-toggle="modal" ' .
                       'data-bs-target="#viewModal' . $subscriber['id'] . '">' .
                       '<i class="fas fa-eye"></i> View' .
                       '</button> ';

            // Notify button
            $output .= '<button type="button" class="btn btn-sm btn-success notify-single-btn" ' .
                       'data-id="' . $subscriber['id'] . '" ' .
                       'data-email="' . htmlspecialchars($subscriber['email']) . '" ' .
                       'data-feature="' . htmlspecialchars($subscriber['feature']) . '">' .
                       '<i class="fas fa-envelope"></i> Notify' .
                       '</button>';

            return $output;
        };

        // Define which fields are editable inline
        $editableFields = ['email', 'name'];

        // Render the table using the appropriate function
        if (function_exists('renderEnhancedTable')) {
            // Prepare data for the enhanced table
            $tableData = [];
            foreach ($subscribers as $subscriber) {
                // Format the status
                $status = $subscriber['is_contacted'] ? 'Contacted' : 'Not Contacted';

                // Add the item to the table data
                $tableData[] = [
                    'id' => $subscriber['id'],
                    'email' => $subscriber['email'],
                    'name' => $subscriber['name'] ?? '',
                    'feature' => ucfirst($subscriber['feature']),
                    'created_at' => date('M d, Y', strtotime($subscriber['created_at'])),
                    'is_contacted' => $status,
                    'message' => $subscriber['message'] ?? '',
                    'admin_notes' => $subscriber['admin_notes'] ?? ''
                ];
            }

            // Render the enhanced table
            renderEnhancedTable(
                $tableData,
                $columns,
                'subscriber', // This must match a key in the $tableMap array in update-field.php
                'subscribers-table',
                [
                    'showCheckboxes' => true,
                    'showActions' => true,
                    'actions' => ['view', 'edit', 'delete'],
                    'editableFields' => $editableFields,
                    'bulkActions' => ['delete', 'mark_contacted', 'mark_not_contacted', 'notify'],
                    'itemsPerPage' => $perPage,
                    'currentPage' => $page
                ]
            );
        } else {
            // Fallback if no table rendering function is available
            echo '<div class="alert alert-warning">Table component not available. Please check your installation.</div>';

            // Basic table rendering
            echo '<div class="table-responsive">';
            echo '<table class="table">';
            echo '<thead><tr>';
            foreach ($columns as $key => $label) {
                echo '<th>' . htmlspecialchars($label) . '</th>';
            }
            echo '<th>Actions</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            if (empty($subscribers)) {
                echo '<tr><td colspan="' . (count($columns) + 1) . '" class="text-center">No subscribers found.</td></tr>';
            } else {
                foreach ($subscribers as $subscriber) {
                    echo '<tr>';
                    foreach ($columns as $key => $label) {
                        echo '<td>';
                        if (isset($customFormatters[$key])) {
                            echo $customFormatters[$key]($subscriber, $key);
                        } else {
                            echo isset($subscriber[$key]) ? htmlspecialchars($subscriber[$key]) : '';
                        }
                        echo '</td>';
                    }
                    echo '<td>' . $customActions($subscriber) . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
        ?>

        <!-- Modals for each subscriber -->
            <?php if (!empty($subscribers)): ?>
                <?php foreach ($subscribers as $subscriber): ?>
                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?php echo $subscriber['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Subscriber Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="subscriber-details">
                                        <div class="detail-item">
                                            <strong>Email:</strong>
                                            <span><?php echo htmlspecialchars($subscriber['email']); ?></span>
                                        </div>

                                        <?php if (!empty($subscriber['name'])): ?>
                                        <div class="detail-item">
                                            <strong>Name:</strong>
                                            <span><?php echo htmlspecialchars($subscriber['name']); ?></span>
                                        </div>
                                        <?php endif; ?>

                                        <div class="detail-item">
                                            <strong>Feature:</strong>
                                            <span><?php echo htmlspecialchars(ucfirst($subscriber['feature'])); ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <strong>Subscription Date:</strong>
                                            <span><?php echo date('F d, Y H:i', strtotime($subscriber['created_at'])); ?></span>
                                        </div>

                                        <div class="detail-item">
                                            <strong>Status:</strong>
                                            <span>
                                                <?php if ($subscriber['is_contacted']): ?>
                                                    <span class="badge bg-success">Contacted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Not Contacted</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($subscriber['message'])): ?>
                                        <div class="detail-item">
                                            <strong>Message:</strong>
                                            <div class="message-content">
                                                <?php echo nl2br(htmlspecialchars($subscriber['message'])); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($subscriber['admin_notes'])): ?>
                                        <div class="detail-item">
                                            <strong>Admin Notes:</strong>
                                            <div class="notes-content">
                                                <?php echo nl2br(htmlspecialchars($subscriber['admin_notes'])); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#subscriberModal<?php echo $subscriber['id']; ?>"
                                            data-bs-dismiss="modal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Modal -->
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

                                        <div class="form-group mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($subscriber['email']); ?>" readonly>
                                        </div>

                                        <?php if (!empty($subscriber['name'])): ?>
                                        <div class="form-group mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($subscriber['name']); ?>" readonly>
                                        </div>
                                        <?php endif; ?>

                                        <div class="form-group mb-3">
                                            <label class="form-label">Feature</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($subscriber['feature'])); ?>" readonly>
                                        </div>

                                        <?php if (!empty($subscriber['message'])): ?>
                                        <div class="form-group mb-3">
                                            <label class="form-label">Message</label>
                                            <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($subscriber['message']); ?></textarea>
                                        </div>
                                        <?php endif; ?>

                                        <div class="form-group mb-3">
                                            <label class="form-label">Subscription Date</label>
                                            <input type="text" class="form-control" value="<?php echo date('F d, Y H:i', strtotime($subscriber['created_at'])); ?>" readonly>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input type="checkbox" class="form-check-input" id="isContacted<?php echo $subscriber['id']; ?>" name="is_contacted" <?php echo $subscriber['is_contacted'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isContacted<?php echo $subscriber['id']; ?>">Mark as contacted</label>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="adminNotes<?php echo $subscriber['id']; ?>" class="form-label">Admin Notes</label>
                                            <textarea class="form-control" id="adminNotes<?php echo $subscriber['id']; ?>" name="admin_notes" rows="3"><?php echo htmlspecialchars($subscriber['admin_notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?php echo count($subscribers); ?> of <?php echo $totalItems; ?> subscribers
                </div>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <div class="page-item">
                            <a href="?page=1<?php echo !empty($feature) ? '&feature=' . urlencode($feature) : ''; ?><?php echo isset($contactStatus) && $contactStatus !== -1 ? '&contact_status=' . $contactStatus : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </div>
                        <div class="page-item">
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($feature) ? '&feature=' . urlencode($feature) : ''; ?><?php echo isset($contactStatus) && $contactStatus !== -1 ? '&contact_status=' . $contactStatus : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    if ($endPage - $startPage < 4 && $startPage > 1) {
                        $startPage = max(1, $endPage - 4);
                    }

                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <div class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?><?php echo !empty($feature) ? '&feature=' . urlencode($feature) : ''; ?><?php echo isset($contactStatus) && $contactStatus !== -1 ? '&contact_status=' . $contactStatus : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                <?php echo $i; ?>
                            </a>
                        </div>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <div class="page-item">
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($feature) ? '&feature=' . urlencode($feature) : ''; ?><?php echo isset($contactStatus) && $contactStatus !== -1 ? '&contact_status=' . $contactStatus : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </div>
                        <div class="page-item">
                            <a href="?page=<?php echo $totalPages; ?><?php echo !empty($feature) ? '&feature=' . urlencode($feature) : ''; ?><?php echo isset($contactStatus) && $contactStatus !== -1 ? '&contact_status=' . $contactStatus : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-link">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="items-per-page">
                    <span>Items per page:</span>
                    <select name="per_page" onchange="window.location.href=this.value">
                        <?php foreach ([10, 20, 50, 100] as $perPageOption): ?>
                            <option value="?page=1&per_page=<?php echo $perPageOption; ?><?php echo !empty($feature) ? '&feature=' . urlencode($feature) : ''; ?><?php echo isset($contactStatus) && $contactStatus !== -1 ? '&contact_status=' . $contactStatus : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" <?php echo ($perPage ?? 20) == $perPageOption ? 'selected' : ''; ?>>
                                <?php echo $perPageOption; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
