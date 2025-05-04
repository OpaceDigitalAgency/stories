# Archive Files Script

This document contains a bash script to automatically archive inactive files in your project. The script creates _archive folders in each directory and moves the specified files to maintain the directory structure.

## How to Use This Script

1. Copy the script below to a new file named `archive_files.sh` in your project root
2. Make it executable with `chmod +x archive_files.sh`
3. Run it with `./archive_files.sh`

## The Script

```bash
#!/bin/bash

# Script to archive inactive files by moving them to _archive folders within their respective directories
# Created: May 4, 2025

# Set the base directory to the current directory
BASE_DIR="$(pwd)"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="${BASE_DIR}/archive_operation_${TIMESTAMP}.log"

# Function to create archive directory if it doesn't exist
create_archive_dir() {
    local dir="$1"
    if [ ! -d "${dir}/_archive" ]; then
        echo "Creating archive directory: ${dir}/_archive"
        mkdir -p "${dir}/_archive"
    fi
}

# Function to archive a file
archive_file() {
    local file="$1"
    local dir=$(dirname "$file")
    local filename=$(basename "$file")
    
    # Create archive directory if it doesn't exist
    create_archive_dir "$dir"
    
    # Move the file to the archive directory
    if [ -f "$file" ]; then
        echo "Moving $file to ${dir}/_archive/$filename"
        mv "$file" "${dir}/_archive/$filename"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Moved: $file -> ${dir}/_archive/$filename" >> "$LOG_FILE"
    else
        echo "Warning: File not found: $file"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: File not found: $file" >> "$LOG_FILE"
    fi
}

echo "Starting archiving operation at $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$LOG_FILE"
echo "Files will be moved to _archive folders in their respective directories" | tee -a "$LOG_FILE"
echo "Log file: $LOG_FILE" | tee -a "$LOG_FILE"
echo "----------------------------------------" | tee -a "$LOG_FILE"

# Root directory files to archive
echo "Archiving files in root directory..." | tee -a "$LOG_FILE"
ROOT_FILES=(
    "fix-contacts-server.php"
    "fix-footer-includes.php"
    "fix-form-pages.php"
    "fix-header-includes.php"
    "component-flow-diagram.svg"
)

for file in "${ROOT_FILES[@]}"; do
    archive_file "${BASE_DIR}/$file"
done

# stories-backend files to archive
echo "Archiving files in stories-backend directory..." | tee -a "$LOG_FILE"
BACKEND_FILES=(
    "api_test_suite.php"
    "auth_diagnostic.php" 
    "check_auth_status.php"
    "console_fix.js"
    "diagnostic-dashboard.php"
    "fix_subscribers.php"
)

for file in "${BACKEND_FILES[@]}"; do
    archive_file "${BASE_DIR}/stories-backend/$file"
done

# stories-backend/api files to archive
echo "Archiving files in stories-backend/api directory..." | tee -a "$LOG_FILE"
API_FILES=(
    "check_syntax.php"
    "debug_index.php"
    "debug.php"
    "path_info.php"
    "reset_opcache.php"
    "test_api_fix.php"
    "test_connection.php"
    "test_database.php"
    "test_endpoints.php"
)

for file in "${API_FILES[@]}"; do
    archive_file "${BASE_DIR}/stories-backend/api/$file"
done

# stories-backend/api/v1 files to archive
echo "Archiving files in stories-backend/api/v1 directory..." | tee -a "$LOG_FILE"
API_V1_FILES=(
    "debug_dump.php"
    "subscribers-fixed.php"
)

for file in "${API_V1_FILES[@]}"; do
    archive_file "${BASE_DIR}/stories-backend/api/v1/$file"
done

# stories-backend/admin files to archive
echo "Archiving files in stories-backend/admin directory..." | tee -a "$LOG_FILE"
ADMIN_FILES=(
    "test_tools.php"
    "test-db-connection.php"
)

for file in "${ADMIN_FILES[@]}"; do
    archive_file "${BASE_DIR}/stories-backend/admin/$file"
done

# stories-backend/public files to archive
echo "Archiving files in stories-backend/public directory..." | tee -a "$LOG_FILE"
PUBLIC_FILES=(
    "check_database.php"
    "check-contacts.php"
    "debug_import.php"
    "direct_import.php"
    "fix_media.php"
    "fix_media_with_existing_sizes.php"
    "fix_media_direct.php"
    "fix_media_sizes.php"
    "fix_subscribers_browser.php"
    "fix-contacts-table.php"
    "import_wp.php"
    "simple_import.php"
    "basic_import.php"
    "wp_import_tool.php"
    "test-contact-form.php"
    "test-contacts-table.php"
    "test-db-connection.php"
    "update_media_schema.php"
)

for file in "${PUBLIC_FILES[@]}"; do
    archive_file "${BASE_DIR}/stories-backend/public/$file"
done

# Archive files by pattern
echo "Archiving files by pattern..." | tee -a "$LOG_FILE"
echo "Finding files with .bak extension..." | tee -a "$LOG_FILE"
find "${BASE_DIR}" -name "*.bak" -type f | while read file; do
    archive_file "$file"
done

echo "Finding files with test_ prefix..." | tee -a "$LOG_FILE"
find "${BASE_DIR}" -name "test_*.php" -type f | while read file; do
    archive_file "$file"
done

echo "Finding files with debug_ prefix..." | tee -a "$LOG_FILE"
find "${BASE_DIR}" -name "debug_*.php" -type f | while read file; do
    archive_file "$file"
done

echo "Finding files with fix_ prefix..." | tee -a "$LOG_FILE"
find "${BASE_DIR}" -name "fix_*.php" -type f | while read file; do
    # Skip already processed files
    if [[ ! "$file" == *"/_archive/"* ]]; then
        archive_file "$file"
    fi
done

echo "----------------------------------------" | tee -a "$LOG_FILE"
echo "Archiving operation completed at $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$LOG_FILE"
echo "See $LOG_FILE for details"

# Create a summary report of what was archived
echo "Generating summary report..."
echo "# Archive Operation Summary - $(date '+%Y-%m-%d %H:%M:%S')" > "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "" >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "## Files Moved to Archive" >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "" >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
grep "Moved:" "$LOG_FILE" | sed 's/\[.*\] Moved: /- /' >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "" >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "## Files Not Found" >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "" >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
grep "Warning:" "$LOG_FILE" | sed 's/\[.*\] Warning: File not found: /- /' >> "${BASE_DIR}/archive_summary_${TIMESTAMP}.md"

echo "Summary report created: ${BASE_DIR}/archive_summary_${TIMESTAMP}.md"
echo "Done!"
```

## Understanding the Script

This script:

1. Creates _archive folders in each directory where files need to be archived
2. Moves specified files to their corresponding _archive folder
3. Preserves the directory structure so you know where each file came from
4. Generates a detailed log of the operations
5. Creates a summary markdown report of what was archived

## What the Script Archives

- The specific files listed in the arrays for each directory
- All files with `.bak` extension
- All PHP files with `test_`, `debug_`, or `fix_` prefixes

## After Running

After running the script:

1. Check the generated log file and summary report
2. Verify that your application still works correctly
3. Commit the changes to Git if everything looks good
4. Apply similar organization to your cPanel environment

## For cPanel

For cPanel, you would need to:

1. Upload this script
2. Run it via SSH if available
3. If SSH is not available, you can manually move files to _archive folders following the same pattern as this script