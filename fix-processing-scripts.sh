#!/bin/bash

# Find all processing scripts that have header and footer includes
PROCESSING_SCRIPTS=(
  "stories-backend/admin/content/save-story.php"
  "stories-backend/admin/content/delete-ai-tool.php"
  "stories-backend/admin/content/delete-tag.php"
  "stories-backend/admin/content/delete-directory-item.php"
  "stories-backend/admin/content/delete-game.php"
  "stories-backend/admin/content/delete-post.php"
  "stories-backend/admin/content/bulk-tags.php"
  "stories-backend/admin/content/bulk-subscribers.php"
  "stories-backend/admin/content/bulk-directory-items.php"
  "stories-backend/admin/content/bulk-media.php"
  "stories-backend/admin/content/bulk-authors.php"
  "stories-backend/admin/content/bulk-posts.php"
  "stories-backend/admin/content/bulk-ai-tools.php"
  "stories-backend/admin/content/bulk-games.php"
  "stories-backend/admin/content/bulk-contacts.php"
)

for script in "${PROCESSING_SCRIPTS[@]}"; do
  if [ -f "$script" ]; then
    echo "Processing $script..."
    
    # Remove header include
    sed -i '1,20s/\/\/ Include header/\/\/ Start session if not already started\nif (session_status() == PHP_SESSION_NONE) {\n    session_start();\n}\n\n\/\/ This is a processing script, no UI needed/' "$script"
    sed -i '1,20s/require_once ..\\/includes\\/header.php;//' "$script"
    
    # Remove page variables
    sed -i '1,20s/\/\/ Page variables/\/\/ No page variables needed for processing script/' "$script"
    sed -i '1,20s/$pageTitle = .*;//' "$script"
    sed -i '1,20s/$currentPage = .*;//' "$script"
    
    # Remove footer include
    sed -i 's/\/\/ Include footer/\/\/ No footer needed for processing script/' "$script"
    sed -i 's/require_once ..\\/includes\\/footer.php;//' "$script"
    
    echo "Done processing $script"
  else
    echo "File $script not found, skipping..."
  fi
done

echo "All processing scripts updated!"
