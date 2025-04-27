#!/bin/bash

# Create the backup files archive directory if it doesn't exist
mkdir -p stories-backend/_archive/backup_files

# Find all .bak, .orig, .old, and .tmp files and move them to the archive directory
echo "Finding and moving backup files to archive..."
find stories-backend -name "*.bak" -o -name "*.orig" -o -name "*.old" -o -name "*.tmp" | while read file; do
  # Create the directory structure in the archive
  rel_path=$(echo "$file" | sed 's|stories-backend/||')
  dir_path=$(dirname "$rel_path")
  mkdir -p "stories-backend/_archive/backup_files/$dir_path"
  
  # Move the file to the archive
  echo "Moving $file to archive..."
  mv "$file" "stories-backend/_archive/backup_files/$rel_path"
done

echo "Backup files archiving complete!"