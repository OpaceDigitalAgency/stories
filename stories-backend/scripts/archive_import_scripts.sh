#!/bin/bash

# Get the script's directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/../.." && pwd )"

# Create archive directory if it doesn't exist
ARCHIVE_DIR="$PROJECT_ROOT/stories-backend/_archive/public_scripts/import_scripts"
mkdir -p "$ARCHIVE_DIR"

echo "Moving files from $PROJECT_ROOT/stories-backend/public to $ARCHIVE_DIR"

# List of redundant scripts to archive
IMPORT_SCRIPTS=(
    # Import scripts
    "simple_import.php"
    "basic_import.php"
    "debug_import.php"
    "import_wp.php"
    "wp_import_tool.php"
    
    # Media scripts
    "fix_media.php"
    "fix_media_with_existing_sizes.php"
    "fix_media_direct.php"
    "fix_media_sizes.php"
    
    # Utility scripts
    "check_story_authors_table.php"
    "clean_database.php"
    "diagnose.php"
    "fix_author_form.php"
    "force_update_author_type.php"
    "reset_database.php"
    "setup_database.php"
    "test_api.php"
    "update_author_types.php"
    "update_media_schema.php"
    "verify_all_connections.php"
    "verify_api.php"
    "verify_db_connection.php"
    "verify_structure.php"
)

# Move each script to archive
for script in "${IMPORT_SCRIPTS[@]}"; do
    SOURCE="$PROJECT_ROOT/stories-backend/public/$script"
    if [ -f "$SOURCE" ]; then
        echo "Moving $script to archive..."
        mv "$SOURCE" "$ARCHIVE_DIR/"
        if [ $? -eq 0 ]; then
            echo "Successfully moved $script"
        else
            echo "Failed to move $script"
        fi
    else
        echo "File not found: $script"
    fi
done

echo "Import scripts archived. Only direct_import.php remains in public folder."

# Create README in archive folder
cat > "$ARCHIVE_DIR/README.md" << EOL
# Archived Scripts

These scripts have been archived as part of the system cleanup. They are kept for reference only and should not be used.

## Active Scripts
The following scripts are currently in use:

- direct_import.php - Main import script for WordPress content
  https://api.storiesfromtheweb.org/public/direct_import.php
- optimize_image.php - Current image optimization script
  https://api.storiesfromtheweb.org/public/optimize_image.php
- check_database.php - Database health check script
  https://api.storiesfromtheweb.org/public/check_database.php

## Archived Scripts

### Import Scripts
- simple_import.php - Basic WordPress import (replaced by direct_import.php)
- basic_import.php - Early version of import script
- debug_import.php - Debug version of import script
- import_wp.php - Old WordPress import script
- wp_import_tool.php - WordPress import utility

### Media Scripts
- fix_media.php - Old media fix script
- fix_media_with_existing_sizes.php - Media size fix script
- fix_media_direct.php - Direct media fix script
- fix_media_sizes.php - Image size optimization script

### Utility Scripts
- Various database, verification, and utility scripts that have been consolidated into the main system

All functionality from these scripts has been incorporated into the active scripts or the main system.
EOL

echo "Archive README created."