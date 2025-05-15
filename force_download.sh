#!/bin/bash

# Script to force Dropbox to download files
echo "Starting to force download of files..."

# Find all zero-byte files and try to access them
find . -type f -size 0 | while read file; do
    echo "Processing: $file"
    
    # Try to open the file and write it back to itself
    # This should force Dropbox to download the actual content
    if [ -f "$file" ]; then
        # Create a temporary file
        temp_file=$(mktemp)
        
        # Try to read the file (this might trigger Dropbox to download it)
        cat "$file" > "$temp_file" 2>/dev/null
        
        # Write the content back to the original file
        cat "$temp_file" > "$file" 2>/dev/null
        
        # Remove the temporary file
        rm "$temp_file"
        
        # Check if the file is still zero bytes
        if [ ! -s "$file" ]; then
            echo "  Still zero bytes: $file"
        else
            echo "  Successfully downloaded: $file"
        fi
    fi
done

echo "Completed processing files."
