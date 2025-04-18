# Authentication Fix Documentation

## Overview

This document outlines the fixes implemented to resolve authentication issues in the Stories from the Web admin panel. The main problem was that CRUD operations (add, edit, delete) were failing with "API error: Authentication required (Status: 401)" errors.

## Issues Identified

1. **Token Storage Inconsistency**
   - Tokens were stored in cookies but not in session
   - ApiClient was looking for tokens in session first, then falling back to instance token
   - No mechanism to synchronize tokens between cookie and session

2. **Missing Token Refresh Mechanism**
   - When tokens expired, users were forced to log in again
   - No automatic token refresh when 401 errors were encountered
   - Token expiration handling was limited to clearing the token

3. **Session Management Issues**
   - Session was started in multiple places, potentially causing inconsistencies
   - User data was stored in session, but token was only in cookie

## Implemented Fixes

### 1. Token Storage Consistency

- Updated `Auth::login()` to store token in both session and cookie
- Added token consistency checks in `AdminPage::checkAuth()`
- Modified `ApiClient::request()` to check for tokens in session, cookie, and instance, in that order
- Added automatic session token update when using cookie token

### 2. Token Refresh Mechanism

- Added `Auth::refreshToken()` method to generate a new token for a user
- Added `ApiClient::refreshToken()` method to call the API refresh endpoint
- Added automatic token refresh in ApiClient when 401 errors with "expired" message are encountered
- Created a new API endpoint `auth/refresh` to handle token refresh requests

### 3. Session Management Improvements

- Added `AdminPage::ensureTokenConsistency()` to synchronize tokens between session and cookie
- Improved token handling in `Auth::logout()` to clear both session and cookie tokens
- Enhanced error reporting for authentication issues

## How It Works

1. **Login Process**
   - User logs in via login.php
   - Auth::login() authenticates the user and generates a JWT token
   - Token is stored in both $_SESSION['token'] and a cookie named 'auth_token'
   - User data is stored in $_SESSION['user']

2. **Request Authentication**
   - ApiClient checks for token in session, cookie, and instance
   - Token is included in the Authorization header for API requests
   - If token is found in cookie but not in session, it's automatically added to session

3. **Token Expiration Handling**
   - When a 401 error with "expired" message is received, ApiClient attempts to refresh the token
   - ApiClient calls the auth/refresh endpoint with the user ID
   - If successful, the new token is stored in both session and cookie
   - The original request is retried with the new token
   - If refresh fails, user is prompted to log in again

4. **Token Consistency**
   - AdminPage checks token consistency on each page load
   - If tokens in session and cookie don't match, token is refreshed
   - If token is missing in either session or cookie, it's copied from the other

## Testing

To verify the fix:
1. Log in to the admin panel
2. Navigate to any CRUD page (stories, authors, etc.)
3. Perform CRUD operations (add, edit, delete)
4. Leave the admin panel idle for a while (longer than token expiry)
5. Try to perform CRUD operations again - they should work without requiring re-login

## Future Improvements

1. Implement a more sophisticated token refresh mechanism with refresh tokens
2. Add automatic token refresh before expiration (e.g., refresh when token is 80% through its lifetime)
3. Improve error handling for network issues during token refresh
4. Add more detailed logging for authentication issues