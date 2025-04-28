#!/bin/bash
# Script to archive old import scripts to avoid confusion

# Create archive directory if it doesn't exist
mkdir -p stories-backend/_archive/import_scripts

# Move import files from public directory (except direct_import.php which works correctly)
mv stories-backend/public/import_wp.php stories-backend/_archive/import_scripts/
mv stories-backend/public/simple_import.php stories-backend/_archive/import_scripts/
mv stories-backend/public/debug_import.php stories-backend/_archive/import_scripts/
mv stories-backend/public/basic_import.php stories-backend/_archive/import_scripts/

# Check if wp_import_tool.php exists and move it
if [ -f stories-backend/public/wp_import_tool.php ]; then
    mv stories-backend/public/wp_import_tool.php stories-backend/_archive/import_scripts/
fi

# Create archive directory for _wp migration files
mkdir -p stories-backend/_archive/wp_migration

# Copy _wp migration files (keeping originals for reference)
cp stories-backend/_wp\ migration/export.xml stories-backend/_archive/wp_migration/
cp stories-backend/_wp\ migration/import.mjs stories-backend/_archive/wp_migration/
cp stories-backend/_wp\ migration/package-lock.json stories-backend/_archive/wp_migration/
cp stories-backend/_wp\ migration/package.json stories-backend/_archive/wp_migration/

echo "Import scripts have been archived to stories-backend/_archive/import_scripts/"
echo "WP migration files have been copied to stories-backend/_archive/wp_migration/"
echo ""
echo "IMPORTANT: The direct_import.php script has been kept as it's the recommended import method."