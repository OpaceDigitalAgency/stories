# Contacts Page Issue: Duplicate Header and Footer

I've found the issue with the contacts page showing duplicate header and navigation elements. Let me explain the problem and the solution:

## The Problem

Looking at `stories-backend/admin/content/contacts.php`, I can see that:

1. The page includes the header and footer files normally:
   - Line 18: `require_once '../includes/header.php';`
   - Line 722: `require_once '../includes/footer.php';`

2. But the page content itself (lines 333-720) contains its own header-like section:
   ```php
   <div class="content-wrapper">
       <div class="container-fluid">
           <!-- Page header is already included in header.php, so we don't need to repeat it here -->
           // ... rest of the content
       </div>
   </div>
   ```

This results in the duplication you're seeing on the page.

## The Solution

Since this is a server-side issue, this needs to be fixed in the PHP code itself, not with a client-side script. Here's how to fix it:

1. Edit `stories-backend/admin/content/contacts.php`:
   - Keep the `require_once '../includes/header.php';` at the top
   - Keep the `require_once '../includes/footer.php';` at the bottom  
   - Remove the duplicate header/navigation section within the page content
   - Specifically, you need to modify the HTML structure so it doesn't create a second header

2. The fix would involve:
   - Removing any duplicate logo, navigation, or header elements
   - Keeping only the content-specific parts of the page

## Why This Happens

This issue commonly happens during merges or when copying templates from different sources. The developer might have:

1. Started with a complete page template that included header and navigation
2. Added the standard header/footer includes without removing the original ones
3. Or merged code from multiple implementations with overlapping functionality

## Implementation on cPanel

After deploying to cPanel from Git, this should be automatically fixed if the corrected version is in your repository. If you see this issue on cPanel after deployment, it means the corrected version wasn't pushed to Git before deployment.

The cleanest solution is to:
1. Fix the file locally
2. Push to Git
3. Deploy from Git to cPanel

This will ensure all environments have the same, correct version.