<?php
// Include database connection
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Set content type to plain text for better debugging
header('Content-Type: text/plain');

try {
    // Begin transaction
    $db->beginTransaction();

    // Create directory_item_tags table
    $sql = "CREATE TABLE IF NOT EXISTS directory_item_tags (
        directory_item_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (directory_item_id, tag_id),
        FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);
    echo "Created directory_item_tags table\n";

    // Commit the transaction
    $db->commit();
    echo "Transaction committed successfully\n";
    echo "Directory item tags table created successfully!\n";

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
