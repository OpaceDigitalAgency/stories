<?php
/**
 * Update Response.php Script
 * 
 * This script updates the Response.php file to add the formatData method
 * that ensures all API responses have the correct structure with an 'attributes' key.
 */

// Define the path to the Response.php file
$responsePath = __DIR__ . '/api/v1/utils/Response.php';

// Check if the file exists
if (!file_exists($responsePath)) {
    echo "Error: Response.php file not found at $responsePath\n";
    exit(1);
}

// Read the current content of the file
$content = file_get_contents($responsePath);
if ($content === false) {
    echo "Error: Failed to read Response.php file\n";
    exit(1);
}

// Define the formatData method to add
$formatDataMethod = '
    /**
     * Format data to ensure it has the correct structure with attributes
     * 
     * @param array $data The data to format
     * @return array The formatted data
     */
    private static function formatData($data) {
        // If data is already in the correct format, return it as is
        if (isset($data[\'id\']) && isset($data[\'attributes\'])) {
            return $data;
        }
        
        // If data is an array of items, format each item
        if (is_array($data) && !isset($data[\'id\']) && !empty($data)) {
            $formattedData = [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item[\'id\'])) {
                    // Format each item
                    $attributes = [];
                    foreach ($item as $key => $value) {
                        if ($key !== \'id\') {
                            $attributes[$key] = $value;
                        }
                    }
                    
                    $formattedData[] = [
                        \'id\' => $item[\'id\'],
                        \'attributes\' => $attributes
                    ];
                } else {
                    // If item doesn\'t have an ID, keep it as is
                    $formattedData[] = $item;
                }
            }
            return $formattedData;
        }
        
        // Format a single item
        $id = $data[\'id\'] ?? null;
        if ($id === null) {
            // If no ID, return data as is
            return $data;
        }
        
        // Create attributes array
        $attributes = [];
        foreach ($data as $key => $value) {
            if ($key !== \'id\') {
                $attributes[$key] = $value;
            }
        }
        
        // Return formatted data
        return [
            \'id\' => $id,
            \'attributes\' => $attributes
        ];
    }';

// Check if the formatData method already exists
if (strpos($content, 'private static function formatData') !== false) {
    echo "The formatData method already exists in Response.php\n";
    exit(0);
}

// Find the position to insert the formatData method (before the sendSuccess method)
$sendSuccessPos = strpos($content, 'public static function sendSuccess');
if ($sendSuccessPos === false) {
    echo "Error: Could not find the sendSuccess method in Response.php\n";
    exit(1);
}

// Find the beginning of the sendSuccess method
$methodStartPos = strrpos(substr($content, 0, $sendSuccessPos), '/**');
if ($methodStartPos === false) {
    echo "Error: Could not find the beginning of the sendSuccess method in Response.php\n";
    exit(1);
}

// Insert the formatData method before the sendSuccess method
$newContent = substr($content, 0, $methodStartPos) . $formatDataMethod . "\n" . substr($content, $methodStartPos);

// Update the sendSuccess method to use formatData
$newContent = str_replace(
    "self::json(['data' => \$data, 'meta' => \$meta]);",
    "self::json(['data' => self::formatData(\$data), 'meta' => \$meta]);",
    $newContent
);
$newContent = str_replace(
    "self::json(self::success(\$data, \$meta, \$statusCode));",
    "self::json(self::success(self::formatData(\$data), \$meta, \$statusCode));",
    $newContent
);

// Update the sendPaginated method to use formatData
$newContent = str_replace(
    "self::json(self::paginated(\$data, \$page, \$pageSize, \$total, \$additionalMeta, \$statusCode));",
    "self::json(self::paginated(self::formatData(\$data), \$page, \$pageSize, \$total, \$additionalMeta, \$statusCode));",
    $newContent
);

// Write the updated content back to the file
if (file_put_contents($responsePath, $newContent) === false) {
    echo "Error: Failed to write updated content to Response.php\n";
    exit(1);
}

echo "Successfully updated Response.php with formatData method\n";
exit(0);
