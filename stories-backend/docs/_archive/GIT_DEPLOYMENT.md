# Git Deployment Guide for Stories from the Web

This guide explains how to deploy the Stories from the Web backend using cPanel's Git Version Control feature.

## Overview

We've moved from FTPS/GitHub Actions deployment to using cPanel's built-in Git Version Control feature. This approach:

1. Is more reliable than FTPS deployment
2. Doesn't require GitHub Actions configuration
3. Provides a simple UI for deployment
4. Uses a `.cpanel.yml` file to define deployment tasks

## How It Works

1. You push changes to GitHub
2. In cPanel, you update the repository from GitHub
3. You deploy the changes using cPanel's deployment feature
4. The `.cpanel.yml` file defines what files to deploy and where

## Setting Up Git Version Control in cPanel

1. Log in to cPanel
2. Go to "Git Version Control"
3. Click "Create" to create a new repository
4. Enter the following details:
   - Clone URL: `https://github.com/OpaceDigitalAgency/stories.git`
   - Repository Path: `/home/stories/repositories/stories`
   - Repository Name: `stories`
5. Click "Create"

## The .cpanel.yml File

The `.cpanel.yml` file in the root of the repository defines what files to deploy and where:

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

This configuration copies files from the `stories-backend` directory to the website root directory.

## Deployment Process

### 1. Push Changes to GitHub

Make your changes locally, commit them, and push to GitHub:

```bash
git add .
git commit -m "Your commit message"
git push origin main
```

### 2. Update the Repository in cPanel

1. Log in to cPanel
2. Go to "Git Version Control"
3. Find your repository in the list
4. Click "Manage"
5. Click "Update from Remote" to pull the latest changes from GitHub

### 3. Deploy the Changes

After updating the repository:

1. Click "Deploy HEAD Commit" in the repository management page
2. The deployment will run according to the tasks defined in `.cpanel.yml`
3. Wait for the deployment to complete

### 4. Verify the Deployment

After deployment:

1. Check that the files were deployed correctly
2. Test the admin interface at `https://api.storiesfromtheweb.org/admin/`
3. Test the API endpoints

## Troubleshooting

### Deployment Fails

If the deployment fails:

1. **Check the .cpanel.yml file**: Make sure it's properly formatted
2. **Check file permissions**: Files should be readable by the web server
3. **Check for errors in the deployment log**: Look for specific error messages

### Cannot Deploy

If you see "The system cannot deploy" error:

1. Make sure the `.cpanel.yml` file exists in the repository
2. Check that there are no uncommitted changes in the repository
3. Try updating from remote again

### .htaccess Issues

If you're getting 403 Forbidden errors when accessing PHP files:

1. Check the `.htaccess` file in the root directory
2. Make sure it allows access to the necessary PHP files:

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

## Benefits of Git Version Control Deployment

1. **Reliability**: More reliable than FTPS deployment
2. **Simplicity**: No need for complex GitHub Actions configuration
3. **Control**: Deploy when you're ready, not automatically on every push
4. **Visibility**: See deployment history and logs in cPanel
5. **Security**: No need to store FTP credentials in GitHub Secrets

## Frontend Deployment

The frontend is still automatically deployed to Netlify when changes are pushed to GitHub.

## Conclusion

This Git Version Control deployment method provides a more reliable and straightforward way to deploy the Stories from the Web backend. By using cPanel's built-in features, we avoid the complexities and potential issues of FTPS deployment through GitHub Actions.