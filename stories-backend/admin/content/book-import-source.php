<?php
/**
 * Book Import Source
 * 
 * This script handles the management of review sources for the book import tool.
 * It allows adding, editing, and deleting review sources.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sourceId = isset($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
        $sourceName = $_POST['source_name'] ?? '';
        $sourceUrl = $_POST['source_url'] ?? '';
        $isThirdParty = isset($_POST['is_third_party']) && $_POST['is_third_party'] == 1 ? 1 : 0;
        
        // Validate input
        if (empty($sourceName)) {
            throw new Exception('Source name is required');
        }
        
        if (empty($sourceUrl)) {
            throw new Exception('Source URL is required');
        }
        
        // Check if URL is valid
        if (!filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        if ($sourceId > 0) {
            // Update existing source
            $stmt = $db->prepare("
                UPDATE review_sources
                SET name = ?, url = ?, is_third_party = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$sourceName, $sourceUrl, $isThirdParty, $sourceId]);
            
            $message = 'Review source updated successfully';
        } else {
            // Check if source with same name already exists
            $checkStmt = $db->prepare("SELECT id FROM review_sources WHERE name = ?");
            $checkStmt->execute([$sourceName]);
            
            if ($checkStmt->fetch()) {
                throw new Exception('A review source with this name already exists');
            }
            
            // Add new source
            $stmt = $db->prepare("
                INSERT INTO review_sources (name, url, is_third_party, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$sourceName, $sourceUrl, $isThirdParty]);
            
            $message = 'Review source added successfully';
        }
        
        // Commit transaction
        $db->commit();
        
        // Set success message in session
        $_SESSION['success'] = $message;
    } catch (Exception $e) {
        // Rollback transaction
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    // Redirect back to the book import tool
    header('Location: book-import-tool.php');
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    // Handle delete request
    try {
        $sourceId = (int)$_GET['delete'];
        
        // Check if source exists
        $checkStmt = $db->prepare("SELECT id FROM review_sources WHERE id = ?");
        $checkStmt->execute([$sourceId]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception('Review source not found');
        }
        
        // Check if source is in use
        $useStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE source_id = ?");
        $useStmt->execute([$sourceId]);
        $useCount = $useStmt->fetchColumn();
        
        if ($useCount > 0) {
            throw new Exception('Cannot delete review source that is in use by ' . $useCount . ' reviews');
        }
        
        // Delete source
        $stmt = $db->prepare("DELETE FROM review_sources WHERE id = ?");
        $stmt->execute([$sourceId]);
        
        // Set success message in session
        $_SESSION['success'] = 'Review source deleted successfully';
    } catch (Exception $e) {
        // Set error message in session
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    // Redirect back to the book import tool
    header('Location: book-import-tool.php');
    exit;
} else {
    // Invalid request method
    $_SESSION['error'] = 'Invalid request method';
    header('Location: book-import-tool.php');
    exit;
}
