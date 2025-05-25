<!-- Data Enrichment Modal -->
<div class="modal fade" id="dataEnrichmentModal" tabindex="-1" role="dialog" aria-labelledby="dataEnrichmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataEnrichmentModalLabel">
                    <i class="fas fa-database"></i> Enrich Book Data
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="enrichment-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Searching for book data...</span>
                    </div>
                    <p class="mt-2">Searching Google Books and OpenLibrary...</p>
                </div>

                <!-- Results Container -->
                <div id="enrichment-results" style="display: none;">
                    <!-- Match Confidence -->
                    <div class="alert alert-info mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <strong>Match Confidence:</strong>
                                <span id="confidence-score" class="badge badge-primary">0%</span>
                                <span id="confidence-details" class="small text-muted ml-2"></span>
                            </div>
                            <div class="col-md-4 text-right">
                                <span id="sources-checked" class="small text-muted"></span>
                            </div>
                        </div>

                        <!-- ISBN Validation Status -->
                        <div id="isbn-validation-status" class="mt-2" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>ISBN Validation:</strong>
                                    <span id="isbn-status-badge"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Goodreads Check:</strong>
                                    <span id="goodreads-status-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrichment Fields -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fas fa-edit"></i> Available Data Enrichments
                                    <small class="text-muted ml-2">Select fields to update</small>
                                </span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-fields">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="deselect-all-fields">
                                        <i class="fas fa-square"></i> Deselect All
                                    </button>
                                </div>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="enrichment-form">
                                <div class="row" id="enrichment-fields">
                                    <!-- Fields will be populated dynamically -->
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- No Data Found -->
                    <div id="no-enrichment-data" style="display: none;" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No additional data found.</strong>
                        <p class="mb-0">We couldn't find enrichment data from Google Books or OpenLibrary for this book.</p>
                    </div>
                </div>

                <!-- Error State -->
                <div id="enrichment-error" style="display: none;" class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Error occurred while searching for data.</strong>
                    <p class="mb-0" id="error-message"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="fix-all-btn">
                    <i class="fas fa-magic"></i> Fix All
                </button>
                <button type="button" class="btn btn-primary" id="apply-enrichment-btn" disabled>
                    <i class="fas fa-save"></i> Apply Selected Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.enrichment-field {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
}

.enrichment-field.selected {
    border-color: #007bff;
    background-color: #e7f3ff;
}

.field-confidence {
    font-size: 0.875rem;
}

