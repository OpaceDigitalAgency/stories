<?php
/**
 * Book Validation Interface Template
 *
 * This template displays the main validation interface for comparing book data
 * from multiple sources and applying changes.
 */

// Ensure $book and $validationData are available
if (!isset($book)) {
    echo '<div class="alert alert-danger">Error: Book data not available</div>';
    return;
}

// Initialize sourceData if not set
if (!isset($validationData) || !isset($validationData['sourceData'])) {
    echo '<div class="alert alert-warning">No validation data available for this book. Please try validating again.</div>';
    return;
}

// Set sourceData from validationData
$sourceData = $validationData['sourceData'] ?? [];

// Define status icons and classes
$statusIcons = [
    'match' => ['icon' => 'check', 'class' => 'success', 'text' => 'Matches current value'],
    'new' => ['icon' => 'sync-alt', 'class' => 'warning', 'text' => 'New value available'],
    'conflict' => ['icon' => 'exclamation-triangle', 'class' => 'danger', 'text' => 'Conflict with other sources'],
    'invalid' => ['icon' => 'times', 'class' => 'danger', 'text' => 'Likely incorrect value'],
    'empty' => ['icon' => 'minus', 'class' => 'secondary', 'text' => 'No data available']
];

// Get available sources
$sources = array_keys($sourceData);
?>

