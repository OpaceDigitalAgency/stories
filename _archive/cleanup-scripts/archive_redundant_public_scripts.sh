#!/bin/bash

# Create archive directory if it doesn't exist
mkdir -p stories-backend/_archive/public_scripts

# List of essential public scripts to keep
essential_scripts=(
  "diagnose.php"
  "check_database.php"
  "verify_db_connection.php"
  "verify_api.php"
  "verify_all_connections.php"
  "verify_structure.php"
  "setup_database.php"
  "reset_database.php"
  "clean_database.php"
  "test_api.php"
)

# Function to check if a file should be kept
should_keep() {
  local filename=$1
  
  # Keep essential scripts
  for script in "${essential_scripts[@]}"; do
    if [ "$filename" == "$script" ]; then
      return 0
    fi
  done
  
  # Archive other scripts
  return 1
}

# Move redundant scripts to archive
echo "Moving redundant public scripts to archive..."
for file in stories-backend/public/*.php; do
  filename=$(basename "$file")
  if ! should_keep "$filename"; then
    echo "Moving $file to archive..."
    mv "$file" "stories-backend/_archive/public_scripts/"
  else
    echo "Keeping $file..."
  fi
done

echo "Public script archiving complete!"

# List remaining files in public directory
echo ""
echo "Remaining files in public directory:"
ls -la stories-backend/public/