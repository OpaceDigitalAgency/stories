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

        // Drop all tables
        $tables = [
            'story_tags', 'post_tags', 'story_authors', 'blog_posts', 
            'stories', 'authors', 'tags', 'games', 'directory_items', 
            'ai_tools', 'media'
        ];
        foreach ($tables as $table) {
            $db->exec("DROP TABLE IF EXISTS $table");
            echo "<p class='success'>✓ Dropped table: $table</p>";
        }

        // Create tables in correct order
        $db->exec("CREATE TABLE media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100),
            width INT,
            height INT,
            size INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: media</p>";

        $db->exec("CREATE TABLE authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            bio TEXT,
            avatar_url VARCHAR(255),
            is_published BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: authors</p>";

        $db->exec("CREATE TABLE tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: tags</p>";

        $db->exec("CREATE TABLE stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            excerpt TEXT,
            slug VARCHAR(255) NOT NULL UNIQUE,
            is_published BOOLEAN DEFAULT TRUE,
            featured BOOLEAN DEFAULT FALSE,
            average_rating DECIMAL(3,1) DEFAULT 0,
            review_count INT DEFAULT 0,
            estimated_reading_time VARCHAR(50) DEFAULT '5 minutes',
            is_sponsored BOOLEAN DEFAULT FALSE,
            age_group VARCHAR(50) DEFAULT '6-8 years',
            needs_moderation BOOLEAN DEFAULT FALSE,
            is_self_published BOOLEAN DEFAULT TRUE,
            is_ai_enhanced BOOLEAN DEFAULT FALSE,
            cover_url VARCHAR(255) DEFAULT 'https://storiesfromtheweb.org/default-cover.jpg',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: stories</p>";

        $db->exec("CREATE TABLE blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            excerpt TEXT,
            slug VARCHAR(255) NOT NULL UNIQUE,
            is_published BOOLEAN DEFAULT TRUE,
            author_id INT,
            cover_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: blog_posts</p>";

        $db->exec("CREATE TABLE story_authors (
            story_id INT,
            author_id INT,
            PRIMARY KEY (story_id, author_id),
            FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: story_authors</p>";

        $db->exec("CREATE TABLE story_tags (
            story_id INT,
            tag_id INT,
            PRIMARY KEY (story_id, tag_id),
            FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: story_tags</p>";

        $db->exec("CREATE TABLE post_tags (
            post_id INT,
            tag_id INT,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: post_tags</p>";

        $db->exec("CREATE TABLE games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL UNIQUE,
            website_url VARCHAR(255),
            genre VARCHAR(100),
            platform VARCHAR(100),
            developer VARCHAR(255),
            publisher VARCHAR(255),
            release_date DATE,
            rating DECIMAL(3,1) DEFAULT 0,
            price DECIMAL(10,2) DEFAULT 0,
            cover_url VARCHAR(255),
            is_published BOOLEAN DEFAULT TRUE,
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
            cover_url VARCHAR(255),
            is_published BOOLEAN DEFAULT TRUE,
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
            cover_url VARCHAR(255),
            is_published BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Created table: ai_tools</p>";

        // Add sample data
        $db->exec("INSERT INTO authors (name, slug, bio, avatar_url, is_published) VALUES 
            ('John Doe', 'john-doe', 'A test author', 'https://storiesfromtheweb.org/authors/john-doe.jpg', TRUE),
            ('Jane Smith', 'jane-smith', 'Another test author', 'https://storiesfromtheweb.org/authors/jane-smith.jpg', TRUE)");
        echo "<p class='success'>✓ Added sample authors</p>";

        $db->exec("INSERT INTO tags (name, slug) VALUES 
            ('Fiction', 'fiction'),
            ('Fantasy', 'fantasy'),
            ('Adventure', 'adventure'),
            ('Educational', 'educational')");
        echo "<p class='success'>✓ Added sample tags</p>";

        $db->exec("INSERT INTO stories (
            title, slug, content, excerpt, featured, average_rating, review_count, 
            estimated_reading_time, is_sponsored, age_group, cover_url, is_published
        ) VALUES 
            ('Example Story', 'example-story', 'Full story content here...', 'This is an example story...', 
            TRUE, 4.5, 10, '5 minutes', FALSE, '6-8 years', 'https://storiesfromtheweb.org/stories/example-story.jpg', TRUE),
            ('Another Story', 'another-story', 'More story content...', 'Another great story...', 
            FALSE, 4.0, 5, '3 minutes', FALSE, '3-5 years', 'https://storiesfromtheweb.org/stories/another-story.jpg', TRUE)");
        echo "<p class='success'>✓ Added sample stories</p>";

        $db->exec("INSERT INTO story_authors (story_id, author_id) VALUES (1, 1), (2, 2)");
        echo "<p class='success'>✓ Added story-author relationships</p>";

        $db->exec("INSERT INTO story_tags (story_id, tag_id) VALUES (1, 1), (1, 2), (2, 1), (2, 3)");
        echo "<p class='success'>✓ Added story tags</p>";

        $db->exec("INSERT INTO blog_posts (title, slug, content, excerpt, author_id, cover_url, is_published) VALUES
            ('Writing Tips for Children', 'writing-tips-for-children', 'Full blog post content...', 'Learn how to write for children...', 1, 'https://storiesfromtheweb.org/blog/writing-tips.jpg', TRUE),
            ('The Importance of Reading', 'importance-of-reading', 'More blog content...', 'Why reading matters...', 2, 'https://storiesfromtheweb.org/blog/reading.jpg', TRUE)");
        echo "<p class='success'>✓ Added sample blog posts</p>";

        $db->exec("INSERT INTO post_tags (post_id, tag_id) VALUES (1, 4), (2, 4)");
        echo "<p class='success'>✓ Added blog post tags</p>";

        $db->exec("INSERT INTO games (title, slug, description, website_url, genre, platform, developer, publisher, cover_url, is_published) VALUES 
            ('Test Game', 'test-game', 'Test game description', 'http://example.com', 'Action', 'PC', 'Test Dev', 'Test Pub', 'https://storiesfromtheweb.org/games/test-game.jpg', TRUE),
            ('Another Game', 'another-game', 'More game content', 'http://example.org', 'RPG', 'Console', 'Dev2', 'Pub2', 'https://storiesfromtheweb.org/games/another-game.jpg', TRUE)");
        echo "<p class='success'>✓ Added sample games</p>";

        $db->exec("INSERT INTO directory_items (title, slug, description, website_url, category, rating, price_range, cover_url, is_published) VALUES 
            ('Test Directory', 'test-directory', 'Test directory description', 'http://example.com', 'Category1', 4.5, 'Free', 'https://storiesfromtheweb.org/directory/test-directory.jpg', TRUE),
            ('Another Directory', 'another-directory', 'More directory content', 'http://example.org', 'Category2', 4.0, 'Premium', 'https://storiesfromtheweb.org/directory/another-directory.jpg', TRUE)");
        echo "<p class='success'>✓ Added sample directory items</p>";

        $db->exec("INSERT INTO ai_tools (title, slug, description, website_url, category, pricing_type, featured, cover_url, is_published) VALUES 
            ('Test AI Tool', 'test-ai-tool', 'Test tool description', 'http://example.com', 'Category1', 'Free', TRUE, 'https://storiesfromtheweb.org/ai-tools/test-tool.jpg', TRUE),
            ('Another AI Tool', 'another-ai-tool', 'More tool content', 'http://example.org', 'Category2', 'Paid', FALSE, 'https://storiesfromtheweb.org/ai-tools/another-tool.jpg', TRUE)");
        echo "<p class='success'>✓ Added sample AI tools</p>";

        echo "<h2>Setup Complete</h2>";
        echo "<p>You can now access the API endpoints:</p>";
        echo "<ul>";
        echo "<li><a href='/api/v1/stories'>/api/v1/stories</a></li>";
        echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
        echo "<li><a href='/api/v1/blog-posts'>/api/v1/blog-posts</a></li>";
        echo "<li><a href='/api/v1/tags'>/api/v1/tags</a></li>";
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