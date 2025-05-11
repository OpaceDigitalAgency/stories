# Debug Files Cleanup Plan

## Files to Move to _archive

### Initial Cleanup (May 11, 2025 - First Pass)
The following files in `stories-backend/admin/content/` were moved to `stories-backend/admin/content/_archive/`:

```bash
media_debug.php
debug-inline-editing.php
author-delete-debug.php
debug.php
test-update-avatar.php
```

### Additional Cleanup (May 11, 2025 - Second Pass)

#### From stories-backend/admin/ to stories-backend/admin/_archive/
```bash
emergency_login.php
fix_auth_tables.php
fix_media.php
test_session.php
test-book-form.php
```

#### From stories-backend/admin/content/ to stories-backend/admin/content/_archive/
```bash
fix-author-images.php
fix-blog-posts.php
fix-games.php
subscribers-fixed.php
example-image-generator.php
direct-sql-update.php
```

## Debug Script References Removed

Removed the following debug script reference from these files:

```html
<!-- Include debug script -->
<script src="../assets/js/image-upload-debug.js"></script>
```

Files updated:
- stories-backend/admin/content/author-form.php
- stories-backend/admin/content/post-form.php
- stories-backend/admin/content/story-form.php

## Implementation Steps

1. Switch to Code mode to make the changes
2. Remove debug script references from form files using apply_diff
3. Move debug files to _archive directory
4. Update cleanup documentation
5. Commit changes with message: "Latest stable version - pagination, thumbnails and image upload all working!"

## Rollback Instructions

If needed, you can roll back these changes using:

```bash
git revert HEAD
```

Or to roll back to a specific commit, first find the commit hash:
```bash
git log --grep="Latest stable version"
```

Then revert to that commit:
```bash
git revert <commit-hash>
```

## Summary of Changes
- Removed debug script references from form files
- Archived 5 test/fix files from admin directory
- Archived 6 test/fix files from admin/content directory
- All functionality (pagination, thumbnails, image upload) verified working before archiving

## Deployment Configuration Update
To prevent archived files from being deployed to production, the deployment configuration has been updated:

1. Changed from `cp -R` to `rsync` with exclude pattern
2. Added `--exclude="_archive"` to exclude all _archive directories
3. This ensures archived files remain in git history but don't get deployed

The updated .cpanel.yml configuration:
```yaml
deployment:
  tasks:
    - export DEPLOYPATH=/home/stories/api.storiesfromtheweb.org/
    # Exclude _archive directories from deployment
    - /usr/bin/rsync -av --exclude="_archive" stories-backend/* $DEPLOYPATH
  git:
    merge_strategy: --no-ff