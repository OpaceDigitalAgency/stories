#!/bin/bash

# Deploy Authentication Fix to Git
# This script helps deploy the simple authentication solution to Git

echo "Deploying Simple Authentication Fix to Git"
echo "=========================================="

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo "Error: Git is not installed. Please install Git first."
    exit 1
fi

# Check if we're in a git repository
if ! git rev-parse --is-inside-work-tree &> /dev/null; then
    echo "Error: Not in a Git repository. Please run this script from within your Git repository."
    exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo "Warning: You have uncommitted changes in your repository."
    read -p "Do you want to continue anyway? (y/n): " continue_anyway
    if [[ $continue_anyway != "y" && $continue_anyway != "Y" ]]; then
        echo "Deployment aborted."
        exit 1
    fi
fi

# Create a new branch for the auth fix
branch_name="auth-fix-$(date +%Y%m%d)"
echo "Creating new branch: $branch_name"
git checkout -b $branch_name

# Add the new files
echo "Adding new files to Git..."
git add stories-backend/simple_auth.php
git add stories-backend/setup_simple_auth.php
git add stories-backend/test_simple_auth.php
git add stories-backend/SIMPLE_AUTH_GUIDE.md
git add stories-backend/api/v1/Middleware/SimpleAuthMiddleware.php
git add stories-backend/api/v1/Endpoints/SimpleAuthController.php
git add stories-backend/api/v1/routes.php

# Commit the changes
echo "Committing changes..."
git commit -m "Implement simplified authentication system to fix database write issues"

# Push to remote repository
echo "Do you want to push these changes to the remote repository?"
read -p "Enter 'y' to push or any other key to skip: " push_changes

if [[ $push_changes == "y" || $push_changes == "Y" ]]; then
    echo "Pushing changes to remote repository..."
    git push -u origin $branch_name
    echo "Changes pushed successfully to branch: $branch_name"
    
    # Print instructions for creating a pull request
    echo ""
    echo "To create a pull request:"
    echo "1. Go to your repository on GitHub/GitLab/etc."
    echo "2. You should see a prompt to create a pull request for the new branch"
    echo "3. Click on 'Compare & pull request'"
    echo "4. Add a title and description for your pull request"
    echo "5. Click 'Create pull request'"
else
    echo "Changes committed locally but not pushed to remote repository."
    echo "To push later, use: git push -u origin $branch_name"
fi

echo ""
echo "Deployment complete!"
echo "Follow the instructions in SIMPLE_AUTH_GUIDE.md to implement the solution."