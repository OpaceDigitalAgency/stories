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
  - Case-sensitive autoloading vs. lowercase folder names (endpoints/ vs. Endpoints/)
  - Error reporting turned off in development mode
  - Error logging pointing to non-existent path