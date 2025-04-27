#!/bin/bash

# Create directories if they don't exist
mkdir -p _archive/root-files
mkdir -p _archive/images
mkdir -p _archive/scripts
mkdir -p _archive/docs
mkdir -p public/images

# Move PHP scripts to archive/scripts
echo "Moving PHP scripts to archive..."
mv case_dir_audit.php _archive/scripts/
mv case_sensitivity_scan.php _archive/scripts/
mv fix_case.php _archive/scripts/
mv phpinfo.php _archive/scripts/

# Move images to appropriate locations
echo "Organizing images..."
# Move logo files to public/images for use in the application
cp "stories from the web logo 1.png" public/images/
cp "stories from the web logo trans new.png" public/images/
cp "stories from the web logo trans small.png" public/images/
cp "stories from the web logo trans.png" public/images/
cp "stories_from_the_web_transparent.png" public/images/

# Archive original logo files
mv "stories from the web logo 1.png" _archive/images/
mv "stories from the web logo trans new.png" _archive/images/
mv "stories from the web logo trans small.png" _archive/images/
mv "stories from the web logo trans.png" _archive/images/
mv "stories_from_the_web_transparent.png" _archive/images/

# Move other image files to archive
mv a86ce8c1-93c2-419f-8554-6e5b5db084f8.png _archive/images/
mv "ChatGPT Image Apr 14, 2025, 09_22_10 PM.png" _archive/images/

# Move ERD.png to documentation
cp ERD.png stories-backend/docs/
mv ERD.png _archive/docs/

# Move deployment script to archive
echo "Archiving deployment script..."
mv deploy.sh _archive/scripts/

# Move source zip to archive
echo "Archiving source zip..."
mv src.zip _archive/root-files/

# Move our temporary scripts to archive when done
echo "Note: The move_*.sh and organize_root_files.sh scripts should be archived after use"

# Create README file for the archive
echo "Creating README file for the archive..."
cat > _archive/README.md << 'EOF'
# Archive Directory

This directory contains archived files from the Stories from the Web platform. These files have been archived rather than deleted to ensure quick recovery if needed.

## Directory Structure

- **root-files/**: Files from the root directory that are no longer needed
- **images/**: Image files that have been moved to more appropriate locations
- **scripts/**: PHP scripts and shell scripts that are no longer needed
- **docs/**: Documentation files that have been archived

## Recovery Process

If you need to recover a file from this archive, you can:

1. Copy the file back to its original location:
   ```bash
   cp _archive/path/to/file original/location/
   ```

2. Or view the file's content to extract specific information:
   ```bash
   cat _archive/path/to/file
   ```

## Archive Date

These files were archived on April 27, 2025 as part of the comprehensive cleanup and documentation initiative.
EOF

echo "Root file organization complete!"

# List remaining files in root directory
echo ""
echo "Remaining files in root directory:"
ls -la | grep -v "_archive" | grep -v "node_modules" | grep -v "\.git"