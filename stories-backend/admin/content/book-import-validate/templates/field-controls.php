<?php
/**
 * Field Controls Template
 * 
 * This template provides controls for individual field editing.
 */

// Ensure $field, $currentValue, and $sourceValues are available
if (!isset($field) || !isset($currentValue) || !isset($sourceValues)) {
    echo '<div class="alert alert-danger">Error: Required data not available</div>';
    return;
}

// Define field types and validation rules
$fieldTypes = [
    'title' => ['type' => 'text', 'required' => true, 'maxlength' => 255],
    'author' => ['type' => 'text', 'required' => true, 'maxlength' => 255],
    'isbn' => ['type' => 'text', 'pattern' => '[0-9X]{10}', 'maxlength' => 10],
    'isbn13' => ['type' => 'text', 'pattern' => '[0-9]{13}', 'maxlength' => 13],
    'publisher' => ['type' => 'text', 'maxlength' => 255],
    'publication_date' => ['type' => 'date'],
    'page_count' => ['type' => 'number', 'min' => 1, 'max' => 10000],
    'language' => ['type' => 'text', 'maxlength' => 50],
    'format' => ['type' => 'text', 'maxlength' => 50],
    'series' => ['type' => 'text', 'maxlength' => 255],
    'awards' => ['type' => 'textarea', 'rows' => 3],
    'characters' => ['type' => 'textarea', 'rows' => 3],
    'settings' => ['type' => 'textarea', 'rows' => 3],
    'preview_link' => ['type' => 'url', 'maxlength' => 255],
    'cover_url' => ['type' => 'url', 'maxlength' => 255],
    'rating' => ['type' => 'number', 'min' => 0, 'max' => 5, 'step' => 0.01],
    'rating_count' => ['type' => 'number', 'min' => 0],
    'review_count' => ['type' => 'number', 'min' => 0],
    'maturity_rating' => ['type' => 'text', 'maxlength' => 50]
];

// Get field configuration
$fieldConfig = $fieldTypes[$field] ?? ['type' => 'text'];

// Format current value for input
function formatValueForInput($field, $value, $type) {
    if (empty($value)) {
        return '';
    }
    
    switch ($field) {
        case 'publication_date':
            return date('Y-m-d', strtotime($value));
        
        case 'awards':
        case 'characters':
        case 'settings':
            // Handle array or JSON
            if (is_array($value)) {
                return implode(', ', $value);
            } elseif (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
                try {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        return implode(', ', $decoded);
                    }
                } catch (Exception $e) {
                    // Fall through to default
                }
            }
            return $value;
            
        default:
            return $value;
    }
}

// Format value for display
$formattedValue = formatValueForInput($field, $currentValue, $fieldConfig['type']);
?>

<div class="field-editor" id="field-editor-<?php echo htmlspecialchars($field); ?>">
    <div class="card">
        <div class="card-header">
            <h5>Edit <?php echo htmlspecialchars(ucfirst($field)); ?></h5>
        </div>
        <div class="card-body">
            <form id="field-edit-form" class="field-edit-form">
                <div class="mb-3">
                    <label for="current-value" class="form-label">Current Value</label>
                    <?php if ($fieldConfig['type'] === 'textarea'): ?>
                        <textarea id="current-value" name="current_value" class="form-control"
                                 rows="<?php echo $fieldConfig['rows'] ?? 3; ?>"
                                 <?php echo isset($fieldConfig['required']) && $fieldConfig['required'] ? 'required' : ''; ?>
                                 <?php echo isset($fieldConfig['maxlength']) ? 'maxlength="' . $fieldConfig['maxlength'] . '"' : ''; ?>
                        ><?php echo htmlspecialchars($formattedValue); ?></textarea>
                    <?php else: ?>
                        <input type="<?php echo htmlspecialchars($fieldConfig['type']); ?>" 
                               id="current-value" 
                               name="current_value" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($formattedValue); ?>"
                               <?php echo isset($fieldConfig['required']) && $fieldConfig['required'] ? 'required' : ''; ?>
                               <?php echo isset($fieldConfig['pattern']) ? 'pattern="' . $fieldConfig['pattern'] . '"' : ''; ?>
                               <?php echo isset($fieldConfig['min']) ? 'min="' . $fieldConfig['min'] . '"' : ''; ?>
                               <?php echo isset($fieldConfig['max']) ? 'max="' . $fieldConfig['max'] . '"' : ''; ?>
                               <?php echo isset($fieldConfig['step']) ? 'step="' . $fieldConfig['step'] . '"' : ''; ?>
                               <?php echo isset($fieldConfig['maxlength']) ? 'maxlength="' . $fieldConfig['maxlength'] . '"' : ''; ?>
                        >
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($sourceValues)): ?>
                    <div class="mb-3">
                        <label class="form-label">Available Values from Sources</label>
                        <div class="source-values-list">
                            <?php foreach ($sourceValues as $source => $value): ?>
                                <?php if (!empty($value)): ?>
                                    <div class="source-value-item mb-2">
                                        <div class="d-flex align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-2 use-source-value"
                                                    data-value="<?php echo htmlspecialchars($value); ?>"
                                                    data-source="<?php echo htmlspecialchars($source); ?>">
                                                <i class="fas fa-check"></i> Use
                                            </button>
                                            <span class="source-label"><?php echo ucfirst(htmlspecialchars($source)); ?>:</span>
                                            <span class="source-value ms-2"><?php echo htmlspecialchars($value); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary cancel-edit">Cancel</button>
                    <button type="submit" class="btn btn-primary save-field">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
