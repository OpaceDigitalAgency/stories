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
- Frontend-Backend Integration:
  * Frontend (Netlify) not displaying content from backend API
  * API URL configuration needs updating
  * CORS (Cross-Origin Resource Sharing) configuration required
  * Error handling improvements needed
  * Authentication token management to be enhanced
  * See API_CONNECTIVITY_FIX.md for detailed implementation plan

## Case Sensitivity Enforcement Plan

### 1. Initial Cleanup (All Environments)
1. Run permanent_case_fix.php which will:
   - Remove duplicate directories (e.g., both "core" and "Core")
   - Move files from wrong-case to correct-case directories
   - Delete all backup/temporary files (*.bak, *.orig)
   - Update namespace references
   - Install strict PSR-4 autoloader

### 2. Prevention Measures
1. Git Configuration:
   ```bash
   # Add to .git/config
   [core]
       ignorecase = false
   ```

2. Deployment Process:
   - Add check_case_sensitivity.php to deployment pipeline
   - Block deployments if case issues are found
   - Prevent backup file creation on production

3. Development Guidelines:
   - Use correct capitalization:
     * Core/ (not core/)
     * Middleware/ (not middleware/)
     * Endpoints/ (not endpoints/)
     * Utils/ (not utils/)
   - No backup files in version control
   - Run case sensitivity check before commits

### 4. Monitoring
1. Regular Checks:
   - Weekly scan for case mismatches
   - Alert if backup files are found
   - Verify autoloader strict mode

2. Documentation:
   - Update coding standards
   - Document correct directory structure
   - Add case sensitivity section to onboarding

See permanent_case_fix.php for implementation details.

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
## Upcoming
- JWT refresh overhaul: Implementing robust token refresh mechanism to fix recurring 401 failures in Admin CRUD operations. This will ensure expired JWTs auto-refresh, maintain consistency between cookie and PHP session storage, and allow clients to retry the original request after refresh.