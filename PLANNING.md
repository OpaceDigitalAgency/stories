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
- PHP API returning HTTP 500 errors with blank bodies due to:
  - Autoloader implementation issues:
    - Not properly requiring class files even with correct case-matched paths
    - Case-insensitive fallback logic only matching directories, not final PHP files
    - Test scripts not using the same autoloader bootstrap
  - Error reporting turned off in development mode (fixed)
  - Error logging pointing to non-existent path (fixed)