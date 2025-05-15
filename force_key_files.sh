#!/bin/bash

# Script to force Dropbox to download key files in stories-backend and Astro files
echo "Starting to force Dropbox to download key files..."

# Process stories-backend folder
echo "Processing stories-backend folder..."
find ./stories-backend -type f -size 0 -name "*.php" -o -name "*.js" -o -name "*.html" -o -name "*.css" | while read file; do
    echo "Touching: $file"
    touch "$file"
    # Try to read the file to force download
    cat "$file" > /dev/null 2>&1
done

# Process main Astro files
echo "Processing main Astro files..."
find ./src -type f -size 0 -name "*.astro" -o -name "*.ts" -o -name "*.js" | while read file; do
    echo "Touching: $file"
    touch "$file"
    # Try to read the file to force download
    cat "$file" > /dev/null 2>&1
done

echo "Completed processing key files."
