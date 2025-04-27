# Scripts Archive

This directory contains archived PHP scripts from the Stories from the Web platform. These scripts have been archived rather than deleted to ensure quick recovery if needed.

## Categories of Archived Scripts

### Obsolete Fix Scripts
These scripts were created to fix specific issues that have now been incorporated into the main codebase:
- `fix_admin_form.php` - Fixed admin form issues
- `fix_auth_for_save.php` - Fixed authentication for save operations
- `fix_case_sensitivity.php` - Fixed case sensitivity issues
- `fix_controller_inheritance.php` - Fixed controller inheritance
- `fix_dashboard.php` - Fixed dashboard issues
- `fix_database_schema.php` - Fixed database schema
- `fix_directory_items_and_ai_tools.php` - Fixed directory items and AI tools
- `fix_games_endpoint.php` - Fixed games endpoint
- `fix_navigation_and_dropdowns.php` - Fixed navigation and dropdowns

### Redundant Scripts
These scripts have functionality that is now part of the core system or is no longer needed:
- `add_debug_logging.php` - Added debug logging
- `add_slug_to_games.php` - Added slug field to games table
- `all_in_one_fix.php` - Combined multiple fixes
- `check_files.php` - Checked file existence
- `console_fix.js` - Fixed console issues
- `critical_fix.php` - Applied critical fix
- `debug_admin_interface.php` - Debugged admin interface
- `deploy_auth_fix.sh` - Deployed authentication fix
- `find_admin.php` - Found admin files
- `move_files.php` - Moved files

### Backup Files
These are temporary backup files that are no longer needed:
- Files with `.bak` extension
- Files with `.orig` extension
- Files with `.old` extension
- Files with `.tmp` extension

## Recovery Process

If you need to recover a script from this archive, you can:

1. Copy the script back to its original location:
   ```bash
   cp stories-backend/_archive/scripts/[script_name] stories-backend/
   ```

2. Or view the script's content to extract specific code:
   ```bash
   cat stories-backend/_archive/scripts/[script_name]
   ```

## Archive Date

These scripts were archived on April 27, 2025 as part of the comprehensive cleanup and documentation initiative.