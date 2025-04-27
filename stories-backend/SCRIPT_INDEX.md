# Stories from the Web - Script Index

This document provides a comprehensive index of all PHP scripts in the Stories from the Web platform, including their purposes and locations.

## Table of Contents

- [Active Scripts](#active-scripts)
  - [Diagnostic Scripts](#diagnostic-scripts)
  - [API Scripts](#api-scripts)
  - [Authentication Scripts](#authentication-scripts)
  - [Admin Scripts](#admin-scripts)
  - [Public Utility Scripts](#public-utility-scripts)
  - [Utility Scripts](#utility-scripts)
  - [Test Scripts](#test-scripts)
- [Archived Scripts](#archived-scripts)
  - [Fix Scripts](#fix-scripts)
  - [Redundant Scripts](#redundant-scripts)
  - [Backup Files](#backup-files)

## Active Scripts

### Diagnostic Scripts

These scripts provide diagnostic capabilities for testing and troubleshooting the platform.

| Script | Purpose | Location |
|--------|---------|----------|
| `diagnostic-dashboard.php` | Comprehensive dashboard that provides links to all diagnostic and testing tools | `/stories-backend/diagnostic-dashboard.php` |
| `api_test_suite.php` | Comprehensive API testing tool that checks endpoint availability, response format consistency, and validates data structures | `/stories-backend/api_test_suite.php` |
| `admin_diagnostic.php` | Comprehensive admin interface diagnostic tool that tests authentication, form submission, API integration, and database connectivity | `/stories-backend/admin_diagnostic.php` |
| `auth_diagnostic.php` | Authentication diagnostic tool that tests login, token handling, and API authentication | `/stories-backend/auth_diagnostic.php` |

### API Scripts

These scripts handle API functionality.

| Script | Purpose | Location |
|--------|---------|----------|
| `api.php` | Main API router that handles all API requests | `/stories-backend/api/v1/api.php` |
| `config.php` | API configuration file | `/stories-backend/api/v1/config/config.php` |
| `index.php` | API entry point | `/stories-backend/api/v1/index.php` |
| `submit-review.php` | API endpoint for submitting reviews | `/stories-backend/api/v1/submit-review.php` |

### Authentication Scripts

These scripts handle authentication functionality.

| Script | Purpose | Location |
|--------|---------|----------|
| `Auth.php` | Authentication class that handles user authentication, token generation, and validation | `/stories-backend/admin/includes/Auth.php` |
| `simple_auth.php` | Simplified authentication system that provides a fallback for the main authentication system | `/stories-backend/simple_auth.php` |
| `check_auth_status.php` | Script to check the current authentication status | `/stories-backend/check_auth_status.php` |

### Admin Scripts

These scripts handle admin interface functionality.

| Script | Purpose | Location |
|--------|---------|----------|
| `Database.php` | Database connection and query handling class | `/stories-backend/admin/includes/Database.php` |
| `dashboard.php` | Admin dashboard | `/stories-backend/admin/dashboard.php` |
| `index.php` | Admin entry point | `/stories-backend/admin/index.php` |
| `login.php` | Admin login page | `/stories-backend/admin/login.php` |
| `logout.php` | Admin logout page | `/stories-backend/admin/logout.php` |
| `story-form.php` | Admin form for creating/editing stories | `/stories-backend/admin/story-form.php` |
| `delete-story.php` | Admin page for deleting stories | `/stories-backend/admin/delete-story.php` |
| `directory-items.php` | Admin interface for managing directory items | `/stories-backend/admin/directory-items.php` |
| `games.php` | Admin interface for managing games | `/stories-backend/admin/games.php` |
| `test_tools.php` | Admin interface for accessing test tools | `/stories-backend/admin/test_tools.php` |

### Public Utility Scripts

These scripts provide public utility functions for database management, testing, and diagnostics.

| Script | Purpose | Location |
|--------|---------|----------|
| `diagnose.php` | Diagnostic tool for troubleshooting | `/stories-backend/public/diagnose.php` |
| `check_database.php` | Check database schema and data | `/stories-backend/public/check_database.php` |
| `verify_db_connection.php` | Verify database connection | `/stories-backend/public/verify_db_connection.php` |
| `verify_api.php` | Verify API connectivity and functionality | `/stories-backend/public/verify_api.php` |
| `verify_all_connections.php` | Verify all connections (database, API, etc.) | `/stories-backend/public/verify_all_connections.php` |
| `verify_structure.php` | Verify file and directory structure | `/stories-backend/public/verify_structure.php` |
| `setup_database.php` | Set up database schema and initial data | `/stories-backend/public/setup_database.php` |
| `reset_database.php` | Reset database to initial state | `/stories-backend/public/reset_database.php` |
| `clean_database.php` | Clean up database by removing orphaned records | `/stories-backend/public/clean_database.php` |

### Utility Scripts

These scripts provide utility functions for the platform.

| Script | Purpose | Location |
|--------|---------|----------|
| `database.sql` | SQL script for creating the database schema | `/stories-backend/database.sql` |
| `create_admin_user.sql` | SQL script for creating an admin user | `/stories-backend/create_admin_user.sql` |
| `console_fix.js` | JavaScript fix for console errors | `/stories-backend/console_fix.js` |

### Test Scripts

These scripts provide unit and integration tests for the platform.

| Script | Purpose | Location |
|--------|---------|----------|
| `StoriesEndpointTest.php` | PHPUnit test for the stories endpoint | `/stories-backend/tests/StoriesEndpointTest.php` |
| `DirectoryAndAiToolsEndpointTest.php` | Test for directory items and AI tools endpoints | `/stories-backend/tests/DirectoryAndAiToolsEndpointTest.php` |

## Archived Scripts

These scripts have been archived and are no longer actively used in the platform. They are kept for reference purposes.

### Fix Scripts

These scripts were created to fix specific issues that have now been incorporated into the main codebase.

| Script | Purpose | Location |
|--------|---------|----------|
| `fix_admin_form.php` | Fixed admin form issues | `/stories-backend/_archive/scripts/fix_admin_form.php` |
| `fix_auth_for_save.php` | Fixed authentication for save operations | `/stories-backend/_archive/scripts/fix_auth_for_save.php` |
| `fix_case_sensitivity.php` | Fixed case sensitivity issues | `/stories-backend/_archive/scripts/fix_case_sensitivity.php` |
| `fix_controller_inheritance.php` | Fixed controller inheritance | `/stories-backend/_archive/scripts/fix_controller_inheritance.php` |
| `fix_dashboard.php` | Fixed dashboard issues | `/stories-backend/_archive/scripts/fix_dashboard.php` |
| `fix_database_schema.php` | Fixed database schema | `/stories-backend/_archive/scripts/fix_database_schema.php` |
| `fix_directory_items_and_ai_tools.php` | Fixed directory items and AI tools | `/stories-backend/_archive/scripts/fix_directory_items_and_ai_tools.php` |
| `fix_games_endpoint.php` | Fixed games endpoint | `/stories-backend/_archive/scripts/fix_games_endpoint.php` |
| `fix_navigation_and_dropdowns.php` | Fixed navigation and dropdowns | `/stories-backend/_archive/scripts/fix_navigation_and_dropdowns.php` |
| `fix_admin_boolean_fields.php` | Fixed admin boolean fields | `/stories-backend/_archive/scripts/fix_admin_boolean_fields.php` |
| `fix_admin_interface_emergency.php` | Emergency fix for admin interface | `/stories-backend/_archive/scripts/fix_admin_interface_emergency.php` |
| `fix_ai_tools_controller.php` | Fixed AI tools controller | `/stories-backend/_archive/scripts/fix_ai_tools_controller.php` |
| `fix_auth_middleware.php` | Fixed authentication middleware | `/stories-backend/_archive/scripts/fix_auth_middleware.php` |
| `fix_case_once_and_for_all.php` | Fixed case sensitivity issues | `/stories-backend/_archive/scripts/fix_case_once_and_for_all.php` |
| `fix_config_simple.php` | Fixed configuration issues | `/stories-backend/_archive/scripts/fix_config_simple.php` |
| `fix_controller_class.php` | Fixed controller class | `/stories-backend/_archive/scripts/fix_controller_class.php` |
| `fix_controller_loading.php` | Fixed controller loading | `/stories-backend/_archive/scripts/fix_controller_loading.php` |
| `fix_controllers_use_statement.php` | Fixed controller use statements | `/stories-backend/_archive/scripts/fix_controllers_use_statement.php` |
| `fix_database_credentials.php` | Fixed database credentials | `/stories-backend/_archive/scripts/fix_database_credentials.php` |
| `fix_debug_mode.php` | Fixed debug mode | `/stories-backend/_archive/scripts/fix_debug_mode.php` |
| `fix_directories.php` | Fixed directories | `/stories-backend/_archive/scripts/fix_directories.php` |
| `fix_directory_items_controller.php` | Fixed directory items controller | `/stories-backend/_archive/scripts/fix_directory_items_controller.php` |
| `fix_dropdowns.php` | Fixed dropdowns | `/stories-backend/_archive/scripts/fix_dropdowns.php` |
| `fix_duplicate_stories.php` | Fixed duplicate stories | `/stories-backend/_archive/scripts/fix_duplicate_stories.php` |
| `fix_form_submission.php` | Fixed form submission | `/stories-backend/_archive/scripts/fix_form_submission.php` |
| `fix_games_endpoint_config.php` | Fixed games endpoint configuration | `/stories-backend/_archive/scripts/fix_games_endpoint_config.php` |
| `fix_login.php` | Fixed login | `/stories-backend/_archive/scripts/fix_login.php` |
| `fix_navigation_only.php` | Fixed navigation | `/stories-backend/_archive/scripts/fix_navigation_only.php` |
| `fix_redirects.php` | Fixed redirects | `/stories-backend/_archive/scripts/fix_redirects.php` |
| `fix_response_class.php` | Fixed response class | `/stories-backend/_archive/scripts/fix_response_class.php` |
| `fix_router_and_config.php` | Fixed router and configuration | `/stories-backend/_archive/scripts/fix_router_and_config.php` |
| `fix_story_flags.php` | Fixed story flags | `/stories-backend/_archive/scripts/fix_story_flags.php` |

### Redundant Scripts

These scripts have functionality that is now part of the core system or is no longer needed.

| Script | Purpose | Location |
|--------|---------|----------|
| `add_debug_logging.php` | Added debug logging | `/stories-backend/_archive/scripts/add_debug_logging.php` |
| `add_slug_to_games.php` | Added slug field to games table | `/stories-backend/_archive/scripts/add_slug_to_games.php` |
| `all_in_one_fix.php` | Combined multiple fixes | `/stories-backend/_archive/scripts/all_in_one_fix.php` |
| `check_files.php` | Checked file existence | `/stories-backend/_archive/scripts/check_files.php` |
| `critical_fix.php` | Applied critical fix | `/stories-backend/_archive/scripts/critical_fix.php` |
| `debug_admin_interface.php` | Debugged admin interface | `/stories-backend/_archive/scripts/debug_admin_interface.php` |
| `deploy_auth_fix.sh` | Deployed authentication fix | `/stories-backend/_archive/scripts/deploy_auth_fix.sh` |
| `find_admin.php` | Found admin files | `/stories-backend/_archive/scripts/find_admin.php` |
| `move_files.php` | Moved files | `/stories-backend/_archive/scripts/move_files.php` |

### Backup Files

These are backup files that are no longer needed but are kept for reference.

| File | Purpose | Location |
|------|---------|----------|
| `config.php.bak` | Backup of configuration file | `/stories-backend/_archive/backup_files/admin/includes/config.php.bak` |
| `form.php.bak` | Backup of form template | `/stories-backend/_archive/backup_files/admin/views/generic/form.php.bak` |
| `list.php.bak` | Backup of list template | `/stories-backend/_archive/backup_files/admin/views/generic/list.php.bak` |
| `delete.php.bak` | Backup of delete template | `/stories-backend/_archive/backup_files/admin/views/generic/delete.php.bak` |
| `view.php.bak` | Backup of view template | `/stories-backend/_archive/backup_files/admin/views/generic/view.php.bak` |

## Conclusion

This script index provides a comprehensive overview of all PHP scripts in the Stories from the Web platform. It helps developers understand what each script does and where to find it, making it easier to maintain and extend the platform.

For more information about the platform architecture and implementation, please refer to the [System Architecture](docs/system-architecture.md) and [Implementation Plan](docs/implementation-plan.md) documentation.

## Accessing Diagnostic Tools

For easy access to all diagnostic and testing tools, visit the [Diagnostic Dashboard](diagnostic-dashboard.php). This dashboard provides links to all the diagnostic and testing tools in one place, making it easier to troubleshoot and maintain the platform.