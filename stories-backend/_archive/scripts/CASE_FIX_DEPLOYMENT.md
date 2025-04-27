# Case Sensitivity Fix - Git Deployment Instructions

## 1. Local Setup

```bash
# Clone the repository if you haven't already
git clone https://github.com/your-org/stories-from-the-web.git
cd stories-from-the-web

# Configure Git to be case-sensitive (IMPORTANT)
git config core.ignorecase false

# Create a new branch for the fix
git checkout -b fix/case-sensitivity
```

## 2. Add Fix Files

```bash
# Copy the fix files to the correct locations
cp fix_case_once_and_for_all.php stories-backend/
cp CASE_FIX_INSTRUCTIONS.md stories-backend/

# Stage the new files
git add stories-backend/fix_case_once_and_for_all.php
git add stories-backend/CASE_FIX_INSTRUCTIONS.md
git add stories-backend/CASE_FIX_DEPLOYMENT.md
```

## 3. Commit and Push

```bash
# Commit the changes
git commit -m "Add case sensitivity fix script and documentation

- Add fix_case_once_and_for_all.php for comprehensive case fix
- Add CASE_FIX_INSTRUCTIONS.md with usage guide
- Add CASE_FIX_DEPLOYMENT.md with deployment steps
- Configure Git to be case-sensitive"

# Push to remote
git push origin fix/case-sensitivity
```

## 4. Server Deployment

1. SSH into your server:
```bash
ssh user@your-server
```

2. Navigate to your project:
```bash
cd /path/to/stories-from-the-web
```

3. Configure Git on server:
```bash
git config core.ignorecase false
```

4. Pull the changes:
```bash
git pull origin fix/case-sensitivity
```

5. Run the fix script:
```bash
cd stories-backend
php fix_case_once_and_for_all.php
```

6. Verify the changes:
```bash
# Check directory structure
ls -la api/v1/
```

## 5. Verification Steps

1. Verify directory structure:
- Core/ (not core/)
- Middleware/ (not middleware/)
- Endpoints/ (not endpoints/)
- Utils/ (not utils/)
- Config/ (not config/)

2. Check for duplicate directories:
```bash
find . -type d -iname "core" | wc -l    # Should return 1
find . -type d -iname "middleware" | wc -l    # Should return 1
find . -type d -iname "endpoints" | wc -l    # Should return 1
find . -type d -iname "utils" | wc -l    # Should return 1
```

3. Check for backup files:
```bash
find . -type f -name "*.bak*" -o -name "*.orig" -o -name "*.old"
# Should return nothing
```

## 6. Post-Deployment

1. Test the application:
- Check all API endpoints
- Verify admin interface
- Test file uploads

2. Monitor error logs for any case sensitivity issues

3. If everything works:
```bash
# Create a tag for the fix
git tag -a v1.0.0-case-fix -m "Case sensitivity fix applied"
git push origin v1.0.0-case-fix

# Merge to main branch
git checkout main
git merge fix/case-sensitivity
git push origin main
```

## 7. Prevention

The fix script adds these prevention measures:
- Strict PSR-4 autoloader
- .gitignore rules for backup files
- .htaccess protection
- Directory access restrictions

## 8. Rollback (if needed)

If issues occur:
1. Find the backup directory (api_backup_YYYY-MM-DD_HH-II-SS)
2. Restore from backup:
```bash
# Replace dates with actual backup timestamp
cp -r api_backup_2025-04-21_09-38-00/* api/v1/
```

## 9. Future Deployments

For all future deployments:
1. Ensure Git config remains case-sensitive
2. Run case_sensitivity_scan.php periodically
3. Never bypass the strict autoloader
4. Keep prevention measures in place