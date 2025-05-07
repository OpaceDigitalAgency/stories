<?php
/**
 * Direct Contact Preview Handler
 *
 * This script provides a direct HTML preview of a contact.
 * It's used as a fallback when the AJAX preview fails.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Get contact ID from query string
$contactId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$contact = null;
$error = null;

try {
    // Get contact details
    $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contact) {
        $error = "Contact not found";
    }
} catch (Exception $e) {
    $error = "Error loading contact: " . $e->getMessage();
}

// Set page title
$pageTitle = $contact ? $contact['name'] : 'Contact Preview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .contact-card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .card-header h3 {
            margin: 0 0 10px;
            font-size: 24px;
            color: #212529;
        }
        .card-body {
            padding: 20px;
        }
        .card-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .message-content {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            white-space: pre-line;
        }
        .admin-notes {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }
        .bg-success {
            background-color: #28a745;
            color: white;
        }
        .bg-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .error-message {
            text-align: center;
            padding: 40px 20px;
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error-message">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif ($contact): ?>
            <div class="contact-card">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                        <div><?php echo htmlspecialchars($contact['email']); ?></div>
                    </div>
                    <div class="card-body">
                        <h4><?php echo htmlspecialchars($contact['subject']); ?></h4>
                        <div class="message-content"><?php echo htmlspecialchars($contact['message']); ?></div>
                        <?php if (!empty($contact['admin_notes'])): ?>
                            <div class="admin-notes">
                                <h5>Admin Notes</h5>
                                <div><?php echo htmlspecialchars($contact['admin_notes']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="status">
                            Status: <span class="badge <?php echo $contact['is_responded'] ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $contact['is_responded'] ? 'Responded' : 'Not Responded'; ?>
                            </span>
                        </div>
                        <div class="date">
                            Received: <?php echo date('F j, Y, g:i a', strtotime($contact['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="error-message">
                <h2>Contact Not Found</h2>
                <p>The requested contact could not be found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
