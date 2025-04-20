<?php
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
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "Connected to database successfully.\n";

    // Fix AI Tools table
    $db->exec("DROP TABLE IF EXISTS ai_tools");
    $db->exec("CREATE TABLE ai_tools (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category_id INT,
        tool_url VARCHAR(255),
        pricing_type ENUM('free', 'freemium', 'paid', 'subscription') DEFAULT 'free',
        price_info VARCHAR(255),
        features TEXT,
        rating DECIMAL(3,1) DEFAULT 0,
        featured TINYINT(1) DEFAULT 0,
        is_published TINYINT(1) DEFAULT 0,
        slug VARCHAR(255) NOT NULL,
        published_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )");
    echo "AI Tools table created successfully.\n";

    // Fix AI Tool Categories table
    $db->exec("DROP TABLE IF EXISTS ai_tool_categories");
    $db->exec("CREATE TABLE ai_tool_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )");
    
    // Add default AI tool categories
    $db->exec("INSERT INTO ai_tool_categories (name, slug, description, created_at, updated_at) VALUES 
        ('Text Generation', 'text-generation', 'AI tools for generating text content', NOW(), NOW()),
        ('Image Generation', 'image-generation', 'AI tools for generating images', NOW(), NOW()),
        ('Content Summarization', 'content-summarization', 'AI tools for summarizing content', NOW(), NOW()),
        ('Translation', 'translation', 'AI tools for translating content', NOW(), NOW()),
        ('Chatbots', 'chatbots', 'AI chatbot tools', NOW(), NOW())
    ");
    echo "AI Tool Categories table created successfully.\n";

    // Add demo AI tools
    $db->exec("INSERT INTO ai_tools (title, description, category_id, tool_url, pricing_type, price_info, features, rating, featured, is_published, slug, published_at, created_at, updated_at) VALUES 
        ('ChatGPT', 'Advanced AI chatbot for natural language conversations', 5, 'https://chat.openai.com', 'freemium', 'Free tier available, $20/month for Plus', 'Natural language processing\nContext awareness\nMulti-turn conversations\nCode generation\nCreative writing', 4.8, 1, 1, 'chatgpt', NOW(), NOW(), NOW()),
        ('DALL-E', 'AI image generation from text descriptions', 2, 'https://openai.com/dall-e', 'paid', 'Credits-based system', 'Text to image generation\nHigh resolution output\nMultiple styles\nEditing capabilities', 4.5, 1, 1, 'dall-e', NOW(), NOW(), NOW()),
        ('Grammarly', 'AI-powered writing assistant', 1, 'https://www.grammarly.com', 'freemium', 'Free tier, Premium from $12/month', 'Grammar checking\nSpelling correction\nTone adjustment\nPlagiarism detection\nStyle suggestions', 4.7, 0, 1, 'grammarly', NOW(), NOW(), NOW())
    ");
    echo "Demo AI tools added successfully.\n";

    // Fix Games table
    $db->exec("DROP TABLE IF EXISTS games");
    $db->exec("CREATE TABLE games (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        slug VARCHAR(255) NOT NULL,
        featured TINYINT(1) DEFAULT 0,
        is_published TINYINT(1) DEFAULT 0,
        published_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )");
    echo "Games table created successfully.\n";

    // Add demo games
    $db->exec("INSERT INTO games (title, description, slug, featured, is_published, published_at, created_at, updated_at) VALUES 
        ('Word Adventure', 'A fun word-finding game that challenges your vocabulary', 'word-adventure', 1, 1, NOW(), NOW(), NOW()),
        ('Story Quest', 'Interactive storytelling game where your choices matter', 'story-quest', 1, 1, NOW(), NOW(), NOW()),
        ('Puzzle Master', 'Collection of brain-teasing puzzles for all ages', 'puzzle-master', 0, 1, NOW(), NOW(), NOW())
    ");
    echo "Demo games added successfully.\n";

    // Fix Directory Items table
    $db->exec("DROP TABLE IF EXISTS directory_items");
    $db->exec("CREATE TABLE directory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category_id INT,
        website_url VARCHAR(255),
        contact_email VARCHAR(255),
        contact_phone VARCHAR(50),
        address TEXT,
        featured TINYINT(1) DEFAULT 0,
        is_published TINYINT(1) DEFAULT 0,
        slug VARCHAR(255) NOT NULL,
        published_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )");
    echo "Directory Items table created successfully.\n";

    // Fix Directory Categories table
    $db->exec("DROP TABLE IF EXISTS directory_categories");
    $db->exec("CREATE TABLE directory_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )");
    
    // Add default directory categories
    $db->exec("INSERT INTO directory_categories (name, slug, description, created_at, updated_at) VALUES 
        ('General', 'general', 'General directory listings', NOW(), NOW()),
        ('Business', 'business', 'Business directory listings', NOW(), NOW()),
        ('Education', 'education', 'Education directory listings', NOW(), NOW())
    ");
    echo "Directory Categories table created successfully.\n";

    // Add demo directory items
    $db->exec("INSERT INTO directory_items (title, description, category_id, website_url, contact_email, contact_phone, address, featured, is_published, slug, published_at, created_at, updated_at) VALUES 
        ('Creative Writing Academy', 'Learn creative writing from professional authors', 3, 'https://example.com/writing-academy', 'info@writingacademy.com', '555-123-4567', '123 Main St, Anytown, USA', 1, 1, 'creative-writing-academy', NOW(), NOW(), NOW()),
        ('Story Publishing Services', 'Professional publishing services for authors', 2, 'https://example.com/publishing', 'contact@publishing.com', '555-987-6543', '456 Business Ave, Commerce City, USA', 1, 1, 'story-publishing-services', NOW(), NOW(), NOW()),
        ('Writers Community Center', 'A place for writers to connect and collaborate', 1, 'https://example.com/writers-community', 'community@writers.org', '555-789-0123', '789 Community Blvd, Writersville, USA', 0, 1, 'writers-community-center', NOW(), NOW(), NOW())
    ");
    echo "Demo directory items added successfully.\n";

    echo "All database tables have been fixed and populated with demo content successfully.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}