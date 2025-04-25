<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Setup</title>
    <style>
        body { font-family: Arial; margin: 40px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h1>API Setup</h1>
    <?php
    try {
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

        // Drop existing tables in reverse order
        $tables = ['stories', 'authors', 'games', 'directory_items', 'ai_tools'];
        foreach ($tables as $table) {
            try {
                $db->exec("DROP TABLE IF EXISTS $table");
                echo "<p class='success'>✓ Dropped table: $table</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Error dropping table $table: " . $e->getMessage() . "</p>";
            }
        }

        // Create tables in correct order
        $db->exec("CREATE TABLE authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            bio TEXT,
            avatar_url VARCHAR(255),
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: authors</p>";

        $db->exec("CREATE TABLE stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            slug VARCHAR(255) NOT NULL UNIQUE,
            is_published BOOLEAN DEFAULT FALSE,
            author_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: stories</p>";

        $db->exec("CREATE TABLE games (
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
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: games</p>";

        $db->exec("CREATE TABLE directory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL UNIQUE,
            website_url VARCHAR(255) NOT NULL,
            category VARCHAR(100),
            rating DECIMAL(3,1) DEFAULT 0,
            price_range VARCHAR(50),
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: directory_items</p>";

        $db->exec("CREATE TABLE ai_tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL UNIQUE,
            website_url VARCHAR(255),
            category VARCHAR(100),
            pricing_type VARCHAR(50),
            price_info TEXT,
            features TEXT,
            rating DECIMAL(3,1) DEFAULT 0,
            featured BOOLEAN DEFAULT FALSE,
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: ai_tools</p>";

        // Add sample data
        $db->exec("INSERT INTO authors (name, slug, bio, is_published) VALUES 
            ('John Doe', 'john-doe', 'A test author', TRUE),
            ('Jane Smith', 'jane-smith', 'Another test author', TRUE)");
        echo "<p class='success'>✓ Added sample authors</p>";

        $db->exec("INSERT INTO stories (title, slug, content, author_id, is_published) VALUES 
            ('Test Story', 'test-story', 'Test content', 1, TRUE),
            ('Another Story', 'another-story', 'More test content', 2, TRUE)");
        echo "<p class='success'>✓ Added sample stories</p>";

        $db->exec("INSERT INTO games (title, slug, description, is_published) VALUES 
            ('Test Game', 'test-game', 'Test game description', TRUE),
            ('Another Game', 'another-game', 'More game content', TRUE)");
        echo "<p class='success'>✓ Added sample games</p>";

        $db->exec("INSERT INTO directory_items (title, slug, description, website_url, is_published) VALUES 
            ('Test Item', 'test-item', 'Test item description', 'http://example.com', TRUE),
            ('Another Item', 'another-item', 'More item content', 'http://example.org', TRUE)");
        echo "<p class='success'>✓ Added sample directory items</p>";

        $db->exec("INSERT INTO ai_tools (title, slug, description, website_url, is_published) VALUES 
            ('Test Tool', 'test-tool', 'Test tool description', 'http://example.com', TRUE),
            ('Another Tool', 'another-tool', 'More tool content', 'http://example.org', TRUE)");
        echo "<p class='success'>✓ Added sample AI tools</p>";

        echo "<h2>Setup Complete</h2>";
        echo "<p>You can now access the API endpoints:</p>";
        echo "<ul>";
        echo "<li><a href='/api/v1/stories'>/api/v1/stories</a></li>";
        echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
        echo "<li><a href='/api/v1/games'>/api/v1/games</a></li>";
        echo "<li><a href='/api/v1/directory-items'>/api/v1/directory-items</a></li>";
        echo "<li><a href='/api/v1/ai-tools'>/api/v1/ai-tools</a></li>";
        echo "</ul>";

    } catch (Exception $e) {
        echo "<h2>Error</h2>";
        echo "<p class='error'>" . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html>