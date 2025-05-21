<?php
/**
 * Source Comparison Table Template
 *
 * This template displays a comparison table of book data from multiple sources.
 */

// Ensure $book, $sourceData, and $sources are available
if (!isset($book) || !isset($sourceData) || !isset($sources)) {
    echo '<div class="alert alert-danger">Error: Required data not available</div>';
    return;
}

// Define fields to display in the comparison table
$fields = [
    'title' => 'Title',
    'author' => 'Author',
    'isbn' => 'ISBN-10',
    'isbn13' => 'ISBN-13',
    'publisher' => 'Publisher',
    'publication_date' => 'Pub Date',
    'page_count' => 'Pages',
    'language' => 'Language',
    'format' => 'Format',
    'series' => 'Series',
    'awards' => 'Awards',
    'characters' => 'Characters',
    'settings' => 'Settings',
    'preview_link' => 'Preview Link',
    'cover_url' => 'Cover Image',
    'rating' => 'Rating',
    'rating_count' => 'Rating Count',
    'review_count' => 'Review Count',
    'maturity_rating' => 'Maturity Rating'
];

// Helper function to determine field status
function getFieldStatus($currentValue, $sourceValue) {
    if (empty($sourceValue)) {
        return 'empty';
    }

    if (empty($currentValue)) {
        return 'new';
    }

    // For numeric values, allow small differences
    if (is_numeric($currentValue) && is_numeric($sourceValue)) {
        if (abs((float)$currentValue - (float)$sourceValue) < 0.01) {
            return 'match';
        }
    }

    // For dates, normalize format
    if (strtotime($currentValue) && strtotime($sourceValue)) {
        if (date('Y-m-d', strtotime($currentValue)) === date('Y-m-d', strtotime($sourceValue))) {
            return 'match';
        }
    }

    // For strings, case-insensitive comparison
    if (strtolower(trim($currentValue)) === strtolower(trim($sourceValue))) {
        return 'match';
    }

    return 'new';
}

// Helper function to format field value for display
function formatFieldValue($field, $value) {
    if (empty($value)) {
        return '<span class="text-muted">Not available</span>';
    }

    switch ($field) {
        case 'publication_date':
            if (is_string($value) && strtotime($value)) {
                return date('Y-m-d', strtotime($value));
            }
            return htmlspecialchars($value);

        case 'cover_url':
            return '<img src="' . htmlspecialchars($value) . '" alt="Cover" class="img-thumbnail" style="max-height: 100px;">';

        case 'preview_link':
            return '<a href="' . htmlspecialchars($value) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i> View</a>';

        case 'rating':
            return number_format((float)$value, 2) . '/5';

        case 'rating_count':
        case 'review_count':
            return number_format((int)$value);

        case 'awards':
        case 'characters':
        case 'settings':
        case 'genres':
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
            return htmlspecialchars($value);

        case 'series':
            // Handle series which might be an array or a string
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
            // Clean up series format like "The Worst Witch (#1)"
            if (is_string($value) && preg_match('/^(.*?)\s*\(#\d+\)$/', $value, $matches)) {
                return htmlspecialchars($matches[1]);
            }
            return htmlspecialchars($value);

        default:
            return htmlspecialchars($value);
    }
}

// Helper function to render apply button
function renderApplyButton($field, $status, $source) {
    global $statusIcons;

    $icon = $statusIcons[$status]['icon'];
    $btnClass = 'btn-outline-' . $statusIcons[$status]['class'];
    $disabled = ($status === 'match' || $status === 'empty') ? 'disabled' : '';

    return '<button type="button" class="btn btn-sm apply-button ' . $btnClass . ' ' . $disabled . '" ' .
           'data-field="' . htmlspecialchars($field) . '" ' .
           'data-source="' . htmlspecialchars($source) . '" ' .
           'data-status="' . htmlspecialchars($status) . '">' .
           '<i class="fas fa-' . $icon . '"></i> Apply</button>';
}
?>

<div class="table-responsive">
    <table class="table table-bordered comparison-table">
        <thead class="table-light">
            <tr>
                <th>Field</th>
                <th>Current Value</th>
                <?php foreach ($sources as $source): ?>
                    <?php if (!empty($sourceData[$source])): ?>
                        <th><?php echo ucfirst(htmlspecialchars($source)); ?></th>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fields as $field => $label): ?>
                <tr class="field-row" data-field="<?php echo htmlspecialchars($field); ?>">
                    <td class="field-name"><?php echo htmlspecialchars($label); ?></td>
                    <td class="current-value">
                        <?php echo formatFieldValue($field, $book[$field] ?? null); ?>
                    </td>

                    <?php foreach ($sources as $source): ?>
                        <?php if (!empty($sourceData[$source])): ?>
                            <?php if ($sourceData[$source]['status'] === 'success'): ?>
                                <?php
                                $sourceValue = $sourceData[$source]['data'][$field] ?? null;
                                $status = getFieldStatus($book[$field] ?? '', $sourceValue);
                                $cellClass = 'field-' . $status;
                                ?>
                                <td class="source-value <?php echo $cellClass; ?>">
                                    <div class="value-container">
                                        <?php echo formatFieldValue($field, $sourceValue); ?>
                                    </div>
                                    <div class="action-container mt-2">
                                        <?php if ($status !== 'empty'): ?>
                                            <?php echo renderApplyButton($field, $status, $source); ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">[-]</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($field === 'characters' || $field === 'series' || $field === 'publication_date'): ?>
                                        <div class="debug-info mt-1">
                                            <small class="text-muted">Raw value: <?php echo is_array($sourceValue) ? json_encode($sourceValue) : htmlspecialchars($sourceValue ?? 'null'); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td class="source-value field-empty">
                                    <div class="value-container">
                                        <span class="text-danger">
                                            <?php echo $sourceData[$source]['message'] ?? 'Error fetching data'; ?>
                                        </span>
                                    </div>
                                    <div class="action-container mt-2">
                                        <span class="badge bg-danger">Error</span>
                                    </div>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="legend mt-3">
    <h6>Legend:</h6>
    <div class="d-flex flex-wrap gap-3">
        <?php foreach ($statusIcons as $status => $info): ?>
            <div class="legend-item">
                <span class="badge bg-<?php echo $info['class']; ?>">
                    <i class="fas fa-<?php echo $info['icon']; ?>"></i>
                </span>
                <span class="legend-text"><?php echo $info['text']; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
