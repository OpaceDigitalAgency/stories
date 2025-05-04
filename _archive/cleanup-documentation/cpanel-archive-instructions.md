# cPanel Archive Instructions (Without SSH)

Since you don't have SSH access to your cPanel server, this guide provides manual steps to archive inactive files in your cPanel environment using the File Manager interface.

## 1. Initial Backup

Before starting, create a full backup of your cPanel account:

1. Log into cPanel
2. Navigate to "Backup" or "Backup Wizard"
3. Select "Download a Full Account Backup"
4. Click "Generate Backup" 
5. Download the backup to your local machine when complete

## 2. Create Archive Directories

For each directory containing files to archive, create an `_archive` folder:

1. In cPanel, navigate to "File Manager"
2. Browse to the directory that contains files to archive
3. Click "New Folder" button
4. Name the folder `_archive`
5. Repeat for each directory where you'll be archiving files:
   - `/api.storiesfromtheweb.org/`
   - `/api.storiesfromtheweb.org/stories-backend/`
   - `/api.storiesfromtheweb.org/stories-backend/api/`
   - `/api.storiesfromtheweb.org/stories-backend/api/v1/`
   - `/api.storiesfromtheweb.org/stories-backend/admin/`
   - `/api.storiesfromtheweb.org/stories-backend/public/`

## 3. Archive Files Using File Manager

### Root Directory Files

In `/api.storiesfromtheweb.org/`, move these files to the `_archive` folder:
- `fix-contacts-server.php`
- `fix-footer-includes.php`
- `fix-form-pages.php`
- `fix-header-includes.php`
- `component-flow-diagram.svg`

Steps:
1. Select the files by checking the boxes next to them
2. Click "Move" button
3. In the destination field, enter `_archive`
4. Click "Move Files" to confirm

### stories-backend Files

In `/api.storiesfromtheweb.org/stories-backend/`, move these files to its `_archive` folder:
- `api_test_suite.php`
- `auth_diagnostic.php` 
- `check_auth_status.php`
- `console_fix.js`
- `diagnostic-dashboard.php`
- `fix_subscribers.php`

### stories-backend/api Files

In `/api.storiesfromtheweb.org/stories-backend/api/`, move these files to its `_archive` folder:
- `check_syntax.php`
- `debug_index.php`
- `debug.php`
- `path_info.php`
- `reset_opcache.php`
- `test_api_fix.php`
- `test_connection.php`
- `test_database.php`
- `test_endpoints.php`

### stories-backend/api/v1 Files

In `/api.storiesfromtheweb.org/stories-backend/api/v1/`, move these files to its `_archive` folder:
- `debug_dump.php`
- `subscribers-fixed.php`

### stories-backend/admin Files

In `/api.storiesfromtheweb.org/stories-backend/admin/`, move these files to its `_archive` folder:
- `test_tools.php`
- `test-db-connection.php`

### stories-backend/public Files

In `/api.storiesfromtheweb.org/stories-backend/public/`, move these files to its `_archive` folder:
- `check_database.php`
- `check-contacts.php`
- `debug_import.php`
- `direct_import.php`
- `fix_media.php`
- `fix_media_with_existing_sizes.php`
- `fix_media_direct.php`
- `fix_media_sizes.php`
- `fix_subscribers_browser.php`
- `fix-contacts-table.php`
- `import_wp.php`
- `simple_import.php`
- `basic_import.php`
- `wp_import_tool.php`
- `test-contact-form.php`
- `test-contacts-table.php`
- `test-db-connection.php`
- `update_media_schema.php`

## 4. Archive Files by Pattern

cPanel File Manager allows searching for files. For each pattern:

1. Click the "Search" button in File Manager
2. Enter the search pattern
3. Select the search location (e.g., `/api.storiesfromtheweb.org/`)
4. View search results and select files to archive
5. Move selected files to the appropriate `_archive` folder

Search for:
- Files with `.bak` extension
- Files starting with `test_`
- Files starting with `debug_`
- Files starting with `fix_`

## 5. Check admin-new Directory

If `/api.storiesfromtheweb.org/admin-new/` exists:

1. Compare it with `/api.storiesfromtheweb.org/admin/` to determine which is newer
2. If `admin-new` is newer, back up `admin`:
   - Create a folder named `_archive_admin`
   - Move the contents of `admin` to `_archive_admin`
   - Move the contents of `admin-new` to `admin`
   - Delete the empty `admin-new` directory
3. If `admin` is newer, archive `admin-new`:
   - Rename `admin-new` to `_archive_admin-new`

## 6. Test After Archiving

1. Test the admin dashboard: https://api.storiesfromtheweb.org/admin/
2. Verify you can log in and manage content
3. Test key API endpoints to ensure they work
4. If any issues occur, move files back from the `_archive` folders as needed

## 7. Document Changes

Create a simple text file in each `_archive` folder listing:
- What files were moved
- When they were moved
- Why they were moved (e.g., "inactive test files")

Example:
```
# Archive Contents (May 4, 2025)

This directory contains files that were archived because they are:
- Test/debug files
- One-time fix scripts
- Backup/old versions

If you need to restore a file, simply move it back to its original location.
```

## 8. Synchronize with Local and Git

Once you've completed the archiving on cPanel:
1. Apply the same organization to your local files
2. Commit the changes to Git
3. Push to your repository

This ensures all three environments (local, Git, cPanel) remain in sync.