# Automatic Deployment to cPanel with GitHub Actions (FTPS Method)

This guide explains how to set up automatic deployment to your cPanel server whenever you push to GitHub.

## How It Works

1. You push changes to GitHub
2. GitHub Actions automatically runs the deployment workflow
3. Your changes are deployed to cPanel via FTPS (secure FTP)
4. Netlify automatically deploys frontend changes

## Understanding the File Structure

There's a difference between how files are organized in your GitHub repository and on your cPanel server:

### GitHub Repository Structure
```
stories-backend/
├── admin/
├── api/
├── direct_login.php
├── check_auth_status.php
├── go_to_dashboard.php
├── logout.php
└── ...
```

### cPanel Server Structure
```
api.storiesfromtheweb.org/
├── admin/
├── api/
├── direct_login.php
├── check_auth_status.php
├── go_to_dashboard.php
├── logout.php
└── ...
```

The GitHub Action workflow is configured to handle this difference by deploying files to the correct locations.

## Why FTPS Instead of SSH?

We're using FTPS deployment for two reasons:
1. Your cPanel hosting doesn't allow shell access, which is required for SSH/rsync deployment
2. Your server requires secure connections and doesn't accept plain FTP connections

FTPS adds encryption to protect your credentials and data during transfer.

## Setup Instructions

### 1. Create FTP Credentials in cPanel

1. Log in to cPanel
2. Go to "FTP Accounts" (usually under the "Files" section)
3. Create a new FTP account:
   - Username: Choose a username (e.g., `stories_deploy`)
   - Domain: Select your domain (`api.storiesfromtheweb.org`)
   - Password: Generate a strong password
   - Directory: Set to `/home/stories/api.storiesfromtheweb.org/` (or leave blank for root)
   - Quota: Set as needed (or unlimited)
4. Click "Create" or "Add FTP Account"
5. Note down the FTP username, password, and host

### 2. Add FTP Credentials to GitHub Secrets

1. Go to your GitHub repository
2. Click "Settings"
3. Click "Secrets and variables" → "Actions"
4. Add two new repository secrets:
   - Name: `FTP_USERNAME`
   - Value: Your FTP username (e.g., `stories_deploy@api.storiesfromtheweb.org`)
   
   - Name: `FTP_PASSWORD`
   - Value: Your FTP password

### 3. Test the Deployment

Make a small change to any file in your repository and push it to GitHub:

```bash
git add .
git commit -m "Test FTPS deployment"
git push origin main
```

Then check the Actions tab to see if the workflow runs successfully.

## How to Use

Now, whenever you push changes to the `main` branch:

1. GitHub Actions will automatically deploy your backend files to cPanel via FTPS
2. Netlify will automatically deploy your frontend files

You don't need to run any manual commands. Just:

```bash
git add .
git commit -m "Your commit message"
git push origin main
```

## What the GitHub Action Does

The GitHub Action workflow (`.github/workflows/deploy.yml`) uses the FTP-Deploy-Action to:

1. Connect to your cPanel server via FTPS (secure FTP)
2. Upload all files from the `stories-backend` directory to your server
3. Maintain the same directory structure on the server

## Troubleshooting

### Deployment Fails

If the deployment fails, check:

1. GitHub Actions logs (in your repository under "Actions" tab)
2. Make sure the FTP credentials are correctly added to GitHub Secrets
3. Verify that your hosting provider supports FTPS connections

### Files Not Updating on cPanel

If files aren't updating on cPanel:

1. Check if the GitHub Action ran successfully
2. Verify the paths in the workflow file
3. Check file permissions on cPanel

## Manual Deployment

If you need to deploy manually, you can use an FTP client like FileZilla:

1. Connect to your server using your FTP credentials and enable "Require explicit FTP over TLS"
2. Upload the files from your `stories-backend` directory to your server

## For More Information

See the `FTP_DEPLOYMENT.md` file for more detailed instructions and troubleshooting tips.