<?php
// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'debug' => []
];

try {
    // Get the author ID from the query string
    $authorId = isset($_GET['id']) ? intval($_GET['id']) : 2048; // Default to ID 2048 if not provided
    
    // Get the image URL from the query string or use a test URL
    $imageUrl = isset($_GET['url']) ? $_GET['url'] : 'https://api.storiesfromtheweb.org/uploads/optimized/test-image.jpg';
    
    // Log the parameters
    error_log("Test update avatar - Author ID: $authorId, Image URL: $imageUrl");
    $response['debug']['params'] = [
        'author_id' => $authorId,
        'image_url' => $imageUrl
    ];
    
    // Check if the author exists
    $checkStmt = $db->prepare("SELECT id, name, avatar_url FROM authors WHERE id = ?");
    $checkStmt->execute([$authorId]);
    $author = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$author) {
        throw new Exception("Author with ID $authorId not found");
    }
    
    // Log the current author data
    error_log("Current author data: " . print_r($author, true));
    $response['debug']['before'] = $author;
    
    // Update the avatar_url using a direct SQL query
    $sql = "UPDATE authors SET avatar_url = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$imageUrl, $authorId]);
    
    // Log the number of affected rows
    $rowCount = $stmt->rowCount();
    error_log("Update affected $rowCount rows");
    $response['debug']['rows_affected'] = $rowCount;
    
    // Verify the update
    $verifyStmt = $db->prepare("SELECT id, name, avatar_url FROM authors WHERE id = ?");
    $verifyStmt->execute([$authorId]);
    $updatedAuthor = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the updated author data
    error_log("Updated author data: " . print_r($updatedAuthor, true));
    $response['debug']['after'] = $updatedAuthor;
    
    // Set success response
    $response['success'] = true;
    $response['message'] = "Avatar URL updated successfully for author '{$author['name']}'";
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in test-update-avatar.php: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
