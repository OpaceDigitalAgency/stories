# Automatic Deployment to cPanel with GitHub Actions

This guide explains how to set up automatic deployment to your cPanel server whenever you push to GitHub.

## How It Works

1. You push changes to GitHub
2. GitHub Actions automatically runs the deployment workflow
3. Your changes are deployed to cPanel
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

## Setup Instructions

### 1. Generate an SSH Key Pair

On your Mac, open Terminal and run:

```bash
ssh-keygen -t rsa -b 4096 -C "your_email@example.com" -f ~/.ssh/cpanel_deploy
```

This creates:
- Private key: `~/.ssh/cpanel_deploy`
- Public key: `~/.ssh/cpanel_deploy.pub`

### 2. Add the Public Key to cPanel

1. Log in to cPanel
2. Go to "SSH Access" or "SSH/Shell Access"
3. Click "Manage SSH Keys"
4. Click "Import Key" or "Add Key"
5. Copy the content of `~/.ssh/cpanel_deploy.pub` (run `cat ~/.ssh/cpanel_deploy.pub` in Terminal)
6. Paste it into the "Public Key" field
7. Give it a name like "GitHub Deploy"
8. Click "Import" or "Add Key"
9. Click "Manage" next to the key
10. Click "Authorize" to authorize the key

### 3. Add the Private Key to GitHub Secrets

1. Go to your GitHub repository
2. Click "Settings"
3. Click "Secrets and variables" → "Actions"
4. Click "New repository secret"
5. Name: `CPANEL_SSH_KEY`
6. Value: Copy the entire content of your private key (run `cat ~/.ssh/cpanel_deploy` in Terminal)
7. Click "Add secret"

### 4. Test the Connection

To test if your SSH key works:

```bash
ssh -i ~/.ssh/cpanel_deploy stories@api.storiesfromtheweb.org
```

If it connects without asking for a password, the setup is correct.

## How to Use

Now, whenever you push changes to the `main` branch:

1. GitHub Actions will automatically deploy your backend files to cPanel
2. Netlify will automatically deploy your frontend files

You don't need to run any manual commands. Just:

```bash
git add .
git commit -m "Your commit message"
git push origin main
```

## What the GitHub Action Does

The GitHub Action workflow (`.github/workflows/deploy.yml`) does the following:

1. Deploys direct login scripts to the root of your cPanel domain
2. Deploys the admin directory to the admin directory on your server
3. Deploys the api directory to the api directory on your server
4. Deploys other files (.htaccess, database.sql, README.md) to the root

This ensures that all files are placed in the correct locations on your server.

## Troubleshooting

### Deployment Fails

If the deployment fails, check:

1. GitHub Actions logs (in your repository under "Actions" tab)
2. Make sure the SSH key is correctly added to cPanel and GitHub Secrets
3. Verify the cPanel username and path in the workflow file

### Files Not Updating on cPanel

If files aren't updating on cPanel:

1. Check if the GitHub Action ran successfully
2. Verify the paths in the workflow file
3. Check file permissions on cPanel

## Manual Deployment

If you need to deploy manually, you can use these commands:

```bash
# Deploy direct login scripts
rsync -avz --delete stories-backend/check_auth_status.php stories-backend/direct_login.php stories-backend/go_to_dashboard.php stories-backend/logout.php stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/

# Deploy admin directory
rsync -avz --delete stories-backend/admin/ stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/admin/

# Deploy api directory
rsync -avz --delete stories-backend/api/ stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/api/

# Deploy other files
rsync -avz --delete stories-backend/.htaccess stories-backend/database.sql stories-backend/README.md stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/
```

These are the same commands that the GitHub Action uses.