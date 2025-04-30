# Stories From The Web

A platform for sharing and discovering children's stories from around the web.

## Overview

Stories From The Web is a web application that allows users to browse, read, and publish children's stories. The platform categorizes stories into different sections:

- **Most Loved Stories**: Stories with the highest ratings
- **Latest Self-Published**: Recently published stories by community members
- **AI-Enhanced Picks**: Stories that have been enhanced using AI tools
- **Sponsored Stories**: Stories that are sponsored by partners

## Recent Improvements

We've addressed several issues and made improvements to the platform:

1. **Fixed Section Filtering**: Each section now correctly displays stories that match its criteria
   - AI-Enhanced section only shows stories with the "AI Enhanced" flag
   - Self-Published section only shows stories with the "Self Published" flag
   - Sponsored section only shows stories with the "Sponsored" flag

2. **Fixed Admin Form Saving**: The admin form now properly saves all fields
   - Boolean fields like "Is Self Published" and "AI Enhanced" now save correctly
   - Author selection now saves properly
   - All form fields are properly processed and stored in the database

3. **Fixed Author Display Issues**: Authors now display correctly in the admin interface
   - Authors are properly shown in the stories list
   - The correct author is selected when editing a story
   - Added verification to ensure selected authors exist

4. **Improved "Much Loved" Section**: This section now displays stories sorted by their average rating in descending order

5. **Enhanced Admin Interface**: The admin interface has been completely redesigned
   - Modern, professional design with responsive layout
   - Consistent header and navigation across all pages
   - View functionality for all content types
   - Consistent actions (view, edit, delete) for all content types
   - CSS-only expand/collapse functionality for dashboard content sections

## Technical Architecture

- **Frontend**: Astro.js static site with TypeScript and Tailwind CSS, deployed on Netlify CDN
- **Backend**: Custom PHP RESTful API with MySQL database, hosted on cPanel shared hosting
- **Admin Interface**: JavaScript-free PHP admin panel for content management
  - Active admin pages are in `stories-backend/admin/content/` directory
  - Unused template-based implementation is archived in `stories-backend/admin/_archive/unused_crud_implementation/`
  - Features Bootstrap modals for confirmation dialogs, age-group fields, and tag extraction

## Current Cleanup Initiative

We're currently undertaking a comprehensive cleanup and documentation initiative to:

1. **Clean up redundant PHP scripts** - Removing obsolete scripts while safely archiving them for reference
2. **Consolidate documentation** - Organizing and updating documentation to make it easier to understand the system
3. **Standardize API responses** - Ensuring consistent response formats across all endpoints
4. **Improve admin interface reliability** - Enhancing the admin interface to be more reliable and user-friendly

## Documentation

For more detailed information, please refer to:

### Core Documentation

- [Documentation Index](documentation-index.md) - Central index of all documentation
- [Revised Cleanup Plan](revised-cleanup-plan.md) - Detailed plan for cleaning up scripts and documentation
- [System Architecture](system-architecture.md) - Comprehensive system architecture documentation
- [System Architecture with Improvements](system-architecture-with-improvements.md) - Visual representation of areas needing improvement
- [Database Schema](database-schema.md) - Complete database schema documentation
- [API Documentation](api-documentation.md) - Comprehensive API endpoint documentation
- [PHP Scripts Cleanup Guide](php-scripts-cleanup-guide.md) - Guide for cleaning up PHP scripts
- [Known Issues and Fixes](KNOWN_ISSUES_AND_FIXES.md) - Documentation of known issues and their solutions

### Historical Documentation

- [Planning Document](PLANNING.md) - Contains goals, architecture decisions, and solutions to known issues
- [Progress Log](PROGRESS.md) - Tracks changes and progress over time

### Deployment Documentation

- [Consolidated Deployment Guide](consolidated-deployment-guide.md) - Comprehensive guide for deploying the platform

## Getting Started

### Development Environment Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/OpaceDigitalAgency/stories.git
   cd stories
   ```

2. Install frontend dependencies:
   ```bash
   npm install
   ```

3. Start the development server:
   ```bash
   npm run dev
   ```

4. Set up the backend:
   - Import the database from `stories-backend/database/stories_db_26.04.25_1337.sql`
   - Configure `stories-backend/api/v1/config/config.php` with your database credentials

### Production Environments

- **Frontend**: https://storiesfromtheweb.netlify.app/
- **Backend API**: https://api.storiesfromtheweb.org/api/v1/
- **Admin Interface**: https://api.storiesfromtheweb.org/admin/

## Contributing

Please refer to the [Revised Cleanup Plan](revised-cleanup-plan.md) for guidelines on contributing to the cleanup initiative.
