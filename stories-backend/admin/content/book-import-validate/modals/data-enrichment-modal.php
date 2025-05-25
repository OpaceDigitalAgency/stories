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
                            <h6 class="mb-0">
                                <i class="fas fa-edit"></i> Available Data Enrichments
                                <small class="text-muted ml-2">Select fields to update</small>
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
    currentBookISBN = currentISBN;

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
    if (currentBookISBN) {
        $('#isbn-status-badge').html('<span class="badge badge-info">Validating Current ISBN</span>');
        // Check Goodreads using the current ISBN passed to the modal
        checkGoodreadsStatus(currentBookISBN);
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

    const fieldLabels = {
        'isbn': 'ISBN-10',
        'isbn13': 'ISBN-13',
        'author': 'Author',
        'publisher': 'Publisher',
        'publication_date': 'Publication Date',
        'page_count': 'Page Count',
        'language': 'Language',
        'format': 'Format',
        'cover_url': 'Cover Image',
        'preview_link': 'Preview Link',
        'series': 'Series'
    };

    Object.keys(fields).forEach(fieldName => {
        const field = fields[fieldName];
        const label = fieldLabels[fieldName] || fieldName;
        const confidence = field.confidence;
        const confidenceClass = confidence >= 80 ? 'confidence-high' :
                               confidence >= 60 ? 'confidence-medium' : 'confidence-low';

        const fieldHtml = `
            <div class="col-md-6">
                <div class="enrichment-field" data-field="${fieldName}">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               id="field_${fieldName}" name="fields[]" value="${fieldName}">
                        <label class="form-check-label font-weight-bold" for="field_${fieldName}">
                            ${label}
                            <span class="badge badge-secondary source-badge ml-2">${field.source}</span>
                            <span class="field-confidence ${confidenceClass} ml-1">(${confidence}%)</span>
                        </label>
                    </div>
                    <div class="new-value mt-2">
                        <strong>New Value:</strong> ${formatFieldValue(fieldName, field.value)}
                    </div>
                </div>
            </div>
        `;

        container.append(fieldHtml);
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
}

function formatFieldValue(fieldName, value) {
    if (fieldName === 'cover_url') {
        return `<img src="${value}" alt="Cover" style="max-height: 60px; max-width: 100px;" class="img-thumbnail">`;
    } else if (fieldName === 'preview_link') {
        return `<a href="${value}" target="_blank" class="btn btn-sm btn-outline-primary">View Preview</a>`;
    }
    return value;
}

function checkGoodreadsStatus(isbn) {
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
                '<span class="badge badge-success">Found</span>' :
                '<span class="badge badge-warning">Not Found</span>';
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
        selectedFields[fieldName] = currentEnrichmentData.fields[fieldName];
    });

    if (Object.keys(selectedFields).length === 0) {
        alert('Please select at least one field to update.');
        return;
    }

    // Apply the changes
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

                // Refresh the page or update the row
                location.reload();
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
