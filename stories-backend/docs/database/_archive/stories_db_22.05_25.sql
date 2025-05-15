-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 11, 2025 at 08:13 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.20

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

DELIMITER $$
--
-- Procedures
--
$$

--
-- Functions
--
$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ai_generations`
--

CREATE TABLE `ai_generations` (
  `id` int NOT NULL,
  `provider_id` int DEFAULT NULL,
  `type` enum('image','text','audio','video') COLLATE utf8mb4_unicode_ci NOT NULL,
  `prompt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `result_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` enum('pending','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_models_cache`
--

CREATE TABLE `ai_models_cache` (
  `id` int NOT NULL,
  `models_data` longtext NOT NULL,
  `timestamp` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ai_models_cache`
--

INSERT INTO `ai_models_cache` (`id`, `models_data`, `timestamp`) VALUES
(1, '{\"image\":{\"dall-e-3\":\"DALL\\u00b7E 3 (Legacy)\",\"dall-e-2\":\"DALL\\u00b7E 2 (Legacy)\",\"gpt-image-1\":\"GPT Image 1 (Latest)\"},\"text\":{\"gpt-4-turbo-preview\":\"gpt-4-turbo-preview\",\"gpt-4-1106-preview\":\"gpt-4-1106-preview\",\"gpt-4-turbo\":\"gpt-4-turbo\",\"o3-2025-04-16\":\"o3-2025-04-16\",\"gpt-4-turbo-2024-04-09\":\"gpt-4-turbo-2024-04-09\",\"gpt-4.1-nano\":\"gpt-4.1-nano\",\"gpt-4.1-nano-2025-04-14\":\"gpt-4.1-nano-2025-04-14\",\"gpt-4o-realtime-preview-2024-10-01\":\"gpt-4o-realtime-preview-2024-10-01\",\"o3\":\"o3 (Powerful)\",\"gpt-4o-realtime-preview\":\"gpt-4o-realtime-preview\",\"gpt-4\":\"gpt-4\",\"gpt-4o-mini-realtime-preview\":\"gpt-4o-mini-realtime-preview\",\"gpt-4.1-mini\":\"gpt-4.1-mini\",\"gpt-4o-mini-realtime-preview-2024-12-17\":\"gpt-4o-mini-realtime-preview-2024-12-17\",\"gpt-4.1-mini-2025-04-14\":\"gpt-4.1-mini-2025-04-14\",\"gpt-3.5-turbo-16k\":\"gpt-3.5-turbo-16k\",\"gpt-3.5-turbo-1106\":\"gpt-3.5-turbo-1106\",\"gpt-3.5-turbo\":\"GPT-3.5 Turbo (Economical)\",\"gpt-4-0125-preview\":\"gpt-4-0125-preview\",\"gpt-4o-2024-11-20\":\"gpt-4o-2024-11-20\",\"gpt-4o-2024-05-13\":\"gpt-4o-2024-05-13\",\"gpt-4-0613\":\"gpt-4-0613\",\"gpt-4o-mini-tts\":\"gpt-4o-mini-tts\",\"gpt-4o-transcribe\":\"gpt-4o-transcribe\",\"gpt-4.5-preview\":\"gpt-4.5-preview\",\"gpt-4.5-preview-2025-02-27\":\"gpt-4.5-preview-2025-02-27\",\"gpt-4o-mini-transcribe\":\"gpt-4o-mini-transcribe\",\"o3-mini\":\"o3-mini (Balanced)\",\"o3-mini-2025-01-31\":\"o3-mini-2025-01-31\",\"gpt-4o\":\"GPT-4o (Balanced)\",\"gpt-4o-mini\":\"gpt-4o-mini\",\"gpt-4o-2024-08-06\":\"gpt-4o-2024-08-06\",\"gpt-4.1\":\"GPT-4.1 (Latest)\",\"gpt-4.1-2025-04-14\":\"gpt-4.1-2025-04-14\",\"gpt-4o-mini-2024-07-18\":\"gpt-4o-mini-2024-07-18\",\"gpt-3.5-turbo-0125\":\"gpt-3.5-turbo-0125\",\"gpt-4o-realtime-preview-2024-12-17\":\"gpt-4o-realtime-preview-2024-12-17\",\"o4-mini-2025-04-16\":\"o4-mini-2025-04-16\",\"o4-mini\":\"o4-mini (Fast)\"}}', 1746514848),
(2, '{\"image\":{\"dall-e-3\":\"DALL\\u00b7E 3 (Legacy)\",\"dall-e-2\":\"DALL\\u00b7E 2 (Legacy)\",\"gpt-image-1\":\"GPT Image 1 (Latest)\"},\"text\":{\"gpt-4-turbo-preview\":\"gpt-4-turbo-preview\",\"gpt-4-1106-preview\":\"gpt-4-1106-preview\",\"gpt-4-turbo\":\"gpt-4-turbo\",\"o3-2025-04-16\":\"o3-2025-04-16\",\"gpt-4-turbo-2024-04-09\":\"gpt-4-turbo-2024-04-09\",\"gpt-4.1-nano\":\"gpt-4.1-nano\",\"gpt-4.1-nano-2025-04-14\":\"gpt-4.1-nano-2025-04-14\",\"gpt-4o-realtime-preview-2024-10-01\":\"gpt-4o-realtime-preview-2024-10-01\",\"o3\":\"o3 (Powerful)\",\"gpt-4o-realtime-preview\":\"gpt-4o-realtime-preview\",\"gpt-4\":\"gpt-4\",\"gpt-4o-mini-realtime-preview\":\"gpt-4o-mini-realtime-preview\",\"gpt-4.1-mini\":\"gpt-4.1-mini\",\"gpt-4o-mini-realtime-preview-2024-12-17\":\"gpt-4o-mini-realtime-preview-2024-12-17\",\"gpt-4.1-mini-2025-04-14\":\"gpt-4.1-mini-2025-04-14\",\"gpt-3.5-turbo-16k\":\"gpt-3.5-turbo-16k\",\"gpt-3.5-turbo-1106\":\"gpt-3.5-turbo-1106\",\"gpt-3.5-turbo\":\"GPT-3.5 Turbo (Economical)\",\"gpt-4-0125-preview\":\"gpt-4-0125-preview\",\"gpt-4o-2024-11-20\":\"gpt-4o-2024-11-20\",\"gpt-4o-2024-05-13\":\"gpt-4o-2024-05-13\",\"gpt-4-0613\":\"gpt-4-0613\",\"gpt-4o-mini-tts\":\"gpt-4o-mini-tts\",\"gpt-4o-transcribe\":\"gpt-4o-transcribe\",\"gpt-4.5-preview\":\"gpt-4.5-preview\",\"gpt-4.5-preview-2025-02-27\":\"gpt-4.5-preview-2025-02-27\",\"gpt-4o-mini-transcribe\":\"gpt-4o-mini-transcribe\",\"o3-mini\":\"o3-mini (Balanced)\",\"o3-mini-2025-01-31\":\"o3-mini-2025-01-31\",\"gpt-4o\":\"GPT-4o (Balanced)\",\"gpt-4o-mini\":\"gpt-4o-mini\",\"gpt-4o-2024-08-06\":\"gpt-4o-2024-08-06\",\"gpt-4.1\":\"GPT-4.1 (Latest)\",\"gpt-4.1-2025-04-14\":\"gpt-4.1-2025-04-14\",\"gpt-4o-mini-2024-07-18\":\"gpt-4o-mini-2024-07-18\",\"gpt-3.5-turbo-0125\":\"gpt-3.5-turbo-0125\",\"gpt-4o-realtime-preview-2024-12-17\":\"gpt-4o-realtime-preview-2024-12-17\",\"o4-mini-2025-04-16\":\"o4-mini-2025-04-16\",\"o4-mini\":\"o4-mini (Fast)\"}}', 1746516403),
(3, '{\"image\":{\"dall-e-3\":\"DALL\\u00b7E 3 (Legacy)\",\"dall-e-2\":\"DALL\\u00b7E 2 (Legacy)\",\"gpt-image-1\":\"GPT Image 1 (Latest)\"},\"text\":{\"gpt-4-turbo-preview\":\"gpt-4-turbo-preview\",\"gpt-4-1106-preview\":\"gpt-4-1106-preview\",\"gpt-4-turbo\":\"gpt-4-turbo\",\"o3-2025-04-16\":\"o3-2025-04-16\",\"gpt-4-turbo-2024-04-09\":\"gpt-4-turbo-2024-04-09\",\"gpt-4.1-nano\":\"gpt-4.1-nano\",\"gpt-4.1-nano-2025-04-14\":\"gpt-4.1-nano-2025-04-14\",\"gpt-4o-realtime-preview-2024-10-01\":\"gpt-4o-realtime-preview-2024-10-01\",\"o3\":\"o3 (Powerful)\",\"gpt-4o-realtime-preview\":\"gpt-4o-realtime-preview\",\"gpt-4\":\"gpt-4\",\"gpt-4o-mini-realtime-preview\":\"gpt-4o-mini-realtime-preview\",\"gpt-4.1-mini\":\"gpt-4.1-mini\",\"gpt-4o-mini-realtime-preview-2024-12-17\":\"gpt-4o-mini-realtime-preview-2024-12-17\",\"gpt-4.1-mini-2025-04-14\":\"gpt-4.1-mini-2025-04-14\",\"gpt-3.5-turbo-16k\":\"gpt-3.5-turbo-16k\",\"gpt-3.5-turbo-1106\":\"gpt-3.5-turbo-1106\",\"gpt-3.5-turbo\":\"GPT-3.5 Turbo (Economical)\",\"gpt-4-0125-preview\":\"gpt-4-0125-preview\",\"gpt-4o-2024-11-20\":\"gpt-4o-2024-11-20\",\"gpt-4o-2024-05-13\":\"gpt-4o-2024-05-13\",\"gpt-4-0613\":\"gpt-4-0613\",\"gpt-4o-mini-tts\":\"gpt-4o-mini-tts\",\"gpt-4o-transcribe\":\"gpt-4o-transcribe\",\"gpt-4.5-preview\":\"gpt-4.5-preview\",\"gpt-4.5-preview-2025-02-27\":\"gpt-4.5-preview-2025-02-27\",\"gpt-4o-mini-transcribe\":\"gpt-4o-mini-transcribe\",\"o3-mini\":\"o3-mini (Balanced)\",\"o3-mini-2025-01-31\":\"o3-mini-2025-01-31\",\"gpt-4o\":\"GPT-4o (Balanced)\",\"gpt-4o-mini\":\"gpt-4o-mini\",\"gpt-4o-2024-08-06\":\"gpt-4o-2024-08-06\",\"gpt-4.1\":\"GPT-4.1 (Latest)\",\"gpt-4.1-2025-04-14\":\"gpt-4.1-2025-04-14\",\"gpt-4o-mini-2024-07-18\":\"gpt-4o-mini-2024-07-18\",\"gpt-3.5-turbo-0125\":\"gpt-3.5-turbo-0125\",\"gpt-4o-realtime-preview-2024-12-17\":\"gpt-4o-realtime-preview-2024-12-17\",\"o4-mini-2025-04-16\":\"o4-mini-2025-04-16\",\"o4-mini\":\"o4-mini (Fast)\"}}', 1746516410),
(4, '{\"image\":{\"dall-e-3\":\"DALL\\u00b7E 3 (Legacy)\",\"dall-e-2\":\"DALL\\u00b7E 2 (Legacy)\",\"gpt-image-1\":\"GPT Image 1 (Latest)\"},\"text\":{\"gpt-4-turbo-preview\":\"gpt-4-turbo-preview\",\"o3-2025-04-16\":\"o3-2025-04-16\",\"gpt-4.1-nano\":\"gpt-4.1-nano\",\"gpt-4.1-nano-2025-04-14\":\"gpt-4.1-nano-2025-04-14\",\"gpt-4o-realtime-preview-2024-10-01\":\"gpt-4o-realtime-preview-2024-10-01\",\"o3\":\"o3 (Powerful)\",\"gpt-4o-realtime-preview\":\"gpt-4o-realtime-preview\",\"gpt-4\":\"gpt-4\",\"gpt-4-1106-preview\":\"gpt-4-1106-preview\",\"gpt-4o-mini-realtime-preview\":\"gpt-4o-mini-realtime-preview\",\"gpt-4.1-mini\":\"gpt-4.1-mini\",\"gpt-4o-mini-realtime-preview-2024-12-17\":\"gpt-4o-mini-realtime-preview-2024-12-17\",\"gpt-4.1-mini-2025-04-14\":\"gpt-4.1-mini-2025-04-14\",\"gpt-3.5-turbo-16k\":\"gpt-3.5-turbo-16k\",\"gpt-3.5-turbo-1106\":\"gpt-3.5-turbo-1106\",\"gpt-3.5-turbo\":\"GPT-3.5 Turbo (Economical)\",\"gpt-4-0125-preview\":\"gpt-4-0125-preview\",\"gpt-4o-2024-11-20\":\"gpt-4o-2024-11-20\",\"gpt-4o-2024-05-13\":\"gpt-4o-2024-05-13\",\"gpt-4-0613\":\"gpt-4-0613\",\"gpt-4o-mini-tts\":\"gpt-4o-mini-tts\",\"gpt-4o-transcribe\":\"gpt-4o-transcribe\",\"gpt-4.5-preview\":\"gpt-4.5-preview\",\"gpt-4.5-preview-2025-02-27\":\"gpt-4.5-preview-2025-02-27\",\"gpt-4o-mini-transcribe\":\"gpt-4o-mini-transcribe\",\"o3-mini\":\"o3-mini (Balanced)\",\"o3-mini-2025-01-31\":\"o3-mini-2025-01-31\",\"gpt-4o\":\"GPT-4o (Balanced)\",\"gpt-4o-mini\":\"gpt-4o-mini\",\"gpt-4o-2024-08-06\":\"gpt-4o-2024-08-06\",\"gpt-4.1\":\"GPT-4.1 (Latest)\",\"gpt-4.1-2025-04-14\":\"gpt-4.1-2025-04-14\",\"gpt-4o-mini-2024-07-18\":\"gpt-4o-mini-2024-07-18\",\"gpt-3.5-turbo-0125\":\"gpt-3.5-turbo-0125\",\"gpt-4o-realtime-preview-2024-12-17\":\"gpt-4o-realtime-preview-2024-12-17\",\"gpt-4-turbo\":\"gpt-4-turbo\",\"gpt-4-turbo-2024-04-09\":\"gpt-4-turbo-2024-04-09\",\"o4-mini-2025-04-16\":\"o4-mini-2025-04-16\",\"o4-mini\":\"o4-mini (Fast)\"}}', 1746633519),
(5, '{\"image\":{\"dall-e-3\":\"DALL\\u00b7E 3 (Legacy)\",\"dall-e-2\":\"DALL\\u00b7E 2 (Legacy)\",\"gpt-image-1\":\"GPT Image 1 (Latest)\"},\"text\":{\"gpt-4-turbo-preview\":\"gpt-4-turbo-preview\",\"o3-2025-04-16\":\"o3-2025-04-16\",\"gpt-4o-realtime-preview-2024-10-01\":\"gpt-4o-realtime-preview-2024-10-01\",\"o3\":\"o3 (Powerful)\",\"gpt-4o-realtime-preview\":\"gpt-4o-realtime-preview\",\"gpt-4\":\"gpt-4\",\"gpt-4o-mini-realtime-preview\":\"gpt-4o-mini-realtime-preview\",\"gpt-4o-mini-realtime-preview-2024-12-17\":\"gpt-4o-mini-realtime-preview-2024-12-17\",\"gpt-3.5-turbo-16k\":\"gpt-3.5-turbo-16k\",\"gpt-3.5-turbo-1106\":\"gpt-3.5-turbo-1106\",\"gpt-3.5-turbo\":\"GPT-3.5 Turbo (Economical)\",\"gpt-4-0125-preview\":\"gpt-4-0125-preview\",\"gpt-4o-2024-11-20\":\"gpt-4o-2024-11-20\",\"gpt-4o-2024-05-13\":\"gpt-4o-2024-05-13\",\"gpt-4-1106-preview\":\"gpt-4-1106-preview\",\"gpt-4-0613\":\"gpt-4-0613\",\"gpt-4o-mini-tts\":\"gpt-4o-mini-tts\",\"gpt-4o-transcribe\":\"gpt-4o-transcribe\",\"gpt-4.5-preview\":\"gpt-4.5-preview\",\"gpt-4.5-preview-2025-02-27\":\"gpt-4.5-preview-2025-02-27\",\"o3-mini\":\"o3-mini (Balanced)\",\"o3-mini-2025-01-31\":\"o3-mini-2025-01-31\",\"gpt-4o\":\"GPT-4o (Balanced)\",\"gpt-4o-2024-08-06\":\"gpt-4o-2024-08-06\",\"gpt-4o-mini-2024-07-18\":\"gpt-4o-mini-2024-07-18\",\"gpt-4.1-mini\":\"gpt-4.1-mini\",\"gpt-4o-mini\":\"gpt-4o-mini\",\"gpt-3.5-turbo-0125\":\"gpt-3.5-turbo-0125\",\"gpt-4-turbo\":\"gpt-4-turbo\",\"gpt-4o-realtime-preview-2024-12-17\":\"gpt-4o-realtime-preview-2024-12-17\",\"gpt-4-turbo-2024-04-09\":\"gpt-4-turbo-2024-04-09\",\"gpt-4o-mini-transcribe\":\"gpt-4o-mini-transcribe\",\"gpt-4.1-mini-2025-04-14\":\"gpt-4.1-mini-2025-04-14\",\"gpt-4.1\":\"GPT-4.1 (Latest)\",\"gpt-4.1-2025-04-14\":\"gpt-4.1-2025-04-14\",\"gpt-4.1-nano-2025-04-14\":\"gpt-4.1-nano-2025-04-14\",\"o4-mini-2025-04-16\":\"o4-mini-2025-04-16\",\"o4-mini\":\"o4-mini (Fast)\",\"gpt-4.1-nano\":\"gpt-4.1-nano\"}}', 1746782869);

-- --------------------------------------------------------

--
-- Table structure for table `ai_prompt_templates`
--

CREATE TABLE `ai_prompt_templates` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `content_type` enum('story','blog_post','author','game','ai_tool','directory','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `prompt_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_prompt_templates`
--

INSERT INTO `ai_prompt_templates` (`id`, `name`, `description`, `content_type`, `prompt_template`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Story Cover', 'Generate a cover image for a children\'s story', 'story', 'Generate an image for a children\'s story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: {{title}}{{#if summary}}. Summary: {{summary}}{{/if}}{{#if age_group}}. Target age: {{age_group}}{{/if}}', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15'),
(2, 'Blog Post Cover', 'Generate a cover image for a blog post', 'blog_post', 'Create a professional and engaging featured image for a blog post titled \"{{title}}\". {{#if summary}}The post discusses {{summary}}.{{/if}} Style: clean, modern design with relevant imagery that captures the essence of the topic.', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15'),
(3, 'Author Avatar', 'Generate an avatar for an author', 'author', 'Create a professional portrait-style avatar for an author named {{name}}. {{#if bio}}They describe themselves as: {{bio}}{{/if}}. Style: warm, approachable, professional illustration suitable for an author profile.', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15'),
(4, 'Game Cover', 'Generate a cover image for a game', 'game', 'Create an exciting game cover image for \"{{title}}\". {{#if description}}The game is about {{description}}{{/if}}. {{#if genre}}Genre: {{genre}}{{/if}}. Style: dynamic, colorful, eye-catching design that conveys the excitement and theme of the game.', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15'),
(5, 'AI Tool Icon', 'Generate an icon for an AI tool', 'ai_tool', 'Create a modern icon for an AI tool called \"{{title}}\". {{#if description}}The tool\'s purpose is {{description}}{{/if}}. Style: sleek, tech-focused design with AI-themed elements, using blues and purples for a tech feel.', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15'),
(6, 'Directory Listing Image', 'Generate an image for a directory listing', 'directory', 'Create a representative image for a directory listing titled \"{{title}}\". {{#if description}}This is {{description}}{{/if}}. Style: clean, professional image that represents the business or service.', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15'),
(7, 'General Image', 'Generate a general purpose image', 'general', 'Create an image based on the following description: {{title}}{{#if description}}. {{description}}{{/if}}. Style: professional, high-quality, suitable for a website.', 1, '2025-05-08 14:48:15', '2025-05-08 14:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `ai_providers`
--

CREATE TABLE `ai_providers` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('image','text','audio','video') COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_providers`
--

INSERT INTO `ai_providers` (`id`, `name`, `type`, `config`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'openai', 'image', '{\"model\": \"gpt-image-1\", \"api_key\": \"YOUR_API_KEY_HERE\", \"max_tokens\": 2000, \"text_model\": \"gpt-4o\", \"temperature\": 0.7}', 1, '2025-05-05 16:49:24', '2025-05-06 07:24:07');

-- --------------------------------------------------------

--
-- Table structure for table `ai_rate_limit`
--

CREATE TABLE `ai_rate_limit` (
  `id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `ai_rate_limit`
--
DELIMITER $$
CREATE TRIGGER `cleanup_rate_limit` BEFORE INSERT ON `ai_rate_limit` FOR EACH ROW BEGIN
            DELETE FROM ai_rate_limit
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);
        END
$$
DELIMITER ;

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
(1, 'Test AI Tool2', 'Test tool description', 3, 'test-ai-tool', NULL, 'http://example.com', 'free', '', '', 0.0, 1, 'https://api.storiesfromtheweb.org/public/uploads/681b76ec37921-vibrant-dinosaur-adventure.png', 1, '2025-04-26 09:17:50', '2025-05-07 17:02:57'),
(2, 'Another AI Tool', 'More tool content', 3, 'another-ai-tool', NULL, 'http://example.org', 'paid', '', '', 0.0, 0, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92b5341d-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-medium.webp', 1, '2025-04-26 09:17:50', '2025-05-10 22:01:35'),
(3, '2222223', '', NULL, '222222', '2025-04-26 15:56:00', '', 'free', '', '', 0.0, 1, NULL, 1, '2025-04-26 15:56:12', '2025-05-06 07:44:48');

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
-- Table structure for table `ai_usage`
--

CREATE TABLE `ai_usage` (
  `id` int NOT NULL,
  `provider_id` int DEFAULT NULL,
  `type` enum('image','text','audio','video') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` decimal(10,6) NOT NULL DEFAULT '0.000000',
  `tokens` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `author_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` tinyint UNSIGNED DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `name`, `slug`, `bio`, `avatar_url`, `is_published`, `created_at`, `updated_at`, `author_type`, `age`, `location`) VALUES
(2148, 'Frances Lincoln', 'frances-lincoln', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa93019790-The-Worst-Witch-by-Jill-Murphy-medium.webp', 1, '2025-05-10 19:28:31', '2025-05-10 20:46:22', 'retail', NULL, ''),
(2149, 'Wendy Meddour', 'wendy-meddour', NULL, NULL, 1, '2025-05-10 19:28:31', '2025-05-10 19:28:31', 'retail', NULL, NULL),
(2150, 'Neil Gaiman', 'neil-gaiman', NULL, NULL, 1, '2025-05-10 19:28:35', '2025-05-10 19:28:35', 'retail', NULL, NULL),
(2151, 'HarperCollins Children\'s Books', 'harpercollins-children-s-books', NULL, NULL, 1, '2025-05-10 19:28:38', '2025-05-10 19:28:38', 'retail', NULL, NULL),
(2152, 'David Walliams', 'david-walliams', NULL, NULL, 1, '2025-05-10 19:28:38', '2025-05-10 19:28:38', 'retail', NULL, NULL),
(2153, 'Diana Wynne Jones', 'diana-wynne-jones', '', 'https://api.storiesfromtheweb.org/uploads/optimized/681fbb64ded760-26955434-Screenshot-2025-05-10-at-17-04-06-medium.webp', 1, '2025-05-10 19:28:40', '2025-05-10 20:47:38', 'retail', NULL, ''),
(2154, 'Marion Lloyd Books, an imprint of Scholastic Ltd', 'marion-lloyd-books-an-imprint-of-scholastic-ltd', NULL, NULL, 1, '2025-05-10 19:28:46', '2025-05-10 19:28:46', 'retail', NULL, NULL),
(2155, 'Philip Reeve', 'philip-reeve', NULL, NULL, 1, '2025-05-10 19:28:46', '2025-05-10 19:28:46', 'retail', NULL, NULL),
(2156, 'Bloomsbury Publishing Plc', 'bloomsbury-publishing-plc', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-medium.webp', 1, '2025-05-10 19:28:48', '2025-05-10 21:09:40', 'retail', NULL, ''),
(2157, 'J.K. Rowling', 'j-k-rowling', NULL, NULL, 1, '2025-05-10 19:28:48', '2025-05-10 19:28:48', 'retail', NULL, NULL),
(2158, 'Yearling Books, an imprint of Random House Children\'s Books', 'yearling-books-an-imprint-of-random-house-children-s-books', NULL, NULL, 1, '2025-05-10 19:28:52', '2025-05-10 19:28:52', 'retail', NULL, NULL),
(2159, 'Louis Sachar', 'louis-sachar', NULL, NULL, 1, '2025-05-10 19:28:52', '2025-05-10 19:28:52', 'retail', NULL, NULL),
(2160, ' Orion Children\'s Books ', 'orion-children-s-books', '', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 19:28:55', '2025-05-10 19:30:53', 'retail', NULL, ''),
(2161, 'Lauren St John', 'lauren-st-john', NULL, NULL, 1, '2025-05-10 19:28:55', '2025-05-10 19:28:55', 'retail', NULL, NULL),
(2162, 'Michael Morpurgo', 'michael-morpurgo', NULL, NULL, 1, '2025-05-10 19:28:57', '2025-05-10 19:28:57', 'retail', NULL, NULL),
(2163, 'David Fickling Books, an imprint of Random House3', 'david-fickling-books-an-imprint-of-random-house3', '', NULL, 1, '2025-05-10 19:28:59', '2025-05-10 20:22:41', 'retail', NULL, ''),
(2164, 'Simon Mason', 'simon-mason', NULL, NULL, 1, '2025-05-10 19:28:59', '2025-05-10 19:28:59', 'retail', NULL, NULL),
(2165, 'Orion Children\'s Books', 'orion-children-s-books-1', NULL, NULL, 1, '2025-05-10 19:29:04', '2025-05-10 19:29:04', 'retail', NULL, NULL),
(2166, 'Maudie Smith', 'maudie-smith', NULL, NULL, 1, '2025-05-10 19:29:04', '2025-05-10 19:29:04', 'retail', NULL, NULL),
(2167, 'Scholastic Children\'s Books', 'scholastic-children-s-books', NULL, NULL, 1, '2025-05-10 19:29:10', '2025-05-10 19:29:10', 'retail', NULL, NULL),
(2168, 'Liz Pichon', 'liz-pichon', NULL, NULL, 1, '2025-05-10 19:29:16', '2025-05-10 19:29:16', 'retail', NULL, NULL),
(2169, 'Harper Collins Children’s Books', 'harper-collins-children-s-books', NULL, NULL, 1, '2025-05-10 19:29:19', '2025-05-10 19:29:19', 'retail', NULL, NULL),
(2170, 'C.S. Lewis', 'c-s-lewis', '', NULL, 1, '2025-05-10 19:29:19', '2025-05-10 20:20:07', 'retail', NULL, ''),
(2171, 'J.R.R. Tolkien', 'j-r-r-tolkien', NULL, NULL, 1, '2025-05-10 19:29:26', '2025-05-10 19:29:26', 'retail', NULL, NULL),
(2172, 'Brian Selznick', 'brian-selznick', '', NULL, 1, '2025-05-10 19:29:29', '2025-05-10 20:14:40', 'retail', NULL, ''),
(2173, 'Egmont UK', 'egmont-uk', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa937538d2-To-Be-A-Cat-by-Matt-Haig-medium.webp', 1, '2025-05-10 19:29:32', '2025-05-10 20:46:33', 'retail', NULL, ''),
(2174, 'Sue Monroe', 'sue-monroe', NULL, NULL, 1, '2025-05-10 19:29:32', '2025-05-10 19:29:32', 'retail', NULL, NULL),
(2175, 'Piccadilly Press', 'piccadilly-press', NULL, NULL, 1, '2025-05-10 19:29:38', '2025-05-10 19:29:38', 'retail', NULL, NULL),
(2176, 'Joanna Nadin', 'joanna-nadin', NULL, NULL, 1, '2025-05-10 19:29:38', '2025-05-10 19:29:38', 'retail', NULL, NULL),
(2177, 'Helen Moss', 'helen-moss', '', NULL, 1, '2025-05-10 19:29:41', '2025-05-10 20:26:54', 'retail', NULL, ''),
(2178, 'Simon & Schuster Children\'s Books', 'simon-schuster-children-s-books', NULL, NULL, 1, '2025-05-10 19:29:44', '2025-05-10 19:29:44', 'retail', NULL, NULL),
(2179, 'Sian Pattenden', 'sian-pattenden', NULL, NULL, 1, '2025-05-10 19:29:44', '2025-05-10 19:29:44', 'retail', NULL, NULL),
(2180, 'Simon & Schuster Books for Young Readers', 'simon-schuster-books-for-young-readers', NULL, NULL, 1, '2025-05-10 19:29:47', '2025-05-10 19:29:47', 'retail', NULL, NULL),
(2181, 'Tony DiTerlizzi and Holly Black', 'tony-diterlizzi-and-holly-black', NULL, NULL, 1, '2025-05-10 19:29:47', '2025-05-10 19:29:47', 'retail', NULL, NULL),
(2182, 'Marion Lloyd Books Text © Kate Saunders 2012', 'marion-lloyd-books-text-kate-saunders-2012', NULL, NULL, 1, '2025-05-10 19:29:49', '2025-05-10 19:29:49', 'retail', NULL, NULL),
(2183, 'ghosts.', 'ghosts', '', NULL, 1, '2025-05-10 19:29:49', '2025-05-10 19:48:28', 'retail', NULL, ''),
(2184, 'Puffin Books, a division of Penguin Books Ltd', 'puffin-books-a-division-of-penguin-books-ltd', NULL, NULL, 1, '2025-05-10 19:29:52', '2025-05-10 19:29:52', 'retail', NULL, NULL),
(2185, 'Jill Murphy', 'jill-murphy', NULL, NULL, 1, '2025-05-10 19:29:52', '2025-05-10 19:29:52', 'retail', NULL, NULL),
(2186, 'The Bodley Head, an imprint of Random House Children\'s Books', 'the-bodley-head-an-imprint-of-random-house-children-s-books', NULL, NULL, 1, '2025-05-10 19:29:59', '2025-05-10 19:29:59', 'retail', NULL, NULL),
(2187, 'Matt Haig', 'matt-haig', NULL, NULL, 1, '2025-05-10 19:29:59', '2025-05-10 19:29:59', 'retail', NULL, NULL),
(2188, 'Natalie Babbitt', 'natalie-babbitt', NULL, NULL, 1, '2025-05-10 19:30:06', '2025-05-10 19:30:06', 'retail', NULL, NULL),
(2189, 'David Fickling Books, an imprint of Random House', 'david-fickling-books-an-imprint-of-random-house', NULL, NULL, 1, '2025-05-10 21:05:38', '2025-05-10 21:05:38', 'retail', NULL, NULL),
(2190, 'Dearbhla', 'dearbhla', 'Dearbhla is a child author aged 9 from Northern Ireland.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:50', '2025-05-10 21:06:50', 'child', 9, 'Northern Ireland'),
(2191, 'Niall', 'niall', 'Niall is a child author aged 9 from Omagh Northern Ireland.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:50', '2025-05-10 21:06:50', 'child', 9, 'Omagh Northern Ireland'),
(2192, 'Kerys', 'kerys', 'Kerys is a child author aged 7 from Paisley.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:50', '2025-05-10 21:06:50', 'child', 7, 'Paisley'),
(2193, 'Aine K', 'aine-k', 'Aine K is a child author aged 9 from Northern Ireland.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:50', '2025-05-10 21:06:50', 'child', 9, 'Northern Ireland'),
(2194, 'Orla', 'orla', 'Orla is a child author aged 11 from The Smiley Book Club.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:50', '2025-05-10 21:06:50', 'child', 11, 'The Smiley Book Club'),
(2195, 'Nadia', 'nadia', 'Nadia is a child author aged 10 from Banbridge.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:50', '2025-05-10 21:06:50', 'child', 10, 'Banbridge'),
(2196, 'Alfie', 'alfie', 'Alfie is a child author aged 9 from Dorset.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 9, 'Dorset'),
(2197, 'Rachel', 'rachel', 'Rachel is a child author aged 12 from South Tyneside.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 12, 'South Tyneside'),
(2198, 'Ellie', 'ellie', 'Ellie is a child author aged 8 from Northern Ireland.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 8, 'Northern Ireland'),
(2199, 'Abigail', 'abigail', 'Abigail is a child author aged 7 from Dorset.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 7, 'Dorset'),
(2200, 'Eve', 'eve', 'Eve is a child author aged 9 from Rhondda.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 9, 'Rhondda'),
(2201, 'Cory', 'cory', 'Cory is a child author aged 3 from Cae Garw.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 3, 'Cae Garw'),
(2202, 'Ria', 'ria', 'Ria is a child author aged 8 from Hayes.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 8, 'Hayes'),
(2203, 'Rebekah', 'rebekah', 'Rebekah is a child author aged 9 from Lurgan Co.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 9, 'Lurgan Co'),
(2204, 'Leah', 'leah', 'Leah is a child author aged 11 from Rhondda.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 11, 'Rhondda'),
(2205, 'Ashton', 'ashton', 'Ashton is a child author aged 6 from Jarrow.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:51', '2025-05-10 21:06:51', 'child', 6, 'Jarrow'),
(2206, 'Danielle', 'danielle', 'Danielle is a child author aged 11 from Omagh.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 11, 'Omagh'),
(2207, 'Abbey-lei', 'abbey-lei', 'Abbey-lei is a child author aged 10 from Killyclogher.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 10, 'Killyclogher'),
(2208, 'Chris', 'chris', 'Chris is a child author aged 11 from Perth and Kinross.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 11, 'Perth and Kinross'),
(2209, 'Meranie', 'meranie', 'Meranie is a child author aged 9 from Luton.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 9, 'Luton'),
(2210, 'Alice', 'alice', 'Alice is a child author aged 7 from Scotland.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 7, 'Scotland'),
(2211, 'Lisa', 'lisa', 'Lisa is a child author aged 10 from Inverclyde.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 10, 'Inverclyde'),
(2212, 'Ella', 'ella', 'Ella is a child author aged 10 from Poole.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 10, 'Poole'),
(2213, 'Melissa', 'melissa', 'Melissa is a child author aged 10 from Paisley.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:52', '2025-05-10 21:06:52', 'child', 10, 'Paisley'),
(2214, 'Joshua', 'joshua', 'Joshua is a child author aged 8 from Spring Hill.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:53', '2025-05-10 21:06:53', 'child', 8, 'Spring Hill'),
(2215, 'Jane', 'jane', 'Jane is a child author aged 9 from East Dunbartonshire.', 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg', 1, '2025-05-10 21:06:53', '2025-05-10 21:06:53', 'child', 9, 'East Dunbartonshire');

-- --------------------------------------------------------

--
-- Stand-in structure for view `author_stats`
-- (See below for the actual view)
--
CREATE TABLE `author_stats` (
`author_type` varchar(20)
,`author_count` bigint
,`story_count` bigint
);

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
(1, 'MXxhZG1pbnwxNzQ2OTQ2ODY1fDdlYWVjNGRjODM2OWY3MzdhNDI5YzE0YTJkNzNmMmVifDE1NmJhMGExZDQ0MGI1MmNiYTgwYTY3NWM1NmQ4OTg1MzYzNzUwNjc1OTlhOWVhZGZlZWNlOTA5YTE3NmMzMmY=', '2025-05-12 08:01:05', '2025-04-26 08:05:16');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `content`, `excerpt`, `slug`, `is_published`, `author_id`, `cover_url`, `created_at`, `updated_at`, `featured_image`) VALUES
(1, 'Writing Tips for Children3', '<p><img src=\"https://api.storiesfromtheweb.org/../../uploads/optimized/681b7eb8d65141-03608815-Screenshot-2025-05-07-at-15-43-14-medium.webp\">Full blog post content…3</p>', 'Learn how to write for children...3', 'writing-tips-for-children3', 1, NULL, 'https://api.storiesfromtheweb.org/public/uploads/681b76edc6991-girl-likes-pink-children\'s-storybook.png', '2025-04-26 08:17:50', '2025-05-07 15:40:02', 'https://api.storiesfromtheweb.org/public/uploads/681b76edc6991-girl-likes-pink-children\'s-storybook.png'),
(2, 'The Importance of Reading', '<p>More blog content...</p>', 'Why reading matters...', 'importance-of-reading', 0, 2156, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-medium.webp', '2025-04-26 08:17:50', '2025-05-10 20:59:08', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-medium.webp'),
(4, 'test post2', '<p>sdfsdfsdf</p>', 'sdfsdfsdf', 'dsfadsfds', 1, NULL, 'https://api.storiesfromtheweb.org/public/uploads/681b76eef1360-boy-meets-vampire-storybook-illustration.png', '2025-04-26 15:22:55', '2025-05-07 16:53:57', 'https://api.storiesfromtheweb.org/public/uploads/681b76eef1360-boy-meets-vampire-storybook-illustration.png');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `directory_item_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `isbn13` varchar(20) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `page_count` int DEFAULT NULL,
  `age_range` varchar(50) DEFAULT NULL,
  `reading_level` varchar(50) DEFAULT NULL,
  `cover_url` varchar(255) DEFAULT NULL,
  `purchase_links` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `series` varchar(255) DEFAULT NULL,
  `publisher_id` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`directory_item_id`, `title`, `isbn`, `isbn13`, `author`, `publisher`, `publication_date`, `page_count`, `age_range`, `reading_level`, `cover_url`, `purchase_links`, `metadata`, `genre`, `series`, `publisher_id`) VALUES
(2028, 'The Graveyard Book', '', '978-0747594802', 'Neil Gaiman', 'Bloomsbury Publishing Plc', '2008-09-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0747594802/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0747594802\", \"google_books\": \"https://books.google.com/books?isbn=978-0747594802\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Graveyard-Book-By-Neil-Gaiman.jpeg\"}', '', '', NULL),
(2029, 'The Hobbit', '', '978-0007269709', 'J.R.R. Tolkien', 'HarperCollins Children\'s Books', '1937-01-01', NULL, '12 and up', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007269709/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007269709\", \"google_books\": \"https://books.google.com/books?isbn=978-0007269709\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Hobbit-by-J.R.R.-Tolkien.jpg\"}', '', 'Middle-earth Universe', NULL),
(2030, 'The Invention of Hugo Cabret', '', '978-1407103488', 'Brian Selznick', 'Scholastic Children\'s Books', '2007-07-01', NULL, '8-12', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1407103488/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407103488\", \"google_books\": \"https://books.google.com/books?isbn=978-1407103488\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Invention-of-Hugo-Cabret-by-Brian-Selznick.webp\"}', '', 'Unknown', NULL),
(2031, 'The Magnificent Moon Hare', '', '978-1405258753', 'Sue Monroe', 'Egmont UK', '2012-01-01', NULL, '', '', NULL, '{\"amazon\": \"https://www.amazon.com/dp/978-1405258753/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1405258753\", \"google_books\": \"https://books.google.com/books?isbn=978-1405258753\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Magnificent-Moon-Hare.jpg\"}', '', 'The Moon Hare Series Book', NULL),
(2032, 'The Midnight Gang', '', '978-0008164614', 'David Walliams', 'HarperCollins Children\'s Books', '2016-11-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/childrens-storybook-illustration-red-robot-rabbit-leafy-lane.png', '{\"amazon\": \"https://www.amazon.com/dp/978-0008164614/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0008164614\", \"google_books\": \"https://books.google.com/books?isbn=978-0008164614\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Midnight-Gang-by-David-Walliams.jpg\"}', '', 'Unknown', NULL),
(2033, 'The Money, Stan, Big Lauren and Me Book', '', '9781848122279', 'Joanna Nadin', 'Piccadilly Press', '2013-01-01', NULL, '', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/9781848122279/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/9781848122279\", \"google_books\": \"https://books.google.com/books?isbn=9781848122279\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Money-Stan-Big-Lauren-and-Me.jpg\"}', '', 'N/A', NULL),
(2034, 'The Mystery of the Whistling Caves', '', '978-1444003284', 'Helen Moss', 'Orion Children\'s Books', '2011-07-01', NULL, '7-10', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1444003284/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444003284\", \"google_books\": \"https://books.google.com/books?isbn=978-1444003284\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Mystery-of-the-Whistling-Caves-by-Helen-Moss.webp\"}', '', 'Adventure Island', NULL),
(2035, 'The Peppers and the International Magic Guys', '', '978-1847387741', 'Sian Pattenden', 'Simon & Schuster Children\'s Books', '2010-08-01', NULL, '7-10', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1847387741/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1847387741\", \"google_books\": \"https://books.google.com/books?isbn=978-1847387741\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden.jpg\"}', '', 'The Peppers', NULL),
(2036, 'The Spiderwick Chronicles: The Field Guide', '', '978-0689859366', 'Tony DiTerlizzi and Holly Black', 'Simon & Schuster Books for Young Readers', '2003-05-01', NULL, '7-10', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0689859366/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0689859366\", \"google_books\": \"https://books.google.com/books?isbn=978-0689859366\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black.jpeg\"}', '', 'The Spiderwick Chronicles', NULL),
(2037, 'The Whizz Pop Chocolate Shop', '', '978-1407129860', 'ghosts.', 'Marion Lloyd Books Text © Kate Saunders 2012', '2012-02-01', NULL, '', '', NULL, '{\"amazon\": \"https://www.amazon.com/dp/978-1407129860/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407129860\", \"google_books\": \"https://books.google.com/books?isbn=978-1407129860\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Whizz-Pop-Chocolate-Shop.jpg\"}', '', '', NULL),
(2038, 'The Worst Witch', '', '978-0141349592', 'Jill Murphy', 'Puffin Books, a division of Penguin Books Ltd', '1974-01-01', NULL, '8-12', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0141349592/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0141349592\", \"google_books\": \"https://books.google.com/books?isbn=978-0141349592\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Worst-Witch-by-Jill-Murphy.jpeg\"}', '', 'The Worst Witch', NULL),
(968, NULL, '', '978-1408851405', 'Natalie Babbitt', 'Bloomsbury Publishing Plc', '1975-01-01', NULL, '8-12', '', '/uploads/books/book_681f1a14adec6_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-1408851405/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1408851405\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Tuck-Everlasting-by-Natalie-Babbitt.jpeg\"}', '', '', 1),
(965, NULL, '', '978-1407129860', '', '', NULL, NULL, '', '', '/uploads/books/book_681f1a1459ebf_The_Whizz_Pop_Chocolate_Shop.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407129860/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407129860\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Whizz-Pop-Chocolate-Shop.jpg\"}', '', '', NULL),
(966, NULL, '', '978-0141349592', 'Jill Murphy', 'Puffin Books, a division of Penguin Books Ltd', '1974-01-01', NULL, '8-12', '', '/uploads/books/book_681f1a1475c7f_The_Worst_Witch_by_Jill_Murphy.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0141349592/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0141349592\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Worst-Witch-by-Jill-Murphy.jpeg\"}', '', '', 6),
(967, NULL, '', '978-0370332062', 'Matt Haig', 'The Bodley Head, an imprint of Random House Children\'s Books', '2012-02-01', NULL, '9-12', '', '/uploads/books/book_681f1a1491e10_To_Be_A_Cat_by_Matt_Haig.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0370332062/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0370332062\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"To-Be-A-Cat-by-Matt-Haig.jpeg\"}', '', '', 5),
(963, NULL, '', '978-1847387741', 'Sian Pattenden', 'Simon & Schuster Children\'s Books', '2010-08-01', NULL, '7-10', '', '/uploads/books/book_681f1a1421df6_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1847387741/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1847387741\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden.jpg\"}', '', '', 8),
(964, NULL, '', '978-0689859366', 'Tony DiTerlizzi and Holly Black', 'Simon & Schuster Books for Young Readers', '2003-05-01', NULL, '7-10', '', '/uploads/books/book_681f1a143df27_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0689859366/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0689859366\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black.jpeg\"}', '', '', 7),
(960, NULL, '', '978-0008164614', 'David Walliams', 'HarperCollins Children\'s Books', '2016-11-01', NULL, '9-12', '', '/uploads/books/book_681f1a13bfb0f_The_Midnight_Gang_by_David_Walliams.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0008164614/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0008164614\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Midnight-Gang-by-David-Walliams.jpg\"}', '', '', 3),
(962, NULL, '', '978-1444003284', 'Helen Moss', 'Orion Children\'s Books', '2011-07-01', NULL, '7-10', '', '/uploads/books/book_681f1a14058af_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1444003284/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444003284\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Mystery-of-the-Whistling-Caves-by-Helen-Moss.webp\"}', '', '', 9),
(958, NULL, '', '978-1407103488', 'Brian Selznick', 'Scholastic Children\'s Books', '2007-07-01', NULL, '8-12', '', '/uploads/books/book_681f1a1388586_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1407103488/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407103488\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Invention-of-Hugo-Cabret-by-Brian-Selznick.webp\"}', '', '', 10),
(959, NULL, '', '978-1405258753', 'Sue Monroe', '', NULL, NULL, '', '', '/uploads/books/book_681f1a13a3dc7_The_Magnificent_Moon_Hare.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1405258753/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1405258753\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Magnificent-Moon-Hare.jpg\"}', '', '', NULL),
(2040, 'Tuck Everlasting', '', '978-1408851405', 'Natalie Babbitt', 'Bloomsbury Publishing Plc', '1975-01-01', NULL, '8-12', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1408851405/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1408851405\", \"google_books\": \"https://books.google.com/books?isbn=978-1408851405\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Tuck-Everlasting-by-Natalie-Babbitt.jpeg\"}', '', '', NULL),
(956, NULL, '', '978-0747594802', 'Neil Gaiman', 'Bloomsbury Publishing Plc', '2008-09-01', NULL, '9-12', '', '/uploads/books/book_681f1a1350f87_The_Graveyard_Book_By_Neil_Gaiman.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0747594802/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0747594802\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Graveyard-Book-By-Neil-Gaiman.jpeg\"}', '', '', 1),
(952, NULL, '', '978-1407104065', '', 'Scholastic Children\'s Books', '2000-10-01', NULL, ' 12+', '', '/uploads/books/book_681f1a12d45de_The_Amber_Spyglass_by_Philip_Pullman.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407104065/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407104065\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Amber-Spyglass-by-Philip-Pullman.jpg\"}', '', '', 10),
(953, NULL, '', '978-0007279043', 'David Walliams', 'HarperCollins Children\'s Books', '2008-11-01', NULL, '9-12', '', '/uploads/books/book_681f1a12efd63_The_Boy_in_the_Dress_by_David_Walliams.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007279043/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007279043\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Boy-in-the-Dress-by-David-Walliams.jpg\"}', '', '', 3),
(954, NULL, '', '978-1407120697', 'Liz Pichon', 'Scholastic Children\'s Books', '2011-10-01', NULL, '9-12', '', '/uploads/books/book_681f1a131a80c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407120697/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407120697\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon.jpeg\"}', '', '', 10),
(950, NULL, '', '978-1444004786', 'Maudie Smith', 'Orion Children\'s Books', '2012-01-01', NULL, '8-12', '', '/uploads/books/book_681f1a129c6e7_Opal_Moonbaby_by_Maudie_Smith.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-1444004786/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444004786\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Opal-Moonbaby-by-Maudie-Smith.jpeg\"}', '', '', 9),
(951, NULL, '', '978-0007453542', 'David Walliams', 'HarperCollins Children\'s Books', '2012-09-01', NULL, '9-12', '', '/uploads/books/book_681f1a12b8d75_Ratburger_by_David_Walliams.gif', '{\"amazon\": \"https://www.amazon.com/dp/978-0007453542/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007453542\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Ratburger-by-David-Walliams.gif\"}', '', '', 3),
(949, NULL, '', '978-0007279067', 'David Walliams', 'HarperCollins Children\'s Books', '2009-10-01', NULL, '9-12', '', '/uploads/books/book_681f1a1280a95_Mr._Stink_by_David_Walliams.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007279067/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007279067\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Mr.-Stink-by-David-Walliams.jpeg\"}', '', '', 3),
(914, NULL, '', '978-0440414803', 'Louis Sachar', 'Yearling Books, an imprint of Random House Children\'s Books', '1998-08-01', NULL, '10+', '', '/uploads/books/book_681f1a09295b1_Holes_by_Louis_Sachar.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0440414803/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0440414803\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Holes-by-Louis-Sachar.jpg\"}', '', '', 2),
(943, NULL, '', '978-1407115273', 'Philip Reeve', 'Marion Lloyd Books, an imprint of Scholastic Ltd', '2012-09-01', NULL, 'Unknown', '', '/uploads/books/book_681f1a11cd643_Goblins_by_Philip_Reeve.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407115273/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407115273\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Goblins-by-Philip-Reeve.jpg\"}', '', '', 12),
(942, NULL, '', '978-0007371440', 'David Walliams', 'HarperCollins Children\'s Books', '2011-10-01', NULL, '9-12', '', '/uploads/books/book_681f1a11b1c54_Gangsta_Granny_by_David_Walliams.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007371440/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007371440\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Gangsta-Granny-by-David-Walliams.jpeg\"}', '', '', 3),
(910, NULL, '', '978-0007416851', 'Diana Wynne Jones', 'HarperCollins Children\'s Books', '2011-06-01', NULL, '8-12', '', '/uploads/books/book_681f1a08ad2a7_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007416851/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007416851\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Earwig-and-the-Witch-by-Diana-Wynne-Jones.jpg\"}', '', '', 3),
(945, NULL, '', '978-0440414803', 'Louis Sachar', 'Yearling Books, an imprint of Random House Children\'s Books', '1998-08-01', NULL, '10+', '', '/uploads/books/book_681f1a1210813_Holes_by_Louis_Sachar.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0440414803/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0440414803\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Holes-by-Louis-Sachar.jpg\"}', '', '', 2),
(937, NULL, '', '978-1408851405', 'Natalie Babbitt', 'Bloomsbury Publishing Plc', '1975-01-01', NULL, '8-12', '', '/uploads/books/book_681f1a0bc3557_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-1408851405/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1408851405\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Tuck-Everlasting-by-Natalie-Babbitt.jpeg\"}', '', '', 1),
(936, NULL, '', '978-0370332062', 'Matt Haig', 'The Bodley Head, an imprint of Random House Children\'s Books', '2012-02-01', NULL, '9-12', '', '/uploads/books/book_681f1a0ba78bb_To_Be_A_Cat_by_Matt_Haig.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0370332062/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0370332062\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"To-Be-A-Cat-by-Matt-Haig.jpeg\"}', '', '', 5),
(935, NULL, '', '978-0141349592', 'Jill Murphy', 'Puffin Books, a division of Penguin Books Ltd', '1974-01-01', NULL, '8-12', '', '/uploads/books/book_681f1a0b8b67d_The_Worst_Witch_by_Jill_Murphy.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0141349592/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0141349592\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Worst-Witch-by-Jill-Murphy.jpeg\"}', '', '', 6),
(934, NULL, '', '978-1407129860', '', '', NULL, NULL, '', '', '/uploads/books/book_681f1a0b6f56c_The_Whizz_Pop_Chocolate_Shop.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407129860/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407129860\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Whizz-Pop-Chocolate-Shop.jpg\"}', '', '', NULL),
(933, NULL, '', '978-0689859366', 'Tony DiTerlizzi and Holly Black', 'Simon & Schuster Books for Young Readers', '2003-05-01', NULL, '7-10', '', '/uploads/books/book_681f1a0b533b8_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0689859366/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0689859366\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black.jpeg\"}', '', '', 7),
(932, NULL, '', '978-1847387741', 'Sian Pattenden', 'Simon & Schuster Children\'s Books', '2010-08-01', NULL, '7-10', '', '/uploads/books/book_681f1a0b37015_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1847387741/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1847387741\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden.jpg\"}', '', '', 8),
(938, NULL, '', '978-1847802255', 'Wendy Meddour', 'Frances Lincoln', '2012-02-01', NULL, '7-10', '', '/uploads/books/book_681f1a1143dd0_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1847802255/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1847802255\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"A-Hen-in-the-Wardrobe-by-Wendy-Meddour.jpg\"}', '', '', 4),
(931, NULL, '', '978-1444003284', 'Helen Moss', 'Orion Children\'s Books', '2011-07-01', NULL, '7-10', '', '/uploads/books/book_681f1a0b1ad7e_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1444003284/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444003284\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Mystery-of-the-Whistling-Caves-by-Helen-Moss.webp\"}', '', '', 9),
(929, NULL, '', '978-0008164614', 'David Walliams', 'HarperCollins Children\'s Books', '2016-11-01', NULL, '9-12', '', '/uploads/books/book_681f1a0ad7bb3_The_Midnight_Gang_by_David_Walliams.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0008164614/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0008164614\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Midnight-Gang-by-David-Walliams.jpg\"}', '', '', 3),
(928, NULL, '', '978-1405258753', 'Sue Monroe', '', NULL, NULL, '', '', '/uploads/books/book_681f1a0abb803_The_Magnificent_Moon_Hare.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1405258753/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1405258753\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Magnificent-Moon-Hare.jpg\"}', '', '', NULL),
(939, NULL, '', '', 'Neil Gaiman', '', NULL, NULL, '9-12', '', '/uploads/books/book_681f1a115ef06_Coraline_by_Neil_Gaiman.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Coraline-by-Neil-Gaiman.jpeg\"}', '', '', NULL),
(940, NULL, '', '978-0007453573', 'David Walliams', 'HarperCollins Children\'s Books', '2013-09-01', NULL, '9-12', '', '/uploads/books/book_681f1a117a618_Demon_Dentist_by_David_Walliams.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007453573/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007453573\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Demon-Dentist-by-David-Walliams.jpeg\"}', '', '', 3),
(927, NULL, '', '978-1407103488', 'Brian Selznick', 'Scholastic Children\'s Books', '2007-07-01', NULL, '8-12', '', '/uploads/books/book_681f1a0a9fc9b_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1407103488/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407103488\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Invention-of-Hugo-Cabret-by-Brian-Selznick.webp\"}', '', '', 10),
(941, NULL, '', '978-0007416851', 'Diana Wynne Jones', 'HarperCollins Children\'s Books', '2011-06-01', NULL, '8-12', '', '/uploads/books/book_681f1a1195dad_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007416851/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007416851\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Earwig-and-the-Witch-by-Diana-Wynne-Jones.jpg\"}', '', '', 3),
(925, NULL, '', '978-0747594802', 'Neil Gaiman', 'Bloomsbury Publishing Plc', '2008-09-01', NULL, '9-12', '', '/uploads/books/book_681f1a0a6870a_The_Graveyard_Book_By_Neil_Gaiman.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0747594802/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0747594802\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Graveyard-Book-By-Neil-Gaiman.jpeg\"}', '', '', 1),
(923, NULL, '', '978-1407120697', 'Liz Pichon', 'Scholastic Children\'s Books', '2011-10-01', NULL, '9-12', '', '/uploads/books/book_681f1a0a31324_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407120697/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407120697\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon.jpeg\"}', '', '', 10),
(922, NULL, '', '978-0007279043', 'David Walliams', 'HarperCollins Children\'s Books', '2008-11-01', NULL, '9-12', '', '/uploads/books/book_681f1a0a15931_The_Boy_in_the_Dress_by_David_Walliams.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007279043/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007279043\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Boy-in-the-Dress-by-David-Walliams.jpg\"}', '', '', 3),
(921, NULL, '', '978-1407104065', '', 'Scholastic Children\'s Books', '2000-10-01', NULL, ' 12+', '', '/uploads/books/book_681f1a09eddcb_The_Amber_Spyglass_by_Philip_Pullman.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407104065/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407104065\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Amber-Spyglass-by-Philip-Pullman.jpg\"}', '', '', 10),
(920, NULL, '', '978-0007453542', 'David Walliams', 'HarperCollins Children\'s Books', '2012-09-01', NULL, '9-12', '', '/uploads/books/book_681f1a09d153e_Ratburger_by_David_Walliams.gif', '{\"amazon\": \"https://www.amazon.com/dp/978-0007453542/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007453542\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Ratburger-by-David-Walliams.gif\"}', '', '', 3),
(919, NULL, '', '978-1444004786', 'Maudie Smith', 'Orion Children\'s Books', '2012-01-01', NULL, '8-12', '', '/uploads/books/book_681f1a09b550d_Opal_Moonbaby_by_Maudie_Smith.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-1444004786/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444004786\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Opal-Moonbaby-by-Maudie-Smith.jpeg\"}', '', '', 9),
(918, NULL, '', '978-0007279067', 'David Walliams', 'HarperCollins Children\'s Books', '2009-10-01', NULL, '9-12', '', '/uploads/books/book_681f1a0999ad2_Mr._Stink_by_David_Walliams.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007279067/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007279067\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Mr.-Stink-by-David-Walliams.jpeg\"}', '', '', 3),
(2039, 'To Be A Cat', '', '978-0370332062', 'Matt Haig', 'The Bodley Head, an imprint of Random House Children\'s Books', '2012-02-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0370332062/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0370332062\", \"google_books\": \"https://books.google.com/books?isbn=978-0370332062\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"To-Be-A-Cat-by-Matt-Haig.jpeg\"}', '', 'Unknown', NULL),
(911, NULL, '', '978-0007371440', 'David Walliams', 'HarperCollins Children\'s Books', '2011-10-01', NULL, '9-12', '', '/uploads/books/book_681f1a08c9f0d_Gangsta_Granny_by_David_Walliams.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007371440/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007371440\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Gangsta-Granny-by-David-Walliams.jpeg\"}', '', '', 3),
(912, NULL, '', '978-1407115273', 'Philip Reeve', 'Marion Lloyd Books, an imprint of Scholastic Ltd', '2012-09-01', NULL, 'Unknown', '', '/uploads/books/book_681f1a08e5954_Goblins_by_Philip_Reeve.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1407115273/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407115273\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Goblins-by-Philip-Reeve.jpg\"}', '', '', 12),
(908, NULL, '', '', 'Neil Gaiman', '', NULL, NULL, '9-12', '', '/uploads/books/book_681f1a0874edd_Coraline_by_Neil_Gaiman.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Coraline-by-Neil-Gaiman.jpeg\"}', '', '', NULL),
(909, NULL, '', '978-0007453573', 'David Walliams', 'HarperCollins Children\'s Books', '2013-09-01', NULL, '9-12', '', '/uploads/books/book_681f1a08919f5_Demon_Dentist_by_David_Walliams.jpeg', '{\"amazon\": \"https://www.amazon.com/dp/978-0007453573/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007453573\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Demon-Dentist-by-David-Walliams.jpeg\"}', '', '', 3),
(907, NULL, '', '978-1847802255', 'Wendy Meddour', 'Frances Lincoln', '2012-02-01', NULL, '7-10', '', '/uploads/books/book_681f1a085853b_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', '{\"amazon\": \"https://www.amazon.com/dp/978-1847802255/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1847802255\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"A-Hen-in-the-Wardrobe-by-Wendy-Meddour.jpg\"}', '', '', 4),
(2026, 'The Brilliant World of Tom Gates', '', '978-1407120697', 'Liz Pichon', 'Scholastic Children\'s Books', '2011-10-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1407120697/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407120697\", \"google_books\": \"https://books.google.com/books?isbn=978-1407120697\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon.jpeg\"}', '', 'Tom Gates', NULL),
(2027, 'The Chronicles of Narnia: The Lion', '', '978-0007115617', 'C.S. Lewis', 'Harper Collins Children’s Books', '1950-10-01', NULL, '8-12', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007115617/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007115617\", \"google_books\": \"https://books.google.com/books?isbn=978-0007115617\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe.jpg\"}', '', 'The Chronicles of Narnia', NULL),
(2024, 'The Amber Spyglass', '', '978-1407104065', 'Scholastic Children\'s Books', 'Scholastic Children\'s Books', '2000-10-01', NULL, ' 12+', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1407104065/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407104065\", \"google_books\": \"https://books.google.com/books?isbn=978-1407104065\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Amber-Spyglass-by-Philip-Pullman.jpg\"}', '', 'His Dark Materials', NULL),
(2025, 'The Boy in the Dress', '', '978-0007279043', 'David Walliams', 'HarperCollins Children\'s Books', '2008-11-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007279043/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007279043\", \"google_books\": \"https://books.google.com/books?isbn=978-0007279043\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Boy-in-the-Dress-by-David-Walliams.jpg\"}', '', 'Unknown', NULL),
(2023, 'Ratburger', '', '978-0007453542', 'David Walliams', 'HarperCollins Children\'s Books', '2012-09-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007453542/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007453542\", \"google_books\": \"https://books.google.com/books?isbn=978-0007453542\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Ratburger-by-David-Walliams.gif\"}', '', 'Unknown', NULL),
(2020, 'Moon Pie', '', '9781446453322', 'Simon Mason', 'David Fickling Books, an imprint of Random House', '2011-01-01', NULL, '', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/9781446453322/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/9781446453322\", \"google_books\": \"https://books.google.com/books?isbn=9781446453322\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"Moon-Pie-by-Simon-Mason.jpeg\"}', '', 'for young readers. He studied English at Lady Margaret Hall, Oxford, and currently splits his time between writing at home and a part-time editorial position with David Fickling Books, an imprint of Random House and publisher of his 2011 children\'s nov...', NULL),
(2021, 'Mr. Stink', '', '978-0007279067', 'David Walliams', 'HarperCollins Children\'s Books', '2009-10-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007279067/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007279067\", \"google_books\": \"https://books.google.com/books?isbn=978-0007279067\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Mr.-Stink-by-David-Walliams.jpeg\"}', '', 'Unknown', NULL),
(2022, 'Opal Moonbaby', '', '978-1444004786', 'Maudie Smith', 'Orion Children\'s Books', '2012-01-01', NULL, '8-12', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1444004786/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444004786\", \"google_books\": \"https://books.google.com/books?isbn=978-1444004786\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Opal-Moonbaby-by-Maudie-Smith.jpeg\"}', '', 'Opal Moonbaby', NULL),
(2019, 'Little Manfred Book', '', '978-0007339662', 'Michael Morpurgo', 'HarperCollins Children\'s Books', '2011-06-01', NULL, '', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007339662/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007339662\", \"google_books\": \"https://books.google.com/books?isbn=978-0007339662\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Little-Manfred-by-Michael-Morpurgo.jpeg\"}', '', '', NULL),
(2018, 'Kidnap in the Caribbean', '', '978-1444003273', 'Lauren St John', ' Orion Children\'s Books ', '2011-01-01', NULL, '', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1444003273/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1444003273\", \"google_books\": \"https://books.google.com/books?isbn=978-1444003273\"}', '{\"date\": \"2023-07-19\", \"coverImage\": \"Kidnap-oin-the-Caribbean.jpeg\"}', '', 'Laura Marlin Mysteries', NULL),
(2017, 'Holes', '', '978-0440414803', 'Louis Sachar', 'Yearling Books, an imprint of Random House Children\'s Books', '1998-08-01', NULL, '10+', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0440414803/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0440414803\", \"google_books\": \"https://books.google.com/books?isbn=978-0440414803\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Holes-by-Louis-Sachar.jpg\"}', '', '', NULL),
(2014, 'Gangsta Granny', '', '978-0007371440', 'David Walliams', 'HarperCollins Children\'s Books', '2011-10-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007371440/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007371440\", \"google_books\": \"https://books.google.com/books?isbn=978-0007371440\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Gangsta-Granny-by-David-Walliams.jpeg\"}', '', 'Unknown', NULL),
(2015, 'Goblins', '', '978-1407115273', 'Philip Reeve', 'Marion Lloyd Books, an imprint of Scholastic Ltd', '2012-09-01', NULL, 'Unknown', '', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1407115273/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1407115273\", \"google_books\": \"https://books.google.com/books?isbn=978-1407115273\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Goblins-by-Philip-Reeve.jpg\"}', '', 'Unknown', NULL),
(2016, 'Harry Potter and the Philosopher’s Stone', '', '978-1408855656', 'J.K. Rowling', 'Bloomsbury Publishing Plc', '1997-06-01', NULL, '9+', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1408855656/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1408855656\", \"google_books\": \"https://books.google.com/books?isbn=978-1408855656\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Harry-Potter-and-the-Philosophers-Stone.jpeg\"}', '', 'Harry Potter series', NULL),
(2013, 'Earwig and the Witch', '', '978-0007416851', 'Diana Wynne Jones', 'HarperCollins Children\'s Books', '2011-06-01', NULL, '8-12', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007416851/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007416851\", \"google_books\": \"https://books.google.com/books?isbn=978-0007416851\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Earwig-and-the-Witch-by-Diana-Wynne-Jones.jpg\"}', '', 'Unknown', NULL),
(2012, 'Demon Dentist', '', '978-0007453573', 'David Walliams', 'HarperCollins Children\'s Books', '2013-09-01', NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-0007453573/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-0007453573\", \"google_books\": \"https://books.google.com/books?isbn=978-0007453573\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"Demon-Dentist-by-David-Walliams.jpeg\"}', '', 'Unknown', NULL),
(2010, 'A Hen in the Wardrobe', '', '978-1847802255', 'Wendy Meddour', 'Frances Lincoln', '2012-02-01', NULL, '7-10', 'chapter-book', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-medium.webp', '{\"amazon\": \"https://www.amazon.com/dp/978-1847802255/\", \"goodreads\": \"https://www.goodreads.com/book/isbn/978-1847802255\", \"google_books\": \"https://books.google.com/books?isbn=978-1847802255\"}', '{\"date\": \"2023-07-18\", \"coverImage\": \"A-Hen-in-the-Wardrobe-by-Wendy-Meddour.jpg\"}', '', 'Unknown', NULL),
(2011, 'Coraline', '', '', 'Neil Gaiman', '', NULL, NULL, '9-12', 'middle-grade', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-medium.webp', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Coraline-by-Neil-Gaiman.jpeg\"}', '', 'Unknown', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `books_backup`
--

CREATE TABLE `books_backup` (
  `directory_item_id` int NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `isbn13` varchar(20) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `page_count` int DEFAULT NULL,
  `age_range` varchar(50) DEFAULT NULL,
  `reading_level` varchar(50) DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `purchase_links` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `series` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `books_backup`
--

INSERT INTO `books_backup` (`directory_item_id`, `isbn`, `isbn13`, `author`, `publisher`, `publication_date`, `page_count`, `age_range`, `reading_level`, `cover_image_url`, `purchase_links`, `metadata`, `genre`, `series`) VALUES
(734, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b17f073_Ratburger_by_David_Walliams.gif', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Ratburger-by-David-Walliams.gif\"}', '', ''),
(735, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b19a328_The_Amber_Spyglass_by_Philip_Pullman.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Amber-Spyglass-by-Philip-Pullman.jpg\"}', '', ''),
(736, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b1b567b_The_Boy_in_the_Dress_by_David_Walliams.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Boy-in-the-Dress-by-David-Walliams.jpg\"}', '', ''),
(737, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b1cff0c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon.jpeg\"}', '', ''),
(738, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b1ea7e8_The_Chronicles_of_Narnia_The_Lion_the_Witch_and_the_Wardrobe.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe.jpg\"}', '', ''),
(751, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b361c7f_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Tuck-Everlasting-by-Natalie-Babbitt.jpeg\"}', '', ''),
(750, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b346a08_To_Be_A_Cat_by_Matt_Haig.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"To-Be-A-Cat-by-Matt-Haig.jpeg\"}', '', ''),
(749, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b327cab_The_Worst_Witch_by_Jill_Murphy.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Worst-Witch-by-Jill-Murphy.jpeg\"}', '', ''),
(748, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b30cd4f_The_Whizz_Pop_Chocolate_Shop.jpg', '[]', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Whizz-Pop-Chocolate-Shop.jpg\"}', '', ''),
(747, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b2e62d7_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black.jpeg\"}', '', ''),
(746, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b2cb2a9_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden.jpg\"}', '', ''),
(744, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b295977_The_Money_Stan_Big_Lauren_and_Me.jpg', '[]', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Money-Stan-Big-Lauren-and-Me.jpg\"}', '', ''),
(745, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b2b0400_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Mystery-of-the-Whistling-Caves-by-Helen-Moss.webp\"}', '', ''),
(743, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b27aee4_The_Midnight_Gang_by_David_Walliams.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Midnight-Gang-by-David-Walliams.jpg\"}', '', ''),
(742, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b2604b7_The_Magnificent_Moon_Hare.jpg', '[]', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Magnificent-Moon-Hare.jpg\"}', '', ''),
(740, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b22b7e5_The_Hobbit_by_J.R.R._Tolkien.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Hobbit-by-J.R.R.-Tolkien.jpg\"}', '', ''),
(741, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b245dc5_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"The-Invention-of-Hugo-Cabret-by-Brian-Selznick.webp\"}', '', ''),
(739, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b210f0e_The_Graveyard_Book_By_Neil_Gaiman.jpeg', '[]', '{\"date\": \"2023-07-19\", \"coverImage\": \"The-Graveyard-Book-By-Neil-Gaiman.jpeg\"}', '', ''),
(721, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b00e307_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"A-Hen-in-the-Wardrobe-by-Wendy-Meddour.jpg\"}', '', ''),
(722, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b029e80_Coraline_by_Neil_Gaiman.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Coraline-by-Neil-Gaiman.jpeg\"}', '', ''),
(723, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b045cbd_Demon_Dentist_by_David_Walliams.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Demon-Dentist-by-David-Walliams.jpeg\"}', '', ''),
(724, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b06137c_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Earwig-and-the-Witch-by-Diana-Wynne-Jones.jpg\"}', '', ''),
(725, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b07cb19_Gangsta_Granny_by_David_Walliams.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Gangsta-Granny-by-David-Walliams.jpeg\"}', '', ''),
(726, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b098063_Goblins_by_Philip_Reeve.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Goblins-by-Philip-Reeve.jpg\"}', '', ''),
(727, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b0b384c_Harry_Potter_and_the_Philosophers_Stone.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Harry-Potter-and-the-Philosophers-Stone.jpeg\"}', '', ''),
(728, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b0cef8f_Holes_by_Louis_Sachar.jpg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Holes-by-Louis-Sachar.jpg\"}', '', ''),
(729, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b0ea144_Kidnap_oin_the_Caribbean.jpeg', '[]', '{\"date\": \"2023-07-19\", \"coverImage\": \"Kidnap-oin-the-Caribbean.jpeg\"}', '', ''),
(730, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b110d5c_Little_Manfred_by_Michael_Morpurgo.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Little-Manfred-by-Michael-Morpurgo.jpeg\"}', '', ''),
(731, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b12c603_Moon_Pie_by_Simon_Mason.jpeg', '[]', '{\"date\": \"2023-07-19\", \"coverImage\": \"Moon-Pie-by-Simon-Mason.jpeg\"}', '', ''),
(732, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b147f02_Mr._Stink_by_David_Walliams.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Mr.-Stink-by-David-Walliams.jpeg\"}', '', ''),
(733, '', '', '', '', NULL, NULL, '', '', '/uploads/books/book_681e34b163720_Opal_Moonbaby_by_Maudie_Smith.jpeg', '[]', '{\"date\": \"2023-07-18\", \"coverImage\": \"Opal-Moonbaby-by-Maudie-Smith.jpeg\"}', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `book_authors`
--

CREATE TABLE `book_authors` (
  `id` int NOT NULL,
  `directory_item_id` int NOT NULL,
  `author_id` int NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'author',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `book_authors`
--

INSERT INTO `book_authors` (`id`, `directory_item_id`, `author_id`, `role`, `created_at`, `updated_at`) VALUES
(1, 40, 977, 'author', '2025-05-09 13:22:58', '2025-05-09 13:22:58'),
(2, 45, 986, 'author', '2025-05-09 13:22:59', '2025-05-09 13:22:59'),
(3, 47, 989, 'author', '2025-05-09 13:22:59', '2025-05-09 13:22:59'),
(4, 49, 990, 'author', '2025-05-09 13:22:59', '2025-05-09 13:22:59'),
(5, 56, 1003, 'author', '2025-05-09 13:23:00', '2025-05-09 13:23:00'),
(6, 60, 1009, 'author', '2025-05-09 13:23:00', '2025-05-09 13:23:00'),
(7, 66, 1018, 'author', '2025-05-09 13:23:01', '2025-05-09 13:23:01'),
(8, 71, 977, 'author', '2025-05-09 13:24:52', '2025-05-09 13:24:52'),
(9, 76, 986, 'author', '2025-05-09 13:24:52', '2025-05-09 13:24:52'),
(10, 78, 989, 'author', '2025-05-09 13:24:52', '2025-05-09 13:24:52'),
(11, 80, 990, 'author', '2025-05-09 13:24:53', '2025-05-09 13:24:53'),
(12, 87, 1003, 'author', '2025-05-09 13:24:53', '2025-05-09 13:24:53'),
(13, 91, 1009, 'author', '2025-05-09 13:24:54', '2025-05-09 13:24:54'),
(14, 97, 1018, 'author', '2025-05-09 13:24:54', '2025-05-09 13:24:54'),
(15, 102, 977, 'author', '2025-05-09 13:26:21', '2025-05-09 13:26:21'),
(16, 107, 986, 'author', '2025-05-09 13:26:21', '2025-05-09 13:26:21'),
(17, 109, 989, 'author', '2025-05-09 13:26:22', '2025-05-09 13:26:22'),
(18, 111, 990, 'author', '2025-05-09 13:26:22', '2025-05-09 13:26:22'),
(19, 118, 1003, 'author', '2025-05-09 13:26:23', '2025-05-09 13:26:23'),
(20, 122, 1009, 'author', '2025-05-09 13:26:23', '2025-05-09 13:26:23'),
(21, 128, 1018, 'author', '2025-05-09 13:26:24', '2025-05-09 13:26:24'),
(22, 133, 977, 'author', '2025-05-09 13:30:45', '2025-05-09 13:30:45'),
(23, 138, 986, 'author', '2025-05-09 13:30:46', '2025-05-09 13:30:46'),
(24, 140, 989, 'author', '2025-05-09 13:30:46', '2025-05-09 13:30:46'),
(25, 142, 990, 'author', '2025-05-09 13:30:46', '2025-05-09 13:30:46'),
(26, 149, 1003, 'author', '2025-05-09 13:30:47', '2025-05-09 13:30:47'),
(27, 153, 1009, 'author', '2025-05-09 13:30:47', '2025-05-09 13:30:47'),
(28, 159, 1018, 'author', '2025-05-09 13:30:48', '2025-05-09 13:30:48'),
(29, 164, 977, 'author', '2025-05-09 13:41:19', '2025-05-09 13:41:19'),
(30, 169, 986, 'author', '2025-05-09 13:41:20', '2025-05-09 13:41:20'),
(31, 171, 989, 'author', '2025-05-09 13:41:20', '2025-05-09 13:41:20'),
(32, 173, 990, 'author', '2025-05-09 13:41:20', '2025-05-09 13:41:20'),
(33, 180, 1003, 'author', '2025-05-09 13:41:21', '2025-05-09 13:41:21'),
(34, 184, 1009, 'author', '2025-05-09 13:41:21', '2025-05-09 13:41:21'),
(35, 190, 1018, 'author', '2025-05-09 13:41:22', '2025-05-09 13:41:22'),
(36, 195, 977, 'author', '2025-05-09 13:43:51', '2025-05-09 13:43:51'),
(37, 200, 986, 'author', '2025-05-09 13:43:52', '2025-05-09 13:43:52'),
(38, 202, 989, 'author', '2025-05-09 13:43:52', '2025-05-09 13:43:52'),
(39, 204, 990, 'author', '2025-05-09 13:43:52', '2025-05-09 13:43:52'),
(40, 211, 1003, 'author', '2025-05-09 13:43:53', '2025-05-09 13:43:53'),
(41, 215, 1009, 'author', '2025-05-09 13:43:54', '2025-05-09 13:43:54'),
(42, 221, 1018, 'author', '2025-05-09 13:43:54', '2025-05-09 13:43:54'),
(43, 226, 977, 'author', '2025-05-09 13:45:39', '2025-05-09 13:45:39'),
(44, 231, 986, 'author', '2025-05-09 13:45:39', '2025-05-09 13:45:39'),
(45, 233, 989, 'author', '2025-05-09 13:45:40', '2025-05-09 13:45:40'),
(46, 235, 990, 'author', '2025-05-09 13:45:40', '2025-05-09 13:45:40'),
(47, 242, 1003, 'author', '2025-05-09 13:45:41', '2025-05-09 13:45:41'),
(48, 246, 1009, 'author', '2025-05-09 13:45:41', '2025-05-09 13:45:41'),
(49, 252, 1018, 'author', '2025-05-09 13:45:42', '2025-05-09 13:45:42'),
(50, 256, 1284, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(51, 256, 1283, 'publisher', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(52, 257, 977, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(53, 258, 1286, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(54, 258, 1285, 'publisher', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(55, 259, 1287, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(56, 259, 1285, 'publisher', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(57, 260, 1286, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(58, 260, 1285, 'publisher', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(59, 261, 1289, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(60, 261, 1288, 'publisher', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(61, 262, 986, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(62, 263, 1291, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(63, 263, 1290, 'publisher', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(64, 264, 989, 'author', '2025-05-09 13:48:02', '2025-05-09 13:48:02'),
(65, 266, 990, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(66, 267, 1286, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(67, 267, 1285, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(68, 268, 1293, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(69, 268, 1292, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(70, 269, 1286, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(71, 269, 1285, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(72, 270, 1295, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(73, 270, 1294, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(74, 271, 1286, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(75, 271, 1285, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(76, 272, 1296, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(77, 272, 1294, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(78, 273, 1003, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(79, 274, 977, 'author', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(80, 274, 1297, 'publisher', '2025-05-09 13:48:03', '2025-05-09 13:48:03'),
(81, 275, 1298, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(82, 275, 1285, 'publisher', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(83, 276, 1299, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(84, 276, 1294, 'publisher', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(85, 277, 1009, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(86, 278, 1286, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(87, 278, 1285, 'publisher', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(88, 280, 1300, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(89, 280, 1292, 'publisher', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(90, 281, 1302, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(91, 281, 1301, 'publisher', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(92, 282, 1304, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(93, 282, 1303, 'publisher', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(94, 283, 1018, 'author', '2025-05-09 13:48:04', '2025-05-09 13:48:04'),
(95, 284, 1306, 'author', '2025-05-09 13:48:05', '2025-05-09 13:48:05'),
(96, 284, 1305, 'publisher', '2025-05-09 13:48:05', '2025-05-09 13:48:05'),
(97, 285, 1308, 'author', '2025-05-09 13:48:05', '2025-05-09 13:48:05'),
(98, 285, 1307, 'publisher', '2025-05-09 13:48:05', '2025-05-09 13:48:05'),
(99, 286, 1309, 'author', '2025-05-09 13:48:05', '2025-05-09 13:48:05'),
(100, 286, 1297, 'publisher', '2025-05-09 13:48:05', '2025-05-09 13:48:05'),
(101, 287, 1284, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(102, 287, 1283, 'publisher', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(103, 288, 977, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(104, 289, 1286, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(105, 289, 1285, 'publisher', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(106, 290, 1287, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(107, 290, 1285, 'publisher', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(108, 291, 1286, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(109, 291, 1285, 'publisher', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(110, 292, 1289, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(111, 292, 1288, 'publisher', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(112, 293, 986, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(113, 294, 1291, 'author', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(114, 294, 1290, 'publisher', '2025-05-09 13:56:12', '2025-05-09 13:56:12'),
(115, 295, 989, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(116, 297, 990, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(117, 298, 1286, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(118, 298, 1285, 'publisher', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(119, 299, 1293, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(120, 299, 1292, 'publisher', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(121, 300, 1286, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(122, 300, 1285, 'publisher', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(123, 301, 1295, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(124, 301, 1294, 'publisher', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(125, 302, 1286, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(126, 302, 1285, 'publisher', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(127, 303, 1296, 'author', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(128, 303, 1294, 'publisher', '2025-05-09 13:56:13', '2025-05-09 13:56:13'),
(129, 304, 1003, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(130, 305, 977, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(131, 305, 1297, 'publisher', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(132, 306, 1298, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(133, 306, 1285, 'publisher', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(134, 307, 1299, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(135, 307, 1294, 'publisher', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(136, 308, 1009, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(137, 309, 1286, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(138, 309, 1285, 'publisher', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(139, 311, 1300, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(140, 311, 1292, 'publisher', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(141, 312, 1302, 'author', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(142, 312, 1301, 'publisher', '2025-05-09 13:56:14', '2025-05-09 13:56:14'),
(143, 313, 1304, 'author', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(144, 313, 1303, 'publisher', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(145, 314, 1018, 'author', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(146, 315, 1306, 'author', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(147, 315, 1305, 'publisher', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(148, 316, 1308, 'author', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(149, 316, 1307, 'publisher', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(150, 317, 1309, 'author', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(151, 317, 1297, 'publisher', '2025-05-09 13:56:15', '2025-05-09 13:56:15'),
(152, 318, 1284, 'author', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(153, 318, 1283, 'publisher', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(154, 319, 977, 'author', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(155, 320, 1286, 'author', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(156, 320, 1285, 'publisher', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(157, 321, 1287, 'author', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(158, 321, 1285, 'publisher', '2025-05-09 14:14:03', '2025-05-09 14:14:03'),
(159, 322, 1286, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(160, 322, 1285, 'publisher', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(161, 323, 1289, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(162, 323, 1288, 'publisher', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(163, 324, 986, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(164, 325, 1291, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(165, 325, 1290, 'publisher', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(166, 326, 989, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(167, 328, 990, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(168, 329, 1286, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(169, 329, 1285, 'publisher', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(170, 330, 1293, 'author', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(171, 330, 1292, 'publisher', '2025-05-09 14:14:04', '2025-05-09 14:14:04'),
(172, 331, 1286, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(173, 331, 1285, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(174, 332, 1295, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(175, 332, 1294, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(176, 333, 1286, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(177, 333, 1285, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(178, 334, 1296, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(179, 334, 1294, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(180, 335, 1003, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(181, 336, 977, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(182, 336, 1297, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(183, 337, 1298, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(184, 337, 1285, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(185, 338, 1299, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(186, 338, 1294, 'publisher', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(187, 339, 1009, 'author', '2025-05-09 14:14:05', '2025-05-09 14:14:05'),
(188, 340, 1286, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(189, 340, 1285, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(190, 342, 1300, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(191, 342, 1292, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(192, 343, 1302, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(193, 343, 1301, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(194, 344, 1304, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(195, 344, 1303, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(196, 345, 1018, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(197, 346, 1306, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(198, 346, 1305, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(199, 347, 1308, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(200, 347, 1307, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(201, 348, 1309, 'author', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(202, 348, 1297, 'publisher', '2025-05-09 14:14:06', '2025-05-09 14:14:06'),
(296, 406, 1304, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(295, 405, 1301, 'publisher', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(294, 405, 1302, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(293, 404, 1292, 'publisher', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(292, 404, 1300, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(291, 402, 1285, 'publisher', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(290, 402, 1286, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(289, 401, 1009, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(288, 400, 1294, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(287, 400, 1299, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(286, 399, 1285, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(285, 399, 1298, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(284, 398, 1297, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(283, 398, 977, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(282, 397, 1003, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(281, 396, 1294, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(280, 396, 1296, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(279, 395, 1285, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(278, 395, 1286, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(277, 394, 1294, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(276, 394, 1295, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(275, 393, 1285, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(274, 393, 1286, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(273, 392, 1292, 'publisher', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(272, 392, 1293, 'author', '2025-05-09 14:56:01', '2025-05-09 14:56:01'),
(271, 391, 1285, 'publisher', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(270, 391, 1286, 'author', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(269, 390, 990, 'author', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(268, 388, 989, 'author', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(267, 387, 1290, 'publisher', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(266, 387, 1291, 'author', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(265, 386, 986, 'author', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(264, 385, 1288, 'publisher', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(263, 385, 1289, 'author', '2025-05-09 14:56:00', '2025-05-09 14:56:00'),
(262, 384, 1285, 'publisher', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(261, 384, 1286, 'author', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(260, 383, 1285, 'publisher', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(259, 383, 1287, 'author', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(258, 382, 1285, 'publisher', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(257, 382, 1286, 'author', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(256, 381, 977, 'author', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(255, 380, 1283, 'publisher', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(254, 380, 1284, 'author', '2025-05-09 14:55:59', '2025-05-09 14:55:59'),
(297, 406, 1303, 'publisher', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(298, 407, 1018, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(299, 408, 1306, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(300, 408, 1305, 'publisher', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(301, 409, 1308, 'author', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(302, 409, 1307, 'publisher', '2025-05-09 14:56:02', '2025-05-09 14:56:02'),
(303, 410, 1309, 'author', '2025-05-09 14:56:03', '2025-05-09 14:56:03'),
(304, 410, 1297, 'publisher', '2025-05-09 14:56:03', '2025-05-09 14:56:03'),
(305, 690, 1760, 'author', '2025-05-09 17:00:21', '2025-05-09 17:00:21'),
(306, 691, 1760, 'author', '2025-05-09 17:00:21', '2025-05-09 17:00:21'),
(307, 692, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(308, 693, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(309, 694, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(310, 695, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(311, 696, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(312, 697, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(313, 698, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(314, 699, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(315, 700, 1760, 'author', '2025-05-09 17:00:22', '2025-05-09 17:00:22'),
(316, 701, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(317, 702, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(318, 703, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(319, 704, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(320, 705, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(321, 706, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(322, 707, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(323, 708, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(324, 709, 1760, 'author', '2025-05-09 17:00:23', '2025-05-09 17:00:23'),
(325, 710, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(326, 711, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(327, 712, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(328, 713, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(329, 714, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(330, 715, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(331, 716, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(332, 717, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(333, 718, 1760, 'author', '2025-05-09 17:00:24', '2025-05-09 17:00:24'),
(334, 719, 1760, 'author', '2025-05-09 17:00:25', '2025-05-09 17:00:25'),
(335, 720, 1760, 'author', '2025-05-09 17:00:25', '2025-05-09 17:00:25'),
(336, 721, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(337, 722, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(338, 723, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(339, 724, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(340, 725, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(341, 726, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(342, 727, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(343, 728, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(344, 729, 1760, 'author', '2025-05-09 17:00:32', '2025-05-09 17:00:32'),
(345, 730, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(346, 731, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(347, 732, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(348, 733, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(349, 734, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(350, 735, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(351, 736, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(352, 737, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(353, 738, 1760, 'author', '2025-05-09 17:00:33', '2025-05-09 17:00:33'),
(354, 739, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(355, 740, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(356, 741, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(357, 742, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(358, 743, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(359, 744, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(360, 745, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(361, 746, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(362, 747, 1760, 'author', '2025-05-09 17:00:34', '2025-05-09 17:00:34'),
(363, 748, 1760, 'author', '2025-05-09 17:00:35', '2025-05-09 17:00:35'),
(364, 749, 1760, 'author', '2025-05-09 17:00:35', '2025-05-09 17:00:35'),
(365, 750, 1760, 'author', '2025-05-09 17:00:35', '2025-05-09 17:00:35'),
(366, 751, 1760, 'author', '2025-05-09 17:00:35', '2025-05-09 17:00:35'),
(367, 752, 1760, 'author', '2025-05-10 08:25:36', '2025-05-10 08:25:36'),
(368, 753, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(369, 754, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(370, 755, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(371, 756, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(372, 757, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(373, 758, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(374, 759, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(375, 760, 1760, 'author', '2025-05-10 08:25:37', '2025-05-10 08:25:37'),
(376, 761, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(377, 762, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(378, 763, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(379, 764, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(380, 765, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(381, 766, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(382, 767, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(383, 768, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(384, 769, 1760, 'author', '2025-05-10 08:25:38', '2025-05-10 08:25:38'),
(385, 770, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(386, 771, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(387, 772, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(388, 773, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(389, 774, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(390, 775, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(391, 776, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(392, 777, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(393, 778, 1760, 'author', '2025-05-10 08:25:39', '2025-05-10 08:25:39'),
(394, 779, 1760, 'author', '2025-05-10 08:25:40', '2025-05-10 08:25:40'),
(395, 780, 1760, 'author', '2025-05-10 08:25:40', '2025-05-10 08:25:40'),
(396, 781, 1760, 'author', '2025-05-10 08:25:40', '2025-05-10 08:25:40'),
(397, 782, 1760, 'author', '2025-05-10 08:25:40', '2025-05-10 08:25:40'),
(398, 783, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(399, 784, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(400, 785, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(401, 786, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(402, 787, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(403, 788, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(404, 789, 1760, 'author', '2025-05-10 08:34:32', '2025-05-10 08:34:32'),
(405, 790, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(406, 791, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(407, 792, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(408, 793, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(409, 794, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(410, 795, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(411, 796, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(412, 797, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(413, 798, 1760, 'author', '2025-05-10 08:34:33', '2025-05-10 08:34:33'),
(414, 799, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(415, 800, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(416, 801, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(417, 802, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(418, 803, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(419, 804, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(420, 805, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(421, 806, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(422, 807, 1760, 'author', '2025-05-10 08:34:34', '2025-05-10 08:34:34'),
(423, 808, 1760, 'author', '2025-05-10 08:34:35', '2025-05-10 08:34:35'),
(424, 809, 1760, 'author', '2025-05-10 08:34:35', '2025-05-10 08:34:35'),
(425, 810, 1760, 'author', '2025-05-10 08:34:35', '2025-05-10 08:34:35'),
(426, 811, 1760, 'author', '2025-05-10 08:34:35', '2025-05-10 08:34:35'),
(427, 812, 1760, 'author', '2025-05-10 08:34:35', '2025-05-10 08:34:35'),
(428, 813, 1760, 'author', '2025-05-10 08:34:35', '2025-05-10 08:34:35'),
(429, 814, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(430, 815, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(431, 816, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(432, 817, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(433, 818, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(434, 819, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(435, 820, 1760, 'author', '2025-05-10 08:42:43', '2025-05-10 08:42:43'),
(436, 821, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(437, 822, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(438, 823, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(439, 824, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(440, 825, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(441, 826, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(442, 827, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(443, 828, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(444, 829, 1760, 'author', '2025-05-10 08:42:44', '2025-05-10 08:42:44'),
(445, 830, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(446, 831, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(447, 832, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(448, 833, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(449, 834, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(450, 835, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(451, 836, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(452, 837, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(453, 838, 1760, 'author', '2025-05-10 08:42:45', '2025-05-10 08:42:45'),
(454, 839, 1760, 'author', '2025-05-10 08:42:46', '2025-05-10 08:42:46'),
(455, 840, 1760, 'author', '2025-05-10 08:42:46', '2025-05-10 08:42:46'),
(456, 841, 1760, 'author', '2025-05-10 08:42:46', '2025-05-10 08:42:46'),
(457, 842, 1760, 'author', '2025-05-10 08:42:46', '2025-05-10 08:42:46'),
(458, 843, 1760, 'author', '2025-05-10 08:42:46', '2025-05-10 08:42:46'),
(459, 844, 1760, 'author', '2025-05-10 08:42:46', '2025-05-10 08:42:46'),
(460, 845, 1284, 'author', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(461, 845, 1283, 'publisher', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(462, 846, 977, 'author', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(463, 847, 1286, 'author', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(464, 847, 1285, 'publisher', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(465, 848, 1287, 'author', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(466, 848, 1285, 'publisher', '2025-05-10 08:45:53', '2025-05-10 08:45:53'),
(467, 849, 1286, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(468, 849, 1285, 'publisher', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(469, 850, 1289, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(470, 850, 1288, 'publisher', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(471, 851, 986, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(472, 851, 1297, 'publisher', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(473, 852, 1291, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(474, 852, 1290, 'publisher', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(475, 853, 989, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(476, 854, 1760, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(477, 854, 1285, 'publisher', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(478, 855, 990, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(479, 856, 1286, 'author', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(480, 856, 1285, 'publisher', '2025-05-10 08:45:54', '2025-05-10 08:45:54'),
(481, 857, 1293, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(482, 857, 1292, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(483, 858, 1286, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(484, 858, 1285, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(485, 859, 1760, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(486, 859, 1294, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(487, 860, 1286, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(488, 860, 1285, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(489, 861, 1296, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(490, 861, 1294, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(491, 862, 1003, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(492, 862, 1839, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(493, 863, 977, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(494, 863, 1297, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(495, 864, 1298, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(496, 864, 1285, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(497, 865, 1299, 'author', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(498, 865, 1294, 'publisher', '2025-05-10 08:45:55', '2025-05-10 08:45:55'),
(499, 866, 1009, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(500, 867, 1286, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(501, 867, 1285, 'publisher', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(502, 868, 1760, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(503, 869, 1300, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(504, 869, 1292, 'publisher', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(505, 870, 1302, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(506, 870, 1301, 'publisher', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(507, 871, 1304, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(508, 871, 1303, 'publisher', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(509, 872, 1760, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(510, 873, 1306, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(511, 873, 1305, 'publisher', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(512, 874, 1308, 'author', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(513, 874, 1307, 'publisher', '2025-05-10 08:45:56', '2025-05-10 08:45:56'),
(514, 875, 1309, 'author', '2025-05-10 08:45:57', '2025-05-10 08:45:57'),
(515, 875, 1297, 'publisher', '2025-05-10 08:45:57', '2025-05-10 08:45:57'),
(516, 876, 1284, 'author', '2025-05-10 09:15:49', '2025-05-10 09:15:49'),
(517, 876, 1283, 'publisher', '2025-05-10 09:15:49', '2025-05-10 09:15:49'),
(518, 877, 977, 'author', '2025-05-10 09:15:49', '2025-05-10 09:15:49'),
(519, 878, 1286, 'author', '2025-05-10 09:15:49', '2025-05-10 09:15:49'),
(520, 878, 1285, 'publisher', '2025-05-10 09:15:49', '2025-05-10 09:15:49'),
(521, 879, 1287, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(522, 879, 1285, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(523, 880, 1286, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(524, 880, 1285, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(525, 881, 1289, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(526, 881, 1288, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(527, 882, 986, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(528, 882, 1297, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(529, 883, 1291, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(530, 883, 1290, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(531, 884, 989, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(532, 885, 1760, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(533, 885, 1285, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(534, 886, 990, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(535, 887, 1286, 'author', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(536, 887, 1285, 'publisher', '2025-05-10 09:15:50', '2025-05-10 09:15:50'),
(537, 888, 1293, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(538, 888, 1292, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(539, 889, 1286, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(540, 889, 1285, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(541, 890, 1760, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(542, 890, 1294, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(543, 891, 1286, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(544, 891, 1285, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(545, 892, 1296, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(546, 892, 1294, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(547, 893, 1003, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(548, 893, 1839, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(549, 894, 977, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(550, 894, 1297, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(551, 895, 1298, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(552, 895, 1285, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(553, 896, 1299, 'author', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(554, 896, 1294, 'publisher', '2025-05-10 09:15:51', '2025-05-10 09:15:51'),
(555, 897, 1009, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(556, 898, 1286, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(557, 898, 1285, 'publisher', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(558, 899, 1760, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(559, 900, 1300, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(560, 900, 1292, 'publisher', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(561, 901, 1302, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(562, 901, 1301, 'publisher', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(563, 902, 1304, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(564, 902, 1303, 'publisher', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(565, 903, 1760, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(566, 904, 1306, 'author', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(567, 904, 1305, 'publisher', '2025-05-10 09:15:52', '2025-05-10 09:15:52'),
(568, 905, 1308, 'author', '2025-05-10 09:15:53', '2025-05-10 09:15:53'),
(569, 905, 1307, 'publisher', '2025-05-10 09:15:53', '2025-05-10 09:15:53'),
(570, 906, 1309, 'author', '2025-05-10 09:15:53', '2025-05-10 09:15:53'),
(571, 906, 1297, 'publisher', '2025-05-10 09:15:53', '2025-05-10 09:15:53'),
(572, 907, 1284, 'author', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(573, 907, 1283, 'publisher', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(574, 908, 977, 'author', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(575, 909, 1286, 'author', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(576, 909, 1285, 'publisher', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(577, 910, 1287, 'author', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(578, 910, 1285, 'publisher', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(579, 911, 1286, 'author', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(580, 911, 1285, 'publisher', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(581, 912, 1289, 'author', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(582, 912, 1288, 'publisher', '2025-05-10 09:19:04', '2025-05-10 09:19:04'),
(583, 913, 986, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(584, 913, 1297, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(585, 914, 1291, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(586, 914, 1290, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(587, 915, 989, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(588, 916, 1760, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(589, 916, 1285, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(590, 917, 990, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(591, 918, 1286, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(592, 918, 1285, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(593, 919, 1293, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(594, 919, 1292, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(595, 920, 1286, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(596, 920, 1285, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(597, 921, 1760, 'author', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(598, 921, 1294, 'publisher', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(599, 922, 1286, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(600, 922, 1285, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(601, 923, 1296, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(602, 923, 1294, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(603, 924, 1003, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(604, 924, 1839, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(605, 925, 977, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(606, 925, 1297, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(607, 926, 1298, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(608, 926, 1285, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(609, 927, 1299, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(610, 927, 1294, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(611, 928, 1009, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(612, 929, 1286, 'author', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(613, 929, 1285, 'publisher', '2025-05-10 09:19:06', '2025-05-10 09:19:06'),
(614, 930, 1760, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(615, 931, 1300, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(616, 931, 1292, 'publisher', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(617, 932, 1302, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(618, 932, 1301, 'publisher', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(619, 933, 1304, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(620, 933, 1303, 'publisher', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(621, 934, 1760, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(622, 935, 1306, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(623, 935, 1305, 'publisher', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(624, 936, 1308, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(625, 936, 1307, 'publisher', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(626, 937, 1309, 'author', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(627, 937, 1297, 'publisher', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(628, 938, 1284, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(629, 938, 1283, 'publisher', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(630, 939, 977, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(631, 940, 1286, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(632, 940, 1285, 'publisher', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(633, 941, 1287, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(634, 941, 1285, 'publisher', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(635, 942, 1286, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(636, 942, 1285, 'publisher', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(637, 943, 1289, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(638, 943, 1288, 'publisher', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(639, 944, 986, 'author', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(640, 944, 1297, 'publisher', '2025-05-10 09:19:13', '2025-05-10 09:19:13'),
(641, 945, 1291, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(642, 945, 1290, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(643, 946, 989, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(644, 947, 1760, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(645, 947, 1285, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(646, 948, 990, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(647, 949, 1286, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(648, 949, 1285, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(649, 950, 1293, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(650, 950, 1292, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(651, 951, 1286, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(652, 951, 1285, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(653, 952, 1760, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(654, 952, 1294, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(655, 953, 1286, 'author', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(656, 953, 1285, 'publisher', '2025-05-10 09:19:14', '2025-05-10 09:19:14'),
(657, 954, 1296, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(658, 954, 1294, 'publisher', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(659, 955, 1003, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(660, 955, 1839, 'publisher', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(661, 956, 977, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(662, 956, 1297, 'publisher', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(663, 957, 1298, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(664, 957, 1285, 'publisher', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(665, 958, 1299, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(666, 958, 1294, 'publisher', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(667, 959, 1009, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(668, 960, 1286, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(669, 960, 1285, 'publisher', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(670, 961, 1760, 'author', '2025-05-10 09:19:15', '2025-05-10 09:19:15'),
(671, 962, 1300, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(672, 962, 1292, 'publisher', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(673, 963, 1302, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(674, 963, 1301, 'publisher', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(675, 964, 1304, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(676, 964, 1303, 'publisher', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(677, 965, 1760, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(678, 966, 1306, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(679, 966, 1305, 'publisher', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(680, 967, 1308, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(681, 967, 1307, 'publisher', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(682, 968, 1309, 'author', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(683, 968, 1297, 'publisher', '2025-05-10 09:19:16', '2025-05-10 09:19:16'),
(684, 969, 1284, 'author', '2025-05-10 09:21:44', '2025-05-10 09:21:44'),
(685, 969, 1283, 'publisher', '2025-05-10 09:21:44', '2025-05-10 09:21:44'),
(686, 970, 977, 'author', '2025-05-10 09:21:44', '2025-05-10 09:21:44'),
(687, 971, 1286, 'author', '2025-05-10 09:21:44', '2025-05-10 09:21:44'),
(688, 971, 1285, 'publisher', '2025-05-10 09:21:44', '2025-05-10 09:21:44'),
(689, 972, 1287, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(690, 972, 1285, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(691, 973, 1286, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(692, 973, 1285, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(693, 974, 1289, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(694, 974, 1288, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(695, 975, 986, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(696, 975, 1297, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(697, 976, 1291, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(698, 976, 1290, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(699, 977, 989, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(700, 978, 1760, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(701, 978, 1285, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(702, 979, 990, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(703, 980, 1286, 'author', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(704, 980, 1285, 'publisher', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(705, 981, 1293, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(706, 981, 1292, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(707, 982, 1286, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(708, 982, 1285, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(709, 983, 1760, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(710, 983, 1294, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(711, 984, 1286, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(712, 984, 1285, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(713, 985, 1296, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(714, 985, 1294, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(715, 986, 1003, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(716, 986, 1839, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(717, 987, 977, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(718, 987, 1297, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(719, 988, 1298, 'author', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(720, 988, 1285, 'publisher', '2025-05-10 09:21:46', '2025-05-10 09:21:46'),
(721, 989, 1299, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(722, 989, 1294, 'publisher', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(723, 990, 1009, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(724, 991, 1286, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(725, 991, 1285, 'publisher', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(726, 992, 1760, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(727, 993, 1300, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(728, 993, 1292, 'publisher', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(729, 994, 1302, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(730, 994, 1301, 'publisher', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(731, 995, 1304, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(732, 995, 1303, 'publisher', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(733, 996, 1760, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(734, 997, 1306, 'author', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(735, 997, 1305, 'publisher', '2025-05-10 09:21:47', '2025-05-10 09:21:47'),
(736, 998, 1308, 'author', '2025-05-10 09:21:48', '2025-05-10 09:21:48'),
(737, 998, 1307, 'publisher', '2025-05-10 09:21:48', '2025-05-10 09:21:48');
INSERT INTO `book_authors` (`id`, `directory_item_id`, `author_id`, `role`, `created_at`, `updated_at`) VALUES
(738, 999, 1309, 'author', '2025-05-10 09:21:48', '2025-05-10 09:21:48'),
(739, 999, 1297, 'publisher', '2025-05-10 09:21:48', '2025-05-10 09:21:48'),
(740, 1000, 1284, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(741, 1000, 1283, 'publisher', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(742, 1001, 977, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(743, 1002, 1286, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(744, 1002, 1285, 'publisher', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(745, 1003, 1287, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(746, 1003, 1285, 'publisher', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(747, 1004, 1286, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(748, 1004, 1285, 'publisher', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(749, 1005, 1289, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(750, 1005, 1288, 'publisher', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(751, 1006, 986, 'author', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(752, 1006, 1297, 'publisher', '2025-05-10 09:22:09', '2025-05-10 09:22:09'),
(753, 1007, 1291, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(754, 1007, 1290, 'publisher', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(755, 1008, 989, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(756, 1009, 1760, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(757, 1009, 1285, 'publisher', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(758, 1010, 990, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(759, 1011, 1286, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(760, 1011, 1285, 'publisher', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(761, 1012, 1293, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(762, 1012, 1292, 'publisher', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(763, 1013, 1286, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(764, 1013, 1285, 'publisher', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(765, 1014, 1760, 'author', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(766, 1014, 1294, 'publisher', '2025-05-10 09:22:10', '2025-05-10 09:22:10'),
(767, 1015, 1286, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(768, 1015, 1285, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(769, 1016, 1296, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(770, 1016, 1294, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(771, 1017, 1003, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(772, 1017, 1839, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(773, 1018, 977, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(774, 1018, 1297, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(775, 1019, 1298, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(776, 1019, 1285, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(777, 1020, 1299, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(778, 1020, 1294, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(779, 1021, 1009, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(780, 1022, 1286, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(781, 1022, 1285, 'publisher', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(782, 1023, 1760, 'author', '2025-05-10 09:22:11', '2025-05-10 09:22:11'),
(783, 1024, 1300, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(784, 1024, 1292, 'publisher', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(785, 1025, 1302, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(786, 1025, 1301, 'publisher', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(787, 1026, 1304, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(788, 1026, 1303, 'publisher', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(789, 1027, 1760, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(790, 1028, 1306, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(791, 1028, 1305, 'publisher', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(792, 1029, 1308, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(793, 1029, 1307, 'publisher', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(794, 1030, 1309, 'author', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(795, 1030, 1297, 'publisher', '2025-05-10 09:22:12', '2025-05-10 09:22:12'),
(796, 1031, 1284, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(797, 1031, 1283, 'publisher', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(798, 1032, 977, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(799, 1033, 1286, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(800, 1033, 1285, 'publisher', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(801, 1034, 1287, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(802, 1034, 1285, 'publisher', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(803, 1035, 1286, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(804, 1035, 1285, 'publisher', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(805, 1036, 1289, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(806, 1036, 1288, 'publisher', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(807, 1037, 986, 'author', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(808, 1037, 1297, 'publisher', '2025-05-10 09:39:52', '2025-05-10 09:39:52'),
(809, 1038, 1291, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(810, 1038, 1290, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(811, 1039, 989, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(812, 1040, 1760, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(813, 1040, 1285, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(814, 1041, 990, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(815, 1042, 1286, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(816, 1042, 1285, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(817, 1043, 1293, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(818, 1043, 1292, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(819, 1044, 1286, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(820, 1044, 1285, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(821, 1045, 1760, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(822, 1045, 1294, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(823, 1046, 1286, 'author', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(824, 1046, 1285, 'publisher', '2025-05-10 09:39:53', '2025-05-10 09:39:53'),
(825, 1047, 1296, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(826, 1047, 1294, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(827, 1048, 1003, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(828, 1048, 1839, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(829, 1049, 977, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(830, 1049, 1297, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(831, 1050, 1298, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(832, 1050, 1285, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(833, 1051, 1299, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(834, 1051, 1294, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(835, 1052, 1009, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(836, 1053, 1286, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(837, 1053, 1285, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(838, 1054, 1760, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(839, 1055, 1300, 'author', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(840, 1055, 1292, 'publisher', '2025-05-10 09:39:54', '2025-05-10 09:39:54'),
(841, 1056, 1302, 'author', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(842, 1056, 1301, 'publisher', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(843, 1057, 1304, 'author', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(844, 1057, 1303, 'publisher', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(845, 1058, 1760, 'author', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(846, 1059, 1306, 'author', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(847, 1059, 1305, 'publisher', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(848, 1060, 1308, 'author', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(849, 1060, 1307, 'publisher', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(850, 1061, 1309, 'author', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(851, 1061, 1297, 'publisher', '2025-05-10 09:39:55', '2025-05-10 09:39:55'),
(852, 1062, 1284, 'author', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(853, 1062, 1283, 'publisher', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(854, 1063, 977, 'author', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(855, 1064, 1286, 'author', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(856, 1064, 1285, 'publisher', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(857, 1065, 1287, 'author', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(858, 1065, 1285, 'publisher', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(859, 1066, 1286, 'author', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(860, 1066, 1285, 'publisher', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(861, 1067, 1289, 'author', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(862, 1067, 1288, 'publisher', '2025-05-10 09:43:09', '2025-05-10 09:43:09'),
(863, 1068, 986, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(864, 1068, 1297, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(865, 1069, 1291, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(866, 1069, 1290, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(867, 1070, 989, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(868, 1071, 1760, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(869, 1071, 1285, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(870, 1072, 990, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(871, 1073, 1286, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(872, 1073, 1285, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(873, 1074, 1293, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(874, 1074, 1292, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(875, 1075, 1286, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(876, 1075, 1285, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(877, 1076, 1760, 'author', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(878, 1076, 1294, 'publisher', '2025-05-10 09:43:10', '2025-05-10 09:43:10'),
(879, 1077, 1286, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(880, 1077, 1285, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(881, 1078, 1296, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(882, 1078, 1294, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(883, 1079, 1003, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(884, 1079, 1839, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(885, 1080, 977, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(886, 1080, 1297, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(887, 1081, 1298, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(888, 1081, 1285, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(889, 1082, 1299, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(890, 1082, 1294, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(891, 1083, 1009, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(892, 1084, 1286, 'author', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(893, 1084, 1285, 'publisher', '2025-05-10 09:43:11', '2025-05-10 09:43:11'),
(894, 1085, 1760, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(895, 1086, 1300, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(896, 1086, 1292, 'publisher', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(897, 1087, 1302, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(898, 1087, 1301, 'publisher', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(899, 1088, 1304, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(900, 1088, 1303, 'publisher', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(901, 1089, 1760, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(902, 1090, 1306, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(903, 1090, 1305, 'publisher', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(904, 1091, 1308, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(905, 1091, 1307, 'publisher', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(906, 1092, 1309, 'author', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(907, 1092, 1297, 'publisher', '2025-05-10 09:43:12', '2025-05-10 09:43:12'),
(908, 1093, 1284, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(909, 1093, 1283, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(910, 1094, 977, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(911, 1095, 1286, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(912, 1095, 1285, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(913, 1096, 1287, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(914, 1096, 1285, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(915, 1097, 1286, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(916, 1097, 1285, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(917, 1098, 1289, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(918, 1098, 1288, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(919, 1099, 986, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(920, 1099, 1297, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(921, 1100, 1291, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(922, 1100, 1290, 'publisher', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(923, 1101, 989, 'author', '2025-05-10 09:43:19', '2025-05-10 09:43:19'),
(924, 1102, 1760, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(925, 1102, 1285, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(926, 1103, 990, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(927, 1104, 1286, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(928, 1104, 1285, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(929, 1105, 1293, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(930, 1105, 1292, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(931, 1106, 1286, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(932, 1106, 1285, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(933, 1107, 1760, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(934, 1107, 1294, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(935, 1108, 1286, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(936, 1108, 1285, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(937, 1109, 1296, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(938, 1109, 1294, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(939, 1110, 1003, 'author', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(940, 1110, 1839, 'publisher', '2025-05-10 09:43:20', '2025-05-10 09:43:20'),
(941, 1111, 977, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(942, 1111, 1297, 'publisher', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(943, 1112, 1298, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(944, 1112, 1285, 'publisher', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(945, 1113, 1299, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(946, 1113, 1294, 'publisher', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(947, 1114, 1009, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(948, 1115, 1286, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(949, 1115, 1285, 'publisher', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(950, 1116, 1760, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(951, 1117, 1300, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(952, 1117, 1292, 'publisher', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(953, 1118, 1302, 'author', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(954, 1118, 1301, 'publisher', '2025-05-10 09:43:21', '2025-05-10 09:43:21'),
(955, 1119, 1304, 'author', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(956, 1119, 1303, 'publisher', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(957, 1120, 1760, 'author', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(958, 1121, 1306, 'author', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(959, 1121, 1305, 'publisher', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(960, 1122, 1308, 'author', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(961, 1122, 1307, 'publisher', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(962, 1123, 1309, 'author', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(963, 1123, 1297, 'publisher', '2025-05-10 09:43:22', '2025-05-10 09:43:22'),
(964, 1124, 1284, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(965, 1124, 1283, 'publisher', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(966, 1125, 977, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(967, 1126, 1286, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(968, 1126, 1285, 'publisher', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(969, 1127, 1287, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(970, 1127, 1285, 'publisher', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(971, 1128, 1286, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(972, 1128, 1285, 'publisher', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(973, 1129, 1289, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(974, 1129, 1288, 'publisher', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(975, 1130, 986, 'author', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(976, 1130, 1297, 'publisher', '2025-05-10 10:00:56', '2025-05-10 10:00:56'),
(977, 1131, 1291, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(978, 1131, 1290, 'publisher', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(979, 1132, 989, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(980, 1133, 1760, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(981, 1133, 1285, 'publisher', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(982, 1134, 990, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(983, 1135, 1286, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(984, 1135, 1285, 'publisher', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(985, 1136, 1293, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(986, 1136, 1292, 'publisher', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(987, 1137, 1286, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(988, 1137, 1285, 'publisher', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(989, 1138, 1760, 'author', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(990, 1138, 1294, 'publisher', '2025-05-10 10:00:57', '2025-05-10 10:00:57'),
(991, 1139, 1286, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(992, 1139, 1285, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(993, 1140, 1296, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(994, 1140, 1294, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(995, 1141, 1003, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(996, 1141, 1839, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(997, 1142, 977, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(998, 1142, 1297, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(999, 1143, 1298, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1000, 1143, 1285, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1001, 1144, 1299, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1002, 1144, 1294, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1003, 1145, 1009, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1004, 1146, 1286, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1005, 1146, 1285, 'publisher', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1006, 1147, 1760, 'author', '2025-05-10 10:00:58', '2025-05-10 10:00:58'),
(1007, 1148, 1300, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1008, 1148, 1292, 'publisher', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1009, 1149, 1302, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1010, 1149, 1301, 'publisher', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1011, 1150, 1304, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1012, 1150, 1303, 'publisher', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1013, 1151, 1760, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1014, 1152, 1306, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1015, 1152, 1305, 'publisher', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1016, 1153, 1308, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1017, 1153, 1307, 'publisher', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1018, 1154, 1309, 'author', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1019, 1154, 1297, 'publisher', '2025-05-10 10:00:59', '2025-05-10 10:00:59'),
(1020, 1155, 1284, 'author', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1021, 1155, 1283, 'publisher', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1022, 1156, 977, 'author', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1023, 1157, 1286, 'author', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1024, 1157, 1285, 'publisher', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1025, 1158, 1287, 'author', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1026, 1158, 1285, 'publisher', '2025-05-10 10:01:05', '2025-05-10 10:01:05'),
(1027, 1159, 1286, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1028, 1159, 1285, 'publisher', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1029, 1160, 1289, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1030, 1160, 1288, 'publisher', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1031, 1161, 986, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1032, 1161, 1297, 'publisher', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1033, 1162, 1291, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1034, 1162, 1290, 'publisher', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1035, 1163, 989, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1036, 1164, 1760, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1037, 1164, 1285, 'publisher', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1038, 1165, 990, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1039, 1166, 1286, 'author', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1040, 1166, 1285, 'publisher', '2025-05-10 10:01:06', '2025-05-10 10:01:06'),
(1041, 1167, 1293, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1042, 1167, 1292, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1043, 1168, 1286, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1044, 1168, 1285, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1045, 1169, 1760, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1046, 1169, 1294, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1047, 1170, 1286, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1048, 1170, 1285, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1049, 1171, 1296, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1050, 1171, 1294, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1051, 1172, 1003, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1052, 1172, 1839, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1053, 1173, 977, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1054, 1173, 1297, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1055, 1174, 1298, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1056, 1174, 1285, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1057, 1175, 1299, 'author', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1058, 1175, 1294, 'publisher', '2025-05-10 10:01:07', '2025-05-10 10:01:07'),
(1059, 1176, 1009, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1060, 1177, 1286, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1061, 1177, 1285, 'publisher', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1062, 1178, 1760, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1063, 1179, 1300, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1064, 1179, 1292, 'publisher', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1065, 1180, 1302, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1066, 1180, 1301, 'publisher', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1067, 1181, 1304, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1068, 1181, 1303, 'publisher', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1069, 1182, 1760, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1070, 1183, 1306, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1071, 1183, 1305, 'publisher', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1072, 1184, 1308, 'author', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1073, 1184, 1307, 'publisher', '2025-05-10 10:01:08', '2025-05-10 10:01:08'),
(1074, 1185, 1309, 'author', '2025-05-10 10:01:09', '2025-05-10 10:01:09'),
(1075, 1185, 1297, 'publisher', '2025-05-10 10:01:09', '2025-05-10 10:01:09'),
(1076, 1186, 1284, 'author', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1077, 1186, 1283, 'publisher', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1078, 1187, 977, 'author', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1079, 1188, 1286, 'author', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1080, 1188, 1285, 'publisher', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1081, 1189, 1287, 'author', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1082, 1189, 1285, 'publisher', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1083, 1190, 1286, 'author', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1084, 1190, 1285, 'publisher', '2025-05-10 10:01:26', '2025-05-10 10:01:26'),
(1085, 1191, 1289, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1086, 1191, 1288, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1087, 1192, 986, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1088, 1192, 1297, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1089, 1193, 1291, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1090, 1193, 1290, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1091, 1194, 989, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1092, 1195, 1760, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1093, 1195, 1285, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1094, 1196, 990, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1095, 1197, 1286, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1096, 1197, 1285, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1097, 1198, 1293, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1098, 1198, 1292, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1099, 1199, 1286, 'author', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1100, 1199, 1285, 'publisher', '2025-05-10 10:01:27', '2025-05-10 10:01:27'),
(1101, 1200, 1760, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1102, 1200, 1294, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1103, 1201, 1286, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1104, 1201, 1285, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1105, 1202, 1296, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1106, 1202, 1294, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1107, 1203, 1003, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1108, 1203, 1839, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1109, 1204, 977, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1110, 1204, 1297, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1111, 1205, 1298, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1112, 1205, 1285, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1113, 1206, 1299, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1114, 1206, 1294, 'publisher', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1115, 1207, 1009, 'author', '2025-05-10 10:01:28', '2025-05-10 10:01:28'),
(1116, 1208, 1286, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1117, 1208, 1285, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1118, 1209, 1760, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1119, 1210, 1300, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1120, 1210, 1292, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1121, 1211, 1302, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1122, 1211, 1301, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1123, 1212, 1304, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1124, 1212, 1303, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1125, 1213, 1760, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1126, 1214, 1306, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1127, 1214, 1305, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1128, 1215, 1308, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1129, 1215, 1307, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1130, 1216, 1309, 'author', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1131, 1216, 1297, 'publisher', '2025-05-10 10:01:29', '2025-05-10 10:01:29'),
(1132, 1217, 1284, 'author', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1133, 1217, 1283, 'publisher', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1134, 1218, 977, 'author', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1135, 1219, 1286, 'author', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1136, 1219, 1285, 'publisher', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1137, 1220, 1287, 'author', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1138, 1220, 1285, 'publisher', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1139, 1221, 1286, 'author', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1140, 1221, 1285, 'publisher', '2025-05-10 10:01:36', '2025-05-10 10:01:36'),
(1141, 1222, 1289, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1142, 1222, 1288, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1143, 1223, 986, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1144, 1223, 1297, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1145, 1224, 1291, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1146, 1224, 1290, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1147, 1225, 989, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1148, 1226, 1760, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1149, 1226, 1285, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1150, 1227, 990, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1151, 1228, 1286, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1152, 1228, 1285, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1153, 1229, 1293, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1154, 1229, 1292, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1155, 1230, 1286, 'author', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1156, 1230, 1285, 'publisher', '2025-05-10 10:01:37', '2025-05-10 10:01:37'),
(1157, 1231, 1760, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1158, 1231, 1294, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1159, 1232, 1286, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1160, 1232, 1285, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1161, 1233, 1296, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1162, 1233, 1294, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1163, 1234, 1003, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1164, 1234, 1839, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1165, 1235, 977, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1166, 1235, 1297, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1167, 1236, 1298, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1168, 1236, 1285, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1169, 1237, 1299, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1170, 1237, 1294, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1171, 1238, 1009, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1172, 1239, 1286, 'author', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1173, 1239, 1285, 'publisher', '2025-05-10 10:01:38', '2025-05-10 10:01:38'),
(1174, 1240, 1760, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1175, 1241, 1300, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1176, 1241, 1292, 'publisher', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1177, 1242, 1302, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1178, 1242, 1301, 'publisher', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1179, 1243, 1304, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1180, 1243, 1303, 'publisher', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1181, 1244, 1760, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1182, 1245, 1306, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1183, 1245, 1305, 'publisher', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1184, 1246, 1308, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1185, 1246, 1307, 'publisher', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1186, 1247, 1309, 'author', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1187, 1247, 1297, 'publisher', '2025-05-10 10:01:39', '2025-05-10 10:01:39'),
(1188, 1248, 1284, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1189, 1248, 1283, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1190, 1249, 977, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1191, 1250, 1286, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1192, 1250, 1285, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1193, 1251, 1287, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1194, 1251, 1285, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1195, 1252, 1286, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1196, 1252, 1285, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1197, 1253, 1289, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1198, 1253, 1288, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1199, 1254, 986, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1200, 1254, 1297, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1201, 1255, 1291, 'author', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1202, 1255, 1290, 'publisher', '2025-05-10 10:24:02', '2025-05-10 10:24:02'),
(1203, 1256, 989, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1204, 1257, 1760, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1205, 1257, 1285, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1206, 1258, 990, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1207, 1259, 1286, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1208, 1259, 1285, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1209, 1260, 1293, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1210, 1260, 1292, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1211, 1261, 1286, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1212, 1261, 1285, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1213, 1262, 1760, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1214, 1262, 1294, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1215, 1263, 1286, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1216, 1263, 1285, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1217, 1264, 1296, 'author', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1218, 1264, 1294, 'publisher', '2025-05-10 10:24:03', '2025-05-10 10:24:03'),
(1219, 1265, 1003, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1220, 1265, 1839, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1221, 1266, 977, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1222, 1266, 1297, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1223, 1267, 1298, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1224, 1267, 1285, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1225, 1268, 1299, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1226, 1268, 1294, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1227, 1269, 1009, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1228, 1270, 1286, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1229, 1270, 1285, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1230, 1271, 1760, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1231, 1272, 1300, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1232, 1272, 1292, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1233, 1273, 1302, 'author', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1234, 1273, 1301, 'publisher', '2025-05-10 10:24:04', '2025-05-10 10:24:04'),
(1235, 1274, 1304, 'author', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1236, 1274, 1303, 'publisher', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1237, 1275, 1760, 'author', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1238, 1276, 1306, 'author', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1239, 1276, 1305, 'publisher', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1240, 1277, 1308, 'author', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1241, 1277, 1307, 'publisher', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1242, 1278, 1309, 'author', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1243, 1278, 1297, 'publisher', '2025-05-10 10:24:05', '2025-05-10 10:24:05'),
(1244, 1279, 1284, 'author', '2025-05-10 10:24:12', '2025-05-10 10:24:12'),
(1245, 1279, 1283, 'publisher', '2025-05-10 10:24:12', '2025-05-10 10:24:12'),
(1246, 1280, 977, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1247, 1281, 1286, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1248, 1281, 1285, 'publisher', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1249, 1282, 1287, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1250, 1282, 1285, 'publisher', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1251, 1283, 1286, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1252, 1283, 1285, 'publisher', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1253, 1284, 1289, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1254, 1284, 1288, 'publisher', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1255, 1285, 986, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1256, 1285, 1297, 'publisher', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1257, 1286, 1291, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1258, 1286, 1290, 'publisher', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1259, 1287, 989, 'author', '2025-05-10 10:24:13', '2025-05-10 10:24:13'),
(1260, 1288, 1760, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1261, 1288, 1285, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1262, 1289, 990, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1263, 1290, 1286, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1264, 1290, 1285, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1265, 1291, 1293, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1266, 1291, 1292, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1267, 1292, 1286, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1268, 1292, 1285, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1269, 1293, 1760, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1270, 1293, 1294, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1271, 1294, 1286, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1272, 1294, 1285, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1273, 1295, 1296, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1274, 1295, 1294, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1275, 1296, 1003, 'author', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1276, 1296, 1839, 'publisher', '2025-05-10 10:24:14', '2025-05-10 10:24:14'),
(1277, 1297, 977, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1278, 1297, 1297, 'publisher', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1279, 1298, 1298, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1280, 1298, 1285, 'publisher', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1281, 1299, 1299, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1282, 1299, 1294, 'publisher', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1283, 1300, 1009, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1284, 1301, 1286, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1285, 1301, 1285, 'publisher', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1286, 1302, 1760, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1287, 1303, 1300, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1288, 1303, 1292, 'publisher', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1289, 1304, 1302, 'author', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1290, 1304, 1301, 'publisher', '2025-05-10 10:24:15', '2025-05-10 10:24:15'),
(1291, 1305, 1304, 'author', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1292, 1305, 1303, 'publisher', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1293, 1306, 1760, 'author', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1294, 1307, 1306, 'author', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1295, 1307, 1305, 'publisher', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1296, 1308, 1308, 'author', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1297, 1308, 1307, 'publisher', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1298, 1309, 1309, 'author', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1299, 1309, 1297, 'publisher', '2025-05-10 10:24:16', '2025-05-10 10:24:16'),
(1300, 1310, 1866, 'author', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1301, 1310, 1283, 'publisher', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1302, 1311, 1867, 'author', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1303, 1312, 1868, 'author', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1304, 1312, 1285, 'publisher', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1305, 1313, 1869, 'author', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1306, 1313, 1285, 'publisher', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1307, 1314, 1868, 'author', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1308, 1314, 1285, 'publisher', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1309, 1315, 1870, 'author', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1310, 1315, 1288, 'publisher', '2025-05-10 10:51:19', '2025-05-10 10:51:19'),
(1311, 1316, 1871, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1312, 1316, 1297, 'publisher', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1313, 1317, 1872, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1314, 1317, 1290, 'publisher', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1315, 1318, 1873, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1316, 1319, 1875, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1317, 1320, 1868, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1318, 1320, 1285, 'publisher', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1319, 1321, 1876, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1320, 1321, 1292, 'publisher', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1321, 1322, 1868, 'author', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1322, 1322, 1285, 'publisher', '2025-05-10 10:51:20', '2025-05-10 10:51:20'),
(1323, 1323, 1868, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1324, 1323, 1285, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1325, 1324, 1878, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1326, 1324, 1294, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1327, 1325, 1879, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1328, 1325, 1839, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1329, 1326, 1867, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1330, 1326, 1297, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1331, 1327, 1880, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1332, 1327, 1285, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1333, 1328, 1881, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1334, 1328, 1294, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1335, 1329, 1882, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1336, 1330, 1868, 'author', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1337, 1330, 1285, 'publisher', '2025-05-10 10:51:21', '2025-05-10 10:51:21'),
(1338, 1331, 1884, 'author', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1339, 1331, 1292, 'publisher', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1340, 1332, 1885, 'author', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1341, 1332, 1301, 'publisher', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1342, 1333, 1886, 'author', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1343, 1333, 1303, 'publisher', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1344, 1334, 1888, 'author', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1345, 1334, 1305, 'publisher', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1346, 1335, 1889, 'author', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1347, 1335, 1307, 'publisher', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1348, 1336, 1890, 'author', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1349, 1336, 1297, 'publisher', '2025-05-10 10:51:22', '2025-05-10 10:51:22'),
(1350, 1337, 1866, 'author', '2025-05-10 11:09:26', '2025-05-10 11:09:26'),
(1351, 1337, 1283, 'publisher', '2025-05-10 11:09:26', '2025-05-10 11:09:26'),
(1352, 1338, 1867, 'author', '2025-05-10 11:09:26', '2025-05-10 11:09:26'),
(1353, 1339, 1868, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1354, 1339, 1285, 'publisher', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1355, 1340, 1869, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1356, 1340, 1285, 'publisher', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1357, 1341, 1868, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1358, 1341, 1285, 'publisher', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1359, 1342, 1870, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1360, 1342, 1288, 'publisher', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1361, 1343, 1871, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1362, 1343, 1297, 'publisher', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1363, 1344, 1872, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1364, 1344, 1290, 'publisher', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1365, 1345, 1873, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1366, 1346, 1875, 'author', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(1367, 1347, 1868, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1368, 1347, 1285, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1369, 1348, 1876, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1370, 1348, 1292, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1371, 1349, 1868, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1372, 1349, 1285, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1373, 1350, 1868, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1374, 1350, 1285, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1375, 1351, 1878, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1376, 1351, 1294, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1377, 1352, 1879, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1378, 1352, 1839, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1379, 1353, 1867, 'author', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1380, 1353, 1297, 'publisher', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(1381, 1354, 1880, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1382, 1354, 1285, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1383, 1355, 1881, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1384, 1355, 1294, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1385, 1356, 1882, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1386, 1357, 1868, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1387, 1357, 1285, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1388, 1358, 1884, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1389, 1358, 1292, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1390, 1359, 1885, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1391, 1359, 1301, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1392, 1360, 1886, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1393, 1360, 1303, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1394, 1361, 1888, 'author', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1395, 1361, 1305, 'publisher', '2025-05-10 11:09:29', '2025-05-10 11:09:29'),
(1396, 1362, 1889, 'author', '2025-05-10 11:09:30', '2025-05-10 11:09:30'),
(1397, 1362, 1307, 'publisher', '2025-05-10 11:09:30', '2025-05-10 11:09:30'),
(1398, 1363, 1890, 'author', '2025-05-10 11:09:30', '2025-05-10 11:09:30'),
(1399, 1363, 1297, 'publisher', '2025-05-10 11:09:30', '2025-05-10 11:09:30'),
(1400, 1364, 1866, 'author', '2025-05-10 12:30:09', '2025-05-10 12:30:09'),
(1401, 1364, 1283, 'publisher', '2025-05-10 12:30:09', '2025-05-10 12:30:09'),
(1402, 1365, 1867, 'author', '2025-05-10 12:30:09', '2025-05-10 12:30:09'),
(1403, 1366, 1868, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10');
INSERT INTO `book_authors` (`id`, `directory_item_id`, `author_id`, `role`, `created_at`, `updated_at`) VALUES
(1404, 1366, 1285, 'publisher', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1405, 1367, 1869, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1406, 1367, 1285, 'publisher', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1407, 1368, 1868, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1408, 1368, 1285, 'publisher', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1409, 1369, 1870, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1410, 1369, 1288, 'publisher', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1411, 1370, 1871, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1412, 1370, 1297, 'publisher', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1413, 1371, 1872, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1414, 1371, 1290, 'publisher', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1415, 1372, 1873, 'author', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(1416, 1373, 1875, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1417, 1374, 1868, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1418, 1374, 1285, 'publisher', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1419, 1375, 1876, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1420, 1375, 1292, 'publisher', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1421, 1376, 1868, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1422, 1376, 1285, 'publisher', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1423, 1377, 1868, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1424, 1377, 1285, 'publisher', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1425, 1378, 1878, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1426, 1378, 1294, 'publisher', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1427, 1379, 1879, 'author', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1428, 1379, 1839, 'publisher', '2025-05-10 12:30:11', '2025-05-10 12:30:11'),
(1429, 1380, 1867, 'author', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1430, 1380, 1297, 'publisher', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1431, 1381, 1880, 'author', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1432, 1381, 1285, 'publisher', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1433, 1382, 1881, 'author', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1434, 1382, 1294, 'publisher', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1435, 1383, 1882, 'author', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1436, 1384, 1868, 'author', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1437, 1384, 1285, 'publisher', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1438, 1385, 1884, 'author', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1439, 1385, 1292, 'publisher', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(1440, 1386, 1885, 'author', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1441, 1386, 1301, 'publisher', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1442, 1387, 1886, 'author', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1443, 1387, 1303, 'publisher', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1444, 1388, 1888, 'author', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1445, 1388, 1305, 'publisher', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1446, 1389, 1889, 'author', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1447, 1389, 1307, 'publisher', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1448, 1390, 1890, 'author', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1449, 1390, 1297, 'publisher', '2025-05-10 12:30:13', '2025-05-10 12:30:13'),
(1450, 1391, 1866, 'author', '2025-05-10 13:25:06', '2025-05-10 13:25:06'),
(1451, 1391, 1283, 'publisher', '2025-05-10 13:25:06', '2025-05-10 13:25:06'),
(1452, 1392, 1867, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1453, 1393, 1868, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1454, 1393, 1285, 'publisher', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1455, 1394, 1869, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1456, 1394, 1285, 'publisher', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1457, 1395, 1868, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1458, 1395, 1285, 'publisher', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1459, 1396, 1870, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1460, 1396, 1288, 'publisher', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1461, 1397, 1871, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1462, 1397, 1297, 'publisher', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1463, 1398, 1872, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1464, 1398, 1290, 'publisher', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1465, 1399, 1873, 'author', '2025-05-10 13:25:07', '2025-05-10 13:25:07'),
(1466, 1400, 1875, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1467, 1401, 1868, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1468, 1401, 1285, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1469, 1402, 1876, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1470, 1402, 1292, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1471, 1403, 1868, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1472, 1403, 1285, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1473, 1404, 1868, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1474, 1404, 1285, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1475, 1405, 1878, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1476, 1405, 1294, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1477, 1406, 1879, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1478, 1406, 1839, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1479, 1407, 1867, 'author', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1480, 1407, 1297, 'publisher', '2025-05-10 13:25:08', '2025-05-10 13:25:08'),
(1481, 1408, 1880, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1482, 1408, 1285, 'publisher', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1483, 1409, 1881, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1484, 1409, 1294, 'publisher', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1485, 1410, 1882, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1486, 1411, 1868, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1487, 1411, 1285, 'publisher', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1488, 1412, 1884, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1489, 1412, 1292, 'publisher', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1490, 1413, 1885, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1491, 1413, 1301, 'publisher', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1492, 1414, 1886, 'author', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1493, 1414, 1303, 'publisher', '2025-05-10 13:25:09', '2025-05-10 13:25:09'),
(1494, 1415, 1888, 'author', '2025-05-10 13:25:10', '2025-05-10 13:25:10'),
(1495, 1415, 1305, 'publisher', '2025-05-10 13:25:10', '2025-05-10 13:25:10'),
(1496, 1416, 1889, 'author', '2025-05-10 13:25:10', '2025-05-10 13:25:10'),
(1497, 1416, 1307, 'publisher', '2025-05-10 13:25:10', '2025-05-10 13:25:10'),
(1498, 1417, 1890, 'author', '2025-05-10 13:25:10', '2025-05-10 13:25:10'),
(1499, 1417, 1297, 'publisher', '2025-05-10 13:25:10', '2025-05-10 13:25:10'),
(1500, 1418, 1866, 'author', '2025-05-10 13:34:28', '2025-05-10 13:34:28'),
(1501, 1418, 1283, 'publisher', '2025-05-10 13:34:28', '2025-05-10 13:34:28'),
(1502, 1419, 1867, 'author', '2025-05-10 13:34:28', '2025-05-10 13:34:28'),
(1503, 1420, 1868, 'author', '2025-05-10 13:34:28', '2025-05-10 13:34:28'),
(1504, 1420, 1285, 'publisher', '2025-05-10 13:34:28', '2025-05-10 13:34:28'),
(1505, 1421, 1869, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1506, 1421, 1285, 'publisher', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1507, 1422, 1868, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1508, 1422, 1285, 'publisher', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1509, 1423, 1870, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1510, 1423, 1288, 'publisher', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1511, 1424, 1871, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1512, 1424, 1297, 'publisher', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1513, 1425, 1872, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1514, 1425, 1290, 'publisher', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1515, 1426, 1873, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1516, 1427, 1875, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1517, 1428, 1868, 'author', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1518, 1428, 1285, 'publisher', '2025-05-10 13:34:29', '2025-05-10 13:34:29'),
(1519, 1429, 1876, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1520, 1429, 1292, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1521, 1430, 1868, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1522, 1430, 1285, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1523, 1431, 1868, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1524, 1431, 1285, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1525, 1432, 1878, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1526, 1432, 1294, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1527, 1433, 1879, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1528, 1433, 1839, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1529, 1434, 1867, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1530, 1434, 1297, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1531, 1435, 1880, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1532, 1435, 1285, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1533, 1436, 1881, 'author', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1534, 1436, 1294, 'publisher', '2025-05-10 13:34:30', '2025-05-10 13:34:30'),
(1535, 1437, 1882, 'author', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1536, 1438, 1868, 'author', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1537, 1438, 1285, 'publisher', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1538, 1439, 1884, 'author', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1539, 1439, 1292, 'publisher', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1540, 1440, 1885, 'author', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1541, 1440, 1301, 'publisher', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1542, 1441, 1886, 'author', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1543, 1441, 1303, 'publisher', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1544, 1442, 1888, 'author', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1545, 1442, 1305, 'publisher', '2025-05-10 13:34:31', '2025-05-10 13:34:31'),
(1546, 1443, 1889, 'author', '2025-05-10 13:34:32', '2025-05-10 13:34:32'),
(1547, 1443, 1307, 'publisher', '2025-05-10 13:34:32', '2025-05-10 13:34:32'),
(1548, 1444, 1890, 'author', '2025-05-10 13:34:32', '2025-05-10 13:34:32'),
(1549, 1444, 1297, 'publisher', '2025-05-10 13:34:32', '2025-05-10 13:34:32'),
(1550, 1445, 1866, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1551, 1445, 1283, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1552, 1446, 1867, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1553, 1447, 1868, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1554, 1447, 1285, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1555, 1448, 1869, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1556, 1448, 1285, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1557, 1449, 1868, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1558, 1449, 1285, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1559, 1450, 1870, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1560, 1450, 1288, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1561, 1451, 1871, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1562, 1451, 1297, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1563, 1452, 1872, 'author', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1564, 1452, 1290, 'publisher', '2025-05-10 13:34:38', '2025-05-10 13:34:38'),
(1565, 1453, 1873, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1566, 1454, 1875, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1567, 1455, 1868, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1568, 1455, 1285, 'publisher', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1569, 1456, 1876, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1570, 1456, 1292, 'publisher', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1571, 1457, 1868, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1572, 1457, 1285, 'publisher', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1573, 1458, 1868, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1574, 1458, 1285, 'publisher', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1575, 1459, 1878, 'author', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1576, 1459, 1294, 'publisher', '2025-05-10 13:34:39', '2025-05-10 13:34:39'),
(1577, 1460, 1879, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1578, 1460, 1839, 'publisher', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1579, 1461, 1867, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1580, 1461, 1297, 'publisher', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1581, 1462, 1880, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1582, 1462, 1285, 'publisher', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1583, 1463, 1881, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1584, 1463, 1294, 'publisher', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1585, 1464, 1882, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1586, 1465, 1868, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1587, 1465, 1285, 'publisher', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1588, 1466, 1884, 'author', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1589, 1466, 1292, 'publisher', '2025-05-10 13:34:40', '2025-05-10 13:34:40'),
(1590, 1467, 1885, 'author', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1591, 1467, 1301, 'publisher', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1592, 1468, 1886, 'author', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1593, 1468, 1303, 'publisher', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1594, 1469, 1888, 'author', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1595, 1469, 1305, 'publisher', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1596, 1470, 1889, 'author', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1597, 1470, 1307, 'publisher', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1598, 1471, 1890, 'author', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1599, 1471, 1297, 'publisher', '2025-05-10 13:34:41', '2025-05-10 13:34:41'),
(1600, 1472, 1866, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1601, 1472, 1283, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1602, 1473, 1867, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1603, 1474, 1868, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1604, 1474, 1285, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1605, 1475, 1869, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1606, 1475, 1285, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1607, 1476, 1868, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1608, 1476, 1285, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1609, 1477, 1870, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1610, 1477, 1288, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1611, 1478, 1871, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1612, 1478, 1297, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1613, 1479, 1872, 'author', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1614, 1479, 1290, 'publisher', '2025-05-10 13:34:49', '2025-05-10 13:34:49'),
(1615, 1480, 1873, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1616, 1481, 1875, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1617, 1482, 1868, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1618, 1482, 1285, 'publisher', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1619, 1483, 1876, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1620, 1483, 1292, 'publisher', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1621, 1484, 1868, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1622, 1484, 1285, 'publisher', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1623, 1485, 1868, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1624, 1485, 1285, 'publisher', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1625, 1486, 1878, 'author', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1626, 1486, 1294, 'publisher', '2025-05-10 13:34:50', '2025-05-10 13:34:50'),
(1627, 1487, 1879, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1628, 1487, 1839, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1629, 1488, 1867, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1630, 1488, 1297, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1631, 1489, 1880, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1632, 1489, 1285, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1633, 1490, 1881, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1634, 1490, 1294, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1635, 1491, 1882, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1636, 1492, 1868, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1637, 1492, 1285, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1638, 1493, 1884, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1639, 1493, 1292, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1640, 1494, 1885, 'author', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1641, 1494, 1301, 'publisher', '2025-05-10 13:34:51', '2025-05-10 13:34:51'),
(1642, 1495, 1886, 'author', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1643, 1495, 1303, 'publisher', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1644, 1496, 1888, 'author', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1645, 1496, 1305, 'publisher', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1646, 1497, 1889, 'author', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1647, 1497, 1307, 'publisher', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1648, 1498, 1890, 'author', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1649, 1498, 1297, 'publisher', '2025-05-10 13:34:52', '2025-05-10 13:34:52'),
(1650, 1499, 1866, 'author', '2025-05-10 13:51:30', '2025-05-10 13:51:30'),
(1651, 1499, 1283, 'publisher', '2025-05-10 13:51:30', '2025-05-10 13:51:30'),
(1652, 1500, 1866, 'author', '2025-05-10 13:51:46', '2025-05-10 13:51:46'),
(1653, 1500, 1283, 'publisher', '2025-05-10 13:51:46', '2025-05-10 13:51:46'),
(1654, 1501, 1866, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1655, 1501, 1283, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1656, 1502, 1867, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1657, 1503, 1868, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1658, 1503, 1285, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1659, 1504, 1869, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1660, 1504, 1285, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1661, 1505, 1868, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1662, 1505, 1285, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1663, 1506, 1870, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1664, 1506, 1288, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1665, 1507, 1871, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1666, 1507, 1297, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1667, 1508, 1872, 'author', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1668, 1508, 1290, 'publisher', '2025-05-10 13:52:55', '2025-05-10 13:52:55'),
(1669, 1509, 1873, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1670, 1510, 1993, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1671, 1510, 1285, 'publisher', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1672, 1512, 1868, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1673, 1512, 1285, 'publisher', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1674, 1513, 1876, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1675, 1513, 1292, 'publisher', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1676, 1514, 1868, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1677, 1514, 1285, 'publisher', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1678, 1515, 1993, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1679, 1515, 1294, 'publisher', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1680, 1516, 1868, 'author', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1681, 1516, 1285, 'publisher', '2025-05-10 13:52:56', '2025-05-10 13:52:56'),
(1682, 1517, 1878, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1683, 1517, 1294, 'publisher', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1684, 1518, 1879, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1685, 1518, 1839, 'publisher', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1686, 1519, 1867, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1687, 1519, 1297, 'publisher', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1688, 1520, 1880, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1689, 1520, 1285, 'publisher', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1690, 1521, 1881, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1691, 1521, 1294, 'publisher', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1692, 1522, 1882, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1693, 1523, 1868, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1694, 1523, 1285, 'publisher', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1695, 1524, 1993, 'author', '2025-05-10 13:52:57', '2025-05-10 13:52:57'),
(1696, 1525, 1884, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1697, 1525, 1292, 'publisher', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1698, 1526, 1885, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1699, 1526, 1301, 'publisher', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1700, 1527, 1886, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1701, 1527, 1303, 'publisher', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1702, 1528, 1993, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1703, 1529, 1888, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1704, 1529, 1305, 'publisher', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1705, 1530, 1889, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1706, 1530, 1307, 'publisher', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1707, 1531, 1890, 'author', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1708, 1531, 1297, 'publisher', '2025-05-10 13:52:58', '2025-05-10 13:52:58'),
(1709, 1532, 1866, 'author', '2025-05-10 13:53:09', '2025-05-10 13:53:09'),
(1710, 1532, 1283, 'publisher', '2025-05-10 13:53:09', '2025-05-10 13:53:09'),
(1711, 1533, 1867, 'author', '2025-05-10 13:53:09', '2025-05-10 13:53:09'),
(1712, 1534, 1868, 'author', '2025-05-10 13:53:09', '2025-05-10 13:53:09'),
(1713, 1534, 1285, 'publisher', '2025-05-10 13:53:09', '2025-05-10 13:53:09'),
(1714, 1535, 1869, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1715, 1535, 1285, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1716, 1536, 1868, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1717, 1536, 1285, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1718, 1537, 1870, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1719, 1537, 1288, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1720, 1538, 1871, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1721, 1538, 1297, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1722, 1539, 1872, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1723, 1539, 1290, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1724, 1540, 1873, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1725, 1541, 1993, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1726, 1541, 1285, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1727, 1543, 1868, 'author', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1728, 1543, 1285, 'publisher', '2025-05-10 13:53:10', '2025-05-10 13:53:10'),
(1729, 1544, 1876, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1730, 1544, 1292, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1731, 1545, 1868, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1732, 1545, 1285, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1733, 1546, 1993, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1734, 1546, 1294, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1735, 1547, 1868, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1736, 1547, 1285, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1737, 1548, 1878, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1738, 1548, 1294, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1739, 1549, 1879, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1740, 1549, 1839, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1741, 1550, 1867, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1742, 1550, 1297, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1743, 1551, 1880, 'author', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1744, 1551, 1285, 'publisher', '2025-05-10 13:53:11', '2025-05-10 13:53:11'),
(1745, 1552, 1881, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1746, 1552, 1294, 'publisher', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1747, 1553, 1882, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1748, 1554, 1868, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1749, 1554, 1285, 'publisher', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1750, 1555, 1993, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1751, 1556, 1884, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1752, 1556, 1292, 'publisher', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1753, 1557, 1885, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1754, 1557, 1301, 'publisher', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1755, 1558, 1886, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1756, 1558, 1303, 'publisher', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1757, 1559, 1993, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1758, 1560, 1888, 'author', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1759, 1560, 1305, 'publisher', '2025-05-10 13:53:12', '2025-05-10 13:53:12'),
(1760, 1561, 1889, 'author', '2025-05-10 13:53:13', '2025-05-10 13:53:13'),
(1761, 1561, 1307, 'publisher', '2025-05-10 13:53:13', '2025-05-10 13:53:13'),
(1762, 1562, 1890, 'author', '2025-05-10 13:53:13', '2025-05-10 13:53:13'),
(1763, 1562, 1297, 'publisher', '2025-05-10 13:53:13', '2025-05-10 13:53:13'),
(1764, 1563, 1866, 'author', '2025-05-10 14:09:13', '2025-05-10 14:09:13'),
(1765, 1563, 1283, 'publisher', '2025-05-10 14:09:13', '2025-05-10 14:09:13'),
(1766, 1564, 1867, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1767, 1565, 1868, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1768, 1565, 1285, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1769, 1566, 1869, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1770, 1566, 1285, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1771, 1567, 1868, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1772, 1567, 1285, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1773, 1568, 1870, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1774, 1568, 1288, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1775, 1569, 1871, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1776, 1569, 1297, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1777, 1570, 1872, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1778, 1570, 1290, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1779, 1571, 1995, 'author', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1780, 1571, 1285, 'publisher', '2025-05-10 14:09:14', '2025-05-10 14:09:14'),
(1781, 1573, 1868, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1782, 1573, 1285, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1783, 1574, 1876, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1784, 1574, 1292, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1785, 1575, 1868, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1786, 1575, 1285, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1787, 1576, 1868, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1788, 1576, 1285, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1789, 1577, 1878, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1790, 1577, 1294, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1791, 1578, 1879, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1792, 1578, 1839, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1793, 1579, 1867, 'author', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1794, 1579, 1297, 'publisher', '2025-05-10 14:09:15', '2025-05-10 14:09:15'),
(1795, 1580, 1880, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1796, 1580, 1285, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1797, 1581, 1881, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1798, 1581, 1294, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1799, 1582, 1882, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1800, 1582, 1998, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1801, 1583, 1868, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1802, 1583, 1285, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1803, 1584, 2000, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1804, 1584, 1999, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1805, 1585, 1884, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1806, 1585, 1292, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1807, 1586, 1885, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1808, 1586, 1301, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1809, 1587, 1886, 'author', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1810, 1587, 1303, 'publisher', '2025-05-10 14:09:16', '2025-05-10 14:09:16'),
(1811, 1588, 2002, 'author', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1812, 1588, 2001, 'publisher', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1813, 1589, 1888, 'author', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1814, 1589, 1305, 'publisher', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1815, 1590, 1889, 'author', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1816, 1590, 1307, 'publisher', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1817, 1591, 1890, 'author', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1818, 1591, 1297, 'publisher', '2025-05-10 14:09:17', '2025-05-10 14:09:17'),
(1819, 1592, 1866, 'author', '2025-05-10 14:09:23', '2025-05-10 14:09:23'),
(1820, 1592, 1283, 'publisher', '2025-05-10 14:09:23', '2025-05-10 14:09:23'),
(1821, 1593, 1867, 'author', '2025-05-10 14:09:23', '2025-05-10 14:09:23'),
(1822, 1594, 1868, 'author', '2025-05-10 14:09:23', '2025-05-10 14:09:23'),
(1823, 1594, 1285, 'publisher', '2025-05-10 14:09:23', '2025-05-10 14:09:23'),
(1824, 1595, 1869, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1825, 1595, 1285, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1826, 1596, 1868, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1827, 1596, 1285, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1828, 1597, 1870, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1829, 1597, 1288, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1830, 1598, 1871, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1831, 1598, 1297, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1832, 1599, 1872, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1833, 1599, 1290, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1834, 1600, 1995, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1835, 1600, 1285, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1836, 1602, 1868, 'author', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1837, 1602, 1285, 'publisher', '2025-05-10 14:09:24', '2025-05-10 14:09:24'),
(1838, 1603, 1876, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1839, 1603, 1292, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1840, 1604, 1868, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1841, 1604, 1285, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1842, 1605, 1868, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1843, 1605, 1285, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1844, 1606, 1878, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1845, 1606, 1294, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1846, 1607, 1879, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1847, 1607, 1839, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1848, 1608, 1867, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1849, 1608, 1297, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1850, 1609, 1880, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1851, 1609, 1285, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1852, 1610, 1881, 'author', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1853, 1610, 1294, 'publisher', '2025-05-10 14:09:25', '2025-05-10 14:09:25'),
(1854, 1611, 1882, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1855, 1611, 1998, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1856, 1612, 1868, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1857, 1612, 1285, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1858, 1613, 2000, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1859, 1613, 1999, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1860, 1614, 1884, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1861, 1614, 1292, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1862, 1615, 1885, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1863, 1615, 1301, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1864, 1616, 1886, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1865, 1616, 1303, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1866, 1617, 2002, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1867, 1617, 2001, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1868, 1618, 1888, 'author', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1869, 1618, 1305, 'publisher', '2025-05-10 14:09:26', '2025-05-10 14:09:26'),
(1870, 1619, 1889, 'author', '2025-05-10 14:09:27', '2025-05-10 14:09:27'),
(1871, 1619, 1307, 'publisher', '2025-05-10 14:09:27', '2025-05-10 14:09:27'),
(1872, 1620, 1890, 'author', '2025-05-10 14:09:27', '2025-05-10 14:09:27'),
(1873, 1620, 1297, 'publisher', '2025-05-10 14:09:27', '2025-05-10 14:09:27'),
(1874, 1621, 1284, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1875, 1621, 1283, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1876, 1622, 977, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1877, 1623, 1286, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1878, 1623, 1285, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1879, 1624, 1287, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1880, 1624, 1285, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1881, 1625, 1286, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1882, 1625, 1285, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1883, 1626, 1289, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1884, 1626, 1288, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1885, 1627, 986, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1886, 1627, 1297, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1887, 1628, 1291, 'author', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1888, 1628, 1290, 'publisher', '2025-05-10 14:25:00', '2025-05-10 14:25:00'),
(1889, 1630, 1286, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1890, 1630, 1285, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1891, 1631, 1293, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1892, 1631, 1292, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1893, 1632, 1286, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1894, 1632, 1285, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1895, 1633, 1294, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1896, 1633, 1294, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1897, 1634, 1286, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1898, 1634, 1285, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1899, 1635, 1296, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1900, 1635, 1294, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1901, 1636, 1003, 'author', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1902, 1636, 1839, 'publisher', '2025-05-10 14:25:01', '2025-05-10 14:25:01'),
(1903, 1637, 977, 'author', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1904, 1637, 1297, 'publisher', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1905, 1638, 1298, 'author', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1906, 1638, 1285, 'publisher', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1907, 1639, 1299, 'author', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1908, 1639, 1294, 'publisher', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1909, 1640, 1286, 'author', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1910, 1640, 1285, 'publisher', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1911, 1641, 1300, 'author', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1912, 1641, 1292, 'publisher', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1913, 1642, 1302, 'author', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1914, 1642, 1301, 'publisher', '2025-05-10 14:25:02', '2025-05-10 14:25:02'),
(1915, 1643, 1304, 'author', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1916, 1643, 1303, 'publisher', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1917, 1644, 1306, 'author', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1918, 1644, 1305, 'publisher', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1919, 1645, 1308, 'author', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1920, 1645, 1307, 'publisher', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1921, 1646, 1309, 'author', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1922, 1646, 1297, 'publisher', '2025-05-10 14:25:03', '2025-05-10 14:25:03'),
(1923, 1647, 1284, 'author', '2025-05-10 14:25:06', '2025-05-10 14:25:06'),
(1924, 1647, 1283, 'publisher', '2025-05-10 14:25:06', '2025-05-10 14:25:06'),
(1925, 1648, 977, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1926, 1649, 1286, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1927, 1649, 1285, 'publisher', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1928, 1650, 1287, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1929, 1650, 1285, 'publisher', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1930, 1651, 1286, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1931, 1651, 1285, 'publisher', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1932, 1652, 1289, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1933, 1652, 1288, 'publisher', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1934, 1653, 986, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1935, 1653, 1297, 'publisher', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1936, 1654, 1291, 'author', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1937, 1654, 1290, 'publisher', '2025-05-10 14:25:07', '2025-05-10 14:25:07'),
(1938, 1656, 1286, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1939, 1656, 1285, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1940, 1657, 1293, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1941, 1657, 1292, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1942, 1658, 1286, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1943, 1658, 1285, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1944, 1659, 1294, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1945, 1659, 1294, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1946, 1660, 1286, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1947, 1660, 1285, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1948, 1661, 1296, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1949, 1661, 1294, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1950, 1662, 1003, 'author', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1951, 1662, 1839, 'publisher', '2025-05-10 14:25:08', '2025-05-10 14:25:08'),
(1952, 1663, 977, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1953, 1663, 1297, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1954, 1664, 1298, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1955, 1664, 1285, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1956, 1665, 1299, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1957, 1665, 1294, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1958, 1666, 1286, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1959, 1666, 1285, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1960, 1667, 1300, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1961, 1667, 1292, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1962, 1668, 1302, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1963, 1668, 1301, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1964, 1669, 1304, 'author', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1965, 1669, 1303, 'publisher', '2025-05-10 14:25:09', '2025-05-10 14:25:09'),
(1966, 1670, 1306, 'author', '2025-05-10 14:25:10', '2025-05-10 14:25:10'),
(1967, 1670, 1305, 'publisher', '2025-05-10 14:25:10', '2025-05-10 14:25:10'),
(1968, 1671, 1308, 'author', '2025-05-10 14:25:10', '2025-05-10 14:25:10'),
(1969, 1671, 1307, 'publisher', '2025-05-10 14:25:10', '2025-05-10 14:25:10'),
(1970, 1672, 1309, 'author', '2025-05-10 14:25:10', '2025-05-10 14:25:10'),
(1971, 1672, 1297, 'publisher', '2025-05-10 14:25:10', '2025-05-10 14:25:10'),
(1972, 1673, 1284, 'author', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1973, 1673, 1283, 'publisher', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1974, 1674, 977, 'author', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1975, 1675, 1286, 'author', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1976, 1675, 1285, 'publisher', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1977, 1676, 1287, 'author', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1978, 1676, 1285, 'publisher', '2025-05-10 14:25:15', '2025-05-10 14:25:15'),
(1979, 1677, 1286, 'author', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1980, 1677, 1285, 'publisher', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1981, 1678, 1289, 'author', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1982, 1678, 1288, 'publisher', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1983, 1679, 986, 'author', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1984, 1679, 1297, 'publisher', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1985, 1680, 1291, 'author', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1986, 1680, 1290, 'publisher', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1987, 1682, 1286, 'author', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1988, 1682, 1285, 'publisher', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1989, 1683, 1293, 'author', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1990, 1683, 1292, 'publisher', '2025-05-10 14:25:16', '2025-05-10 14:25:16'),
(1991, 1684, 1286, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1992, 1684, 1285, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1993, 1685, 1294, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1994, 1685, 1294, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1995, 1686, 1286, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1996, 1686, 1285, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1997, 1687, 1296, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1998, 1687, 1294, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(1999, 1688, 1003, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2000, 1688, 1839, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2001, 1689, 977, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2002, 1689, 1297, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2003, 1690, 1298, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2004, 1690, 1285, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2005, 1691, 1299, 'author', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2006, 1691, 1294, 'publisher', '2025-05-10 14:25:17', '2025-05-10 14:25:17'),
(2007, 1692, 1286, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2008, 1692, 1285, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2009, 1693, 1300, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2010, 1693, 1292, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2011, 1694, 1302, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2012, 1694, 1301, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2013, 1695, 1304, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2014, 1695, 1303, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2015, 1696, 1306, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2016, 1696, 1305, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2017, 1697, 1308, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2018, 1697, 1307, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2019, 1698, 1309, 'author', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2020, 1698, 1297, 'publisher', '2025-05-10 14:25:18', '2025-05-10 14:25:18'),
(2021, 1699, 1284, 'author', '2025-05-10 14:34:18', '2025-05-10 14:34:18'),
(2022, 1699, 1283, 'publisher', '2025-05-10 14:34:18', '2025-05-10 14:34:18'),
(2023, 1700, 977, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2024, 1701, 1286, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2025, 1701, 1285, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2026, 1702, 1287, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2027, 1702, 1285, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2028, 1703, 1286, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2029, 1703, 1285, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2030, 1704, 1289, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2031, 1704, 1288, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2032, 1705, 986, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2033, 1705, 1297, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2034, 1706, 1291, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2035, 1706, 1290, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2036, 1707, 989, 'author', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2037, 1707, 2024, 'publisher', '2025-05-10 14:34:19', '2025-05-10 14:34:19'),
(2038, 1708, 2025, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2039, 1708, 1285, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2040, 1710, 1286, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2041, 1710, 1285, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2042, 1711, 1293, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2043, 1711, 1292, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2044, 1712, 1286, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2045, 1712, 1285, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2046, 1713, 1294, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2047, 1713, 1294, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2048, 1714, 1286, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2049, 1714, 1285, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2050, 1715, 1296, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2051, 1715, 1294, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2052, 1716, 1003, 'author', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2053, 1716, 1839, 'publisher', '2025-05-10 14:34:20', '2025-05-10 14:34:20'),
(2054, 1717, 977, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2055, 1717, 1297, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2056, 1718, 1298, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2057, 1718, 1285, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2058, 1719, 1299, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2059, 1719, 1294, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2060, 1720, 1009, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2061, 1720, 2027, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2062, 1721, 1286, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2063, 1721, 1285, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2064, 1722, 2029, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21');
INSERT INTO `book_authors` (`id`, `directory_item_id`, `author_id`, `role`, `created_at`, `updated_at`) VALUES
(2065, 1722, 2028, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2066, 1723, 1300, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2067, 1723, 1292, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2068, 1724, 1302, 'author', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2069, 1724, 1301, 'publisher', '2025-05-10 14:34:21', '2025-05-10 14:34:21'),
(2070, 1725, 1304, 'author', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2071, 1725, 1303, 'publisher', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2072, 1726, 2031, 'author', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2073, 1726, 2030, 'publisher', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2074, 1727, 1306, 'author', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2075, 1727, 1305, 'publisher', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2076, 1728, 1308, 'author', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2077, 1728, 1307, 'publisher', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2078, 1729, 1309, 'author', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2079, 1729, 1297, 'publisher', '2025-05-10 14:34:22', '2025-05-10 14:34:22'),
(2080, 1730, 1284, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2081, 1730, 1283, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2082, 1731, 977, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2083, 1732, 1286, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2084, 1732, 1285, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2085, 1733, 1287, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2086, 1733, 1285, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2087, 1734, 1286, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2088, 1734, 1285, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2089, 1735, 1289, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2090, 1735, 1288, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2091, 1736, 986, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2092, 1736, 1297, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2093, 1737, 1291, 'author', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2094, 1737, 1290, 'publisher', '2025-05-10 14:34:26', '2025-05-10 14:34:26'),
(2095, 1738, 989, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2096, 1738, 2024, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2097, 1739, 2025, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2098, 1739, 1285, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2099, 1741, 1286, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2100, 1741, 1285, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2101, 1742, 1293, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2102, 1742, 1292, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2103, 1743, 1286, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2104, 1743, 1285, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2105, 1744, 1294, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2106, 1744, 1294, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2107, 1745, 1286, 'author', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2108, 1745, 1285, 'publisher', '2025-05-10 14:34:27', '2025-05-10 14:34:27'),
(2109, 1746, 1296, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2110, 1746, 1294, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2111, 1747, 1003, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2112, 1747, 1839, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2113, 1748, 977, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2114, 1748, 1297, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2115, 1749, 1298, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2116, 1749, 1285, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2117, 1750, 1299, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2118, 1750, 1294, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2119, 1751, 1009, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2120, 1751, 2027, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2121, 1752, 1286, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2122, 1752, 1285, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2123, 1753, 2029, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2124, 1753, 2028, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2125, 1754, 1300, 'author', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2126, 1754, 1292, 'publisher', '2025-05-10 14:34:28', '2025-05-10 14:34:28'),
(2127, 1755, 1302, 'author', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2128, 1755, 1301, 'publisher', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2129, 1756, 1304, 'author', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2130, 1756, 1303, 'publisher', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2131, 1757, 2031, 'author', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2132, 1757, 2030, 'publisher', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2133, 1758, 1306, 'author', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2134, 1758, 1305, 'publisher', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2135, 1759, 1308, 'author', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2136, 1759, 1307, 'publisher', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2137, 1760, 1309, 'author', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2138, 1760, 1297, 'publisher', '2025-05-10 14:34:29', '2025-05-10 14:34:29'),
(2139, 1761, 1284, 'author', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2140, 1761, 1283, 'publisher', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2141, 1762, 977, 'author', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2142, 1763, 1286, 'author', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2143, 1763, 1285, 'publisher', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2144, 1764, 1287, 'author', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2145, 1764, 1285, 'publisher', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2146, 1765, 1286, 'author', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2147, 1765, 1285, 'publisher', '2025-05-10 14:34:35', '2025-05-10 14:34:35'),
(2148, 1766, 1289, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2149, 1766, 1288, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2150, 1767, 986, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2151, 1767, 1297, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2152, 1768, 1291, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2153, 1768, 1290, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2154, 1769, 989, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2155, 1769, 2024, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2156, 1770, 2025, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2157, 1770, 1285, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2158, 1772, 1286, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2159, 1772, 1285, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2160, 1773, 1293, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2161, 1773, 1292, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2162, 1774, 1286, 'author', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2163, 1774, 1285, 'publisher', '2025-05-10 14:34:36', '2025-05-10 14:34:36'),
(2164, 1775, 1294, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2165, 1775, 1294, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2166, 1776, 1286, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2167, 1776, 1285, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2168, 1777, 1296, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2169, 1777, 1294, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2170, 1778, 1003, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2171, 1778, 1839, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2172, 1779, 977, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2173, 1779, 1297, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2174, 1780, 1298, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2175, 1780, 1285, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2176, 1781, 1299, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2177, 1781, 1294, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2178, 1782, 1009, 'author', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2179, 1782, 2027, 'publisher', '2025-05-10 14:34:37', '2025-05-10 14:34:37'),
(2180, 1783, 1286, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2181, 1783, 1285, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2182, 1784, 2029, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2183, 1784, 2028, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2184, 1785, 1300, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2185, 1785, 1292, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2186, 1786, 1302, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2187, 1786, 1301, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2188, 1787, 1304, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2189, 1787, 1303, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2190, 1788, 2031, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2191, 1788, 2030, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2192, 1789, 1306, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2193, 1789, 1305, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2194, 1790, 1308, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2195, 1790, 1307, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2196, 1791, 1309, 'author', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2197, 1791, 1297, 'publisher', '2025-05-10 14:34:38', '2025-05-10 14:34:38'),
(2198, 1792, 1284, 'author', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2199, 1792, 1283, 'publisher', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2200, 1793, 977, 'author', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2201, 1794, 1286, 'author', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2202, 1794, 1285, 'publisher', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2203, 1795, 1287, 'author', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2204, 1795, 1285, 'publisher', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2205, 1796, 1286, 'author', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2206, 1796, 1285, 'publisher', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2207, 1797, 1289, 'author', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2208, 1797, 1288, 'publisher', '2025-05-10 14:45:38', '2025-05-10 14:45:38'),
(2209, 1798, 986, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2210, 1798, 1297, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2211, 1799, 1291, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2212, 1799, 1290, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2213, 1800, 989, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2214, 1800, 2024, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2215, 1801, 2025, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2216, 1801, 1285, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2217, 1803, 1286, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2218, 1803, 1285, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2219, 1804, 1293, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2220, 1804, 1292, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2221, 1805, 1286, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2222, 1805, 1285, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2223, 1806, 1294, 'author', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2224, 1806, 1294, 'publisher', '2025-05-10 14:45:39', '2025-05-10 14:45:39'),
(2225, 1807, 1286, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2226, 1807, 1285, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2227, 1808, 1296, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2228, 1808, 1294, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2229, 1809, 1003, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2230, 1809, 1839, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2231, 1810, 977, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2232, 1810, 1297, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2233, 1811, 1298, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2234, 1811, 1285, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2235, 1812, 1299, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2236, 1812, 1294, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2237, 1813, 1009, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2238, 1813, 2027, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2239, 1814, 1286, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2240, 1814, 1285, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2241, 1815, 2029, 'author', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2242, 1815, 2028, 'publisher', '2025-05-10 14:45:40', '2025-05-10 14:45:40'),
(2243, 1816, 1300, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2244, 1816, 1292, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2245, 1817, 1302, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2246, 1817, 1301, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2247, 1818, 1304, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2248, 1818, 1303, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2249, 1819, 2031, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2250, 1819, 2030, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2251, 1820, 1306, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2252, 1820, 1305, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2253, 1821, 1308, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2254, 1821, 1307, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2255, 1822, 1309, 'author', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2256, 1822, 1297, 'publisher', '2025-05-10 14:45:41', '2025-05-10 14:45:41'),
(2257, 1823, 1284, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2258, 1823, 1283, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2259, 1824, 977, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2260, 1825, 1286, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2261, 1825, 1285, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2262, 1826, 1287, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2263, 1826, 1285, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2264, 1827, 1286, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2265, 1827, 1285, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2266, 1828, 1289, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2267, 1828, 1288, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2268, 1829, 986, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2269, 1829, 1297, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2270, 1830, 1291, 'author', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2271, 1830, 1290, 'publisher', '2025-05-10 14:45:48', '2025-05-10 14:45:48'),
(2272, 1831, 989, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2273, 1831, 2024, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2274, 1832, 2025, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2275, 1832, 1285, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2276, 1834, 1286, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2277, 1834, 1285, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2278, 1835, 1293, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2279, 1835, 1292, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2280, 1836, 1286, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2281, 1836, 1285, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2282, 1837, 1294, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2283, 1837, 1294, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2284, 1838, 1286, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2285, 1838, 1285, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2286, 1839, 1296, 'author', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2287, 1839, 1294, 'publisher', '2025-05-10 14:45:49', '2025-05-10 14:45:49'),
(2288, 1840, 1003, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2289, 1840, 1839, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2290, 1841, 977, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2291, 1841, 1297, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2292, 1842, 1298, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2293, 1842, 1285, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2294, 1843, 1299, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2295, 1843, 1294, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2296, 1844, 1009, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2297, 1844, 2027, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2298, 1845, 1286, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2299, 1845, 1285, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2300, 1846, 2029, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2301, 1846, 2028, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2302, 1847, 1300, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2303, 1847, 1292, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2304, 1848, 1302, 'author', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2305, 1848, 1301, 'publisher', '2025-05-10 14:45:50', '2025-05-10 14:45:50'),
(2306, 1849, 1304, 'author', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2307, 1849, 1303, 'publisher', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2308, 1850, 2031, 'author', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2309, 1850, 2030, 'publisher', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2310, 1851, 1306, 'author', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2311, 1851, 1305, 'publisher', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2312, 1852, 1308, 'author', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2313, 1852, 1307, 'publisher', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2314, 1853, 1309, 'author', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2315, 1853, 1297, 'publisher', '2025-05-10 14:45:51', '2025-05-10 14:45:51'),
(2316, 1854, 2037, 'author', '2025-05-10 15:34:15', '2025-05-10 15:34:15'),
(2317, 1854, 2036, 'publisher', '2025-05-10 15:34:15', '2025-05-10 15:34:15'),
(2318, 1855, 2038, 'author', '2025-05-10 15:34:15', '2025-05-10 15:34:15'),
(2319, 1856, 2040, 'author', '2025-05-10 15:34:15', '2025-05-10 15:34:15'),
(2320, 1856, 2039, 'publisher', '2025-05-10 15:34:15', '2025-05-10 15:34:15'),
(2321, 1857, 2041, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2322, 1857, 2039, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2323, 1858, 2040, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2324, 1858, 2039, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2325, 1859, 2043, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2326, 1859, 2042, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2327, 1860, 2045, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2328, 1860, 2044, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2329, 1861, 2047, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2330, 1861, 2046, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2331, 1862, 2049, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2332, 1862, 2048, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2333, 1863, 2050, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2334, 1863, 2039, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2335, 1865, 2040, 'author', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2336, 1865, 2039, 'publisher', '2025-05-10 15:34:16', '2025-05-10 15:34:16'),
(2337, 1866, 2054, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2338, 1866, 2053, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2339, 1867, 2040, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2340, 1867, 2039, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2341, 1868, 2055, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2342, 1868, 2055, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2343, 1869, 2040, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2344, 1869, 2039, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2345, 1870, 2056, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2346, 1870, 2055, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2347, 1871, 2058, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2348, 1871, 2057, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2349, 1872, 2038, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2350, 1872, 2044, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2351, 1873, 2059, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2352, 1873, 2039, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2353, 1874, 2060, 'author', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2354, 1874, 2055, 'publisher', '2025-05-10 15:34:17', '2025-05-10 15:34:17'),
(2355, 1875, 2062, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2356, 1875, 2061, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2357, 1876, 2040, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2358, 1876, 2039, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2359, 1877, 2064, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2360, 1877, 2063, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2361, 1878, 2065, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2362, 1878, 2053, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2363, 1879, 2067, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2364, 1879, 2066, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2365, 1880, 2069, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2366, 1880, 2068, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2367, 1881, 2071, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2368, 1881, 2070, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2369, 1882, 2073, 'author', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2370, 1882, 2072, 'publisher', '2025-05-10 15:34:18', '2025-05-10 15:34:18'),
(2371, 1883, 2075, 'author', '2025-05-10 15:34:19', '2025-05-10 15:34:19'),
(2372, 1883, 2074, 'publisher', '2025-05-10 15:34:19', '2025-05-10 15:34:19'),
(2373, 1884, 2076, 'author', '2025-05-10 15:34:19', '2025-05-10 15:34:19'),
(2374, 1884, 2044, 'publisher', '2025-05-10 15:34:19', '2025-05-10 15:34:19'),
(2375, 1885, 2037, 'author', '2025-05-10 15:34:37', '2025-05-10 15:34:37'),
(2376, 1885, 2036, 'publisher', '2025-05-10 15:34:37', '2025-05-10 15:34:37'),
(2377, 1886, 2038, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2378, 1887, 2040, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2379, 1887, 2039, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2380, 1888, 2041, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2381, 1888, 2039, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2382, 1889, 2040, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2383, 1889, 2039, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2384, 1890, 2043, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2385, 1890, 2042, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2386, 1891, 2045, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2387, 1891, 2044, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2388, 1892, 2047, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2389, 1892, 2046, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2390, 1893, 2049, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2391, 1893, 2048, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2392, 1894, 2050, 'author', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2393, 1894, 2039, 'publisher', '2025-05-10 15:34:38', '2025-05-10 15:34:38'),
(2394, 1896, 2040, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2395, 1896, 2039, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2396, 1897, 2054, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2397, 1897, 2053, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2398, 1898, 2040, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2399, 1898, 2039, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2400, 1899, 2055, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2401, 1899, 2055, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2402, 1900, 2040, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2403, 1900, 2039, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2404, 1901, 2056, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2405, 1901, 2055, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2406, 1902, 2058, 'author', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2407, 1902, 2057, 'publisher', '2025-05-10 15:34:39', '2025-05-10 15:34:39'),
(2408, 1903, 2038, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2409, 1903, 2044, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2410, 1904, 2059, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2411, 1904, 2039, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2412, 1905, 2060, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2413, 1905, 2055, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2414, 1906, 2062, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2415, 1906, 2061, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2416, 1907, 2040, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2417, 1907, 2039, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2418, 1908, 2064, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2419, 1908, 2063, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2420, 1909, 2065, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2421, 1909, 2053, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2422, 1910, 2067, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2423, 1910, 2066, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2424, 1911, 2069, 'author', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2425, 1911, 2068, 'publisher', '2025-05-10 15:34:40', '2025-05-10 15:34:40'),
(2426, 1912, 2071, 'author', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2427, 1912, 2070, 'publisher', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2428, 1913, 2073, 'author', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2429, 1913, 2072, 'publisher', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2430, 1914, 2075, 'author', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2431, 1914, 2074, 'publisher', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2432, 1915, 2076, 'author', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2433, 1915, 2044, 'publisher', '2025-05-10 15:34:41', '2025-05-10 15:34:41'),
(2434, 1916, 2037, 'author', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2435, 1916, 2036, 'publisher', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2436, 1917, 2038, 'author', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2437, 1918, 2040, 'author', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2438, 1918, 2039, 'publisher', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2439, 1919, 2041, 'author', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2440, 1919, 2039, 'publisher', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2441, 1920, 2040, 'author', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2442, 1920, 2039, 'publisher', '2025-05-10 19:11:17', '2025-05-10 19:11:17'),
(2443, 1921, 2043, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2444, 1921, 2042, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2445, 1922, 2045, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2446, 1922, 2044, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2447, 1923, 2047, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2448, 1923, 2046, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2449, 1924, 2049, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2450, 1924, 2048, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2451, 1925, 2050, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2452, 1925, 2039, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2453, 1927, 2040, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2454, 1927, 2039, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2455, 1928, 2054, 'author', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2456, 1928, 2053, 'publisher', '2025-05-10 19:11:18', '2025-05-10 19:11:18'),
(2457, 1929, 2040, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2458, 1929, 2039, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2459, 1930, 2055, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2460, 1930, 2055, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2461, 1931, 2040, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2462, 1931, 2039, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2463, 1932, 2056, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2464, 1932, 2055, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2465, 1933, 2058, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2466, 1933, 2057, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2467, 1934, 2038, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2468, 1934, 2044, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2469, 1935, 2059, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2470, 1935, 2039, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2471, 1936, 2060, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2472, 1936, 2055, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2473, 1937, 2062, 'author', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2474, 1937, 2061, 'publisher', '2025-05-10 19:11:19', '2025-05-10 19:11:19'),
(2475, 1938, 2040, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2476, 1938, 2039, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2477, 1939, 2064, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2478, 1939, 2063, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2479, 1940, 2065, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2480, 1940, 2053, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2481, 1941, 2067, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2482, 1941, 2066, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2483, 1942, 2069, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2484, 1942, 2068, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2485, 1943, 2071, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2486, 1943, 2070, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2487, 1944, 2073, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2488, 1944, 2072, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2489, 1945, 2075, 'author', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2490, 1945, 2074, 'publisher', '2025-05-10 19:11:20', '2025-05-10 19:11:20'),
(2491, 1946, 2076, 'author', '2025-05-10 19:11:21', '2025-05-10 19:11:21'),
(2492, 1946, 2044, 'publisher', '2025-05-10 19:11:21', '2025-05-10 19:11:21'),
(2493, 1947, 2108, 'author', '2025-05-10 19:15:47', '2025-05-10 19:15:47'),
(2494, 1947, 2107, 'publisher', '2025-05-10 19:15:47', '2025-05-10 19:15:47'),
(2495, 1948, 2109, 'author', '2025-05-10 19:15:48', '2025-05-10 19:15:48'),
(2496, 1949, 2111, 'author', '2025-05-10 19:15:50', '2025-05-10 19:15:50'),
(2497, 1949, 2110, 'publisher', '2025-05-10 19:15:50', '2025-05-10 19:15:50'),
(2498, 1950, 2112, 'author', '2025-05-10 19:15:52', '2025-05-10 19:15:52'),
(2499, 1950, 2110, 'publisher', '2025-05-10 19:15:52', '2025-05-10 19:15:52'),
(2500, 1951, 2111, 'author', '2025-05-10 19:15:53', '2025-05-10 19:15:53'),
(2501, 1951, 2110, 'publisher', '2025-05-10 19:15:53', '2025-05-10 19:15:53'),
(2502, 1952, 2114, 'author', '2025-05-10 19:15:55', '2025-05-10 19:15:55'),
(2503, 1952, 2113, 'publisher', '2025-05-10 19:15:55', '2025-05-10 19:15:55'),
(2504, 1953, 2116, 'author', '2025-05-10 19:15:58', '2025-05-10 19:15:58'),
(2505, 1953, 2115, 'publisher', '2025-05-10 19:15:58', '2025-05-10 19:15:58'),
(2506, 1954, 2118, 'author', '2025-05-10 19:15:59', '2025-05-10 19:15:59'),
(2507, 1954, 2117, 'publisher', '2025-05-10 19:15:59', '2025-05-10 19:15:59'),
(2508, 1955, 2120, 'author', '2025-05-10 19:16:00', '2025-05-10 19:16:00'),
(2509, 1955, 2119, 'publisher', '2025-05-10 19:16:00', '2025-05-10 19:16:00'),
(2510, 1956, 2121, 'author', '2025-05-10 19:16:02', '2025-05-10 19:16:02'),
(2511, 1956, 2110, 'publisher', '2025-05-10 19:16:02', '2025-05-10 19:16:02'),
(2512, 1957, 2123, 'author', '2025-05-10 19:16:03', '2025-05-10 19:16:03'),
(2513, 1957, 2122, 'publisher', '2025-05-10 19:16:03', '2025-05-10 19:16:03'),
(2514, 1958, 2111, 'author', '2025-05-10 19:16:04', '2025-05-10 19:16:04'),
(2515, 1958, 2110, 'publisher', '2025-05-10 19:16:04', '2025-05-10 19:16:04'),
(2516, 1959, 2125, 'author', '2025-05-10 19:16:07', '2025-05-10 19:16:07'),
(2517, 1959, 2124, 'publisher', '2025-05-10 19:16:07', '2025-05-10 19:16:07'),
(2518, 1960, 2111, 'author', '2025-05-10 19:16:09', '2025-05-10 19:16:09'),
(2519, 1960, 2110, 'publisher', '2025-05-10 19:16:09', '2025-05-10 19:16:09'),
(2520, 1961, 2126, 'author', '2025-05-10 19:16:11', '2025-05-10 19:16:11'),
(2521, 1961, 2126, 'publisher', '2025-05-10 19:16:11', '2025-05-10 19:16:11'),
(2522, 1962, 2111, 'author', '2025-05-10 19:16:13', '2025-05-10 19:16:13'),
(2523, 1962, 2110, 'publisher', '2025-05-10 19:16:13', '2025-05-10 19:16:13'),
(2524, 1963, 2127, 'author', '2025-05-10 19:16:15', '2025-05-10 19:16:15'),
(2525, 1963, 2126, 'publisher', '2025-05-10 19:16:15', '2025-05-10 19:16:15'),
(2526, 1964, 2129, 'author', '2025-05-10 19:16:17', '2025-05-10 19:16:17'),
(2527, 1964, 2128, 'publisher', '2025-05-10 19:16:17', '2025-05-10 19:16:17'),
(2528, 1965, 2109, 'author', '2025-05-10 19:16:20', '2025-05-10 19:16:20'),
(2529, 1965, 2115, 'publisher', '2025-05-10 19:16:20', '2025-05-10 19:16:20'),
(2530, 1966, 2130, 'author', '2025-05-10 19:16:22', '2025-05-10 19:16:22'),
(2531, 1966, 2110, 'publisher', '2025-05-10 19:16:22', '2025-05-10 19:16:22'),
(2532, 1967, 2131, 'author', '2025-05-10 19:16:25', '2025-05-10 19:16:25'),
(2533, 1967, 2126, 'publisher', '2025-05-10 19:16:25', '2025-05-10 19:16:25'),
(2534, 1968, 2133, 'author', '2025-05-10 19:16:26', '2025-05-10 19:16:26'),
(2535, 1968, 2132, 'publisher', '2025-05-10 19:16:26', '2025-05-10 19:16:26'),
(2536, 1969, 2111, 'author', '2025-05-10 19:16:28', '2025-05-10 19:16:28'),
(2537, 1969, 2110, 'publisher', '2025-05-10 19:16:28', '2025-05-10 19:16:28'),
(2538, 1970, 2135, 'author', '2025-05-10 19:16:31', '2025-05-10 19:16:31'),
(2539, 1970, 2134, 'publisher', '2025-05-10 19:16:31', '2025-05-10 19:16:31'),
(2540, 1971, 2136, 'author', '2025-05-10 19:16:34', '2025-05-10 19:16:34'),
(2541, 1971, 2124, 'publisher', '2025-05-10 19:16:34', '2025-05-10 19:16:34'),
(2542, 1972, 2138, 'author', '2025-05-10 19:16:36', '2025-05-10 19:16:36'),
(2543, 1972, 2137, 'publisher', '2025-05-10 19:16:36', '2025-05-10 19:16:36'),
(2544, 1973, 2140, 'author', '2025-05-10 19:16:38', '2025-05-10 19:16:38'),
(2545, 1973, 2139, 'publisher', '2025-05-10 19:16:38', '2025-05-10 19:16:38'),
(2546, 1974, 2142, 'author', '2025-05-10 19:16:40', '2025-05-10 19:16:40'),
(2547, 1974, 2141, 'publisher', '2025-05-10 19:16:40', '2025-05-10 19:16:40'),
(2548, 1975, 2144, 'author', '2025-05-10 19:16:47', '2025-05-10 19:16:47'),
(2549, 1975, 2143, 'publisher', '2025-05-10 19:16:47', '2025-05-10 19:16:47'),
(2550, 1976, 2146, 'author', '2025-05-10 19:16:52', '2025-05-10 19:16:52'),
(2551, 1976, 2145, 'publisher', '2025-05-10 19:16:52', '2025-05-10 19:16:52'),
(2552, 1977, 2147, 'author', '2025-05-10 19:16:53', '2025-05-10 19:16:53'),
(2553, 1977, 2115, 'publisher', '2025-05-10 19:16:53', '2025-05-10 19:16:53'),
(2554, 1978, 2149, 'author', '2025-05-10 19:28:34', '2025-05-10 19:28:34'),
(2555, 1978, 2148, 'publisher', '2025-05-10 19:28:34', '2025-05-10 19:28:34'),
(2556, 1979, 2150, 'author', '2025-05-10 19:28:37', '2025-05-10 19:28:37'),
(2557, 1980, 2152, 'author', '2025-05-10 19:28:40', '2025-05-10 19:28:40'),
(2558, 1980, 2151, 'publisher', '2025-05-10 19:28:40', '2025-05-10 19:28:40'),
(2559, 1981, 2153, 'author', '2025-05-10 19:28:43', '2025-05-10 19:28:43'),
(2560, 1981, 2151, 'publisher', '2025-05-10 19:28:43', '2025-05-10 19:28:43'),
(2561, 1982, 2152, 'author', '2025-05-10 19:28:45', '2025-05-10 19:28:45'),
(2562, 1982, 2151, 'publisher', '2025-05-10 19:28:45', '2025-05-10 19:28:45'),
(2563, 1983, 2155, 'author', '2025-05-10 19:28:48', '2025-05-10 19:28:48'),
(2564, 1983, 2154, 'publisher', '2025-05-10 19:28:48', '2025-05-10 19:28:48'),
(2565, 1984, 2157, 'author', '2025-05-10 19:28:52', '2025-05-10 19:28:52'),
(2566, 1984, 2156, 'publisher', '2025-05-10 19:28:52', '2025-05-10 19:28:52'),
(2567, 1985, 2159, 'author', '2025-05-10 19:28:55', '2025-05-10 19:28:55'),
(2568, 1985, 2158, 'publisher', '2025-05-10 19:28:55', '2025-05-10 19:28:55'),
(2569, 1986, 2161, 'author', '2025-05-10 19:28:57', '2025-05-10 19:28:57'),
(2570, 1986, 2160, 'publisher', '2025-05-10 19:28:57', '2025-05-10 19:28:57'),
(2571, 1987, 2162, 'author', '2025-05-10 19:28:59', '2025-05-10 19:28:59'),
(2572, 1987, 2151, 'publisher', '2025-05-10 19:28:59', '2025-05-10 19:28:59'),
(2573, 1988, 2164, 'author', '2025-05-10 19:29:02', '2025-05-10 19:29:02'),
(2574, 1988, 2163, 'publisher', '2025-05-10 19:29:02', '2025-05-10 19:29:02'),
(2575, 1989, 2152, 'author', '2025-05-10 19:29:04', '2025-05-10 19:29:04'),
(2576, 1989, 2151, 'publisher', '2025-05-10 19:29:04', '2025-05-10 19:29:04'),
(2577, 1990, 2166, 'author', '2025-05-10 19:29:08', '2025-05-10 19:29:08'),
(2578, 1990, 2165, 'publisher', '2025-05-10 19:29:08', '2025-05-10 19:29:08'),
(2579, 1991, 2152, 'author', '2025-05-10 19:29:10', '2025-05-10 19:29:10'),
(2580, 1991, 2151, 'publisher', '2025-05-10 19:29:10', '2025-05-10 19:29:10'),
(2581, 1992, 2167, 'author', '2025-05-10 19:29:13', '2025-05-10 19:29:13'),
(2582, 1992, 2167, 'publisher', '2025-05-10 19:29:13', '2025-05-10 19:29:13'),
(2583, 1993, 2152, 'author', '2025-05-10 19:29:16', '2025-05-10 19:29:16'),
(2584, 1993, 2151, 'publisher', '2025-05-10 19:29:16', '2025-05-10 19:29:16'),
(2585, 1994, 2168, 'author', '2025-05-10 19:29:19', '2025-05-10 19:29:19'),
(2586, 1994, 2167, 'publisher', '2025-05-10 19:29:19', '2025-05-10 19:29:19'),
(2587, 1995, 2170, 'author', '2025-05-10 19:29:22', '2025-05-10 19:29:22'),
(2588, 1995, 2169, 'publisher', '2025-05-10 19:29:22', '2025-05-10 19:29:22'),
(2589, 1996, 2150, 'author', '2025-05-10 19:29:26', '2025-05-10 19:29:26'),
(2590, 1996, 2156, 'publisher', '2025-05-10 19:29:26', '2025-05-10 19:29:26'),
(2591, 1997, 2171, 'author', '2025-05-10 19:29:29', '2025-05-10 19:29:29'),
(2592, 1997, 2151, 'publisher', '2025-05-10 19:29:29', '2025-05-10 19:29:29'),
(2593, 1998, 2172, 'author', '2025-05-10 19:29:32', '2025-05-10 19:29:32'),
(2594, 1998, 2167, 'publisher', '2025-05-10 19:29:32', '2025-05-10 19:29:32'),
(2595, 1999, 2174, 'author', '2025-05-10 19:29:35', '2025-05-10 19:29:35'),
(2596, 1999, 2173, 'publisher', '2025-05-10 19:29:35', '2025-05-10 19:29:35'),
(2597, 2000, 2152, 'author', '2025-05-10 19:29:38', '2025-05-10 19:29:38'),
(2598, 2000, 2151, 'publisher', '2025-05-10 19:29:38', '2025-05-10 19:29:38'),
(2599, 2001, 2176, 'author', '2025-05-10 19:29:41', '2025-05-10 19:29:41'),
(2600, 2001, 2175, 'publisher', '2025-05-10 19:29:41', '2025-05-10 19:29:41'),
(2601, 2002, 2177, 'author', '2025-05-10 19:29:44', '2025-05-10 19:29:44'),
(2602, 2002, 2165, 'publisher', '2025-05-10 19:29:44', '2025-05-10 19:29:44'),
(2603, 2003, 2179, 'author', '2025-05-10 19:29:47', '2025-05-10 19:29:47'),
(2604, 2003, 2178, 'publisher', '2025-05-10 19:29:47', '2025-05-10 19:29:47'),
(2605, 2004, 2181, 'author', '2025-05-10 19:29:49', '2025-05-10 19:29:49'),
(2606, 2004, 2180, 'publisher', '2025-05-10 19:29:49', '2025-05-10 19:29:49'),
(2607, 2005, 2183, 'author', '2025-05-10 19:29:51', '2025-05-10 19:29:51'),
(2608, 2005, 2182, 'publisher', '2025-05-10 19:29:51', '2025-05-10 19:29:51'),
(2609, 2006, 2185, 'author', '2025-05-10 19:29:59', '2025-05-10 19:29:59'),
(2610, 2006, 2184, 'publisher', '2025-05-10 19:29:59', '2025-05-10 19:29:59'),
(2611, 2007, 2187, 'author', '2025-05-10 19:30:06', '2025-05-10 19:30:06'),
(2612, 2007, 2186, 'publisher', '2025-05-10 19:30:06', '2025-05-10 19:30:06'),
(2613, 2008, 2188, 'author', '2025-05-10 19:30:09', '2025-05-10 19:30:09'),
(2614, 2008, 2156, 'publisher', '2025-05-10 19:30:09', '2025-05-10 19:30:09'),
(2615, 2009, 2149, 'author', '2025-05-10 21:05:05', '2025-05-10 21:05:05'),
(2616, 2009, 2148, 'publisher', '2025-05-10 21:05:05', '2025-05-10 21:05:05'),
(2617, 2010, 2149, 'author', '2025-05-10 21:05:13', '2025-05-10 21:05:13'),
(2618, 2010, 2148, 'publisher', '2025-05-10 21:05:13', '2025-05-10 21:05:13'),
(2619, 2011, 2150, 'author', '2025-05-10 21:05:15', '2025-05-10 21:05:15'),
(2620, 2012, 2152, 'author', '2025-05-10 21:05:17', '2025-05-10 21:05:17'),
(2621, 2012, 2151, 'publisher', '2025-05-10 21:05:17', '2025-05-10 21:05:17'),
(2622, 2013, 2153, 'author', '2025-05-10 21:05:20', '2025-05-10 21:05:20'),
(2623, 2013, 2151, 'publisher', '2025-05-10 21:05:20', '2025-05-10 21:05:20'),
(2624, 2014, 2152, 'author', '2025-05-10 21:05:23', '2025-05-10 21:05:23'),
(2625, 2014, 2151, 'publisher', '2025-05-10 21:05:23', '2025-05-10 21:05:23'),
(2626, 2015, 2155, 'author', '2025-05-10 21:05:26', '2025-05-10 21:05:26'),
(2627, 2015, 2154, 'publisher', '2025-05-10 21:05:26', '2025-05-10 21:05:26'),
(2628, 2016, 2157, 'author', '2025-05-10 21:05:30', '2025-05-10 21:05:30'),
(2629, 2016, 2156, 'publisher', '2025-05-10 21:05:30', '2025-05-10 21:05:30'),
(2630, 2017, 2159, 'author', '2025-05-10 21:05:33', '2025-05-10 21:05:33'),
(2631, 2017, 2158, 'publisher', '2025-05-10 21:05:33', '2025-05-10 21:05:33'),
(2632, 2018, 2161, 'author', '2025-05-10 21:05:36', '2025-05-10 21:05:36'),
(2633, 2018, 2160, 'publisher', '2025-05-10 21:05:36', '2025-05-10 21:05:36'),
(2634, 2019, 2162, 'author', '2025-05-10 21:05:38', '2025-05-10 21:05:38'),
(2635, 2019, 2151, 'publisher', '2025-05-10 21:05:38', '2025-05-10 21:05:38'),
(2636, 2020, 2164, 'author', '2025-05-10 21:05:41', '2025-05-10 21:05:41'),
(2637, 2020, 2189, 'publisher', '2025-05-10 21:05:41', '2025-05-10 21:05:41'),
(2638, 2021, 2152, 'author', '2025-05-10 21:05:43', '2025-05-10 21:05:43'),
(2639, 2021, 2151, 'publisher', '2025-05-10 21:05:43', '2025-05-10 21:05:43'),
(2640, 2022, 2166, 'author', '2025-05-10 21:05:46', '2025-05-10 21:05:46'),
(2641, 2022, 2165, 'publisher', '2025-05-10 21:05:46', '2025-05-10 21:05:46'),
(2642, 2023, 2152, 'author', '2025-05-10 21:05:49', '2025-05-10 21:05:49'),
(2643, 2023, 2151, 'publisher', '2025-05-10 21:05:49', '2025-05-10 21:05:49'),
(2644, 2024, 2167, 'author', '2025-05-10 21:05:54', '2025-05-10 21:05:54'),
(2645, 2024, 2167, 'publisher', '2025-05-10 21:05:54', '2025-05-10 21:05:54'),
(2646, 2025, 2152, 'author', '2025-05-10 21:05:57', '2025-05-10 21:05:57'),
(2647, 2025, 2151, 'publisher', '2025-05-10 21:05:57', '2025-05-10 21:05:57'),
(2648, 2026, 2168, 'author', '2025-05-10 21:06:00', '2025-05-10 21:06:00'),
(2649, 2026, 2167, 'publisher', '2025-05-10 21:06:00', '2025-05-10 21:06:00'),
(2650, 2027, 2170, 'author', '2025-05-10 21:06:04', '2025-05-10 21:06:04'),
(2651, 2027, 2169, 'publisher', '2025-05-10 21:06:04', '2025-05-10 21:06:04'),
(2652, 2028, 2150, 'author', '2025-05-10 21:06:08', '2025-05-10 21:06:08'),
(2653, 2028, 2156, 'publisher', '2025-05-10 21:06:08', '2025-05-10 21:06:08'),
(2654, 2029, 2171, 'author', '2025-05-10 21:06:12', '2025-05-10 21:06:12'),
(2655, 2029, 2151, 'publisher', '2025-05-10 21:06:12', '2025-05-10 21:06:12'),
(2656, 2030, 2172, 'author', '2025-05-10 21:06:14', '2025-05-10 21:06:14'),
(2657, 2030, 2167, 'publisher', '2025-05-10 21:06:14', '2025-05-10 21:06:14'),
(2680, 2031, 2174, 'author', '2025-05-10 21:48:51', '2025-05-10 21:48:51'),
(2682, 2032, 2152, 'author', '2025-05-10 21:48:59', '2025-05-10 21:48:59'),
(2662, 2033, 2176, 'author', '2025-05-10 21:06:23', '2025-05-10 21:06:23'),
(2663, 2033, 2175, 'publisher', '2025-05-10 21:06:23', '2025-05-10 21:06:23'),
(2664, 2034, 2177, 'author', '2025-05-10 21:06:26', '2025-05-10 21:06:26'),
(2665, 2034, 2165, 'publisher', '2025-05-10 21:06:26', '2025-05-10 21:06:26'),
(2666, 2035, 2179, 'author', '2025-05-10 21:06:29', '2025-05-10 21:06:29'),
(2667, 2035, 2178, 'publisher', '2025-05-10 21:06:29', '2025-05-10 21:06:29'),
(2668, 2036, 2181, 'author', '2025-05-10 21:06:31', '2025-05-10 21:06:31'),
(2669, 2036, 2180, 'publisher', '2025-05-10 21:06:31', '2025-05-10 21:06:31'),
(2678, 2037, 2183, 'author', '2025-05-10 21:46:55', '2025-05-10 21:46:55'),
(2672, 2038, 2185, 'author', '2025-05-10 21:06:41', '2025-05-10 21:06:41'),
(2673, 2038, 2184, 'publisher', '2025-05-10 21:06:41', '2025-05-10 21:06:41'),
(2674, 2039, 2187, 'author', '2025-05-10 21:06:47', '2025-05-10 21:06:47'),
(2675, 2039, 2186, 'publisher', '2025-05-10 21:06:47', '2025-05-10 21:06:47'),
(2676, 2040, 2188, 'author', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(2677, 2040, 2156, 'publisher', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(2679, 2037, 2182, 'publisher', '2025-05-10 21:46:55', '2025-05-10 21:46:55'),
(2681, 2031, 2173, 'publisher', '2025-05-10 21:48:51', '2025-05-10 21:48:51'),
(2683, 2032, 2151, 'publisher', '2025-05-10 21:48:59', '2025-05-10 21:48:59');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_responded` tinyint(1) DEFAULT '0',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `is_responded`, `admin_notes`, `created_at`, `updated_at`) VALUES
(4, 'David Bryan', 'david.bryan@opace.co.uk', 'feedback', 'dsdsd', 0, 'SYSTEM NOTE: This submission was flagged for suspicious activity. User Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36. IP: 88.97.164.14. Time: 2025-05-04 13:24:51', '2025-05-04 13:24:51', '2025-05-04 13:24:51');

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
  `story_id` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `directory_items`
--

INSERT INTO `directory_items` (`id`, `title`, `description`, `category_id`, `slug`, `published_at`, `website_url`, `contact_email`, `contact_phone`, `address`, `featured`, `rating`, `price_range`, `cover_url`, `is_published`, `created_at`, `updated_at`, `story_id`, `type`) VALUES
(1, 'Test Directory', 'Test directory description', 3, 'test-directory', NULL, 'http://example.com', '', '', '', 0, 4.5, 'Free', 'https://api.storiesfromtheweb.org/../../uploads/optimized/681b92b6b29d25-36717642-20c9ed6698f8d14fca050b48822c5eef-medium.webp', 1, '2025-04-26 09:17:50', '2025-05-07 18:05:42', NULL, 'general'),
(2, 'Another Directory2', 'More directory content2222222', 3, 'another-directory2', NULL, 'http://example.org2', 'david.bryan@opace.co.uk', '+447859022297', 'dfdfdf', 0, 4.0, 'Premium', 'https://api.storiesfromtheweb.org/public/uploads/681b76eef1360-boy-meets-vampire-storybook-illustration.png', 1, '2025-04-26 09:17:50', '2025-05-07 16:50:32', NULL, 'general'),
(2010, 'A Hen in the Wardrobe', 'Follow Ramzi\'s journey as he travels with his parents to Algeria to meet his Berber relatives and discover the importance of family and the beauty of his Algerian heritage. Along the way, Ramzi\'s dad starts acting strangely and sleepwalking, searching for a hen. Join Ramzi on a funny and heartwarming adventure that celebrates family and cultural identity.', 1, 'a-hen-in-the-wardrobe', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-medium.webp', 1, '2025-05-10 22:05:13', '2025-05-10 22:05:13', NULL, 'book'),
(2011, 'Coraline', 'When Coraline discovers a secret door in her new home, she unlocks a parallel world that seems too good to be true. But as Coraline delves deeper into the Other World, she realizes that everything comes with a price. This haunting and captivating tale explores themes of bravery, imagination, and the importance of appreciating what we have.', 1, 'coraline', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-medium.webp', 1, '2025-05-10 22:05:15', '2025-05-10 22:05:15', NULL, 'book'),
(2012, 'Demon Dentist', 'In this darkly humorous tale, meet Alfie, a boy who dreads going to the dentist. When a new dentist arrives in town with a terrifying secret, Alfie finds himself in a perilous situation. Join him on a thrilling and funny adventure that will make you think twice before your next dental check-up!', 1, 'demon-dentist', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-medium.webp', 1, '2025-05-10 22:05:17', '2025-05-10 22:05:17', NULL, 'book'),
(2013, 'Earwig and the Witch', 'Meet Earwig, a young orphan who is content with her life at St. Morwald\'s Home for Children until she is adopted by a witch named Bella Yaga. Earwig must use her wit and cleverness to outsmart the witch and navigate the magical world she finds herself in. This enchanting story is filled with humour, talking cats, and unexpected twists.', 1, 'earwig-and-the-witch', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-medium.webp', 1, '2025-05-10 22:05:20', '2025-05-10 22:05:20', NULL, 'book'),
(2014, 'Gangsta Granny', 'Meet Ben, a boy who discovers that his seemingly boring granny has an exciting secret life as an international jewel thief. Join Ben and his granny on a hilarious and heartwarming adventure that celebrates the power of imagination, the joy of storytelling, and the special bond between grandparents and grandchildren.', 1, 'gangsta-granny', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-medium.webp', 1, '2025-05-10 22:05:23', '2025-05-10 22:05:23', NULL, 'book'),
(2015, 'Goblins', 'Meet Skarper, a young goblin with a talent for thieving. When he stumbles upon an ancient Goblin\'s Handbook, Skarper\'s life takes an unexpected turn. Join him on a thrilling and hilarious adventure as he confronts magic, danger, and the true nature of goblinhood. This fast-paced and imaginative tale will keep you hooked from start to finish.', 1, 'goblins', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-medium.webp', 1, '2025-05-10 22:05:26', '2025-05-10 22:05:26', NULL, 'book'),
(2016, 'Harry Potter and the Philosopher’s Stone', 'Enter the magical world of Harry Potter, a young wizard who discovers he\'s destined for greatness when he enrolls at Hogwarts School of Witchcraft and Wizardry. Follow Harry and his friends Hermione and Ron as they navigate the challenges of school, unravel the mystery of the Philosopher\'s Stone, and face the ultimate battle against the dark wizard Voldemort. This beloved and enchanting tale of friendship, bravery, and the power of love captured the hearts of millions.', 1, 'harry-potter-and-the-philosopher-s-stone', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-medium.webp', 1, '2025-05-10 22:05:30', '2025-05-10 22:05:30', NULL, 'book'),
(2017, 'Holes', 'Join Stanley Yelnats as he is sent to Camp Green Lake, a juvenile detention center where the boys are forced to dig holes in the desert. As Stanley uncovers the secrets of the camp and its mysterious past, he discovers the power of friendship and the importance of personal redemption.', 1, 'holes', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-medium.webp', 1, '2025-05-10 22:05:33', '2025-05-10 22:05:33', NULL, 'book'),
(2018, 'Kidnap in the Caribbean', 'The story follows Laura, a young girl who, along with her uncle and her dog Skye, wins a trip to the Caribbean. However, the trip takes a dangerous turn when her uncle is kidnapped. With the help of her friend Tari, Laura embarks on a race against time to save her uncle. Their adventure involves battling pirates, escaping sharks, and fleeing from an eruptating volcano.', 1, 'kidnap-in-the-caribbean', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-medium.webp', 1, '2025-05-10 22:05:36', '2025-05-10 22:05:36', NULL, 'book'),
(2019, 'Little Manfred Book', 'In this heart-lifting story, follow the journey of Walter, a German survivor of the sinking of the Bismarck, as he finds himself in a British ship and is taken in by a host family. Set during the 1966 World Cup, Walter\'s touching friendship with ten-year-old Grace and the power of hope and connection will capture your heart.', 1, 'little-manfred-book', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-medium.webp', 1, '2025-05-10 22:05:38', '2025-05-10 22:05:38', NULL, 'book'),
(2020, 'Moon Pie', 'Moon Pie is a heartwarming and bittersweet novel about family, love, and pies. The story revolves around 11-year-old Martha who is used to being the responsible one in her family. Her little brother, Tug, is too small and her dad has been acting too strange. Martha takes on the role of the caregiver in the family, looking after both her brother and her father. However, as her father\'s problems become too big for her to handle, she realizes that she can\'t do it all by herself and there are people and places she can turn to for help.\n\nSimon Mason, born on 5 February 1962 in Sheffield, Yorkshire, is an English author of children\'s and adult books. He is best known for his Quigleys series for young readers. He studied English at Lady Margaret Hall, Oxford, and currently splits his time between writing at home and a part-time editorial position with David Fickling Books, an imprint of Random House and publisher of his 2011 children\'s novel, Moon Pie.', 1, 'moon-pie', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-medium.webp', 1, '2025-05-10 22:05:41', '2025-05-10 22:05:41', NULL, 'book'),
(2021, 'Mr. Stink', 'Meet Chloe, a young girl who befriends Mr. Stink, a smelly and surprisingly kind homeless man she encounters in the park. Together, they embark on a heartwarming adventure that explores the themes of friendship, empathy, and the importance of seeing beyond appearances. This touching story will leave you with a smile on your face.', 1, 'mr-stink', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-medium.webp', 1, '2025-05-10 22:05:43', '2025-05-10 22:05:43', NULL, 'book'),
(2022, 'Opal Moonbaby', 'Martha has always thought that friends are overrated, until she meets Opal Moonbaby. Opal is a strange, furry creature from another planet on a mission to understand humans and make a friend. Together, they embark on an exciting adventure that will teach them the true meaning of friendship.', 1, 'opal-moonbaby', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-medium.webp', 1, '2025-05-10 22:05:46', '2025-05-10 22:05:46', NULL, 'book'),
(2023, 'Ratburger', 'Meet Zoe, a young girl who finds solace in her pet rat, Armitage. When Armitage goes missing and Zoe discovers a sinister burger van owner with a dark secret, she embarks on a daring mission to save her beloved rat. This hilarious and heartwarming story explores themes of friendship, family, and standing up against bullies.', 1, 'ratburger', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-medium.webp', 1, '2025-05-10 22:05:49', '2025-05-10 22:05:49', NULL, 'book'),
(2024, 'The Amber Spyglass', 'Follow Lyra and Will as their worlds collide and they embark on a perilous journey to save all they hold dear. In the epic conclusion of the His Dark Materials trilogy, Lyra and Will face incredible challenges, encounter fascinating characters, and explore the mysteries of parallel universes. This captivating and thought-provoking tale explores love, destiny, and the power of human connection.', 1, 'the-amber-spyglass', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-medium.webp', 1, '2025-05-10 22:05:54', '2025-05-10 22:05:54', NULL, 'book'),
(2025, 'The Boy in the Dress', 'Follow the story of Dennis, a twelve-year-old boy who loves soccer and fashion. When he discovers a dress in a catalog, Dennis finds the courage to explore his passion for fashion and challenge societal expectations. This heartwarming and inspiring tale celebrates individuality, acceptance, and the power of self-expression.', 1, 'the-boy-in-the-dress', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-medium.webp', 1, '2025-05-10 22:05:57', '2025-05-10 22:05:57', NULL, 'book'),
(2026, 'The Brilliant World of Tom Gates', 'Tom Gates is a master of excuses and doodling. Follow his hilarious and relatable diary as he navigates school, family, and his band, DogZombies. Packed with doodles, funny illustrations, and Tom\'s quirky observations, this book will keep you entertained from beginning to end.', 1, 'the-brilliant-world-of-tom-gates', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-medium.webp', 1, '2025-05-10 22:06:00', '2025-05-10 22:06:00', NULL, 'book'),
(2027, 'The Chronicles of Narnia: The Lion', 'The Lion, the Witch, and the Wardrobe is the first book in The Chronicles of Narnia series. It tells the story of four siblings, Peter, Susan, Edmund, and Lucy, who stumble upon a magical wardrobe that leads them to the enchanting world of Narnia. There, they become embroiled in a battle against the White Witch, aided by the majestic lion Aslan. This timeless tale explores themes of bravery, sacrifice, and the triumph of good over evil.', 1, 'the-chronicles-of-narnia-the-lion', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-medium.webp', 1, '2025-05-10 22:06:04', '2025-05-10 22:06:04', NULL, 'book'),
(2028, 'The Graveyard Book', 'Discover the enchanting tale of Nobody Owens, a boy raised in a graveyard by ghosts. As he navigates the mysteries and dangers of the supernatural world, Nobody learns valuable lessons about life, love, and the power of friendship. This haunting and heartwarming story will captivate readers of all ages.', 1, 'the-graveyard-book', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-medium.webp', 1, '2025-05-10 22:06:08', '2025-05-10 22:06:08', NULL, 'book'),
(2029, 'The Hobbit', 'Follow Bilbo Baggins, a hobbit who embarks on a grand adventure to reclaim a stolen treasure from the fearsome dragon Smaug. Along the way, Bilbo encounters trolls, elves, goblins, and a mysterious ring that will change his life forever. This timeless and epic tale of bravery, friendship, and self-discovery is a cornerstone of fantasy literature.', 1, 'the-hobbit', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-medium.webp', 1, '2025-05-10 22:06:12', '2025-05-10 22:06:12', NULL, 'book'),
(2030, 'The Invention of Hugo Cabret', 'In this captivating novel, meet Hugo Cabret, a young orphan living in the walls of a Paris train station. Hugo\'s talent for fixing clocks and his quest to uncover a secret message left by his late father lead him on a breathtaking adventure. This unique story is told through a combination of words and stunning illustrations.', 1, 'the-invention-of-hugo-cabret', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-medium.webp', 1, '2025-05-10 22:06:14', '2025-05-10 22:06:14', NULL, 'book'),
(2031, 'The Magnificent Moon Hare', 'The Magnificent Moon Hare is a humorous children\'s book written by Sue Monroe. The story revolves around P.J. Petulant, a pampered princess who gets more than she bargained for when she\'s whisked away on an adventure by the Moon Hare. Along with a dragon named Sandra (who\'s actually a boy), they crash from one adventure to another, demanding sponge cake with blue icing and much Tight Twanging along the way.', NULL, 'the-magnificent-moon-hare', NULL, '', '', '', '', 0, 0.0, NULL, NULL, 1, '2025-05-10 22:06:17', '2025-05-10 22:48:51', NULL, 'book'),
(2032, 'The Midnight Gang', 'When the clock strikes midnight at the Lord Funt Hospital, a secret gang of children with unusual illnesses embarks on exciting adventures. Join them as they navigate the hospital\'s corridors and encounter eccentric characters, heartwarming moments, and the magic of friendship. This enchanting tale is packed with humor, imagination, and David Walliams\' signature wit.', NULL, 'the-midnight-gang', NULL, '', '', '', '', 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/childrens-storybook-illustration-red-robot-rabbit-leafy-lane.png', 1, '2025-05-10 22:06:20', '2025-05-10 22:48:59', NULL, 'book'),
(2033, 'The Money, Stan, Big Lauren and Me Book', 'The story revolves around a young boy named Billy Grimshaw Jones who is excited about the arrival of a new baby in the family as it means they get to move to a bigger house. However, when his mother loses her job, their plans are put on hold. Billy, his little brother Stan, and his best friend Big Lauren then embark on various adventures to try and make money. The story takes a turn when Billy finds an envelope full of money, leading to a series of events that ultimately teach him that money cannot buy happiness.', 1, 'the-money-stan-big-lauren-and-me-book', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-medium.webp', 1, '2025-05-10 22:06:23', '2025-05-10 22:06:23', NULL, 'book'),
(2034, 'The Mystery of the Whistling Caves', 'Join Scott and Jack during their summer with their great aunt as they explore the lighthouse, the castle, and the mysterious whistling caves. When priceless treasures are stolen and the caves stop whistling, the friends must solve the mystery and catch the thief before it\'s too late!', 1, 'the-mystery-of-the-whistling-caves', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-medium.webp', 1, '2025-05-10 22:06:26', '2025-05-10 22:06:26', NULL, 'book'),
(2035, 'The Peppers and the International Magic Guys', 'Join Monty and Esme Pepper, the mischievous Pepper twins, on a show-stopping adventure. When their Uncle Potty\'s magic organization, International Magic Guys, faces closure, it\'s up to the Pepper twins to save the day. With real magic tricks, eccentric characters, and a dash of mayhem, this book will leave you spellbound!', 1, 'the-peppers-and-the-international-magic-guys', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-medium.webp', 1, '2025-05-10 22:06:29', '2025-05-10 22:06:29', NULL, 'book'),
(2036, 'The Spiderwick Chronicles: The Field Guide', 'Discover the magical world of the Spiderwick Estate, where three siblings encounter a hidden realm of faeries, goblins, and fantastical creatures. With the help of their great-great-uncle\'s Field Guide, the Grace children must navigate this dangerous and enchanting world. This spellbinding tale is filled with suspense, adventure, and captivating illustrations.', 1, 'the-spiderwick-chronicles-the-field-guide', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-medium.webp', 1, '2025-05-10 22:06:31', '2025-05-10 22:06:31', NULL, 'book'),
(2037, 'The Whizz Pop Chocolate Shop', '\"The Whizz Pop Chocolate Shop\" follows the adventures of Oz and Lily, two twins who move into a mysterious house that also houses a chocolate shop. Little do they know that the chocolate shop is not just an ordinary shop. It is filled with magical secrets and haunted by ghosts.\r\n\r\nAs Oz and Lily settle into their new home, they discover gold chocolate molds with magical powers. These molds attract not only the ghosts but also a group of evil villains who are determined to obtain the secrets of the magical chocolate.\r\n\r\nThe twins find themselves caught up in a thrilling mission as they join forces with talking animals like Demerara the cat and Spike the rat. Together, they must protect the magical chocolate and prevent it from falling into the wrong hands.\r\n\r\nThroughout their journey, Oz and Lily encounter unexpected twists, face dangerous situations, and discover the true extent of their own abilities. Along the way, they learn about friendship, bravery, and the power of imagination.\r\n\r\nWill the twins be able to outwit the villains and protect the magical chocolate? Join Oz and Lily in \"The Whizz Pop Chocolate Shop\" to find out!', NULL, 'the-whizz-pop-chocolate-shop', NULL, '', '', '', '', 0, 0.0, NULL, NULL, 1, '2025-05-10 22:06:34', '2025-05-10 22:46:55', NULL, 'book'),
(2038, 'The Worst Witch', 'Meet Mildred Hubble, the worst witch at Miss Cackle\'s Academy for Witches. Follow her hilarious and often disastrous adventures as she tries to fit in, learn spells, and prove herself to her teachers and classmates. This classic tale of friendship, magic, and self-discovery has been enchanting readers for decades.', 1, 'the-worst-witch', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-medium.webp', 1, '2025-05-10 22:06:41', '2025-05-10 22:06:41', NULL, 'book'),
(2039, 'To Be A Cat', 'Barney Willow is tired of being himself and wants a different life. When he wakes up one day as a cat, he thinks it\'s the perfect escape. However, being a cat turns out to be more challenging than Barney expected. Join him on a hilarious and heartwarming journey as he learns valuable lessons about identity and self-acceptance.', 1, 'to-be-a-cat', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-medium.webp', 1, '2025-05-10 22:06:47', '2025-05-10 22:06:47', NULL, 'book'),
(2040, 'Tuck Everlasting', 'Discover the extraordinary story of the Tuck family who, after drinking from a magical spring, became immortal. When ten-year-old Winnie Foster stumbles upon their secret, she must decide between eternal life and the joys and sorrows of a normal existence. This thought-provoking and beautifully written tale explores the value of life, the inevitability of death, and the power of choices.', 1, 'tuck-everlasting', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-medium.webp', 1, '2025-05-10 22:06:50', '2025-05-10 22:06:50', NULL, 'book');

-- --------------------------------------------------------

--
-- Table structure for table `directory_items_backup`
--

CREATE TABLE `directory_items_backup` (
  `id` int NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `website_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(3,1) DEFAULT '0.0',
  `price_range` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `story_id` int DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `directory_items_backup`
--

INSERT INTO `directory_items_backup` (`id`, `title`, `description`, `category_id`, `slug`, `published_at`, `website_url`, `contact_email`, `contact_phone`, `address`, `featured`, `rating`, `price_range`, `cover_url`, `is_published`, `created_at`, `updated_at`, `story_id`, `type`) VALUES
(1, 'Test Directory', 'Test directory description', 3, 'test-directory', NULL, 'http://example.com', '', '', '', 0, 4.5, 'Free', 'https://api.storiesfromtheweb.org/../../uploads/optimized/681b92b6b29d25-36717642-20c9ed6698f8d14fca050b48822c5eef-medium.webp', 1, '2025-04-26 09:17:50', '2025-05-07 18:05:42', NULL, 'general'),
(2, 'Another Directory2', 'More directory content2222222', 3, 'another-directory2', NULL, 'http://example.org2', 'david.bryan@opace.co.uk', '+447859022297', 'dfdfdf', 0, 4.0, 'Premium', 'https://api.storiesfromtheweb.org/public/uploads/681b76eef1360-boy-meets-vampire-storybook-illustration.png', 1, '2025-04-26 09:17:50', '2025-05-07 16:50:32', NULL, 'general'),
(721, 'A Hen in the Wardrobe by Wendy Meddour', '## Book & Author Info', 1, 'en-in-the-ardrobe-by-endy-eddour', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b00e307_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(722, 'Coraline by Neil Gaiman', '## Book & Author Info', 1, 'oraline-by-eil-aiman', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b029e80_Coraline_by_Neil_Gaiman.jpeg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(723, 'Demon Dentist by David Walliams', '## Book & Author Info', 1, 'emon-entist-by-avid-alliams', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b045cbd_Demon_Dentist_by_David_Walliams.jpeg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(724, 'Earwig and the Witch by Diana Wynne Jones', '## Book & Author Info', 1, 'arwig-and-the-itch-by-iana-ynne-ones', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b06137c_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(725, 'Gangsta Granny by David Walliams', '## Book & Author Info', 1, 'angsta-ranny-by-avid-alliams', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b07cb19_Gangsta_Granny_by_David_Walliams.jpeg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(726, 'Goblins by Philip Reeve', '## Book & Author Info', 1, 'oblins-by-hilip-eeve', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b098063_Goblins_by_Philip_Reeve.jpg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(727, 'Harry Potter and the Philosopher\'s Stone by J.K. Rowling', '## Book & Author Info', 1, 'arry-otter-and-the-hilosopher-s-tone-by-owling', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b0b384c_Harry_Potter_and_the_Philosophers_Stone.jpeg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(728, 'Holes by Louis Sachar', '## Book & Author Info', 1, 'oles-by-ouis-achar', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b0cef8f_Holes_by_Louis_Sachar.jpg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(729, 'Kidnap in the Caribbean (Laura Marlin Mysteries, Book 2) by Lauren St John', '\"Kidnap in the Caribbean\" by Lauren St John has received a mixed bag of reviews from our young readers. Many of them found the book to be an exciting adventure filled with suspense, drama, and mystery.', 1, 'idnap-in-the-aribbean-aura-arlin-ysteries-ook-2-by-auren-t-ohn', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b0ea144_Kidnap_oin_the_Caribbean.jpeg', 1, '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, 'book'),
(730, 'Little Manfred by Michael Morpurgo', '\"Little Manfred\" by Michael Morpurgo has received a variety of reviews from children aged 9-11. Many of the reviewers enjoyed the book, finding it interesting, emotional, and engaging. They particularly liked the characters and the setting of the story, which is based around World War II. Some reviewers found the book a bit sad, but still enjoyable. The book seems to be particularly appealing to those who enjoy books about the war and those who like emotional stories.', 1, 'ittle-anfred-by-ichael-orpurgo', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b110d5c_Little_Manfred_by_Michael_Morpurgo.jpeg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(731, 'Moon Pie by Simon Mason', 'The reviews for Moon Pie by Simon Mason are mixed, with most readers enjoying the book and finding it engaging and emotional. Many readers appreciate the complex themes the book explores, such as family dynamics, addiction, and resilience. The characters, particularly Martha and Tug, are well-liked for their relatable qualities and growth throughout the story. Some readers found the book a bit hard to get into or felt that it had sad moments, but overall, they still found it to be a good read. The book receives praise for its realistic portrayal of modern life issues and its ability to evoke different emotions. However, a few reviewers found the book either upsetting, boring, or not as exciting as they had hoped.', 1, 'oon-ie-by-imon-ason', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b12c603_Moon_Pie_by_Simon_Mason.jpeg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(732, 'Mr. Stink by David Walliams', '## Book & Author Info', 1, 'r-tink-by-avid-alliams', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b147f02_Mr._Stink_by_David_Walliams.jpeg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(733, 'Opal Moonbaby by Maudie Smith', '## Book & Author Info', 1, 'pal-oonbaby-by-audie-mith', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b163720_Opal_Moonbaby_by_Maudie_Smith.jpeg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(734, 'Ratburger by David Walliams', '## Book & Author Info', 1, 'atburger-by-avid-alliams', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b17f073_Ratburger_by_David_Walliams.gif', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(735, 'The Amber Spyglass by Philip Pullman', '## Book & Author Info', 1, 'he-mber-pyglass-by-hilip-ullman', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b19a328_The_Amber_Spyglass_by_Philip_Pullman.jpg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(736, 'The Boy in the Dress by David Walliams', '## Book & Author Info', 1, 'he-oy-in-the-ress-by-avid-alliams', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b1b567b_The_Boy_in_the_Dress_by_David_Walliams.jpg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(737, 'The Brilliant World of Tom Gates by Liz Pichon', '## Book & Author Info', 1, 'he-rilliant-orld-of-om-ates-by-iz-ichon', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b1cff0c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(738, 'The Chronicles of Narnia: The Lion, the Witch and the Wardrobe by C.S. Lewis', '## Book & Author Info', 1, 'he-hronicles-of-arnia-he-ion-the-itch-and-the-ardrobe-by-ewis', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b1ea7e8_The_Chronicles_of_Narnia_The_Lion_the_Witch_and_the_Wardrobe.jpg', 1, '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, 'book'),
(739, 'The Graveyard Book By Neil Gaiman', '## Book & Author Info', 1, 'he-raveyard-ook-y-eil-aiman', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b210f0e_The_Graveyard_Book_By_Neil_Gaiman.jpeg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(740, 'The Hobbit by J.R.R. Tolkien', '## Book & Author Info', 1, 'he-obbit-by-olkien', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b22b7e5_The_Hobbit_by_J.R.R._Tolkien.jpg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(741, 'The Invention of Hugo Cabret by Brian Selznick', '## Book & Author Info', 1, 'he-nvention-of-ugo-abret-by-rian-elznick', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b245dc5_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(742, 'The Magnificent Moon Hare by Sue Monroe', '\"The Magnificent Moon Hare\" by Sue Monroe is a children\'s book that has received overwhelmingly positive reviews from young readers. The book is praised for its humor, imaginative characters, and engaging plot. Many reviewers recommend the book for children aged 8-10, but it seems to be enjoyed by a broader age range as well. The book\'s humor and adventurous storyline are frequently mentioned in the reviews, with many readers finding it \"funny,\" \"exciting,\" and \"brilliant.\" Some reviewers expressed a desire for a sequel, indicating that they were highly engaged with the story and characters.', 1, 'he-agnificent-oon-are-by-ue-onroe', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b2604b7_The_Magnificent_Moon_Hare.jpg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(743, 'The Midnight Gang by David Walliams', '## Book & Author Info', 1, 'he-idnight-ang-by-avid-alliams', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b27aee4_The_Midnight_Gang_by_David_Walliams.jpg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(744, 'The Money, Stan, Big Lauren and Me by Joanna Nadin', 'The book \"The Money, Stan, Big Lauren and Me\" by Joanna Nadin has received mixed reviews from children. Some children found the book to be exciting and humorous, appreciating the storyline and the characters. They enjoyed the plot twists and the lessons the book imparted about money and happiness. However, some children found the book to be boring and hard to get into. They felt that the book was not suitable for their age group due to the use of inappropriate language.', 1, 'he-oney-tan-ig-auren-and-e-by-oanna-adin', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b295977_The_Money_Stan_Big_Lauren_and_Me.jpg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(745, 'The Mystery of the Whistling Caves by Helen Moss', '## Book & Author Info', 1, 'he-ystery-of-the-histling-aves-by-elen-oss', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b2b0400_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(746, 'The Peppers and the International Magic Guys by Sian Pattenden', '## Book & Author Info', 1, 'he-eppers-and-the-nternational-agic-uys-by-ian-attenden', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b2cb2a9_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(747, 'The Spiderwick Chronicles: The Field Guide by Tony DiTerlizzi and Holly Black', '## Book & Author Info', 1, 'he-piderwick-hronicles-he-ield-uide-by-ony-i-erlizzi-and-olly-lack', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b2e62d7_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', 1, '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, 'book'),
(748, 'The Whizz Pop Chocolate Shop by Kate Saunders', '\"The Whizz Pop Chocolate Shop\" by Kate Saunders received mixed reviews from the young readers. Many of them enjoyed the book\'s magical elements, exciting plot, and humorous characters. The imaginative world created by the author and the adventures of the main characters were appreciated by several readers. However, some reviewers found the story confusing or difficult to get into, which affected their overall enjoyment. The book\'s lack of illustrations was also mentioned as a drawback by a few readers. Overall, \"The Whizz Pop Chocolate Shop\" seems to have appealed to readers who enjoy fantasy, magic, and adventure, but individual preferences varied.', 1, 'he-hizz-op-hocolate-hop-by-ate-aunders', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b30cd4f_The_Whizz_Pop_Chocolate_Shop.jpg', 1, '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, 'book'),
(749, 'The Worst Witch by Jill Murphy', '## Book & Author Info', 1, 'he-orst-itch-by-ill-urphy', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b327cab_The_Worst_Witch_by_Jill_Murphy.jpeg', 1, '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, 'book'),
(750, 'To Be A Cat by Matt Haig', '## Book & Author Info', 1, 'o-e-at-by-att-aig', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b346a08_To_Be_A_Cat_by_Matt_Haig.jpeg', 1, '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, 'book'),
(751, 'Tuck Everlasting by Natalie Babbitt', '## Book & Author Info', 1, 'uck-verlasting-by-atalie-abbitt', NULL, '', NULL, NULL, NULL, 0, 0.0, NULL, '/uploads/books/book_681e34b361c7f_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', 1, '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, 'book');

-- --------------------------------------------------------

--
-- Table structure for table `directory_item_tags`
--

CREATE TABLE `directory_item_tags` (
  `directory_item_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `directory_item_tags`
--

INSERT INTO `directory_item_tags` (`directory_item_id`, `tag_id`) VALUES
(2020, 7),
(2017, 17),
(2018, 17),
(2034, 17),
(2016, 27),
(2018, 27),
(2019, 27),
(2027, 27),
(2029, 27),
(2031, 27),
(2033, 27),
(2011, 45),
(2013, 45),
(2015, 45),
(2016, 45),
(2024, 45),
(2027, 45),
(2028, 45),
(2029, 45),
(2035, 45),
(2036, 45),
(2037, 45),
(2038, 45),
(2039, 45),
(2040, 45),
(2019, 49),
(2020, 50),
(2020, 51),
(2031, 65),
(2033, 65),
(2010, 97),
(2011, 97),
(2012, 97),
(2013, 97),
(2014, 97),
(2015, 97),
(2017, 97),
(2018, 97),
(2019, 97),
(2020, 97),
(2021, 97),
(2022, 97),
(2023, 97),
(2024, 97),
(2025, 97),
(2026, 97),
(2028, 97),
(2030, 97),
(2031, 97),
(2032, 97),
(2033, 97),
(2034, 97),
(2035, 97),
(2036, 97),
(2037, 97),
(2038, 97),
(2039, 97),
(2040, 97),
(2015, 101),
(2020, 118),
(2020, 119),
(2010, 120),
(2034, 120),
(2035, 120),
(2036, 120),
(2011, 121),
(2012, 121),
(2014, 121),
(2021, 121),
(2023, 121),
(2025, 121),
(2026, 121),
(2028, 121),
(2032, 121),
(2039, 121),
(2013, 122),
(2022, 122),
(2027, 122),
(2030, 122),
(2038, 122),
(2040, 122),
(2016, 123),
(2017, 124),
(2029, 125),
(2024, 126);

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
(1, 'Test Game4', 'Test game description', 0, 'test-game2', 'http://example.com', 'Action', 'PC', 'Test Dev', 'Test Pub', NULL, 0.0, 0.00, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa93e9c27c-Tuck-Everlasting-by-Natalie-Babbitt-medium.webp', 1, NULL, '2025-04-26 08:17:50', '2025-05-10 21:01:11'),
(2, 'Another Game', 'More game content', 0, 'another-game', 'http://example.org', 'RPG', 'Console', 'Dev2', 'Pub2', NULL, 0.0, 0.00, 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-medium.webp', 1, NULL, '2025-04-26 08:17:50', '2025-05-10 21:01:22');

-- --------------------------------------------------------

--
-- Table structure for table `item_tags`
--

CREATE TABLE `item_tags` (
  `item_id` int NOT NULL,
  `tag_id` int NOT NULL,
  `item_type` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `item_tags`
--

INSERT INTO `item_tags` (`item_id`, `tag_id`, `item_type`) VALUES
(2031, 27, 'directory_item'),
(2031, 65, 'directory_item'),
(2031, 97, 'directory_item'),
(2032, 97, 'directory_item'),
(2037, 45, 'directory_item'),
(2037, 97, 'directory_item');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `small_url` varchar(255) DEFAULT NULL,
  `medium_url` varchar(255) DEFAULT NULL,
  `large_url` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `filename`, `file_path`, `thumbnail_url`, `small_url`, `medium_url`, `large_url`, `file_type`, `file_size`, `alt_text`, `created_at`, `updated_at`, `width`, `height`) VALUES
(280, 'whimsical-wind-playful-girl.png', '/uploads/whimsical-wind-playful-girl.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for A Windy Day by Dearbhla, aged 9, from Northern Ireland', '2025-05-10 09:25:20', '2025-05-10 09:25:20', NULL, NULL),
(281, 'whimsical-autumn-animals.png', '/uploads/whimsical-autumn-animals.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Autumn poem by Niall, aged 9, from Omagh Northern Ireland. CO.Tyrone', '2025-05-10 09:25:20', '2025-05-10 09:25:20', NULL, NULL),
(282, 'children-storybook-illustration-christmas.png', '/uploads/children-storybook-illustration-christmas.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Christmas by Kerys, aged 7, from Paisley, Renfrewshire', '2025-05-10 09:25:20', '2025-05-10 09:25:20', NULL, NULL),
(283, 'a-man-who-lived-in-a-cave.png', '/uploads/a-man-who-lived-in-a-cave.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Dave by Aine K, aged 9, from Northern Ireland', '2025-05-10 09:25:20', '2025-05-10 09:25:20', NULL, NULL),
(284, 'dewey-library-cat-childrens-storybook-illustration.png', '/uploads/dewey-library-cat-childrens-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Dewy the Little Library Cat by Orla, aged 11 , from The Smiley Book Club, Northern Ireland', '2025-05-10 09:25:20', '2025-05-10 09:25:20', NULL, NULL),
(285, 'vibrant-dinosaur-adventure.png', '/uploads/vibrant-dinosaur-adventure.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Dinosaur! by Nadia, aged 10, from Banbridge, Northern Ireland', '2025-05-10 09:25:20', '2025-05-10 09:25:20', NULL, NULL),
(286, 'show-playful-geckos-falling-into-a-stinlky.png', '/uploads/show-playful-geckos-falling-into-a-stinlky.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Geckos are sacred by Alfie, aged 9, from Dorset, England', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(287, 'illustration-sammy-hidden-cottage.png', '/uploads/illustration-sammy-hidden-cottage.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Little Infinity by Rachel, aged 12, from South Tyneside, England', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(288, 'little-star-little-dude-childrens-storybook-illustration.png', '/uploads/little-star-little-dude-childrens-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Little Star by Ellie, aged 8, from Northern Ireland', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(289, 'mischievous-cat-illustration.png', '/uploads/mischievous-cat-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Milly by Abigail, aged 7, from Dorset, England', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(290, 'sammy-benny-reunion-swiss-alps.png', '/uploads/sammy-benny-reunion-swiss-alps.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Monster Crush by Eve, aged 9, from Rhondda, South Wales', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(291, 'playful-dog-adventures-children-storybook-illustration.png', '/uploads/playful-dog-adventures-children-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for My Bog Poeming by Cory, aged 3, from Cae Garw, Wales', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(292, 'playful-cat-city-storybook-illustration.png', '/uploads/playful-cat-city-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for My Cat by Ria, aged 8, from Hayes, Hillingdon', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(293, 'matilda-adventure-treasure-storybook-illustration.png', '/uploads/matilda-adventure-treasure-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for My Favourite Book Character by Rebekah, aged 9, from Lurgan Co. Armagh, Northern Ireland', '2025-05-10 09:25:21', '2025-05-10 09:25:21', NULL, NULL),
(294, 'leah-christmas-wishes-children-storybook-illustration.png', '/uploads/leah-christmas-wishes-children-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for My Secret Wish List by Leah, aged 11, from Rhondda, South Wales', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(295, 'ashtons-adventure-hello-kitty-umbrella-hebburn-library.png', '/uploads/ashtons-adventure-hello-kitty-umbrella-hebburn-library.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Nice day out with mummy x by Ashton, aged 6, from Jarrow', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(296, 'girl-likes-pink-children\'s-storybook.png', '/uploads/girl-likes-pink-children\'s-storybook.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for All About Me by Abbey-lei, aged 10, from Killyclogher, Northern Ireland', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(297, 'boy-meets-beast-children\'s-storybook.png', '/uploads/boy-meets-beast-children\'s-storybook.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for The Beast Within by Chris, aged 11, from Perth and Kinross, Scotland', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(298, 'children-storybook-illustration-adventures-bean-man-plate-fork-sheep.png', '/uploads/children-storybook-illustration-adventures-bean-man-plate-fork-sheep.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for The Day Before Yesterday by Meranie, aged 9, from Luton, England', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(299, 'magical-underwater-tree-house-adventure-childrens-story-book-illustration.png', '/uploads/magical-underwater-tree-house-adventure-childrens-story-book-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for The Magic Treehouse  Kerry, aged 9, from Northern Ireland', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(300, 'illustration-alice-optician.png', '/uploads/illustration-alice-optician.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for The Optition by Alice, aged 7, from Scotland', '2025-05-10 09:25:22', '2025-05-10 09:25:22', NULL, NULL),
(302, 'tim-the-can-children-storybook-illustration.png', '/uploads/tim-the-can-children-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Tim Can by Melissa, aged 10, from Paisley', '2025-05-10 09:25:23', '2025-05-10 09:25:23', NULL, NULL),
(520, 'book_681f1a1491e10_To_Be_A_Cat_by_Matt_Haig.jpeg', '/uploads/books/book_681f1a1491e10_To_Be_A_Cat_by_Matt_Haig.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 229771, 'Cover image for To Be A Cat', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(521, 'book_681f1a14adec6_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', '/uploads/books/book_681f1a14adec6_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 345456, 'Cover image for Tuck Everlasting', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(519, 'book_681f1a1475c7f_The_Worst_Witch_by_Jill_Murphy.jpeg', '/uploads/books/book_681f1a1475c7f_The_Worst_Witch_by_Jill_Murphy.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 536466, 'Cover image for The Worst Witch', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(517, 'book_681f1a143df27_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', '/uploads/books/book_681f1a143df27_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 45477, 'Cover image for The Spiderwick Chronicles: The Field Guide', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(518, 'book_681f1a1459ebf_The_Whizz_Pop_Chocolate_Shop.jpg', '/uploads/books/book_681f1a1459ebf_The_Whizz_Pop_Chocolate_Shop.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 78222, 'Cover image for The Whizz Pop Chocolate Shop by Kate Saunders', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(515, 'book_681f1a14058af_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', '/uploads/books/book_681f1a14058af_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', NULL, NULL, NULL, NULL, 'image/webp', 331984, 'Cover image for The Mystery of the Whistling Caves', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(516, 'book_681f1a1421df6_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', '/uploads/books/book_681f1a1421df6_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 147249, 'Cover image for The Peppers and the International Magic Guys', '2025-05-10 10:19:16', '2025-05-10 10:19:16', NULL, NULL),
(512, 'book_681f1a13a3dc7_The_Magnificent_Moon_Hare.jpg', '/uploads/books/book_681f1a13a3dc7_The_Magnificent_Moon_Hare.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 123070, 'Cover image for The Magnificent Moon Hare', '2025-05-10 10:19:15', '2025-05-10 10:19:15', NULL, NULL),
(513, 'book_681f1a13bfb0f_The_Midnight_Gang_by_David_Walliams.jpg', '/uploads/books/book_681f1a13bfb0f_The_Midnight_Gang_by_David_Walliams.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 134586, 'Cover image for The Midnight Gang', '2025-05-10 10:19:15', '2025-05-10 10:19:15', NULL, NULL),
(511, 'book_681f1a1388586_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', '/uploads/books/book_681f1a1388586_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', NULL, NULL, NULL, NULL, 'image/webp', 37718, 'Cover image for The Invention of Hugo Cabret', '2025-05-10 10:19:15', '2025-05-10 10:19:15', NULL, NULL),
(507, 'book_681f1a131a80c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', '/uploads/books/book_681f1a131a80c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 60753, 'Cover image for The Brilliant World of Tom Gates', '2025-05-10 10:19:15', '2025-05-10 10:19:15', NULL, NULL),
(509, 'book_681f1a1350f87_The_Graveyard_Book_By_Neil_Gaiman.jpeg', '/uploads/books/book_681f1a1350f87_The_Graveyard_Book_By_Neil_Gaiman.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 245492, 'Cover image for The Graveyard Book', '2025-05-10 10:19:15', '2025-05-10 10:19:15', NULL, NULL),
(505, 'book_681f1a12d45de_The_Amber_Spyglass_by_Philip_Pullman.jpg', '/uploads/books/book_681f1a12d45de_The_Amber_Spyglass_by_Philip_Pullman.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 163816, 'Cover image for The Amber Spyglass by Philip Pullman', '2025-05-10 10:19:14', '2025-05-10 10:19:14', NULL, NULL),
(506, 'book_681f1a12efd63_The_Boy_in_the_Dress_by_David_Walliams.jpg', '/uploads/books/book_681f1a12efd63_The_Boy_in_the_Dress_by_David_Walliams.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 65531, 'Cover image for The Boy in the Dress', '2025-05-10 10:19:14', '2025-05-10 10:19:14', NULL, NULL),
(504, 'book_681f1a12b8d75_Ratburger_by_David_Walliams.gif', '/uploads/books/book_681f1a12b8d75_Ratburger_by_David_Walliams.gif', NULL, NULL, NULL, NULL, 'image/gif', 88341, 'Cover image for Ratburger', '2025-05-10 10:19:14', '2025-05-10 10:19:14', NULL, NULL),
(502, 'book_681f1a1280a95_Mr._Stink_by_David_Walliams.jpeg', '/uploads/books/book_681f1a1280a95_Mr._Stink_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 79129, 'Cover image for Mr. Stink', '2025-05-10 10:19:14', '2025-05-10 10:19:14', NULL, NULL),
(503, 'book_681f1a129c6e7_Opal_Moonbaby_by_Maudie_Smith.jpeg', '/uploads/books/book_681f1a129c6e7_Opal_Moonbaby_by_Maudie_Smith.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 688281, 'Cover image for Opal Moonbaby', '2025-05-10 10:19:14', '2025-05-10 10:19:14', NULL, NULL),
(498, 'book_681f1a1210813_Holes_by_Louis_Sachar.jpg', '/uploads/books/book_681f1a1210813_Holes_by_Louis_Sachar.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 117700, 'Cover image for Holes', '2025-05-10 10:19:14', '2025-05-10 10:19:14', NULL, NULL),
(494, 'book_681f1a1195dad_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', '/uploads/books/book_681f1a1195dad_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 80326, 'Cover image for Earwig and the Witch', '2025-05-10 10:19:13', '2025-05-10 10:19:13', NULL, NULL),
(496, 'book_681f1a11cd643_Goblins_by_Philip_Reeve.jpg', '/uploads/books/book_681f1a11cd643_Goblins_by_Philip_Reeve.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 72542, 'Cover image for Goblins', '2025-05-10 10:19:13', '2025-05-10 10:19:13', NULL, NULL),
(495, 'book_681f1a11b1c54_Gangsta_Granny_by_David_Walliams.jpeg', '/uploads/books/book_681f1a11b1c54_Gangsta_Granny_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 51919, 'Cover image for Gangsta Granny', '2025-05-10 10:19:13', '2025-05-10 10:19:13', NULL, NULL),
(490, 'book_681f1a0bc3557_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', '/uploads/books/book_681f1a0bc3557_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 345456, 'Cover image for Tuck Everlasting', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(489, 'book_681f1a0ba78bb_To_Be_A_Cat_by_Matt_Haig.jpeg', '/uploads/books/book_681f1a0ba78bb_To_Be_A_Cat_by_Matt_Haig.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 229771, 'Cover image for To Be A Cat', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(488, 'book_681f1a0b8b67d_The_Worst_Witch_by_Jill_Murphy.jpeg', '/uploads/books/book_681f1a0b8b67d_The_Worst_Witch_by_Jill_Murphy.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 536466, 'Cover image for The Worst Witch', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(487, 'book_681f1a0b6f56c_The_Whizz_Pop_Chocolate_Shop.jpg', '/uploads/books/book_681f1a0b6f56c_The_Whizz_Pop_Chocolate_Shop.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 78222, 'Cover image for The Whizz Pop Chocolate Shop by Kate Saunders', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(486, 'book_681f1a0b533b8_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', '/uploads/books/book_681f1a0b533b8_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 45477, 'Cover image for The Spiderwick Chronicles: The Field Guide', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(485, 'book_681f1a0b37015_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', '/uploads/books/book_681f1a0b37015_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 147249, 'Cover image for The Peppers and the International Magic Guys', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(484, 'book_681f1a0b1ad7e_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', '/uploads/books/book_681f1a0b1ad7e_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', NULL, NULL, NULL, NULL, 'image/webp', 331984, 'Cover image for The Mystery of the Whistling Caves', '2025-05-10 10:19:07', '2025-05-10 10:19:07', NULL, NULL),
(482, 'book_681f1a0ad7bb3_The_Midnight_Gang_by_David_Walliams.jpg', '/uploads/books/book_681f1a0ad7bb3_The_Midnight_Gang_by_David_Walliams.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 134586, 'Cover image for The Midnight Gang', '2025-05-10 10:19:06', '2025-05-10 10:19:06', NULL, NULL),
(481, 'book_681f1a0abb803_The_Magnificent_Moon_Hare.jpg', '/uploads/books/book_681f1a0abb803_The_Magnificent_Moon_Hare.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 123070, 'Cover image for The Magnificent Moon Hare', '2025-05-10 10:19:06', '2025-05-10 10:19:06', NULL, NULL),
(480, 'book_681f1a0a9fc9b_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', '/uploads/books/book_681f1a0a9fc9b_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', NULL, NULL, NULL, NULL, 'image/webp', 37718, 'Cover image for The Invention of Hugo Cabret', '2025-05-10 10:19:06', '2025-05-10 10:19:06', NULL, NULL),
(478, 'book_681f1a0a6870a_The_Graveyard_Book_By_Neil_Gaiman.jpeg', '/uploads/books/book_681f1a0a6870a_The_Graveyard_Book_By_Neil_Gaiman.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 245492, 'Cover image for The Graveyard Book', '2025-05-10 10:19:06', '2025-05-10 10:19:06', NULL, NULL),
(475, 'book_681f1a0a15931_The_Boy_in_the_Dress_by_David_Walliams.jpg', '/uploads/books/book_681f1a0a15931_The_Boy_in_the_Dress_by_David_Walliams.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 65531, 'Cover image for The Boy in the Dress', '2025-05-10 10:19:06', '2025-05-10 10:19:06', NULL, NULL),
(476, 'book_681f1a0a31324_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', '/uploads/books/book_681f1a0a31324_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 60753, 'Cover image for The Brilliant World of Tom Gates', '2025-05-10 10:19:06', '2025-05-10 10:19:06', NULL, NULL),
(474, 'book_681f1a09eddcb_The_Amber_Spyglass_by_Philip_Pullman.jpg', '/uploads/books/book_681f1a09eddcb_The_Amber_Spyglass_by_Philip_Pullman.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 163816, 'Cover image for The Amber Spyglass by Philip Pullman', '2025-05-10 10:19:05', '2025-05-10 10:19:05', NULL, NULL),
(473, 'book_681f1a09d153e_Ratburger_by_David_Walliams.gif', '/uploads/books/book_681f1a09d153e_Ratburger_by_David_Walliams.gif', NULL, NULL, NULL, NULL, 'image/gif', 88341, 'Cover image for Ratburger', '2025-05-10 10:19:05', '2025-05-10 10:19:05', NULL, NULL),
(472, 'book_681f1a09b550d_Opal_Moonbaby_by_Maudie_Smith.jpeg', '/uploads/books/book_681f1a09b550d_Opal_Moonbaby_by_Maudie_Smith.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 688281, 'Cover image for Opal Moonbaby', '2025-05-10 10:19:05', '2025-05-10 10:19:05', NULL, NULL),
(471, 'book_681f1a0999ad2_Mr._Stink_by_David_Walliams.jpeg', '/uploads/books/book_681f1a0999ad2_Mr._Stink_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 79129, 'Cover image for Mr. Stink', '2025-05-10 10:19:05', '2025-05-10 10:19:05', NULL, NULL),
(465, 'book_681f1a08e5954_Goblins_by_Philip_Reeve.jpg', '/uploads/books/book_681f1a08e5954_Goblins_by_Philip_Reeve.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 72542, 'Cover image for Goblins', '2025-05-10 10:19:04', '2025-05-10 10:19:04', NULL, NULL),
(467, 'book_681f1a09295b1_Holes_by_Louis_Sachar.jpg', '/uploads/books/book_681f1a09295b1_Holes_by_Louis_Sachar.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 117700, 'Cover image for Holes', '2025-05-10 10:19:05', '2025-05-10 10:19:05', NULL, NULL),
(464, 'book_681f1a08c9f0d_Gangsta_Granny_by_David_Walliams.jpeg', '/uploads/books/book_681f1a08c9f0d_Gangsta_Granny_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 51919, 'Cover image for Gangsta Granny', '2025-05-10 10:19:04', '2025-05-10 10:19:04', NULL, NULL),
(463, 'book_681f1a08ad2a7_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', '/uploads/books/book_681f1a08ad2a7_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 80326, 'Cover image for Earwig and the Witch', '2025-05-10 10:19:04', '2025-05-10 10:19:04', NULL, NULL),
(462, 'book_681f1a08919f5_Demon_Dentist_by_David_Walliams.jpeg', '/uploads/books/book_681f1a08919f5_Demon_Dentist_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 40497, 'Cover image for Demon Dentist', '2025-05-10 10:19:04', '2025-05-10 10:19:04', NULL, NULL),
(460, 'book_681f1a085853b_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', '/uploads/books/book_681f1a085853b_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 117973, 'Cover image for A Hen in the Wardrobe', '2025-05-10 10:19:04', '2025-05-10 10:19:04', NULL, NULL),
(461, 'book_681f1a0874edd_Coraline_by_Neil_Gaiman.jpeg', '/uploads/books/book_681f1a0874edd_Coraline_by_Neil_Gaiman.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 231665, 'Cover image for Coraline', '2025-05-10 10:19:04', '2025-05-10 10:19:04', NULL, NULL),
(493, 'book_681f1a117a618_Demon_Dentist_by_David_Walliams.jpeg', '/uploads/books/book_681f1a117a618_Demon_Dentist_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 40497, 'Cover image for Demon Dentist', '2025-05-10 10:19:13', '2025-05-10 10:19:13', NULL, NULL),
(492, 'book_681f1a115ef06_Coraline_by_Neil_Gaiman.jpeg', '/uploads/books/book_681f1a115ef06_Coraline_by_Neil_Gaiman.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 231665, 'Cover image for Coraline', '2025-05-10 10:19:13', '2025-05-10 10:19:13', NULL, NULL),
(491, 'book_681f1a1143dd0_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', '/uploads/books/book_681f1a1143dd0_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 117973, 'Cover image for A Hen in the Wardrobe', '2025-05-10 10:19:13', '2025-05-10 10:19:13', NULL, NULL),
(1620, 'book_681fbfe7c6a40_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe7c6a40-Tuck-Everlasting-by-Natalie-Babbitt-large.webp', 'image/webp', 93284, 'Cover image for Tuck Everlasting', '2025-05-10 22:06:50', '2025-05-10 22:06:50', NULL, NULL),
(1618, 'book_681fbfda73271_The_Worst_Witch_by_Jill_Murphy.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfda73271-The-Worst-Witch-by-Jill-Murphy-large.webp', 'image/webp', 52836, 'Cover image for The Worst Witch', '2025-05-10 22:06:41', '2025-05-10 22:06:41', NULL, NULL),
(1619, 'book_681fbfe1a3f4c_To_Be_A_Cat_by_Matt_Haig.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfe1a3f4c-To-Be-A-Cat-by-Matt-Haig-large.webp', 'image/webp', 44016, 'Cover image for To Be A Cat', '2025-05-10 22:06:47', '2025-05-10 22:06:47', NULL, NULL),
(1616, 'book_681fbfd5630b8_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd5630b8-The-Spiderwick-Chronicles-The-Field-Guide-by-Tony-DiTerlizzi-and-Holly-Black-large.webp', 'image/webp', 45182, 'Cover image for The Spiderwick Chronicles: The Field Guide', '2025-05-10 22:06:31', '2025-05-10 22:06:31', NULL, NULL),
(1615, 'book_681fbfd294aa0_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfd294aa0-The-Peppers-and-the-International-Magic-Guys-by-Sian-Pattenden-large.webp', 'image/webp', 80600, 'Cover image for The Peppers and the International Magic Guys', '2025-05-10 22:06:29', '2025-05-10 22:06:29', NULL, NULL),
(1613, 'book_681fbfccc7678_The_Money_Stan_Big_Lauren_and_Me.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfccc7678-The-Money-Stan-Big-Lauren-and-Me-large.webp', 'image/webp', 46716, 'Cover image for The Money, Stan, Big Lauren and Me Book', '2025-05-10 22:06:23', '2025-05-10 22:06:23', NULL, NULL),
(1614, 'book_681fbfcfb5e5f_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfcfb5e5f-The-Mystery-of-the-Whistling-Caves-by-Helen-Moss-large.webp', 'image/webp', 57866, 'Cover image for The Mystery of the Whistling Caves', '2025-05-10 22:06:26', '2025-05-10 22:06:26', NULL, NULL),
(1610, 'book_681fbfc44ded5_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc44ded5-The-Invention-of-Hugo-Cabret-by-Brian-Selznick-large.webp', 'image/webp', 55914, 'Cover image for The Invention of Hugo Cabret', '2025-05-10 22:06:14', '2025-05-10 22:06:14', NULL, NULL),
(1608, 'book_681fbfbc4598f_The_Graveyard_Book_By_Neil_Gaiman.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfbc4598f-The-Graveyard-Book-By-Neil-Gaiman-large.webp', 'image/webp', 43364, 'Cover image for The Graveyard Book', '2025-05-10 22:06:08', '2025-05-10 22:06:08', NULL, NULL),
(1609, 'book_681fbfc08f7a7_The_Hobbit_by_J.R.R._Tolkien.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfc08f7a7-The-Hobbit-by-J-R-R-Tolkien-large.webp', 'image/webp', 84392, 'Cover image for The Hobbit', '2025-05-10 22:06:12', '2025-05-10 22:06:12', NULL, NULL),
(1607, 'book_681fbfb8507b2_The_Chronicles_of_Narnia_The_Lion_the_Witch_and_the_Wardrobe.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb8507b2-The-Chronicles-of-Narnia-The-Lion-the-Witch-and-the-Wardrobe-large.webp', 'image/webp', 75012, 'Cover image for The Chronicles of Narnia: The Lion', '2025-05-10 22:06:04', '2025-05-10 22:06:04', NULL, NULL),
(1606, 'book_681fbfb588e88_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb588e88-The-Brilliant-World-of-Tom-Gates-by-Liz-Pichon-large.webp', 'image/webp', 91864, 'Cover image for The Brilliant World of Tom Gates', '2025-05-10 22:06:00', '2025-05-10 22:06:00', NULL, NULL),
(1605, 'book_681fbfb2363fb_The_Boy_in_the_Dress_by_David_Walliams.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfb2363fb-The-Boy-in-the-Dress-by-David-Walliams-large.webp', 'image/webp', 35050, 'Cover image for The Boy in the Dress', '2025-05-10 22:05:57', '2025-05-10 22:05:57', NULL, NULL),
(1604, 'book_681fbfae192be_The_Amber_Spyglass_by_Philip_Pullman.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfae192be-The-Amber-Spyglass-by-Philip-Pullman-large.webp', 'image/webp', 86980, 'Cover image for The Amber Spyglass', '2025-05-10 22:05:54', '2025-05-10 22:05:54', NULL, NULL),
(1603, 'book_681fbfaac57a4_Ratburger_by_David_Walliams.gif', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfaac57a4-Ratburger-by-David-Walliams-large.webp', 'image/webp', 59264, 'Cover image for Ratburger', '2025-05-10 22:05:49', '2025-05-10 22:05:49', NULL, NULL),
(1602, 'book_681fbfa73bd4f_Opal_Moonbaby_by_Maudie_Smith.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa73bd4f-Opal-Moonbaby-by-Maudie-Smith-large.webp', 'image/webp', 57106, 'Cover image for Opal Moonbaby', '2025-05-10 22:05:46', '2025-05-10 22:05:46', NULL, NULL),
(1601, 'book_681fbfa521c57_Mr._Stink_by_David_Walliams.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa521c57-Mr-Stink-by-David-Walliams-large.webp', 'image/webp', 50434, 'Cover image for Mr. Stink', '2025-05-10 22:05:43', '2025-05-10 22:05:43', NULL, NULL),
(1600, 'book_681fbfa2cf5e6_Moon_Pie_by_Simon_Mason.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa2cf5e6-Moon-Pie-by-Simon-Mason-large.webp', 'image/webp', 54988, 'Cover image for Moon Pie', '2025-05-10 22:05:41', '2025-05-10 22:05:41', NULL, NULL),
(1599, 'book_681fbfa0458f9_Little_Manfred_by_Michael_Morpurgo.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbfa0458f9-Little-Manfred-by-Michael-Morpurgo-large.webp', 'image/webp', 44050, 'Cover image for Little Manfred Book', '2025-05-10 22:05:38', '2025-05-10 22:05:38', NULL, NULL),
(1598, 'book_681fbf9dba7d8_Kidnap_oin_the_Caribbean.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9dba7d8-Kidnap-oin-the-Caribbean-large.webp', 'image/webp', 38002, 'Cover image for Kidnap in the Caribbean', '2025-05-10 22:05:36', '2025-05-10 22:05:36', NULL, NULL),
(1597, 'book_681fbf9ac28f7_Holes_by_Louis_Sachar.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf9ac28f7-Holes-by-Louis-Sachar-large.webp', 'image/webp', 65300, 'Cover image for Holes', '2025-05-10 22:05:33', '2025-05-10 22:05:33', NULL, NULL),
(1596, 'book_681fbf96d5c47_Harry_Potter_and_the_Philosophers_Stone.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf96d5c47-Harry-Potter-and-the-Philosophers-Stone-large.webp', 'image/webp', 34294, 'Cover image for Harry Potter and the Philosopher’s Stone', '2025-05-10 22:05:30', '2025-05-10 22:05:30', NULL, NULL),
(1595, 'book_681fbf93b3f04_Goblins_by_Philip_Reeve.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf93b3f04-Goblins-by-Philip-Reeve-large.webp', 'image/webp', 37496, 'Cover image for Goblins', '2025-05-10 22:05:26', '2025-05-10 22:05:26', NULL, NULL),
(1594, 'book_681fbf90ce50f_Gangsta_Granny_by_David_Walliams.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf90ce50f-Gangsta-Granny-by-David-Walliams-large.webp', 'image/webp', 51498, 'Cover image for Gangsta Granny', '2025-05-10 22:05:23', '2025-05-10 22:05:23', NULL, NULL),
(1592, 'book_681fbf8b88f9e_Demon_Dentist_by_David_Walliams.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8b88f9e-Demon-Dentist-by-David-Walliams-large.webp', 'image/webp', 67244, 'Cover image for Demon Dentist', '2025-05-10 22:05:17', '2025-05-10 22:05:17', NULL, NULL),
(1593, 'book_681fbf8db4ae2_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf8db4ae2-Earwig-and-the-Witch-by-Diana-Wynne-Jones-large.webp', 'image/webp', 81660, 'Cover image for Earwig and the Witch', '2025-05-10 22:05:20', '2025-05-10 22:05:20', NULL, NULL),
(1584, 'book_681fa92d6054c_The_Whizz_Pop_Chocolate_Shop.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fa92d6054c-The-Whizz-Pop-Chocolate-Shop-large.webp', 'image/webp', 38868, 'Cover image for The Whizz Pop Chocolate Shop', '2025-05-10 20:29:51', '2025-05-10 20:29:51', NULL, NULL),
(1591, 'book_681fbf892d563_Coraline_by_Neil_Gaiman.jpeg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf892d563-Coraline-by-Neil-Gaiman-large.webp', 'image/webp', 52108, 'Cover image for Coraline', '2025-05-10 22:05:15', '2025-05-10 22:05:15', NULL, NULL),
(1590, 'book_681fbf85663e1_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-thumbnail.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-small.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-medium.webp', 'https://api.storiesfromtheweb.org/uploads/optimized/book-681fbf85663e1-A-Hen-in-the-Wardrobe-by-Wendy-Meddour-large.webp', 'image/webp', 70388, 'Cover image for A Hen in the Wardrobe', '2025-05-10 22:05:13', '2025-05-10 22:05:13', NULL, NULL),
(1622, 'magical-pebble-adventure-childrens-storybook-illustration.png', '/uploads/magical-pebble-adventure-childrens-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Time Stood Still by Joshua , aged 8, from Spring Hill, Birmingham, England', '2025-05-10 22:06:53', '2025-05-10 22:06:53', NULL, NULL),
(1623, 'boy-meets-vampire-storybook-illustration.png', '/uploads/boy-meets-vampire-storybook-illustration.png', NULL, NULL, NULL, NULL, 'image/png', 3147977, 'Cover image for Vampire by Jane, aged 9, from East Dunbartonshire', '2025-05-10 22:06:53', '2025-05-10 22:06:53', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `media_backup`
--

CREATE TABLE `media_backup` (
  `id` int NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `small_url` varchar(255) DEFAULT NULL,
  `medium_url` varchar(255) DEFAULT NULL,
  `large_url` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `media_backup`
--

INSERT INTO `media_backup` (`id`, `filename`, `file_path`, `thumbnail_url`, `small_url`, `medium_url`, `large_url`, `file_type`, `file_size`, `alt_text`, `created_at`, `updated_at`, `width`, `height`) VALUES
(249, 'book_681e34b00e307_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', '/uploads/books/book_681e34b00e307_A_Hen_in_the_Wardrobe_by_Wendy_Meddour.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 117973, 'Cover image for A Hen in the Wardrobe by Wendy Meddour', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(250, 'book_681e34b029e80_Coraline_by_Neil_Gaiman.jpeg', '/uploads/books/book_681e34b029e80_Coraline_by_Neil_Gaiman.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 231665, 'Cover image for Coraline by Neil Gaiman', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(251, 'book_681e34b045cbd_Demon_Dentist_by_David_Walliams.jpeg', '/uploads/books/book_681e34b045cbd_Demon_Dentist_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 40497, 'Cover image for Demon Dentist by David Walliams', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(252, 'book_681e34b06137c_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', '/uploads/books/book_681e34b06137c_Earwig_and_the_Witch_by_Diana_Wynne_Jones.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 80326, 'Cover image for Earwig and the Witch by Diana Wynne Jones', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(253, 'book_681e34b07cb19_Gangsta_Granny_by_David_Walliams.jpeg', '/uploads/books/book_681e34b07cb19_Gangsta_Granny_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 51919, 'Cover image for Gangsta Granny by David Walliams', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(254, 'book_681e34b098063_Goblins_by_Philip_Reeve.jpg', '/uploads/books/book_681e34b098063_Goblins_by_Philip_Reeve.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 72542, 'Cover image for Goblins by Philip Reeve', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(255, 'book_681e34b0b384c_Harry_Potter_and_the_Philosophers_Stone.jpeg', '/uploads/books/book_681e34b0b384c_Harry_Potter_and_the_Philosophers_Stone.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 99267, 'Cover image for Harry Potter and the Philosopher\'s Stone by J.K. Rowling', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(256, 'book_681e34b0cef8f_Holes_by_Louis_Sachar.jpg', '/uploads/books/book_681e34b0cef8f_Holes_by_Louis_Sachar.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 117700, 'Cover image for Holes by Louis Sachar', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(257, 'book_681e34b0ea144_Kidnap_oin_the_Caribbean.jpeg', '/uploads/books/book_681e34b0ea144_Kidnap_oin_the_Caribbean.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 66257, 'Cover image for Kidnap in the Caribbean (Laura Marlin Mysteries, Book 2) by Lauren St John', '2025-05-09 18:00:32', '2025-05-09 18:00:32', NULL, NULL),
(258, 'book_681e34b110d5c_Little_Manfred_by_Michael_Morpurgo.jpeg', '/uploads/books/book_681e34b110d5c_Little_Manfred_by_Michael_Morpurgo.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 47492, 'Cover image for Little Manfred by Michael Morpurgo', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(259, 'book_681e34b12c603_Moon_Pie_by_Simon_Mason.jpeg', '/uploads/books/book_681e34b12c603_Moon_Pie_by_Simon_Mason.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 32296, 'Cover image for Moon Pie by Simon Mason', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(260, 'book_681e34b147f02_Mr._Stink_by_David_Walliams.jpeg', '/uploads/books/book_681e34b147f02_Mr._Stink_by_David_Walliams.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 79129, 'Cover image for Mr. Stink by David Walliams', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(261, 'book_681e34b163720_Opal_Moonbaby_by_Maudie_Smith.jpeg', '/uploads/books/book_681e34b163720_Opal_Moonbaby_by_Maudie_Smith.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 688281, 'Cover image for Opal Moonbaby by Maudie Smith', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(262, 'book_681e34b17f073_Ratburger_by_David_Walliams.gif', '/uploads/books/book_681e34b17f073_Ratburger_by_David_Walliams.gif', NULL, NULL, NULL, NULL, 'image/gif', 88341, 'Cover image for Ratburger by David Walliams', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(263, 'book_681e34b19a328_The_Amber_Spyglass_by_Philip_Pullman.jpg', '/uploads/books/book_681e34b19a328_The_Amber_Spyglass_by_Philip_Pullman.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 163816, 'Cover image for The Amber Spyglass by Philip Pullman', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(264, 'book_681e34b1b567b_The_Boy_in_the_Dress_by_David_Walliams.jpg', '/uploads/books/book_681e34b1b567b_The_Boy_in_the_Dress_by_David_Walliams.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 65531, 'Cover image for The Boy in the Dress by David Walliams', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(265, 'book_681e34b1cff0c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', '/uploads/books/book_681e34b1cff0c_The_Brilliant_World_of_Tom_Gates_by_Liz_Pichon.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 60753, 'Cover image for The Brilliant World of Tom Gates by Liz Pichon', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(266, 'book_681e34b1ea7e8_The_Chronicles_of_Narnia_The_Lion_the_Witch_and_the_Wardrobe.jpg', '/uploads/books/book_681e34b1ea7e8_The_Chronicles_of_Narnia_The_Lion_the_Witch_and_the_Wardrobe.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 116885, 'Cover image for The Chronicles of Narnia: The Lion, the Witch and the Wardrobe by C.S. Lewis', '2025-05-09 18:00:33', '2025-05-09 18:00:33', NULL, NULL),
(267, 'book_681e34b210f0e_The_Graveyard_Book_By_Neil_Gaiman.jpeg', '/uploads/books/book_681e34b210f0e_The_Graveyard_Book_By_Neil_Gaiman.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 245492, 'Cover image for The Graveyard Book By Neil Gaiman', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(268, 'book_681e34b22b7e5_The_Hobbit_by_J.R.R._Tolkien.jpg', '/uploads/books/book_681e34b22b7e5_The_Hobbit_by_J.R.R._Tolkien.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 133517, 'Cover image for The Hobbit by J.R.R. Tolkien', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(269, 'book_681e34b245dc5_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', '/uploads/books/book_681e34b245dc5_The_Invention_of_Hugo_Cabret_by_Brian_Selznick.webp', NULL, NULL, NULL, NULL, 'image/webp', 37718, 'Cover image for The Invention of Hugo Cabret by Brian Selznick', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(270, 'book_681e34b2604b7_The_Magnificent_Moon_Hare.jpg', '/uploads/books/book_681e34b2604b7_The_Magnificent_Moon_Hare.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 123070, 'Cover image for The Magnificent Moon Hare by Sue Monroe', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(271, 'book_681e34b27aee4_The_Midnight_Gang_by_David_Walliams.jpg', '/uploads/books/book_681e34b27aee4_The_Midnight_Gang_by_David_Walliams.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 134586, 'Cover image for The Midnight Gang by David Walliams', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(272, 'book_681e34b295977_The_Money_Stan_Big_Lauren_and_Me.jpg', '/uploads/books/book_681e34b295977_The_Money_Stan_Big_Lauren_and_Me.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 94339, 'Cover image for The Money, Stan, Big Lauren and Me by Joanna Nadin', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(273, 'book_681e34b2b0400_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', '/uploads/books/book_681e34b2b0400_The_Mystery_of_the_Whistling_Caves_by_Helen_Moss.webp', NULL, NULL, NULL, NULL, 'image/webp', 331984, 'Cover image for The Mystery of the Whistling Caves by Helen Moss', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(274, 'book_681e34b2cb2a9_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', '/uploads/books/book_681e34b2cb2a9_The_Peppers_and_the_International_Magic_Guys_by_Sian_Pattenden.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 147249, 'Cover image for The Peppers and the International Magic Guys by Sian Pattenden', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(275, 'book_681e34b2e62d7_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', '/uploads/books/book_681e34b2e62d7_The_Spiderwick_Chronicles_The_Field_Guide_by_Tony_DiTerlizzi_and_Holly_Black.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 45477, 'Cover image for The Spiderwick Chronicles: The Field Guide by Tony DiTerlizzi and Holly Black', '2025-05-09 18:00:34', '2025-05-09 18:00:34', NULL, NULL),
(276, 'book_681e34b30cd4f_The_Whizz_Pop_Chocolate_Shop.jpg', '/uploads/books/book_681e34b30cd4f_The_Whizz_Pop_Chocolate_Shop.jpg', NULL, NULL, NULL, NULL, 'image/jpeg', 78222, 'Cover image for The Whizz Pop Chocolate Shop by Kate Saunders', '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, NULL),
(277, 'book_681e34b327cab_The_Worst_Witch_by_Jill_Murphy.jpeg', '/uploads/books/book_681e34b327cab_The_Worst_Witch_by_Jill_Murphy.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 536466, 'Cover image for The Worst Witch by Jill Murphy', '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, NULL),
(278, 'book_681e34b346a08_To_Be_A_Cat_by_Matt_Haig.jpeg', '/uploads/books/book_681e34b346a08_To_Be_A_Cat_by_Matt_Haig.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 229771, 'Cover image for To Be A Cat by Matt Haig', '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, NULL),
(279, 'book_681e34b361c7f_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', '/uploads/books/book_681e34b361c7f_Tuck_Everlasting_by_Natalie_Babbitt.jpeg', NULL, NULL, NULL, NULL, 'image/jpeg', 345456, 'Cover image for Tuck Everlasting by Natalie Babbitt', '2025-05-09 18:00:35', '2025-05-09 18:00:35', NULL, NULL);

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
-- Table structure for table `publishers`
--

CREATE TABLE `publishers` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `publishers`
--

INSERT INTO `publishers` (`id`, `name`, `slug`, `website`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Bloomsbury Publishing Plc', 'bloomsbury-publishing-plc', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(2, 'Yearling Books, an imprint of Random House Children\'s Books', 'yearling-books-an-imprint-of-random-house-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(3, 'HarperCollins Children\'s Books', 'harpercollins-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(4, 'Frances Lincoln', 'frances-lincoln', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(5, 'The Bodley Head, an imprint of Random House Children\'s Books', 'the-bodley-head-an-imprint-of-random-house-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(6, 'Puffin Books, a division of Penguin Books Ltd', 'puffin-books-a-division-of-penguin-books-ltd', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(7, 'Simon & Schuster Books for Young Readers', 'simon-schuster-books-for-young-readers', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(8, 'Simon & Schuster Children\'s Books', 'simon-schuster-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(9, 'Orion Children\'s Books', 'orion-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(10, 'Scholastic Children\'s Books', 'scholastic-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(11, 'Harper Collins Children’s Books', 'harper-collins-children-s-books', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54'),
(12, 'Marion Lloyd Books, an imprint of Scholastic Ltd', 'marion-lloyd-books-an-imprint-of-scholastic-ltd', NULL, NULL, '2025-05-10 10:45:54', '2025-05-10 10:45:54');

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
(2, 'parent', 'Another Story2', 'More story content...22', 'Another great story...2', 'another-story2', 1, 0, 2.1, 1, 10, '2 minutes', 0, '2+', 0, 1, 0, 'https://example.com/cover2.jpg2', '2025-04-26 08:17:50', '2025-04-27 10:38:19'),
(1663, 'child', 'A Windy Day by Dearbhla, aged 9, from Northern Ireland', '## Author\n\n**Name:** Dearbhla\n\n**Age:** 9\n\n**Location:** Northern Ireland\n\n## Summary\n\nOn a windy day, a little girl experiences the swirling leaves and the howling wind, bringing the world to life around her.\n\n## Story\n\nIt\'s a windy day, So I go out to play. In the garden the leaves are scattered, And the trees bow down battered. It\'s a very windy day, So I go out to play. At the park the swings tie in a bow, While the sparrows start too and fro. It\'s an extremely windy day, So I go out to play. At the beach the sand blows wild, The ships crash with the tide.\n', 'On a windy day, a little girl experiences the swirling leaves and the howling wind, bringing the world to life around her.', 'a-windy-day', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/whimsical-wind-playful-girl.png', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(1664, 'child', 'Autumn poem by Niall, aged 9, from Omagh Northern Ireland. CO.Tyrone', '## Author\n\n**Name:** Niall\n\n**Age:** 9\n\n**Location:** Omagh, Northern Ireland\n\n## Summary\n\nA delightful poem capturing the essence of autumn with its vibrant colors, falling leaves, and the animals preparing for the season.\n\n## Story\n\nAnimals Hibernating\n\nU is for the hedgehogs Under the leaves\n\nT is for the Trees swishing in the autumn wind\n\nU is for the birds Up in the sky\n\nM is for Me playing in the leaves\n\nN is for nuts falling from the trees.\n', 'A delightful poem capturing the essence of autumn with its vibrant colors, falling leaves, and the animals preparing for the season.', 'autumn-poem-co-tyrone', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/whimsical-autumn-animals.png', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(1665, 'child', 'Christmas by Kerys, aged 7, from Paisley, Renfrewshire', '## **Author**\n\n**Name:** Kerys\n\n**Age:** 7\n\n**Location:** Paisley, Renfrewshire\n\n## Summary\n\nJoin the festive fun as a family gathers around the Christmas tree, singing, ringing jingle bells, and enjoying the best time of the year while their parents relax, in this merry and heartwarming children\'s story.\n\n## The Story\n\nChristmas is a time for family to be together. Having fun all day long while your parents relax. Ringing the bells is so fun. I love Christmas so much. Singing all day long with your family. This time of year is the best time of year. Merry Christmas everyone, have fun. Art and crafts for Christmas from your parents. Singing jingle bells all day is annoying.\n', 'Join the festive fun as a family gathers around the Christmas tree, singing, ringing jingle bells, and enjoying the best time of the year while their parents relax, in this merry and heartwarming children\'s story.', 'christmas-renfrewshire', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/children-storybook-illustration-christmas.png', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(1666, 'child', 'Dave by Aine K, aged 9, from Northern Ireland', '## Author\n\n**Name:** Aine K\n\n**Age:** 9\n\n**Location:** Northern Ireland\n\n## Summary\n\nJoin Dave, a man living in a big cave, on his hilarious and unexpected adventures filled with laughter, mischief, and surprising encounters.\n\n## Story\n\nThere once was a man called Dave Who lived in a very big cave He had no food but he thought that was good And he had no money but be just thought that was funny Until he had to go away To pay A man called Ray.\n', 'Join Dave, a man living in a big cave, on his hilarious and unexpected adventures filled with laughter, mischief, and surprising encounters.', 'dave', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/a-man-who-lived-in-a-cave.png', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(1667, 'child', 'Dewy the Little Library Cat by Orla, aged 11 , from The Smiley Book Club, Northern Ireland', '## **Author**\n\n**Name:** Orla\n\n**Age:** 11\n\n**Location:** The Smiley Book Club, Northern Ireland\n\n## Summary\n\nJoin Dewey the Library cat on his delightful adventures as he quietly sits behind the mat, ready to help children discover magical worlds within the pages of their favourite books.\n\n## The Story\n\nI\'m a little Library cat, give my back a little pat. Watch for me behind the mat. I\'m Dewey the Library cat.\n', 'Join Dewey the Library cat on his delightful adventures as he quietly sits behind the mat, ready to help children discover magical worlds within the pages of their favourite books.', 'dewy-the-little-library-cat-by-orla-aged-11-from-the-smiley-book-club-northern-ireland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/dewey-library-cat-childrens-storybook-illustration.png', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(1668, 'child', 'Dinosaur! by Nadia, aged 10, from Banbridge, Northern Ireland', '## Author\n\n**Name:** Nadia\n\n**Age:** 10\n\n**Location:** Banbridge, Northern Ireland\n\n## Summary\n\nEmbark on a thrilling adventure to the land of dinosaurs, where a brave young explorer encounters these magnificent creatures up close.\n\n## Story\n\nHe heard the heavy footsteps of the beast he had feared. He turned to run but his jumper got caught. He pulled as hard as he could but it was no use. If he couldn\'t run he would have to hide. He managed to get to a bush that was not far away. He ducked down and covered his head with his hands. Not long after, the footsteps stopped and he lifted his head. There was nothing there. He knew he was safe and ran home quickly in case the dinosaur was still there. He was safe and never went out on his own again.\n', 'Embark on a thrilling adventure to the land of dinosaurs, where a brave young explorer encounters these magnificent creatures up close.', 'dinosaur-northern-ireland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/vibrant-dinosaur-adventure.png', '2025-05-10 21:06:50', '2025-05-10 21:06:50'),
(1669, 'child', 'Geckos are sacred by Alfie, aged 9, from Dorset, England', '## **Author**\n\n**Name:** Alfie\n\n**Age:** 9\n\n**Location:** Dorset, England\n\n## Summary\n\nDiscover the enchanting world of geckos, where their sacredness intertwines with valuable lessons about compassion and harmony.\n\n## The Story\n\nGeckos are sacred. Don\'t kill Geckos or you will have to eat 5 courses of CHEESE AND PICKLE SANDWICHES... YUCK! Don\'t kill Geckos or you will fall in a stinky mudbath. Don\'t kill Geckos or they will eat your nose.\n', 'Discover the enchanting world of geckos, where their sacredness intertwines with valuable lessons about compassion and harmony.', 'geckos-are-sacred-england', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/show-playful-geckos-falling-into-a-stinlky.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1670, 'child', 'Little Infinity by Rachel, aged 12, from South Tyneside, England', '## **Author**\n\n**Author:** Rachel\n\n**Age:** 12\n\n**Location:** South Tyneside, England\n\n## Summary\n\nLittle Infinity is a thrilling adventure of a young boy named Sammy who stumbles upon a hidden cottage in the forest. Little does he know that a mad scientist is creating a monstrous creature inside!\n\n## The Story\n\nSammy had been walking through the forest for hours trying to find the cursed diamond sword that he didn\'t believe in at all, it was getting dark and he was getting scared. He was frightened of every sound from a stick cracking, to an owl hooting. The ground was getting damper as it started to rain. Sammy was running through the forest looking for a place to stay for the night. After a while of walking and getting soaking he came across a hill that he had never seen before. So he wandered off up the hill and to his surprise there was a cottage on the very top of the hill. As he climbed to where the cottage was he saw bright flashing lights in the window, different colours every time and there was smoke seeping through the gaps in the door. He could smell the smoke it was making him cough, all he could taste was a burning sensation in his mouth. Wizz, bang, crackle, pop went what Sammy thought was a fire. He knocked the door but the only thing he could hear was an evil \"mwa ha ha ha\" He knocked again, knock.... knock.... knock on the big, black, wooden door, but still no answer. So he opened the door it gave out a loud creak but he wasn\'t hear he opened it out of curiosity and what he saw he would never forget. I mean would you forget if you had seen what he had seen? A mad scientist making a 4 metre high MONSTER!!!! it could have easily been Frankenstein\'s monsters cousin it could have been but we\'ll never really know it looked like Frankenstein\'s monster electric volts in the neck scars on the forehead the only difference was this monster had orange skin, if it was skin anyway. \"Life, life to you, awake from your slumber\" the mad scientist chanted and the monster sat up and grew. Sammy was still watching this the scientist looked around and saw Sammy \"you you\" he shouted \"Sorry sir\" said Sammy “I was only trying to find a place to stay.\" Not right now my boy we\'ve got bigger problems\" the scientist said as the monster was still growing. As the monster grew just bigger than a house it stopped and started crushing everything in his path from cars, to trees, to houses the whole town was at risk of getting squished by a 800kg beast he stampeded all the way to Switzerland to the Swiss alps. Through water, mud and marshes he marched to Monte Rosa the highest point in the Swiss alps he climbed with ease up the 4634 metre rocky, icy covered mountain. His thundering footsteps almost caused an avalanche BANG BANG went his huge feet crushing trees until he got to the highest point. Well after he was on it it wasn\'t really a point any more it was more of a giant plate. He sat down on it and watched the incredible sunset then went to sleep he woke up to a lovely sunrise feeling calm refreshed. As he stood up he saw a green figure moving slowly up the mountain, it looked like him but it had green skin he immediately recognised the face. It was Frankenstein\'s monster his long lost cousin “come on come on” the orange monster shouted as happy as a lonely monster could get. He was crying with tears of joy he couldn\'t believe it Frankenstein\'s monster ran up the mountain as quick as he could. They then gave each other a big cuddle, they were crying in each others arms it was the happiest day of their lives since they were created” I love you” they said to each other crying and they slowly began to shrink. Their hands turned peach their skin turned peach they were turning human the orange monster had bright blue eyes and ginger hair and Frankenstein\'s monster had black hair and really dark brown eyes. The orange monster said “we are human no one will run away from us anymore “I am changing my name to Benny.” “I\'m changing my name to Frank “said Frankenstein\'s monster.” “Come on let\'s go to a restaurant. I am starving, I need a burger and maybe some fries “said Benny “Good idea” said Frank They are still living a very happy life; both aged 45 living in Essex maybe you have visited one of their restaurants? They like taking long holidays anywhere and everywhere so you may have already seen them they are inseparable.\n', 'Little Infinity is a thrilling adventure of a young boy named Sammy who stumbles upon a hidden cottage in the forest.', 'little-infinity-england', 1, 0, 4.5, 0, 10, '5', 0, '9-12', 0, 1, 0, '/uploads/illustration-sammy-hidden-cottage.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1671, 'child', 'Little Star by Ellie, aged 8, from Northern Ireland', '## **Author**\n\n**Name:** Ellie\n\n**Age:** 8\n\n**Location:** Northern Ireland\n\n## Summary\n\nJoin Little Star on a thrilling adventure through space as he falls from the sky and befriends a little dude, discovering the importance of friendship and facing an unexpected twist in this enchanting children\'s story.\n\n## The Story\n\nOne night in space the stars were having a party. Little star was getting tossed about too much that he fell out of the sky. He was left lying on the ground for a couple of days until a little dude came. Little star was getting so happy until he found out he was going to be eaten.\n', 'Join Little Star on a thrilling adventure through space as he falls from the sky and befriends a little dude, discovering the importance of friendship and facing an unexpected twist in this enchanting children\'s story.', 'little-star', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/little-star-little-dude-childrens-storybook-illustration.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1672, 'child', 'Milly by Abigail, aged 7, from Dorset, England', '## Author\n\n**Name:** Abigail\n\n**Age:** 7\n\n**Location:** Dorset, England\n\n## Summary\n\nFollow Milly, a brave and curious cat, as she embarks on exciting adventures, showing the world her unique and lovable character.\n\n## Story\n\nOnce there was a cat called Milly she was a brave little cat who liked adventures. She had silky black fur that gleamed like the moon and shining blue eyes like sun beams. Now this story begins when Milly was five years old she said to her mum I\'m going to see the world said Milly. But Milly said her mother you are only five years old. I know said Milly so she packed a bag and left...\n', 'Follow Milly, a brave and curious cat, as she embarks on exciting adventures, showing the world her unique and lovable character.', 'milly-england', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/mischievous-cat-illustration.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1673, 'child', 'Monster Crush by Eve, aged 9, from Rhondda, South Wales', '## **Author**\n\n**Name: Eve**\n\n**Age:** 9\n\n**Location:** Rhondda, South Wales\n\n## Summary\n\nSammy goes on an adventurous journey through a forest, only to stumble upon a mad scientist and his giant monster creation. Little does he know that his encounter will lead to a heartwarming reunion and a lifelong friendship.\n\n## The Story\n\nSammy had been walking through the forest for hours trying to find the cursed diamond sword that he didn\'t believe in at all, it was getting dark and he was getting scared. He was frightened of every sound from a stick cracking, to an owl hooting. The ground was getting damper as it started to rain. Sammy was running through the forest looking for a place to stay for the night. After a while of walking and getting soaking he came across a hill that he had never seen before. So he wandered off up the hill and to his surprise there was a cottage on the very top of the hill. As he climbed to where the cottage was he saw bright flashing lights in the window, different colours every time and there was smoke seeping through the gaps in the door. He could smell the smoke it was making him cough, all he could taste was a burning sensation in his mouth. Wizz, bang, crackle, pop went what Sammy thought was a fire. He knocked the door but the only thing he could hear was an evil \"mwa ha ha ha\" He knocked again, knock.... knock.... knock on the big, black, wooden door, but still no answer. So he opened the door it gave out a loud creak but he wasn\'t hear he opened it out of curiosity and what he saw he would never forget. I mean would you forget if you had seen what he had seen? A mad scientist making a 4 metre high MONSTER!!!! it could have easily been Frankenstein\'s monsters cousin it could have been but we\'ll never really know it looked like Frankenstein\'s monster electric volts in the neck scars on the forehead the only difference was this monster had orange skin, if it was skin anyway. \"Life, life to you, awake from your slumber\" the mad scientist chanted and the monster sat up and grew. Sammy was still watching this the scientist looked around and saw Sammy \"you you\" he shouted \"Sorry sir\" said Sammy “I was only trying to find a place to stay.\" Not right now my boy we\'ve got bigger problems\" the scientist said as the monster was still growing. As the monster grew just bigger than a house it stopped and started crushing everything in his path from cars, to trees, to houses the whole town was at risk of getting squished by a 800kg beast he stampeded all the way to Switzerland to the Swiss alps. Through water, mud and marshes he marched to Monte Rosa the highest point in the Swiss alps he climbed with ease up the 4634 metre rocky, icy covered mountain. His thundering footsteps almost caused an avalanche BANG BANG went his huge feet crushing trees until he got to the highest point. Well after he was on it it wasn\'t really a point any more it was more of a giant plate. He sat down on it and watched the incredible sunset then went to sleep he woke up to a lovely sunrise feeling calm refreshed. As he stood up he saw a green figure moving slowly up the mountain, it looked like him but it had green skin he immediately recognised the face. It was Frankenstein\'s monster his long lost cousin “come on come on” the orange monster shouted as happy as a lonely monster could get. He was crying with tears of joy he couldn\'t believe it Frankenstein\'s monster ran up the mountain as quick as he could. They then gave each other a big cuddle, they were crying in each others arms it was the happiest day of their lives since they were created” I love you” they said to each other crying and they slowly began to shrink. Their hands turned peach their skin turned peach they were turning human the orange monster had bright blue eyes and ginger hair and Frankenstein\'s monster had black hair and really dark brown eyes. The orange monster said “we are human no one will run away from us anymore “I am changing my name to Benny.” “I\'m changing my name to Frank “said Frankenstein\'s monster.” “Come on let\'s go to a restaurant. I am starving, I need a burger and maybe some fries “said Benny “Good idea” said Frank They are still living a very happy life; both aged 45 living in Essex maybe you have visited one of their restaurants? They like taking long holidays anywhere and everywhere so you may have already seen them they are inseparable.\n', 'Sammy goes on an adventurous journey through a forest, only to stumble upon a mad scientist and his giant monster creation.', 'monster-crush-south-wales', 1, 0, 4.5, 0, 10, '5', 0, '9-12', 0, 1, 0, '/uploads/sammy-benny-reunion-swiss-alps.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1674, 'child', 'My Bog Poeming by Cory, aged 3, from Cae Garw, Wales', '## **Author**\n\n**Name:** Cory\n\n**Age:** 3\n\n**Location:** Cae Garw, Wales\n\n## Summary\n\nJoin the exciting adventures of a lively 9-year-old dog who loves to sleep, play, bark, howl at night, and fetch toys, in this whimsical children\'s storybook illustration.\n\n## The Story\n\nMy dog likes to sleep and play and bark and he his very playful and 9 years old he howls in the night cos he is scared and likes to follow my mum around the house and when he wants to play he fached is his toys.\n', 'Join the exciting adventures of a lively 9-year-old dog who loves to sleep, play, bark, howl at night, and fetch toys, in this whimsical children\'s storybook illustration.', 'my-bog-poeming-wales', 1, 0, 4.5, 0, 10, '1', 0, '3-5', 0, 1, 0, '/uploads/playful-dog-adventures-children-storybook-illustration.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1675, 'child', 'My Cat by Ria, aged 8, from Hayes, Hillingdon', '## Author\n\n**Name:** Ria\n\n**Age:** 8\n\n**Location:** Hayes, Hillingdon\n\n## Summary\n\nA delightful tale about a fluffy and playful cat that brings joy and laughter to its owner\'s life.\n\n## Story\n\nMy cat is very fluffy, And she can play the flute, She\'s also very puffy\' But very very cute. My cat is also pretty, But she\'s also kind of weird, She goes around the city, Please don\'t be feared. My cat is a kitten , She\'s also very small, She likes to where her mittens, She can never grow tall.\n', 'A delightful tale about a fluffy and playful cat that brings joy and laughter to its owner\'s life.', 'my-cat-hillingdon', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/playful-cat-city-storybook-illustration.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1676, 'child', 'My Favourite Book Character by Rebekah, aged 9, from Lurgan Co. Armagh, Northern Ireland', '## **Author**\n\n**Name:** Rebekah\n\n**Age:** 9\n\n**Location:** Lurgan Co. Armagh, Northern Ireland\n\n## Summary\n\nJoin Matilda, the brilliant and imaginative bookworm, as she embarks on exciting adventures, unravels hidden treasures, and discovers the magic within the pages of her beloved storybooks.\n\n## The Story\n\nMy favourite book character is Matilda. Because she is full of great ideas and adventures. I think Matilda is a brilliant book character because I love the way she loves to read books. Another reason why I love Matilda is that I absouloutly love Roald Dahl books. I think he is a really interesting and funny author. I love everyone of his books. I love them that much I\'ve nearly read all of them. I love to read.\n', 'Join Matilda, the brilliant and imaginative bookworm, as she embarks on exciting adventures, unravels hidden treasures, and discovers the magic within the pages of her beloved storybooks.', 'my-favourite-book-character-armagh-northern-ireland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/matilda-adventure-treasure-storybook-illustration.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1677, 'child', 'My Secret Wish List by Leah, aged 11, from Rhondda, South Wales', '## **Author**\n\n**Name:** Leah\n\n**Age:** 11\n\n**Location:** Rhondda, South Wales\n\n## Summary\n\nJoin Leagh on an exciting adventure as their Christmas wishes magically come true, bringing an iPad, a dog, their own bedroom, a fridge, and a computer in this fun-filled children\'s story.\n\n## The Story\n\nWish 1: I want to have an iPad for Christmas.\n\nnn\n\nWish 2: I want a dog for Christmas.\n\nnn\n\nWish 3: I want my own bedroom.\n\nnn\n\nWish 4: I want my own fridge.\n\nnn\n\nWish 5: I want a computer.\n', 'Join Leagh on an exciting adventure as their Christmas wishes magically come true, bringing an iPad, a dog, their own bedroom, a fridge, and a computer in this fun-filled children\'s story.', 'my-secret-wish-list-south-wales', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/leah-christmas-wishes-children-storybook-illustration.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1678, 'child', 'Nice day out with mummy x by Ashton, aged 6, from Jarrow', '## **Author**\n\n**Name:** Ashton\n\n**Age:** 6\n\n**Location:** Jarrow\n\n## Summary\n\nJoin Ashton on her exciting day as she enjoys breakfast at McDonald\'s, takes the metro to Hebburn library, and carries her trusty Hello Kitty umbrella to stay dry.\n\n## The Story\n\nWe went to mcdonalds for our breakfast then we took the metro to Hebburn library. I have got my hello kitty umberella with me in case it rains today. I am going to get some sweets after the library because I have been a good girl all day and all through the holidays for my mummy. have a nice day . x\n', 'Join Ashton on her exciting day as she enjoys breakfast at McDonald\'s, takes the metro to Hebburn library, and carries her trusty Hello Kitty umbrella to stay dry.', 'nice-day-out-with-mummy-x-by-ashton-aged-6-from-jarrow', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/ashtons-adventure-hello-kitty-umbrella-hebburn-library.png', '2025-05-10 21:06:51', '2025-05-10 21:06:51'),
(1679, 'child', 'Omagh Library of the Future by Danielle, aged 11, from Omagh', '## Author\n\n**Name:** Danielle\n\n**Age:** 11\n\n**Location:** Omagh, Northern Ireland\n\n## Summary\n\nExplore the magical world of Omagh Library, where books come to life and the librarians of the future are robots with a sense of humor.\n\n## Story\n\nMy local library is called: **Omagh Library**\n\nWhat\'s your local library like? **It\'s comfortable, has lots of good books in it.It has computers and things that children can play or do homework on.**\n\nWhat will it be like in the year 2150? **It will have T.Vs everywhere,a chocolate fountain in it and it will be very big inside and small outside. There will be loads of books and when you open a book you can watch the story.**\n\nWhat will the librarians be like in the year 2150? **The librarians will be robots and will be telling jokes instead of giving out information.**\n\nWhat do you like about using the library now? **I can get books out for free and I can use the computer.**\n', 'Explore the magical world of Omagh Library, where books come to life and the librarians of the future are robots with a sense of humor.', 'omagh-library-of-the-future', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, 'https://api.storiesfromtheweb.org/images/default-cover.svg', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1680, 'child', 'All About Me by Abbey-lei, aged 10, from Killyclogher, Northern Ireland', '## Author\n\n**Name:** Abbey-lei\n\n**Age:** 10\n\n**Location:** Killyclogher, Northern Ireland\n\n## Summary\n\noin Abbey-lei, a lively 10-year-old from Killyclogher, as she explores a whimsical pink world filled with her favourite things, including vibrant colours, adorable elephants, delicious chicken wraps, and catchy tunes, creating a fun and exciting journey for children\'s imaginations.\n\n## Story\n\nFavourite Colour: **Pink**\n\nFavourite Animal: **Elephant**\n\nFavourite Food: **Chicken wraps**\n\nFavourite Music: **Nicki Minaj**\n\nFavourite TV Programme: **Victorious**\n\nThings I Like: **Clothes, me, Justin Bieber**\n\nThings I Don\'t Like: **Dry things**\n\nWould Love To Meet: **Justin Bieber**\n', 'oin Abbey-lei, a lively 10-year-old from Killyclogher, as she explores a whimsical pink world filled with her favourite things, including vibrant colours, adorable elephants, delicious chicken wraps, and catchy tunes, creating a fun and exciting journey for children\'s imaginations.', 'all-about-me-northern-ireland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/girl-likes-pink-children\'s-storybook.png', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1681, 'child', 'The Beast Within by Chris, aged 11, from Perth and Kinross, Scotland', '## Author\n\n**Name:** Chris\n\n**Age:** 11\n\n**Location:** Perth and Kinross, Scotland\n\n## Summary\n\nFollow the thrilling tale of a young hero who must confront the beast within and find the strength to overcome his darkest fears.\n\n## Story\n\nIt was charging at me with claws like knives and a breath like a sewer rat. It suddenly came to a halt about 10 feet away from me and roared. I ran up to it and tried to hit it with my broom but it moved to the side and swiped at me with its sharp claws. I fell to the ground and looked at my bleeding scar. I limped over the road as fast as I could. I felt a thud behind me. As I turned my head I watched two shadows run away from the beast. Still limping I looked at the fallen beast, he snorted. I looked at him for a minute and suddenlly ripped one of his claws off and impaled him with it...\n', 'Follow the thrilling tale of a young hero who must confront the beast within and find the strength to overcome his darkest fears.', 'the-beast-within-scotland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/boy-meets-beast-children\'s-storybook.png', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1682, 'child', 'The Day Before Yesterday by Meranie, aged 9, from Luton, England', '## **Author**\n\n**Name:** Meranie\n\n**Age:** 9\n\n**Location:** Luton, England\n\n## Summary\n\nJoin the bean-man, the plate, and the fork as they embark on a whimsical quest to find the knife and the spoon, only to encounter a mischievous and powerful bean-man named Sheep who can turn back time in the land of ridiculous times.\n\n## The Story\n\nOne day in the land of ridiculous times there lived a bean-man, a plate and a fork. They were going on a quest to find the knife and the spoon. Suddenly a flash of light stung their eyes and they fell on the ground, when they woke they found a big evil bean-man called Sheep. He turned the world into the day before yesterday. That\'s how it all began!!\n', 'Join the bean-man, the plate, and the fork as they embark on a whimsical quest to find the knife and the spoon, only to encounter a mischievous and powerful bean-man named Sheep who can turn back time in the land of ridiculous times.', 'the-day-before-yesterday-england', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/children-storybook-illustration-adventures-bean-man-plate-fork-sheep.png', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1683, 'child', 'The Magic Treehouse  Kerry, aged 9, from Northern Ireland', '## **Author**\n\n**Name:** Kerry\n\n**Age:** 9\n\n**Location:** Northern Ireland\n\n## Summary\n\nJoin Jake and Katy on their exciting adventure as their new tree house takes them on a magical underwater journey filled with friendly fish, enchanting coral reefs, and a golden mermaid.\n\n## The Story\n\nJake and Katy looked up at their new tree house they couldn\'t wait to play in it with all their friends but they wanted to be first in it. Katy dashed up the ladder followed by Jake when they reached the top an amazing sight met their eyes a glowing gold key sat beside the doll. House they opened the door but it was too late they were in a whole different place they were under water. A gold mermaid swam across their face they were breathing, the tree house was magic!!!\n', 'Join Jake and Katy on their exciting adventure as their new tree house takes them on a magical underwater journey filled with friendly fish, enchanting coral reefs, and a golden mermaid.', 'the-magic-treehouse-kerry-aged-9-from-northern-ireland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/magical-underwater-tree-house-adventure-childrens-story-book-illustration.png', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1684, 'child', 'The Optition by Alice, aged 7, from Scotland', '## **Author**\n\n**Author: Allice**\n\n**Age:** 7\n\n**Location:** Scotland\n\n## Summary\n\nAlice visits an optician with her mom and gets a surprise during the eye test. She ends up making her own pair of glasses and discovers the joy of creativity and imagination.\n\n## The Story\n\nOne day I went with my mum to get my eyes test I really wanted a pair of glasses when the other girl came out it was my turn. my mum said be brave then a lady put the lights off. the lady said what does this say. i got them all correct so she said that I do not need glasses. I was a bit upset. so my mum thought and said how about we make a pair. I said that is a very good idea so I got and made a pair of glasses I invited my friend over and we made a pair of glasses. I like my new pair of glasses they are very nice THE END\n', 'Alice visits an optician with her mom and gets a surprise during the eye test.', 'the-optition', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/illustration-alice-optician.png', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1685, 'child', 'The Reader and the Old Library by Lisa, aged 10, from Inverclyde, Scotland', '## Author\n\n**Name:** Lisa\n\n**Age:** 10\n\n**Location:** Inverclyde, Scotland\n\n## Summary\n\nJoin Rani on an exciting journey through books as she discovers a hidden treasure, encounters mysterious posters, and unlocks the magic of imagination in a whimsical and enchanting story.\n\n## Story\n\nRani loved reading. In her room, walking down the street or even in her sleep. One day Rani was trying to find her favourite book, when she found an old looking book called Journey to the Lost Land. It didn\'t look long to read this book and make it her favourite. She was walking down the street one day and reading a book when she found herself walking into an old, deserted building. Rani looked around and saw it was full of old books but the one that stood out to her was a bright coloured poster. Well, a piece of a poster with a piece of paper attached saying : Find More Pieces Around This Library. There is three bits in total. Rani found one on top of an old bookshelf. She couldn\'t find the last one so she decided to give up. When she was walking out she found the last piece on a poster for the Journey to the Lost Land! Rani stuck the pieces together and it was a poster for the new book from D. K. Bookworm who wrote The Journey to the Lost Land. It was called Living in the Lost Land. So she bought and read it straight away!\n', 'Join Rani on an exciting journey through books as she discovers a hidden treasure, encounters mysterious posters, and unlocks the magic of imagination in a whimsical and enchanting story.', 'the-reader-and-the-old-library-scotland', 1, 0, 4.5, 0, 10, '2', 0, '9-12', 0, 1, 0, 'https://api.storiesfromtheweb.org/images/default-cover.svg', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1686, 'child', 'The Red Robot and the Rabbit by Ella, Willow, Carlotta & Jodie , aged 10-11, from Poole, England', '## Summary\r\n\r\nJoin the red robot and the rabbit on a whimsical adventure in a leafy lane where kindness and friendship bloom, and a mischievous rabbit learns the joy of being good for a bowl of rice.\r\n\r\n## Story\r\n\r\n<p>The red robot and the rabbit, met down a leafy lane... where the rabbit was being a pain the robot said, \"be nice and if you\'re good I\'ll give you some rice\".</p>', 'Join the red robot and the rabbit on a whimsical adventure in a leafy lane where kindness and friendship bloom, and a mischievous rabbit learns the joy of being good for a bowl of rice.', 'the-red-robot-and-the-rabbit-willow-carlotta-jodie-aged-10-11-from-poole-england', 1, 0, 0.0, 0, 0, '1 minute', 0, '7-12', 0, 1, 0, 'https://api.storiesfromtheweb.org/uploads/magical-pebble-adventure-childrens-storybook-illustration.png', '2025-05-10 21:06:52', '2025-05-10 21:07:35'),
(1687, 'child', 'Tim Can by Melissa, aged 10, from Paisley', '## **Author**\n\n**Name:** Melissa\n\n**Age:** 10\n\n**Location:** Paisley\n\n## Summary\n\nJoin Tim the can on his exciting adventure in the grocery store, where he meets new friends and curious children in this fun-filled children\'s storybook tale.\n\n## The Story\n\nThere was a can called Tim and one day Tim said I can\'t wait until somebody buys me. And then a women walked up to Tim and bought him. After she used him and then put him in the bin. Tim said is that all said Tim and then a box said no of course not next week you will be something different. The end\n', 'Join Tim the can on his exciting adventure in the grocery store, where he meets new friends and curious children in this fun-filled children\'s storybook tale.', 'tim-can', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/tim-the-can-children-storybook-illustration.png', '2025-05-10 21:06:52', '2025-05-10 21:06:52'),
(1688, 'child', 'Time Stood Still by Joshua , aged 8, from Spring Hill, Birmingham, England', '## **Author**\n\n**Name:** Joshua\n\n**Age:** 8\n\n**Location:** Spring Hill, Birmingham, England\n\n## Summary\n\nJoin Joshua on a thrilling journey as a shiny pebble brings his garden to life in this enchanting tale of magic and mischief!\n\n## The Story\n\nOne day I was walking in my garden when I saw a small, shiny pebble on the floor. . . but it wasn\'t a pebble, it was a penny. I went inside to wash it. Then I rubbed it with a paper towel. Suddenly everything stopped. My sister stopped while playing on the Wii. My mom stopped while cleaning the display. My dad stopped while hammering a nail. The only thing that didn\'t stop was me ... This could change my life. The end.\n', 'Join Joshua on a thrilling journey as a shiny pebble brings his garden to life in this enchanting tale of magic and mischief!', 'time-stood-still-birmingham-england', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/magical-pebble-adventure-childrens-storybook-illustration.png', '2025-05-10 21:06:53', '2025-05-10 21:06:53'),
(1689, 'child', 'Vampire by Jane, aged 9, from East Dunbartonshire', '## Author\n\n**Name:** Jane\n\n**Age:** 9\n\n**Location:** East Dunbartonshire\n\n## Summary\n\nA young boy finds himself facing a fearsome vampire, read as the story unfolds.\n\n## Story\n\nBlood dribbling down her chin, Her heart not beating, but full of sin. Children trembling in their beds with fright, Wondering who will be her victim tonight. Her cape drags along the street, Footsteps echoing on the concrete. Head tilted, she sniffs the air, Sensing a child is not far from there. She bursts into the room, her fangs bared, The unlucky little boy is awfully scared. She races towards him, and lunges for his neck... But nobody knows what happens next. Did she get him, is he dead? Or maybe he got away instead? Although one thing is true, one thing\'s for sure, That vampires live forever more.\n', 'A young boy finds himself facing a fearsome vampire, read as the story unfolds.', 'vampire', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/boy-meets-vampire-storybook-illustration.png', '2025-05-10 21:06:53', '2025-05-10 21:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `stories_backup`
--

CREATE TABLE `stories_backup` (
  `id` int NOT NULL DEFAULT '0',
  `source_type` enum('child','parent','classic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'child',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `featured` tinyint(1) DEFAULT '0',
  `average_rating` decimal(3,1) DEFAULT '4.5',
  `allow_reviews` tinyint(1) NOT NULL DEFAULT '0',
  `review_count` int DEFAULT '10',
  `estimated_reading_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '5 minutes',
  `is_sponsored` tinyint(1) DEFAULT '0',
  `age_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '12+',
  `needs_moderation` tinyint(1) DEFAULT '0',
  `is_self_published` tinyint(1) DEFAULT '1',
  `is_ai_enhanced` tinyint(1) DEFAULT '0',
  `cover_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'https://example.com/cover.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `stories_backup`
--

INSERT INTO `stories_backup` (`id`, `source_type`, `title`, `content`, `excerpt`, `slug`, `is_published`, `featured`, `average_rating`, `allow_reviews`, `review_count`, `estimated_reading_time`, `is_sponsored`, `age_group`, `needs_moderation`, `is_self_published`, `is_ai_enhanced`, `cover_url`, `created_at`, `updated_at`) VALUES
(2, 'parent', 'Another Story2', 'More story content...22', 'Another great story...2', 'another-story2', 1, 0, 2.1, 1, 10, '2 minutes', 0, '2+', 0, 1, 0, 'https://example.com/cover2.jpg2', '2025-04-26 08:17:50', '2025-04-27 10:38:19'),
(1474, 'child', 'A Windy Day by Dearbhla, aged 9, from Northern Ireland', '## Author\n\n**Name:** Dearbhla\n\n**Age:** 9\n\n**Location:** Northern Ireland\n\n## Summary\n\nOn a windy day, a little girl experiences the swirling leaves and the howling wind, bringing the world to life around her.\n\n## Story\n\nIt\'s a windy day, So I go out to play. In the garden the leaves are scattered, And the trees bow down battered. It\'s a very windy day, So I go out to play. At the park the swings tie in a bow, While the sparrows start too and fro. It\'s an extremely windy day, So I go out to play. At the beach the sand blows wild, The ships crash with the tide.\n', 'On a windy day, a little girl experiences the swirling leaves and the howling wind, bringing the world to life around her.', 'indy-ay', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/whimsical-wind-playful-girl.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1475, 'child', 'Autumn poem by Niall, aged 9, from Omagh Northern Ireland. CO.Tyrone', '## Author\n\n**Name:** Niall\n\n**Age:** 9\n\n**Location:** Omagh, Northern Ireland\n\n## Summary\n\nA delightful poem capturing the essence of autumn with its vibrant colors, falling leaves, and the animals preparing for the season.\n\n## Story\n\nAnimals Hibernating\n\nU is for the hedgehogs Under the leaves\n\nT is for the Trees swishing in the autumn wind\n\nU is for the birds Up in the sky\n\nM is for Me playing in the leaves\n\nN is for nuts falling from the trees.\n', 'A delightful poem capturing the essence of autumn with its vibrant colors, falling leaves, and the animals preparing for the season.', 'utumn-poem-yrone', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/whimsical-autumn-animals.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1476, 'child', 'Christmas by Kerys, aged 7, from Paisley, Renfrewshire', '## **Author**\n\n**Name:** Kerys\n\n**Age:** 7\n\n**Location:** Paisley, Renfrewshire\n\n## Summary\n\nJoin the festive fun as a family gathers around the Christmas tree, singing, ringing jingle bells, and enjoying the best time of the year while their parents relax, in this merry and heartwarming children\'s story.\n\n## The Story\n\nChristmas is a time for family to be together. Having fun all day long while your parents relax. Ringing the bells is so fun. I love Christmas so much. Singing all day long with your family. This time of year is the best time of year. Merry Christmas everyone, have fun. Art and crafts for Christmas from your parents. Singing jingle bells all day is annoying.\n', 'Join the festive fun as a family gathers around the Christmas tree, singing, ringing jingle bells, and enjoying the best time of the year while their parents relax, in this merry and heartwarming children\'s story.', 'hristmas-enfrewshire', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/children-storybook-illustration-christmas.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1477, 'child', 'Dave by Aine K, aged 9, from Northern Ireland', '## Author\n\n**Name:** Aine K\n\n**Age:** 9\n\n**Location:** Northern Ireland\n\n## Summary\n\nJoin Dave, a man living in a big cave, on his hilarious and unexpected adventures filled with laughter, mischief, and surprising encounters.\n\n## Story\n\nThere once was a man called Dave Who lived in a very big cave He had no food but he thought that was good And he had no money but be just thought that was funny Until he had to go away To pay A man called Ray.\n', 'Join Dave, a man living in a big cave, on his hilarious and unexpected adventures filled with laughter, mischief, and surprising encounters.', 'ave', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/a-man-who-lived-in-a-cave.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1478, 'child', 'Dewy the Little Library Cat by Orla, aged 11 , from The Smiley Book Club, Northern Ireland', '## **Author**\n\n**Name:** Orla\n\n**Age:** 11\n\n**Location:** The Smiley Book Club, Northern Ireland\n\n## Summary\n\nJoin Dewey the Library cat on his delightful adventures as he quietly sits behind the mat, ready to help children discover magical worlds within the pages of their favourite books.\n\n## The Story\n\nI\'m a little Library cat, give my back a little pat. Watch for me behind the mat. I\'m Dewey the Library cat.\n', 'Join Dewey the Library cat on his delightful adventures as he quietly sits behind the mat, ready to help children discover magical worlds within the pages of their favourite books.', 'ewy-the-ittle-ibrary-at-by-rla-aged-11-from-he-miley-ook-lub-orthern-reland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/dewey-library-cat-childrens-storybook-illustration.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1479, 'child', 'Dinosaur! by Nadia, aged 10, from Banbridge, Northern Ireland', '## Author\n\n**Name:** Nadia\n\n**Age:** 10\n\n**Location:** Banbridge, Northern Ireland\n\n## Summary\n\nEmbark on a thrilling adventure to the land of dinosaurs, where a brave young explorer encounters these magnificent creatures up close.\n\n## Story\n\nHe heard the heavy footsteps of the beast he had feared. He turned to run but his jumper got caught. He pulled as hard as he could but it was no use. If he couldn\'t run he would have to hide. He managed to get to a bush that was not far away. He ducked down and covered his head with his hands. Not long after, the footsteps stopped and he lifted his head. There was nothing there. He knew he was safe and ran home quickly in case the dinosaur was still there. He was safe and never went out on his own again.\n', 'Embark on a thrilling adventure to the land of dinosaurs, where a brave young explorer encounters these magnificent creatures up close.', 'inosaur-orthern-reland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/vibrant-dinosaur-adventure.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1480, 'child', 'Geckos are sacred by Alfie, aged 9, from Dorset, England', '## **Author**\n\n**Name:** Alfie\n\n**Age:** 9\n\n**Location:** Dorset, England\n\n## Summary\n\nDiscover the enchanting world of geckos, where their sacredness intertwines with valuable lessons about compassion and harmony.\n\n## The Story\n\nGeckos are sacred. Don\'t kill Geckos or you will have to eat 5 courses of CHEESE AND PICKLE SANDWICHES... YUCK! Don\'t kill Geckos or you will fall in a stinky mudbath. Don\'t kill Geckos or they will eat your nose.\n', 'Discover the enchanting world of geckos, where their sacredness intertwines with valuable lessons about compassion and harmony.', 'eckos-are-sacred-ngland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/show-playful-geckos-falling-into-a-stinlky.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1481, 'child', 'Little Infinity by Rachel, aged 12, from South Tyneside, England', '## **Author**\n\n**Author:** Rachel\n\n**Age:** 12\n\n**Location:** South Tyneside, England\n\n## Summary\n\nLittle Infinity is a thrilling adventure of a young boy named Sammy who stumbles upon a hidden cottage in the forest. Little does he know that a mad scientist is creating a monstrous creature inside!\n\n## The Story\n\nSammy had been walking through the forest for hours trying to find the cursed diamond sword that he didn\'t believe in at all, it was getting dark and he was getting scared. He was frightened of every sound from a stick cracking, to an owl hooting. The ground was getting damper as it started to rain. Sammy was running through the forest looking for a place to stay for the night. After a while of walking and getting soaking he came across a hill that he had never seen before. So he wandered off up the hill and to his surprise there was a cottage on the very top of the hill. As he climbed to where the cottage was he saw bright flashing lights in the window, different colours every time and there was smoke seeping through the gaps in the door. He could smell the smoke it was making him cough, all he could taste was a burning sensation in his mouth. Wizz, bang, crackle, pop went what Sammy thought was a fire. He knocked the door but the only thing he could hear was an evil \"mwa ha ha ha\" He knocked again, knock.... knock.... knock on the big, black, wooden door, but still no answer. So he opened the door it gave out a loud creak but he wasn\'t hear he opened it out of curiosity and what he saw he would never forget. I mean would you forget if you had seen what he had seen? A mad scientist making a 4 metre high MONSTER!!!! it could have easily been Frankenstein\'s monsters cousin it could have been but we\'ll never really know it looked like Frankenstein\'s monster electric volts in the neck scars on the forehead the only difference was this monster had orange skin, if it was skin anyway. \"Life, life to you, awake from your slumber\" the mad scientist chanted and the monster sat up and grew. Sammy was still watching this the scientist looked around and saw Sammy \"you you\" he shouted \"Sorry sir\" said Sammy “I was only trying to find a place to stay.\" Not right now my boy we\'ve got bigger problems\" the scientist said as the monster was still growing. As the monster grew just bigger than a house it stopped and started crushing everything in his path from cars, to trees, to houses the whole town was at risk of getting squished by a 800kg beast he stampeded all the way to Switzerland to the Swiss alps. Through water, mud and marshes he marched to Monte Rosa the highest point in the Swiss alps he climbed with ease up the 4634 metre rocky, icy covered mountain. His thundering footsteps almost caused an avalanche BANG BANG went his huge feet crushing trees until he got to the highest point. Well after he was on it it wasn\'t really a point any more it was more of a giant plate. He sat down on it and watched the incredible sunset then went to sleep he woke up to a lovely sunrise feeling calm refreshed. As he stood up he saw a green figure moving slowly up the mountain, it looked like him but it had green skin he immediately recognised the face. It was Frankenstein\'s monster his long lost cousin “come on come on” the orange monster shouted as happy as a lonely monster could get. He was crying with tears of joy he couldn\'t believe it Frankenstein\'s monster ran up the mountain as quick as he could. They then gave each other a big cuddle, they were crying in each others arms it was the happiest day of their lives since they were created” I love you” they said to each other crying and they slowly began to shrink. Their hands turned peach their skin turned peach they were turning human the orange monster had bright blue eyes and ginger hair and Frankenstein\'s monster had black hair and really dark brown eyes. The orange monster said “we are human no one will run away from us anymore “I am changing my name to Benny.” “I\'m changing my name to Frank “said Frankenstein\'s monster.” “Come on let\'s go to a restaurant. I am starving, I need a burger and maybe some fries “said Benny “Good idea” said Frank They are still living a very happy life; both aged 45 living in Essex maybe you have visited one of their restaurants? They like taking long holidays anywhere and everywhere so you may have already seen them they are inseparable.\n', 'Little Infinity is a thrilling adventure of a young boy named Sammy who stumbles upon a hidden cottage in the forest.', 'ittle-nfinity-ngland', 1, 0, 4.5, 0, 10, '5', 0, '9-12', 0, 1, 0, '/uploads/illustration-sammy-hidden-cottage.png', '2025-05-09 17:07:45', '2025-05-09 17:07:45'),
(1482, 'child', 'Little Star by Ellie, aged 8, from Northern Ireland', '## **Author**\n\n**Name:** Ellie\n\n**Age:** 8\n\n**Location:** Northern Ireland\n\n## Summary\n\nJoin Little Star on a thrilling adventure through space as he falls from the sky and befriends a little dude, discovering the importance of friendship and facing an unexpected twist in this enchanting children\'s story.\n\n## The Story\n\nOne night in space the stars were having a party. Little star was getting tossed about too much that he fell out of the sky. He was left lying on the ground for a couple of days until a little dude came. Little star was getting so happy until he found out he was going to be eaten.\n', 'Join Little Star on a thrilling adventure through space as he falls from the sky and befriends a little dude, discovering the importance of friendship and facing an unexpected twist in this enchanting children\'s story.', 'ittle-tar', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/little-star-little-dude-childrens-storybook-illustration.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1483, 'child', 'Milly by Abigail, aged 7, from Dorset, England', '## Author\n\n**Name:** Abigail\n\n**Age:** 7\n\n**Location:** Dorset, England\n\n## Summary\n\nFollow Milly, a brave and curious cat, as she embarks on exciting adventures, showing the world her unique and lovable character.\n\n## Story\n\nOnce there was a cat called Milly she was a brave little cat who liked adventures. She had silky black fur that gleamed like the moon and shining blue eyes like sun beams. Now this story begins when Milly was five years old she said to her mum I\'m going to see the world said Milly. But Milly said her mother you are only five years old. I know said Milly so she packed a bag and left...\n', 'Follow Milly, a brave and curious cat, as she embarks on exciting adventures, showing the world her unique and lovable character.', 'illy-ngland', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/mischievous-cat-illustration.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1484, 'child', 'Monster Crush by Eve, aged 9, from Rhondda, South Wales', '## **Author**\n\n**Name: Eve**\n\n**Age:** 9\n\n**Location:** Rhondda, South Wales\n\n## Summary\n\nSammy goes on an adventurous journey through a forest, only to stumble upon a mad scientist and his giant monster creation. Little does he know that his encounter will lead to a heartwarming reunion and a lifelong friendship.\n\n## The Story\n\nSammy had been walking through the forest for hours trying to find the cursed diamond sword that he didn\'t believe in at all, it was getting dark and he was getting scared. He was frightened of every sound from a stick cracking, to an owl hooting. The ground was getting damper as it started to rain. Sammy was running through the forest looking for a place to stay for the night. After a while of walking and getting soaking he came across a hill that he had never seen before. So he wandered off up the hill and to his surprise there was a cottage on the very top of the hill. As he climbed to where the cottage was he saw bright flashing lights in the window, different colours every time and there was smoke seeping through the gaps in the door. He could smell the smoke it was making him cough, all he could taste was a burning sensation in his mouth. Wizz, bang, crackle, pop went what Sammy thought was a fire. He knocked the door but the only thing he could hear was an evil \"mwa ha ha ha\" He knocked again, knock.... knock.... knock on the big, black, wooden door, but still no answer. So he opened the door it gave out a loud creak but he wasn\'t hear he opened it out of curiosity and what he saw he would never forget. I mean would you forget if you had seen what he had seen? A mad scientist making a 4 metre high MONSTER!!!! it could have easily been Frankenstein\'s monsters cousin it could have been but we\'ll never really know it looked like Frankenstein\'s monster electric volts in the neck scars on the forehead the only difference was this monster had orange skin, if it was skin anyway. \"Life, life to you, awake from your slumber\" the mad scientist chanted and the monster sat up and grew. Sammy was still watching this the scientist looked around and saw Sammy \"you you\" he shouted \"Sorry sir\" said Sammy “I was only trying to find a place to stay.\" Not right now my boy we\'ve got bigger problems\" the scientist said as the monster was still growing. As the monster grew just bigger than a house it stopped and started crushing everything in his path from cars, to trees, to houses the whole town was at risk of getting squished by a 800kg beast he stampeded all the way to Switzerland to the Swiss alps. Through water, mud and marshes he marched to Monte Rosa the highest point in the Swiss alps he climbed with ease up the 4634 metre rocky, icy covered mountain. His thundering footsteps almost caused an avalanche BANG BANG went his huge feet crushing trees until he got to the highest point. Well after he was on it it wasn\'t really a point any more it was more of a giant plate. He sat down on it and watched the incredible sunset then went to sleep he woke up to a lovely sunrise feeling calm refreshed. As he stood up he saw a green figure moving slowly up the mountain, it looked like him but it had green skin he immediately recognised the face. It was Frankenstein\'s monster his long lost cousin “come on come on” the orange monster shouted as happy as a lonely monster could get. He was crying with tears of joy he couldn\'t believe it Frankenstein\'s monster ran up the mountain as quick as he could. They then gave each other a big cuddle, they were crying in each others arms it was the happiest day of their lives since they were created” I love you” they said to each other crying and they slowly began to shrink. Their hands turned peach their skin turned peach they were turning human the orange monster had bright blue eyes and ginger hair and Frankenstein\'s monster had black hair and really dark brown eyes. The orange monster said “we are human no one will run away from us anymore “I am changing my name to Benny.” “I\'m changing my name to Frank “said Frankenstein\'s monster.” “Come on let\'s go to a restaurant. I am starving, I need a burger and maybe some fries “said Benny “Good idea” said Frank They are still living a very happy life; both aged 45 living in Essex maybe you have visited one of their restaurants? They like taking long holidays anywhere and everywhere so you may have already seen them they are inseparable.\n', 'Sammy goes on an adventurous journey through a forest, only to stumble upon a mad scientist and his giant monster creation.', 'onster-rush-outh-ales', 1, 0, 4.5, 0, 10, '5', 0, '9-12', 0, 1, 0, '/uploads/sammy-benny-reunion-swiss-alps.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1485, 'child', 'My Bog Poeming by Cory, aged 3, from Cae Garw, Wales', '## **Author**\n\n**Name:** Cory\n\n**Age:** 3\n\n**Location:** Cae Garw, Wales\n\n## Summary\n\nJoin the exciting adventures of a lively 9-year-old dog who loves to sleep, play, bark, howl at night, and fetch toys, in this whimsical children\'s storybook illustration.\n\n## The Story\n\nMy dog likes to sleep and play and bark and he his very playful and 9 years old he howls in the night cos he is scared and likes to follow my mum around the house and when he wants to play he fached is his toys.\n', 'Join the exciting adventures of a lively 9-year-old dog who loves to sleep, play, bark, howl at night, and fetch toys, in this whimsical children\'s storybook illustration.', 'y-og-oeming-ales', 1, 0, 4.5, 0, 10, '1', 0, '3-5', 0, 1, 0, '/uploads/playful-dog-adventures-children-storybook-illustration.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1486, 'child', 'My Cat by Ria, aged 8, from Hayes, Hillingdon', '## Author\n\n**Name:** Ria\n\n**Age:** 8\n\n**Location:** Hayes, Hillingdon\n\n## Summary\n\nA delightful tale about a fluffy and playful cat that brings joy and laughter to its owner\'s life.\n\n## Story\n\nMy cat is very fluffy, And she can play the flute, She\'s also very puffy\' But very very cute. My cat is also pretty, But she\'s also kind of weird, She goes around the city, Please don\'t be feared. My cat is a kitten , She\'s also very small, She likes to where her mittens, She can never grow tall.\n', 'A delightful tale about a fluffy and playful cat that brings joy and laughter to its owner\'s life.', 'y-at-illingdon', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/playful-cat-city-storybook-illustration.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1487, 'child', 'My Favourite Book Character by Rebekah, aged 9, from Lurgan Co. Armagh, Northern Ireland', '## **Author**\n\n**Name:** Rebekah\n\n**Age:** 9\n\n**Location:** Lurgan Co. Armagh, Northern Ireland\n\n## Summary\n\nJoin Matilda, the brilliant and imaginative bookworm, as she embarks on exciting adventures, unravels hidden treasures, and discovers the magic within the pages of her beloved storybooks.\n\n## The Story\n\nMy favourite book character is Matilda. Because she is full of great ideas and adventures. I think Matilda is a brilliant book character because I love the way she loves to read books. Another reason why I love Matilda is that I absouloutly love Roald Dahl books. I think he is a really interesting and funny author. I love everyone of his books. I love them that much I\'ve nearly read all of them. I love to read.\n', 'Join Matilda, the brilliant and imaginative bookworm, as she embarks on exciting adventures, unravels hidden treasures, and discovers the magic within the pages of her beloved storybooks.', 'y-avourite-ook-haracter-rmagh-orthern-reland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/matilda-adventure-treasure-storybook-illustration.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1488, 'child', 'My Secret Wish List by Leah, aged 11, from Rhondda, South Wales', '## **Author**\n\n**Name:** Leah\n\n**Age:** 11\n\n**Location:** Rhondda, South Wales\n\n## Summary\n\nJoin Leagh on an exciting adventure as their Christmas wishes magically come true, bringing an iPad, a dog, their own bedroom, a fridge, and a computer in this fun-filled children\'s story.\n\n## The Story\n\nWish 1: I want to have an iPad for Christmas.\n\nnn\n\nWish 2: I want a dog for Christmas.\n\nnn\n\nWish 3: I want my own bedroom.\n\nnn\n\nWish 4: I want my own fridge.\n\nnn\n\nWish 5: I want a computer.\n', 'Join Leagh on an exciting adventure as their Christmas wishes magically come true, bringing an iPad, a dog, their own bedroom, a fridge, and a computer in this fun-filled children\'s story.', 'y-ecret-ish-ist-outh-ales', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/leah-christmas-wishes-children-storybook-illustration.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1489, 'child', 'Nice day out with mummy x by Ashton, aged 6, from Jarrow', '## **Author**\n\n**Name:** Ashton\n\n**Age:** 6\n\n**Location:** Jarrow\n\n## Summary\n\nJoin Ashton on her exciting day as she enjoys breakfast at McDonald\'s, takes the metro to Hebburn library, and carries her trusty Hello Kitty umbrella to stay dry.\n\n## The Story\n\nWe went to mcdonalds for our breakfast then we took the metro to Hebburn library. I have got my hello kitty umberella with me in case it rains today. I am going to get some sweets after the library because I have been a good girl all day and all through the holidays for my mummy. have a nice day . x\n', 'Join Ashton on her exciting day as she enjoys breakfast at McDonald\'s, takes the metro to Hebburn library, and carries her trusty Hello Kitty umbrella to stay dry.', 'ice-day-out-with-mummy-x-by-shton-aged-6-from-arrow', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/ashtons-adventure-hello-kitty-umbrella-hebburn-library.png', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1490, 'child', 'Omagh Library of the Future by Danielle, aged 11, from Omagh', '## Author\n\n**Name:** Danielle\n\n**Age:** 11\n\n**Location:** Omagh, Northern Ireland\n\n## Summary\n\nExplore the magical world of Omagh Library, where books come to life and the librarians of the future are robots with a sense of humor.\n\n## Story\n\nMy local library is called: **Omagh Library**\n\nWhat\'s your local library like? **It\'s comfortable, has lots of good books in it.It has computers and things that children can play or do homework on.**\n\nWhat will it be like in the year 2150? **It will have T.Vs everywhere,a chocolate fountain in it and it will be very big inside and small outside. There will be loads of books and when you open a book you can watch the story.**\n\nWhat will the librarians be like in the year 2150? **The librarians will be robots and will be telling jokes instead of giving out information.**\n\nWhat do you like about using the library now? **I can get books out for free and I can use the computer.**\n', 'Explore the magical world of Omagh Library, where books come to life and the librarians of the future are robots with a sense of humor.', 'magh-ibrary-of-the-uture', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, 'https://api.storiesfromtheweb.org/images/default-cover.svg', '2025-05-09 17:07:46', '2025-05-09 17:07:46'),
(1491, 'child', 'All About Me by Abbey-lei, aged 10, from Killyclogher, Northern Ireland', '## Author\n\n**Name:** Abbey-lei\n\n**Age:** 10\n\n**Location:** Killyclogher, Northern Ireland\n\n## Summary\n\noin Abbey-lei, a lively 10-year-old from Killyclogher, as she explores a whimsical pink world filled with her favourite things, including vibrant colours, adorable elephants, delicious chicken wraps, and catchy tunes, creating a fun and exciting journey for children\'s imaginations.\n\n## Story\n\nFavourite Colour: **Pink**\n\nFavourite Animal: **Elephant**\n\nFavourite Food: **Chicken wraps**\n\nFavourite Music: **Nicki Minaj**\n\nFavourite TV Programme: **Victorious**\n\nThings I Like: **Clothes, me, Justin Bieber**\n\nThings I Don\'t Like: **Dry things**\n\nWould Love To Meet: **Justin Bieber**\n', 'oin Abbey-lei, a lively 10-year-old from Killyclogher, as she explores a whimsical pink world filled with her favourite things, including vibrant colours, adorable elephants, delicious chicken wraps, and catchy tunes, creating a fun and exciting journey for children\'s imaginations.', 'll-bout-e-orthern-reland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/girl-likes-pink-children\'s-storybook.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1492, 'child', 'The Beast Within by Chris, aged 11, from Perth and Kinross, Scotland', '## Author\n\n**Name:** Chris\n\n**Age:** 11\n\n**Location:** Perth and Kinross, Scotland\n\n## Summary\n\nFollow the thrilling tale of a young hero who must confront the beast within and find the strength to overcome his darkest fears.\n\n## Story\n\nIt was charging at me with claws like knives and a breath like a sewer rat. It suddenly came to a halt about 10 feet away from me and roared. I ran up to it and tried to hit it with my broom but it moved to the side and swiped at me with its sharp claws. I fell to the ground and looked at my bleeding scar. I limped over the road as fast as I could. I felt a thud behind me. As I turned my head I watched two shadows run away from the beast. Still limping I looked at the fallen beast, he snorted. I looked at him for a minute and suddenlly ripped one of his claws off and impaled him with it...\n', 'Follow the thrilling tale of a young hero who must confront the beast within and find the strength to overcome his darkest fears.', 'he-east-ithin-cotland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/boy-meets-beast-children\'s-storybook.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1493, 'child', 'The Day Before Yesterday by Meranie, aged 9, from Luton, England', '## **Author**\n\n**Name:** Meranie\n\n**Age:** 9\n\n**Location:** Luton, England\n\n## Summary\n\nJoin the bean-man, the plate, and the fork as they embark on a whimsical quest to find the knife and the spoon, only to encounter a mischievous and powerful bean-man named Sheep who can turn back time in the land of ridiculous times.\n\n## The Story\n\nOne day in the land of ridiculous times there lived a bean-man, a plate and a fork. They were going on a quest to find the knife and the spoon. Suddenly a flash of light stung their eyes and they fell on the ground, when they woke they found a big evil bean-man called Sheep. He turned the world into the day before yesterday. That\'s how it all began!!\n', 'Join the bean-man, the plate, and the fork as they embark on a whimsical quest to find the knife and the spoon, only to encounter a mischievous and powerful bean-man named Sheep who can turn back time in the land of ridiculous times.', 'he-ay-efore-esterday-ngland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/children-storybook-illustration-adventures-bean-man-plate-fork-sheep.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1494, 'child', 'The Magic Treehouse  Kerry, aged 9, from Northern Ireland', '## **Author**\n\n**Name:** Kerry\n\n**Age:** 9\n\n**Location:** Northern Ireland\n\n## Summary\n\nJoin Jake and Katy on their exciting adventure as their new tree house takes them on a magical underwater journey filled with friendly fish, enchanting coral reefs, and a golden mermaid.\n\n## The Story\n\nJake and Katy looked up at their new tree house they couldn\'t wait to play in it with all their friends but they wanted to be first in it. Katy dashed up the ladder followed by Jake when they reached the top an amazing sight met their eyes a glowing gold key sat beside the doll. House they opened the door but it was too late they were in a whole different place they were under water. A gold mermaid swam across their face they were breathing, the tree house was magic!!!\n', 'Join Jake and Katy on their exciting adventure as their new tree house takes them on a magical underwater journey filled with friendly fish, enchanting coral reefs, and a golden mermaid.', 'he-agic-reehouse-erry-aged-9-from-orthern-reland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/magical-underwater-tree-house-adventure-childrens-story-book-illustration.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1495, 'child', 'The Optition by Alice, aged 7, from Scotland', '## **Author**\n\n**Author: Allice**\n\n**Age:** 7\n\n**Location:** Scotland\n\n## Summary\n\nAlice visits an optician with her mom and gets a surprise during the eye test. She ends up making her own pair of glasses and discovers the joy of creativity and imagination.\n\n## The Story\n\nOne day I went with my mum to get my eyes test I really wanted a pair of glasses when the other girl came out it was my turn. my mum said be brave then a lady put the lights off. the lady said what does this say. i got them all correct so she said that I do not need glasses. I was a bit upset. so my mum thought and said how about we make a pair. I said that is a very good idea so I got and made a pair of glasses I invited my friend over and we made a pair of glasses. I like my new pair of glasses they are very nice THE END\n', 'Alice visits an optician with her mom and gets a surprise during the eye test.', 'he-ptition', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/illustration-alice-optician.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1496, 'child', 'The Reader and the Old Library by Lisa, aged 10, from Inverclyde, Scotland', '## Author\n\n**Name:** Lisa\n\n**Age:** 10\n\n**Location:** Inverclyde, Scotland\n\n## Summary\n\nJoin Rani on an exciting journey through books as she discovers a hidden treasure, encounters mysterious posters, and unlocks the magic of imagination in a whimsical and enchanting story.\n\n## Story\n\nRani loved reading. In her room, walking down the street or even in her sleep. One day Rani was trying to find her favourite book, when she found an old looking book called Journey to the Lost Land. It didn\'t look long to read this book and make it her favourite. She was walking down the street one day and reading a book when she found herself walking into an old, deserted building. Rani looked around and saw it was full of old books but the one that stood out to her was a bright coloured poster. Well, a piece of a poster with a piece of paper attached saying : Find More Pieces Around This Library. There is three bits in total. Rani found one on top of an old bookshelf. She couldn\'t find the last one so she decided to give up. When she was walking out she found the last piece on a poster for the Journey to the Lost Land! Rani stuck the pieces together and it was a poster for the new book from D. K. Bookworm who wrote The Journey to the Lost Land. It was called Living in the Lost Land. So she bought and read it straight away!\n', 'Join Rani on an exciting journey through books as she discovers a hidden treasure, encounters mysterious posters, and unlocks the magic of imagination in a whimsical and enchanting story.', 'he-eader-and-the-ld-ibrary-cotland', 1, 0, 4.5, 0, 10, '2', 0, '9-12', 0, 1, 0, 'https://api.storiesfromtheweb.org/images/default-cover.svg', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1497, 'child', 'The Red Robot and the Rabbit by Ella, Willow, Carlotta & Jodie , aged 10-11, from Poole, England', '## **Author**\n\n**Names:** Ella, Willow, Carlotta, Jodie\n\n**Age:** 10-11\n\n**Location:** Poole, England\n\n## Summary\n\nJoin the red robot and the rabbit on a whimsical adventure in a leafy lane where kindness and friendship bloom, and a mischievous rabbit learns the joy of being good for a bowl of rice.\n\n## The Story\n\nThe red robot and the rabbit, met down a leafy lane... where the rabbit was being a pain the robot said, \"be nice and if you\'re good I\'ll give you some rice\".\n', 'Join the red robot and the rabbit on a whimsical adventure in a leafy lane where kindness and friendship bloom, and a mischievous rabbit learns the joy of being good for a bowl of rice.', 'he-ed-obot-and-the-abbit-illow-arlotta-odie-aged-10-11-from-oole-ngland', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/childrens-storybook-illustration-red-robot-rabbit-leafy-lane.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1498, 'child', 'Tim Can by Melissa, aged 10, from Paisley', '## **Author**\n\n**Name:** Melissa\n\n**Age:** 10\n\n**Location:** Paisley\n\n## Summary\n\nJoin Tim the can on his exciting adventure in the grocery store, where he meets new friends and curious children in this fun-filled children\'s storybook tale.\n\n## The Story\n\nThere was a can called Tim and one day Tim said I can\'t wait until somebody buys me. And then a women walked up to Tim and bought him. After she used him and then put him in the bin. Tim said is that all said Tim and then a box said no of course not next week you will be something different. The end\n', 'Join Tim the can on his exciting adventure in the grocery store, where he meets new friends and curious children in this fun-filled children\'s storybook tale.', 'im-an', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/tim-the-can-children-storybook-illustration.png', '2025-05-09 17:07:47', '2025-05-09 17:07:47'),
(1499, 'child', 'Time Stood Still by Joshua , aged 8, from Spring Hill, Birmingham, England', '## **Author**\n\n**Name:** Joshua\n\n**Age:** 8\n\n**Location:** Spring Hill, Birmingham, England\n\n## Summary\n\nJoin Joshua on a thrilling journey as a shiny pebble brings his garden to life in this enchanting tale of magic and mischief!\n\n## The Story\n\nOne day I was walking in my garden when I saw a small, shiny pebble on the floor. . . but it wasn\'t a pebble, it was a penny. I went inside to wash it. Then I rubbed it with a paper towel. Suddenly everything stopped. My sister stopped while playing on the Wii. My mom stopped while cleaning the display. My dad stopped while hammering a nail. The only thing that didn\'t stop was me ... This could change my life. The end.\n', 'Join Joshua on a thrilling journey as a shiny pebble brings his garden to life in this enchanting tale of magic and mischief!', 'ime-tood-till-irmingham-ngland', 1, 0, 4.5, 0, 10, '1', 0, '6-8', 0, 1, 0, '/uploads/magical-pebble-adventure-childrens-storybook-illustration.png', '2025-05-09 17:07:48', '2025-05-09 17:07:48'),
(1500, 'child', 'Vampire by Jane, aged 9, from East Dunbartonshire', '## Author\n\n**Name:** Jane\n\n**Age:** 9\n\n**Location:** East Dunbartonshire\n\n## Summary\n\nA young boy finds himself facing a fearsome vampire, read as the story unfolds.\n\n## Story\n\nBlood dribbling down her chin, Her heart not beating, but full of sin. Children trembling in their beds with fright, Wondering who will be her victim tonight. Her cape drags along the street, Footsteps echoing on the concrete. Head tilted, she sniffs the air, Sensing a child is not far from there. She bursts into the room, her fangs bared, The unlucky little boy is awfully scared. She races towards him, and lunges for his neck... But nobody knows what happens next. Did she get him, is he dead? Or maybe he got away instead? Although one thing is true, one thing\'s for sure, That vampires live forever more.\n', 'A young boy finds himself facing a fearsome vampire, read as the story unfolds.', 'ampire', 1, 0, 4.5, 0, 10, '1', 0, '9-12', 0, 1, 0, '/uploads/boy-meets-vampire-storybook-illustration.png', '2025-05-09 17:07:48', '2025-05-09 17:07:48');

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
(1663, 2190),
(1664, 2191),
(1665, 2192),
(1666, 2193),
(1667, 2194),
(1668, 2195),
(1669, 2196),
(1670, 2197),
(1671, 2198),
(1672, 2199),
(1673, 2200),
(1674, 2201),
(1675, 2202),
(1676, 2203),
(1677, 2204),
(1678, 2205),
(1679, 2206),
(1680, 2207),
(1681, 2208),
(1682, 2209),
(1684, 2210),
(1685, 2211),
(1686, 2212),
(1687, 2213),
(1688, 2214),
(1689, 2215);

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
(2, 1),
(2, 4),
(1664, 6),
(1665, 7),
(1667, 8),
(1676, 8),
(1677, 8),
(1679, 8),
(1683, 8),
(1685, 8),
(1688, 8),
(1671, 11),
(1673, 11),
(1686, 11),
(1671, 12),
(1680, 16),
(1684, 16),
(1685, 16),
(1663, 22),
(1675, 22),
(1682, 22),
(1689, 22),
(1663, 23),
(1675, 23),
(1682, 23),
(1689, 23),
(1664, 24),
(1664, 25),
(1669, 25),
(1671, 25),
(1681, 25),
(1665, 26),
(1677, 26),
(1666, 27),
(1667, 27),
(1668, 27),
(1670, 27),
(1671, 27),
(1672, 27),
(1674, 27),
(1676, 27),
(1677, 27),
(1683, 27),
(1686, 27),
(1687, 27),
(1668, 28),
(1670, 29),
(1673, 29),
(1670, 30),
(1673, 30),
(1678, 30),
(1679, 31),
(1686, 31),
(1688, 32),
(2, 45);

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feature` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `is_contacted` tinyint(1) DEFAULT '0',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscribers`
--

INSERT INTO `subscribers` (`id`, `email`, `name`, `feature`, `message`, `is_contacted`, `admin_notes`, `created_at`, `updated_at`) VALUES
(10, 'david.bryan@opace.co.uk', NULL, 'premium stories', NULL, 0, NULL, '2025-05-04 08:00:47', '2025-05-04 08:00:47'),
(11, 'info@opace.co.uk', NULL, 'newsletter', NULL, 0, NULL, '2025-05-05 13:37:04', '2025-05-05 13:37:04');

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
(4, 'Educational', 'educational', '2025-04-26 08:17:50', '2025-04-26 08:17:50'),
(6, 'animals', 'animals', '2025-04-28 14:38:43', '2025-04-28 14:38:43'),
(7, 'family', 'family', '2025-04-28 14:38:43', '2025-04-28 14:38:43'),
(8, 'magic', 'magic', '2025-04-28 14:38:43', '2025-04-28 14:38:43'),
(10, 'monsters', 'monsters', '2025-04-28 14:38:43', '2025-04-28 14:38:43'),
(11, 'friendship', 'friendship', '2025-04-28 14:38:43', '2025-04-28 14:38:43'),
(12, 'space', 'space', '2025-04-28 14:38:43', '2025-04-28 14:38:43'),
(13, 'robots', 'robots', '2025-04-28 14:38:44', '2025-04-28 14:38:44'),
(14, 'nature', 'nature', '2025-04-30 10:50:42', '2025-04-30 10:50:42'),
(15, 'science fiction', 'science-fiction', '2025-04-30 10:50:45', '2025-04-30 10:50:45'),
(16, 'imagination', 'imagination', '2025-04-30 11:05:11', '2025-04-30 11:05:11'),
(17, 'mystery', 'mystery', '2025-04-30 11:05:12', '2025-04-30 11:05:12'),
(18, 'fairy tale', 'fairy-tale', '2025-04-30 11:05:12', '2025-04-30 11:05:12'),
(19, 'school', 'school', '2025-04-30 11:05:12', '2025-04-30 11:05:12'),
(20, 'magical creatures', 'magical-creatures', '2025-04-30 11:05:14', '2025-04-30 11:05:14'),
(21, 'test333', 'test333', '2025-05-04 08:10:49', '2025-05-04 08:10:49'),
(22, 'children story', 'children-story', '2025-05-09 10:19:34', '2025-05-09 10:19:34'),
(23, 'kids literature', 'kids-literature', '2025-05-09 10:19:34', '2025-05-09 10:19:34'),
(24, 'autumn', 'autumn', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(25, 'fall', 'fall', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(26, 'christmas', 'christmas', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(27, 'adventure', 'adventure', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(28, 'dinosaurs', 'dinosaurs', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(29, 'monster', 'monster', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(30, 'holiday', 'holiday', '2025-05-09 10:19:35', '2025-05-09 10:19:35'),
(31, 'robot', 'robot', '2025-05-09 10:19:36', '2025-05-09 10:19:36'),
(32, 'spring', 'spring', '2025-05-09 10:19:37', '2025-05-09 10:19:37'),
(45, 'Fantasy', 'antasy', '2025-05-10 09:19:05', '2025-05-10 10:49:30'),
(49, 'Historical', 'istorical', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(50, 'Realistic Fiction', 'ealistic-iction', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(51, 'Middle Grade', 'iddle-rade', '2025-05-10 09:19:05', '2025-05-10 09:19:05'),
(65, 'Comedy', 'omedy', '2025-05-10 09:19:07', '2025-05-10 09:19:07'),
(97, 'Children\'s Fiction', 'hildren-s-iction-1', '2025-05-10 09:21:44', '2025-05-10 09:21:44'),
(101, 'Unknown', 'nknown', '2025-05-10 09:21:45', '2025-05-10 09:21:45'),
(118, 'Young Adult', 'oung-dult', '2025-05-10 11:09:27', '2025-05-10 11:09:27'),
(119, 'Coming Of Age', 'oming-f-ge', '2025-05-10 11:09:28', '2025-05-10 11:09:28'),
(120, '7-10', '7-10', '2025-05-10 12:30:09', '2025-05-10 12:30:09'),
(121, '9-12', '9-12', '2025-05-10 12:30:09', '2025-05-10 12:30:09'),
(122, '8-12', '8-12', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(123, '9+', '9', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(124, '10+', '10', '2025-05-10 12:30:10', '2025-05-10 12:30:10'),
(125, '12 and up', '12-and-up', '2025-05-10 12:30:12', '2025-05-10 12:30:12'),
(126, ' 12+', '12', '2025-05-10 13:52:56', '2025-05-10 13:52:56');

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

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_ai_generation_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_ai_generation_stats` (
`provider_name` varchar(50)
,`type` enum('image','text','audio','video')
,`generation_date` date
,`total_generations` bigint
,`total_cost` decimal(32,6)
,`failed_generations` bigint
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_generations`
--
ALTER TABLE `ai_generations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_type_status` (`type`,`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `ai_models_cache`
--
ALTER TABLE `ai_models_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_prompt_templates`
--
ALTER TABLE `ai_prompt_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name_type` (`name`,`content_type`);

--
-- Indexes for table `ai_providers`
--
ALTER TABLE `ai_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `ai_rate_limit`
--
ALTER TABLE `ai_rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_date` (`ip_address`,`created_at`);

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
-- Indexes for table `ai_usage`
--
ALTER TABLE `ai_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_type_date` (`type`,`created_at`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_author_type` (`author_type`),
  ADD KEY `idx_location` (`location`);

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
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`directory_item_id`);

--
-- Indexes for table `book_authors`
--
ALTER TABLE `book_authors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `directory_item_id` (`directory_item_id`,`author_id`,`role`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `directory_item_tags`
--
ALTER TABLE `directory_item_tags`
  ADD PRIMARY KEY (`directory_item_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `item_tags`
--
ALTER TABLE `item_tags`
  ADD PRIMARY KEY (`item_id`,`tag_id`,`item_type`);

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
-- Indexes for table `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for table `ai_generations`
--
ALTER TABLE `ai_generations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_models_cache`
--
ALTER TABLE `ai_models_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ai_prompt_templates`
--
ALTER TABLE `ai_prompt_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ai_providers`
--
ALTER TABLE `ai_providers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ai_rate_limit`
--
ALTER TABLE `ai_rate_limit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `ai_usage`
--
ALTER TABLE `ai_usage`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2216;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `book_authors`
--
ALTER TABLE `book_authors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2684;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2041;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1624;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1690;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Structure for view `author_stats`
--
DROP TABLE IF EXISTS `author_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `author_stats`  AS SELECT `a`.`author_type` AS `author_type`, count(0) AS `author_count`, (select count(0) from (`stories` `s` join `story_authors` `sa` on((`s`.`id` = `sa`.`story_id`))) where (`sa`.`author_id` = `a`.`id`)) AS `story_count` FROM `authors` AS `a` GROUP BY `a`.`author_type` ;

-- --------------------------------------------------------

--
-- Structure for view `v_ai_generation_stats`
--
DROP TABLE IF EXISTS `v_ai_generation_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`stories_user`@`localhost` SQL SECURITY DEFINER VIEW `v_ai_generation_stats`  AS SELECT `p`.`name` AS `provider_name`, `g`.`type` AS `type`, cast(`g`.`created_at` as date) AS `generation_date`, count(0) AS `total_generations`, coalesce(sum(`u`.`cost`),0) AS `total_cost`, count((case when (`g`.`status` = 'failed') then 1 end)) AS `failed_generations` FROM ((`ai_generations` `g` join `ai_providers` `p` on((`g`.`provider_id` = `p`.`id`))) left join `ai_usage` `u` on(((`u`.`provider_id` = `p`.`id`) and (cast(`u`.`created_at` as date) = cast(`g`.`created_at` as date))))) GROUP BY `p`.`name`, `g`.`type`, cast(`g`.`created_at` as date) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_generations`
--
ALTER TABLE `ai_generations`
  ADD CONSTRAINT `ai_generations_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `ai_providers` (`id`);

--
-- Constraints for table `ai_usage`
--
ALTER TABLE `ai_usage`
  ADD CONSTRAINT `ai_usage_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `ai_providers` (`id`);

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
-- Constraints for table `directory_item_tags`
--
ALTER TABLE `directory_item_tags`
  ADD CONSTRAINT `directory_item_tags_ibfk_1` FOREIGN KEY (`directory_item_id`) REFERENCES `directory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `directory_item_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

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
