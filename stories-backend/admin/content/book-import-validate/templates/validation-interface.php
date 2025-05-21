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
                    <form method="get" class="d-inline-block">
                        <input type="hidden" name="action" value="validate_book">
                        <input type="hidden" name="book_id" value="<?php echo (int)$book['id']; ?>">
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-sync-alt"></i> Force Fresh Data
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger btn-lg ms-2" id="clearCacheBtn">
                        <i class="fas fa-trash-alt"></i> Clear All Caches
                    </button>

                    <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/book-import-validate/download-raw-data.php?book_id=<?php echo (int)$book['id']; ?>"
                       class="btn btn-info btn-lg ms-2"
                       target="_blank">
                        <i class="fas fa-download"></i> Download Raw Data
                    </a>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('clearCacheBtn').addEventListener('click', function() {
                            if (confirm('This will clear all caches for Goodreads data. Continue?')) {
                                // Store button reference
                                const button = this;

                                // Show loading indicator
                                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
                                button.disabled = true;

                                // Make AJAX request to clear cache
                                fetch('book-import-validate/clear-goodreads-cache.php')
                                    .then(response => {
                                        // Even if the response is not OK, try to parse the JSON
                                        // as it might contain useful error information
                                        return response.json().catch(e => {
                                            // If JSON parsing fails, create a simple error object
                                            if (!response.ok) {
                                                return {
                                                    status: 'error',
                                                    message: `HTTP error! Status: ${response.status}`,
                                                    actions: [{
                                                        name: 'http_error',
                                                        status: 'error',
                                                        message: `Server returned HTTP ${response.status}`
                                                    }]
                                                };
                                            } else {
                                                throw new Error('Invalid JSON response');
                                            }
                                        });
                                    })
                                    .then(data => {
                                        console.log('Cache clear response:', data);

                                        // Show result
                                        if (data.status === 'success' || data.status === 'partial') {
                                            alert('Cache cleared successfully. ' + data.message);

                                            // Show detailed actions if available
                                            if (data.actions && data.actions.length > 0) {
                                                console.log('Cache clear actions:', data.actions);
                                            }

                                            // Reload the page to get fresh data
                                            window.location.reload();
                                        } else {
                                            let errorMessage = data.message || 'Unknown error';

                                            // Add action details if available
                                            if (data.actions && data.actions.length > 0) {
                                                const errorActions = data.actions.filter(a => a.status === 'error');
                                                if (errorActions.length > 0) {
                                                    errorMessage += '\n\nDetails:\n' + errorActions.map(a => `- ${a.message}`).join('\n');
                                                }
                                            }

                                            alert('Error clearing cache: ' + errorMessage);
                                            button.innerHTML = '<i class="fas fa-trash-alt"></i> Clear All Caches';
                                            button.disabled = false;
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error clearing cache:', error);
                                        alert('Error clearing cache: ' + error.message);
                                        button.innerHTML = '<i class="fas fa-trash-alt"></i> Clear All Caches';
                                        button.disabled = false;
                                    });
                            }
                        });
                    });
                    </script>
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
                                            $methodDisplay = 'Python Script';
                                            break;
                                        case 'curl_direct':
                                            $methodIcon = 'globe';
                                            $methodClass = 'info';
                                            $methodDisplay = 'Direct Web Request';
                                            break;
                                        case 'curl_fallback':
                                            $methodIcon = 'globe';
                                            $methodClass = 'info';
                                            $methodDisplay = 'Fallback Web Request';
                                            break;
                                        case 'api':
                                            $methodIcon = 'plug';
                                            $methodClass = 'success';
                                            $methodDisplay = 'API Request';
                                            break;
                                        case 'headless':
                                            $methodIcon = 'robot';
                                            $methodClass = 'primary';
                                            $methodDisplay = 'Headless Browser';
                                            break;
                                        case 'review_fetcher':
                                            $methodIcon = 'server';
                                            $methodClass = 'info';
                                            $methodDisplay = 'Review Fetcher';
                                            break;
                                        case 'unknown':
                                            $methodIcon = 'question';
                                            $methodClass = 'secondary';
                                            $methodDisplay = 'Unknown';
                                            break;
                                        default:
                                            $methodIcon = 'info-circle';
                                            $methodClass = 'secondary';
                                            $methodDisplay = ucfirst(str_replace('_', ' ', $method));
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
                                                    data-bs-toggle="collapse" data-bs-target="#steps-<?php echo $source; ?>"
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

                                                                <?php if (!empty($step['fetch_url'])): ?>
                                                                <div class="mt-1 small">
                                                                    <strong>URL:</strong> <code><?php echo htmlspecialchars($step['fetch_url']); ?></code>
                                                                </div>
                                                                <?php endif; ?>

                                                                <?php if (!empty($step['response'])): ?>
                                                                <div class="mt-1 small">
                                                                    <strong>Response:</strong> <code><?php echo htmlspecialchars(substr($step['response'], 0, 100) . (strlen($step['response']) > 100 ? '...' : '')); ?></code>
                                                                </div>
                                                                <?php endif; ?>
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

                <!-- Removed unnecessary buttons -->
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

<!-- Notification container -->
<div class="notification-container"></div>

<!-- JavaScript -->
<script src="book-import-validate/js/ajax-validation.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize validation interface
    initValidationInterface();

    // Initialize AJAX validation
    if (typeof initAjaxValidation === 'function') {
        initAjaxValidation();
    }
});
</script>
