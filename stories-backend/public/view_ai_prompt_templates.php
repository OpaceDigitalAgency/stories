<?php
/**
 * View AI Prompt Templates
 * 
 * This script displays all AI prompt templates in the database.
 */

// Include database connection
require_once '../includes/db_connect.php';

try {
    // Get all prompt templates
    $stmt = $db->query("SELECT * FROM ai_prompt_templates ORDER BY content_type, name");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>AI Prompt Templates</h1>";
    
    if (empty($templates)) {
        echo "<p>No templates found.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Name</th><th>Content Type</th><th>Description</th><th>Prompt Template</th><th>Active</th></tr>";
        
        foreach ($templates as $template) {
            echo "<tr>";
            echo "<td>{$template['id']}</td>";
            echo "<td>{$template['name']}</td>";
            echo "<td>{$template['content_type']}</td>";
            echo "<td>{$template['description']}</td>";
            echo "<td>" . htmlspecialchars($template['prompt_template']) . "</td>";
            echo "<td>" . ($template['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
