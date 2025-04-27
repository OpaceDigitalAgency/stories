# Case Sensitivity Fix Instructions

## Overview
The `fix_case_once_and_for_all.php` script provides a comprehensive solution for case sensitivity issues by:

1. Directory Structure:
   - Renames directories to correct case (e.g., core → Core)
   - Merges duplicate directories with different cases
   - Removes all backup and temporary files

2. Code References:
   - Updates namespace references (e.g., StoriesAPI\core\ → StoriesAPI\Core\)
   - Fixes require/include statements
   - Updates all file path references
   - Handles both absolute and relative paths

3. Prevention:
   - Installs strict PSR-4 autoloader
   - Adds security measures
   - Implements verification checks

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

1. Automatic Backup:
   - Creates timestamped backup before making changes
   - Backup directory: api_backup_YYYY-MM-DD_HH-II-SS
   - Automatic rollback if any errors occur

2. Verification:
   - Checks directory structure after fixes
   - Verifies all code references
   - Ensures no incorrect cases remain
   - Rolls back if verification fails

3. Logging:
   - Detailed logs of all changes
   - Clear error messages
   - Lists any remaining issues

## Directory Structure

The script enforces the following structure:
```
api/v1/
├── Core/           (not core/)
├── Middleware/     (not middleware/)
├── Endpoints/      (not endpoints/)
├── Utils/         (not utils/)
└── Config/        (not config/)
```

## Prevention Measures

1. Strict Autoloader:
   - Case-sensitive PSR-4 implementation
   - No fallback for wrong case
   - Throws exceptions for case mismatches

2. Security:
   - .htaccess files in each directory
   - Prevents direct PHP file access
   - Protects include files

3. Git Configuration:
   - .gitignore rules for backup files
   - Prevents accidental commits of temporary files

## Verification Steps

After running the script:

1. Check Directory Structure:
   ```bash
   ls -la api/v1/
   ```
   - Verify correct capitalization
   - No duplicate directories

2. Test API Endpoints:
   ```bash
   curl https://your-domain.com/api/v1/stories
   curl https://your-domain.com/api/v1/authors
   ```
   - Should return proper JSON
   - No 500 errors

3. Check Error Logs:
   ```bash
   tail -f logs/api-error.log
   ```
   - No case sensitivity errors
   - No file not found errors

## Troubleshooting

If issues occur:

1. Check Backup:
   - Find latest backup in api_backup_YYYY-MM-DD_HH-II-SS
   - Contains original state before changes

2. Manual Restore:
   ```bash
   cp -r api_backup_YYYY-MM-DD_HH-II-SS/* api/v1/
   ```

3. Review Logs:
   - Script output shows all changes
   - Lists any verification failures

The fix is permanent because:
1. All code references are updated
2. Strict autoloader prevents wrong case usage
3. Security measures prevent inconsistencies
4. Verification ensures completeness