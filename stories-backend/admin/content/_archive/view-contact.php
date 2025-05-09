<?php

// Page variables
$pageTitle = 'Contact Details';
$currentPage = 'contacts';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Include database connection
require_once '../includes/db-connect.php';

// Get contact ID from URL
$contactId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$contact = null;
$error = null;
$success = null;

try {
    // Get contact details
    $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch();

    if (!$contact) {
        $error = "Contact not found.";
    }

    // Handle response submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_response') {
        $responseMessage = $_POST['response_message'] ?? '';

        if (empty($responseMessage)) {
            $error = "Response message cannot be empty.";
        } else {
            // Send email
            $to = $contact['email'];
            $subject = "Re: " . $contact['subject'];
            $message = $responseMessage;
            $headers = "From: Stories From The Web <noreply@storiesfromtheweb.org>\r\n";
            $headers .= "Reply-To: support@storiesfromtheweb.org\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

            // Use mail function
            $mailResult = mail($to, $subject, $message, $headers);

            if ($mailResult) {
                // Update contact as responded
                $stmt = $db->prepare("UPDATE contacts SET is_responded = 1, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n\nResponse sent on " . date('Y-m-d H:i:s') . ":\n', ?) WHERE id = ?");
                $stmt->execute([$responseMessage, $contactId]);

                $success = "Response sent successfully to " . htmlspecialchars($contact['email']);
            } else {
                $error = "Failed to send email. Please check server configuration.";
            }
        }
    }

    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $isResponded = isset($_POST['is_responded']) ? 1 : 0;
        $adminNotes = $_POST['admin_notes'] ?? '';

        $stmt = $db->prepare("UPDATE contacts SET is_responded = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$isResponded, $adminNotes, $contactId]);

        $success = "Contact status updated successfully.";

        // Refresh contact data
        $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

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
            <div class="d-flex gap-2">
                <a href="contacts.php?id=<?php echo $contact['id']; ?>" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit
                </a>
            </div>
        </div>

// Display any errors
if ($error) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error</h4>';
    echo '<p>' . htmlspecialchars($error) . '</p>';
    echo '</div>';
}

// Display success message
if ($success) {
    echo '<div class="alert alert-success" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-check-circle"></i> Success</h4>';
    echo '<p>' . htmlspecialchars($success) . '</p>';
    echo '</div>';
}

// Display contact details
if ($contact) {
?>
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Details</h5>
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
            <div class="col-md-9"><?php echo date('F j, Y g:i a', strtotime($contact['created_at'])); ?></div>
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
        <div class="row mb-3">
            <div class="col-md-3 fw-bold text-secondary">Message:</div>
            <div class="col-md-9">
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                </div>
            </div>
        </div>
        <?php if (!empty($contact['admin_notes'])): ?>
        <div class="row mb-3">
            <div class="col-md-3 fw-bold text-secondary">Admin Notes:</div>
            <div class="col-md-9">
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($contact['admin_notes'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-reply me-2"></i>Send Response</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="send_response">
                    <div class="mb-3">
                        <label for="response_message" class="form-label">Response Message</label>
                        <textarea class="form-control" id="response_message" name="response_message" rows="6" required>Dear <?php echo htmlspecialchars($contact['name']); ?>,

Thank you for contacting Stories From The Web regarding "<?php echo htmlspecialchars($contact['subject']); ?>".

[Your response here]

Best regards,
The Stories From The Web Team</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Response</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Update Status</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_responded" name="is_responded" <?php echo $contact['is_responded'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_responded">
                                Mark as Responded
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="6"><?php echo htmlspecialchars($contact['admin_notes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
} else {
    echo '<div class="alert alert-warning" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Contact Not Found</h4>';
    echo '<p>The requested contact could not be found.</p>';
    echo '<a href="contacts.php" class="btn btn-primary">Back to Contacts</a>';
    echo '</div>';
}

// Include footer
require_once '../includes/footer.php';
?>
