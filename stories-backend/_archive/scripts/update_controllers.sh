#!/bin/bash

# Define the controllers to update
CONTROLLERS=(
    "stories-backend/api/v1/endpoints/TagsController.php"
    "stories-backend/api/v1/endpoints/AuthorsController.php"
    "stories-backend/api/v1/endpoints/BlogPostsController.php"
)

# Function to update a controller
update_controller() {
    local controller=$1
    local controller_name=$(basename "$controller" .php)
    local entity_name=${controller_name%Controller}
    local entity_name_lower=$(echo "$entity_name" | tr '[:upper:]' '[:lower:]')
    
    echo "Updating $controller_name..."
    
    # Check if the file exists
    if [ ! -f "$controller" ]; then
        echo "Error: $controller not found"
        return 1
    fi
    
    # Create a backup
    cp "$controller" "${controller}.bak"
    
    # Fix the show method response format
    # This is a simple sed replacement that might not work for all cases
    # but it should work for the basic structure we're seeing in the controllers
    
    # Pattern 1: Response::sendSuccess(['data' => $formatted...]);
    sed -i '' 's/Response::sendSuccess(\[\s*'\''data'\''\s*=>\s*\$formatted[^)]*)/Response::sendSuccess(\$formatted)/g' "$controller"
    
    # Pattern 2: $formattedTag = [ 'id' => $tagId, 'name' => ... ];
    # Replace with: $formattedTag = [ 'id' => $tagId, 'attributes' => [ 'name' => ... ] ];
    perl -i -pe '
        if (/\$formatted'$entity_name'\s*=\s*\[/) {
            $in_formatted = 1;
            s/(\$formatted'$entity_name'\s*=\s*\[)/\1\n    "id" => \$'$entity_name_lower'Id,\n    "attributes" => [/;
            next;
        }
        if ($in_formatted && /^\s*\];/) {
            $in_formatted = 0;
            s/^\s*\];/    ]\n];/;
            next;
        }
        if ($in_formatted && /^\s*'\''id'\''\s*=>\s*/) {
            # Skip the ID line as we already added it
            s/^.*$//;
            next;
        }
    ' "$controller"
    
    echo "Updated $controller_name"
}

# Update each controller
for controller in "${CONTROLLERS[@]}"; do
    update_controller "$controller"
done

echo "All controllers updated"
