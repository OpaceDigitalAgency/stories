# Project Planning

## Architecture
- Frontend: Astro.js static site
- Backend: PHP API and admin panel
- Database: MySQL

## Goals
- Create a platform for sharing and discovering stories
- Provide tools for authors to publish and manage their content
- Build an admin interface for content management

## Current Issues
- None at this time. All identified issues have been fixed.

## Previous Issues (Fixed)
- Login authentication issue:
  - Main login page (admin/login.php) always returning "Invalid credentials"
  - Root cause: Missing admin user or admin user with plaintext password instead of proper hash
  - Solution: Created scripts to insert/update admin user with proper bcrypt hash and secure the system
  - Documentation: Created LOGIN_FIX.md with detailed explanation
  - Additional fix: Updated Content Security Policy to allow external resources from CDNs

- Admin interface UX/UI and data issues:
  - Media page not working (HTTP ERROR 500) (fixed)
  - Main dashboard needs to show all recent content types and be more intuitive (fixed)
  - Data is missing in content type admin pages (e.g., title, author information) (fixed)
  - Overall design and usability needs improvement across all admin pages (fixed)

- PHP API returning HTTP 500 errors with blank bodies due to:
  - Autoloader implementation issues:
    - Not properly requiring class files even with correct case-matched paths
    - Case-insensitive fallback logic only matching directories, not final PHP files
    - Test scripts not using the same autoloader bootstrap
  - Error reporting turned off in development mode (fixed)
  - Error logging pointing to non-existent path (fixed)
## Database Schema

This section documents the database schema based on `stories-backend/database.sql`.

### Tables:

*   **users**: `id`, `name`, `email`, `password`, `role`, `active`, `created_at`, `updated_at`
*   **authors**: `id`, `name`, `slug`, `bio`, `featured`, `twitter`, `instagram`, `website`, `created_at`, `updated_at`
*   **stories**: `id`, `title`, `slug`, `excerpt`, `content`, `published_at`, `featured`, `average_rating`, `review_count`, `estimated_reading_time`, `is_sponsored`, `age_group`, `needs_moderation`, `is_self_published`, `is_ai_enhanced`, `created_at`, `updated_at`
*   **tags**: `id`, `name`, `slug`, `created_at`, `updated_at`
*   **story_authors**: `story_id`, `author_id` (Many-to-Many)
*   **story_tags**: `story_id`, `tag_id` (Many-to-Many)
*   **blog_posts**: `id`, `title`, `slug`, `excerpt`, `content`, `published_at`, `created_at`, `updated_at`
*   **blog_post_authors**: `blog_post_id`, `author_id` (Many-to-Many)
*   **directory_items**: `id`, `name`, `description`, `url`, `category`, `created_at`, `updated_at`
*   **games**: `id`, `title`, `description`, `url`, `category`, `created_at`, `updated_at`
*   **ai_tools**: `id`, `name`, `description`, `url`, `category`, `created_at`, `updated_at`
*   **media**: `id`, `entity_type`, `entity_id`, `type`, `url`, `width`, `height`, `alt_text`, `created_at`