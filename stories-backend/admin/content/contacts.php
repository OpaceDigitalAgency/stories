<?php
/**
 * Admin page for managing contact form submissions
 */

// Include common admin files
include_once '../includes/header.php';
include_once '../includes/sidebar.php';
include_once '../includes/functions.php';

// Process form submissions
$successMessage = '';
$errorMessage = '';

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
                
                if (mail($to, $subject, $message, $headers)) {
                    // Update contact as responded
                    $stmt = $db->prepare("UPDATE contacts SET is_responded = 1, admin_notes = CONCAT(admin_notes, '\n\nResponse sent on " . date('Y-m-d H:i:s') . ":\n', ?) WHERE id = ?");
                    $stmt->execute([$responseMessage, $contactId]);
                    
                    $successMessage = "Response sent successfully to " . htmlspecialchars($contact['email']);
                } else {
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
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();
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
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="page-title">Contact Form Submissions</h1>
                <p class="text-muted">Manage and respond to contact form submissions</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="../dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email, subject or message" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="response_status" class="form-label">Response Status</label>
                        <select class="form-select" id="response_status" name="response_status">
                            <option value="-1" <?php echo (!isset($_GET['response_status']) || $_GET['response_status'] == -1) ? 'selected' : ''; ?>>All</option>
                            <option value="0" <?php echo (isset($_GET['response_status']) && $_GET['response_status'] == 0) ? 'selected' : ''; ?>>Not Responded</option>
                            <option value="1" <?php echo (isset($_GET['response_status']) && $_GET['response_status'] == 1) ? 'selected' : ''; ?>>Responded</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Contacts Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Contact Submissions</h5>
                <span class="badge bg-primary"><?php echo count($contacts); ?> contacts</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contacts)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No contact submissions found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contacts as $contact): ?>
                                    <tr>
                                        <td><?php echo $contact['id']; ?></td>
                                        <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                                        <td><?php echo $customFormatters['message']($contact['message']); ?></td>
                                        <td><?php echo $customFormatters['created_at']($contact['created_at']); ?></td>
                                        <td><?php echo $customFormatters['is_responded']($contact['is_responded']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewModal<?php echo $contact['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#responseModal<?php echo $contact['id']; ?>">
                                                <i class="fas fa-reply"></i> Respond
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
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
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($contact['name']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($contact['email']); ?></p>
                                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($contact['subject']); ?></p>
                                        <p><strong>Date:</strong> <?php echo $customFormatters['created_at']($contact['created_at']); ?></p>
                                        <p><strong>Status:</strong> <?php echo $customFormatters['is_responded']($contact['is_responded']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="post" class="border rounded p-3">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_responded<?php echo $contact['id']; ?>" name="is_responded" <?php echo $contact['is_responded'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_responded<?php echo $contact['id']; ?>">
                                                        Mark as Responded
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="admin_notes<?php echo $contact['id']; ?>" class="form-label">Admin Notes</label>
                                                <textarea class="form-control" id="admin_notes<?php echo $contact['id']; ?>" name="admin_notes" rows="4"><?php echo htmlspecialchars($contact['admin_notes'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="mb-3">Message:</h6>
                                    <div class="message-content" style="white-space: pre-wrap;"><?php echo htmlspecialchars($contact['message']); ?></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $contact['id']; ?>" data-bs-dismiss="modal">
                                    <i class="fas fa-reply"></i> Respond
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Response Modal -->
                <div class="modal fade" id="responseModal<?php echo $contact['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Respond to Contact</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="send_response">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <p><strong>To:</strong> <?php echo htmlspecialchars($contact['name']); ?> (<?php echo htmlspecialchars($contact['email']); ?>)</p>
                                        <p><strong>Subject:</strong> Re: <?php echo htmlspecialchars($contact['subject']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="response_message<?php echo $contact['id']; ?>" class="form-label">Response Message</label>
                                        <textarea class="form-control" id="response_message<?php echo $contact['id']; ?>" name="response_message" rows="10" required>Dear <?php echo htmlspecialchars($contact['name']); ?>,

Thank you for contacting Stories From The Web. 

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
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
