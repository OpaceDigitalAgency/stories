# Admin Dashboard Deployment Plan (No SSH Access)

This document provides a step-by-step plan for cleaning up redundant code and ensuring synchronization between local, cPanel, and Git environments for the Stories admin dashboard, using cPanel's web interface (without SSH access).

## Pre-Deployment Steps

### 1. Create Full Backups

#### Local Backup
```bash
# Local backup
cd /path/to/Stories
zip -r stories-backup-$(date +"%Y%m%d").zip .
```

#### cPanel Backup (via cPanel Web Interface)
1. Log into cPanel
2. Navigate to "Backup" or "Backup Wizard"
3. Select "Download a Full Account Backup"
4. Click "Generate Backup" 
5. Wait for the backup to complete and download it to your local machine

### 2. Database Backup

1. Log into cPanel
2. Navigate to "phpMyAdmin"
3. Select the "stories_db" database from the left sidebar
4. Click the "Export" tab at the top
5. Select "Custom" export method for more options
6. Make sure "SQL" is selected as the format
7. Under "Output" select "Save output to a file"
8. Click "Go" to download the SQL backup file

### 3. Identify Active vs. Redundant Code

Review the following locations for redundant code based on our analysis:

- `stories-backend/admin/_archive/` - Can be removed
- `stories-backend/admin/_wp migration/` - Migration tools, can be archived
- Any file with `test_` or `fix_` prefix - Development tools, not needed in production
- Check if `/api.storiesfromtheweb.org/admin-new/` is a newer version of `/admin/`

## Synchronization Steps

### 1. Git Repository Clean-up

```bash
# Clone fresh copy
git clone https://github.com/OpaceDigitalAgency/stories.git stories-clean

# Remove redundant files
cd stories-clean
rm -rf _archive
rm -rf stories-backend/_archive
rm -rf stories-backend/_wp\ migration
find . -name "test_*.php" -type f -delete
find . -name "fix_*.php" -type f -delete

# Commit clean version
git add .
git commit -m "Clean up redundant files and folders"
git push origin main
```

### 2. Local Environment Clean-up

```bash
# Create clean local copy
cp -r stories-clean /path/to/Stories-clean

# Update configuration if needed
# Edit stories-clean/stories-backend/includes/config.php
```

### 3. Create a Centralized Configuration

```php
<?php
// stories-backend/includes/config.php
return [
    'database' => [
        'host' => 'localhost',
        'name' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    'site' => [
        'name' => 'Stories From The Web',
        'url' => 'https://storiesfromtheweb.org',
        'api_url' => 'https://api.storiesfromtheweb.org',
        'version' => '2.1',
        'environment' => 'production' // 'development', 'staging', or 'production'
    ],
    'auth' => [
        'session_lifetime' => 86400, // 24 hours
        'token_secret' => 'your-secret-key-here'
    ]
];
```

## Deployment Plan

### 1. Prepare Deployment Package

```bash
# Create deployment package
cd /path/to/stories-clean
zip -r stories-deploy.zip stories-backend

# Exclude unnecessary files
zip -d stories-deploy.zip "*.git*" "*.DS_Store" "*__MACOSX*" "*.bak"
```

### 2. cPanel Deployment (Without SSH)

1. **Backup Current Files**:
   - Log into cPanel
   - Navigate to "File Manager"
   - Select the folder `/api.storiesfromtheweb.org`
   - Click "Rename" and rename it to `api.storiesfromtheweb.org.bak-YYYYMMDD` (replace with current date)

2. **Create New Directory**:
   - In File Manager, click "New Folder"
   - Create a folder named `api.storiesfromtheweb.org`

3. **Upload Deployment Package**:
   - In File Manager, navigate to the new folder
   - Click "Upload" and select your `stories-deploy.zip` file
   - Once uploaded, select the zip file and click "Extract"
   - Extract the contents to the current directory

4. **Set Permissions**:
   - Select all files and folders
   - Click "Permissions" (or "Change Permissions")
   - Set directories to 755 (drwxr-xr-x)
   - Set files to 644 (-rw-r--r--)
   - Apply recursively to all files and directories

### 3. Database Updates (if needed)

1. Log into cPanel
2. Navigate to "phpMyAdmin"
3. Select the "stories_db" database
4. Click the "SQL" tab
5. Run any needed database migrations, for example:

```sql
-- Example: Adding new columns or tables
ALTER TABLE stories ADD COLUMN IF NOT EXISTS estimated_reading_time INT DEFAULT 0;
```

### 4. Test Deployment

1. Test admin login: https://api.storiesfromtheweb.org/admin/login.php
2. Verify dashboard loads: https://api.storiesfromtheweb.org/admin/dashboard.php
3. Test content management: Create/edit/delete a test story
4. Test API endpoints: https://api.storiesfromtheweb.org/api/v1/stories.php

### 5. Rollback Plan (if needed)

If deployment fails:

1. Log into cPanel
2. Navigate to "File Manager" 
3. Delete (or rename) the `api.storiesfromtheweb.org` folder
4. Rename `api.storiesfromtheweb.org.bak-YYYYMMDD` back to `api.storiesfromtheweb.org`
5. If needed, restore the database backup through phpMyAdmin:
   - Navigate to phpMyAdmin
   - Select the database
   - Click the "Import" tab
   - Upload your SQL backup file and click "Go"

## Post-Deployment Cleanup

After successful deployment and verification:

1. Remove backup after 30 days if everything works
   - In cPanel File Manager, navigate to your home directory
   - Delete or archive old backup folders

2. Remove local backup files older than 90 days
   - In your local environment, clean up old backups

## Ongoing Synchronization Strategy

To keep all environments in sync going forward:

1. **Development workflow**:
   - Make changes in local environment
   - Test thoroughly
   - Commit to Git
   - Pull changes to staging for testing
   - Deploy to production

2. **Use cPanel Git Version Control**:
   - In cPanel, navigate to "Git Version Control"
   - Create/manage repositories
   - Set up automated deployment from Git
   - Example configuration:
     - Repository URL: https://github.com/OpaceDigitalAgency/stories.git
     - Branch: main
     - Repository Path: /home/username/repositories/stories
     - Deploy Path: /home/username/api.storiesfromtheweb.org

3. **Regular database backups**:
   - Set up automated weekly backups in cPanel:
     - Navigate to "Backup"
     - Configure "Backup Configuration"
     - Enable automated backups
   - Download and store backups securely

4. **Use cPanel File Manager for maintenance**:
   - Periodically check for and remove redundant files
   - Monitor disk usage in cPanel
   - Archive old log files

5. **Documentation updates**:
   - Update these deployment docs when architecture changes
   - Maintain a changelog for significant updates