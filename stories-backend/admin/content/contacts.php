<?php
/**
 * Admin page for managing contact form submissions
 */

// Include auth check first
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include enhanced table component
require_once '../includes/enhanced-table-component.php';

// Include email functions
require_once '../includes/email-functions.php';

// Check if viewing/editing a specific contact
if (isset($_GET['id'])) {
    $contactId = (int)$_GET['id'];

    // Get contact details
    $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch();

    if ($contact) {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isResponded = isset($_POST['is_responded']) ? 1 : 0;
            $adminNotes = $_POST['admin_notes'] ?? '';

            try {
                $stmt = $db->prepare("UPDATE contacts SET is_responded = ?, admin_notes = ? WHERE id = ?");
                $stmt->execute([$isResponded, $adminNotes, $contactId]);
                header("Location: contacts.php");
                exit;
            } catch (PDOException $e) {
                $error = "Error updating contact: " . $e->getMessage();
            }
        }

        $pageTitle = 'View Contact';
        $currentPage = 'contacts';

        // Include header
        require_once '../includes/header.php';
        ?>
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title"><?php echo htmlspecialchars($contact['name']); ?></h1>
                        <p class="page-description">
                            <a href="contacts.php" class="text-primary">← Back to Contacts</a>
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
                                <h2 class="section-title">Contact Details</h2>
                            </div>
                            <div class="section-body">
                                <form method="POST">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($contact['name']); ?>" readonly>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($contact['email']); ?>" readonly>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Subject</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($contact['subject']); ?>" readonly>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-control" rows="5" readonly><?php echo htmlspecialchars($contact['message']); ?></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Admin Notes</label>
                                        <textarea name="admin_notes" class="form-control" rows="3"><?php echo htmlspecialchars($contact['admin_notes'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="is_responded" class="form-check-input" id="is_responded" <?php echo $contact['is_responded'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_responded">Mark as Responded</label>
                                    </div>

                                    <!-- Sticky action bar -->
                                    <div class="sticky-action-bar">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                        <a href="contacts.php" class="btn btn-secondary">Cancel</a>
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
                                    <?php if (isset($contact['created_at'])): ?>
                                    <div class="metadata-item">
                                        <strong>Received:</strong> <?php echo date('M j, Y g:i A', strtotime($contact['created_at'])); ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="metadata-item">
                                        <strong>Status:</strong>
                                        <span class="badge <?php echo $contact['is_responded'] ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $contact['is_responded'] ? 'Responded' : 'Not Responded'; ?>
                                        </span>
                                    </div>

                                    <div class="metadata-item">
                                        <strong>ID:</strong> <?php echo $contact['id']; ?>
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

                    .badge {
                        font-size: 0.85rem;
                        padding: 0.35em 0.65em;
                    }
                </style>
            </div>
        </div>
        <?php
    } else {
        header("Location: contacts.php");
        exit;
    }
} else {
    // List view
    $pageTitle = 'Contact Form Submissions';
    $currentPage = 'contacts';

    // Include header
    require_once '../includes/header.php';
    ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php
            try {
                // Get all contacts
                $stmt = $db->query("SELECT * FROM contacts ORDER BY created_at DESC");
                $contacts = $stmt->fetchAll();

                // Define columns for enhanced table
                $columns = [
                    'name' => 'Name',
                    'email' => 'Email',
                    'subject' => 'Subject',
                    'message' => 'Message',
                    'created_at' => 'Date',
                    'is_responded' => 'Status'
                ];

                // Custom formatters
                $formatters = [
                    'message' => function($value) {
                        return '<div class="message-preview">' . htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . '</div>';
                    },
                    'is_responded' => function($value) {
                        return $value ? '<span class="badge bg-success">Responded</span>' : '<span class="badge bg-warning">Not Responded</span>';
                    },
                    'created_at' => function($value) {
                        return date('M d, Y H:i', strtotime($value));
                    }
                ];

                // Render enhanced table
                renderEnhancedTable(
                    $contacts,
                    $columns,
                    'contact',
                    'contacts-table',
                    [
                        'showCheckboxes' => true,
                        'showActions' => true,
                        'actions' => ['view', 'edit', 'delete'],
                        'htmlFields' => ['message', 'is_responded'],
                        'formatters' => $formatters,
                        'bulkActions' => ['delete', 'mark_responded']
                    ]
                );

            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error loading contacts: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Include footer
require_once '../includes/footer.php';
?>
