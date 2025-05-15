-- AI Prompt Templates Table
CREATE TABLE IF NOT EXISTS ai_prompt_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    content_type ENUM('story', 'blog_post', 'author', 'game', 'ai_tool', 'directory', 'general') NOT NULL,
    prompt_template TEXT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name_type (name, content_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default prompt templates
INSERT INTO ai_prompt_templates (name, content_type, description, prompt_template, is_active) VALUES 
('Story Cover', 'story', 'Generate a cover image for a story', 'Create a captivating illustration for a children\'s story titled "{{title}}". The story is about {{excerpt}}. The target age group is {{age_group}}. Style: vibrant, whimsical, child-friendly illustration with characters and setting from the story.', true),

('Blog Post Cover', 'blog_post', 'Generate a cover image for a blog post', 'Create a professional and engaging featured image for a blog post titled "{{title}}". The post discusses {{excerpt}}. Style: clean, modern design with relevant imagery that captures the essence of the topic.', true),

('Author Avatar', 'author', 'Generate an avatar for an author', 'Create a professional portrait-style avatar for an author named {{name}}. {{#if bio}}They describe themselves as: {{bio}}{{/if}}. Style: warm, approachable, professional illustration suitable for an author profile.', true),

('Game Cover', 'game', 'Generate a cover image for a game', 'Create an exciting game cover image for "{{title}}". {{#if description}}The game is about {{description}}{{/if}}. {{#if genre}}Genre: {{genre}}{{/if}}. Style: dynamic, colorful, eye-catching design that conveys the excitement and theme of the game.', true),

('AI Tool Icon', 'ai_tool', 'Generate an icon for an AI tool', 'Create a modern icon for an AI tool called "{{title}}". {{#if description}}The tool\'s purpose is {{description}}{{/if}}. Style: sleek, tech-focused design with AI-themed elements, using blues and purples for a tech feel.', true),

('Directory Listing Image', 'directory', 'Generate an image for a directory listing', 'Create a representative image for a directory listing titled "{{title}}". {{#if description}}This is {{description}}{{/if}}. Style: clean, professional image that represents the business or service.', true),

('General Image', 'general', 'Generate a general purpose image', 'Create an image based on the following description: {{description}}. Style: professional, high-quality, suitable for a website.', true);
