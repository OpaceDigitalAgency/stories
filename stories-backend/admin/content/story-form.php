tories-backend/admin/content/story-form.php</path>
<content lines="262-276">
            <?php 
            // Display remaining additional fields
            foreach ($additionalFields as $field): 
                // Skip fields that are already handled above
                if (in_array($field, ['featured', 'is_sponsored', 'is_published', 'published', 'published_at'])) continue;
                
                $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                $isIntField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'int') !== false || strpos($columnInfo[$field]['Type'], 'tinyint') !== false);
                $isDecimalField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'decimal') !== false || strpos($columnInfo[$field]['Type'], 'float') !== false || strpos($columnInfo[$field]['Type'], 'double') !== false);
                $isEnumField = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'enum') !== false;
                $isBooleanField = isset($columnInfo[$field]) && (
                    (strpos($columnInfo[$field]['Type'], 'tinyint(1)') !== false) || 
                    (strpos($field, 'is_') === 0) || 
                    (strpos($field, 'has_') === 0) || 
                    (strpos($field, 'needs_') === 0)
                );
