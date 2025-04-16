#!/bin/bash
# Deployment script for Stories from the Web
# This script helps keep local files, GitHub, and cPanel server in sync

# Configuration
CPANEL_HOST="api.storiesfromtheweb.org"
CPANEL_USER="stories"  # Replace with your cPanel username
CPANEL_PATH="/home/stories/api.storiesfromtheweb.org"
LOCAL_BACKEND_PATH="./stories-backend"
REMOTE_TEMP_DIR="/tmp/stories_sync"

# Text colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Stories from the Web Deployment Script ===${NC}"
echo "This script will sync your local repository with the cPanel server and GitHub."

# Step 1: Download any new files from cPanel that were added directly
echo -e "\n${YELLOW}Step 1: Downloading files from cPanel server...${NC}"

# Create a list of files on the server
echo "Creating file list on server..."
ssh $CPANEL_USER@$CPANEL_HOST "mkdir -p $REMOTE_TEMP_DIR && find $CPANEL_PATH -type f -name '*.php' -o -name '*.js' -o -name '*.css' -o -name '*.html' -o -name '.htaccess' | sort > $REMOTE_TEMP_DIR/server_files.txt"

# Create a list of local files
echo "Creating local file list..."
find $LOCAL_BACKEND_PATH -type f -name '*.php' -o -name '*.js' -o -name '*.css' -o -name '*.html' -o -name '.htaccess' | sed "s|$LOCAL_BACKEND_PATH|$CPANEL_PATH|g" | sort > /tmp/local_files.txt

# Download the server file list
echo "Downloading server file list..."
scp $CPANEL_USER@$CPANEL_HOST:$REMOTE_TEMP_DIR/server_files.txt /tmp/server_files.txt

# Find files that exist on the server but not locally
echo "Finding files that exist on server but not locally..."
comm -23 /tmp/server_files.txt /tmp/local_files.txt > /tmp/files_to_download.txt

# Download missing files
if [ -s /tmp/files_to_download.txt ]; then
    echo "Downloading missing files from server..."
    while IFS= read -r file; do
        local_file=$(echo "$file" | sed "s|$CPANEL_PATH|$LOCAL_BACKEND_PATH|g")
        local_dir=$(dirname "$local_file")
        mkdir -p "$local_dir"
        echo "Downloading: $file -> $local_file"
        scp "$CPANEL_USER@$CPANEL_HOST:$file" "$local_file"
    done < /tmp/files_to_download.txt
    echo -e "${GREEN}Downloaded all missing files from server.${NC}"
else
    echo -e "${GREEN}No new files to download from server.${NC}"
fi

# Step 2: Check for direct login scripts
echo -e "\n${YELLOW}Step 2: Checking for direct login scripts...${NC}"

# List of direct login scripts to check
DIRECT_LOGIN_SCRIPTS=(
    "direct_login.php"
    "auth_test.php"
    "check_auth_status.php"
    "go_to_dashboard.php"
    "simple_login_form.php"
    "logout.php"
)

# Check each script
for script in "${DIRECT_LOGIN_SCRIPTS[@]}"; do
    remote_path="$CPANEL_PATH/$script"
    local_path="$LOCAL_BACKEND_PATH/$script"
    
    # Check if script exists on server
    ssh $CPANEL_USER@$CPANEL_HOST "test -f $remote_path" && {
        echo "Found direct login script on server: $script"
        
        # Check if it exists locally
        if [ ! -f "$local_path" ]; then
            echo "Downloading direct login script: $script"
            scp "$CPANEL_USER@$CPANEL_HOST:$remote_path" "$local_path"
        else
            # Compare files
            scp "$CPANEL_USER@$CPANEL_HOST:$remote_path" "/tmp/$script"
            if ! cmp -s "/tmp/$script" "$local_path"; then
                echo "Updating direct login script: $script"
                cp "/tmp/$script" "$local_path"
            fi
        fi
    }
done

# Step 3: Commit and push changes to GitHub
echo -e "\n${YELLOW}Step 3: Committing and pushing changes to GitHub...${NC}"

# Check if there are any changes
if [ -n "$(git status --porcelain)" ]; then
    echo "Changes detected. Adding files to git..."
    git add .
    
    echo "Committing changes..."
    git commit -m "Sync with cPanel server: Added direct login scripts and other server changes"
    
    echo "Pushing to GitHub..."
    git push origin main
    
    echo -e "${GREEN}Changes committed and pushed to GitHub.${NC}"
else
    echo -e "${GREEN}No changes to commit.${NC}"
fi

# Step 4: Upload any local changes to cPanel
echo -e "\n${YELLOW}Step 4: Uploading local changes to cPanel...${NC}"

# Create a list of files that have changed since the last commit
git diff --name-only HEAD~1 HEAD | grep "stories-backend/" > /tmp/changed_files.txt

if [ -s /tmp/changed_files.txt ]; then
    echo "Uploading changed files to server..."
    while IFS= read -r file; do
        if [ -f "$file" ]; then
            remote_file=$(echo "$file" | sed "s|stories-backend|$CPANEL_PATH|g")
            remote_dir=$(dirname "$remote_file")
            echo "Uploading: $file -> $remote_file"
            ssh $CPANEL_USER@$CPANEL_HOST "mkdir -p $remote_dir"
            scp "$file" "$CPANEL_USER@$CPANEL_HOST:$remote_file"
        fi
    done < /tmp/changed_files.txt
    echo -e "${GREEN}Uploaded all changed files to server.${NC}"
else
    echo -e "${GREEN}No files to upload to server.${NC}"
fi

# Step 5: Clean up
echo -e "\n${YELLOW}Step 5: Cleaning up...${NC}"
rm -f /tmp/server_files.txt /tmp/local_files.txt /tmp/files_to_download.txt /tmp/changed_files.txt
ssh $CPANEL_USER@$CPANEL_HOST "rm -rf $REMOTE_TEMP_DIR"

echo -e "\n${GREEN}=== Deployment Complete ===${NC}"
echo "Your local repository, GitHub, and cPanel server are now in sync."
echo "Netlify will automatically deploy the frontend changes from GitHub."