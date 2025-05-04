<?php
/**
 * Bulk Actions for Contact Form Submissions
 * Handles bulk operations on contact form submissions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
include_once '../includes/auth-check.php';

// Include common admin files
include_once '../includes/email-functions.php';
include_once '../includes/db-connect.php';

// Initialize response
$_SESSION['error'] = null;
$_SESSION['success'] = null;

// Check if action is specified
if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $selectedIds = $_POST['selected_ids'] ?? [];

    // Validate selected IDs
    if (empty($selectedIds)) {
        $_SESSION['error'] = 'No contacts selected';
        header('Location: contacts.php');
        exit;
    } else {
        // Convert to integers to prevent SQL injection
        $selectedIds = array_map('intval', $selectedIds);

        // Prepare placeholders for SQL query
        $idPlaceholders = implode(',', array_fill(0, count($selectedIds), '?'));

        try {
            switch ($action) {
                case 'delete':
                    // Delete selected contacts
                    $stmt = $db->prepare("DELETE FROM contacts WHERE id IN ($idPlaceholders)");
                    $stmt->execute($selectedIds);
                    $count = $stmt->rowCount();

                    $_SESSION['success'] = "$count contact(s) deleted successfully";
                    break;

                case 'mark_responded':
                    // Mark selected contacts as responded
                    $stmt = $db->prepare("UPDATE contacts SET is_responded = 1 WHERE id IN ($idPlaceholders)");
                    $stmt->execute($selectedIds);
                    $count = $stmt->rowCount();

                    $_SESSION['success'] = "$count contact(s) marked as responded";
                    break;

                case 'mark_not_responded':
                    // Mark selected contacts as not responded
                    $stmt = $db->prepare("UPDATE contacts SET is_responded = 0 WHERE id IN ($idPlaceholders)");
                    $stmt->execute($selectedIds);
                    $count = $stmt->rowCount();

                    $_SESSION['success'] = "$count contact(s) marked as not responded";
                    break;

                case 'notify':
                    // Send notification to selected contacts
                    $subject = $_POST['notification_subject'] ?? 'Response from Stories From The Web';
                    $message = $_POST['notification_message'] ?? '';

                    if (empty($message)) {
                        $_SESSION['error'] = 'Notification message cannot be empty';
                        header('Location: contacts.php');
                        exit;
                    }

                    // Get contact details
                    $stmt = $db->prepare("SELECT * FROM contacts WHERE id IN ($idPlaceholders)");
                    $stmt->execute($selectedIds);
                    $contacts = $stmt->fetchAll();

                    $successCount = 0;
                    $errorCount = 0;

                    foreach ($contacts as $contact) {
                        // Replace placeholders in message
                        $personalizedMessage = str_replace(
                            ['[NAME]', '[SUBJECT]'],
                            [$contact['name'], $contact['subject']],
                            $message
                        );

                        // Send email
                        $to = $contact['email'];
                        $headers = "From: Stories From The Web <noreply@storiesfromtheweb.org>\r\n";
                        $headers .= "Reply-To: support@storiesfromtheweb.org\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

                        // Use our sendEmail function instead of mail()
                        $mailResult = sendEmail($to, $subject, $personalizedMessage, $headers);

                        if ($mailResult) {
                            error_log("Email sent successfully to {$to}");

                            // Update contact as responded
                            $stmt = $db->prepare("UPDATE contacts SET is_responded = 1, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n\nResponse sent on " . date('Y-m-d H:i:s') . ":\n', ?) WHERE id = ?");
                            $stmt->execute([$personalizedMessage, $contact['id']]);
                            $successCount++;
                        } else {
                            error_log("Failed to send email to {$to}. PHP mail() function returned false.");
                            $errorCount++;
                        }
                    }

                    if ($errorCount === 0) {
                        $_SESSION['success'] = "Notification sent successfully to $successCount contact(s)";
                    } else {
                        $_SESSION['success'] = "Sent to $successCount contact(s), failed for $errorCount contact(s)";
                    }
                    break;

                default:
                    $_SESSION['error'] = "Unknown action: $action";
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            error_log("Error in bulk-contacts.php: " . $e->getMessage());
        }
    }
}

// Redirect back to contacts page
header('Location: contacts.php');
exit;
