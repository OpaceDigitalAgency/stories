<?php
/**
 * Nuke Media Library Handler
 * 
 * This script completely resets the media library by deleting all media records.
 * It can optionally delete the physical image files from the server as well.
 * 
 * WARNING: This is a destructive operation and cannot be undone.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'stats' => [
        'records_deleted' => 0,
        'files_deleted' => 0
    ]
];

// Check if delete files parameter is set
$deleteFiles = isset($_POST['delete_files']) && $_POST['delete_files'] == '1';

try {
    // Begin transaction
    $db->beginTransaction();
    
    // Get all file paths if we need to delete files
    $filePaths = [];
    if ($deleteFiles) {
        $stmt = $db->query("SELECT file_path FROM media");
        $filePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Delete all media records
    $stmt = $db->query("DELETE FROM media");
    $response['stats']['records_deleted'] = $stmt->rowCount();
    
    // Delete physical files if requested
    if ($deleteFiles) {
        foreach ($filePaths as $path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            if (file_exists($fullPath) && is_file($fullPath)) {
                unlink($fullPath);
                $response['stats']['files_deleted']++;
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    $response['success'] = true;
    $response['message'] = "Successfully nuked media library. Deleted {$response['stats']['records_deleted']} records and {$response['stats']['files_deleted']} files.";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $response['success'] = false;
    $response['message'] = "Error nuking media library: " . $e->getMessage();
    $response['error_details'] = $e->getTraceAsString();
}

// Return JSON response
echo json_encode($response);
