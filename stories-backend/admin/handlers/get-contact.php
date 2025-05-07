<?php
/**
 * Handler for getting contact details for preview
 */

// Include database connection
require_once '../includes/db-config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Contact ID is required'
    ]);
    exit;
}

$contactId = intval($_GET['id']);

try {
    // Get contact details
    $stmt = $db->prepare("
        SELECT *
        FROM contacts
        WHERE id = ?
    ");
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contact) {
        echo json_encode([
            'success' => false,
            'message' => 'Contact not found'
        ]);
        exit;
    }

    // Return the contact data
    echo json_encode([
        'success' => true,
        'contact' => $contact
    ]);

} catch (Exception $e) {
    error_log('Error in get-contact.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching contact data'
    ]);
}
