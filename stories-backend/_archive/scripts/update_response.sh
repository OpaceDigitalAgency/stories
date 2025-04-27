#!/bin/bash

# Define the path to the Response.php file
RESPONSE_FILE="stories-backend/api/v1/utils/Response.php"

# Check if the file exists
if [ ! -f "$RESPONSE_FILE" ]; then
    echo "Error: Response.php file not found at $RESPONSE_FILE"
    exit 1
fi

# Check if the formatData method already exists
if grep -q "private static function formatData" "$RESPONSE_FILE"; then
    echo "The formatData method already exists in Response.php"
    exit 0
fi

# Create a temporary file with the formatData method
cat > /tmp/formatData.txt << 'EOT'
    /**
     * Format data to ensure it has the correct structure with attributes
     * 
     * @param array $data The data to format
     * @return array The formatted data
     */
    private static function formatData($data) {
        // If data is already in the correct format, return it as is
        if (isset($data['id']) && isset($data['attributes'])) {
            return $data;
        }
        
        // If data is an array of items, format each item
        if (is_array($data) && !isset($data['id']) && !empty($data)) {
            $formattedData = [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item['id'])) {
                    // Format each item
                    $attributes = [];
                    foreach ($item as $key => $value) {
                        if ($key !== 'id') {
                            $attributes[$key] = $value;
                        }
                    }
                    
                    $formattedData[] = [
                        'id' => $item['id'],
                        'attributes' => $attributes
                    ];
                } else {
                    // If item doesn't have an ID, keep it as is
                    $formattedData[] = $item;
                }
            }
            return $formattedData;
        }
        
        // Format a single item
        $id = $data['id'] ?? null;
        if ($id === null) {
            // If no ID, return data as is
            return $data;
        }
        
        // Create attributes array
        $attributes = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $attributes[$key] = $value;
            }
        }
        
        // Return formatted data
        return [
            'id' => $id,
            'attributes' => $attributes
        ];
    }
EOT

# Find the position to insert the formatData method (before the sendSuccess method)
LINE_NUM=$(grep -n "public static function sendSuccess" "$RESPONSE_FILE" | cut -d: -f1)
if [ -z "$LINE_NUM" ]; then
    echo "Error: Could not find the sendSuccess method in Response.php"
    exit 1
fi

# Find the beginning of the sendSuccess method
DOC_START=$(grep -n "/\*\*" "$RESPONSE_FILE" | awk -v line="$LINE_NUM" '$1 < line' | tail -1 | cut -d: -f1)
if [ -z "$DOC_START" ]; then
    echo "Error: Could not find the beginning of the sendSuccess method in Response.php"
    exit 1
fi

# Insert the formatData method before the sendSuccess method
sed -i.bak "${DOC_START}r /tmp/formatData.txt" "$RESPONSE_FILE"

# Update the sendSuccess method to use formatData
sed -i.bak 's/self::json(\['\''data'\'' => $data, '\''meta'\'' => $meta\]);/self::json(\['\''data'\'' => self::formatData($data), '\''meta'\'' => $meta\]);/g' "$RESPONSE_FILE"
sed -i.bak 's/self::json(self::success($data, $meta, $statusCode));/self::json(self::success(self::formatData($data), $meta, $statusCode));/g' "$RESPONSE_FILE"

# Update the sendPaginated method to use formatData
sed -i.bak 's/self::json(self::paginated($data, $page, $pageSize, $total, $additionalMeta, $statusCode));/self::json(self::paginated(self::formatData($data), $page, $pageSize, $total, $additionalMeta, $statusCode));/g' "$RESPONSE_FILE"

# Clean up
rm -f "$RESPONSE_FILE.bak"
rm -f /tmp/formatData.txt

echo "Successfully updated Response.php with formatData method"
exit 0
