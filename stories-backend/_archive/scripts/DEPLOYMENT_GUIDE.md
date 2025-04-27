# Simple Authentication System Deployment Guide

This guide provides step-by-step instructions for deploying the Simple Authentication System to fix the database write issues.

## Files Created/Modified

1. **New Files:**
   - `stories-backend/simple_auth.php` - Main authentication class
   - `stories-backend/setup_simple_auth.php` - Database setup script
   - `stories-backend/test_simple_auth.php` - Testing script
   - `stories-backend/SIMPLE_AUTH_GUIDE.md` - Implementation guide
   - `stories-backend/api/v1/Middleware/SimpleAuthMiddleware.php` - API middleware
   - `stories-backend/api/v1/Endpoints/SimpleAuthController.php` - API controller

2. **Modified Files:**
   - `stories-backend/api/v1/routes.php` - Updated to use the new authentication system

## Git Deployment Instructions

### 1. Push Changes to Git

```bash
# Add all new and modified files
git add stories-backend/simple_auth.php
git add stories-backend/setup_simple_auth.php
git add stories-backend/test_simple_auth.php
git add stories-backend/SIMPLE_AUTH_GUIDE.md
git add stories-backend/DEPLOYMENT_GUIDE.md
git add stories-backend/api/v1/Middleware/SimpleAuthMiddleware.php
git add stories-backend/api/v1/Endpoints/SimpleAuthController.php
git add stories-backend/api/v1/routes.php

# Commit the changes
git commit -m "Implement simplified authentication system to fix database write issues"

# Push to your repository
git push
```

### 2. Server Deployment

After the changes are pushed to Git and auto-deployed to your server:

1. **Create the auth_tokens table:**

   ```bash
   # SSH into your server
   ssh user@your-server
   
   # Navigate to your project directory
   cd /path/to/stories-backend
   
   # Run the setup script
   php setup_simple_auth.php
   ```

2. **Test the authentication system:**

   ```bash
   # Run the test script
   php test_simple_auth.php
   ```

3. **Verify API functionality:**

   Test the login endpoint with a tool like cURL or Postman:

   ```bash
   curl -X POST https://api.storiesfromtheweb.org/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"your-email@example.com","password":"your-password"}'
   ```

## Implementation Steps

1. **Database Setup:**
   - The `setup_simple_auth.php` script creates the necessary `auth_tokens` table
   - This table stores active authentication tokens for validation

2. **API Integration:**
   - The API now uses `SimpleAuthMiddleware` for authentication
   - All protected routes automatically use the new authentication system
   - Existing API endpoints continue to work with the new system

3. **Frontend Updates:**
   - No immediate changes needed for the frontend
   - The API maintains backward compatibility
   - For future improvements, follow the frontend code examples in `SIMPLE_AUTH_GUIDE.md`

## Troubleshooting

If you encounter any issues after deployment:

1. **Check PHP error logs:**
   ```bash
   tail -f /path/to/php/error.log
   ```

2. **Verify database table creation:**
   ```sql
   SHOW TABLES LIKE 'auth_tokens';
   ```

3. **Test authentication manually:**
   ```bash
   php test_simple_auth.php
   ```

4. **Check API response headers:**
   Look for authentication-related headers in API responses

## Rollback Plan

If you need to revert these changes:

```bash
# Revert the commit
git revert HEAD

# Push the revert
git push
```

## Additional Resources

- Refer to `SIMPLE_AUTH_GUIDE.md` for detailed implementation information
- The `test_simple_auth.php` script can be used for ongoing testing and verification