# Consolidated Deployment Guide for Stories from the Web

This document provides a comprehensive guide for deploying the Stories from the Web platform, consolidating information from various deployment-related documentation files.

## Table of Contents

- [Overview](#overview)
- [Project Structure](#project-structure)
- [Deployment Methods](#deployment-methods)
  - [Method 1: cPanel Git Version Control (Recommended)](#method-1-cpanel-git-version-control-recommended)
  - [Method 2: FTPS Deployment with GitHub Actions](#method-2-ftps-deployment-with-github-actions)
  - [Method 3: Manual FTP Deployment](#method-3-manual-ftp-deployment)
- [Frontend Deployment](#frontend-deployment)
- [Troubleshooting](#troubleshooting)
- [Best Practices](#best-practices)

## Overview

The Stories from the Web platform consists of two main components:

1. **Frontend**: Astro.js application hosted on Netlify
2. **Backend**: PHP/MySQL application hosted on cPanel

This guide covers the deployment process for both components, with a focus on the backend deployment which requires more manual steps.

## Project Structure

### GitHub Repository Structure

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
└── netlify.toml          # Netlify configuration
```

### cPanel Server Structure

```
api.storiesfromtheweb.org/
├── admin/                # Admin UI
├── api/                  # API endpoints
├── direct_login.php      # Direct login script
├── check_auth_status.php # Auth status check
├── go_to_dashboard.php   # Dashboard redirect
├── logout.php            # Logout script
└── .htaccess             # Apache configuration
```

## Deployment Methods

There are three methods for deploying the backend to cPanel, listed in order of preference:

### Method 1: cPanel Git Version Control (Recommended)

This is the recommended method as it's more reliable and provides better control over the deployment process.

#### Setting Up Git Version Control in cPanel

1. Log in to cPanel
2. Go to "Git Version Control"
3. Click "Create" to create a new repository
4. Enter the following details:
   - Clone URL: `https://github.com/OpaceDigitalAgency/stories.git`
   - Repository Path: `/home/stories/repositories/stories`
   - Repository Name: `stories`
5. Click "Create"

#### The .cpanel.yml File

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

#### Deployment Process

1. **Push Changes to GitHub**
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin main
   ```

2. **Update the Repository in cPanel**
   - Log in to cPanel
   - Go to "Git Version Control"
   - Find your repository in the list
   - Click "Manage"
   - Click "Update from Remote" to pull the latest changes from GitHub

3. **Deploy the Changes**
   - Click "Deploy HEAD Commit" in the repository management page
   - The deployment will run according to the tasks defined in `.cpanel.yml`
   - Wait for the deployment to complete

4. **Verify the Deployment**
   - Check that the files were deployed correctly
   - Test the admin interface at `https://api.storiesfromtheweb.org/admin/`
   - Test the API endpoints

### Method 2: FTPS Deployment with GitHub Actions

This method uses GitHub Actions to automatically deploy changes to cPanel via FTPS whenever you push to GitHub.

#### Setting Up FTPS Deployment

1. **Create FTP Credentials in cPanel**
   - Log in to cPanel
   - Go to "FTP Accounts" (usually under the "Files" section)
   - Create a new FTP account:
     - Username: Choose a username (e.g., `stories_deploy`)
     - Domain: Select your domain (`api.storiesfromtheweb.org`)
     - Password: Generate a strong password
     - Directory: Set to `/home/stories/api.storiesfromtheweb.org/` (or leave blank for root)
     - Quota: Set as needed (or unlimited)
   - Click "Create" or "Add FTP Account"
   - Note down the FTP username, password, and host

2. **Add FTP Credentials to GitHub Secrets**
   - Go to your GitHub repository
   - Click "Settings"
   - Click "Secrets and variables" → "Actions"
   - Add two new repository secrets:
     - Name: `FTP_USERNAME`
     - Value: Your FTP username (e.g., `stories_deploy@api.storiesfromtheweb.org`)
     
     - Name: `FTP_PASSWORD`
     - Value: Your FTP password

3. **Create GitHub Actions Workflow File**
   - Create a file at `.github/workflows/deploy.yml` with the following content:

   ```yaml
   name: Deploy to cPanel
   
   on:
     push:
       branches: [ main ]
   
   jobs:
     deploy:
       runs-on: ubuntu-latest
       
       steps:
       - uses: actions/checkout@v2
       
       - name: FTP Deploy
         uses: SamKirkland/FTP-Deploy-Action@4.3.0
         with:
           server: api.storiesfromtheweb.org
           username: ${{ secrets.FTP_USERNAME }}
           password: ${{ secrets.FTP_PASSWORD }}
           protocol: ftps
           local-dir: ./stories-backend/
           server-dir: /
           dangerous-clean-slate: false
   ```

4. **Test the Deployment**
   - Make a small change to any file in your repository
   - Commit and push the change:
     ```bash
     git add .
     git commit -m "Test FTPS deployment"
     git push origin main
     ```
   - Go to the "Actions" tab in your GitHub repository
   - Watch the workflow run and check for any errors

### Method 3: Manual FTP Deployment

If automated methods fail, you can always deploy manually using an FTP client.

1. **Download an FTP Client**
   - FileZilla is recommended as it supports FTPS

2. **Connect to Your Server**
   - Host: `api.storiesfromtheweb.org`
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21
   - Protocol: FTP - File Transfer Protocol
   - Encryption: Require explicit FTP over TLS

3. **Upload Files**
   - Navigate to the `stories-backend` directory in your local repository
   - Upload the files to the appropriate location on your server

## Frontend Deployment

The frontend is automatically deployed to Netlify when changes are pushed to GitHub.

### Netlify Configuration

The `netlify.toml` file in the root of the repository configures the Netlify deployment:

```toml
[build]
  command = "npm run build"
  publish = "dist"

[build.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"

[context.production.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"

[context.deploy-preview.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"
```

### Frontend Deployment Process

1. Push changes to GitHub
2. Netlify automatically detects the changes
3. Netlify builds the frontend using the command specified in `netlify.toml`
4. Netlify deploys the built files to its CDN

## Troubleshooting

### cPanel Git Version Control Issues

1. **Deployment Fails**
   - Check the `.cpanel.yml` file for proper formatting
   - Verify file permissions (755 for directories, 644 for files)
   - Check for errors in the deployment log

2. **Cannot Deploy**
   - Make sure the `.cpanel.yml` file exists in the repository
   - Check that there are no uncommitted changes in the repository
   - Try updating from remote again

### FTPS Deployment Issues

1. **Certificate Verification**
   - Some servers use self-signed certificates that might cause verification issues
   - Try adding `forceSSL: false` to the GitHub Action configuration

2. **Authentication Failed**
   - Double-check your FTP username and password
   - Make sure the secrets are correctly added to GitHub

3. **Permission Denied**
   - Make sure the FTP user has write permissions to the target directory

4. **Passive Mode Issues**
   - Some firewalls block passive mode connections
   - Try adding `forcePasv: true` to the GitHub Action configuration

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

## Best Practices

1. **Always Test Locally First**
   - Make sure your changes work locally before deploying
   - Use a local development environment that matches the production environment

2. **Use Version Control**
   - Always commit your changes to Git
   - Use descriptive commit messages
   - Create branches for new features or bug fixes

3. **Backup Before Deployment**
   - Create a backup of the production environment before deploying
   - This includes both files and database

4. **Monitor Deployment**
   - Check the deployment logs for any errors
   - Test the application after deployment
   - Monitor server logs for any issues

5. **Rollback Plan**
   - Have a plan for rolling back changes if something goes wrong
   - Know how to restore from backup
   - Document the rollback process

6. **Security Considerations**
   - Don't commit sensitive information to Git
   - Use environment variables for sensitive information
   - Regularly update dependencies
   - Follow security best practices