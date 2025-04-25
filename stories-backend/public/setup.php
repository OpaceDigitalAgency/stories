<?php
// Database connection
$db = new PDO(
    'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
    'stories_user',
    '$tw1cac3*sOt',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS stories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        slug VARCHAR(255) NOT NULL UNIQUE,
        is_published BOOLEAN DEFAULT FALSE,
        author_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS authors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        bio TEXT,
        avatar_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS games (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        slug VARCHAR(255) NOT NULL UNIQUE,
        genre VARCHAR(100),
        platform VARCHAR(100),
        developer VARCHAR(255),
        publisher VARCHAR(255),
        release_date DATE,
        rating DECIMAL(3,1) DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS directory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        slug VARCHAR(255) NOT NULL UNIQUE,
        url VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        rating DECIMAL(3,1) DEFAULT 0,
        price_range VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS ai_tools (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        slug VARCHAR(255) NOT NULL UNIQUE,
        tool_url VARCHAR(255),
        category_id INT,
        pricing_type VARCHAR(50),
        price_info TEXT,
        features TEXT,
        rating DECIMAL(3,1) DEFAULT 0,
        featured BOOLEAN DEFAULT FALSE,
        is_published BOOLEAN DEFAULT FALSE,
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Execute each table creation
foreach ($tables as $sql) {
    try {
        $db->exec($sql);
        echo "✓ Table created successfully<br>";
    } catch (PDOException $e) {
        echo "✗ Error creating table: " . $e->getMessage() . "<br>";
    }
}

// Add sample data
$sampleData = [
    "INSERT IGNORE INTO authors (name, slug, bio) VALUES 
        ('John Doe', 'john-doe', 'A test author'),
        ('Jane Smith', 'jane-smith', 'Another test author')",

    "INSERT IGNORE INTO stories (title, slug, content, author_id) VALUES 
        ('Test Story', 'test-story', 'Test content', 1)",

    "INSERT IGNORE INTO games (title, slug, description) VALUES 
        ('Test Game', 'test-game', 'Test game description')",

    "INSERT IGNORE INTO directory_items (title, slug, description, url) VALUES 
        ('Test Item', 'test-item', 'Test item description', 'http://example.com')",

    "INSERT IGNORE INTO ai_tools (title, slug, description) VALUES 
        ('Test Tool', 'test-tool', 'Test tool description')"
];

// Execute each sample data insertion
foreach ($sampleData as $sql) {
    try {
        $db->exec($sql);
        echo "✓ Sample data added successfully<br>";
    } catch (PDOException $e) {
        echo "✗ Error adding sample data: " . $e->getMessage() . "<br>";
    }
}

echo "<br>Setup complete. You can now access the API endpoints:<br>";
echo "- /api/v1/stories<br>";
echo "- /api/v1/authors<br>";
echo "- /api/v1/games<br>";
echo "- /api/v1/directory-items<br>";
echo "- /api/v1/ai-tools<br>";