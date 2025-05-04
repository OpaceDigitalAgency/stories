# Steps to Archive Inactive Files (Not Delete)

I understand you want to keep archive folders as a backup strategy. Here's a revised approach focusing on moving files to archive folders rather than deleting them.

## Step 1: Identify and Archive Inactive Local Files

### Files to Move to `_archive/` or `stories-backend/_archive/`

1. **One-time Fix Scripts**:
   - `fix-contacts-server.php`
   - `fix-footer-includes.php`
   - `fix-form-pages.php`
   - `fix-header-includes.php`
   - Any file starting with `fix-`

2. **Test and Debug Files**:
   - All files with `test` in the name: `*test*.php`
   - All files with `debug` in the name: `*debug*.php`
   - Diagnostic tools: `diagnostic-dashboard.php`
   - Files like `test-db-connection.php`

3. **Backup/Old Files**:
   - Any file with `.bak` extension 
   - Any file with `.old` extension
   - Files with dates in the name (indicating a point-in-time backup)

4. **Development Tools**:
   - `console_fix.js`
   - `api_test_suite.php`

5. **Unused/Outdated Documentation**:
   - Old documentation versions (keep only current versions)
   - `system-documentation.html` (if newer versions exist)

6. **WordPress Migration Files**:
   - Keep `_wp migration/` folder but ensure it's clearly archived

## Step 2: Organize Git Repository

1. **Commit your reorganization**:
   ```bash
   git add .
   git commit -m "Organize files: Move inactive files to _archive folders"
   git push origin main
   ```

2. **Create a .gitignore file to exclude _archive folders** (optional):
   ```
   # Exclude archive folders from Git
   _archive/
   stories-backend/_archive/
   stories-backend/_wp migration/
   ```

## Step 3: Clean Up cPanel (Focus on organization)

1. **Back up cPanel first**:
   - Go to cPanel > Backup > Download a Full Account Backup

2. **Check if admin-new exists**:
   - In cPanel File Manager, check if `/api.storiesfromtheweb.org/admin-new/` exists
   - If it does, determine which is newer (admin or admin-new)
   - Move the older one to an `_archive_admin` folder rather than deleting

3. **Create archive folders if they don't exist**:
   - `/api.storiesfromtheweb.org/_archive/`
   - `/api.storiesfromtheweb.org/stories-backend/_archive/`

4. **Move these types of files to archive folders** (don't delete):
   - Any file starting with `test_` or `fix_`
   - Any file with `debug` in the name
   - Old diagnostic files
   - `.bak` files
   - One-time scripts

## Step 4: Synchronize Everything

1. **Use Git to pull fresh copy to cPanel** (if Git is set up on cPanel):
   - In cPanel, go to Git Version Control
   - Pull the latest changes from your repository

2. **Or manually upload organized files**:
   - Zip your organized local version
   - Upload and extract to cPanel, respecting your archive structure

3. **Final verification**:
   - Test admin dashboard on cPanel: https://api.storiesfromtheweb.org/admin/
   - Verify you can log in and manage content
   - Test a few API endpoints to ensure they work

## Active Folders/Files to Keep in Root Directories

The following should remain in the main directories (not archived):

1. **Core Admin Dashboard**:
   - `stories-backend/admin/dashboard.php`
   - `stories-backend/admin/login.php`
   - `stories-backend/admin/logout.php`
   - `stories-backend/admin/content/*` (content management pages)
   - `stories-backend/admin/includes/*` (component files)
   - `stories-backend/admin/assets/*` (CSS, JS, images)

2. **Authentication**:
   - `stories-backend/simple_auth.php`

3. **API Endpoints**:
   - `stories-backend/api/v1/*` (active API files)
   - `stories-backend/api/index.php`

4. **Core Includes**:
   - `stories-backend/includes/config.php`
   - `stories-backend/includes/image_optimizer.php`

5. **Frontend Files**:
   - `public/*` (publicly accessible files)
   - `src/*` (source files for frontend)

By organizing files this way, you maintain a clean structure where all active code is immediately visible, while preserving backups and older code in clearly marked archive folders.