#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting deployment to cPanel...${NC}"

# Deploy direct login scripts
echo "Deploying direct login scripts..."
scp stories-backend/check_auth_status.php stories-backend/direct_login.php stories-backend/go_to_dashboard.php stories-backend/logout.php stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/

# Deploy admin directory
echo "Deploying admin directory..."
ssh stories@api.storiesfromtheweb.org "mkdir -p /home/stories/api.storiesfromtheweb.org/admin"
scp -r stories-backend/admin/* stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/admin/

# Deploy api directory
echo "Deploying api directory..."
ssh stories@api.storiesfromtheweb.org "mkdir -p /home/stories/api.storiesfromtheweb.org/api"
scp -r stories-backend/api/* stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/api/

# Deploy test_folder directory
echo "Deploying test_folder directory..."
ssh stories@api.storiesfromtheweb.org "mkdir -p /home/stories/api.storiesfromtheweb.org/test_folder"
scp -r stories-backend/test_folder/* stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/test_folder/

# Deploy other files
echo "Deploying other files..."
scp stories-backend/.htaccess stories-backend/database.sql stories-backend/README.md stories@api.storiesfromtheweb.org:/home/stories/api.storiesfromtheweb.org/

echo -e "${GREEN}Deployment completed successfully!${NC}"