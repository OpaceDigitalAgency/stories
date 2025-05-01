<?php
/**
 * Bulk Actions Handler for Subscribers
 * 
 * Handles bulk operations on subscribers like delete, mark as contacted, send notifications, etc.
 */

// Include auth check
include_once '../includes/auth-check.php';

// Include database connection
include_once '../includes/db-connect.php';

// Initialize response
$response = [
    'success' => false,
    'message' => 'No action specified',
    'redirect' => 'subscribers.php'
];

// Check if action and selected IDs are provided
if (isset($_POST['action']) && isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
    $action = $_POST['action'];
    $selectedIds = array_map('intval', $_POST['selected_ids']);
    
    // Validate that we have IDs
    if (empty($selectedIds)) {
        $_SESSION['error'] = 'No subscribers selected.';
        header('Location: subscribers.php');
        exit;
    }
    
    try {
        // Prepare ID placeholders for SQL query
        $idPlaceholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        switch ($action) {
            case 'delete':
                // Delete selected subscribers
                $stmt = $db->prepare("DELETE FROM subscribers WHERE id IN ($idPlaceholders)");
                $stmt->execute($selectedIds);
                
                $count = $stmt->rowCount();
                $_SESSION['success'] = "$count subscriber(s) deleted successfully.";
                break;
                
            case 'mark_contacted':
                // Mark selected subscribers as contacted
                $stmt = $db->prepare("UPDATE subscribers SET is_contacted = 1 WHERE id IN ($idPlaceholders)");
                $stmt->execute($selectedIds);
                
                $count = $stmt->rowCount();
                $_SESSION['success'] = "$count subscriber(s) marked as contacted.";
                break;
                
            case 'mark_not_contacted':
                // Mark selected subscribers as not contacted
                $stmt = $db->prepare("UPDATE subscribers SET is_contacted = 0 WHERE id IN ($idPlaceholders)");
                $stmt->execute($selectedIds);
                
                $count = $stmt->rowCount();
                $_SESSION['success'] = "$count subscriber(s) marked as not contacted.";
                break;
                
            case 'notify':
                // Get the notification message
                $message = isset($_POST['notification_message']) ? trim($_POST['notification_message']) : '';
                $subject = isset($_POST['notification_subject']) ? trim($_POST['notification_subject']) : 'Update from Stories From The Web';
                
                if (empty($message)) {
                    $_SESSION['error'] = 'Notification message cannot be empty.';
                    header('Location: subscribers.php');
                    exit;
                }
                
                // Get subscribers' emails
                $stmt = $db->prepare("SELECT id, email, name, feature FROM subscribers WHERE id IN ($idPlaceholders)");
                $stmt->execute($selectedIds);
                $subscribers = $stmt->fetchAll();
                
                // Send notifications
                $successCount = 0;
                $failCount = 0;
                
                foreach ($subscribers as $subscriber) {
                    $to = $subscriber['email'];
                    $name = !empty($subscriber['name']) ? $subscriber['name'] : 'Subscriber';
                    $feature = ucfirst($subscriber['feature']);
                    
                    // Personalize the message
                    $personalizedMessage = str_replace(
                        ['[NAME]', '[FEATURE]'],
                        [$name, $feature],
                        $message
                    );
                    
                    // Email headers
                    $headers = [
                        'From: Stories From The Web <noreply@storiesfromtheweb.org>',
                        'Reply-To: support@storiesfromtheweb.org',
                        'MIME-Version: 1.0',
                        'Content-Type: text/html; charset=UTF-8'
                    ];
                    
                    // HTML email template
                    $emailBody = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <title>' . htmlspecialchars($subject) . '</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #4361ee; color: white; padding: 20px; text-align: center; }
                            .content { padding: 20px; background-color: #f9f9f9; }
                            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                            a { color: #4361ee; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>Stories From The Web</h1>
                            </div>
                            <div class="content">
                                <p>Hello ' . htmlspecialchars($name) . ',</p>
                                ' . nl2br(htmlspecialchars($personalizedMessage)) . '
                                <p>Thank you for your interest in Stories From The Web!</p>
                            </div>
                            <div class="footer">
                                <p>This email was sent to ' . htmlspecialchars($to) . ' because you subscribed to updates about ' . htmlspecialchars($feature) . '.</p>
                                <p>© ' . date('Y') . ' Stories From The Web. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>';
                    
                    // Send the email
                    $mailSuccess = mail($to, $subject, $emailBody, implode("\r\n", $headers));
                    
                    if ($mailSuccess) {
                        $successCount++;
                        
                        // Update the subscriber as contacted
                        $updateStmt = $db->prepare("UPDATE subscribers SET is_contacted = 1 WHERE id = ?");
                        $updateStmt->execute([$subscriber['id']]);
                    } else {
                        $failCount++;
                        error_log("Failed to send notification email to {$to}");
                    }
                }
                
                if ($successCount > 0) {
                    $_SESSION['success'] = "Notifications sent to $successCount subscriber(s) successfully." . 
                                          ($failCount > 0 ? " Failed to send to $failCount subscriber(s)." : "");
                } else {
                    $_SESSION['error'] = "Failed to send notifications. Please check the server's email configuration.";
                }
                break;
                
            default:
                $_SESSION['error'] = "Unknown action: $action";
                break;
        }
    } catch (PDOException $e) {
        error_log("Bulk subscribers action error: " . $e->getMessage());
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Redirect back to subscribers page
header('Location: subscribers.php');
exit;
