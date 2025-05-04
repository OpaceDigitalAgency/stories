# cPanel Deployment Safety Checklist

Use this checklist to ensure you don't miss anything critical when refreshing your cPanel deployment.

## Before Deletion

- [ ] Downloaded `.htaccess` file
- [ ] Downloaded `config.php` and any other configuration files 
- [ ] Captured any custom server settings (PHP version, memory limits, etc.)
- [ ] Noted any environment variables or settings specific to production
- [ ] Checked for user-uploaded content not tracked in Git
- [ ] Verified Git repository contains all active code after our cleanup
- [ ] Created a compressed backup of the entire `api.storiesfromtheweb.org` directory just in case
- [ ] Taken screenshots of critical admin settings

## Folders to Preserve (if they contain custom data)

- [ ] `public/uploads` or similar media directories
- [ ] Any cache directories that should persist
- [ ] Custom configuration directories

## Post-Deployment Verification

- [ ] Admin login works
- [ ] Database connection is functioning
- [ ] API endpoints return expected responses
- [ ] All content types are displaying properly
- [ ] Image uploads work
- [ ] Search functionality works
- [ ] Forms submission works
- [ ] Any third-party integrations function as expected

## Common Issues to Watch For

- [ ] Path issues (absolute vs. relative paths)
- [ ] Permission problems (files that need execute permissions)
- [ ] Missing `.htaccess` rules
- [ ] Database credential mismatches
- [ ] PHP version compatibility issues

## Rollback Plan

If something goes wrong:

1. Restore your compressed backup of the entire directory
2. Verify database is still intact
3. Test critical functionality
4. Identify what went wrong before trying again

## Long-term Improvements

- [ ] Create a `.cpanel.yml` file for automated deployments
- [ ] Implement environment-specific configuration management
- [ ] Document server-specific settings for future reference
- [ ] Set up regular backups of user-uploaded content