<div class="validation-interface">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-light btn-sm" id="refreshValidation">
                        <i class="fas fa-sync-alt"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="book-info mb-4">
                <h4>Current Book: <?php echo htmlspecialchars($book['title']); ?></h4>
                <div class="status-badge">
                    <?php
                    $sourceCount = count(array_filter($sources, function($source) use ($sourceData) {
                        return !empty($sourceData[$source]) && $sourceData[$source]['status'] === 'success';
                    }));

                    if ($sourceCount > 0) {
                        echo '<span class="badge bg-success"><i class="fas fa-check"></i> Found in ' . $sourceCount . ' ' . ($sourceCount === 1 ? 'source' : 'sources') . '</span>';
                    } else {
                        echo '<span class="badge bg-danger"><i class="fas fa-times"></i> Not found in any sources</span>';
                    }
                    ?>
                </div>
            </div>

            <!-- Data Source Status Panel -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Data Source Status</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sources as $source): ?>
                                <?php
                                    $status = !empty($sourceData[$source]) ? $sourceData[$source]['status'] : 'not_attempted';
                                    $statusClass = '';
                                    $statusIcon = '';
                                    $statusText = '';

                                    switch ($status) {
                                        case 'success':
                                            $statusClass = 'success';
                                            $statusIcon = 'check-circle';
                                            $statusText = 'Success';
                                            break;
                                        case 'error':
                                            $statusClass = 'danger';
                                            $statusIcon = 'times-circle';
                                            $statusText = 'Error';
                                            break;
                                        case 'partial':
                                            $statusClass = 'warning';
                                            $statusIcon = 'exclamation-circle';
                                            $statusText = 'Partial Data';
                                            break;
                                        case 'not_attempted':
                                        default:
                                            $statusClass = 'secondary';
                                            $statusIcon = 'minus-circle';
                                            $statusText = 'Not Attempted';
                                            break;
                                    }

                                    $details = !empty($sourceData[$source]['message']) ? $sourceData[$source]['message'] : '';
                                    $processingTime = !empty($sourceData[$source]['processing_time']) ? $sourceData[$source]['processing_time'] . 's' : '';
                                    $method = !empty($sourceData[$source]['method']) ? $sourceData[$source]['method'] : 'unknown';
                                    $steps = !empty($sourceData[$source]['steps']) ? $sourceData[$source]['steps'] : [];

                                    // Format method for display
                                    $methodDisplay = ucfirst(str_replace('_', ' ', $method));
                                    $methodIcon = '';
                                    $methodClass = '';

                                    switch ($method) {
                                        case 'python_script':
                                            $methodIcon = 'code';
                                            $methodClass = 'primary';
                                            break;
                                        case 'curl_direct':
                                        case 'curl_fallback':
                                            $methodIcon = 'globe';
                                            $methodClass = 'info';
                                            break;
                                        case 'api':
                                            $methodIcon = 'plug';
                                            $methodClass = 'success';
                                            break;
                                        default:
                                            $methodIcon = 'question';
                                            $methodClass = 'secondary';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo ucfirst(str_replace('_', ' ', $source)); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <i class="fas fa-<?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                                        </span>
                                        <?php if ($processingTime): ?>
                                            <small class="text-muted">(<?php echo $processingTime; ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $methodClass; ?>">
                                            <i class="fas fa-<?php echo $methodIcon; ?>"></i> <?php echo $methodDisplay; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($details); ?>

                                        <?php if (!empty($steps)): ?>
                                            <button class="btn btn-sm btn-outline-secondary mt-1" type="button"
                                                    data-toggle="collapse" data-target="#steps-<?php echo $source; ?>"
                                                    aria-expanded="false" aria-controls="steps-<?php echo $source; ?>">
                                                <i class="fas fa-list"></i> Show Steps
                                            </button>
                                            <div class="collapse mt-2" id="steps-<?php echo $source; ?>">
                                                <div class="card card-body p-2">
                                                    <ul class="list-group list-group-flush">
                                                        <?php foreach ($steps as $step): ?>
                                                            <?php
                                                                $stepStatusClass = '';
                                                                switch ($step['status'] ?? 'unknown') {
                                                                    case 'success':
                                                                        $stepStatusClass = 'success';
                                                                        $stepIcon = 'check-circle';
                                                                        break;
                                                                    case 'error':
                                                                        $stepStatusClass = 'danger';
                                                                        $stepIcon = 'times-circle';
                                                                        break;
                                                                    case 'warning':
                                                                        $stepStatusClass = 'warning';
                                                                        $stepIcon = 'exclamation-circle';
                                                                        break;
                                                                    case 'in_progress':
                                                                        $stepStatusClass = 'info';
                                                                        $stepIcon = 'spinner';
                                                                        break;
                                                                    default:
                                                                        $stepStatusClass = 'secondary';
                                                                        $stepIcon = 'question-circle';
                                                                }
                                                            ?>
                                                            <li class="list-group-item p-1">
                                                                <span class="text-<?php echo $stepStatusClass; ?>">
                                                                    <i class="fas fa-<?php echo $stepIcon; ?>"></i>
                                                                </span>
                                                                <strong><?php echo ucwords(str_replace('_', ' ', $step['name'] ?? 'unknown')); ?>:</strong>
                                                                <?php echo htmlspecialchars($step['message'] ?? ''); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($sourceCount > 0): ?>
                <div class="comparison-table-container">
                    <?php include 'source-comparison-table.php'; ?>
                </div>

                <div class="global-actions mt-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" id="applyAllValid">
                            <i class="fas fa-check-double"></i> Apply All Valid
                        </button>

                        <?php foreach ($sources as $source): ?>
                            <?php if (!empty($sourceData[$source]) && $sourceData[$source]['status'] === 'success'): ?>
                                <button type="button" class="btn btn-primary apply-all-source" data-source="<?php echo htmlspecialchars($source); ?>">
                                    <i class="fas fa-cloud-download-alt"></i> Apply All from <?php echo ucfirst(htmlspecialchars($source)); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <button type="button" class="btn btn-secondary" id="resetAll">
                            <i class="fas fa-undo"></i> Reset All
                        </button>

                        <button type="button" class="btn btn-info" id="validateAgain">
                            <i class="fas fa-sync-alt"></i> Validate Again
                        </button>

                        <a href="?action=clear_google_books_cache&book_id=<?php echo (int)$book['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-trash-alt"></i> Clear Google Books Cache
                        </a>

                        <button type="button" class="btn btn-outline-primary" id="exportChanges">
                            <i class="fas fa-file-export"></i> Export Changes
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No data found for this book in any of the sources.
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" id="searchByTitle">
                            <i class="fas fa-search"></i> Search by Title
                        </button>
                        <button type="button" class="btn btn-secondary" id="manualEntry">
                            <i class="fas fa-edit"></i> Manual Entry
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="validation-history mt-4">
                <h5>Validation History</h5>
                <div class="history-container p-3 border rounded bg-light">
                    <?php if (!empty($validationHistory)): ?>
                        <?php foreach ($validationHistory as $entry): ?>
                            <div class="history-entry">
                                <span class="history-timestamp"><?php echo htmlspecialchars($entry['timestamp']); ?></span> -
                                <span class="history-action"><?php echo htmlspecialchars($entry['action']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No validation history available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for submitting actions -->
<form id="validationActionForm" method="post" style="display: none;">
    <input type="hidden" name="action" id="actionType" value="">
    <input type="hidden" name="book_id" value="<?php echo (int)$book['id']; ?>">
    <input type="hidden" name="field" id="actionField" value="">
    <input type="hidden" name="value" id="actionValue" value="">
    <input type="hidden" name="source" id="actionSource" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize validation interface
    initValidationInterface();
});
</script>
