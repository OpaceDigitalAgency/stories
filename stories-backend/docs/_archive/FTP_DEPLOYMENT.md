# FTPS Deployment Setup for Stories from the Web

This guide explains how to set up FTPS deployment (FTP with TLS security) for your Stories from the Web project.

## What's Changed

We've updated the GitHub Actions workflow to use FTPS deployment instead of SSH, since your hosting provider doesn't allow shell access. The new workflow will:

1. Checkout your code from GitHub
2. Deploy the `stories-backend` directory to your cPanel server via FTPS (secure FTP)

## Why FTPS Instead of FTP?

Your server requires secure connections and doesn't accept plain FTP connections. The error message we received was:

```
Sorry, cleartext sessions and weak ciphers are not accepted on this server.
Please reconnect using TLS security mechanisms.
```

FTPS adds a layer of encryption to protect your credentials and data during transfer.

## What You Need to Do

### 1. Create FTP Credentials in cPanel

1. Log in to your cPanel account
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
2. Click on "Settings"
3. Click on "Secrets and variables" → "Actions"
4. Add two new repository secrets:
   - Name: `FTP_USERNAME`
   - Value: Your FTP username (e.g., `stories_deploy@api.storiesfromtheweb.org`)
   
   - Name: `FTP_PASSWORD`
   - Value: Your FTP password

### 3. Test the Deployment

1. Make a small change to any file in your repository
2. Commit and push the change:
   ```bash
   git add .
   git commit -m "Test FTPS deployment"
   git push origin main
   ```
3. Go to the "Actions" tab in your GitHub repository
4. Watch the workflow run and check for any errors

## Troubleshooting

### Common FTPS Issues

1. **Certificate Verification**: Some servers use self-signed certificates that might cause verification issues
2. **Authentication Failed**: Double-check your FTP username and password
3. **Permission Denied**: Make sure the FTP user has write permissions to the target directory
4. **Passive Mode Issues**: Some firewalls block passive mode connections

### Checking Logs

If the deployment fails, check the logs in the GitHub Actions tab for specific error messages.

## Alternative: Manual Deployment

If GitHub Actions FTPS deployment doesn't work, you can always deploy manually:

1. Download an FTP client like FileZilla that supports FTPS
2. Connect to your server using your FTP credentials and enable "Require explicit FTP over TLS"
3. Upload the files from your `stories-backend` directory to your server

## Next Steps

Once the deployment is working:

1. Update your Netlify frontend to use the new API URL
2. Test the admin UI to make sure it's working correctly
3. Continue developing your application with the confidence that changes will be automatically deployed

Let me know if you encounter any issues with the FTPS deployment!