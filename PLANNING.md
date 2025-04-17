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