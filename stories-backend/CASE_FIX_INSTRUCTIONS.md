# Case Sensitivity Fix Instructions

## Overview
The `fix_case_once_and_for_all.php` script will permanently fix all case sensitivity issues in the project by:
- Fixing directory and file naming
- Removing duplicates and backups
- Updating code references
- Installing strict checks to prevent future issues

## Usage

### Via Web Browser
1. Upload `fix_case_once_and_for_all.php` to your stories-backend directory
2. Access in browser: `https://your-domain.com/stories-backend/fix_case_once_and_for_all.php`
3. Review the output for any errors

### Via Command Line
1. Upload `fix_case_once_and_for_all.php` to your stories-backend directory
2. SSH into your server
3. Run: `php fix_case_once_and_for_all.php`
4. Review the output for any errors

## Safety Features
- Creates backup before making changes
- Automatic rollback if any errors occur
- Detailed logging of all changes

## After Running
1. Verify the directory structure:
   - Core/ (not core/)
   - Middleware/ (not middleware/)
   - Endpoints/ (not endpoints/)
   - Utils/ (not utils/)
   - Config/ (not config/)

2. Test the application thoroughly:
   - Check all API endpoints
   - Verify admin interface functionality
   - Test file uploads and media handling

3. If any issues occur:
   - Check the backup directory (named api_backup_YYYY-MM-DD_HH-II-SS)
   - The script can be safely run multiple times

## Prevention Measures Added
- Strict PSR-4 autoloader that enforces correct case
- .gitignore rules to prevent backup files
- .htaccess files to protect PHP files
- Directory access restrictions

The fix is permanent because:
1. It removes all duplicate directories
2. The strict autoloader prevents wrong case usage
3. Prevention measures stop backup file accumulation
4. Security measures prevent direct PHP file access