-- Stories API Database Schema
-- This file contains the SQL statements to create the database schema for the Stories API.

-- Drop tables if they exist (in reverse order of creation to avoid foreign key constraints)
DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS story_tags;
DROP TABLE IF EXISTS story_authors;
DROP TABLE IF EXISTS blog_post_authors;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS stories;
DROP TABLE IF EXISTS blog_posts;
DROP TABLE IF EXISTS directory_items;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS ai_tools;
DROP TABLE IF EXISTS authors;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'author', 'editor', 'admin') NOT NULL DEFAULT 'user',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create authors table
CREATE TABLE authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    bio TEXT,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    twitter VARCHAR(100),
    instagram VARCHAR(100),
    website VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create stories table
CREATE TABLE stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    published_at DATETIME NOT NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    estimated_reading_time VARCHAR(50),
    is_sponsored TINYINT(1) NOT NULL DEFAULT 0,
    age_group VARCHAR(50),
    needs_moderation TINYINT(1) NOT NULL DEFAULT 1,
    is_self_published TINYINT(1) NOT NULL DEFAULT 1,
    is_ai_enhanced TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tags table
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create story_authors table (many-to-many relationship between stories and authors)
CREATE TABLE story_authors (
    story_id INT NOT NULL,
    author_id INT NOT NULL,
    PRIMARY KEY (story_id, author_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create story_tags table (many-to-many relationship between stories and tags)
CREATE TABLE story_tags (
    story_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (story_id, tag_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create blog_posts table
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    published_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create blog_post_authors table (many-to-many relationship between blog posts and authors)
CREATE TABLE blog_post_authors (
    blog_post_id INT NOT NULL,
    author_id INT NOT NULL,
    PRIMARY KEY (blog_post_id, author_id),
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create directory_items table
CREATE TABLE directory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    url VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create games table
CREATE TABLE games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    url VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create ai_tools table
CREATE TABLE ai_tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    url VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create media table
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('story', 'author', 'blog_post', 'directory_item', 'game', 'ai_tool') NOT NULL,
    entity_id INT NOT NULL,
    type ENUM('cover', 'avatar', 'logo', 'thumbnail') NOT NULL,
    url VARCHAR(255) NOT NULL,
    width INT,
    height INT,
    alt_text VARCHAR(255),
    created_at DATETIME NOT NULL,
    INDEX (entity_type, entity_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX idx_stories_featured ON stories(featured);
CREATE INDEX idx_stories_published_at ON stories(published_at);
CREATE INDEX idx_authors_featured ON authors(featured);
CREATE INDEX idx_blog_posts_published_at ON blog_posts(published_at);
CREATE INDEX idx_directory_items_category ON directory_items(category);
CREATE INDEX idx_games_category ON games(category);
CREATE INDEX idx_ai_tools_category ON ai_tools(category);

-- Insert sample data for testing

-- Insert admin user
INSERT INTO users (name, email, password, role, active, created_at, updated_at)
VALUES ('Admin', 'admin@example.com', '$2y$12$1234567890123456789012uQFWnzxixQx.8RqMZ8hQXyGkTjrsZBW', 'admin', 1, NOW(), NOW());

-- Insert sample author
INSERT INTO authors (name, slug, bio, featured, twitter, instagram, website, created_at, updated_at)
VALUES ('John Doe', 'john-doe', 'Children\'s book author and storyteller', 1, 'johndoe', 'johndoe', 'https://johndoe.example.com', NOW(), NOW());

-- Insert sample tag
INSERT INTO tags (name, slug, created_at, updated_at)
VALUES ('Fantasy', 'fantasy', NOW(), NOW());

-- Insert sample story
INSERT INTO stories (title, slug, excerpt, content, published_at, featured, average_rating, review_count, estimated_reading_time, is_sponsored, age_group, needs_moderation, is_self_published, is_ai_enhanced, created_at, updated_at)
VALUES ('The Magic Forest', 'the-magic-forest', 'A story about a magical forest and the adventures within.', '<p>Once upon a time, there was a magical forest...</p>', NOW(), 1, 4.5, 10, '5 minutes', 0, '5-8', 0, 1, 0, NOW(), NOW());

-- Associate story with author
INSERT INTO story_authors (story_id, author_id)
VALUES (1, 1);

-- Associate story with tag
INSERT INTO story_tags (story_id, tag_id)
VALUES (1, 1);

-- Insert sample blog post
INSERT INTO blog_posts (title, slug, excerpt, content, published_at, created_at, updated_at)
VALUES ('Writing Tips for Children\'s Stories', 'writing-tips-for-childrens-stories', 'Learn how to write engaging children\'s stories with these tips.', '<p>Writing for children requires a special approach...</p>', NOW(), NOW(), NOW());

-- Associate blog post with author
INSERT INTO blog_post_authors (blog_post_id, author_id)
VALUES (1, 1);

-- Insert sample directory item
INSERT INTO directory_items (name, description, url, category, created_at, updated_at)
VALUES ('Children\'s Library', 'A library dedicated to children\'s books', 'https://library.example.com', 'Resources', NOW(), NOW());

-- Insert sample game
INSERT INTO games (title, description, url, category, created_at, updated_at)
VALUES ('Word Adventure', 'A fun word game for young readers', '/games/word-adventure', 'Educational', NOW(), NOW());

-- Insert sample AI tool
INSERT INTO ai_tools (name, description, url, category, created_at, updated_at)
VALUES ('Story Helper', 'AI-powered writing assistant for young authors', '/ai-tools/story-helper', 'Writing', NOW(), NOW());

-- Insert sample media
INSERT INTO media (entity_type, entity_id, type, url, width, height, alt_text, created_at)
VALUES 
('story', 1, 'cover', 'https://example.com/images/magic-forest-cover.jpg', 1200, 800, 'The Magic Forest Cover', NOW()),
('author', 1, 'avatar', 'https://example.com/images/john-doe-avatar.jpg', 300, 300, 'John Doe', NOW()),
('blog_post', 1, 'cover', 'https://example.com/images/writing-tips-cover.jpg', 1200, 800, 'Writing Tips Cover', NOW()),
('directory_item', 1, 'logo', 'https://example.com/images/library-logo.jpg', 200, 200, 'Children\'s Library Logo', NOW()),
('game', 1, 'thumbnail', 'https://example.com/images/word-adventure-thumbnail.jpg', 300, 200, 'Word Adventure Thumbnail', NOW()),
('ai_tool', 1, 'logo', 'https://example.com/images/story-helper-logo.jpg', 200, 200, 'Story Helper Logo', NOW());