# Stories from the Web - Project Overview

This repository contains the code for the Stories from the Web platform.

## Project Structure
- Frontend: Astro.js-based website
- Backend: PHP-based API and admin panel

## Memory Files
- [PLANNING.md](PLANNING.md) - Project architecture, goals, and constraints
- [TASK.md](TASK.md) - Active tasks and backlog
- [PROGRESS.md](PROGRESS.md) - Log of completed work

## Current Focus
The login authentication issue has been fixed. The admin panel now has:
1. Proper authentication with correctly hashed passwords
2. Secure login system without backdoor access
3. Protected admin/includes/ directory
4. Detailed documentation in [LOGIN_FIX.md](stories-backend/LOGIN_FIX.md)

All admin interface UX/UI and data display issues have been fixed. The admin panel now features:
1. Fixed Media page with proper file upload functionality
2. Enhanced dashboard showing all recent content types with intuitive navigation
3. Proper data display in all content type admin pages
4. Improved overall design and usability with consistent UI components and better feedback

Recent improvements include:
1. Complete database schema documentation in PLANNING.md
2. Dashboard data display aligned with the database structure
3. Fixed Stories API endpoint for viewing and editing individual stories
4. Added missing fields to all content type displays on the dashboard
5. Fixed CRUD operations (add, delete, edit save) on all admin pages
   - Enhanced client-side AJAX handling with improved error messages
   - Fixed authentication and token handling
   - Added comprehensive logging for easier troubleshooting

6. Implemented robust authentication system for the admin panel
   - Added consistent JWT token storage in both session and cookie
   - Created automatic token refresh mechanism for expired tokens
   - Enhanced session management with token consistency checks
   - Added proper error handling for authentication failures
   - Created dedicated API endpoint for token refresh

## Deployment Process
- **Frontend**: Automatically deployed to Netlify (https://storiesfromtheweb.netlify.app/) when changes are pushed to GitHub
- **Backend**: Deployed using cPanel's Git Version Control:
  1. Push changes to GitHub repository
  2. Log in to cPanel
  3. Go to "Git Version Control"
  4. Find the "stories" repository
  5. Click "Manage"
  6. Click "Update from Remote" to pull the latest changes
  7. Click "Deploy HEAD Commit" to deploy the changes

See [DEPLOYMENT.md](DEPLOYMENT.md) for more detailed deployment instructions.
