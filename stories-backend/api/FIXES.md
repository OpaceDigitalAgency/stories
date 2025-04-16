# API and Database Connectivity Fixes

This document outlines the changes made to fix the API and database connectivity issues in the admin panel.

## Issues Fixed

1. **Content Security Policy (CSP) Violations**
   - Added the API domain to the connect-src directive in both the main .htaccess file and the admin/.htaccess file.
   - This allows the admin panel to make API requests to api.storiesfromtheweb.org.

2. **API URL Configuration Mismatch**
   - Modified the Router.php file to handle both full URL and relative path formats.
   - The router now correctly processes requests to https://api.storiesfromtheweb.org/api/v1/endpoint.

## Diagnostic Tools

Several diagnostic tools have been created to help troubleshoot API and database connectivity issues:

### 1. API Connection Test

**File:** `test_connection.php`

This script tests the API connectivity and database connection. It checks:
- Database connection
- API routing
- CORS configuration
- API URL configuration

**Usage:** Access this file in your browser at https://api.storiesfromtheweb.org/api/test_connection.php

### 2. PHP Syntax Check

**File:** `check_syntax.php`

This script checks all PHP files in the API directory for syntax errors, which could cause the "Response parsing error: Syntax error" issue.

**Usage:** Access this file in your browser at https://api.storiesfromtheweb.org/api/check_syntax.php

### 3. Database Connection Test

**File:** `test_database.php`

This script tests the database connection specifically and performs basic queries to verify database functionality.

**Usage:** Access this file in your browser at https://api.storiesfromtheweb.org/api/test_database.php

### 4. API Endpoints Test

**File:** `test_endpoints.php`

This script tests the API endpoints directly to check if they're returning properly formatted JSON responses.

**Usage:** Access this file in your browser at https://api.storiesfromtheweb.org/api/test_endpoints.php

## Additional Troubleshooting

If you continue to experience issues, please check the following:

1. **Database Connection**
   - Verify that the database server is running
   - Check that the database credentials in `stories-backend/api/v1/config/config.php` are correct
   - Ensure the database user has the necessary permissions

2. **API Server**
   - Confirm that the API server is running and accessible
   - Check the server logs for any PHP errors or warnings

3. **CORS Configuration**
   - Verify that the allowed origins in `stories-backend/api/v1/config/config.php` include all necessary domains

4. **Content Security Policy**
   - If you're still seeing CSP violations, you may need to adjust the CSP headers in the .htaccess files

## Security Note

The diagnostic tools created for troubleshooting contain sensitive information about your system. Once the issues are resolved, it's recommended to remove or restrict access to these files.