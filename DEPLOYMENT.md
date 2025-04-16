# Deployment Guide for Stories from the Web

This guide explains the current deployment process for the Stories from the Web project.

## Overview

The project consists of two main parts:

1. **Frontend**: Astro.js application hosted on Netlify
2. **Backend**: PHP/MySQL application hosted on cPanel

## Current Deployment Method

We use cPanel's Git Version Control feature for backend deployment:

1. Changes are pushed to the GitHub repository
2. The repository is cloned/updated in cPanel's Git Version Control
3. The `.cpanel.yml` file defines what files to deploy and where
4. Deployment is triggered manually in cPanel

## File Structure

```
/
├── src/                  # Frontend Astro.js code
├── stories-backend/      # Backend PHP code
│   ├── admin/            # Admin UI
│   ├── api/              # API endpoints
│   ├── direct_login.php  # Direct login script
│   ├── check_auth_status.php # Auth status check
│   ├── go_to_dashboard.php # Dashboard redirect
│   ├── logout.php        # Logout script
│   └── .htaccess         # Apache configuration
├── .cpanel.yml           # cPanel deployment configuration
└── DEPLOYMENT.md         # This file
```

## Setting Up Git Version Control in cPanel

1. Log in to cPanel
2. Go to "Git Version Control"
3. Click "Create" to create a new repository
4. Enter the following details:
   - Clone URL: `https://github.com/OpaceDigitalAgency/stories.git`
   - Repository Path: `/home/stories/repositories/stories`
   - Repository Name: `stories`
5. Click "Create"

## Deploying Changes

After pushing changes to GitHub:

1. Log in to cPanel
2. Go to "Git Version Control"
3. Find your repository in the list
4. Click "Manage"
5. Click "Update from Remote" to pull the latest changes
6. Click "Deploy HEAD Commit" to deploy the changes

The `.cpanel.yml` file defines what files to deploy and where:

```yaml
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/stories/api.storiesfromtheweb.org/
    - /bin/cp -R stories-backend/check_auth_status.php $DEPLOYPATH
    - /bin/cp -R stories-backend/direct_login.php $DEPLOYPATH
    - /bin/cp -R stories-backend/go_to_dashboard.php $DEPLOYPATH
    - /bin/cp -R stories-backend/logout.php $DEPLOYPATH
    - /bin/cp -R stories-backend/.htaccess $DEPLOYPATH
    - /bin/cp -R stories-backend/database.sql $DEPLOYPATH
    - /bin/cp -R stories-backend/README.md $DEPLOYPATH
    - /bin/cp -R stories-backend/admin $DEPLOYPATH
    - /bin/cp -R stories-backend/api $DEPLOYPATH
    - /bin/cp -R stories-backend/test_folder $DEPLOYPATH
```

## Accessing the Admin Interface

After deployment, you can access the admin interface using:

1. **Direct Login URL**:
   ```
   https://api.storiesfromtheweb.org/direct_login.php
   ```

2. **Regular Admin Login**:
   ```
   https://api.storiesfromtheweb.org/admin/login.php
   ```

## Important .htaccess Configuration

The `.htaccess` file has been configured to allow access to the direct login scripts while maintaining security:

```apache
<FilesMatch "\.(sql|log|ini|json)$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^(index\.php|direct_login\.php|check_auth_status\.php|go_to_dashboard\.php|logout\.php)$">
    Order allow,deny
    Allow from all
</FilesMatch>
```

## Troubleshooting

### Deployment Issues

If deployment fails:

1. Check if the `.cpanel.yml` file is properly formatted
2. Verify that the repository is correctly set up in cPanel
3. Check for any error messages in the deployment logs

### Access Issues

If you can't access the admin interface:

1. Check the `.htaccess` file to ensure it allows access to the necessary PHP files
2. Verify file permissions (755 for directories, 644 for files)
3. Check the error logs in cPanel

## Frontend Deployment

The frontend is automatically deployed to Netlify when changes are pushed to the GitHub repository.

## Keeping Everything in Sync

To ensure everything stays in sync:

1. Push changes to GitHub
2. Deploy backend changes using cPanel's Git Version Control
3. Netlify will automatically deploy frontend changes