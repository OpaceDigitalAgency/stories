# Stories from the Web - Project Overview

This repository contains the code for the Stories from the Web platform.

## Project Structure
- Frontend: Astro.js-based website
- Backend: PHP-based API and admin panel

## Comprehensive Documentation

A comprehensive cleanup and documentation initiative has been completed to standardize the codebase, fix inconsistencies, and provide detailed documentation. The following documentation is now available:

### Core Documentation
- [Project Cleanup Summary](project-cleanup-summary.md) - Overview of the cleanup initiative
- [Stories Cleanup Plan](stories-cleanup-plan.md) - Comprehensive plan for cleaning up and standardizing the codebase
- [System Architecture](system-architecture.md) - Detailed documentation of the system architecture
- [Database Schema](database-schema.md) - Complete documentation of the database schema
- [API Documentation](api-documentation.md) - Comprehensive documentation of all API endpoints
- [PHP Scripts Cleanup Guide](php-scripts-cleanup-guide.md) - Guide for cleaning up PHP scripts
- [Implementation Plan](implementation-plan.md) - Detailed implementation plan

### Additional Documentation
- [Documentation Index](docs/documentation-index.md) - Central index for all documentation
- [System Architecture with Diagrams](docs/system-architecture.md) - System architecture with mermaid diagrams
- [Comprehensive System Architecture](docs/comprehensive-system-architecture.md) - Complete and detailed system architecture documentation with explanations of all components
- [System Architecture (HTML)](docs/system-architecture.html) - Interactive HTML version with Mermaid.js diagrams
- [Consolidated Deployment Guide](docs/consolidated-deployment-guide.md) - Comprehensive deployment guide

### Historical Documentation
- [PLANNING.md](PLANNING.md) - Project architecture, goals, and constraints
- [PROGRESS.md](PROGRESS.md) - Log of completed work
- [API_CONNECTIVITY_FIX.md](API_CONNECTIVITY_FIX.md) - Guide for fixing API connectivity issues

## Current Status

The platform has undergone significant improvements:

### Admin Interface
1. Proper authentication with correctly hashed passwords
2. Secure login system without backdoor access
3. Protected admin/includes/ directory
4. Fixed Media page with proper file upload functionality
5. Enhanced dashboard showing all recent content types with intuitive navigation
6. Proper data display in all content type admin pages
7. Improved overall design and usability with consistent UI components and better feedback

### API Improvements
1. Standardized API response formats
2. Fixed Stories API endpoint for viewing and editing individual stories
3. Added missing fields to all content type displays
4. Fixed CRUD operations (add, delete, edit save) on all admin pages
5. Enhanced client-side AJAX handling with improved error messages

### Authentication System
1. Consistent JWT token storage in both session and cookie
2. Automatic token refresh mechanism for expired tokens
3. Enhanced session management with token consistency checks
4. Proper error handling for authentication failures
5. Dedicated API endpoint for token refresh

## Deployment Process

The deployment process has been standardized and documented in the [Consolidated Deployment Guide](docs/consolidated-deployment-guide.md).

### Frontend Deployment
- Automatically deployed to Netlify (https://storiesfromtheweb.netlify.app/) when changes are pushed to GitHub

### Backend Deployment
- Deployed using cPanel's Git Version Control:
  1. Push changes to GitHub repository
  2. Log in to cPanel
  3. Go to "Git Version Control"
  4. Find the "stories" repository
  5. Click "Manage"
  6. Click "Update from Remote" to pull the latest changes
  7. Click "Deploy HEAD Commit" to deploy the changes

## Next Steps

The [Implementation Plan](implementation-plan.md) outlines the steps to implement the cleanup and standardization plan. The plan is divided into six phases:

1. **Analysis and Preparation**: Analyze the current state and prepare for cleanup
2. **Backend Cleanup**: Clean up the backend codebase
3. **API Standardization**: Standardize all API endpoints
4. **Admin Interface Improvements**: Improve the admin interface
5. **Documentation Updates**: Create comprehensive documentation
6. **Testing and Verification**: Thoroughly test all aspects of the platform

Follow the implementation plan to complete the cleanup and standardization process.
