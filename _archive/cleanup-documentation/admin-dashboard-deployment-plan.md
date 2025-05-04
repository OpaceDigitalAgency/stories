# Admin Dashboard Deployment Plan

This document provides a step-by-step plan for cleaning up redundant code and ensuring synchronization between local, cPanel, and Git environments for the Stories admin dashboard.

## Pre-Deployment Steps

### 1. Create Full Backups

```bash
# Local backup
cd /path/to/Stories
zip -r stories-backup-$(date +"%Y%m%d").zip .

# cPanel backup (via SSH)
ssh username@api.storiesfromtheweb.org "cd /home/username && zip -r api-storiesfromtheweb-$(date +"%Y%m%d").zip api.storiesfromtheweb.org"

# Download cPanel backup
scp username@api.storiesfromtheweb.org:/home/username/api-storiesfromtheweb-*.zip /path/to/local/backups/
```

### 2. Database Backup

```bash
# Via SSH or phpMyAdmin, export the database
mysqldump -u stories_user -p stories_db > stories_db_backup_$(date +"%Y%m%d").sql
```

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

### 2. cPanel Deployment

```bash
# Upload the deployment package to cPanel
scp stories-deploy.zip username@api.storiesfromtheweb.org:/home/username/

# SSH into cPanel
ssh username@api.storiesfromtheweb.org

# Backup current version
cd /home/username
mv api.storiesfromtheweb.org api.storiesfromtheweb.org.bak-$(date +"%Y%m%d")

# Create new directory
mkdir -p api.storiesfromtheweb.org

# Extract deployment package
unzip stories-deploy.zip -d api.storiesfromtheweb.org/

# Set correct permissions
cd api.storiesfromtheweb.org
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

### 3. Database Updates (if needed)

```sql
-- Run any needed database migrations
-- Example: Adding new columns or tables
ALTER TABLE stories ADD COLUMN IF NOT EXISTS estimated_reading_time INT DEFAULT 0;
```

### 4. Test Deployment

1. Test admin login: https://api.storiesfromtheweb.org/admin/login.php
2. Verify dashboard loads: https://api.storiesfromtheweb.org/admin/dashboard.php
3. Test content management: Create/edit/delete a test story
4. Test API endpoints: https://api.storiesfromtheweb.org/api/v1/stories.php

### 5. Rollback Plan (if needed)

```bash
# If deployment fails, restore from backup
ssh username@api.storiesfromtheweb.org

# Restore files
rm -rf api.storiesfromtheweb.org
mv api.storiesfromtheweb.org.bak-YYYYMMDD api.storiesfromtheweb.org

# Restore database if needed
mysql -u stories_user -p stories_db < stories_db_backup_YYYYMMDD.sql
```

## Post-Deployment Cleanup

After successful deployment and verification:

```bash
# Remove backup after 30 days if everything works
find /home/username -name "api-storiesfromtheweb-*.zip" -mtime +30 -delete

# Remove local backup files older than 90 days
find /path/to/local/backups -name "stories-backup-*.zip" -mtime +90 -delete
```

## Ongoing Synchronization Strategy

To keep all environments in sync going forward:

1. **Development workflow**:
   - Make changes in local environment
   - Test thoroughly
   - Commit to Git
   - Pull changes to staging for testing
   - Deploy to production

2. **Use .cpanel.yml for automated deployment**:
   ```yaml
   ---
   deployment:
     tasks:
       - export DEPLOYPATH=/home/username/api.storiesfromtheweb.org/
       - /bin/cp -R stories-backend/* $DEPLOYPATH
   ```

3. **Regular database backups**:
   - Set up automated weekly backups in cPanel
   - Download and store backups securely

4. **Documentation updates**:
   - Update these deployment docs when architecture changes
   - Maintain a changelog for significant updates