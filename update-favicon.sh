#!/bin/bash

# Find all Astro files that contain a direct favicon reference
FILES=$(grep -l 'rel="icon".*href="/favicon.png"' --include="*.astro" -r src/)

# Update each file
for file in $FILES; do
  echo "Updating $file..."
  sed -i '' 's|<link rel="icon" type="image/png" href="/favicon.png" />|<link rel="icon" type="image/x-icon" href="/favicon.png">\n    <link rel="shortcut icon" type="image/x-icon" href="/favicon.png">|g' "$file"
done

echo "All files updated!"
