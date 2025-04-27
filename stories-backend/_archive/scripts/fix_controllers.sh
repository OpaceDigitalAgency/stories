#!/bin/bash

# Fix the TagsController.php file
echo "Fixing TagsController.php..."

# Create a backup
cp stories-backend/api/v1/endpoints/TagsController.php stories-backend/api/v1/endpoints/TagsController.php.bak2

# Replace the broken index() method
sed -i '' '60,73s/            \/\/ Format tags with a simplified structure to avoid JSON encoding issues.*]/            \/\/ Format tags with a simplified structure to avoid JSON encoding issues\n            $formattedTags = [];\n            \n            foreach ($tags as $tag) {\n                $formattedTags[] = [\n                    '\''id'\'' => $tag['\''id'\''],\n                    '\''attributes'\'' => [\n                        '\''name'\'' => $tag['\''name'\''],\n                        '\''slug'\'' => $tag['\''slug'\''],\n                        '\''storyCount'\'' => (int)$tag['\''storyCount'\'']\n                    ]\n                ];/g' stories-backend/api/v1/endpoints/TagsController.php

# Fix the show() method
sed -i '' '183,189s/            \/\/ Build the formatted tag with simplified structure.*stories => $simpleStories/            \/\/ Build the formatted tag with proper structure\n            $formattedTag = [\n                '\''id'\'' => $tagId,\n                '\''attributes'\'' => [\n                    '\''name'\'' => $tag['\''name'\''],\n                    '\''slug'\'' => $tag['\''slug'\''],\n                    '\''storyCount'\'' => $storyCount,\n                    '\''stories'\'' => $simpleStories/g' stories-backend/api/v1/endpoints/TagsController.php

# Fix the Response::sendSuccess line
sed -i '' '192s/            Response::sendSuccess(\['\''data'\'' => \$formattedTag\]);/            Response::sendSuccess($formattedTag);/g' stories-backend/api/v1/endpoints/TagsController.php

# Fix the create() method
sed -i '' '240,245s/            \/\/ Return the created tag with simplified structure.*storyCount'\'' => 0/            \/\/ Return the created tag with proper structure\n            $formattedTag = [\n                '\''id'\'' => $tagId,\n                '\''attributes'\'' => [\n                    '\''name'\'' => $name,\n                    '\''slug'\'' => $slug,\n                    '\''storyCount'\'' => 0/g' stories-backend/api/v1/endpoints/TagsController.php

# Fix the Response::sendSuccess line in create()
sed -i '' '247s/            Response::sendSuccess(\['\''data'\'' => \$formattedTag\], \[\], 201);/            Response::sendSuccess($formattedTag, [], 201);/g' stories-backend/api/v1/endpoints/TagsController.php

# Fix the update() method
sed -i '' '350,355s/            $formattedTag = \[.*storyCount'\'' => \$this->getStoryCount(\$tagId)/            $formattedTag = [\n                '\''id'\'' => $tagId,\n                '\''attributes'\'' => [\n                    '\''name'\'' => $updatedTag['\''name'\''],\n                    '\''slug'\'' => $updatedTag['\''slug'\''],\n                    '\''storyCount'\'' => $this->getStoryCount($tagId)/g' stories-backend/api/v1/endpoints/TagsController.php

# Fix the Response::sendSuccess line in update()
sed -i '' '357s/            Response::sendSuccess(\['\''data'\'' => \$formattedTag\]);/            Response::sendSuccess($formattedTag);/g' stories-backend/api/v1/endpoints/TagsController.php

echo "Fixed TagsController.php"

# Fix the AuthorsController.php file
echo "Fixing AuthorsController.php..."

# Create a backup
cp stories-backend/api/v1/endpoints/AuthorsController.php stories-backend/api/v1/endpoints/AuthorsController.php.bak2

# Fix the Response::sendSuccess lines
sed -i '' 's/Response::sendSuccess(\['\''data'\'' => \$formatted/Response::sendSuccess(\$formatted/g' stories-backend/api/v1/endpoints/AuthorsController.php

echo "Fixed AuthorsController.php"

# Fix the BlogPostsController.php file
echo "Fixing BlogPostsController.php..."

# Create a backup
cp stories-backend/api/v1/endpoints/BlogPostsController.php stories-backend/api/v1/endpoints/BlogPostsController.php.bak2

# Fix the Response::sendSuccess lines
sed -i '' 's/Response::sendSuccess(\['\''data'\'' => \$formatted/Response::sendSuccess(\$formatted/g' stories-backend/api/v1/endpoints/BlogPostsController.php

echo "Fixed BlogPostsController.php"

echo "All controllers fixed"
