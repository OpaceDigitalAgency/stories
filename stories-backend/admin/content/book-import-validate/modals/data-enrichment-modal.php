<!-- Data Enrichment Modal -->
<style>
/* Pale RAG color backgrounds for enrichment fields */
.bg-light-success {
    background-color: #d4edda !important; /* Pale green */
}
.bg-light-warning {
    background-color: #fff3cd !important; /* Pale amber */
}
.bg-light-danger {
    background-color: #f8d7da !important; /* Pale red */
}
.border-success {
    border: 2px solid #28a745 !important;
}
.border-warning {
    border: 2px solid #ffc107 !important;
}
.border-danger {
    border: 2px solid #dc3545 !important;
}
.enrichment-field {
    border-radius: 8px;
    transition: all 0.2s ease;
}
.enrichment-field:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

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
                    <!-- Amazon Buying Options -->
                    <div class="mt-4">
                        <h5>Amazon Buying Options <span id="amazon-status-badge"></span></h5>
                        <div class="amazon-data-container text-muted">Click “Enrich” to load Amazon info.</div>
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
    console.log('Fetching enrichment data for:', { title, author, currentISBN, bookId: currentBookId });

    $.ajax({
        url: 'book-import-validate/ajax/data-enrichment-ajax.php',
        method: 'POST',
        data: {
            action: 'get_enrichment_data',
            title: title,
            author: author,
            current_isbn: currentISBN,
            book_id: currentBookId
        },
        dataType: 'json',
        success: function(response) {
            console.log('Enrichment response:', response);
            $('#enrichment-loading').hide();
            if (response.success) {
                currentEnrichmentData = response.data;
                displayEnrichmentResults(response.data, response.debug);
            } else {
                showEnrichmentError(response.message || 'Unknown error occurred');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', { xhr, status, error });
            console.error('Response text:', xhr.responseText);
            $('#enrichment-loading').hide();

            // Try to extract meaningful error from response
            let errorMessage = error;
            if (xhr.responseText) {
                // If response contains HTML error, extract the error message
                if (xhr.responseText.includes('<b>')) {
                    const match = xhr.responseText.match(/<b>(.*?)<\/b>/);
                    if (match) {
                        errorMessage = match[1];
                    }
                }
                // Show first 500 characters of response for debugging
                console.error('Full response:', xhr.responseText.substring(0, 500));
            }

            showEnrichmentError(`Network error: ${errorMessage}. Response: ${xhr.responseText.substring(0, 200)}...`);
        }
    });
}

