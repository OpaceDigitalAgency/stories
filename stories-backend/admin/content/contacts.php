<?php
/**
 * Admin page for managing contact form submissions
 */

// Set page variables for header
$pageTitle = 'Contact Form Submissions';
$currentPage = 'contacts';
$pageDescription = 'Manage and respond to contact form submissions from website visitors.';

// Include database connection first
include_once '../includes/db-connect.php';

// Include common admin files
include_once '../includes/functions.php';
include_once '../includes/admin-functions.php';
include_once '../includes/email-functions.php';

// Include header after database connection
include_once '../includes/header.php';

// Add custom CSS for contact details
echo '<style>
    .contact-details {
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
                        alert("Please select at least one contact to notify.");
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
            const subject = this.getAttribute("data-subject");

            // Clear previous selected IDs
            document.getElementById("notification-selected-ids").innerHTML = "";

            // Add the selected ID
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "selected_ids[]";
            input.value = id;
            document.getElementById("notification-selected-ids").appendChild(input);

            // Update the message template with the contact info
            const messageField = document.getElementById("notification-message");
            const defaultMessage = messageField.value;

            // Show the notification modal
            notificationModal.show();
        });
    });
});
</script>';

// Process form submissions
$successMessage = '';
$errorMessage = '';

// Ensure we have a database connection
if (!isset($db) || !$db) {
    error_log("Database connection not available in contacts.php");
    $errorMessage = "Database connection error. Please check the server logs.";
}

