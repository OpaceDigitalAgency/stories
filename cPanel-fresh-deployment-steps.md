# Safe cPanel Cleanup and Fresh Deployment Steps

This guide provides a step-by-step approach for safely deleting and redeploying files on your cPanel server while keeping your database intact.

## 1. Backup Critical Files First

Before deleting anything, backup these potentially important files that might not be in Git:

```
# Critical configuration files
/api.storiesfromtheweb.org/.htaccess
/api.storiesfromtheweb.org/stories-backend/includes/config.php
/api.storiesfromtheweb.org/stories-backend/.env (if exists)

# Custom server configurations
/api.storiesfromtheweb.org/.user.ini (if exists)
/api.storiesfromtheweb.org/php.ini (if exists)

# Media uploads that may not be in Git
/api.storiesfromtheweb.org/public/uploads/ (if exists)
/api.storiesfromtheweb.org/media/ (if exists)
```

Steps to back these up:
1. In cPanel File Manager, navigate to each file/folder
2. Select it and click "Download" 
3. Or create a compressed archive of critical files for easier download

## 2. Create a Special Backup of Environment-Specific Files

Some files may contain environment-specific settings that differ between your local and production environments:

1. Create a directory called `server-config-backup` on your local machine
2. Download and save the above critical files there
3. Make note of any production-specific settings in these files

## 3. Safe Deletion Procedure

With your backups secured:

1. In cPanel File Manager, navigate to `/api.storiesfromtheweb.org/`
2. Select all files and folders EXCEPT:
   - `.htaccess` (if customized and not in Git)
   - Any upload/media directories with user content
3. Click "Delete" to remove all selected files
4. DO NOT delete any databases or phpMyAdmin settings

## 4. Fresh Deployment from Git

Now deploy a fresh copy from Git:

1. In cPanel, navigate to "Git Version Control"
2. Select your repository
3. Click "Manage" and then "Update from Remote"
4. Choose your branch (likely "main" or "master")
5. Click "Update from Remote" to pull the latest changes
6. Click "Deploy HEAD Commit" to deploy the latest version

## 5. Restore Any Critical Configuration

After the fresh deployment:

1. Compare your backed-up configuration files with the newly deployed versions
2. If necessary, restore environment-specific settings while preserving any updated structure
3. Be especially careful with database connection strings, API keys, and path configurations

## 6. Verification Steps

After redeployment:

1. Check the admin dashboard: https://api.storiesfromtheweb.org/admin/
2. Verify you can log in
3. Check a few key pages/functions to ensure they work
4. Verify database connectivity
5. Check API endpoints

## Important Considerations

- **Configuration Management**: Going forward, consider using environment variables or a more structured approach to managing environment-specific configuration
- **Media/Uploads**: Establish a clear strategy for managing user-uploaded content that shouldn't be in Git
- **Deployment Automation**: Consider creating a deployment script that preserves critical files during updates

This approach ensures you get a clean deployment from Git while preserving any necessary server-specific settings.