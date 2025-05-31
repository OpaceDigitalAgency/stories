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
        console.log('SAVE_TEST: Starting AJAX request to apply enrichment');
        console.log('SAVE_TEST: Book ID:', bookId);
        console.log('SAVE_TEST: Selected Fields:', selectedFields);
        console.log('SAVE_TEST: Fields JSON:', JSON.stringify(selectedFields));

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
                console.log('SAVE_TEST: Server response received:', response);

                // Show detailed debugging information on screen
                let debugMessage = 'SAVE_TEST: SERVER RESPONSE DETAILS:\n\n';
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

                if (response.debug) {
                    debugMessage += 'CRITICAL DEBUG INFO:\n';
                    debugMessage += 'Affected Rows: ' + response.debug.affected_rows + '\n';
                    debugMessage += 'Book ID: ' + response.debug.book_id + '\n';
                    debugMessage += 'SQL: ' + response.debug.sql + '\n';
                    debugMessage += 'Params: ' + JSON.stringify(response.debug.params) + '\n';
                    debugMessage += 'Existing Book Data: ' + JSON.stringify(response.debug.existing_book_data) + '\n';
                }

                if (response.debug_error) {
                    debugMessage += 'EXCEPTION ERROR INFO:\n';
                    debugMessage += 'Error Message: ' + response.debug_error.message + '\n';
                    debugMessage += 'Error File: ' + response.debug_error.file + '\n';
                    debugMessage += 'Error Line: ' + response.debug_error.line + '\n';
                    debugMessage += 'Action: ' + response.debug_error.action + '\n';
                }

                console.log(debugMessage);

                if (response.success) {
                    // DEBUGGING: Keep modal open to preserve console logs
                    console.log('SAVE_TEST: 🔍 Modal kept open for debugging - check console for all logs');
                    // $('#dataEnrichmentModal').modal('hide');

                    // Show success message with debug info
                    showNotification(`✅ SUCCESS! Updated ${Object.keys(selectedFields).length} field(s)!`, 'success', 3000);
                    console.log('SAVE_TEST: SUCCESS - Fields updated successfully:', debugMessage);

                    // DEBUGGING: Temporarily disable redirect to preserve console logs
                    console.log('SAVE_TEST: 🚀 SUCCESS! Redirect disabled for debugging - check console for transaction logs');

                    // Re-enable the button since redirect is disabled for debugging
                    $('#apply-enrichment-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Apply Selected Changes');

                    // setTimeout(() => {
                    //     window.location.href = window.location.href.split('?')[0] + '?_refresh=' + Date.now();
                    // }, 2000);
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
                console.error('SAVE_TEST: AJAX error occurred:', error);
                console.error('SAVE_TEST: XHR response:', xhr.responseText);

                let errorMessage = 'SAVE_TEST: AJAX ERROR DETAILS:\n\n';
                errorMessage += 'Status: ' + status + '\n';
                errorMessage += 'Error: ' + error + '\n';
                errorMessage += 'Response Text: ' + xhr.responseText + '\n';

                showNotification(`❌ AJAX ERROR! ${error}`, 'danger', 8000);
                console.log('SAVE_TEST: AJAX ERROR FULL DEBUG:', errorMessage);
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

        // CRITICAL FIX: Enhanced purchase links comparison
        if ((typeof newValue === 'object' && typeof currentValue === 'string') ||
            (typeof newValue === 'string' && typeof currentValue === 'string' &&
             (newValue.includes('£') || currentValue.includes('£') ||
              newValue.includes('$') || currentValue.includes('$')))) {
            // Parse current value as purchase links display format
            try {
                console.log('🛒 PURCHASE_LINKS_DEBUG: Comparing purchase links:', {
                    currentValue: currentValue,
                    newValue: newValue,
                    currentType: typeof currentValue,
                    newType: typeof newValue
                });

                let currentParsed, newParsed;

                if (typeof currentValue === 'string') {
                    currentParsed = parsePurchaseLinksDisplay(currentValue);
                } else {
                    currentParsed = normalizePurchaseLinks(currentValue);
                }

                if (typeof newValue === 'string') {
                    newParsed = parsePurchaseLinksDisplay(newValue);
                } else {
                    newParsed = normalizePurchaseLinks(newValue);
                }

                console.log('🛒 PURCHASE_LINKS_DEBUG: Parsed values:', {
                    currentParsed: currentParsed,
                    newParsed: newParsed
                });

                // Compare the normalized objects (order-independent)
                const isEqual = comparePurchaseLinksObjects(currentParsed, newParsed);
                console.log('🛒 PURCHASE_LINKS_DEBUG: Are equal:', isEqual);
                return isEqual;
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

        // For JSON strings, parse and compare (FIXED: order-independent comparison)
        if (typeof currentValue === 'string' && typeof newValue === 'string') {
            // Try to parse as JSON first
            try {
                const currentParsed = JSON.parse(currentValue);
                const newParsed = JSON.parse(newValue);

                // CRITICAL FIX: Use order-independent comparison for purchase links
                if (isPurchaseLinksObject(currentParsed) && isPurchaseLinksObject(newParsed)) {
                    console.log('🛒 PURCHASE_LINKS_FIX: Using order-independent comparison');
                    console.log('🛒 PURCHASE_LINKS_FIX: currentParsed:', currentParsed);
                    console.log('🛒 PURCHASE_LINKS_FIX: newParsed:', newParsed);
                    const result = comparePurchaseLinksObjects(currentParsed, newParsed);
                    console.log('🛒 PURCHASE_LINKS_FIX: comparison result:', result);
                    return result;
                }

                // For other JSON objects, use normalized comparison
                return JSON.stringify(normalizeObjectForComparison(currentParsed)) ===
                       JSON.stringify(normalizeObjectForComparison(newParsed));
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
     * CRITICAL FIX: Enhanced parsing for various display formats
     */
    function parsePurchaseLinksDisplay(displayText) {
        const links = {};

        // Handle different display formats
        if (typeof displayText === 'string') {
            // Split by newlines or common separators
            const lines = displayText.split(/[\n\r]+/).filter(line => line.trim());

            for (const line of lines) {
                // Match patterns like "Kindle: £3.19" or "Paperback: £2.99 Default"
                const match = line.match(/^([^:]+):\s*([£$€¥]\d+\.?\d*)\s*(.*)?$/);
                if (match) {
                    const format = match[1].trim();
                    const price = match[2].trim();
                    const extra = match[3] ? match[3].trim() : '';

                    links[format] = {
                        price: price,
                        is_selected: extra.toLowerCase().includes('default')
                    };
                }
            }
        }

        console.log('🛒 Parsed purchase links from display:', { displayText, links });
        return links;
    }

    /**
     * Compare two purchase links objects (order-independent)
     */
    function comparePurchaseLinksObjects(obj1, obj2) {
        const keys1 = Object.keys(obj1).sort();
        const keys2 = Object.keys(obj2).sort();

        // Check if they have the same number of keys
        if (keys1.length !== keys2.length) {
            console.log('🛒 Different number of purchase options:', keys1.length, 'vs', keys2.length);
            return false;
        }

        // Check if all keys match
        if (!keys1.every(key => keys2.includes(key))) {
            console.log('🛒 Different purchase option keys:', keys1, 'vs', keys2);
            return false;
        }

        // Check if all values match
        for (const key of keys1) {
            const val1 = obj1[key];
            const val2 = obj2[key];

            // Compare prices (normalize currency symbols)
            const price1 = val1.price ? val1.price.replace(/[£$€¥]/g, '').trim() : '';
            const price2 = val2.price ? val2.price.replace(/[£$€¥]/g, '').trim() : '';

            if (price1 !== price2) {
                console.log('🛒 Different prices for', key, ':', price1, 'vs', price2);
                return false;
            }

            // Compare default status (optional)
            const isDefault1 = val1.is_selected || false;
            const isDefault2 = val2.is_selected || false;

            if (isDefault1 !== isDefault2) {
                console.log('🛒 Different default status for', key, ':', isDefault1, 'vs', isDefault2);
                return false;
            }
        }

        console.log('🛒 Purchase links objects are identical');
        return true;
    }

    /**
     * Check if an object looks like a purchase links object
     */
    function isPurchaseLinksObject(obj) {
        if (!obj || typeof obj !== 'object') {
            console.log('🛒 isPurchaseLinksObject: Not an object:', obj);
            return false;
        }

        // Check if it has the structure of purchase links (format names as keys with price/url/is_selected)
        const keys = Object.keys(obj);
        if (keys.length === 0) {
            console.log('🛒 isPurchaseLinksObject: Empty object');
            return false;
        }

        // Check if at least one key has the expected structure
        const result = keys.some(key => {
            const item = obj[key];
            const hasExpectedStructure = item && typeof item === 'object' &&
                   (item.hasOwnProperty('price') || item.hasOwnProperty('url') || item.hasOwnProperty('is_selected'));
            console.log(`🛒 isPurchaseLinksObject: Key '${key}' has expected structure:`, hasExpectedStructure, item);
            return hasExpectedStructure;
        });

        console.log('🛒 isPurchaseLinksObject: Final result:', result);
        return result;
    }

    /**
     * Normalize any object for comparison (sorts keys recursively)
     */
    function normalizeObjectForComparison(obj) {
        if (obj === null || typeof obj !== 'object') {
            return obj;
        }

        if (Array.isArray(obj)) {
            return obj.map(normalizeObjectForComparison);
        }

        const normalized = {};
        const sortedKeys = Object.keys(obj).sort();

        for (const key of sortedKeys) {
            normalized[key] = normalizeObjectForComparison(obj[key]);
        }

        return normalized;
    }

    /**
     * Normalize purchase links object for comparison
     */
    function normalizePurchaseLinks(linksObj) {
        const normalized = {};

        for (const [format, data] of Object.entries(linksObj)) {
            normalized[format] = {
                price: data.price,
                is_selected: data.is_selected || false
            };
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
     * CRITICAL FIX: Enhanced to handle exact case from user's issue
     */
    function compareTagLists(current, newValue) {
        console.log('🏷️ CRITICAL_TAG_FIX: Comparing tag lists:', { current, newValue });

        // Handle the exact case from user's issue
        const currentTags = splitTagString(current).map(tag => tag.toLowerCase().trim()).filter(tag => tag.length > 0).sort();
        const newTags = splitTagString(newValue).map(tag => tag.toLowerCase().trim()).filter(tag => tag.length > 0).sort();

        console.log('🏷️ CRITICAL_TAG_FIX: Parsed tags:', {
            currentRaw: current,
            newRaw: newValue,
            currentParsed: currentTags,
            newParsed: newTags,
            currentLength: currentTags.length,
            newLength: newTags.length
        });

        // Check if arrays contain the same elements (order-independent)
        const isEqual = currentTags.length === newTags.length &&
                       currentTags.every(tag => newTags.includes(tag)) &&
                       newTags.every(tag => currentTags.includes(tag));

        console.log('🏷️ CRITICAL_TAG_FIX: Tags equal:', isEqual);

        // If not equal, show detailed comparison for debugging
        if (!isEqual) {
            const onlyInCurrent = currentTags.filter(tag => !newTags.includes(tag));
            const onlyInNew = newTags.filter(tag => !currentTags.includes(tag));
            console.log('🏷️ CRITICAL_TAG_FIX: Differences found:', {
                onlyInCurrent: onlyInCurrent,
                onlyInNew: onlyInNew
            });
        }

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
            console.log('SAVE_TEST: Apply button clicked!');
            console.log('SAVE_TEST: Current enrichment data:', window.currentEnrichmentData);
            console.log('SAVE_TEST: Current book ID:', window.currentBookId);

            const selectedFields = {};
            $('.field-checkbox:checked').each(function() {
                const fieldName = $(this).val();
                const fieldData = window.currentEnrichmentData.fields[fieldName];

                console.log(`SAVE_TEST: Processing field: ${fieldName}`, fieldData);

                // Handle multi-source fields
                if (fieldData.new_data && fieldData.new_data.options) {
                    const selectedOption = $(`input[name="field_${fieldName}_option"]:checked`);
                    console.log(`SAVE_TEST: Multi-source field ${fieldName} - found ${selectedOption.length} selected radio buttons`);

                    if (selectedOption.length > 0) {
                        const optionIndex = parseInt(selectedOption.val());
                        console.log(`SAVE_TEST: Selected option index for ${fieldName}:`, optionIndex);
                        console.log(`SAVE_TEST: Available options for ${fieldName}:`, fieldData.new_data.options);

                        if (fieldData.new_data.options[optionIndex]) {
                            const optionValue = fieldData.new_data.options[optionIndex].value;

                            // CRITICAL FIX: Don't include options with null/unknown values
                            if (optionValue === null || optionValue === undefined ||
                                fieldData.new_data.options[optionIndex].source === 'unknown') {
                                console.log(`SAVE_TEST: SKIPPING multi-source field ${fieldName} - selected option has null/unknown value:`, fieldData.new_data.options[optionIndex]);
                                return; // Skip this field
                            }

                            selectedFields[fieldName] = {
                                value: optionValue,
                                source: fieldData.new_data.options[optionIndex].source,
                                confidence: fieldData.new_data.options[optionIndex].confidence
                            };
                            console.log(`SAVE_TEST: Multi-source field ${fieldName}:`, selectedFields[fieldName]);
                        } else {
                            console.log(`SAVE_TEST: ERROR - Option index ${optionIndex} not found in options array for ${fieldName}`);
                        }
                    } else {
                        console.log(`SAVE_TEST: No radio button selected for multi-source field ${fieldName}`);
                    }
                } else if (fieldData.new_data) {
                    // CRITICAL FIX: Don't include fields with null/unknown values
                    if (fieldData.new_data.value === null || fieldData.new_data.value === undefined ||
                        fieldData.new_data.source === 'unknown') {
                        console.log(`SAVE_TEST: SKIPPING field ${fieldName} - has null/unknown value:`, fieldData.new_data);
                        return; // Skip this field
                    }
                    selectedFields[fieldName] = fieldData.new_data;
                    console.log(`SAVE_TEST: Single-source field ${fieldName}:`, selectedFields[fieldName]);
                }
            });

            console.log('SAVE_TEST: Final selected fields to apply:', selectedFields);

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
    window.parsePurchaseLinksDisplay = parsePurchaseLinksDisplay;
    window.normalizePurchaseLinks = normalizePurchaseLinks;
    window.comparePurchaseLinksObjects = comparePurchaseLinksObjects;
    window.isPurchaseLinksObject = isPurchaseLinksObject;
    window.normalizeObjectForComparison = normalizeObjectForComparison;
}
