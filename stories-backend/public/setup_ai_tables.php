<?php
/**
 * AI Tables Setup Script
 *
 * This script automatically creates the necessary database tables for the AI functionality
 * using the existing database configuration.
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

    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS ai_providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        type ENUM('image', 'text', 'audio', 'video') NOT NULL,
        config JSON,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS ai_generations (
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

    $db->exec("CREATE TABLE IF NOT EXISTS ai_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT,
        type ENUM('image', 'text', 'audio', 'video') NOT NULL,
        cost DECIMAL(10,6) NOT NULL DEFAULT 0,
        tokens INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (provider_id) REFERENCES ai_providers(id),
        INDEX idx_type_date (type, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS ai_rate_limit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_date (ip_address, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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

    // Insert default OpenAI provider
    $db->exec("INSERT INTO ai_providers (name, type, config, is_active) VALUES
        ('openai', 'image', '{\"model\": \"gpt-image-1\", \"text_model\": \"gpt-4o\", \"max_tokens\": 2000, \"temperature\": 0.7}', true)
        ON DUPLICATE KEY UPDATE
        type = VALUES(type),
        config = VALUES(config),
        is_active = VALUES(is_active)");

    // Insert default prompt templates if they don't exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM ai_prompt_templates");
    $stmt->execute();
    if ($stmt->fetchColumn() === 0) {
        // Story templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Story Cover', 'story', 'Generate a cover image for a story', 'Create a captivating illustration for a children\\'s story titled \"{{title}}\". The story is about {{excerpt}}. The target age group is {{age_group}}. Style: vibrant, whimsical, child-friendly illustration with characters and setting from the story.', true),
            ('Story Scene', 'story', 'Generate an image for a specific scene in a story', 'Create an illustration for a scene from the children\\'s story \"{{title}}\". In this scene: {{description}}. Style: colorful, engaging illustration that captures the emotion and action of the scene.', true),
            ('Story Character', 'story', 'Generate an image of a character from a story', 'Create a character portrait for the children\\'s story \"{{title}}\". The character is {{description}}. Style: appealing, expressive character design that shows personality and emotion.', true)");

        // Blog post templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Blog Post Cover', 'blog_post', 'Generate a cover image for a blog post', 'Create a professional and engaging featured image for a blog post titled \"{{title}}\". The post discusses {{excerpt}}. Style: clean, modern design with relevant imagery that captures the essence of the topic.', true),
            ('Blog Post Infographic', 'blog_post', 'Generate an infographic for a blog post', 'Create an informative infographic for a blog post about {{title}}. The key points to visualize are: {{description}}. Style: clear, organized design with icons, charts, or diagrams that make the information easy to understand.', true)");

        // Author templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Author Avatar', 'author', 'Generate an avatar for an author', 'Create a professional portrait-style avatar for an author named {{name}}. {{#if bio}}They describe themselves as: {{bio}}{{/if}}. Style: warm, approachable, professional illustration suitable for an author profile.', true),
            ('Author Banner', 'author', 'Generate a banner image for an author profile', 'Create a banner image for author {{name}} who writes {{description}}. Style: creative, literary-themed design that reflects the author\\'s genre and style.', true)");

        // Game templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Game Cover', 'game', 'Generate a cover image for a game', 'Create an exciting game cover image for \"{{title}}\". {{#if description}}The game is about {{description}}{{/if}}. {{#if genre}}Genre: {{genre}}{{/if}}. Style: dynamic, colorful, eye-catching design that conveys the excitement and theme of the game.', true),
            ('Game Character', 'game', 'Generate an image of a character from a game', 'Create a character design for the game \"{{title}}\". The character is {{description}}. Style: detailed, expressive character design that fits the game\\'s aesthetic.', true),
            ('Game Environment', 'game', 'Generate an image of an environment from a game', 'Create an environment scene for the game \"{{title}}\". The environment is {{description}}. Style: immersive, atmospheric design that establishes the mood and setting of the game.', true)");

        // AI tool templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('AI Tool Icon', 'ai_tool', 'Generate an icon for an AI tool', 'Create a modern icon for an AI tool called \"{{title}}\". {{#if description}}The tool\\'s purpose is {{description}}{{/if}}. Style: sleek, tech-focused design with AI-themed elements, using blues and purples for a tech feel.', true),
            ('AI Tool Screenshot', 'ai_tool', 'Generate a mockup screenshot of an AI tool', 'Create a realistic mockup screenshot of an AI tool interface for \"{{title}}\". The tool {{description}}. Style: clean, modern UI design with appropriate controls, data visualizations, and AI elements.', true)");

        // Directory templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('Directory Listing Image', 'directory', 'Generate an image for a directory listing', 'Create a representative image for a directory listing titled \"{{title}}\". {{#if description}}This is {{description}}{{/if}}. Style: clean, professional image that represents the business or service.', true),
            ('Directory Location', 'directory', 'Generate an image of a location for a directory listing', 'Create an image representing the location of \"{{title}}\" at {{address}}. Style: realistic, recognizable representation of the location or building type.', true)");

        // General templates
        $db->exec("INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES
            ('General Image', 'general', 'Generate a general purpose image', 'Create an image based on the following description: {{description}}. Style: professional, high-quality, suitable for a website.', true),
            ('Decorative Banner', 'general', 'Generate a decorative banner image', 'Create a decorative banner image with the theme: {{description}}. Style: attractive, thematic design suitable for a website header or section divider.', true),
            ('Icon Set', 'general', 'Generate a set of related icons', 'Create a set of 4 matching icons related to {{description}}. Style: consistent, simple, recognizable icons with a cohesive design language.', true)");
    }

    // Create cleanup trigger
    $db->exec("DROP TRIGGER IF EXISTS cleanup_rate_limit");
    $db->exec("CREATE TRIGGER cleanup_rate_limit
        BEFORE INSERT ON ai_rate_limit
        FOR EACH ROW
        BEGIN
            DELETE FROM ai_rate_limit
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);
        END");

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

    echo json_encode([
        'success' => true,
        'message' => 'AI tables installed successfully!'
    ]);

} catch (PDOException $e) {
    error_log("Error installing AI tables: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}