function displayEnrichmentResults(data, debug) {
    if (!data.fields || Object.keys(data.fields).length === 0) {
        $('#no-enrichment-data').show();
        return;
    }

    // Debug logging only
    if (debug) {
        console.log('Debug information:', debug);
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

    // Fetch Amazon data asynchronously to populate Amazon-derived fields
    fetchAmazonDataForFields(data.fields);

    $('#enrichment-results').show();
}

function fetchAmazonDataForFields(fields) {
    // Check if we have Amazon-derived fields that need data
    const amazonFields = ['purchase_links', 'format', 'price_range'];
    const hasAmazonFields = amazonFields.some(fieldName =>
        fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived'
    );

    if (!hasAmazonFields || !currentBookISBN) {
        console.log('📦 No Amazon fields to populate or no ISBN available');
        console.log('📦 Debug - fields:', fields);
        console.log('📦 Debug - currentBookISBN:', currentBookISBN);
        console.log('📦 Debug - hasAmazonFields:', hasAmazonFields);

        // Check each Amazon field individually
        amazonFields.forEach(fieldName => {
            console.log(`📦 Debug - ${fieldName}:`, fields[fieldName]);
            if (fields[fieldName]) {
                console.log(`📦 Debug - ${fieldName}.new_data:`, fields[fieldName].new_data);
                if (fields[fieldName].new_data) {
                    console.log(`📦 Debug - ${fieldName}.new_data.source:`, fields[fieldName].new_data.source);
                }
            }
        });
        return;
    }

    console.log('📦 Starting AJAX fetch for Amazon data. ISBN:', currentBookISBN);

    // Show loading indicators for Amazon fields
    amazonFields.forEach(fieldName => {
        if (fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived') {
            const $fieldDiv = $(`.enrichment-field[data-field="${fieldName}"]`);
            const $badge = $fieldDiv.find('.badge:contains("Amazon")');
            $badge.removeClass('badge-warning').addClass('badge-info').text('Amazon (Loading...)');
        }
    });

    // Fetch Amazon data
    $.post('book-import-validate/ajax/data-enrichment-ajax.php', {
        action: 'get_amazon_data',
        isbn: currentBookISBN
    }, function(res) {
        console.log('📦 Amazon AJAX response received:', res);

        if (res.success && res.data && res.data.buying_options && Object.keys(res.data.buying_options).length > 0) {
            // Update the Amazon fields with real data
            updateAmazonFields(res.data);
        } else {
            console.log('📦 No Amazon buying options found or empty response');
            console.log('📦 Debug info:', res.debug);

            // Update badges to show no data found
            amazonFields.forEach(fieldName => {
                if (fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived') {
                    const $fieldDiv = $(`.enrichment-field[data-field="${fieldName}"]`);
                    const $badge = $fieldDiv.find('.badge:contains("Amazon")');
                    $badge.removeClass('badge-info').addClass('badge-secondary').text('Amazon (No data)');
                }
            });
        }
    }, 'json').fail(function(xhr, status, error) {
        console.error('📦 Amazon AJAX error:', { xhr, status, error });
        console.error('📦 Response text:', xhr.responseText);

        // Update badges to show error
        amazonFields.forEach(fieldName => {
            if (fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived') {
                const $fieldDiv = $(`.enrichment-field[data-field="${fieldName}"]`);
                const $badge = $fieldDiv.find('.badge:contains("Amazon")');
                $badge.removeClass('badge-info').addClass('badge-danger').text('Amazon (Error)');
            }
        });
    });
}

function updateAmazonFields(amazonData) {
    // Update purchase_links field
    const purchaseLinksField = $(`.enrichment-field[data-field="purchase_links"]`);
    if (purchaseLinksField.length && amazonData.buying_options) {
        const jsonValue = JSON.stringify(amazonData.buying_options);
        purchaseLinksField.find('.new-value').html(formatFieldValue('purchase_links', jsonValue));
        purchaseLinksField.find('.badge:contains("Amazon")').removeClass('badge-info').addClass('badge-warning').text('Amazon');
        purchaseLinksField.find('.field-checkbox').prop('disabled', false);

        // Update the global enrichment data for form submission
        if (currentEnrichmentData && currentEnrichmentData.fields && currentEnrichmentData.fields.purchase_links) {
            currentEnrichmentData.fields.purchase_links.new_data.value = jsonValue;
            currentEnrichmentData.fields.purchase_links.new_data.status = 'ready';
        }
    }

    // Update format field
    const formatField = $(`.enrichment-field[data-field="format"]`);
    if (formatField.length && amazonData.selected_format) {
        formatField.find('.new-value').text(amazonData.selected_format);
        formatField.find('.badge:contains("Amazon")').removeClass('badge-info').addClass('badge-warning').text('Amazon');
        formatField.find('.field-checkbox').prop('disabled', false);

        // Update the global enrichment data for form submission
        if (currentEnrichmentData && currentEnrichmentData.fields && currentEnrichmentData.fields.format) {
            currentEnrichmentData.fields.format.new_data.value = amazonData.selected_format;
            currentEnrichmentData.fields.format.new_data.status = 'ready';
        }
    }

    // Update price_range field
    const priceRangeField = $(`.enrichment-field[data-field="price_range"]`);
    if (priceRangeField.length && amazonData.selected_price) {
        const price = parseFloat(amazonData.selected_price.replace('£', ''));
        let priceRange;
        if (price < 5) {
            priceRange = 'Under £5';
        } else if (price <= 10) {
            priceRange = '£5-£10';
        } else if (price <= 15) {
            priceRange = '£10-£15';
        } else if (price <= 20) {
            priceRange = '£15-£20';
        } else {
            priceRange = 'Over £20';
        }

        priceRangeField.find('.new-value').text(priceRange);
        priceRangeField.find('.badge:contains("Amazon")').removeClass('badge-info').addClass('badge-warning').text('Amazon');
        priceRangeField.find('.field-checkbox').prop('disabled', false);

        // Update the global enrichment data for form submission
        if (currentEnrichmentData && currentEnrichmentData.fields && currentEnrichmentData.fields.price_range) {
            currentEnrichmentData.fields.price_range.new_data.value = priceRange;
            currentEnrichmentData.fields.price_range.new_data.status = 'ready';
        }
    }
}

function displayEnrichmentFields(fields) {
    const container = $('#enrichment-fields');
    container.empty();

    // Define preferred field order (only actual database fields)
    // Group related Amazon-derived fields together
    const fieldOrder = [
        'isbn', 'isbn13', 'author', 'publisher', 'publication_date', 'page_count',
        'language', 'cover_url', 'preview_link', 'age_range',
        'reading_level', 'maturity_rating', 'average_rating', 'rating_count',
        'internet_archive_id', 'series', 'awards', 'characters', 'settings', 'tags',
        'alternative_isbns',
        // Amazon-derived fields grouped together
        'purchase_links', 'format', 'price_range'
    ];

    // First, display fields in preferred order
    fieldOrder.forEach(fieldName => {
        const field = fields[fieldName];
        if (!field) return; // Skip if field doesn't exist

        const label = field.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        // Handle fields with multiple source options in new_data
        if (field.new_data && field.new_data.options) {
            container.append(createMultiSourceField(fieldName, field, label));
        } else if (field.new_data) {
            // Single source field with new data
            const isUnknown = field.new_data.status === 'unknown';
            const isPendingAmazon = field.new_data.status === 'pending_amazon_data';
            container.append(createSingleSourceField(fieldName, field, label, isUnknown, isPendingAmazon));
        } else {
            // Field with no new data - show current value only (disabled)
            container.append(createCurrentOnlyField(fieldName, field, label));
        }
    });

    // Then, display any remaining fields that weren't in the preferred order
    Object.keys(fields).forEach(fieldName => {
        if (fieldOrder.includes(fieldName)) return; // Already displayed

        const field = fields[fieldName];
        const label = field.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        // Handle fields with multiple source options in new_data
        if (field.new_data && field.new_data.options) {
            container.append(createMultiSourceField(fieldName, field, label));
        } else if (field.new_data) {
            // Single source field with new data
            const isUnknown = field.new_data.status === 'unknown';
            const isPendingAmazon = field.new_data.status === 'pending_amazon_data';
            container.append(createSingleSourceField(fieldName, field, label, isUnknown, isPendingAmazon));
        } else {
            // Field with no new data - show current value only (disabled)
            container.append(createCurrentOnlyField(fieldName, field, label));
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

        // Auto-select highest confidence options for multi-source fields
        Object.keys(currentEnrichmentData.fields).forEach(fieldName => {
            const fieldData = currentEnrichmentData.fields[fieldName];

            if (fieldData && fieldData.new_data && fieldData.new_data.options) {
                // Find the option with highest confidence
                let highestConfidence = 0;
                let bestOptionIndex = 0;

                fieldData.new_data.options.forEach((option, index) => {
                    if (option.confidence > highestConfidence) {
                        highestConfidence = option.confidence;
                        bestOptionIndex = index;
                    }
                });

                // Select the best option
                $(`input[name="field_${fieldName}_option"][value="${bestOptionIndex}"]`).prop('checked', true);
            }
        });
    });

    $('#deselect-all-fields').off('click').on('click', function() {
        $('.field-checkbox').prop('checked', false).trigger('change');
    });
}

function createSingleSourceField(fieldName, field, label, isUnknown, isPendingAmazon) {
    const newData = field.new_data || {};
    const confidence = newData.confidence || 0;
    const source = newData.source || 'unknown';

    let displayValue;
    if (isPendingAmazon) {
        displayValue = '<span class="text-info">Loading Amazon data...</span>';
    } else if (isUnknown) {
        displayValue = '<span class="text-muted">Unknown</span>';
    } else {
        displayValue = formatFieldValue(fieldName, newData.value);
    }

    const confidenceClass = confidence >= 80 ? 'success' : confidence >= 60 ? 'warning' : confidence >= 30 ? 'info' : 'secondary';
    const sourceClass = source.includes('+') ? 'primary' : source === 'google_books' ? 'success' : source === 'open_library' ? 'info' : source === 'amazon_derived' ? 'warning' : 'secondary';

    // Display friendly source names
    const displaySource = source === 'amazon_derived' ? 'Amazon' :
                         source === 'google_books' ? 'Google Books' :
                         source === 'open_library' ? 'OpenLibrary' :
                         source.replace('_', ' ');

    // Determine benefit level for color coding
    const benefitLevel = isPendingAmazon ? 'questionable' : determineBenefitLevel(field.current_value, newData.value, isUnknown);
    const benefitClass = getBenefitColorClass(benefitLevel);
    const benefitBorder = getBenefitBorderClass(benefitLevel);

    return `
        <div class="col-md-6 mb-3">
            <div class="enrichment-field ${benefitBorder}" data-field="${fieldName}">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox"
                           id="field_${fieldName}" name="fields[]" value="${fieldName}" ${isUnknown || isPendingAmazon || benefitLevel === 'not_beneficial' ? 'disabled' : ''}>
                    <label class="form-check-label font-weight-bold" for="field_${fieldName}">
                        ${label}
                        <span class="badge badge-${sourceClass} ml-2">${displaySource}${isPendingAmazon ? ' (Loading...)' : ''}</span>
                        ${!isUnknown && !isPendingAmazon ? `<span class="badge badge-${confidenceClass} ml-1">(${confidence}%)</span>` : ''}
                        ${getBenefitIndicator(benefitLevel)}
                    </label>
                </div>
                <div class="mt-2 p-2 ${benefitClass} rounded">
                    <div class="mb-2">
                        <strong>Current Value:</strong> ${formatCurrentValue(fieldName, field.current_value)}
                    </div>
                    <strong>New Value:</strong> ${displayValue}
                </div>
            </div>
        </div>
    `;
}

function createMultiSourceField(fieldName, field, label) {
    let optionsHtml = '';
    const options = field.new_data.options || [];

    // Determine overall benefit level for multi-source field
    let bestBenefitLevel = 'not_beneficial';
    options.forEach((option) => {
        const benefitLevel = determineBenefitLevel(field.current_value, option.value, false);
        if (benefitLevel === 'beneficial') {
            bestBenefitLevel = 'beneficial';
        } else if (benefitLevel === 'questionable' && bestBenefitLevel !== 'beneficial') {
            bestBenefitLevel = 'questionable';
        }
    });

    const benefitClass = getBenefitColorClass(bestBenefitLevel);
    const benefitBorder = getBenefitBorderClass(bestBenefitLevel);

    options.forEach((option, index) => {
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
            <div class="enrichment-field ${benefitBorder}" data-field="${fieldName}">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox"
                           id="field_${fieldName}" name="fields[]" value="${fieldName}" ${bestBenefitLevel === 'not_beneficial' ? 'disabled' : ''}>
                    <label class="form-check-label font-weight-bold" for="field_${fieldName}">
                        ${label}
                        <span class="badge badge-warning ml-2">Multiple Sources</span>
                        ${getBenefitIndicator(bestBenefitLevel)}
                    </label>
                </div>
                <div class="mt-2 p-2 ${benefitClass} rounded">
                    <div class="mb-2">
                        <strong>Current Value:</strong> ${formatCurrentValue(fieldName, field.current_value)}
                    </div>
                    <strong>Choose Source:</strong>
                    ${optionsHtml}
                </div>
            </div>
        </div>
    `;
}

function createCurrentOnlyField(fieldName, field, label) {
    return `
        <div class="col-md-6 mb-3">
            <div class="enrichment-field" data-field="${fieldName}">
                <div class="form-check">
                    <input class="form-check-input field-checkbox" type="checkbox"
                           id="field_${fieldName}" name="fields[]" value="${fieldName}" disabled>
                    <label class="form-check-label font-weight-bold text-muted" for="field_${fieldName}">
                        ${label}
                        <span class="badge badge-secondary ml-2">No New Data</span>
                    </label>
                </div>
                <div class="mt-2 p-2 bg-light text-muted rounded">
                    <strong>Current Value:</strong> ${formatCurrentValue(fieldName, field.current_value)}
                </div>
            </div>
        </div>
    `;
}

function formatCurrentValue(fieldName, value) {
    if (!value || value === null || value === 'null' || value === '' || (Array.isArray(value) && value.length === 0)) {
        return '<span class="text-muted">None</span>';
    }

    if (fieldName === 'cover_url') {
        return `<img src="${value}" alt="Current Cover" style="max-height: 40px; max-width: 60px;" class="img-thumbnail">`;
    } else if (fieldName === 'preview_link') {
        return `<a href="${value}" target="_blank" class="btn btn-sm btn-outline-secondary">Current Preview</a>`;
    } else if (fieldName === 'tags') {
        // Handle array values for tags (displayed as genres)
        if (Array.isArray(value)) {
            return value.map(item => `<span class="badge badge-primary mr-1">${item}</span>`).join('');
        } else if (typeof value === 'string' && value.includes(',')) {
            return value.split(',').map(item => `<span class="badge badge-primary mr-1">${item.trim()}</span>`).join('');
        }
        return `<span class="badge badge-primary">${value}</span>`;
    } else if (fieldName === 'publication_date') {
        // Format dates nicely
        const date = new Date(value);
        if (!isNaN(date.getTime())) {
            return date.toLocaleDateString();
        }
        return value;
    } else if (fieldName === 'page_count') {
        return `${value} pages`;
    } else if (fieldName === 'age_range') {
        return `<span class="badge badge-light">${value}</span>`;
    } else if (fieldName === 'maturity_rating') {
        const ratingClass = value === 'NOT_MATURE' ? 'success' : 'warning';
        const displayValue = value === 'NOT_MATURE' ? 'All Ages' : value === 'MATURE' ? '18+' : value;
        return `<span class="badge badge-${ratingClass}">${displayValue}</span>`;
    } else if (fieldName === 'average_rating') {
        return `<span class="text-warning">${'★'.repeat(Math.round(value))}${'☆'.repeat(5-Math.round(value))}</span> ${value}`;
    } else if (fieldName === 'rating_count') {
        return `${value} ratings`;
    } else if (fieldName === 'internet_archive_id') {
        return `<a href="https://archive.org/details/${value}" target="_blank" class="btn btn-sm btn-outline-secondary">Current Archive</a>`;
    } else if (fieldName === 'reading_level') {
        return `<span class="badge badge-secondary">${value}</span>`;
    } else if (fieldName === 'awards') {
        return value.split(',').map(award => `<span class="badge badge-light mr-1">${award.trim()}</span>`).join('');
    } else if (fieldName === 'characters' || fieldName === 'settings') {
        return value.split(',').map(item => `<span class="badge badge-light mr-1">${item.trim()}</span>`).join('');
    } else if (fieldName === 'purchase_links') {
        // Handle JSON purchase links from Amazon
        try {
            const links = JSON.parse(value);
            let html = '<ul class="list-unstyled mb-0">';
            Object.entries(links).forEach(([format, info]) => {
                html += `<li><strong>${format}:</strong> <a href="${info.url}" target="_blank">${info.price}</a></li>`;
            });
            html += '</ul>';
            return html;
        } catch (e) {
            return value; // Fallback to raw value if not valid JSON
        }
    }

    return value;
}

function formatFieldValue(fieldName, value) {
    if (!value || value === null || value === 'null' || value === 'Unknown') {
        return '<span class="text-muted">Unknown</span>';
    }

    if (fieldName === 'cover_url') {
        return `<img src="${value}" alt="Cover" style="max-height: 60px; max-width: 100px;" class="img-thumbnail">`;
    } else if (fieldName === 'preview_link') {
        return `<a href="${value}" target="_blank" class="btn btn-sm btn-outline-primary">View Preview</a>`;
    } else if (fieldName === 'tags') {
        // Handle array values for tags (displayed as genres)
        if (Array.isArray(value)) {
            return value.map(item => `<span class="badge badge-success mr-1">${item}</span>`).join('');
        } else if (typeof value === 'string' && value.includes(',')) {
            return value.split(',').map(item => `<span class="badge badge-success mr-1">${item.trim()}</span>`).join('');
        }
        return `<span class="badge badge-success">${value}</span>`;
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
        const displayValue = value === 'NOT_MATURE' ? 'All Ages' : value === 'MATURE' ? '18+' : value;
        return `<span class="badge badge-${ratingClass}">${displayValue}</span>`;
    } else if (fieldName === 'average_rating') {
        return `<span class="text-warning">${'★'.repeat(Math.round(value))}${'☆'.repeat(5-Math.round(value))}</span> ${value}`;
    } else if (fieldName === 'rating_count') {
        return `${value} ratings`;
    } else if (fieldName === 'internet_archive_id') {
        return `<a href="https://archive.org/details/${value}" target="_blank" class="btn btn-sm btn-outline-info">View on Archive.org</a>`;
    } else if (fieldName === 'reading_level') {
        return `<span class="badge badge-info">${value}</span>`;
    } else if (fieldName === 'awards') {
        return value.split(',').map(award => `<span class="badge badge-warning mr-1">${award.trim()}</span>`).join('');
    } else if (fieldName === 'characters' || fieldName === 'settings') {
        return value.split(',').map(item => `<span class="badge badge-light mr-1">${item.trim()}</span>`).join('');
    } else if (fieldName === 'alternative_isbns') {
        // Display alternative ISBNs in a scrollable container
        const isbns = value.split(',').map(isbn => isbn.trim()).filter(isbn => isbn.length >= 10);
        if (isbns.length === 0) return '<span class="text-muted">None found</span>';

        const isbnBadges = isbns.slice(0, 10).map(isbn => {
            const isbnType = isbn.length === 13 ? 'ISBN-13' : 'ISBN-10';
            return `<span class="badge badge-info mr-1 mb-1" title="${isbnType}: ${isbn}">${isbn}</span>`;
        }).join('');

        const moreCount = isbns.length > 10 ? ` <span class="text-muted">+${isbns.length - 10} more</span>` : '';
        return `<div style="max-height: 100px; overflow-y: auto;">${isbnBadges}${moreCount}</div>`;
    } else if (fieldName === 'purchase_links') {
        // Display purchase links as formatted JSON code
        try {
            const linksData = typeof value === 'string' ? JSON.parse(value) : value;
            if (!linksData || typeof linksData !== 'object') {
                return '<span class="text-muted">No links available</span>';
            }

            // Format as JSON with proper indentation
            const formattedJson = JSON.stringify(linksData, null, 2);
            return `<pre class="bg-light p-2 rounded" style="font-size: 12px; max-height: 150px; overflow-y: auto;"><code>${formattedJson}</code></pre>`;
        } catch (e) {
            console.error('Error parsing purchase links:', e);
            return '<span class="text-danger">Error parsing links</span>';
        }
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
        if (fieldData.new_data && fieldData.new_data.options) {
            const selectedOption = $(`input[name="field_${fieldName}_option"]:checked`);
            if (selectedOption.length > 0) {
                const optionIndex = parseInt(selectedOption.val());
                selectedFields[fieldName] = {
                    value: fieldData.new_data.options[optionIndex].value,
                    source: fieldData.new_data.options[optionIndex].source,
                    confidence: fieldData.new_data.options[optionIndex].confidence
                };
            }
        } else if (fieldData.new_data) {
            selectedFields[fieldName] = fieldData.new_data;
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

        if (fieldData && fieldData.new_data && fieldData.new_data.options) {
            // Find the option with highest confidence
            let highestConfidence = 0;
            let bestOptionIndex = 0;

            fieldData.new_data.options.forEach((option, index) => {
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
        if (fieldData.new_data && fieldData.new_data.options) {
            const selectedOption = $(`input[name="field_${fieldName}_option"]:checked`);
            if (selectedOption.length > 0) {
                const optionIndex = parseInt(selectedOption.val());
                selectedFields[fieldName] = {
                    value: fieldData.new_data.options[optionIndex].value,
                    source: fieldData.new_data.options[optionIndex].source,
                    confidence: fieldData.new_data.options[optionIndex].confidence
                };
            }
        } else if (fieldData.new_data) {
            selectedFields[fieldName] = fieldData.new_data;
        }
    });

    // Apply all changes immediately without user intervention
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

/**
 * Determine the benefit level of updating a field
 * @param {*} currentValue - Current value in database
 * @param {*} newValue - New value from API
 * @param {boolean} isUnknown - Whether new value is unknown
 * @returns {string} - 'beneficial', 'questionable', 'not_beneficial'
 */
function determineBenefitLevel(currentValue, newValue, isUnknown) {
    // If new value is unknown or null, it's not beneficial
    if (isUnknown || !newValue || newValue === 'Unknown' || newValue === 'null' || newValue === '') {
        return 'not_beneficial';
    }

    // If current value is empty/null and new value has content, it's beneficial
    if (isEmpty(currentValue) && !isEmpty(newValue)) {
        return 'beneficial';
    }

    // If both have values, it's questionable (user should decide)
    if (!isEmpty(currentValue) && !isEmpty(newValue)) {
        // Check if values are significantly different
        if (normalizeValue(currentValue) === normalizeValue(newValue)) {
            return 'not_beneficial'; // Same value
        }
        return 'questionable';
    }

    return 'not_beneficial';
}

/**
 * Check if a value is considered empty
 */
function isEmpty(value) {
    if (value === null || value === undefined || value === '' || value === 'null') {
        return true;
    }

    if (Array.isArray(value) && value.length === 0) {
        return true;
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();
        return trimmed === '' || trimmed === 'None' || trimmed === 'Unknown' || trimmed === 'N/A';
    }

    return false;
}

/**
 * Normalize value for comparison
 */
function normalizeValue(value) {
    if (typeof value === 'string') {
        return value.toLowerCase().trim().replace(/[^a-z0-9]/g, '');
    }
    return String(value || '').toLowerCase();
}

/**
 * Get CSS class for benefit level background
 */
function getBenefitColorClass(benefitLevel) {
    switch (benefitLevel) {
        case 'beneficial':
            return 'bg-light-success'; // Pale green
        case 'questionable':
            return 'bg-light-warning'; // Pale amber
        case 'not_beneficial':
            return 'bg-light-danger'; // Pale red
        default:
            return 'bg-light';
    }
}

/**
 * Get CSS class for benefit level border
 */
function getBenefitBorderClass(benefitLevel) {
    switch (benefitLevel) {
        case 'beneficial':
            return 'border-success';
        case 'questionable':
            return 'border-warning';
        case 'not_beneficial':
            return 'border-danger';
        default:
            return '';
    }
}

/**
 * Get benefit indicator icon/badge
 */
function getBenefitIndicator(benefitLevel) {
    switch (benefitLevel) {
        case 'beneficial':
            return '<span class="badge badge-success ml-1" title="Beneficial update"><i class="fas fa-check"></i></span>';
        case 'questionable':
            return '<span class="badge badge-warning ml-1" title="Review recommended"><i class="fas fa-question"></i></span>';
        case 'not_beneficial':
            return '<span class="badge badge-danger ml-1" title="Not beneficial"><i class="fas fa-times"></i></span>';
        default:
            return '';
    }
}
</script>
