# Specific Files to Move to Archive Folders

This document lists specific files and file patterns in your codebase that appear to be inactive or redundant and can be safely moved to archive folders.

## Root Directory Files to Archive

- `fix-contacts-server.php`
- `fix-footer-includes.php`
- `fix-form-pages.php`
- `fix-header-includes.php`
- Any other `fix-*.php` files
- `component-flow-diagram.svg` (if this is documentation and not actively used)

## stories-backend/ Files to Archive

- `api_test_suite.php`
- `auth_diagnostic.php`
- `check_auth_status.php`
- `console_fix.js`
- `diagnostic-dashboard.php`
- `fix_subscribers.php`
- Any `*.bak` files
- Any files containing `test` in the name

## stories-backend/api/ Files to Archive

- `check_syntax.php`
- `debug_index.php`
- `debug.php`
- `path_info.php`
- `reset_opcache.php`
- `test_api_fix.php`
- `test_connection.php`
- `test_database.php`
- `test_endpoints.php`

## stories-backend/api/v1/ Files to Archive

- `debug_dump.php`
- Any file with `-fixed` or `-debug` suffix
- `subscribers-fixed.php` (assuming the regular file exists)

## stories-backend/admin/ Files to Archive

- `test_tools.php`
- `test-db-connection.php`
- Any files not actively used in the admin dashboard

## stories-backend/public/ Files to Archive

- `check_database.php`
- `check-contacts.php`
- `debug_import.php`
- `direct_import.php`
- `fix_media*.php` files
- `fix_subscribers_browser.php`
- `fix-contacts-table.php`
- `import_wp.php`
- `simple_import.php`
- `test-*.php` files
- `*_import.php` files (if they were one-time import tools)

## Additional File Types to Consider Archiving

- Files with `.old` extension
- Files with `.tmp` extension
- Files with `_backup` in the name
- Files with specific dates in the name (indicating a point-in-time backup)
- Any PHP file beginning with `debug_` or `test_`
- Any JS file beginning with `fix-` or `debug-`

## Active Files/Folders to KEEP (not archive)

### Core Admin Dashboard

- `stories-backend/admin/dashboard.php`
- `stories-backend/admin/login.php`
- `stories-backend/admin/logout.php`
- `stories-backend/admin/index.php`
- `stories-backend/admin/content/` folder (all active content management pages)
- `stories-backend/admin/includes/` folder (component files)
- `stories-backend/admin/assets/` folder (CSS, JS, images)

### Authentication

- `stories-backend/simple_auth.php`

### API Endpoints

- `stories-backend/api/index.php`
- `stories-backend/api/v1/api.php`
- `stories-backend/api/v1/index.php`
- Other active API endpoint files (without test/debug prefixes)

### Core Configuration

- `stories-backend/includes/config.php`
- `stories-backend/includes/anti-bot.php`
- `stories-backend/includes/image_optimizer.php`
- `stories-backend/includes/image_config.php`

## How to Safely Archive Files

1. **Create archive subfolders if needed**:
   - For each directory, create an `_archived_files` subfolder

2. **Move files systematically**:
   - Move files by category (test files, debug files, fix scripts, etc.)
   - Keep the same relative path structure within archive folders

3. **Document what you've archived**:
   - Keep a simple text file in each archive folder listing what was moved and when

4. **Test after each batch**:
   - Move files in logical batches and test the application after each move
   - This helps identify any accidentally archived active files

This approach maintains all your code history while clearly separating active from inactive code.