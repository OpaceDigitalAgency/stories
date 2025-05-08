<?php
/**
 * Add Default Prompt Templates
 * 
 * This script adds default prompt templates for AI image generation if they don't exist.
 * It ensures that each content type has at least one default template.
 * 
 * This version is designed to be run from a browser.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Default AI Prompt Templates</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .log {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .back-link {
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h1>Add Default AI Prompt Templates</h1>
    
    <div class="log">
<?php
// Define content types
$contentTypes = [
    'story',
    'blog_post',
    'author',
    'game',
    'ai_tool',
    'directory',
    'general'
];

// Define default templates
$defaultTemplates = [
    [
        'name' => 'Story Cover',
        'content_type' => 'story',
        'description' => 'Generate a cover image for a children\'s story',
        'prompt_template' => 'Generate an image for a children\'s story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: {{title}}{{#if summary}}. Summary: {{summary}}{{/if}}{{#if age_group}}. Target age: {{age_group}}{{/if}}',
        'is_active' => true
    ],
    [
        'name' => 'Blog Post Cover',
        'content_type' => 'blog_post',
        'description' => 'Generate a cover image for a blog post',
        'prompt_template' => 'Create a professional and engaging featured image for a blog post titled "{{title}}". {{#if summary}}The post discusses {{summary}}.{{/if}} Style: clean, modern design with relevant imagery that captures the essence of the topic.',
        'is_active' => true
    ],
    [
        'name' => 'Author Avatar',
        'content_type' => 'author',
        'description' => 'Generate an avatar for an author',
        'prompt_template' => 'Create a professional portrait-style avatar for an author named {{name}}. {{#if bio}}They describe themselves as: {{bio}}{{/if}}. Style: warm, approachable, professional illustration suitable for an author profile.',
        'is_active' => true
    ],
    [
        'name' => 'Game Cover',
        'content_type' => 'game',
        'description' => 'Generate a cover image for a game',
        'prompt_template' => 'Create an exciting game cover image for "{{title}}". {{#if description}}The game is about {{description}}{{/if}}. {{#if genre}}Genre: {{genre}}{{/if}}. Style: dynamic, colorful, eye-catching design that conveys the excitement and theme of the game.',
        'is_active' => true
    ],
    [
        'name' => 'AI Tool Icon',
        'content_type' => 'ai_tool',
        'description' => 'Generate an icon for an AI tool',
        'prompt_template' => 'Create a modern icon for an AI tool called "{{title}}". {{#if description}}The tool\'s purpose is {{description}}{{/if}}. Style: sleek, tech-focused design with AI-themed elements, using blues and purples for a tech feel.',
        'is_active' => true
    ],
    [
        'name' => 'Directory Listing Image',
        'content_type' => 'directory',
        'description' => 'Generate an image for a directory listing',
        'prompt_template' => 'Create a representative image for a directory listing titled "{{title}}". {{#if description}}This is {{description}}{{/if}}. Style: clean, professional image that represents the business or service.',
        'is_active' => true
    ],
    [
        'name' => 'General Image',
        'content_type' => 'general',
        'description' => 'Generate a general purpose image',
        'prompt_template' => 'Create an image based on the following description: {{title}}{{#if description}}. {{description}}{{/if}}. Style: professional, high-quality, suitable for a website.',
        'is_active' => true
    ]
];

// Check if the ai_prompt_templates table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'ai_prompt_templates'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo "<div class='info'>Creating ai_prompt_templates table...</div>\n";
        
        // Create the table
        $db->exec("CREATE TABLE IF NOT EXISTS ai_prompt_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            content_type ENUM('story', 'blog_post', 'author', 'game', 'ai_tool', 'directory', 'general') NOT NULL,
            prompt_template TEXT NOT NULL,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name_type (name, content_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<div class='success'>Table created successfully.</div>\n";
    }

    // Check existing templates for each content type
    foreach ($contentTypes as $contentType) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM ai_prompt_templates WHERE content_type = ?");
        $stmt->execute([$contentType]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            echo "<div class='info'>No templates found for content type: $contentType. Adding default template...</div>\n";
            
            // Find the default template for this content type
            foreach ($defaultTemplates as $template) {
                if ($template['content_type'] === $contentType) {
                    // Add the template
                    $stmt = $db->prepare("
                        INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $template['name'],
                        $template['content_type'],
                        $template['description'],
                        $template['prompt_template'],
                        $template['is_active'] ? 1 : 0
                    ]);
                    
                    echo "<div class='success'>Added default template for $contentType: {$template['name']}</div>\n";
                    break;
                }
            }
        } else {
            echo "<div class='info'>Found $count templates for content type: $contentType</div>\n";
        }
    }
    
    echo "<div class='success'>Default prompt templates check completed.</div>\n";
    
} catch (PDOException $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}
?>
    </div>
    
    <a href="/admin/content/ai-settings.php" class="back-link">Back to AI Settings</a>
</body>
</html>
