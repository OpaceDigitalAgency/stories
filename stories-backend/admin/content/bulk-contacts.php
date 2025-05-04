<?php
/**
 * Bulk Actions for Contact Form Submissions
 * Handles bulk operations on contact form submissions
 */

// Include common admin files
include_once '../includes/header.php';
include_once '../includes/functions.php';
include_once '../includes/email-functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'No action specified',
    'redirect' => 'contacts.php'
];

// Check if action is specified
if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $selectedIds = $_POST['selected_ids'] ?? [];

    // Validate selected IDs
    if (empty($selectedIds)) {
        $response['message'] = 'No items selected';
    } else {
        // Convert to integers to prevent SQL injection
        $selectedIds = array_map('intval', $selectedIds);
        $idList = implode(',', $selectedIds);

        try {
            switch ($action) {
                case 'delete':
                    // Delete selected contacts
                    $stmt = $db->prepare("DELETE FROM contacts WHERE id IN ($idList)");
                    $stmt->execute();
                    $count = $stmt->rowCount();

                    $response['success'] = true;
                    $response['message'] = "$count contact(s) deleted successfully";
                    break;

                case 'mark_responded':
                    // Mark selected contacts as responded
                    $stmt = $db->prepare("UPDATE contacts SET is_responded = 1 WHERE id IN ($idList)");
                    $stmt->execute();
                    $count = $stmt->rowCount();

                    $response['success'] = true;
                    $response['message'] = "$count contact(s) marked as responded";
                    break;

                case 'mark_not_responded':
                    // Mark selected contacts as not responded
                    $stmt = $db->prepare("UPDATE contacts SET is_responded = 0 WHERE id IN ($idList)");
                    $stmt->execute();
                    $count = $stmt->rowCount();

                    $response['success'] = true;
                    $response['message'] = "$count contact(s) marked as not responded";
                    break;

                case 'notify':
                    // Send notification to selected contacts
                    $subject = $_POST['notification_subject'] ?? 'Response from Stories From The Web';
                    $message = $_POST['notification_message'] ?? '';

                    if (empty($message)) {
                        $response['message'] = 'Notification message cannot be empty';
                        break;
                    }

                    // Get contact details
                    $stmt = $db->prepare("SELECT * FROM contacts WHERE id IN ($idList)");
                    $stmt->execute();
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
                        $response['success'] = true;
                        $response['message'] = "Notification sent successfully to $successCount contact(s)";
                    } else {
                        $response['message'] = "Sent to $successCount contact(s), failed for $errorCount contact(s)";
                    }
                    break;

                default:
                    $response['message'] = "Unknown action: $action";
                    break;
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
            error_log("Error in bulk-contacts.php: " . $e->getMessage());
        }
    }
}

// Redirect with message
$redirectUrl = $response['redirect'];
if (strpos($redirectUrl, '?') !== false) {
    $redirectUrl .= '&';
} else {
    $redirectUrl .= '?';
}

$redirectUrl .= 'status=' . ($response['success'] ? 'success' : 'error') . '&message=' . urlencode($response['message']);
header("Location: $redirectUrl");
exit;
