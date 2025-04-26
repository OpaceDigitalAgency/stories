# Admin Authentication Fix

This document explains the fixes made to resolve authentication issues in the admin interface.

## Issues Fixed

1. **Class "Auth" not found error**:
   - The Auth class was using a namespace `Admin` but was being called without the namespace
   - The Database class was also using a namespace `Admin` but was being called without the namespace

2. **Session ini settings warnings**:
   - Session ini settings were being changed after a session was already started
   - This caused warnings in the admin interface

## Changes Made

1. **Auth.php**:
   - Removed the `Admin` namespace
   - Added static `init()` and `checkAuth()` methods to match the expected interface in AdminPage.php
   - Made `logout()` method static for consistency

2. **Database.php**:
   - Removed the `Admin` namespace to allow it to be used without namespace qualification

3. **config.php**:
   - Added a check to only set session ini settings if a session hasn't started yet
   - This prevents the "Session ini settings cannot be changed when a session is active" warnings

4. **admin_api_viewer.html**:
   - Created a simple HTML page to view API data directly
   - This provides a workaround for admin interface issues while they're being fixed

## Testing the Fix

1. **Pull the latest changes**:
   ```bash
   git pull
   ```

2. **Access the admin interface**:
   - Navigate to https://api.storiesfromtheweb.org/admin/
   - You should no longer see the "Class Auth not found" error
   - You should no longer see session ini settings warnings

3. **If you still have issues with the admin interface**:
   - Use the API viewer as a temporary workaround
   - Navigate to https://api.storiesfromtheweb.org/admin_api_viewer.html
   - This page allows you to view data from all API endpoints directly

## API Endpoints

All API endpoints now return data in a consistent flat format:

1. **Directory Items**: `/api/v1/directory-items`
   ```json
   [
     {
       "id": "1",
       "name": "Test Directory",
       "description": "Test directory description",
       "url": "http://example.com",
       "category": "Category1",
       "logo": "https://example.com/dir1.jpg",
       "rating": 4.5,
       "priceRange": "Free",
       "slug": "test-directory",
       "isPublished": true,
       "createdAt": "2025-04-26T09:17:50+01:00",
       "updatedAt": "2025-04-26T09:17:50+01:00"
     }
   ]
   ```

2. **AI Tools**: `/api/v1/ai-tools`
   ```json
   [
     {
       "id": "1",
       "name": "Test AI Tool",
       "description": "Test tool description",
       "url": "http://example.com",
       "category": "Category1",
       "logo": "https://example.com/tool1.jpg",
       "slug": "test-ai-tool",
       "isPublished": true,
       "pricingType": "Free",
       "rating": 0,
       "featured": true,
       "createdAt": "2025-04-26T09:17:50+01:00",
       "updatedAt": "2025-04-26T09:17:50+01:00"
     }
   ]
   ```

3. **Games**: `/api/v1/games`
   ```json
   [
     {
       "id": "1",
       "title": "Test Game",
       "description": "Test game description",
       "url": "http://example.com",
       "category": "Action",
       "thumbnail": "https://example.com/game1.jpg",
       "slug": "test-game",
       "isPublished": true,
       "createdAt": "2025-04-26T09:17:50+01:00",
       "updatedAt": "2025-04-26T09:17:50+01:00"
     }
   ]
   ```

## Troubleshooting

If you encounter any issues:

1. **Check the PHP error log**:
   ```bash
   tail -f /path/to/php/error.log
   ```

2. **Clear browser cache**:
   - Press Ctrl+Shift+Delete in your browser
   - Select "Cached images and files"
   - Click "Clear data"

3. **Check browser console for JavaScript errors**:
   - Press F12 to open developer tools
   - Go to the Console tab
   - Look for any red error messages

4. **Test API endpoints directly**:
   - Use the admin_api_viewer.html page
   - Or use curl: `curl https://api.storiesfromtheweb.org/api/v1/directory-items`

5. **Check file permissions**:
   ```bash
   chmod 644 stories-backend/admin/includes/Auth.php
   chmod 644 stories-backend/admin/includes/Database.php
   chmod 644 stories-backend/admin/includes/config.php
   ```

## Related Files

- `stories-backend/admin/includes/Auth.php` - Authentication class
- `stories-backend/admin/includes/Database.php` - Database connection class
- `stories-backend/admin/includes/config.php` - Configuration file
- `stories-backend/admin/includes/AdminPage.php` - Base admin page class
- `stories-backend/admin_api_viewer.html` - API viewer workaround