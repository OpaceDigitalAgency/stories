#!/bin/bash

# Create the archive directory if it doesn't exist
mkdir -p stories-backend/docs/_archive

# List of documentation files to archive
docs=(
  "DEPLOYMENT.md"
  "FTP_DEPLOYMENT.md"
  "GIT_DEPLOYMENT.md"
  "GITHUB_DEPLOY.md"
  "API_CONNECTIVITY_FIX.md"
  "fix-plan.md"
  "fix-styling-plan.md"
  "test_push.md"
  "test2.md"
  "test3.md"
)

# Move each documentation file to the archive directory
for doc in "${docs[@]}"; do
  if [ -f "stories-backend/docs/$doc" ]; then
    echo "Moving $doc to archive..."
    mv "stories-backend/docs/$doc" "stories-backend/docs/_archive/"
  elif [ -f "stories-backend/$doc" ]; then
    echo "Moving $doc from stories-backend to archive..."
    mv "stories-backend/$doc" "stories-backend/docs/_archive/"
  else
    echo "Documentation file $doc not found, skipping..."
  fi
done

# Check for system-documentation.html in various locations
if [ -f "stories-backend/docs/system-documentation.html" ]; then
  echo "Moving system-documentation.html to archive..."
  mv "stories-backend/docs/system-documentation.html" "stories-backend/docs/_archive/"
elif [ -f "stories-backend/system-documentation.html" ]; then
  echo "Moving system-documentation.html from stories-backend to archive..."
  mv "stories-backend/system-documentation.html" "stories-backend/docs/_archive/"
else
  echo "system-documentation.html not found, skipping..."
fi

echo "Documentation archiving complete!"