// Check if contacts table exists, create if not
try {
    if (isset($db) && $db) {
        $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
        if ($stmt->rowCount() === 0) {
            error_log("Creating contacts table as it doesn't exist");
            $db->exec("CREATE TABLE contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_responded TINYINT(1) DEFAULT 0,
                admin_notes TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $infoMessage = "Contacts table created successfully. You can now start collecting contact form submissions.";
            error_log("Contacts table created successfully");
        } else {
            error_log("Contacts table already exists");
        }
    }
} catch (PDOException $e) {
    $errorMessage = "Error checking/creating contacts table: " . $e->getMessage();
    error_log("Error checking/creating contacts table: " . $e->getMessage());
}

// Handle contact status update
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['contact_id'])) {
    $contactId = (int)$_POST['contact_id'];
    $isResponded = isset($_POST['is_responded']) ? 1 : 0;
    $adminNotes = $_POST['admin_notes'] ?? '';

    try {
        $stmt = $db->prepare("UPDATE contacts SET is_responded = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$isResponded, $adminNotes, $contactId]);

        $successMessage = "Contact status updated successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error updating contact: " . $e->getMessage();
    }
}

// Handle sending response
if (isset($_POST['action']) && $_POST['action'] === 'send_response' && isset($_POST['contact_id'])) {
    $contactId = (int)$_POST['contact_id'];
    $responseMessage = $_POST['response_message'] ?? '';

    if (empty($responseMessage)) {
        $errorMessage = "Response message cannot be empty.";
    } else {
        try {
            // Get contact details
            $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->execute([$contactId]);
            $contact = $stmt->fetch();

            if ($contact) {
                // Send email
                $to = $contact['email'];
                $subject = "Re: " . $contact['subject'];
                $message = $responseMessage;
                $headers = "From: Stories From The Web <noreply@storiesfromtheweb.org>\r\n";
                $headers .= "Reply-To: support@storiesfromtheweb.org\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

                // Use our sendEmail function instead of mail()
                $mailResult = sendEmail($to, $subject, $message, $headers);

                if ($mailResult) {
                    error_log("Individual response sent successfully to {$to}");

                    // Update contact as responded
                    $stmt = $db->prepare("UPDATE contacts SET is_responded = 1, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n\nResponse sent on " . date('Y-m-d H:i:s') . ":\n', ?) WHERE id = ?");
                    $stmt->execute([$responseMessage, $contactId]);

                    $successMessage = "Response sent successfully to " . htmlspecialchars($contact['email']);
                } else {
                    error_log("Failed to send individual response to {$to}. PHP mail() function returned false.");
                    $errorMessage = "Failed to send email. Please check server configuration.";
                }
            } else {
                $errorMessage = "Contact not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error sending response: " . $e->getMessage();
        }
    }
}

// Get contacts list
$contacts = [];

try {
    if (isset($db) && $db) {
        // Get filter parameters
        $search = $_GET['search'] ?? '';
        $responseStatus = isset($_GET['response_status']) ? (int)$_GET['response_status'] : -1;

        // Build query
        $query = "SELECT * FROM contacts WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if ($responseStatus !== -1) {
            $query .= " AND is_responded = ?";
            $params[] = $responseStatus;
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

        error_log("Executing contacts query: {$query} with params: " . print_r($params, true));
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();
        error_log("Found " . count($contacts) . " contacts");
    }
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

// Custom formatters for the table
$customFormatters = [
    'is_responded' => function($value) {
        return $value ? '<span class="badge bg-success">Responded</span>' : '<span class="badge bg-warning">Not Responded</span>';
    },
    'created_at' => function($value) {
        return date('M d, Y H:i', strtotime($value));
    },
    'message' => function($value) {
        return '<div class="message-preview">' . htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . '</div>';
    }
];
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page header is already included in header.php, so we don't need to repeat it here -->

        <!-- Alerts -->
        <?php if (isset($successMessage) && !empty($successMessage)): ?>
            <div class="success" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
            <div class="error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($infoMessage) && !empty($infoMessage)): ?>
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
                    <input type="text" name="search" class="search-input" placeholder="Search contacts..."
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>

                <div class="filter-group">
                    <select name="response_status" class="form-select">
                        <option value="-1" <?php echo (!isset($_GET['response_status']) || $_GET['response_status'] == -1) ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="0" <?php echo (isset($_GET['response_status']) && $_GET['response_status'] == 0) ? 'selected' : ''; ?>>Not Responded</option>
                        <option value="1" <?php echo (isset($_GET['response_status']) && $_GET['response_status'] == 1) ? 'selected' : ''; ?>>Responded</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>

                <a href="contacts.php" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>

        <!-- Include bulk actions component -->
        <?php
        include_once '../includes/bulk-actions-component.php';
        if (function_exists('renderBulkActionsComponent')) {
            renderBulkActionsComponent('contacts', ['delete', 'mark_responded', 'mark_not_responded', 'notify']);
        }
        ?>

        <!-- Notification Modal -->
        <div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Response to Contacts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="notification-form" method="post" action="bulk-contacts.php">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="notify">
                            <div id="notification-selected-ids"></div>

                            <div class="form-group mb-3">
                                <label for="notification-subject" class="form-label">Email Subject</label>
                                <input type="text" class="form-control" id="notification-subject" name="notification_subject"
                                       value="Response from Stories From The Web" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="notification-message" class="form-label">Message</label>
                                <p class="text-muted small">You can use [NAME] and [SUBJECT] as placeholders that will be replaced with the contact's information.</p>
                                <textarea class="form-control" id="notification-message" name="notification_message" rows="6" required>Dear [NAME],

Thank you for contacting Stories From The Web regarding "[SUBJECT]".

[Your response here]

Best regards,
The Stories From The Web Team</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Response</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Include table component -->
        <?php
        include_once '../includes/table-component.php';
        if (function_exists('renderTable')) {
            // Define columns
            $columns = [
                'name' => 'Name',
                'email' => 'Email',
                'subject' => 'Subject',
                'message' => 'Message',
                'created_at' => 'Date',
                'is_responded' => 'Status'
            ];

            // Define custom formatters
            $customFormatters = [
                'message' => function($contact, $key) {
                    return '<div class="message-preview">' .
                           htmlspecialchars(substr($contact[$key], 0, 100)) .
                           (strlen($contact[$key]) > 100 ? '...' : '') .
                           '</div>';
                },
                'created_at' => function($contact, $key) {
                    return date('M d, Y H:i', strtotime($contact[$key]));
                },
                'is_responded' => function($contact, $key) {
                    if ($contact[$key]) {
                        return '<span class="badge bg-success">Responded</span>';
                    } else {
                        return '<span class="badge bg-warning text-dark">Not Responded</span>';
                    }
                }
            ];

            // Define custom actions
            $customActions = function($contact) {
                $output = '';

                // View button
                $output .= '<button type="button" class="btn btn-sm btn-info" ' .
                           'data-bs-toggle="modal" ' .
                           'data-bs-target="#viewModal' . $contact['id'] . '">' .
                           '<i class="fas fa-eye"></i> View' .
                           '</button> ';

                // Respond button
                $output .= '<button type="button" class="btn btn-sm btn-success notify-single-btn" ' .
                           'data-id="' . $contact['id'] . '" ' .
                           'data-email="' . htmlspecialchars($contact['email']) . '" ' .
                           'data-subject="' . htmlspecialchars($contact['subject']) . '">' .
                           '<i class="fas fa-reply"></i> Respond' .
                           '</button>';

                return $output;
            };

            // Render the table
            renderTable($contacts, $columns, [
                'content_type' => 'contacts',
                'name_field' => 'name',
                'empty_message' => 'No contact submissions found.',
                'custom_formatters' => $customFormatters,
                'custom_actions' => $customActions,
                'actions' => [
                    'view' => false,
                    'edit' => false,
                    'delete' => false
                ]
            ]);
        }

        // Add pagination
        if (isset($totalPages) && $totalPages > 1) {
            echo '<div class="pagination-container">';
            echo '<ul class="pagination">';

            // Previous page link
            if ($page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '&search=' . urlencode($search) . '&response_status=' . $responseStatus . '">&laquo; Previous</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
            }

            // Page numbers
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            if ($startPage > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&response_status=' . $responseStatus . '">1</a></li>';
                if ($startPage > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                } else {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&response_status=' . $responseStatus . '">' . $i . '</a></li>';
                }
            }

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&response_status=' . $responseStatus . '">' . $totalPages . '</a></li>';
            }

            // Next page link
            if ($page < $totalPages) {
                echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '&search=' . urlencode($search) . '&response_status=' . $responseStatus . '">Next &raquo;</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
            }

            echo '</ul>';
            echo '</div>';
        }
        ?>

        <!-- Modals for each contact -->
        <?php if (!empty($contacts)): ?>
            <?php foreach ($contacts as $contact): ?>
                <!-- View Modal -->
                <div class="modal fade" id="viewModal<?php echo $contact['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Contact Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Contact Information Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Contact Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-3 fw-bold text-secondary">Name:</div>
                                            <div class="col-md-9"><?php echo htmlspecialchars($contact['name']); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-3 fw-bold text-secondary">Email:</div>
                                            <div class="col-md-9"><?php echo htmlspecialchars($contact['email']); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-3 fw-bold text-secondary">Subject:</div>
                                            <div class="col-md-9"><?php echo htmlspecialchars($contact['subject']); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-3 fw-bold text-secondary">Date:</div>
                                            <div class="col-md-9"><?php echo date('M d, Y H:i', strtotime($contact['created_at'])); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-3 fw-bold text-secondary">Status:</div>
                                            <div class="col-md-9">
                                                <?php if ($contact['is_responded']): ?>
                                                    <span class="badge bg-success">Responded</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Not Responded</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Card -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-envelope me-2"></i>Message</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Notes Card (if any) -->
                                <?php if (!empty($contact['admin_notes'])): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Admin Notes</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($contact['admin_notes'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Update Form Card -->
                                <form method="post" class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Update Contact</h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">

                                        <div class="form-group mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_responded<?php echo $contact['id']; ?>"
                                                       name="is_responded" <?php echo $contact['is_responded'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_responded<?php echo $contact['id']; ?>">
                                                    Mark as Responded
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="admin_notes<?php echo $contact['id']; ?>" class="form-label">Admin Notes</label>
                                            <textarea class="form-control" id="admin_notes<?php echo $contact['id']; ?>"
                                                      name="admin_notes" rows="3"><?php echo htmlspecialchars($contact['admin_notes'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Status
                                            </button>
                                            <button type="button" class="btn btn-success notify-single-btn"
                                                    data-id="<?php echo $contact['id']; ?>"
                                                    data-email="<?php echo htmlspecialchars($contact['email']); ?>"
                                                    data-subject="<?php echo htmlspecialchars($contact['subject']); ?>">
                                                <i class="fas fa-reply"></i> Send Response
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
