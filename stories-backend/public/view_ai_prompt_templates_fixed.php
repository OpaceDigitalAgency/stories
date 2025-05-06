<?php
/**
 * View AI Prompt Templates
 * 
 * This script displays all AI prompt templates in the database.
 */

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

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
