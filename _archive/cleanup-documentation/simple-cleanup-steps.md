# Simple Steps to Clean Up and Synchronize Environments

## Step 1: Clean Up Local Environment

1. **Delete these folders locally**:
   - `_archive/` folder in the root directory
   - `stories-backend/_archive/` folder
   - `stories-backend/_wp migration/` folder

2. **Delete these types of files locally**:
   - Any file starting with `test_` or `fix_`
   - Any file with `debug` in the name
   - Files like `test-db-connection.php`, `diagnostic-dashboard.php`

3. **Test your local setup**:
   - Make sure the admin dashboard still loads
   - Verify you can still create/edit content

## Step 2: Update Git Repository

1. **Commit your cleaned-up local files**:
   ```bash
   git add .
   git commit -m "Clean up redundant files and folders"
   git push origin main
   ```

2. **Verify the commit**:
   - Check your GitHub repository to confirm the changes were pushed

## Step 3: Clean Up cPanel

1. **Back up cPanel first**:
   - Go to cPanel > Backup > Download a Full Account Backup
   - Generate and download the backup

2. **Check if admin-new exists**:
   - In cPanel File Manager, check if `/api.storiesfromtheweb.org/admin-new/` exists
   - If it does, determine which is newer (admin or admin-new)
   - Keep only the current version, rename the older one to `_archive_admin`

3. **Delete these folders on cPanel**:
   - `/api.storiesfromtheweb.org/_archive/`
   - `/api.storiesfromtheweb.org/stories-backend/_archive/`
   - `/api.storiesfromtheweb.org/stories-backend/_wp migration/`
   - Any folder named `tests` or `scripts` that's not actively used

4. **Delete these types of files on cPanel**:
   - Any file starting with `test_` or `fix_`
   - Any file with `debug` in the name
   - Old diagnostic files

## Step 4: Synchronize Everything

1. **Use Git to pull fresh copy to cPanel** (if Git is set up on cPanel):
   - In cPanel, go to Git Version Control
   - Pull the latest changes from your repository

2. **Or manually upload cleaned files**:
   - Zip your clean local version
   - Upload and extract to cPanel, replacing existing files

3. **Final verification**:
   - Test admin dashboard on cPanel: https://api.storiesfromtheweb.org/admin/
   - Verify you can log in and manage content
   - Test a few API endpoints to ensure they work