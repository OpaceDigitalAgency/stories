#!/bin/bash

# Create the archive directory if it doesn't exist
mkdir -p stories-backend/_archive/scripts

# List of essential scripts to keep
essential_scripts=(
  "api_test_suite.php"
  "admin_diagnostic.php"
  "auth_diagnostic.php"
  "simple_auth.php"
  "check_auth_status.php"
  "database.sql"
  "create_admin_user.sql"
  "SCRIPT_INDEX.md"
  "README.md"
)

# Function to check if a file should be kept
should_keep() {
  local file=$1
  
  # Keep directories
  if [ -d "$file" ]; then
    return 0
  fi
  
  # Extract the filename without the path
  local filename=$(basename "$file")
  
  # Keep essential scripts
  for script in "${essential_scripts[@]}"; do
    if [ "$filename" == "$script" ]; then
      return 0
    fi
  done
  
  # Keep files in specific directories
  if [[ "$file" == *"/admin/"* || "$file" == *"/api/"* || "$file" == *"/docs/"* || "$file" == *"/_archive/"* ]]; then
    return 0
  fi
  
  # Archive PHP scripts, shell scripts, and markdown files
  if [[ "$filename" == *.php || "$filename" == *.sh || "$filename" == *.md || "$filename" == *.sql || "$filename" == *.txt || "$filename" == *.html ]]; then
    return 1
  fi
  
  # Keep other files
  return 0
}

# Move redundant scripts to archive
echo "Moving redundant scripts to archive..."
find stories-backend -maxdepth 1 -type f | while read file; do
  if ! should_keep "$file"; then
    echo "Moving $file to archive..."
    mv "$file" "stories-backend/_archive/scripts/"
  else
    echo "Keeping $file..."
  fi
done

echo "Script archiving complete!"

# List remaining files in stories-backend
echo ""
echo "Remaining files in stories-backend:"
ls -la stories-backend | grep -v "_archive" | grep -v "admin" | grep -v "api" | grep -v "docs"

# List archived scripts
echo ""
echo "Archived scripts:"
ls -la stories-backend/_archive/scripts/