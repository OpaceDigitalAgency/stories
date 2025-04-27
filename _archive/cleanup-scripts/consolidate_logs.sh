#!/bin/bash

# Create a backup of the logs directory
echo "Creating backup of logs directory..."
mkdir -p stories-backend/_archive/logs_backup
cp -r stories-backend/logs/* stories-backend/_archive/logs_backup/

# Copy logs from the redundant directory to the main logs directory
echo "Copying logs from redundant directory to main logs directory..."
mkdir -p stories-backend/logs
cp -r stories-backend/stories-backend/logs/* stories-backend/logs/

# Archive the redundant directory
echo "Archiving redundant stories-backend/stories-backend directory..."
mkdir -p stories-backend/_archive/redundant_directories
mv stories-backend/stories-backend stories-backend/_archive/redundant_directories/

echo "Log consolidation complete!"