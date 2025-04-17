# Tasks

## Active Tasks
1. Fix remaining admin panel issues
   - Unblock external scripts & styles (CSP)
   - Load jQuery + Bootstrap before dependent plugins
   - Retire the missing initDropdowns() helper
   - Restore Font Awesome icons
   - Send the JWT on every admin → API request
   - Fix media.php & CKEditor
   - Ensure CRUD buttons work
   - Clean-up tasks

## Completed Tasks
1. Fix Login Authentication Issue ✓
   - Fix the issue where the main login page always returns "Invalid credentials" ✓
   - Create a proper admin user with a correctly hashed password ✓
   - Remove the direct_login.php backdoor for security ✓
   - Protect the admin/includes/ directory from direct access ✓
   - Document the solution in LOGIN_FIX.md ✓
   - Fix Content Security Policy to allow external resources from CDNs ✓
   - Fix JavaScript form interception issue by removing "needs-validation" class ✓

2. Fix Media Page HTTP 500 Error ✓
   - Debug and fix the FileUpload class configuration ✓
   - Ensure proper upload directory paths and permissions ✓
   - Fix media file handling in the admin interface ✓

3. Improve Dashboard UI/UX ✓
   - Add recent content for all content types (not just stories) ✓
   - Improve admin button labeling and intuitiveness ✓
   - Enhance overall dashboard design and usability ✓

4. Fix Missing Data in Content Type Admin Pages ✓
   - Fix data mapping issues in the CrudPage class ✓
   - Ensure proper display of titles, author information, and other missing data ✓
   - Improve data presentation in list and detail views ✓

5. Enhance Overall Admin Interface Design ✓
   - Improve navigation and information architecture ✓
   - Standardize UI components across all admin pages ✓
   - Add better visual cues and feedback for user actions ✓

6. Fix PHP API HTTP 500 errors and autoloader issues ✓
   - Rename folders to match namespace case (endpoints → Endpoints, utils → Utils, core → Core) ✓
   - Fix error reporting in development mode (changed environment to 'development') ✓
   - Fix error log path in .htaccess (created logs directory and updated path) ✓
   - Simplify autoloader to pure PSR-4 ✓
   - Align test script with the real bootstrap ✓
   - Add direct include of Router class as a sanity check ✓

7. Fix admin panel styling ✓
   - Fix initial CSS path issue ✓
   - Fix Content Security Policy (CSP) issues with CSS files ✓

8. Fix PHP errors in admin panel ✓
   - Resolve missing AdminPage class error in stories.php ✓
   - Fix multiple constant definitions in config.php ✓
   - Resolve missing AdminPage class error in all other admin pages ✓

9. Fix API returning HTML instead of JSON responses ✓
   - Test if the current code is being executed ✓
   - Fix case sensitivity issues with directory paths ✓
   - Fix protected property access in Router and BaseController ✓
   - Resolve OpCache issues ✓
   - Fix CSP blocking jQuery by adding CDN domains to script-src in .htaccess ✓
   - Fix Font Awesome 404s by using CDN version ✓
   - Set absolute error log path in .htaccess ✓

## Backlog
- None

## Subtasks
### Fix remaining admin panel issues
1. Unblock external scripts & styles (CSP)
   - Update Content Security Policy in admin/.htaccess to allow external resources
   - Add cdn.ckeditor.com to script-src directive
   - Test by hard-refreshing /admin/index.php and verifying no CSP warnings

2. Load jQuery + Bootstrap before dependent plugins
   - Ensure jQuery and Bootstrap are loaded before other plugins in all admin layouts
   - Fix script loading order in footer.php
   - Test by reloading a list page and checking for "reading fn" errors

3. Retire the missing initDropdowns() helper
   - Comment out or delete the call to initDropdowns() in admin.js
   - Test to confirm no "initDropdowns is not defined" message

4. Restore Font Awesome icons
   - Copy font files to admin/assets/webfonts/ directory
   - Update CSS references to use local files
   - Test to ensure icons render properly

5. Send the JWT on every admin → API request
   - Update ApiClient.php to include JWT token in all requests
   - Test dashboard to ensure API errors disappear and lists populate with data

6. Fix media.php & CKEditor
   - Ensure CKEditor script URL matches the CSP whitelist
   - Test media.php to ensure it loads properly with CKEditor active

7. Ensure CRUD buttons work
   - Test add, edit, delete functionality on stories.php
   - Verify successful operations refresh the list and show success messages

8. Clean-up tasks
   - Remove temporary files (simple_login.php, direct_login.php, etc.)
   - Replace CDN links with local assets where appropriate
   - Commit final working version

### Fix Login Authentication Issue (COMPLETED)
- Analyze the authentication flow in admin/login.php and Auth.php ✓
- Examine direct_login.php to understand how it bypasses password verification ✓
- Create SQL script (create_admin_user.sql) to insert/update admin user with proper password hash ✓
- Create PHP script (create_admin.php) to execute the SQL and create/update the admin user ✓
- Create security script (secure_system.php) to remove backdoor and protect includes directory ✓
- Create documentation (LOGIN_FIX.md) explaining the issue and solution ✓

### Fix admin panel styling (COMPLETED)
- Check if CSS files exist in the admin/assets/css directory ✓
- Ensure CSS files are properly linked in the admin panel templates ✓
- Add missing CSS files if needed ✓
- Download CSS files from CDN sources to local server ✓
- Update header.php to use local CSS files instead of CDN links ✓
- Resolve Content Security Policy (CSP) issues ✓

### Fix missing AdminPage class (COMPLETED)
- Check if AdminPage.php file exists ✓
- Ensure AdminPage.php is properly included in stories.php ✓
- Ensure AdminPage.php is properly included in all other admin pages (blog-posts.php, authors.php, directory-items.php, games.php, ai-tools.php, tags.php) ✓
- Create AdminPage.php if it doesn't exist ✓

### Fix multiple constant definitions (COMPLETED)
- Modify config.php to prevent multiple definitions of constants ✓

### Fix PHP API HTTP 500 errors and autoloader issues (COMPLETED)
- Rename folders to match namespace case: ✓
  - Rename api/v1/endpoints/ → api/v1/Endpoints/ ✓
  - Rename api/v1/utils/ → api/v1/Utils/ ✓
  - Rename api/v1/core/ → api/v1/Core/ ✓
- Fix error reporting in development mode: ✓
  - Changed environment from 'production' to 'development' in config.php ✓
- Fix error log path: ✓
  - Created logs directory in stories-backend folder ✓
  - Updated .htaccess to point to logs/api-error.log ✓
- Fix autoloader implementation: ✓
  - Simplified autoloader to pure PSR-4 in index.php ✓
  - Added direct include of Router class as a sanity check ✓
  - Aligned test script with the real bootstrap ✓