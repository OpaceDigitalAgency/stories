<?php
/**
 * Update AI Prompt Template
 * 
 * This script updates the AI prompt template for story covers to use the new children's story book format.
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
    // Update the Story Cover template
    $stmt = $db->prepare("
        UPDATE ai_prompt_templates 
        SET prompt_template = 'Generate an image for a children\'s story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: {{title}}. {{#if excerpt}}The story is about {{excerpt}}.{{/if}} {{#if age_group}}The target age group is {{age_group}}.{{/if}}'
        WHERE content_type = 'story' AND name = 'Story Cover'
    ");
    
    $result = $stmt->execute();
    
    if ($result) {
        echo "Success: Story Cover prompt template updated successfully.";
    } else {
        echo "Error: Failed to update the prompt template.";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
