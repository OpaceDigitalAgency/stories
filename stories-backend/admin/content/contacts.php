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

// Set page variables
$pageTitle = 'Contact Form Submissions';
$currentPage = 'contacts';

// Include header
require_once '../includes/header.php';

// Add custom CSS
echo '<style>
    .message-preview {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .message-content, .notes-content {
        background-color: #f8f9fa;
        padding: 0.75rem;
        border-radius: 0.25rem;
        margin-top: 0.25rem;
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
// Include footer
require_once '../includes/footer.php';
?>
