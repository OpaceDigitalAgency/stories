#!/bin/bash

# Create directories for archived versions if they don't exist
mkdir -p _archive/old-versions

echo "Archiving redundant versions of the application..."

# Archive the static-version directory
echo "Archiving static-version directory..."
mv static-version _archive/old-versions/

# Archive the storiesfromtheweb directory
echo "Archiving storiesfromtheweb directory..."
mv storiesfromtheweb _archive/old-versions/

# Create a README file explaining the archived versions
cat > _archive/old-versions/README.md << 'EOF'
# Archived Versions

This directory contains older or alternative versions of the Stories from the Web platform that have been archived for reference.

## Directory Structure

- **static-version/**: An earlier static version of the frontend
- **storiesfromtheweb/**: An alternative deployment of the frontend

These versions have been archived as part of the cleanup process, as the main application is now maintained in the root directory's `src/` folder.

## Archive Date

These files were archived on April 27, 2025 as part of the comprehensive cleanup and documentation initiative.
EOF

echo "Redundant versions archived successfully!"

# List remaining directories in root
echo ""
echo "Remaining directories in root:"
ls -la | grep "^d" | grep -v "node_modules" | grep -v "\.git"