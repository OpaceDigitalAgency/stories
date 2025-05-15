#!/bin/bash

# Script to force Dropbox to download files by updating their timestamps
echo "Starting to force Dropbox to download files..."

# Find all zero-byte files and update their timestamps
find . -type f -size 0 | while read file; do
    echo "Processing: $file"
    
    # Update the file's timestamp
    touch "$file"
    
    # Check if the file is still zero bytes after a short delay
    sleep 0.1
    if [ ! -s "$file" ]; then
        echo "  Still zero bytes: $file"
    else
        echo "  Successfully downloaded: $file"
    fi
done

echo "Completed processing files."
