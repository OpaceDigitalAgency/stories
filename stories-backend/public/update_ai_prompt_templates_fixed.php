<?php
/**
 * Update AI Prompt Templates
 * 
 * This script updates all AI prompt templates to use the correct variable names.
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
    // Update Story templates
    $storyTemplates = [
        [
            'name' => 'Story Cover',
            'description' => 'Generate a cover image for a story',
            'prompt_template' => 'Generate an image for a children\'s story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: {{title}}. {{#if excerpt}}The story summary is: {{excerpt}}.{{/if}} {{#if age_group}}The target age group is {{age_group}}.{{/if}}'
        ],
        [
            'name' => 'Story Scene',
            'description' => 'Generate an image for a specific scene in a story',
            'prompt_template' => 'Create an illustration for a scene from the children\'s story "{{title}}". In this scene: {{description}}. Style: colorful, engaging illustration that captures the emotion and action of the scene in a hand-drawn or cartoon style typical of traditional story books.'
        ],
        [
            'name' => 'Story Character',
            'description' => 'Generate an image of a character from a story',
            'prompt_template' => 'Create a character portrait for the children\'s story "{{title}}". The character is from this story: {{excerpt}}. Style: appealing, expressive character design in a hand-drawn or cartoon style typical of traditional story books.'
        ]
    ];
    
    // Update Blog Post templates
    $blogTemplates = [
        [
            'name' => 'Blog Post Cover',
            'description' => 'Generate a cover image for a blog post',
            'prompt_template' => 'Create a professional and engaging featured image for a blog post titled "{{title}}". The post discusses {{excerpt}}. Style: clean, modern design with relevant imagery that captures the essence of the topic.'
        ],
        [
            'name' => 'Blog Post Infographic',
            'description' => 'Generate an infographic for a blog post',
            'prompt_template' => 'Create an informative infographic for a blog post about {{title}}. The key points to visualize are: {{description}}. Style: clear, organized design with icons, charts, or diagrams that make the information easy to understand.'
        ]
    ];
    
    // Update Author templates
    $authorTemplates = [
        [
            'name' => 'Author Avatar',
            'description' => 'Generate an avatar for an author',
            'prompt_template' => 'Create a professional portrait-style avatar for an author named {{name}}. {{#if bio}}They describe themselves as: {{bio}}{{/if}}. Style: warm, approachable, professional illustration suitable for an author profile.'
        ],
        [
            'name' => 'Author Banner',
            'description' => 'Generate a banner image for an author profile',
            'prompt_template' => 'Create a banner image for author {{name}} who writes {{description}}. Style: creative, literary-themed design that reflects the author\'s genre and style.'
        ]
    ];
    
    // Update Game templates
    $gameTemplates = [
        [
            'name' => 'Game Cover',
            'description' => 'Generate a cover image for a game',
            'prompt_template' => 'Create an exciting game cover image for "{{title}}". {{#if description}}The game is about {{description}}{{/if}}. {{#if genre}}Genre: {{genre}}{{/if}}. Style: dynamic, colorful, eye-catching design that conveys the excitement and theme of the game.'
        ],
        [
            'name' => 'Game Character',
            'description' => 'Generate an image of a character from a game',
            'prompt_template' => 'Create a character design for the game "{{title}}". The character is {{description}}. Style: detailed, expressive character design that fits the game\'s aesthetic.'
        ]
    ];
    
    // Update AI Tool templates
    $aiToolTemplates = [
        [
            'name' => 'AI Tool Icon',
            'description' => 'Generate an icon for an AI tool',
            'prompt_template' => 'Create a modern icon for an AI tool called "{{title}}". {{#if description}}The tool\'s purpose is {{description}}{{/if}}. Style: sleek, tech-focused design with AI-themed elements, using blues and purples for a tech feel.'
        ],
        [
            'name' => 'AI Tool Screenshot',
            'description' => 'Generate a mockup screenshot of an AI tool',
            'prompt_template' => 'Create a realistic mockup screenshot of an AI tool interface for "{{title}}". The tool {{description}}. Style: clean, modern UI design with appropriate controls, data visualizations, and AI elements.'
        ]
    ];
    
    // Update Directory templates
    $directoryTemplates = [
        [
            'name' => 'Directory Listing Image',
            'description' => 'Generate an image for a directory listing',
            'prompt_template' => 'Create a representative image for a directory listing titled "{{title}}". {{#if description}}This is {{description}}{{/if}}. Style: clean, professional image that represents the business or service.'
        ],
        [
            'name' => 'Directory Location',
            'description' => 'Generate an image of a location for a directory listing',
            'prompt_template' => 'Create an image representing the location of "{{title}}" at {{address}}. Style: realistic, recognizable representation of the location or building type.'
        ]
    ];
    
    // Update General templates
    $generalTemplates = [
        [
            'name' => 'General Image',
            'description' => 'Generate a general purpose image',
            'prompt_template' => 'Create an image based on the following description: {{description}}. Style: professional, high-quality, suitable for a website.'
        ],
        [
            'name' => 'Decorative Banner',
            'description' => 'Generate a decorative banner image',
            'prompt_template' => 'Create a decorative banner image with the theme: {{description}}. Style: attractive, thematic design suitable for a website header or section divider.'
        ]
    ];
    
    // Combine all templates
    $allTemplates = [
        'story' => $storyTemplates,
        'blog_post' => $blogTemplates,
        'author' => $authorTemplates,
        'game' => $gameTemplates,
        'ai_tool' => $aiToolTemplates,
        'directory' => $directoryTemplates,
        'general' => $generalTemplates
    ];
    
    // Update each template in the database
    $stmt = $db->prepare("
        UPDATE ai_prompt_templates 
        SET prompt_template = ?, description = ?
        WHERE content_type = ? AND name = ?
    ");
    
    $updatedCount = 0;
    
    foreach ($allTemplates as $contentType => $templates) {
        foreach ($templates as $template) {
            $result = $stmt->execute([
                $template['prompt_template'],
                $template['description'],
                $contentType,
                $template['name']
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                $updatedCount++;
            }
        }
    }
    
    echo "<h1>AI Prompt Templates Update</h1>";
    echo "<p>Successfully updated $updatedCount templates.</p>";
    echo "<p><a href='view_ai_prompt_templates_fixed.php'>View all templates</a></p>";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
