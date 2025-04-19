# Simple Authentication System Guide

This guide explains how to use the new Simple Authentication System to replace the complex JWT implementation that was causing issues.

## Overview

The Simple Authentication System provides a straightforward, reliable authentication mechanism that:

1. Uses standard PHP sessions for authentication
2. Stores tokens in both sessions and cookies
3. Validates tokens against a database table
4. Provides simple API endpoints for login, logout, and user info
5. Eliminates the JWT secret key inconsistency issues

## Setup Instructions

1. **Create the auth_tokens table**

   Run the setup script to create the necessary database table:

   ```bash
   php setup_simple_auth.php
   ```

2. **Test the authentication system**

   Run the test script to verify the authentication system works:

   ```bash
   php test_simple_auth.php
   ```

3. **The system is already integrated with the API**

   The routes.php file has been updated to use the new SimpleAuthMiddleware and SimpleAuthController.

## How It Works

### Authentication Flow

1. User logs in via the `/api/v1/auth/login` endpoint
2. The system validates credentials against the database
3. If valid, a token is generated and stored in:
   - The database (auth_tokens table)
   - The user's session
   - A cookie for persistent login
4. Protected API endpoints use SimpleAuthMiddleware to validate the token
5. The token is validated against the database and signature

### Key Files

- `simple_auth.php`: The main authentication class
- `setup_simple_auth.php`: Script to set up the database table
- `test_simple_auth.php`: Test script to verify functionality
- `api/v1/Middleware/SimpleAuthMiddleware.php`: API middleware for token validation
- `api/v1/Endpoints/SimpleAuthController.php`: API controller for auth endpoints

## Using in Your Code

### Backend API

The API is already configured to use the new system. Protected routes will automatically use the SimpleAuthMiddleware.

### Frontend

Update your frontend code to use the new authentication endpoints:

```javascript
// Login
async function login(email, password) {
  const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  if (data.data && data.data.token) {
    // Store token in localStorage
    localStorage.setItem('auth_token', data.data.token);
    return data.data.user;
  }
  
  return null;
}

// Logout
async function logout() {
  await fetch('/api/v1/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
    }
  });
  
  localStorage.removeItem('auth_token');
}

// Get current user
async function getCurrentUser() {
  const response = await fetch('/api/v1/auth/me', {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
    }
  });
  
  const data = await response.json();
  
  if (data.data && data.data.user) {
    return data.data.user;
  }
  
  return null;
}

// Add token to API requests
function addAuthHeader(headers = {}) {
  const token = localStorage.getItem('auth_token');
  
  if (token) {
    return {
      ...headers,
      'Authorization': `Bearer ${token}`
    };
  }
  
  return headers;
}
```

## Troubleshooting

If you encounter any issues:

1. Check the PHP error logs for detailed error messages
2. Verify the auth_tokens table was created correctly
3. Ensure the database connection details are correct
4. Test the authentication system with the test script

## Security Considerations

This implementation:

- Uses secure cookies with HttpOnly flag
- Validates tokens against the database
- Includes signature verification
- Expires tokens after 24 hours
- Prevents token reuse by storing in database

## Advantages Over Previous JWT Implementation

1. **Simplicity**: Easier to understand and maintain
2. **Reliability**: No JWT secret key inconsistency issues
3. **Database Validation**: Tokens are validated against the database
4. **Session Support**: Works with standard PHP sessions
5. **Backward Compatibility**: Old API endpoints still work