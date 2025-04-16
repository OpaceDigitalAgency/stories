# Deployment Guide for Stories from the Web

This guide explains how to keep your local repository, GitHub, Netlify frontend, and cPanel backend in sync.

## Overview

The project consists of two main parts:

1. **Frontend**: Astro.js application hosted on Netlify
2. **Backend**: PHP/MySQL application hosted on cPanel

The deployment script (`deploy.sh`) helps you keep everything in sync by:

1. Downloading any files added directly to the cPanel server
2. Ensuring all direct login scripts are included in your local repository
3. Committing and pushing changes to GitHub
4. Uploading any local changes to the cPanel server

## Prerequisites

- SSH access to your cPanel server
- Git installed on your local machine
- Bash shell (Linux, macOS, or WSL on Windows)

## Setup

1. Edit the `deploy.sh` script to update the configuration variables:

```bash
CPANEL_HOST="api.storiesfromtheweb.org"
CPANEL_USER="stories"  # Replace with your cPanel username
CPANEL_PATH="/home/stories/api.storiesfromtheweb.org"
LOCAL_BACKEND_PATH="./stories-backend"
```

2. Make the script executable:

```bash
chmod +x deploy.sh
```

## Usage

### Regular Workflow

1. Make changes to your local files
2. Run the deployment script:

```bash
./deploy.sh
```

3. The script will:
   - Download any files added directly to the server
   - Commit and push changes to GitHub
   - Upload changes to the cPanel server
   - Netlify will automatically deploy frontend changes from GitHub

### After Making Direct Changes on the Server

If you've made changes directly on the server (e.g., adding direct login scripts):

1. Run the deployment script:

```bash
./deploy.sh
```

2. The script will download the changes to your local repository and commit them to GitHub

## File Structure

```
/
├── src/                  # Frontend Astro.js code
├── stories-backend/      # Backend PHP code
│   ├── admin/            # Admin UI
│   ├── api/              # API endpoints
│   └── direct login scripts (e.g., direct_login.php)
├── deploy.sh             # Deployment script
└── DEPLOYMENT.md         # This file
```

## Direct Login Scripts

The following direct login scripts are automatically synced:

- `direct_login.php`: Main direct login script
- `auth_test.php`: Authentication test script
- `check_auth_status.php`: Check authentication status
- `go_to_dashboard.php`: Direct access to admin dashboard
- `simple_login_form.php`: Simple login form
- `logout.php`: Logout script

## Troubleshooting

### SSH Connection Issues

If you encounter SSH connection issues:

1. Verify your SSH credentials
2. Check if you can manually SSH into the server:

```bash
ssh stories@api.storiesfromtheweb.org
```

### File Permission Issues

If you encounter permission issues when uploading files:

1. Check the file permissions on the server
2. You may need to adjust permissions:

```bash
ssh stories@api.storiesfromtheweb.org "chmod 755 /path/to/file"
```

### Git Issues

If you encounter Git issues:

1. Check if you have uncommitted changes:

```bash
git status
```

2. Resolve any merge conflicts if they occur

## Keeping Everything in Sync

To ensure everything stays in sync:

1. Always use the deployment script after making changes
2. If you make changes directly on the server, run the script to download those changes
3. Commit and push regularly to keep GitHub and Netlify up to date

By following this workflow, your local repository, GitHub, Netlify frontend, and cPanel backend will stay in sync.