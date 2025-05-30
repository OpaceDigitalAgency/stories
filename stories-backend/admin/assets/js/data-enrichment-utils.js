// Data Enrichment Utility Functions
// Prevents multiple script loading with a guard
if (typeof window.dataEnrichmentUtilsLoaded === 'undefined') {
    window.dataEnrichmentUtilsLoaded = true;

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
                const $badge = $('#goodreads-status-badge');
                if (response.success && response.exists) {
                    $badge.removeClass('badge-secondary badge-warning badge-danger badge-info')
                          .addClass('badge-success')
                          .css({'background-color': '#28a745', 'color': 'white', 'border': 'none'})
                          .text('Goodreads');
                } else {
                    $badge.removeClass('badge-secondary badge-warning badge-success badge-info')
                          .addClass('badge-danger')
                          .css({'background-color': '#dc3545', 'color': 'white', 'border': 'none'})
                          .text('Not on Goodreads');
                }
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

    function applyEnrichmentChanges(bookId, selectedFields) {
        $('#apply-enrichment-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Applying...');

        // Add debugging display
        console.log('🔧 APPLY ENRICHMENT DEBUG:');
        console.log('Book ID:', bookId);
        console.log('Selected Fields:', selectedFields);
        console.log('Fields JSON:', JSON.stringify(selectedFields));

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
                console.log('🔧 Apply enrichment response:', response);

                // Show detailed debugging information on screen
                let debugMessage = '🔧 ENRICHMENT DEBUG RESPONSE:\n\n';
                debugMessage += 'Success: ' + response.success + '\n';
                debugMessage += 'Message: ' + (response.message || 'No message') + '\n';

                if (response.debug) {
                    debugMessage += 'Debug Info: ' + JSON.stringify(response.debug, null, 2) + '\n';
                }

                if (response.updated_fields) {
                    debugMessage += 'Updated Fields: ' + JSON.stringify(response.updated_fields) + '\n';
                }

                if (response.additional_updates) {
                    debugMessage += 'Additional Updates: ' + JSON.stringify(response.additional_updates) + '\n';
                }

                console.log(debugMessage);

                if (response.success) {
                    $('#dataEnrichmentModal').modal('hide');

                    // Show success message with debug info
                    showNotification(`✅ SUCCESS! Updated ${Object.keys(selectedFields).length} field(s)!`, 'success', 3000);
                    console.log('✅ SUCCESS DEBUG:', debugMessage);

                    // Force page refresh with cache busting
                    setTimeout(() => {
                        window.location.href = window.location.href.split('?')[0] + '?_refresh=' + Date.now();
                    }, 2000);
                } else {
                    // Show user-friendly error message on screen
                    const errorMsg = response.message || 'Unknown error';
                    showNotification(`❌ ERROR! ${errorMsg}`, 'danger', 8000);

                    // Also show detailed error in modal
                    const $errorDiv = $('#enrichment-error');
                    const $errorMessage = $('#error-message');
                    $errorMessage.html(`
                        <strong>Error:</strong> ${errorMsg}<br>
                        <small class="text-muted">Check console for detailed debugging information.</small>
                    `);
                    $errorDiv.show();

                    console.log('❌ ERROR DEBUG:', debugMessage);
                    $('#apply-enrichment-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Apply Selected Changes');
                }
            },
            error: function(xhr, status, error) {
                console.error('🔧 AJAX error:', error);
                console.error('🔧 XHR response:', xhr.responseText);

                let errorMessage = '🔧 AJAX ERROR DEBUG:\n\n';
                errorMessage += 'Status: ' + status + '\n';
                errorMessage += 'Error: ' + error + '\n';
                errorMessage += 'Response Text: ' + xhr.responseText + '\n';

                showNotification(`❌ AJAX ERROR! ${error}`, 'danger', 8000);
                console.log('❌ AJAX ERROR DEBUG:', errorMessage);
                $('#apply-enrichment-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Apply Selected Changes');
            }
        });
    }

    /**
     * Check if two values are exactly the same
     * Handles special cases like JSON objects, formatted text, etc.
     */
    function isExactMatch(currentValue, newValue) {
        // Handle null/undefined cases
        if (isEmpty(currentValue) && isEmpty(newValue)) {
            return true;
        }
        if (isEmpty(currentValue) || isEmpty(newValue)) {
            return false;
        }

        // Special handling for purchase_links (JSON vs display format)
        if (typeof newValue === 'object' && typeof currentValue === 'string') {
            // Parse current value as purchase links display format
            try {
                const currentParsed = parsePurchaseLinksDisplay(currentValue);
                const newParsed = normalizePurchaseLinks(newValue);
                return JSON.stringify(currentParsed) === JSON.stringify(newParsed);
            } catch (e) {
                console.log('🔍 Purchase links comparison failed:', e);
            }
        }

        // Special handling for tags/genres (order-independent comparison)
        if (typeof currentValue === 'object' && Array.isArray(currentValue) &&
            typeof newValue === 'string') {
            // Current value is array of tags, new value is string
            return compareTagArrayToString(currentValue, newValue);
        }

        if (typeof currentValue === 'string' && typeof newValue === 'string') {
            // Check if these look like tag lists
            if (currentValue.includes('Fiction') || newValue.includes('Fiction') ||
                currentValue.includes('Children') || newValue.includes('Children') ||
                currentValue.includes('Africa') || newValue.includes('Africa')) {
                return compareTagLists(currentValue, newValue);
            }
        }

        // For JSON objects (like purchase_links), compare the actual data
        if (typeof currentValue === 'object' && typeof newValue === 'object') {
            return JSON.stringify(currentValue) === JSON.stringify(newValue);
        }

        // For JSON strings, parse and compare
        if (typeof currentValue === 'string' && typeof newValue === 'string') {
            // Try to parse as JSON first
            try {
                const currentParsed = JSON.parse(currentValue);
                const newParsed = JSON.parse(newValue);
                return JSON.stringify(currentParsed) === JSON.stringify(newParsed);
            } catch (e) {
                // Not JSON, continue with string comparison
            }
        }

        // For numbers, handle string vs number comparison
        if ((typeof currentValue === 'number' || !isNaN(currentValue)) &&
            (typeof newValue === 'number' || !isNaN(newValue))) {
            return parseFloat(currentValue) === parseFloat(newValue);
        }

        // For strings, normalize and compare (ENHANCED for ISBNs and text)
        if (typeof currentValue === 'string' && typeof newValue === 'string') {
            const normalize = (str) => {
                return str.trim()
                    .toLowerCase()
                    .replace(/[-\s]/g, '') // Remove hyphens and spaces (for ISBNs)
                    .replace(/\s+/g, ' '); // Normalize remaining whitespace
            };
            return normalize(currentValue) === normalize(newValue);
        }

        // Default comparison
        return currentValue === newValue;
    }

    /**
     * Parse purchase links display format into normalized object
     */
    function parsePurchaseLinksDisplay(displayText) {
        const links = {};
        const lines = displayText.split('\n');

        for (const line of lines) {
            const match = line.match(/^([^:]+):\s*(.+)$/);
            if (match) {
                const format = match[1].trim();
                const price = match[2].trim();
                links[format] = { price: price };
            }
        }

        return links;
    }

    /**
     * Normalize purchase links object for comparison
     */
    function normalizePurchaseLinks(linksObj) {
        const normalized = {};

        for (const [format, data] of Object.entries(linksObj)) {
            normalized[format] = { price: data.price };
        }

        return normalized;
    }

    /**
     * Compare tag array to concatenated string
     */
    function compareTagArrayToString(currentArray, newString) {
        console.log('🏷️ Comparing tag array to string:', { currentArray, newString });

        // Normalize current array tags
        const currentTags = currentArray.map(tag => tag.toLowerCase().trim()).sort();

        // Split the new string by CamelCase and normalize
        const newTags = splitTagString(newString).map(tag => tag.toLowerCase().trim()).sort();

        console.log('🏷️ Normalized comparison:', { currentTags, newTags });

        // Check if arrays contain the same elements
        const isEqual = currentTags.length === newTags.length &&
                       currentTags.every(tag => newTags.includes(tag)) &&
                       newTags.every(tag => currentTags.includes(tag));

        console.log('🏷️ Arrays equal:', isEqual);
        return isEqual;
    }

    /**
     * Split concatenated tag string by CamelCase
     */
    function splitTagString(str) {
        // Split by capital letters that follow lowercase letters (CamelCase boundaries)
        // But preserve apostrophes and common patterns
        let tags = str.replace(/([a-z])([A-Z])/g, '$1|$2')
                     .split('|')
                     .map(tag => tag.trim())
                     .filter(tag => tag.length > 0);

        // Further split by common separators if they exist
        const furtherSplit = [];
        for (const tag of tags) {
            if (tag.includes(',') || tag.includes(';')) {
                furtherSplit.push(...tag.split(/[,;]/).map(t => t.trim()).filter(t => t.length > 0));
            } else {
                furtherSplit.push(tag);
            }
        }

        return furtherSplit;
    }

    /**
     * Compare tag lists (order-independent)
     */
    function compareTagLists(current, newValue) {
        console.log('🔍 Comparing tag lists:', { current, newValue });

        const currentTags = splitTagString(current).map(tag => tag.toLowerCase().trim()).sort();
        const newTags = splitTagString(newValue).map(tag => tag.toLowerCase().trim()).sort();

        console.log('🔍 Parsed tags:', {
            currentRaw: current,
            newRaw: newValue,
            currentParsed: currentTags,
            newParsed: newTags
        });

        // Check if arrays contain the same elements
        const isEqual = currentTags.length === newTags.length &&
                       currentTags.every(tag => newTags.includes(tag)) &&
                       newTags.every(tag => currentTags.includes(tag));

        console.log('🔍 Tags equal:', isEqual);
        return isEqual;
    }

    /**
     * Determine the benefit level of updating a field
     * @param {*} currentValue - Current value in database
     * @param {*} newValue - New value from API
     * @param {boolean} isUnknown - Whether new value is unknown
     * @returns {string} - 'beneficial', 'questionable', 'not_beneficial', 'exact_match'
     */
    function determineBenefitLevel(currentValue, newValue, isUnknown) {
        // If new value is unknown or null, it's not beneficial
        if (isUnknown || !newValue || newValue === 'Unknown' || newValue === 'null' || newValue === '') {
            return 'not_beneficial';
        }

        // Check for exact matches first
        if (isExactMatch(currentValue, newValue)) {
            return 'exact_match';
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
            case 'exact_match':
                return 'bg-light-info'; // Pale blue for exact matches
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
            case 'exact_match':
                return 'border-info';
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
            case 'exact_match':
                return '<span class="badge badge-info ml-1" title="Matches database exactly"><i class="fas fa-check-double"></i></span>';
            default:
                return '';
        }
    }

    // Event handlers for the modal
    $(document).ready(function() {
        // Apply enrichment changes
        $('#apply-enrichment-btn').click(function() {
            const selectedFields = {};
            $('.field-checkbox:checked').each(function() {
                const fieldName = $(this).val();
                const fieldData = window.currentEnrichmentData.fields[fieldName];

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
            applyEnrichmentChanges(window.currentBookId, selectedFields);
        });

        // Fix All button - selects all fields and applies them
        $('#fix-all-btn').click(function() {
            if (!window.currentEnrichmentData || !window.currentEnrichmentData.fields) {
                alert('No enrichment data available.');
                return;
            }

            // Select all checkboxes (except unknown fields)
            $('.field-checkbox:not(:disabled)').prop('checked', true).trigger('change');

            // For fields with multiple options, auto-select the highest confidence option
            Object.keys(window.currentEnrichmentData.fields).forEach(fieldName => {
                const fieldData = window.currentEnrichmentData.fields[fieldName];

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
                const fieldData = window.currentEnrichmentData.fields[fieldName];

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
            applyEnrichmentChanges(window.currentBookId, selectedFields);
        });
    });

    // Make functions globally available
    window.checkGoodreadsStatus = checkGoodreadsStatus;
    window.showEnrichmentError = showEnrichmentError;
    window.applyEnrichmentChanges = applyEnrichmentChanges;
    window.determineBenefitLevel = determineBenefitLevel;
    window.isExactMatch = isExactMatch;
    window.isEmpty = isEmpty;
    window.normalizeValue = normalizeValue;
    window.getBenefitColorClass = getBenefitColorClass;
    window.getBenefitBorderClass = getBenefitBorderClass;
    window.getBenefitIndicator = getBenefitIndicator;
}
