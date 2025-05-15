-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 27, 2025 at 10:55 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stories_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_tools`
--

CREATE TABLE `ai_tools` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `tool_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_type` enum('free','freemium','paid','subscription') COLLATE utf8mb4_unicode_ci DEFAULT 'free',
  `price_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` text COLLATE utf8mb4_unicode_ci,
  `rating` decimal(3,1) DEFAULT '0.0',
  `featured` tinyint(1) DEFAULT '0',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_tools`
--

INSERT INTO `ai_tools` (`id`, `title`, `description`, `category_id`, `slug`, `published_at`, `tool_url`, `pricing_type`, `price_info`, `features`, `rating`, `featured`, `cover_url`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Test AI Tool2', 'Test tool description', NULL, 'test-ai-tool', '2025-04-26 13:33:00', 'http://example.com', 'free', '', '', 0.0, 1, 'https://example.com/tool1.jpg', 1, '2025-04-26 09:17:50', '2025-04-26 13:33:26'),
(2, 'Another AI Tool', 'More tool content', NULL, 'another-ai-tool', NULL, 'http://example.org', 'paid', NULL, NULL, 0.0, 0, 'https://example.com/tool2.jpg', 1, '2025-04-26 09:17:50', '2025-04-26 09:17:50'),
(3, '222222', '', NULL, '222222', '2025-04-26 15:56:00', '', 'free', '', '', 0.0, 1, NULL, 1, '2025-04-26 15:56:12', '2025-04-26 15:56:12'),
(4, '33333', '', 2, '33333', '2025-04-26 15:56:00', '', 'free', '', '', 5.0, 1, NULL, 1, '2025-04-26 15:56:28', '2025-04-26 15:56:28');

-- --------------------------------------------------------

--
-- Table structure for table `ai_tool_categories`
--

CREATE TABLE `ai_tool_categories` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ai_tool_categories`
--

INSERT INTO `ai_tool_categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Text Generation', 'text-generation', 'AI tools for generating text content', '2025-04-25 16:07:39', '2025-04-25 16:07:39'),
(2, 'Image Generation', 'image-generation', 'AI tools for generating images', '2025-04-25 16:07:39', '2025-04-25 16:07:39'),
(3, 'Content Summarization', 'content-summarization', 'AI tools for summarizing content', '2025-04-25 16:07:39', '2025-04-25 16:07:39'),
(4, 'Translation', 'translation', 'AI tools for translating content', '2025-04-25 16:07:39', '2025-04-25 16:07:39'),
(5, 'Chatbots', 'chatbots', 'AI chatbot tools', '2025-04-25 16:07:39', '2025-04-25 16:07:39');

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `name`, `slug`, `bio`, `avatar_url`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'john-doe', 'A test author', 'https://example.com/avatar1.jpg', 1, '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(2, 'Jane Smith', 'jane-smith', 'Another test author', '', 1, '2025-04-26 08:17:50', '2025-04-26 22:01:29'),
(3, 'dave', 'dave', 'dave', 'https://media.designrush.com/agency_team_bios/452524/conversions/david-bryan-opace-thumb.jpg', 1, '2025-04-26 14:55:17', '2025-04-26 22:01:06');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `auth_tokens`
--

INSERT INTO `auth_tokens` (`user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 'MXxhZG1pbnwxNzQ1NjkwODg1fDdkY2ZjNjlhOTgwNTdjODQ4ZjBkMjJiY2M0NDk2OTlkfDUxYjdmZWE1MmQ2M2FkNDEzZTRiZTI3OTQ4ZWNhMWE2NGJjMTMxZWVlYjAxMmE1NjYyMzdlYTlmOGZkYjQ4ODA=', '2025-04-27 19:08:05', '2025-04-26 08:05:16');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `author_id` int DEFAULT NULL,
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `content`, `excerpt`, `slug`, `is_published`, `author_id`, `cover_url`, `created_at`, `updated_at`) VALUES
(1, 'Writing Tips for Children', 'Full blog post content...', 'Learn how to write for children...', 'writing-tips-for-children', 1, 3, 'https://example.com/blog1.jpg', '2025-04-26 08:17:50', '2025-04-26 20:33:25'),
(2, 'The Importance of Reading', 'More blog content...', 'Why reading matters...', 'importance-of-reading', 1, 2, 'https://example.com/blog2.jpg', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(3, 'dsfsdfsdfsd', 'sdfsdf', 'sdfsdf', 'sdasdasd', 1, 2, '', '2025-04-26 15:18:47', '2025-04-26 15:18:47'),
(4, 'dfsdfsdf', 'sdfsdfsdf', 'sdfsdfsdf', 'dsfadsfds', 1, 1, '', '2025-04-26 15:22:55', '2025-04-26 20:33:31');

-- --------------------------------------------------------

--
-- Table structure for table `directory_categories`
--

CREATE TABLE `directory_categories` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `directory_categories`
--

INSERT INTO `directory_categories` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'General', 'general', 'General directory listings', '2025-04-25 16:07:50', '2025-04-25 16:07:50'),
(2, 'Business', 'business', 'Business directory listings', '2025-04-25 16:07:50', '2025-04-25 16:07:50'),
(3, 'Education', 'education', 'Education directory listings', '2025-04-25 16:07:50', '2025-04-25 16:07:50');

-- --------------------------------------------------------

--
-- Table structure for table `directory_items`
--

CREATE TABLE `directory_items` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(3,1) DEFAULT '0.0',
  `price_range` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `story_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `directory_items`
--

INSERT INTO `directory_items` (`id`, `title`, `description`, `category_id`, `slug`, `published_at`, `website_url`, `contact_email`, `contact_phone`, `address`, `featured`, `rating`, `price_range`, `cover_url`, `is_published`, `created_at`, `updated_at`, `story_id`) VALUES
(1, 'Test Directory', 'Test directory description', NULL, 'test-directory', NULL, 'http://example.com', NULL, NULL, NULL, 0, 4.5, 'Free', 'https://example.com/dir1.jpg', 1, '2025-04-26 09:17:50', '2025-04-26 09:17:50', NULL),
(2, 'Another Directory', 'More directory content', NULL, 'another-directory', NULL, 'http://example.org', NULL, NULL, NULL, 0, 4.0, 'Premium', 'https://example.com/dir2.jpg', 1, '2025-04-26 09:17:50', '2025-04-26 09:17:50', NULL),
(3, '123333', '', NULL, '123333', '2025-04-26 15:55:00', '', '', '', '', 0, 0.0, NULL, NULL, 1, '2025-04-26 15:55:59', '2025-04-26 15:55:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `featured` tinyint(1) DEFAULT '0',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `developer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT '0.0',
  `price` decimal(10,2) DEFAULT '0.00',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `title`, `description`, `featured`, `slug`, `website_url`, `genre`, `platform`, `developer`, `publisher`, `release_date`, `rating`, `price`, `cover_url`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'Test Game', 'Test game description', 0, 'test-game', 'http://example.com', 'Action', 'PC', 'Test Dev', 'Test Pub', NULL, 0.0, 0.00, 'https://example.com/game1.jpg', 1, '2025-04-26 16:27:59', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(2, 'Another Game', 'More game content', 0, 'another-game', 'http://example.org', 'RPG', 'Console', 'Dev2', 'Pub2', NULL, 0.0, 0.00, 'https://example.com/game2.jpg', 1, '2025-04-26 16:27:59', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(3, 'testgame', '', 0, 'testgame', NULL, NULL, NULL, NULL, NULL, NULL, 0.0, 0.00, NULL, 1, '2025-04-26 16:30:00', '2025-04-26 15:30:07', '2025-04-26 15:30:07');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `post_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`post_id`, `tag_id`) VALUES
(1, 4),
(2, 4);

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `id` int NOT NULL,
  `source_type` enum('child','parent','classic') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'child',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `featured` tinyint(1) DEFAULT '0',
  `average_rating` decimal(3,1) DEFAULT '4.5',
  `allow_reviews` tinyint(1) NOT NULL DEFAULT '0',
  `review_count` int DEFAULT '10',
  `estimated_reading_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '5 minutes',
  `is_sponsored` tinyint(1) DEFAULT '0',
  `age_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '12+',
  `needs_moderation` tinyint(1) DEFAULT '0',
  `is_self_published` tinyint(1) DEFAULT '1',
  `is_ai_enhanced` tinyint(1) DEFAULT '0',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'https://example.com/cover.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stories`
--

INSERT INTO `stories` (`id`, `source_type`, `title`, `content`, `excerpt`, `slug`, `is_published`, `featured`, `average_rating`, `allow_reviews`, `review_count`, `estimated_reading_time`, `is_sponsored`, `age_group`, `needs_moderation`, `is_self_published`, `is_ai_enhanced`, `cover_url`, `created_at`, `updated_at`) VALUES
(1, 'child', 'Example Story2', 'Full story content here...', 'This is an example story...', 'example-story', 1, 1, 3.1, 0, 3, '5 minutes', 1, '12+', 1, 1, 1, 'https://images.pexels.com/photos/1323206/pexels-photo-1323206.jpeg', '2025-04-26 08:17:50', '2025-04-26 22:58:16'),
(2, 'child', 'Another Story2', 'More story content...22', 'Another great story...2', 'another-story2', 1, 0, 2.1, 0, 10, '2 minutes', 0, '2+', 0, 1, 0, 'https://example.com/cover2.jpg2', '2025-04-26 08:17:50', '2025-04-26 22:25:51');

-- --------------------------------------------------------

--
-- Table structure for table `story_authors`
--

CREATE TABLE `story_authors` (
  `story_id` int NOT NULL,
  `author_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `story_authors`
--

INSERT INTO `story_authors` (`story_id`, `author_id`) VALUES
(1, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `story_tags`
--

CREATE TABLE `story_tags` (
  `story_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `story_tags`
--

INSERT INTO `story_tags` (`story_id`, `tag_id`) VALUES
(1, 1),
(2, 1),
(1, 2),
(2, 2),
(2, 3),
(2, 4);

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Fiction', 'fiction', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(2, 'Fantasy', 'fantasy', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(3, 'Adventure', 'adventure', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(4, 'Educational', 'educational', '2025-04-26 08:17:50', '2025-04-26 08:17:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@storiesfromtheweb.org', '$2y$10$HlYBua/XIdDenKjZoPltJeT26xuptOMlo0O6GrIP/n1HqYoY2N8ai', 'admin', 1, '2025-04-26 07:49:57', '2025-04-26 07:49:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_tools`
--
ALTER TABLE `ai_tools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `ai_tool_categories`
--
ALTER TABLE `ai_tool_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `directory_categories`
--
ALTER TABLE `directory_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `directory_items`
--
ALTER TABLE `directory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_directory_story` (`story_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `story_authors`
--
ALTER TABLE `story_authors`
  ADD PRIMARY KEY (`story_id`,`author_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `story_tags`
--
ALTER TABLE `story_tags`
  ADD PRIMARY KEY (`story_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_tools`
--
ALTER TABLE `ai_tools`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ai_tool_categories`
--
ALTER TABLE `ai_tool_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `directory_categories`
--
ALTER TABLE `directory_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `directory_items`
--
ALTER TABLE `directory_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `directory_items`
--
ALTER TABLE `directory_items`
  ADD CONSTRAINT `fk_directory_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_authors`
--
ALTER TABLE `story_authors`
  ADD CONSTRAINT `story_authors_ibfk_1` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_authors_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_tags`
--
ALTER TABLE `story_tags`
  ADD CONSTRAINT `story_tags_ibfk_1` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
