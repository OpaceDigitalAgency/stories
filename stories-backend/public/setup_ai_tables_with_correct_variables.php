<?php
/**
 * AI Tables Setup Script with Correct Variables
 *
 * This script automatically creates the necessary database tables for the AI functionality
 * and inserts default templates with the correct variable names for stories.
 */

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>AI Tables Setup</h1>";
    
    // Check if tables exist
    $tables = [
        'ai_providers',
        'ai_generations',
        'ai_usage',
        'ai_rate_limit',
        'ai_prompt_templates'
    ];
    
    $existingTables = [];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
            echo "<p>✅ Table '$table' already exists.</p>";
        }
    }
    
    // Create missing tables
    if (!in_array('ai_providers', $existingTables)) {
        $db->exec("CREATE TABLE ai_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            type ENUM('image', 'text', 'audio', 'video') NOT NULL,
            config JSON,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p>✅ Created table 'ai_providers'.</p>";
    }
    
    if (!in_array('ai_generations', $existingTables)) {
        $db->exec("CREATE TABLE ai_generations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider_id INT,
            type ENUM('image', 'text', 'audio', 'video') NOT NULL,
            prompt TEXT NOT NULL,
            result_url VARCHAR(255),
            metadata JSON,
            status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (provider_id) REFERENCES ai_providers(id),
            INDEX idx_type_status (type, status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p>✅ Created table 'ai_generations'.</p>";
    }
    
    if (!in_array('ai_usage', $existingTables)) {
        $db->exec("CREATE TABLE ai_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider_id INT,
            type ENUM('image', 'text', 'audio', 'video') NOT NULL,
            cost DECIMAL(10,6) NOT NULL DEFAULT 0,
            tokens INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (provider_id) REFERENCES ai_providers(id),
            INDEX idx_type_date (type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p>✅ Created table 'ai_usage'.</p>";
    }
    
    if (!in_array('ai_rate_limit', $existingTables)) {
        $db->exec("CREATE TABLE ai_rate_limit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_date (ip_address, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p>✅ Created table 'ai_rate_limit'.</p>";
    }
    
    if (!in_array('ai_prompt_templates', $existingTables)) {
        $db->exec("CREATE TABLE ai_prompt_templates (
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
        echo "<p>✅ Created table 'ai_prompt_templates'.</p>";
    }
    
    // Insert default OpenAI provider if it doesn't exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM ai_providers WHERE name = 'openai'");
    $stmt->execute();
    if ($stmt->fetchColumn() === 0) {
        $db->exec("INSERT INTO ai_providers (name, type, config, is_active) VALUES
            ('openai', 'image', '{\"model\": \"gpt-image-1\", \"text_model\": \"gpt-4o\", \"max_tokens\": 2000, \"temperature\": 0.7}', true)");
        echo "<p>✅ Added default OpenAI provider.</p>";
    } else {
        echo "<p>✅ OpenAI provider already exists.</p>";
    }
    
    // Check if templates exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM ai_prompt_templates");
    $stmt->execute();
    $templateCount = $stmt->fetchColumn();
    
    if ($templateCount === 0) {
        echo "<h2>Adding Default Templates</h2>";
        
        // Story templates with correct variable names
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Story Cover', 'story', 'Generate a cover image for a story', 'Generate an image for a children\\'s story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: {{title}}. {{#if summary}}The story summary is: {{summary}}.{{/if}} {{#if age_group}}The target age group is {{age_group}}.{{/if}}', true),
            ('Story Scene', 'story', 'Generate an image for a specific scene in a story', 'Create an illustration for a scene from the children\\'s story \"{{title}}\". In this scene: {{description}}. Style: colorful, engaging illustration that captures the emotion and action of the scene in a hand-drawn or cartoon style typical of traditional story books.', true),
            ('Story Character', 'story', 'Generate an image of a character from a story', 'Create a character portrait for the children\\'s story \"{{title}}\". The character is from this story: {{summary}}. Style: appealing, expressive character design in a hand-drawn or cartoon style typical of traditional story books.', true)");
        echo "<p>✅ Added Story templates with correct variable names.</p>";
        
        // Blog post templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Blog Post Cover', 'blog_post', 'Generate a cover image for a blog post', 'Create a professional and engaging featured image for a blog post titled \"{{title}}\". The post discusses {{excerpt}}. Style: clean, modern design with relevant imagery that captures the essence of the topic.', true),
            ('Blog Post Infographic', 'blog_post', 'Generate an infographic for a blog post', 'Create an informative infographic for a blog post about {{title}}. The key points to visualize are: {{description}}. Style: clear, organized design with icons, charts, or diagrams that make the information easy to understand.', true)");
        echo "<p>✅ Added Blog Post templates.</p>";
        
        // Author templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Author Avatar', 'author', 'Generate an avatar for an author', 'Create a professional portrait-style avatar for an author named {{name}}. {{#if bio}}They describe themselves as: {{bio}}{{/if}}. Style: warm, approachable, professional illustration suitable for an author profile.', true),
            ('Author Banner', 'author', 'Generate a banner image for an author profile', 'Create a banner image for author {{name}} who writes {{description}}. Style: creative, literary-themed design that reflects the author\\'s genre and style.', true)");
        echo "<p>✅ Added Author templates.</p>";
        
        // Game templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Game Cover', 'game', 'Generate a cover image for a game', 'Create an exciting game cover image for \"{{title}}\". {{#if description}}The game is about {{description}}{{/if}}. {{#if genre}}Genre: {{genre}}{{/if}}. Style: dynamic, colorful, eye-catching design that conveys the excitement and theme of the game.', true),
            ('Game Character', 'game', 'Generate an image of a character from a game', 'Create a character design for the game \"{{title}}\". The character is {{description}}. Style: detailed, expressive character design that fits the game\\'s aesthetic.', true)");
        echo "<p>✅ Added Game templates.</p>";
        
        // AI tool templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('AI Tool Icon', 'ai_tool', 'Generate an icon for an AI tool', 'Create a modern icon for an AI tool called \"{{title}}\". {{#if description}}The tool\\'s purpose is {{description}}{{/if}}. Style: sleek, tech-focused design with AI-themed elements, using blues and purples for a tech feel.', true),
            ('AI Tool Screenshot', 'ai_tool', 'Generate a mockup screenshot of an AI tool', 'Create a realistic mockup screenshot of an AI tool interface for \"{{title}}\". The tool {{description}}. Style: clean, modern UI design with appropriate controls, data visualizations, and AI elements.', true)");
        echo "<p>✅ Added AI Tool templates.</p>";
        
        // Directory templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Directory Listing Image', 'directory', 'Generate an image for a directory listing', 'Create a representative image for a directory listing titled \"{{title}}\". {{#if description}}This is {{description}}{{/if}}. Style: clean, professional image that represents the business or service.', true),
            ('Directory Location', 'directory', 'Generate an image of a location for a directory listing', 'Create an image representing the location of \"{{title}}\" at {{address}}. Style: realistic, recognizable representation of the location or building type.', true)");
        echo "<p>✅ Added Directory templates.</p>";
        
        // General templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('General Image', 'general', 'Generate a general purpose image', 'Create an image based on the following description: {{description}}. Style: professional, high-quality, suitable for a website.', true),
            ('Decorative Banner', 'general', 'Generate a decorative banner image', 'Create a decorative banner image with the theme: {{description}}. Style: attractive, thematic design suitable for a website header or section divider.', true)");
        echo "<p>✅ Added General templates.</p>";
        
        echo "<p>✅ All default templates added successfully!</p>";
    } else {
        echo "<p>✅ Templates already exist ($templateCount templates found).</p>";
        
        // Update Story templates to use correct variable names
        $storyTemplates = [
            [
                'name' => 'Story Cover',
                'description' => 'Generate a cover image for a story',
                'prompt_template' => 'Generate an image for a children\'s story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: {{title}}. {{#if summary}}The story summary is: {{summary}}.{{/if}} {{#if age_group}}The target age group is {{age_group}}.{{/if}}'
            ],
            [
                'name' => 'Story Scene',
                'description' => 'Generate an image for a specific scene in a story',
                'prompt_template' => 'Create an illustration for a scene from the children\'s story "{{title}}". In this scene: {{description}}. Style: colorful, engaging illustration that captures the emotion and action of the scene in a hand-drawn or cartoon style typical of traditional story books.'
            ],
            [
                'name' => 'Story Character',
                'description' => 'Generate an image of a character from a story',
                'prompt_template' => 'Create a character portrait for the children\'s story "{{title}}". The character is from this story: {{summary}}. Style: appealing, expressive character design in a hand-drawn or cartoon style typical of traditional story books.'
            ]
        ];
        
        $stmt = $db->prepare("
            UPDATE ai_prompt_templates 
            SET prompt_template = ?, description = ?
            WHERE content_type = 'story' AND name = ?
        ");
        
        $updatedCount = 0;
        foreach ($storyTemplates as $template) {
            $stmt->execute([
                $template['prompt_template'],
                $template['description'],
                $template['name']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $updatedCount++;
            }
        }
        
        echo "<p>Updated $updatedCount Story templates to use correct variable names.</p>";
    }
    
    // Create cleanup trigger if it doesn't exist
    $db->exec("DROP TRIGGER IF EXISTS cleanup_rate_limit");
    $db->exec("CREATE TRIGGER cleanup_rate_limit
        BEFORE INSERT ON ai_rate_limit
        FOR EACH ROW
        BEGIN
            DELETE FROM ai_rate_limit
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);
        END");
    echo "<p>✅ Created rate limit cleanup trigger.</p>";
    
    // Create usage stats procedure
    $db->exec("DROP PROCEDURE IF EXISTS get_ai_usage_stats");
    $db->exec("CREATE PROCEDURE get_ai_usage_stats(
        IN provider_name VARCHAR(50),
        IN usage_type ENUM('image', 'text', 'audio', 'video'),
        IN period_start TIMESTAMP,
        IN period_end TIMESTAMP
    )
    BEGIN
        SELECT
            COUNT(g.id) as total_generations,
            COALESCE(SUM(u.cost), 0) as total_cost,
            COALESCE(SUM(u.tokens), 0) as total_tokens
        FROM ai_generations g
        LEFT JOIN ai_providers p ON g.provider_id = p.id
        LEFT JOIN ai_usage u ON u.provider_id = p.id
            AND DATE(u.created_at) = DATE(g.created_at)
        WHERE p.name = provider_name
            AND g.type = usage_type
            AND g.created_at BETWEEN period_start AND period_end
            AND g.status = 'completed';
    END");
    echo "<p>✅ Created usage stats procedure.</p>";
    
    // Create stats view
    $db->exec("CREATE OR REPLACE VIEW v_ai_generation_stats AS
        SELECT
            p.name as provider_name,
            g.type,
            DATE(g.created_at) as generation_date,
            COUNT(*) as total_generations,
            COALESCE(SUM(u.cost), 0) as total_cost,
            COUNT(CASE WHEN g.status = 'failed' THEN 1 END) as failed_generations
        FROM ai_generations g
        JOIN ai_providers p ON g.provider_id = p.id
        LEFT JOIN ai_usage u ON u.provider_id = p.id
            AND DATE(u.created_at) = DATE(g.created_at)
        GROUP BY p.name, g.type, DATE(g.created_at)");
    echo "<p>✅ Created generation stats view.</p>";
    
    echo "<h2>✅ AI Tables Setup Complete!</h2>";
    echo "<p><a href='view_ai_prompt_templates_fixed.php'>View All Templates</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error Setting Up AI Tables</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