.confidence-high { color: #28a745; }
.confidence-medium { color: #ffc107; }
.confidence-low { color: #dc3545; }

.current-value {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}

.new-value {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 0.25rem;
    padding: 0.5rem;
}

.field-checkbox {
    transform: scale(1.2);
    margin-right: 0.5rem;
}

.source-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

#confidence-score.badge-success { background-color: #28a745; }
#confidence-score.badge-warning { background-color: #ffc107; }
#confidence-score.badge-danger { background-color: #dc3545; }
</style>

<script>
// Global variables for enrichment modal
let currentEnrichmentData = null;
let currentBookId = null;
let currentBookISBN = null;

function openDataEnrichmentModal(bookId, title, author, currentISBN = '') {
    currentBookId = bookId;
    currentBookISBN = String(currentISBN || ''); // Ensure it's always a string

    // Reset modal state
    $('#enrichment-loading').show();
    $('#enrichment-results').hide();
    $('#enrichment-error').hide();
    $('#apply-enrichment-btn').prop('disabled', true);

    // Update modal title
    $('#dataEnrichmentModalLabel').html(`<i class="fas fa-database"></i> Enrich Data: ${title}`);

    // Show modal
    $('#dataEnrichmentModal').modal('show');

    // Fetch enrichment data
    fetchEnrichmentData(title, author, currentISBN);
}

function fetchEnrichmentData(title, author, currentISBN) {
    console.log('Fetching enrichment data for:', { title, author, currentISBN });

    $.ajax({
        url: 'book-import-validate/ajax/data-enrichment-ajax.php',
        method: 'POST',
        data: {
            action: 'get_enrichment_data',
            title: title,
            author: author,
            current_isbn: currentISBN
        },
        dataType: 'json',
        success: function(response) {
            console.log('Enrichment response:', response);
            $('#enrichment-loading').hide();

            if (response.success) {
                currentEnrichmentData = response.data;
                displayEnrichmentResults(response.data);
            } else {
                showEnrichmentError(response.message || 'Unknown error occurred');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', { xhr, status, error });
            console.error('Response text:', xhr.responseText);
            $('#enrichment-loading').hide();
            showEnrichmentError('Network error: ' + error + ' (Check console for details)');
        }
    });
}

function displayEnrichmentResults(data) {
    if (!data.fields || Object.keys(data.fields).length === 0) {
        $('#no-enrichment-data').show();
        return;
    }

    // Show confidence score
    const confidence = Math.round(data.confidence_score);
    const confidenceClass = confidence >= 80 ? 'badge-success' :
                           confidence >= 60 ? 'badge-warning' : 'badge-danger';

    $('#confidence-score').text(confidence + '%').removeClass().addClass(`badge ${confidenceClass}`);
    $('#confidence-details').text(`Based on ${data.sources_checked.join(', ')}`);
    $('#sources-checked').text(`Sources: ${data.sources_checked.join(', ')}`);

    // Always show ISBN validation status for enrichment
    $('#isbn-validation-status').show();

    // For enrichment, we're validating the current ISBN, not suggesting different ones
    if (currentBookISBN && String(currentBookISBN).trim() !== '') {
        // Ensure ISBN is a string and show detailed ISBN information
        const isbnString = String(currentBookISBN);
        const isbnLength = isbnString.replace(/[^0-9X]/gi, '').length;
        const isbnType = isbnLength === 13 ? 'ISBN-13' : isbnLength === 10 ? 'ISBN-10' : 'Unknown';
        $('#isbn-status-badge').html(`<span class="badge badge-info" title="Validating ${isbnType}: ${isbnString}">Validating ${isbnType}: ${isbnString}</span>`);
        // Check Goodreads using the current ISBN passed to the modal
        checkGoodreadsStatus(isbnString);
    } else {
        $('#isbn-status-badge').html('<span class="badge badge-warning">No ISBN to Validate</span>');
        $('#goodreads-status-badge').html('<span class="badge badge-secondary">No ISBN</span>');
    }

    // Display enrichment fields
    displayEnrichmentFields(data.fields);

    $('#enrichment-results').show();
}

function displayEnrichmentFields(fields) {
    const container = $('#enrichment-fields');
    container.empty();

    // Define preferred field order (high priority fields first)
    const fieldOrder = [
        'isbn', 'isbn13', 'author', 'publisher', 'publication_date', 'page_count',
        'language', 'format', 'cover_url', 'preview_link', 'price_range', 'age_range',
        'reading_level', 'series', 'awards', 'characters', 'settings', 'tags', 'genres',
        'maturity_rating', 'categories', 'subjects', 'description', 'subtitle'
    ];

    // First, display fields in preferred order
    fieldOrder.forEach(fieldName => {
        const field = fields[fieldName];
        if (!field) return; // Skip if field doesn't exist

        const label = field.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const isUnknown = field.status === 'unknown';

        // Handle fields with multiple source options
        if (field.options) {
            container.append(createMultiSourceField(fieldName, field, label));
        } else {
            container.append(createSingleSourceField(fieldName, field, label, isUnknown));
        }
    });

    // Then, display any remaining fields that weren't in the preferred order
    Object.keys(fields).forEach(fieldName => {
        if (fieldOrder.includes(fieldName)) return; // Already displayed

        const field = fields[fieldName];
        const label = field.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const isUnknown = field.status === 'unknown';

        // Handle fields with multiple source options
        if (field.options) {
            container.append(createMultiSourceField(fieldName, field, label));
        } else {
            container.append(createSingleSourceField(fieldName, field, label, isUnknown));
        }
    });

    // Add change handlers
    $('.field-checkbox').change(function() {
        const fieldDiv = $(this).closest('.enrichment-field');
        if ($(this).is(':checked')) {
            fieldDiv.addClass('selected');
        } else {
            fieldDiv.removeClass('selected');
        }

        // Enable/disable apply button
        const hasSelected = $('.field-checkbox:checked').length > 0;
        $('#apply-enrichment-btn').prop('disabled', !hasSelected);
    });

    // Add Select All / Deselect All handlers
    $('#select-all-fields').off('click').on('click', function() {
        $('.field-checkbox').prop('checked', true).trigger('change');
    });

    $('#deselect-all-fields').off('click').on('click', function() {
        $('.field-checkbox').prop('checked', false).trigger('change');
    });
}

function createSingleSourceField(fieldName, field, label, isUnknown) {
    const confidence = field.confidence || 0;
    const source = field.source || 'unknown';
    const displayValue = isUnknown ? '<span class="text-muted">Unknown</span>' : formatFieldValue(fieldName, field.value);

    const confidenceClass = confidence >= 80 ? 'success' : confidence >= 60 ? 'warning' : confidence >= 30 ? 'info' : 'secondary';
    const sourceClass = source.includes('+') ? 'primary' : source === 'google_books' ? 'success' : source === 'open_library' ? 'info' : 'secondary';

    return `
        <div class="col-md-6 mb-3">
            <div class="enrichment-field" data-field="${fieldName}">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox"
                           id="field_${fieldName}" name="fields[]" value="${fieldName}" ${isUnknown ? 'disabled' : ''}>
                    <label class="form-check-label font-weight-bold" for="field_${fieldName}">
                        ${label}
                        <span class="badge badge-${sourceClass} ml-2">${source}</span>
                        ${!isUnknown ? `<span class="badge badge-${confidenceClass} ml-1">(${confidence}%)</span>` : ''}
                    </label>
                </div>
                <div class="mt-2 p-2 ${isUnknown ? 'bg-light text-muted' : 'bg-light'} rounded">
                    <strong>New Value:</strong> ${displayValue}
                </div>
            </div>
        </div>
    `;
}

function createMultiSourceField(fieldName, field, label) {
    let optionsHtml = '';
    field.options.forEach((option, index) => {
        const confidence = option.confidence || 0;
        const source = option.source || 'unknown';
        const displayValue = formatFieldValue(fieldName, option.value);
        const confidenceClass = confidence >= 80 ? 'success' : confidence >= 60 ? 'warning' : confidence >= 30 ? 'info' : 'secondary';
        const sourceClass = source === 'google_books' ? 'success' : 'info';

        optionsHtml += `
            <div class="form-check mt-2">
                <input class="form-check-input" type="radio" name="field_${fieldName}_option" id="field_${fieldName}_${index}" value="${index}">
                <label class="form-check-label" for="field_${fieldName}_${index}">
                    <span class="badge badge-${sourceClass}">${source}</span>
                    <span class="badge badge-${confidenceClass} ml-1">(${confidence}%)</span>
                    <div class="mt-1">${displayValue}</div>
                </label>
            </div>
        `;
    });

    return `
        <div class="col-md-6 mb-3">
            <div class="enrichment-field" data-field="${fieldName}">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox"
                           id="field_${fieldName}" name="fields[]" value="${fieldName}">
                    <label class="form-check-label font-weight-bold" for="field_${fieldName}">
                        ${label}
                        <span class="badge badge-warning ml-2">Multiple Sources</span>
                    </label>
                </div>
                <div class="mt-2 p-2 bg-light rounded">
                    <strong>Choose Source:</strong>
                    ${optionsHtml}
                </div>
            </div>
        </div>
    `;
}

function formatFieldValue(fieldName, value) {
    if (!value || value === null || value === 'null' || value === 'Unknown') {
        return '<span class="text-muted">Unknown</span>';
    }

    if (fieldName === 'cover_url') {
        return `<img src="${value}" alt="Cover" style="max-height: 60px; max-width: 100px;" class="img-thumbnail">`;
    } else if (fieldName === 'preview_link') {
        return `<a href="${value}" target="_blank" class="btn btn-sm btn-outline-primary">View Preview</a>`;
    } else if (fieldName === 'tags' || fieldName === 'genres' || fieldName === 'categories' || fieldName === 'subjects') {
        // Handle array values for tags/genres
        if (Array.isArray(value)) {
            return value.map(tag => `<span class="badge badge-secondary mr-1">${tag}</span>`).join('');
        } else if (typeof value === 'string' && value.includes(',')) {
            return value.split(',').map(tag => `<span class="badge badge-secondary mr-1">${tag.trim()}</span>`).join('');
        }
        return `<span class="badge badge-secondary">${value}</span>`;
    } else if (fieldName === 'publication_date') {
        // Format dates nicely
        const date = new Date(value);
        if (!isNaN(date.getTime())) {
            return date.toLocaleDateString();
        }
        return value;
    } else if (fieldName === 'page_count') {
        return `${value} pages`;
    } else if (fieldName === 'maturity_rating') {
        const ratingClass = value === 'NOT_MATURE' ? 'success' : 'warning';
        return `<span class="badge badge-${ratingClass}">${value}</span>`;
    }

    return value;
}

function checkGoodreadsStatus(isbn) {
    // Ensure ISBN is a string
    isbn = String(isbn || '').trim();

    if (!isbn) {
        $('#goodreads-status-badge').html('<span class="badge badge-secondary">No ISBN</span>');
        return;
    }

    $('#goodreads-status-badge').html('<span class="badge badge-info">Checking...</span>');

    $.ajax({
        url: 'book-import-validate/ajax/data-enrichment-ajax.php',
        method: 'POST',
        data: {
            action: 'check_goodreads_isbn',
            isbn: isbn
        },
        dataType: 'json',
        success: function(response) {
            const badge = response.success && response.exists ?
                '<span class="badge" style="background-color: #28a745; color: white; border: none;"><i class="fas fa-book"></i> Goodreads</span>' :
                '<span class="badge" style="background-color: #dc3545; color: white; border: none;"><i class="fas fa-times"></i> Not on Goodreads</span>';
            $('#goodreads-status-badge').html(badge);
        },
        error: function() {
            $('#goodreads-status-badge').html('<span class="badge badge-danger">Error</span>');
        }
    });
}

function showEnrichmentError(message) {
    $('#error-message').text(message);
    $('#enrichment-error').show();
}

// Apply enrichment changes
$('#apply-enrichment-btn').click(function() {
    const selectedFields = {};
    $('.field-checkbox:checked').each(function() {
        const fieldName = $(this).val();
        const fieldData = currentEnrichmentData.fields[fieldName];

        // Handle multi-source fields
        if (fieldData.options) {
            const selectedOption = $(`input[name="field_${fieldName}_option"]:checked`);
            if (selectedOption.length > 0) {
                const optionIndex = parseInt(selectedOption.val());
                selectedFields[fieldName] = {
                    value: fieldData.options[optionIndex].value,
                    source: fieldData.options[optionIndex].source,
                    confidence: fieldData.options[optionIndex].confidence
                };
            }
        } else {
            selectedFields[fieldName] = fieldData;
        }
    });

    if (Object.keys(selectedFields).length === 0) {
        alert('Please select at least one field to update.');
        return;
    }

    // Apply the changes
    applyEnrichmentChanges(currentBookId, selectedFields);
});

// Fix All button - selects all fields and applies them
$('#fix-all-btn').click(function() {
    if (!currentEnrichmentData || !currentEnrichmentData.fields) {
        alert('No enrichment data available.');
        return;
    }

    // Select all checkboxes (except unknown fields)
    $('.field-checkbox:not(:disabled)').prop('checked', true).trigger('change');

    // For fields with multiple options, auto-select the highest confidence option
    Object.keys(currentEnrichmentData.fields).forEach(fieldName => {
        const fieldData = currentEnrichmentData.fields[fieldName];

        if (fieldData && fieldData.options) {
            // Find the option with highest confidence
            let highestConfidence = 0;
            let bestOptionIndex = 0;

            fieldData.options.forEach((option, index) => {
                if (option.confidence > highestConfidence) {
                    highestConfidence = option.confidence;
                    bestOptionIndex = index;
                }
            });

            // Select the best option
            $(`input[name="field_${fieldName}_option"][value="${bestOptionIndex}"]`).prop('checked', true);
        }
    });

    // Build the selected fields object with proper structure
    const selectedFields = {};
    $('.field-checkbox:checked').each(function() {
        const fieldName = $(this).val();
        const fieldData = currentEnrichmentData.fields[fieldName];

        // Handle multi-source fields
        if (fieldData.options) {
            const selectedOption = $(`input[name="field_${fieldName}_option"]:checked`);
            if (selectedOption.length > 0) {
                const optionIndex = parseInt(selectedOption.val());
                selectedFields[fieldName] = {
                    value: fieldData.options[optionIndex].value,
                    source: fieldData.options[optionIndex].source,
                    confidence: fieldData.options[optionIndex].confidence
                };
            }
        } else {
            selectedFields[fieldName] = fieldData;
        }
    });

    // Apply all changes
    applyEnrichmentChanges(currentBookId, selectedFields);
});

function applyEnrichmentChanges(bookId, selectedFields) {
    $('#apply-enrichment-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Applying...');

    $.ajax({
        url: 'book-import-validate/ajax/data-enrichment-ajax.php',
        method: 'POST',
        data: {
            action: 'apply_enrichment',
            book_id: bookId,
            fields: JSON.stringify(selectedFields)
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#dataEnrichmentModal').modal('hide');

                // Show success message
                showAlert('success', `Successfully updated ${Object.keys(selectedFields).length} field(s)!`);

                // Force page refresh with cache busting
                setTimeout(() => {
                    window.location.href = window.location.href.split('?')[0] + '?_refresh=' + Date.now();
                }, 500);
            } else {
                showAlert('danger', 'Error applying changes: ' + (response.message || 'Unknown error'));
                $('#apply-enrichment-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Apply Selected Changes');
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Network error: ' + error);
            $('#apply-enrichment-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Apply Selected Changes');
        }
    });
}
</script>
