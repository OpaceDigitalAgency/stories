# Diagnostic Tools Documentation

This document provides detailed information about the diagnostic tools available in the Stories from the Web platform.

## Overview

The diagnostic tools are organized into the following categories:

1. **API Tests** - Tools for testing API endpoints and functionality
2. **Authentication Tests** - Tools for testing authentication and session management
3. **Admin Tests** - Tools for testing admin interface functionality
4. **Database Tests** - Tools for testing database connectivity and schema
5. **System Tests** - Tools for testing system-level functionality
6. **Media Tests** - Tools for testing media uploads and image optimization
7. **Documentation** - Documentation for the diagnostic tools

All diagnostic tools are accessible from the main diagnostic dashboard at `/diagnostic-dashboard.php`.

## Directory Structure

The diagnostic tools are organized in the following directory structure:

```
stories-backend/
├── diagnostic-dashboard.php
├── diagnostics/
│   ├── README.md
│   ├── api/
│   │   ├── api_test_suite.php
│   │   ├── test_api_endpoints.php
│   │   └── verify_api.php
│   ├── auth/
│   │   ├── check_auth_status.php
│   │   ├── clear_session.php
│   │   └── emergency_login.php
│   ├── admin/
│   │   └── admin_diagnostic.php
│   ├── database/
│   │   └── check_database.php
│   ├── system/
│   │   └── verify_structure.php
│   ├── media/
│   │   ├── media_diagnostic.php
│   │   └── fix_media.php
│   ├── docs/
│   │   └── diagnostic_tools.md
│   └── includes/
│       └── common.php
```

## API Tests

### API Test Suite (`api/api_test_suite.php`)

This tool tests API endpoints for availability, response format, and data structure. It checks that endpoints return the expected HTTP status codes and that responses contain the expected fields.

### Test API Endpoints (`api/test_api_endpoints.php`)

This tool tests specific API endpoints and functionality. It sends requests to various endpoints and displays the results.

### Verify API (`api/verify_api.php`)

This tool verifies API connectivity and functionality. It checks that the API is accessible and that authentication endpoints are working.

## Authentication Tests

### Authentication Diagnostic (`auth_diagnostic.php`)

This tool tests the authentication flow and provides detailed information about any issues. It can be used to verify that the authentication fixes are working correctly.

### Check Auth Status (`auth/check_auth_status.php`)

This tool checks the current authentication status. It displays information about the current session, tokens, and user.

### Clear Session (`auth/clear_session.php`)

This tool clears all session data and cookies to fix login issues. It can be used when authentication is not working correctly.

### Emergency Login (`auth/emergency_login.php`)

This tool provides emergency login functionality to bypass normal authentication. It should only be used in emergency situations when normal login is not working.

## Database Tests

### Check Database (`database/check_database.php`)

This tool checks database schema and data. It verifies that all required tables and columns exist and displays sample data.

## Media Tests

### Media Diagnostic (`media/media_diagnostic.php`)

This tool diagnoses issues with media uploads and image optimization. It checks for required directories, PHP extensions, and database tables.

### Fix Media Issues (`media/fix_media.php`)

This tool fixes common issues with media uploads and image optimization. It creates required directories, fixes database tables, and creates required files.

## Common Functions

The `includes/common.php` file contains common functions used by all diagnostic tools, including:

- `getBaseUrl()` - Get the base URL for the application
- `getBaseApiUrl()` - Get the base API URL
- `formatFileSize()` - Format file size in human-readable format
- `checkDirectory()` - Check if a directory is writable and create it if it doesn't exist
- `checkExtension()` - Check if a PHP extension is loaded
- `testDatabaseConnection()` - Test database connection
- `outputDiagnostic()` - Output diagnostic information in a standardized format

## Usage

To use the diagnostic tools:

1. Access the diagnostic dashboard at `/diagnostic-dashboard.php`
2. Click on the tool you want to use
3. Follow the instructions on the tool's page

## Important Notes

1. **DO NOT DELETE OR MOVE** these diagnostic tools. They are critical for system maintenance.
2. All diagnostic tools should be accessible from the main diagnostic dashboard at `/diagnostic-dashboard.php`.
3. If you create a new diagnostic tool, please add it to the appropriate directory and update the dashboard.
4. All diagnostic tools should include proper error handling and clear instructions for use.
5. The emergency login tool should only be used in emergency situations when normal login is not working.
6. The fix media issues tool should be used with caution as it may modify database tables and files.

## Troubleshooting

If you encounter issues with the diagnostic tools:

1. Check that the diagnostic tools are in the correct directory
2. Check that the diagnostic tools have the correct permissions
3. Check that the diagnostic tools are accessible from the diagnostic dashboard
4. Check the error logs for any error messages
5. Try clearing your browser cache and cookies
6. Try using a different browser

If you still encounter issues, please contact the system administrator.
