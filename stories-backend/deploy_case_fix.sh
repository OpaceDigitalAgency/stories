#!/bin/bash

# Deployment script for case sensitivity fix
# This script ensures proper deployment and execution of the fix

# Configuration
SCRIPT_NAME="fix_case_once_and_for_all.php"
API_DIR="/home/stories/api.storiesfromtheweb.org"
BACKUP_DIR="${API_DIR}/backups/$(date +%Y%m%d_%H%M%S)"

echo "Starting case sensitivity fix deployment..."

# 1. Create backup directory
mkdir -p "$BACKUP_DIR"
echo "Created backup directory: $BACKUP_DIR"

# 2. Backup current state
cp -r "${API_DIR}/api" "$BACKUP_DIR/"
echo "Backed up current API directory"

# 3. Pull latest changes
cd "$API_DIR"
git pull origin main
echo "Pulled latest changes from git"

# 4. Copy fix script to correct location
cp "stories-backend/${SCRIPT_NAME}" "${API_DIR}/${SCRIPT_NAME}"
chmod 755 "${API_DIR}/${SCRIPT_NAME}"
echo "Copied and set permissions for fix script"

# 5. Run the fix
cd "$API_DIR"
php "${SCRIPT_NAME}"
FIX_RESULT=$?

if [ $FIX_RESULT -eq 0 ]; then
    echo "✅ Fix completed successfully"
    
    # Clean up
    rm "${API_DIR}/${SCRIPT_NAME}"
    echo "Cleaned up fix script"
    
    # Test API endpoints
    echo "Testing API endpoints..."
    curl -s "https://api.storiesfromtheweb.org/api/v1/stories" > /dev/null
    if [ $? -eq 0 ]; then
        echo "✅ API endpoints responding correctly"
    else
        echo "❌ API endpoint test failed"
        echo "Restoring from backup..."
        rm -rf "${API_DIR}/api"
        cp -r "${BACKUP_DIR}/api" "${API_DIR}/"
        echo "Restored from backup"
        exit 1
    fi
else
    echo "❌ Fix failed"
    echo "Restoring from backup..."
    rm -rf "${API_DIR}/api"
    cp -r "${BACKUP_DIR}/api" "${API_DIR}/"
    echo "Restored from backup"
    exit 1
fi

echo "
Deployment completed successfully!

Next steps:
1. Check API endpoints at https://api.storiesfromtheweb.org/test_api_format.php
2. Review logs in ${API_DIR}/logs/api-error.log
3. Test frontend at https://storiesfromtheweb.netlify.app
"