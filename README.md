# Stories From The Web

A platform for sharing and discovering children's stories from around the web.

## Overview

Stories From The Web is a web application that allows users to browse, read, and publish children's stories. The platform categorizes stories into different sections:

- **Most Loved Stories**: Stories with the highest ratings
- **Latest Self-Published**: Recently published stories by community members
- **AI-Enhanced Picks**: Stories that have been enhanced using AI tools
- **Sponsored Stories**: Stories that are sponsored by partners

## Recent Fixes

We've addressed several issues with the platform:

1. **Fixed Section Filtering**: Each section now correctly displays stories that match its criteria
   - AI-Enhanced section only shows stories with the "AI Enhanced" flag
   - Self-Published section only shows stories with the "Self Published" flag
   - Sponsored section only shows stories with the "Sponsored" flag

2. **Fixed Admin Form Saving**: The admin form now properly saves all fields
   - Boolean fields like "Is Self Published" and "AI Enhanced" now save correctly
   - Author selection now saves properly
   - All form fields are properly processed and stored in the database

3. **Improved "Much Loved" Section**: This section now displays stories sorted by their average rating in descending order

## Technical Details

- **Frontend**: Built with Astro.js for server-side rendering
- **Backend**: PHP with MySQL database
- **API**: RESTful API for communication between frontend and backend

## Documentation

For more detailed information, please refer to:

- [Planning Document](PLANNING.md): Contains goals, architecture decisions, and solutions to known issues
- [Progress Log](PROGRESS.md): Tracks changes and progress over time
