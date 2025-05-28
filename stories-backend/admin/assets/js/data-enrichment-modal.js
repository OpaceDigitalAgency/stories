// Data Enrichment Modal JavaScript
// Prevents multiple script loading with a guard
if (typeof window.dataEnrichmentModalLoaded === 'undefined') {
    window.dataEnrichmentModalLoaded = true;

    // Global variables for enrichment modal - make them truly global
    window.currentEnrichmentData = null;
    window.currentBookId = null;
    window.currentBookISBN = null;

    function openDataEnrichmentModal(bookId, title, author, currentISBN = '') {
        window.currentBookId = bookId;
        window.currentBookISBN = String(currentISBN || ''); // Ensure it's always a string

        // Update modal header with book information
        updateModalHeader(title, currentISBN);

        // Reset modal state
        $('#enrichment-loading').show();
        $('#enrichment-results').hide();
        $('#enrichment-error').hide();
        $('#apply-enrichment-btn').prop('disabled', true);

        // Show modal
        $('#dataEnrichmentModal').modal('show');

        // Fetch enrichment data
        fetchEnrichmentData(title, author, window.currentBookISBN);
    }

    // Update modal header with book title and ISBN information
    function updateModalHeader(title, isbn) {
        // Update book title
        $('#enrichment-book-title').text(title || 'Unknown Title');

        // Process ISBN information
        let isbn13 = '-', isbn10 = '-';

        if (isbn && typeof isbn === 'string') {
            const cleanISBN = isbn.replace(/[^0-9X]/gi, '');
            if (cleanISBN.length === 13) {
                isbn13 = cleanISBN;
                // Try to convert to ISBN-10
                if (cleanISBN.startsWith('978')) {
                    const isbn10Digits = cleanISBN.substring(3, 12);
                    let sum = 0;
                    for (let i = 0; i < 9; i++) {
                        sum += parseInt(isbn10Digits[i]) * (10 - i);
                    }
                    const checkDigit = (11 - (sum % 11)) % 11;
                    isbn10 = isbn10Digits + (checkDigit === 10 ? 'X' : checkDigit);
                }
            } else if (cleanISBN.length === 10) {
                isbn10 = cleanISBN;
                // Convert to ISBN-13
                const isbn13Prefix = '978' + cleanISBN.substring(0, 9);
                let sum = 0;
                for (let i = 0; i < 12; i++) {
                    sum += parseInt(isbn13Prefix[i]) * (i % 2 === 0 ? 1 : 3);
                }
                const checkDigit = (10 - (sum % 10)) % 10;
                isbn13 = isbn13Prefix + checkDigit;
            }
        }

        // Update ISBN displays
        $('#enrichment-isbn13').text(isbn13);
        $('#enrichment-isbn10').text(isbn10);

        // Add conversion verification using backend functions
        if (isbn13 !== '-' && isbn10 !== '-') {
            // Call backend to verify conversions
            $.post('book-import-validate/ajax/data-enrichment-ajax.php', {
                action: 'verify_isbn_conversion',
                isbn13: isbn13,
                isbn10: isbn10
            }, function(response) {
                if (response.success) {
                    let verificationText = '';
                    if (response.isbn13_converted && response.isbn13_converted !== isbn13) {
                        verificationText += `Converted from ISBN-10: ${response.isbn13_converted} `;
                    }
                    if (response.isbn10_converted && response.isbn10_converted !== isbn10) {
                        verificationText += `Converted from ISBN-13: ${response.isbn10_converted}`;
                    }
                    if (verificationText) {
                        $('#enrichment-isbn-converted').text(`Verification: ${verificationText}`);
                    } else {
                        $('#enrichment-isbn-converted').text('✓ Conversion verified');
                    }
                }
            }, 'json').fail(function() {
                $('#enrichment-isbn-converted').text('Conversion verification unavailable');
            });
        }

        // Show the identifiers section
        $('#enrichment-book-identifiers').show();
    }

    function fetchEnrichmentData(title, author, currentISBN) {
        console.log('Fetching enrichment data for:', { title, author, currentISBN, bookId: window.currentBookId });

        $.ajax({
            url: 'book-import-validate/ajax/data-enrichment-ajax.php',
            method: 'POST',
            data: {
                action: 'get_enrichment_data',
                title: title,
                author: author,
                current_isbn: currentISBN,
                book_id: window.currentBookId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Enrichment response:', response);
                $('#enrichment-loading').hide();
                if (response.success) {
                    window.currentEnrichmentData = response.data;
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

        // Update status badges to show completion
        updateStatusBadges(data.sources_checked);

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

        // Auto-select fields with single source and beneficial updates
        autoSelectBeneficialFields(data.fields);

        // Debug: Log all field names to see what's available
        console.log('📦 All available fields:', Object.keys(data.fields));
        console.log('📦 Amazon fields check:', ['purchase_links', 'format', 'price_range'].map(f => ({
            field: f,
            exists: !!data.fields[f],
            structure: data.fields[f]
        })));

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

        console.log('📦 Starting AJAX fetch for Amazon data. ISBN:', window.currentBookISBN);

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
            isbn: window.currentBookISBN
        }, function(res) {
            console.log('📦 Amazon AJAX response received:', res);

            if (res.success && res.data && Object.keys(res.data).length > 0) {
                // Integrate Amazon data into the enrichment fields
                updateEnrichmentDataWithAmazon(res.data);
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

    function updateEnrichmentDataWithAmazon(amazonData) {
        console.log('📦 updateEnrichmentDataWithAmazon called with:', amazonData);

        // Merge Amazon data into the current enrichment data
        if (window.currentEnrichmentData && window.currentEnrichmentData.fields) {
            Object.keys(amazonData).forEach(fieldName => {
                const amazonFieldData = amazonData[fieldName];

                // Add or update the field in the enrichment data
                window.currentEnrichmentData.fields[fieldName] = {
                    label: amazonFieldData.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                    current_value: window.currentEnrichmentData.fields[fieldName]?.current_value || null,
                    new_data: amazonFieldData.new_data
                };

                console.log(`📦 Added/updated ${fieldName} field with Amazon data`);
            });

            // Re-render the enrichment fields to include the new Amazon data
            displayEnrichmentFields(window.currentEnrichmentData.fields);
            console.log('📦 Re-rendered enrichment fields with Amazon data');
        } else {
            console.error('📦 No current enrichment data available to merge Amazon data');
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

        // Set up age range and reading level synchronization
        setupAgeRangeReadingLevelSync();
    }

    // Set up synchronization between age range and reading level fields
    function setupAgeRangeReadingLevelSync() {
        // Age range to reading level mapping (matching database values)
        const ageToReadingMap = {
            '0-12 months': 'Pre-literacy (Sensory)',
            '12-24 months': 'Pre-literacy (Naming)',
            '2-3 years': 'Pre-literacy (Mimicry)',
            '3-4 years': 'Early Pre-reader',
            '4-5 years': 'Beginning Reader',
            '5-6 years': 'Early Reader',
            '6-7 years': 'Developing Reader',
            '7-8 years': 'Transitional Reader',
            '8-9 years': 'Fluent Reader',
            '9-10 years': 'Fluent Reader',
            '10-11 years': 'Fluent Reader',
            '11-14 years': 'Advanced Reader',
            '14-16 years': 'Advanced Reader',
            '16-18 years': 'Advanced Reader',
            '18+ years': 'Proficient Reader',
            // Common variations that might come from APIs
            '5-6 years': 'Early Reader',
            'All Ages': 'Early Reader'
        };

        // Reading level to age range mapping (including common API values)
        const readingToAgeMap = {
            'Pre-literacy (Sensory)': '0-12 months',
            'Pre-literacy (Naming)': '12-24 months',
            'Pre-literacy (Mimicry)': '2-3 years',
            'Early Pre-reader': '3-4 years',
            'Beginning Reader': '4-5 years',
            'Early Reader': '5-6 years',
            'Developing Reader': '6-7 years',
            'Transitional Reader': '7-8 years',
            'Fluent Reader': '9-10 years',
            'Advanced Reader': '11-14 years',
            'Proficient Reader': '18+ years',
            // Common API variations
            'Middle Grade': '9-10 years',
            'Young Adult': '14-16 years',
            'Adult': '18+ years',
            'All Ages': '5-6 years'
        };

        // Listen for changes in age range selections
        $(document).on('change', 'input[name="field_age_range_option"], input[name="field_age_range"]', function() {
            console.log('Age range field changed:', $(this).attr('name'), $(this).val(), $(this).is(':checked'));
            if ($(this).is(':checked')) {
                const selectedAgeRange = getSelectedFieldValue('age_range');
                console.log('Selected age range:', selectedAgeRange);
                if (selectedAgeRange && ageToReadingMap[selectedAgeRange]) {
                    const expectedReading = ageToReadingMap[selectedAgeRange];
                    console.log('Expected reading level:', expectedReading);
                    syncReadingLevelField(expectedReading);
                } else {
                    console.log('No mapping found for age range:', selectedAgeRange);
                }
            }
        });

        // Listen for changes in reading level selections
        $(document).on('change', 'input[name="field_reading_level_option"], input[name="field_reading_level"]', function() {
            console.log('Reading level field changed:', $(this).attr('name'), $(this).val(), $(this).is(':checked'));
            if ($(this).is(':checked')) {
                const selectedReadingLevel = getSelectedFieldValue('reading_level');
                console.log('Selected reading level:', selectedReadingLevel);
                if (selectedReadingLevel && readingToAgeMap[selectedReadingLevel]) {
                    const expectedAge = readingToAgeMap[selectedReadingLevel];
                    console.log('Expected age range:', expectedAge);
                    syncAgeRangeField(expectedAge);
                } else {
                    console.log('No mapping found for reading level:', selectedReadingLevel);
                }
            }
        });
    }

    // Get the currently selected value for a field
    function getSelectedFieldValue(fieldName) {
        console.log('Getting selected value for field:', fieldName);

        const checkedOption = $(`input[name="field_${fieldName}_option"]:checked`);
        console.log('Checked option elements found:', checkedOption.length);

        if (checkedOption.length > 0) {
            const optionIndex = parseInt(checkedOption.val());
            console.log('Selected option index:', optionIndex);

            const fieldData = window.currentEnrichmentData.fields[fieldName];
            if (fieldData && fieldData.new_data && fieldData.new_data.options) {
                const value = fieldData.new_data.options[optionIndex]?.value;
                console.log('Multi-option field value:', value);
                return value;
            }
        }

        const checkedField = $(`input[name="field_${fieldName}"]:checked`);
        console.log('Checked field elements found:', checkedField.length);

        if (checkedField.length > 0) {
            const fieldData = window.currentEnrichmentData.fields[fieldName];
            const value = fieldData?.new_data?.value;
            console.log('Single field value:', value);
            return value;
        }

        console.log('No value found for field:', fieldName);
        return null;
    }

    // Sync reading level field based on age range
    function syncReadingLevelField(expectedReading) {
        const readingField = window.currentEnrichmentData.fields['reading_level'];
        if (!readingField) return;

        // Check if we have options to select from
        if (readingField.new_data && readingField.new_data.options) {
            // Find matching option
            readingField.new_data.options.forEach((option, index) => {
                if (option.value === expectedReading) {
                    $(`input[name="field_reading_level_option"][value="${index}"]`).prop('checked', true);
                    $(`input[name="field_reading_level"]`).prop('checked', true);
                }
            });
        } else if (readingField.new_data && readingField.new_data.value === expectedReading) {
            // Single option matches
            $(`input[name="field_reading_level"]`).prop('checked', true);
        }
    }

    // Sync age range field based on reading level
    function syncAgeRangeField(expectedAge) {
        const ageField = window.currentEnrichmentData.fields['age_range'];
        if (!ageField) return;

        // Check if we have options to select from
        if (ageField.new_data && ageField.new_data.options) {
            // Find matching option
            ageField.new_data.options.forEach((option, index) => {
                if (option.value === expectedAge) {
                    $(`input[name="field_age_range_option"][value="${index}"]`).prop('checked', true);
                    $(`input[name="field_age_range"]`).prop('checked', true);
                }
            });
        } else if (ageField.new_data && ageField.new_data.value === expectedAge) {
            // Single option matches
            $(`input[name="field_age_range"]`).prop('checked', true);
        }
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

        // Check if new value exactly matches current value
        const hasExactMatch = !isUnknown && !isPendingAmazon &&
                             normalizeValue(field.current_value) === normalizeValue(newData.value) &&
                             normalizeValue(field.current_value) !== '' &&
                             normalizeValue(field.current_value) !== null;

        // Apply exact match styling if found
        const exactMatchClass = hasExactMatch ? ' exact-match' : '';

        // Determine benefit level for color coding
        const benefitLevel = isPendingAmazon ? 'questionable' : determineBenefitLevel(field.current_value, newData.value, isUnknown);
        const benefitClass = getBenefitColorClass(benefitLevel);
        const benefitBorder = getBenefitBorderClass(benefitLevel);

        // Add disabled styling classes
        const disabledClass = (isUnknown || isPendingAmazon || benefitLevel === 'not_beneficial') ? ' disabled-field' : '';
        const labelClass = (isUnknown || isPendingAmazon || benefitLevel === 'not_beneficial') ? ' text-muted' : '';

        return `
            <div class="col-md-6 mb-3">
                <div class="enrichment-field ${benefitBorder}${exactMatchClass}${disabledClass}" data-field="${fieldName}">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               id="field_${fieldName}" name="fields[]" value="${fieldName}" ${isUnknown || isPendingAmazon || benefitLevel === 'not_beneficial' ? 'disabled' : ''}>
                        <label class="form-check-label font-weight-bold${labelClass}" for="field_${fieldName}">
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

    // Update status badges to show completion instead of "Checking..."
    function updateStatusBadges(sourcesChecked) {
        // Update Google Books status
        if (sourcesChecked.includes('google_books')) {
            $('#google-books-status-badge').html('<span class="badge badge-success">✓ Google Books</span>');
        } else {
            $('#google-books-status-badge').html('<span class="badge badge-secondary">Google Books</span>');
        }

        // Update OpenLibrary status
        if (sourcesChecked.includes('open_library')) {
            $('#open-library-status-badge').html('<span class="badge badge-success">✓ OpenLibrary</span>');
        } else {
            $('#open-library-status-badge').html('<span class="badge badge-secondary">OpenLibrary</span>');
        }
    }

    // Auto-select fields with single source and beneficial updates
    function autoSelectBeneficialFields(fields) {
        Object.keys(fields).forEach(fieldName => {
            const field = fields[fieldName];

            // Skip fields with no new data
            if (!field.new_data) return;

            // For single source fields, auto-select if beneficial
            if (!field.new_data.options) {
                const isUnknown = field.new_data.status === 'unknown';
                const isPendingAmazon = field.new_data.status === 'pending_amazon_data';

                if (!isUnknown && !isPendingAmazon) {
                    const benefitLevel = determineBenefitLevel(field.current_value, field.new_data.value, false);

                    // Auto-select beneficial fields or fields where current value is empty
                    if (benefitLevel === 'beneficial' || isEmpty(field.current_value)) {
                        $(`#field_${fieldName}`).prop('checked', true).trigger('change');
                    }
                }
            }
        });
    }

    // Make functions globally available
    window.openDataEnrichmentModal = openDataEnrichmentModal;
    window.fetchEnrichmentData = fetchEnrichmentData;
    window.displayEnrichmentResults = displayEnrichmentResults;
    window.fetchAmazonDataForFields = fetchAmazonDataForFields;
    window.updateEnrichmentDataWithAmazon = updateEnrichmentDataWithAmazon;
    window.displayEnrichmentFields = displayEnrichmentFields;
    window.createSingleSourceField = createSingleSourceField;
    window.updateStatusBadges = updateStatusBadges;
    window.autoSelectBeneficialFields = autoSelectBeneficialFields;
}
