<?php
// Minimal AJAX test - no includes, no session, just pure JSON
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'test') {
    echo json_encode(['status' => 'success', 'message' => 'AJAX working', 'timestamp' => date('Y-m-d H:i:s')]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action: ' . $action]);
}
