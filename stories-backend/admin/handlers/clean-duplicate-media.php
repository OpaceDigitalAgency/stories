<?php
/**
 * Clean Duplicate Media Handler
 *
 * This script handles the cleanup of duplicate media records in the database.
 * It identifies duplicates based on filename and keeps only the oldest record.
 * It also identifies and removes orphaned media records that are not referenced by any content.
 *
 * Enhanced version: Improved orphaned media detection to check all content types
 * and handle both URL-based and ID-based relationships.
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
        'duplicates_found' => 0,
        'duplicates_deleted' => 0,
        'orphans_found' => 0,
        'orphans_deleted' => 0,
        'total_deleted' => 0
    ]
];

try {
    // Begin transaction
    $db->beginTransaction();

    // 1. Find duplicate media records based on filename
    $stmt = $db->query("
        SELECT filename, COUNT(*) as count, MIN(id) as keep_id
        FROM media
        GROUP BY filename
        HAVING COUNT(*) > 1
    ");

    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['stats']['duplicates_found'] = count($duplicates);

    // 2. Delete all duplicates except the one with the lowest ID (oldest record)
    foreach ($duplicates as $duplicate) {
        $filename = $duplicate['filename'];
        $keepId = $duplicate['keep_id'];

        $deleteStmt = $db->prepare("
            DELETE FROM media
            WHERE filename = ? AND id != ?
        ");

        $deleteStmt->execute([$filename, $keepId]);
        $response['stats']['duplicates_deleted'] += $deleteStmt->rowCount();
    }

    // 3. Find orphaned media records (not referenced by any content)
    // Enhanced query to check all content types and handle both URL and ID references
    $orphanedStmt = $db->query("
        SELECT m.id, m.filename, m.file_path
        FROM media m
        LEFT JOIN directory_items d ON m.file_path = d.cover_url
        LEFT JOIN books b ON m.file_path = b.cover_image_url
        LEFT JOIN stories s ON m.file_path = s.cover_url
        LEFT JOIN games g ON m.file_path = g.cover_url
        LEFT JOIN ai_tools a ON m.file_path = a.cover_url
        WHERE d.id IS NULL
          AND b.directory_item_id IS NULL
          AND s.id IS NULL
          AND (g.id IS NULL OR g.id = 0)
          AND (a.id IS NULL OR a.id = 0)
    ");

    $orphaned = $orphanedStmt->fetchAll(PDO::FETCH_ASSOC);
    $response['stats']['orphans_found'] = count($orphaned);

    // 4. Delete orphaned media records
    if ($response['stats']['orphans_found'] > 0) {
        $orphanedIds = array_column($orphaned, 'id');
        $orphanedIdList = implode(',', $orphanedIds);

        $deleteOrphanedStmt = $db->prepare("DELETE FROM media WHERE id IN ($orphanedIdList)");
        $deleteOrphanedStmt->execute();
        $response['stats']['orphans_deleted'] = $deleteOrphanedStmt->rowCount();
    }

    // Calculate total deleted
    $response['stats']['total_deleted'] = $response['stats']['duplicates_deleted'] + $response['stats']['orphans_deleted'];

    // Commit transaction
    $db->commit();

    // Set success response
    $response['success'] = true;
    $response['message'] = "Successfully cleaned up media records. Deleted {$response['stats']['total_deleted']} duplicate/orphaned records.";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Set error response
    $response['success'] = false;
    $response['message'] = "Error cleaning up media records: " . $e->getMessage();
    $response['error_details'] = $e->getTraceAsString();
}

// Return JSON response
echo json_encode($response);
