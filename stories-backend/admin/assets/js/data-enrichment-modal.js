// Data Enrichment Modal JavaScript
// Prevents multiple script loading with a guard
if (typeof window.dataEnrichmentModalLoaded === 'undefined') {
    window.dataEnrichmentModalLoaded = true;

    /**
     * Fetch ISBN data from database and update modal header
     */
    function fetchBookISBNsFromDatabase(bookId) {
        if (!bookId) {
            console.log('📚 No book ID provided for ISBN fetch');
            return;
        }

        console.log('📚 Fetching ISBNs for book ID:', bookId);

        $.ajax({
            url: 'book-import-validate/ajax/data-enrichment-ajax.php',
            method: 'POST',
            data: {
                action: 'get_book_isbns',
                book_id: bookId
            },
            dataType: 'json',
            success: function(response) {
                console.log('📚 ISBN fetch response:', response);

                if (response.success) {
                    // Update modal header with actual ISBN values and book title
                    const isbn13 = response.isbn_13 || '-';
                    const isbn10 = response.isbn_10 || '-';
                    const title = response.title || 'Unknown Title';

                    // Update book title in modal header
                    $('#enrichment-book-title').text(title);
                    console.log('📚 Updated book title to:', title);

                    // Update ISBN display in modal header
                    $('#enrichment-isbn13').text(isbn13);
                    $('#enrichment-isbn10').text(isbn10);

                    // Calculate and display verified ISBN-10 value using conversion
                    if (isbn13 !== '-') {
                        const cleanISBN13 = isbn13.replace(/[^0-9X]/gi, '');
                        console.log('📚 Attempting ISBN-13 to ISBN-10 conversion:', isbn13, '→ cleaned:', cleanISBN13);
                        if (cleanISBN13.length === 13) {
                            const verifiedISBN10 = convertISBN13ToISBN10(cleanISBN13);
                            $('#enrichment-isbn10-verified').text(verifiedISBN10 || '-');
                            console.log('📚 ✅ ISBN-10 verified value set to:', verifiedISBN10);
                        } else {
                            $('#enrichment-isbn10-verified').text('-');
                            console.log('📚 ⚠️ ISBN-13 length not 13 after cleaning:', cleanISBN13.length);
                        }
                    } else {
                        $('#enrichment-isbn10-verified').text('-');
                        console.log('📚 ⚠️ No ISBN-13 available for conversion');
                    }

                    // Show the identifiers section
                    $('#enrichment-book-identifiers').show();

                    console.log('📚 ✅ ISBN data and title updated in modal header');
                } else {
                    console.log('📚 ⚠️ No ISBN data found or error:', response.message);
                    // Still show the section but with dashes
                    $('#enrichment-isbn13').text('-');
                    $('#enrichment-isbn10').text('-');
                    $('#enrichment-isbn-converted').text('-');
                    $('#enrichment-book-identifiers').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('📚 ❌ Error fetching ISBN data:', error);
                console.error('📚 Response status:', xhr.status);
                console.error('📚 Response text:', xhr.responseText);
                console.error('📚 Request URL was:', 'book-import-validate/ajax/data-enrichment-ajax.php');
                // Still show the section but with dashes
                $('#enrichment-isbn13').text('-');
                $('#enrichment-isbn10').text('-');
                $('#enrichment-isbn-converted').text('Error loading');
                $('#enrichment-book-identifiers').show();
            }
        });
    }

    // Global variables for enrichment modal - make them truly global
    window.currentEnrichmentData = null;
    window.currentBookId = null;
    window.currentBookISBN = null;

    function openDataEnrichmentModal(bookId, title, author, currentISBN = '') {
        console.log('🚀 openDataEnrichmentModal called with:', { bookId, title, author, currentISBN, type: typeof currentISBN });

        window.currentBookId = bookId;
        window.currentBookISBN = String(currentISBN || ''); // Ensure it's always a string

        console.log('🚀 Set global variables:', {
            currentBookId: window.currentBookId,
            currentBookISBN: window.currentBookISBN,
            currentISBNType: typeof window.currentBookISBN
        });

        // Fetch and display ISBN values from database immediately
        // Fetch ISBN data from database for modal header
        fetchBookISBNsFromDatabase(bookId);

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
        console.log('📖 updateModalHeader called with:', { title, isbn, isbnType: typeof isbn });

        // Update book title
        $('#enrichment-book-title').text(title || 'Unknown Title');
        console.log('📖 Updated book title to:', title || 'Unknown Title');

        // Process ISBN information - handle both string and object formats
        let isbn13 = '-', isbn10 = '-';

        // Handle different ISBN input formats
        if (isbn) {
            // Handle object format from database (e.g., {isbn13: "...", isbn10: "..."})
            if (typeof isbn === 'object' && isbn !== null) {
                console.log('📖 Processing ISBN object:', isbn);
                if (isbn.isbn13 && isbn.isbn13 !== '' && isbn.isbn13 !== '-') {
                    isbn13 = formatISBN13(isbn.isbn13.replace(/[^0-9X]/gi, ''));
                }
                if (isbn.isbn10 && isbn.isbn10 !== '' && isbn.isbn10 !== '-') {
                    isbn10 = formatISBN10(isbn.isbn10.replace(/[^0-9X]/gi, ''));
                }
            }
            // Handle string format
            else if (typeof isbn === 'string' && isbn.trim() !== '' && isbn !== '-') {
                console.log('📖 Processing ISBN string:', isbn);
                const cleanISBN = isbn.replace(/[^0-9X]/gi, '');
                console.log('📖 Cleaned ISBN:', cleanISBN, 'Length:', cleanISBN.length);

                if (cleanISBN.length === 13) {
                    isbn13 = formatISBN13(cleanISBN);
                    console.log('📖 Set ISBN-13:', isbn13);
                    // Try to convert to ISBN-10
                    if (cleanISBN.startsWith('978')) {
                        const isbn10Digits = cleanISBN.substring(3, 12);
                        let sum = 0;
                        for (let i = 0; i < 9; i++) {
                            sum += parseInt(isbn10Digits[i]) * (10 - i);
                        }
                        const checkDigit = (11 - (sum % 11)) % 11;
                        isbn10 = formatISBN10(isbn10Digits + (checkDigit === 10 ? 'X' : checkDigit));
                        console.log('📖 Converted to ISBN-10:', isbn10);
                    }
                } else if (cleanISBN.length === 10) {
                    isbn10 = formatISBN10(cleanISBN);
                    console.log('📖 Set ISBN-10:', isbn10);
                    // Convert to ISBN-13
                    const isbn13Prefix = '978' + cleanISBN.substring(0, 9);
                    let sum = 0;
                    for (let i = 0; i < 12; i++) {
                        sum += parseInt(isbn13Prefix[i]) * (i % 2 === 0 ? 1 : 3);
                    }
                    const checkDigit = (10 - (sum % 10)) % 10;
                    isbn13 = formatISBN13(isbn13Prefix + checkDigit);
                    console.log('📖 Converted to ISBN-13:', isbn13);
                } else if (cleanISBN.length > 0) {
                    console.log('📖 ISBN length not 10 or 13, using original value:', isbn);
                    // Use original value if it's not standard length but not empty
                    isbn13 = isbn;
                    isbn10 = isbn;
                } else {
                    console.log('📖 Empty ISBN after cleaning, keeping as dashes');
                }
            } else {
                console.log('📖 Empty or invalid ISBN provided, keeping as dashes');
            }
        } else {
            console.log('📖 No ISBN provided, keeping as dashes');
        }

        // Update ISBN displays immediately
        const $isbn13Element = $('#enrichment-isbn13');
        const $isbn10Element = $('#enrichment-isbn10');
        const $isbn10VerifiedElement = $('#enrichment-isbn10-verified');

        console.log('📖 Setting ISBN displays - ISBN-13:', isbn13, 'ISBN-10:', isbn10);
        console.log('📖 ISBN elements found:', {
            isbn13Element: $isbn13Element.length,
            isbn10Element: $isbn10Element.length,
            isbn10VerifiedElement: $isbn10VerifiedElement.length
        });

        // Debug: Check if modal is actually visible
        console.log('📖 Modal visibility check:', {
            modalExists: $('#dataEnrichmentModal').length,
            modalVisible: $('#dataEnrichmentModal').is(':visible'),
            identifiersSection: $('#enrichment-book-identifiers').length
        });

        if ($isbn13Element.length > 0) {
            $isbn13Element.text(isbn13);
            console.log('📖 Successfully set ISBN-13 display to:', $isbn13Element.text());
        } else {
            console.error('📖 ISBN-13 element not found!');
        }

        if ($isbn10Element.length > 0) {
            $isbn10Element.text(isbn10);
            console.log('📖 Successfully set ISBN-10 display to:', $isbn10Element.text());
        } else {
            console.error('📖 ISBN-10 element not found!');
        }

        // Calculate and display verified ISBN-10 value
        if ($isbn10VerifiedElement.length > 0) {
            let verifiedISBN10 = '-';
            if (isbn13 !== '-' && isbn13.length === 13) {
                // Convert ISBN-13 to ISBN-10 for verification
                const convertedISBN10 = convertISBN13ToISBN10(isbn13.replace(/[^0-9X]/gi, ''));
                if (convertedISBN10) {
                    verifiedISBN10 = convertedISBN10;
                }
            }
            $isbn10VerifiedElement.text(verifiedISBN10);
            console.log('📖 Successfully set ISBN-10 verified value:', verifiedISBN10);
        }

        // Show the identifiers section
        $('#enrichment-book-identifiers').show();

        // Add conversion verification using backend functions
        if (isbn13 !== '-' && isbn10 !== '-') {
            // Call backend to verify conversions
            $.post('book-import-validate/ajax/data-enrichment-ajax.php', {
                action: 'verify_isbn_conversion',
                isbn13: isbn13.replace(/[^0-9X]/gi, ''),
                isbn10: isbn10.replace(/[^0-9X]/gi, '')
            }, function(response) {
                if (response.success) {
                    let verificationText = '';
                    if (response.isbn13_converted && response.isbn13_converted !== isbn13.replace(/[^0-9X]/gi, '')) {
                        verificationText += `Converted from ISBN-10: ${response.isbn13_converted} `;
                    }
                    if (response.isbn10_converted && response.isbn10_converted !== isbn10.replace(/[^0-9X]/gi, '')) {
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
    }

    // Helper function to format ISBN-13 with dashes
    function formatISBN13(isbn) {
        if (!isbn) return '-';
        const cleanISBN = isbn.replace(/[^0-9X]/gi, '');
        if (cleanISBN.length === 13) {
            return `${cleanISBN.substring(0, 3)}-${cleanISBN.substring(3, 4)}-${cleanISBN.substring(4, 6)}-${cleanISBN.substring(6, 12)}-${cleanISBN.substring(12)}`;
        }
        return cleanISBN || '-';
    }

    // Helper function to format ISBN-10 with dashes
    function formatISBN10(isbn) {
        if (!isbn) return '-';
        const cleanISBN = isbn.replace(/[^0-9X]/gi, '');
        if (cleanISBN.length === 10) {
            return `${cleanISBN.substring(0, 1)}-${cleanISBN.substring(1, 3)}-${cleanISBN.substring(3, 9)}-${cleanISBN.substring(9)}`;
        }
        return cleanISBN || '-';
    }

    // Helper function to convert ISBN-13 to ISBN-10
    function convertISBN13ToISBN10(isbn13) {
        if (!isbn13 || isbn13.length !== 13) return null;

        // Remove the 978 prefix and check digit
        const isbn10Base = isbn13.substring(3, 12);

        // Calculate check digit
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(isbn10Base[i]) * (10 - i);
        }

        const checkDigit = (11 - (sum % 11)) % 11;
        const checkChar = checkDigit === 10 ? 'X' : checkDigit.toString();

        return isbn10Base + checkChar;
    }

    // Helper function to convert ISBN-10 to ISBN-13
    function convertISBN10ToISBN13(isbn10) {
        if (!isbn10 || isbn10.length !== 10) return null;

        // Add 978 prefix and remove old check digit
        const isbn13Base = '978' + isbn10.substring(0, 9);

        // Calculate new check digit
        let sum = 0;
        for (let i = 0; i < 12; i++) {
            const digit = parseInt(isbn13Base[i]);
            sum += (i % 2 === 0) ? digit : digit * 3;
        }

        const checkDigit = (10 - (sum % 10)) % 10;

        return isbn13Base + checkDigit.toString();
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
                console.log('🔍 PUBLISHER_DEBUG: Full enrichment response:', response);
                console.log('🔍 PUBLISHER_DEBUG: Publisher field data:', response.data?.fields?.publisher);
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

        // Update Google Books status badge based on available data
        if (data.fields && Object.keys(data.fields).length > 0) {
            // Check if we have Google Books data by looking for fields with google_books source
            const hasGoogleBooksData = Object.values(data.fields).some(field =>
                field.new_data && field.new_data.source === 'google_books'
            );

            if (hasGoogleBooksData) {
                $('#google-books-status-badge').html('<span class="badge badge-success">✓ Google Books</span>');
            } else {
                $('#google-books-status-badge').html('<span class="badge badge-warning">No Data</span>');
            }
        } else {
            $('#google-books-status-badge').html('<span class="badge badge-secondary">No Data</span>');
        }

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
            isbn: window.currentBookISBN,
            book_id: window.currentBookId  // Pass book ID for duplicate prevention
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
            // Store current checkbox states before re-rendering
            const checkboxStates = {};
            const optionStates = {};
            $('.field-checkbox').each(function() {
                const fieldName = $(this).val();
                checkboxStates[fieldName] = $(this).is(':checked');

                // Store selected options for multi-source fields
                const selectedOption = $(`input[name="field_${fieldName}_option"]:checked`);
                if (selectedOption.length > 0) {
                    optionStates[fieldName] = selectedOption.val();
                }
            });
            console.log('📦 Stored checkbox states:', checkboxStates);
            console.log('📦 Stored option states:', optionStates);

            Object.keys(amazonData).forEach(fieldName => {
                const amazonFieldData = amazonData[fieldName];
                const existingField = window.currentEnrichmentData.fields[fieldName];

                console.log(`📦 Processing Amazon field: ${fieldName}`, {
                    amazonData: amazonFieldData,
                    existingField: existingField
                });

                // Skip Amazon data with "unknown" values or "12+" values - don't process these at all
                if (amazonFieldData.new_data.value === 'Unknown' ||
                    amazonFieldData.new_data.value === 'unknown' ||
                    (typeof amazonFieldData.new_data.value === 'string' && amazonFieldData.new_data.value.includes('12+'))) {
                    console.log(`📦 Skipping Amazon field ${fieldName} - value is unknown or contains 12+:`, amazonFieldData.new_data.value);
                    return;
                }

                // CRITICAL FIX: Prevent duplicate ISBN fields completely
                if ((fieldName === 'isbn' || fieldName === 'isbn13')) {
                    console.log(`📦 DUPLICATE_FIX: Processing Amazon ${fieldName} field`);

                    // Check if this ISBN field already exists in the enrichment data
                    if (existingField) {
                        console.log(`📦 DUPLICATE_FIX: ${fieldName} field already exists in data - merging Amazon as additional source`);

                        // If the existing field has the same value as Amazon, skip it entirely
                        if (existingField.current_value === amazonFieldData.new_data.value) {
                            console.log(`📦 DUPLICATE_FIX: Skipping ${fieldName} - Amazon value matches current value exactly`);
                            return; // Skip this field completely
                        }

                        // If existing field has new_data, merge Amazon as additional source
                        if (existingField.new_data) {
                            if (!existingField.new_data.options) {
                                // Convert single source to multi-source
                                const originalData = {
                                    value: existingField.new_data.value,
                                    source: existingField.new_data.source,
                                    confidence: existingField.new_data.confidence,
                                    label: existingField.label
                                };

                                existingField.new_data = {
                                    options: [originalData],
                                    source: existingField.new_data.source
                                };
                            }

                            // Add Amazon as additional source
                            existingField.new_data.options.push({
                                value: amazonFieldData.new_data.value,
                                source: amazonFieldData.new_data.source,
                                confidence: amazonFieldData.new_data.confidence,
                                label: amazonFieldData.label || existingField.label
                            });

                            existingField.new_data.source += ' + amazon';
                            console.log(`📦 DUPLICATE_FIX: Merged Amazon data into existing ${fieldName} field`);
                            return; // Skip creating new field
                        } else {
                            // Field exists but has no new_data (matches database) - don't add Amazon data
                            console.log(`📦 DUPLICATE_FIX: ${fieldName} matches database exactly - skipping Amazon data`);
                            return; // Skip this field completely
                        }
                    } else {
                        // Field doesn't exist in enrichment data - this shouldn't happen for ISBN fields
                        // but if it does, we should not create a new field if it matches current value
                        console.log(`📦 DUPLICATE_FIX: ${fieldName} field not found in enrichment data - this is unexpected`);
                        return; // Skip creating new field to prevent duplicates
                    }
                }

                if (existingField) {
                    // Field already exists - check if it has new_data or is just showing current value
                    console.log(`📦 Field ${fieldName} already exists:`, {
                        hasNewData: !!existingField.new_data,
                        currentValue: existingField.current_value,
                        amazonValue: amazonFieldData.new_data.value
                    });

                    // Skip if Amazon value is the same as current value (no point adding duplicate)
                    if (existingField.current_value === amazonFieldData.new_data.value) {
                        console.log(`📦 Skipping Amazon field ${fieldName} - same as current value`);
                        return;
                    }

                    if (existingField.new_data) {
                        // Field has new_data from other sources - merge Amazon as additional source
                        console.log(`📦 Merging Amazon data with existing field: ${fieldName}`);

                        if (existingField.new_data.options) {
                        // Field already has multiple sources - add Amazon as another option
                        console.log(`📦 Adding Amazon as additional option to multi-source field: ${fieldName}`);
                        existingField.new_data.options.push({
                            value: amazonFieldData.new_data.value,
                            source: amazonFieldData.new_data.source,
                            confidence: amazonFieldData.new_data.confidence,
                            label: amazonFieldData.label || existingField.label,
                            original_value: amazonFieldData.new_data.original_value
                        });

                        // Update source to include Amazon
                        const currentSources = existingField.new_data.source || '';
                        if (!currentSources.includes('amazon')) {
                            existingField.new_data.source = currentSources + ' + amazon';
                        }
                    } else {
                        // Field has single source - convert to multi-source with Amazon
                        console.log(`📦 Converting single-source field to multi-source: ${fieldName}`);
                        const originalData = {
                            value: existingField.new_data.value,
                            source: existingField.new_data.source,
                            confidence: existingField.new_data.confidence,
                            label: existingField.label
                        };

                        existingField.new_data = {
                            options: [
                                originalData,
                                {
                                    value: amazonFieldData.new_data.value,
                                    source: amazonFieldData.new_data.source,
                                    confidence: amazonFieldData.new_data.confidence,
                                    label: amazonFieldData.label || existingField.label,
                                    original_value: amazonFieldData.new_data.original_value
                                }
                            ],
                            source: (originalData.source || 'unknown') + ' + amazon'
                        };
                        }
                    } else {
                        // Field exists but has no new_data (e.g., "Matches Database 100%")
                        // Don't add Amazon data if it's the same as current value (already checked above)
                        console.log(`📦 Field ${fieldName} exists but has no new_data - skipping Amazon merge`);
                        return;
                    }
                } else {
                    // Field doesn't exist or has no data - add Amazon data as new field
                    console.log(`📦 Adding new Amazon field: ${fieldName}`);

                    window.currentEnrichmentData.fields[fieldName] = {
                        label: amazonFieldData.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                        current_value: existingField?.current_value || null,
                        new_data: amazonFieldData.new_data
                    };
                }

                console.log(`📦 Final field structure for ${fieldName}:`, window.currentEnrichmentData.fields[fieldName]);
            });

            // Re-render the enrichment fields to include the new Amazon data
            displayEnrichmentFields(window.currentEnrichmentData.fields);

            // CRITICAL FIX: Update Amazon status badge to show completion
            $('#amazon-status-badge').html('<span class="badge badge-success">✓ Amazon</span>');
            console.log('✅ Updated Amazon status to success');

            // Restore checkbox states after re-rendering
            setTimeout(() => {
                Object.keys(checkboxStates).forEach(fieldName => {
                    if (checkboxStates[fieldName]) {
                        $(`#field_${fieldName}`).prop('checked', true).trigger('change');
                        console.log(`📦 Restored checkbox state for ${fieldName}: checked`);
                    }
                });

                // Restore option states for multi-source fields
                Object.keys(optionStates).forEach(fieldName => {
                    const optionValue = optionStates[fieldName];
                    $(`input[name="field_${fieldName}_option"][value="${optionValue}"]`).prop('checked', true);
                    console.log(`📦 Restored option state for ${fieldName}: option ${optionValue}`);
                });

                console.log('📦 Restored all checkbox and option states after Amazon data integration');
            }, 100);

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

        // Add event listeners for checkbox changes to update apply button
        setTimeout(() => {
            $('.field-checkbox').off('change.applyButton').on('change.applyButton', function() {
                updateApplyButtonState();
            });
        }, 300);

        // Set up age range and reading level synchronization
        setupAgeRangeReadingLevelSync();

        // FIXED: Ensure both fields have synchronized source options before setting up sync
        // Only run if both fields exist and have data
        // TEMPORARILY DISABLED to debug empty modal issue
        // setTimeout(() => {
        //     ensureSynchronizedSourceOptions();
        // }, 500);

        // Debug: Show what fields are actually available
        setTimeout(() => {
            console.log('🔍 DEBUG: All enrichment fields available:', Object.keys(window.currentEnrichmentData?.fields || {}));
            console.log('🔍 DEBUG: Age range field structure:', window.currentEnrichmentData?.fields?.age_range);
            console.log('🔍 DEBUG: Reading level field structure:', window.currentEnrichmentData?.fields?.reading_level);
            console.log('🔍 DEBUG: All age range inputs in DOM:', $('input[name*="age_range"]').length);
            console.log('🔍 DEBUG: All reading level inputs in DOM:', $('input[name*="reading_level"]').length);
        }, 1000);
    }

    // FIXED: Define mapping variables at top level so they're available to all functions
    const ageToReadingMap = {
        '0-12 months': 'Pre-literacy (Sensory)',
        '12-24 months': 'Pre-literacy (Naming)',
        '2-3 years': 'Pre-literacy (Mimicry)',
        '3-4 years': 'Early Pre-reader',
        '4-5 years': 'Beginning Reader',
        '5-6 years': 'Early Reader',        // User's current database value
        '6-7 years': 'Developing Reader',   // FIXED: Correct mapping from database
        '7-8 years': 'Transitional Reader',
        '8-9 years': 'Fluent Reader',       // Amazon value
        '9-10 years': 'Fluent Reader',
        '10-11 years': 'Fluent Reader',
        '11-14 years': 'Advanced Reader',
        '14-16 years': 'Advanced Reader',
        '16-18 years': 'Advanced Reader',
        '18+ years': 'Proficient Reader',   // Google Books value → Adult
        // Amazon-style age ranges
        '8-11 years': 'Fluent Reader',
        '8 - 11 years': 'Fluent Reader',
        // API variations
        'All Ages': 'Early Reader',
        'Adult': 'Proficient Reader',       // Google Books category
        'Young Adult': 'Advanced Reader',
        'Children': 'Early Reader'
    };

    // Reading level to age range mapping (updated to match user requirements)
    const readingToAgeMap = {
        'Pre-literacy (Sensory)': '0-12 months',
        'Pre-literacy (Naming)': '12-24 months',
        'Pre-literacy (Mimicry)': '2-3 years',
        'Early Pre-reader': '3-4 years',
        'Beginning Reader': '4-5 years',
        'Early Reader': '5-6 years',        // Maps to user's current database value
        'Developing Reader': '6-7 years',   // FIXED: Correct mapping from database
        'Transitional Reader': '7-8 years',
        'Fluent Reader': '8-9 years',       // Maps to Amazon's 8-9 years
        'Advanced Reader': '11-14 years',
        'Proficient Reader': '18+ years',   // Maps to Google Books 18+ years
        // Common API variations
        'Middle Grade': '8-9 years',        // Updated to map to Fluent Reader range
        'Young Adult': '14-16 years',
        'Adult': '18+ years',               // Google Books category
        'All Ages': '5-6 years'
    };

    // Set up synchronization between age range and reading level fields
    function setupAgeRangeReadingLevelSync() {
        // Note: Mapping variables are now defined at top level for global access

        // FIXED: Synchronized field selection - both fields must be selected/unselected together
        $(document).on('change', 'input[type="checkbox"][value="age_range"]', function() {
            const isChecked = $(this).is(':checked');
            console.log('🔄 Age range field checkbox changed:', isChecked);

            // TEMPORARILY DISABLED: Don't auto-sync checkboxes to debug the disabled field issue
            // $('input[type="checkbox"][value="reading_level"]').prop('checked', isChecked);

            if (isChecked) {
                // Both fields selected - sync reading level to match current age range selection
                const selectedAgeRange = getSelectedFieldValue('age_range');
                console.log('🔄 Age range selected, would sync reading level to:', selectedAgeRange);

                if (selectedAgeRange && ageToReadingMap[selectedAgeRange]) {
                    const expectedReading = ageToReadingMap[selectedAgeRange];
                    console.log('🔄 Expected reading level:', expectedReading);
                }
            }
        });

        // FIXED: Synchronized field selection - both fields must be selected/unselected together
        $(document).on('change', 'input[type="checkbox"][value="reading_level"]', function() {
            const isChecked = $(this).is(':checked');
            console.log('🔄 Reading level field checkbox changed:', isChecked);

            // TEMPORARILY DISABLED: Don't auto-sync checkboxes to debug the disabled field issue
            // $('input[type="checkbox"][value="age_range"]').prop('checked', isChecked);

            if (isChecked) {
                // Both fields selected - sync age range to match current reading level selection
                const selectedReadingLevel = getSelectedFieldValue('reading_level');
                console.log('🔄 Reading level selected, would sync age range to:', selectedReadingLevel);

                if (selectedReadingLevel && readingToAgeMap[selectedReadingLevel]) {
                    const expectedAge = readingToAgeMap[selectedReadingLevel];
                    console.log('🔄 Expected age range:', expectedAge);
                }
            }
        });

        // FIXED: Age range source changes should sync reading level in real-time
        $(document).on('change', 'input[type="radio"][name*="age_range"]', function() {
            console.log('🔄 Age range source changed:', $(this).val(), $(this).attr('name'));

            // Always sync reading level when age range source changes (regardless of checkbox state)
            setTimeout(() => {
                const selectedAgeRange = getSelectedFieldValue('age_range');
                console.log('🔄 New age range source value:', selectedAgeRange);

                if (selectedAgeRange && ageToReadingMap[selectedAgeRange]) {
                    const expectedReading = ageToReadingMap[selectedAgeRange];
                    console.log('🔄 Syncing reading level to:', expectedReading);

                    // Just log for now - don't try to update display
                    console.log('🔄 Would update reading level to:', expectedReading);
                } else {
                    console.log('🔄 No mapping found for age range:', selectedAgeRange);
                }
            }, 100);
        });

        // Listen for changes in reading level selections - ENHANCED event handling for source switching
        $(document).on('change', 'input[type="checkbox"][value="reading_level"], input[type="radio"][name*="reading_level"]', function() {
            console.log('🔄 Reading level field changed:', $(this).attr('name'), $(this).val(), $(this).is(':checked'));

            // Handle both checkbox (field selection) and radio (source selection) changes
            const isFieldCheckbox = $(this).attr('type') === 'checkbox' && $(this).val() === 'reading_level';
            const isSourceRadio = $(this).attr('type') === 'radio' && $(this).attr('name').includes('reading_level');

            // For field checkboxes, only proceed if being checked
            if (isFieldCheckbox && !$(this).is(':checked')) {
                console.log('🔄 Reading level field unchecked, skipping sync');
                return;
            }

            // For source radios, always proceed when selected
            if (isSourceRadio && !$(this).is(':checked')) {
                console.log('🔄 Reading level source not selected, skipping sync');
                return;
            }

            // Wait a moment for the UI to update, then get the selected value
            setTimeout(() => {
                const selectedReadingLevel = getSelectedFieldValue('reading_level');
                console.log('🔄 Selected reading level:', selectedReadingLevel);
                console.log('🔄 Available mappings:', Object.keys(readingToAgeMap));

                // ENHANCED DEBUG: Show where "Developing Reader" might be coming from
                if (selectedReadingLevel === 'Developing Reader') {
                    console.log('🚨 DEBUG: "Developing Reader" detected! Investigating source...');
                    console.log('🚨 Reading level field structure:', window.currentEnrichmentData?.fields?.reading_level);
                    console.log('🚨 All reading level options in DOM:', $('input[name*="reading_level"]').map(function() {
                        return { name: $(this).attr('name'), value: $(this).val(), checked: $(this).is(':checked'), text: $(this).closest('label').text() };
                    }).get());
                }

                if (selectedReadingLevel && readingToAgeMap[selectedReadingLevel]) {
                    const expectedAge = readingToAgeMap[selectedReadingLevel];
                    console.log('🔄 Expected age range:', expectedAge);

                    // Update the age range field to show the corresponding value
                    updateAgeRangeDisplay(expectedAge);
                } else {
                    console.log('🔄 No mapping found for reading level:', selectedReadingLevel);
                    console.log('🔄 Exact match check:', readingToAgeMap[selectedReadingLevel]);

                    // Try partial matching for reading levels
                    const partialMatch = Object.keys(readingToAgeMap).find(key =>
                        selectedReadingLevel && key.toLowerCase().includes(selectedReadingLevel.toLowerCase())
                    );
                    if (partialMatch) {
                        console.log('🔄 Found partial match:', partialMatch, '→', readingToAgeMap[partialMatch]);
                        updateAgeRangeDisplay(readingToAgeMap[partialMatch]);
                    }
                }
            }, 100);
        });

        // FIXED: Add radio button synchronization for multi-source fields
        $(document).on('change', 'input[type="radio"][name*="age_range"]', function() {
            if (!$(this).is(':checked')) return;

            console.log('🔄 Age range radio changed:', $(this).attr('name'), $(this).val());

            // Extract source from radio button name (e.g., "field_age_range_option_0" -> get option index)
            const radioName = $(this).attr('name');
            const optionIndex = radioName.split('_').pop();

            // Find corresponding reading level radio button and select it
            const readingRadioName = radioName.replace('age_range', 'reading_level');
            const readingRadio = $(`input[type="radio"][name="${readingRadioName}"]`);

            if (readingRadio.length > 0) {
                readingRadio.prop('checked', true);
                console.log('🔄 Synced reading level radio selection to option:', optionIndex);
            }
        });

        $(document).on('change', 'input[type="radio"][name*="reading_level"]', function() {
            if (!$(this).is(':checked')) return;

            console.log('🔄 Reading level radio changed:', $(this).attr('name'), $(this).val());

            // Extract source from radio button name
            const radioName = $(this).attr('name');
            const optionIndex = radioName.split('_').pop();

            // Find corresponding age range radio button and select it
            const ageRadioName = radioName.replace('reading_level', 'age_range');
            const ageRadio = $(`input[type="radio"][name="${ageRadioName}"]`);

            if (ageRadio.length > 0) {
                ageRadio.prop('checked', true);
                console.log('🔄 Synced age range radio selection to option:', optionIndex);
            }
        });
    }

    // Get the currently selected value for a field
    function getSelectedFieldValue(fieldName) {
        console.log('🔍 Getting selected value for field:', fieldName);

        const fieldData = window.currentEnrichmentData?.fields?.[fieldName];
        if (!fieldData) {
            console.log('🔍 No field data found for:', fieldName);
            return null;
        }

        // Check for multi-option fields first
        const checkedOption = $(`input[name="field_${fieldName}_option"]:checked`);
        console.log('🔍 Checked option elements found:', checkedOption.length);

        if (checkedOption.length > 0) {
            const optionIndex = parseInt(checkedOption.val());
            console.log('🔍 Selected option index:', optionIndex);

            if (fieldData.new_data && fieldData.new_data.options && fieldData.new_data.options[optionIndex]) {
                const value = fieldData.new_data.options[optionIndex].value;
                console.log('🔍 Multi-option field value:', value);
                console.log('🔍 All options:', fieldData.new_data.options);
                return value;
            } else {
                console.log('🔍 No options found at index:', optionIndex);
            }
        }

        // For single-source fields, return the new_data value directly
        if (fieldData.new_data && fieldData.new_data.value !== undefined) {
            const value = fieldData.new_data.value;
            console.log('🔍 Single field value:', value);
            return value;
        }

        // Fallback to current value if no new data
        if (fieldData.current_value !== undefined) {
            console.log('🔍 Fallback to current value:', fieldData.current_value);
            return fieldData.current_value;
        }

        console.log('🔍 No value found for field:', fieldName);
        console.log('🔍 Field data structure:', fieldData);
        return null;
    }

    // FIXED: Ensure both age_range and reading_level show the same source options
    function ensureSynchronizedSourceOptions() {
        console.log('🔄 ensureSynchronizedSourceOptions called');

        // Check if enrichment data exists
        if (!window.currentEnrichmentData || !window.currentEnrichmentData.fields) {
            console.log('🔄 No enrichment data available yet');
            return;
        }

        const ageField = window.currentEnrichmentData.fields['age_range'];
        const readingField = window.currentEnrichmentData.fields['reading_level'];

        if (!ageField || !readingField) {
            console.log('🔄 Missing age_range or reading_level field - skipping synchronization');
            return;
        }

        // Check if both fields have data to work with
        if (!ageField.new_data && !readingField.new_data) {
            console.log('🔄 No new data in either field - skipping synchronization');
            return;
        }

        // Collect all available source options from both fields
        let allSourceOptions = [];

        // Get options from age_range field
        if (ageField.new_data?.options) {
            allSourceOptions = [...ageField.new_data.options];
        } else if (ageField.new_data) {
            allSourceOptions.push({
                value: ageField.new_data.value,
                source: ageField.new_data.source,
                confidence: ageField.new_data.confidence,
                label: ageField.label || 'Age Range'
            });
        }

        // Get options from reading_level field and merge
        if (readingField.new_data?.options) {
            // Merge reading level options, but map their values to corresponding age ranges
            readingField.new_data.options.forEach(option => {
                const mappedAge = readingToAgeMap[option.value];
                if (mappedAge) {
                    // Check if we already have this source
                    const existingOption = allSourceOptions.find(opt => opt.source === option.source);
                    if (!existingOption) {
                        allSourceOptions.push({
                            value: mappedAge,
                            source: option.source,
                            confidence: option.confidence,
                            label: 'Age Range'
                        });
                    }
                }
            });
        } else if (readingField.new_data) {
            const mappedAge = readingToAgeMap[readingField.new_data.value];
            if (mappedAge) {
                const existingOption = allSourceOptions.find(opt => opt.source === readingField.new_data.source);
                if (!existingOption) {
                    allSourceOptions.push({
                        value: mappedAge,
                        source: readingField.new_data.source,
                        confidence: readingField.new_data.confidence,
                        label: 'Age Range'
                    });
                }
            }
        }

        // Now ensure both fields have the same source options
        if (allSourceOptions.length > 1) {
            // Create multi-source structure for age_range
            ageField.new_data = { options: allSourceOptions };

            // Create corresponding reading level options
            const readingOptions = allSourceOptions.map(option => ({
                value: ageToReadingMap[option.value] || option.value,
                source: option.source,
                confidence: option.confidence,
                label: 'Reading Level'
            }));

            readingField.new_data = { options: readingOptions };

            console.log('🔄 Created synchronized multi-source options:', {
                age_options: allSourceOptions,
                reading_options: readingOptions
            });

            // Re-render both fields to show the synchronized options
            const container = $('#enrichment-fields');
            const ageContainer = container.find('[data-field="age_range"]').parent();
            const readingContainer = container.find('[data-field="reading_level"]').parent();

            if (ageContainer.length > 0) {
                const ageHtml = createMultiSourceField('age_range', ageField, 'Age Range');
                ageContainer.html(ageHtml);
            }

            if (readingContainer.length > 0) {
                const readingHtml = createMultiSourceField('reading_level', readingField, 'Reading Level');
                readingContainer.html(readingHtml);
            }

            console.log('🔄 Re-rendered both fields with synchronized options');
        }
    }

    // Legacy function - keeping for compatibility but simplified
    function syncReadingLevelField(expectedReading, isRevertMode = false) {
        console.log('🔄 syncReadingLevelField called with:', expectedReading, 'revert mode:', isRevertMode);
        updateReadingLevelDisplay(expectedReading);
    }

    // Legacy function - keeping for compatibility but simplified
    function syncAgeRangeField(expectedAge, isRevertMode = false) {
        console.log('🔄 syncAgeRangeField called with:', expectedAge, 'revert mode:', isRevertMode);
        updateAgeRangeDisplay(expectedAge);
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
        const sourceClass = source.includes('+') ? 'primary' : source === 'google_books' ? 'success' : source === 'open_library' ? 'info' : source === 'amazon_derived' ? 'warning' : source === 'database_recommendation' ? 'success' : 'secondary';

        // Display friendly source names
        const displaySource = source === 'amazon_derived' ? 'Amazon' :
                             source === 'google_books' ? 'Google Books' :
                             source === 'open_library' ? 'OpenLibrary' :
                             source === 'database_recommendation' ? 'Database Match' :
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

        // Determine database state first
        const databaseState = determineDatabaseState(field.current_value, newData.value, source, newData, fieldName);

        // Add disabled styling classes - exact matches should be disabled
        const shouldDisable = isUnknown || isPendingAmazon || benefitLevel === 'not_beneficial' || benefitLevel === 'exact_match' || databaseState === 'matches_database';
        const disabledClass = shouldDisable ? ' disabled-field' : '';
        const labelClass = shouldDisable ? ' text-muted' : '';

        // Auto-select database empty fields and purchase links (unless exact match)
        // Never auto-select if it matches database exactly
        const shouldAutoSelect = databaseState !== 'matches_database' && (
            (databaseState === 'database_empty' || databaseState === 'database_wrong') ||
            (fieldName === 'purchase_links' && databaseState !== 'matches_database')
        );

        // Add appropriate database state labels
        let databaseStateHtml = '';
        let displayConfidence = confidence;

        switch (databaseState) {
            case 'matches_database':
                displayConfidence = 100; // Override confidence for exact matches
                databaseStateHtml = `
                    <div class="mt-2 p-2 bg-light border border-info rounded">
                        <div class="text-info">
                            <i class="fas fa-check-double"></i> <strong>Matches Database</strong>
                            <span class="badge badge-info ml-1">100%</span>
                        </div>
                        <small class="text-muted">Current value exactly matches the new data</small>
                    </div>
                `;
                break;
            case 'database_wrong':
                databaseStateHtml = `
                    <div class="mt-2 p-2 bg-light border border-warning rounded">
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Database Wrong</strong>
                            <span class="badge badge-warning ml-1">${confidence}%</span>
                        </div>
                        <small class="text-muted">Both sources agree - database value appears incorrect</small>
                    </div>
                `;
                break;
            case 'database_empty':
                databaseStateHtml = `
                    <div class="mt-2 p-2 bg-light border border-success rounded">
                        <div class="text-success">
                            <i class="fas fa-plus-circle"></i> <strong>Database Empty</strong>
                            <span class="badge badge-success ml-1">${confidence}%</span>
                        </div>
                        <small class="text-muted">Sources agree - adding missing data to database</small>
                    </div>
                `;
                break;
        }

        // Special handling for publisher database recommendations
        if (fieldName === 'publisher' && source === 'database_recommendation' && newData.match_type) {
            databaseStateHtml = `
                <div class="mt-2 p-2 bg-light border border-success rounded">
                    <div class="text-success">
                        <i class="fas fa-database"></i> <strong>Database Match (Recommended)</strong>
                        <span class="badge badge-success ml-1">${confidence}%</span>
                    </div>
                    <small class="text-muted">
                        ${newData.match_type} match - prevents duplicates and maintains data consistency
                    </small>
                </div>
            `;
        }

        return `
            <div class="col-md-6 mb-3">
                <div class="enrichment-field ${benefitBorder}${exactMatchClass}${disabledClass}" data-field="${fieldName}">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               id="field_${fieldName}" name="fields[]" value="${fieldName}"
                               ${shouldDisable ? 'disabled' : ''}
                               ${shouldAutoSelect ? 'checked' : ''}>
                        <label class="form-check-label font-weight-bold${labelClass}" for="field_${fieldName}">
                            ${label}
                            <span class="badge badge-${sourceClass} ml-2">${displaySource}${isPendingAmazon ? ' (Loading...)' : ''}</span>
                            ${!isUnknown && !isPendingAmazon ? `<span class="badge badge-${confidenceClass} ml-1">(${displayConfidence}%)</span>` : ''}
                            ${getBenefitIndicator(benefitLevel)}
                        </label>
                    </div>
                    <div class="mt-2 p-2 ${benefitClass} rounded">
                        <div class="mb-2">
                            <strong>Current Value:</strong> ${formatCurrentValue(fieldName, field.current_value)}
                        </div>
                        <strong>New Value:</strong> ${displayValue}
                    </div>
                    ${databaseStateHtml}
                </div>
            </div>
        `;
    }

    /**
     * Determine the database state for a field
     * @param {*} currentValue - Current value in database
     * @param {*} newValue - New value from API
     * @param {string} source - Source of the new data
     * @param {object} newData - Full new data object
     * @param {string} fieldName - Name of the field being compared
     * @returns {string} - 'matches_database', 'database_wrong', 'database_empty', or null
     */
    function determineDatabaseState(currentValue, newValue, source, newData, fieldName = null) {
        // Extract actual value from recommendation text for publisher fields
        let actualNewValue = newValue;
        if (typeof newValue === 'string' && newValue.includes('(recommended:')) {
            // Extract the actual value before the recommendation text
            actualNewValue = newValue.split(' (recommended:')[0].trim();
            console.log('🔍 PUBLISHER_DEBUG: Extracted actual value from recommendation:', newValue, '→', actualNewValue);
        }

        // Special handling for tags/genres - use proper comparison for all tag formats
        if (fieldName === 'tags') {
            console.log('🏷️ TAG_DEBUG: Comparing tags for field:', fieldName, {
                currentValue: currentValue,
                currentType: typeof currentValue,
                currentIsArray: Array.isArray(currentValue),
                actualNewValue: actualNewValue,
                newType: typeof actualNewValue,
                newIsArray: Array.isArray(actualNewValue)
            });

            // Normalize both values to arrays for comparison
            let currentTags = [];
            let newTags = [];

            // Handle current value - it might be a concatenated string without separators
            if (Array.isArray(currentValue)) {
                currentTags = currentValue.map(tag => tag.toLowerCase().trim()).sort();
            } else if (typeof currentValue === 'string') {
                // Check if it's a concatenated string like "AfricaAlgeriaBerbersChildren's Fiction..."
                if (currentValue.includes(',')) {
                    currentTags = currentValue.split(',').map(tag => tag.toLowerCase().trim()).sort();
                } else {
                    // This might be a concatenated string - try to split it intelligently
                    // For now, treat it as a single tag for comparison
                    currentTags = [currentValue.toLowerCase().trim()];
                }
            }

            // Handle new value
            if (Array.isArray(actualNewValue)) {
                newTags = actualNewValue.map(tag => tag.toLowerCase().trim()).sort();
            } else if (typeof actualNewValue === 'string') {
                if (actualNewValue.includes(',')) {
                    newTags = actualNewValue.split(',').map(tag => tag.toLowerCase().trim()).sort();
                } else {
                    // Single tag or concatenated string
                    newTags = [actualNewValue.toLowerCase().trim()];
                }
            }

            console.log('🏷️ TAG_DEBUG: Normalized tags for comparison:', {
                currentTags: currentTags,
                newTags: newTags,
                currentLength: currentTags.length,
                newLength: newTags.length
            });

            // CRITICAL FIX: Better handling for concatenated strings
            if (currentTags.length === 1 && newTags.length === 1) {
                const currentStr = currentTags[0];
                const newStr = newTags[0];

                // If both are long strings, they might be concatenated tags
                if (currentStr.length > 30 && newStr.length > 30) {
                    console.log('🏷️ TAG_DEBUG: Detected concatenated tag strings, checking content similarity');

                    // CRITICAL FIX: Enhanced word extraction for concatenated genre strings
                    const extractWords = (str) => {
                        // First, handle common patterns in concatenated genre strings
                        let processed = str
                            // Add spaces before capital letters (camelCase)
                            .replace(/([a-z])([A-Z])/g, '$1 $2')
                            // Handle specific patterns like "People & Places"
                            .replace(/&/g, ' and ')
                            // Handle apostrophes in "Children's Fiction"
                            .replace(/'/g, ' ')
                            // Normalize multiple spaces
                            .replace(/\s+/g, ' ');

                        return processed
                            // Split on various separators
                            .split(/[\s,;-]+/)
                            // Clean and filter
                            .map(w => w.toLowerCase().trim())
                            .filter(w => w.length > 2)
                            // Remove common words that don't add meaning
                            .filter(w => !['and', 'the', 'for', 'with', 'from', 'of', 'in', 'on', 'at', 'to'].includes(w))
                            // Remove duplicates
                            .filter((word, index, arr) => arr.indexOf(word) === index);
                    };

                    const currentWords = extractWords(currentStr);
                    const newWords = extractWords(newStr);

                    console.log('🏷️ TAG_DEBUG: Enhanced word extraction:', {
                        currentStr: currentStr,
                        newStr: newStr,
                        currentWords: currentWords,
                        newWords: newWords
                    });

                    // CRITICAL FIX: Better similarity calculation for genre matching
                    const commonWords = currentWords.filter(word => newWords.includes(word));
                    const allWords = new Set([...currentWords, ...newWords]);

                    // Calculate multiple similarity metrics
                    const jaccardSimilarity = commonWords.length / allWords.size;
                    const overlapSimilarity = commonWords.length / Math.min(currentWords.length, newWords.length);
                    const coverageSimilarity = commonWords.length / Math.max(currentWords.length, newWords.length);

                    console.log('🏷️ TAG_DEBUG: Enhanced similarity analysis:', {
                        currentWords: currentWords,
                        newWords: newWords,
                        commonWords: commonWords,
                        totalUniqueWords: allWords.size,
                        jaccardSimilarity: jaccardSimilarity,
                        overlapSimilarity: overlapSimilarity,
                        coverageSimilarity: coverageSimilarity
                    });

                    // Use multiple criteria for better matching
                    // If most words overlap (high coverage) OR very high Jaccard similarity
                    if (overlapSimilarity > 0.8 || jaccardSimilarity > 0.7 || coverageSimilarity > 0.85) {
                        console.log('🏷️ TAG_DEBUG: High similarity detected - treating as match');
                        console.log('🏷️ TAG_DEBUG: Match criteria: overlap=' + overlapSimilarity + ', jaccard=' + jaccardSimilarity + ', coverage=' + coverageSimilarity);
                        return 'matches_database';
                    }

                    // Additional check: if strings are very similar in length and share many characters
                    const lengthRatio = Math.min(currentStr.length, newStr.length) / Math.max(currentStr.length, newStr.length);
                    if (lengthRatio > 0.8 && jaccardSimilarity > 0.5) {
                        console.log('🏷️ TAG_DEBUG: Similar length and moderate similarity - treating as match');
                        return 'matches_database';
                    }
                }
            }

            // Compare arrays element by element
            const arraysEqual = currentTags.length === newTags.length &&
                               currentTags.every((tag, index) => {
                                   const isEqual = tag === newTags[index];
                                   console.log(`🏷️ TAG_DEBUG: Comparing "${tag}" vs "${newTags[index]}" = ${isEqual}`);
                                   return isEqual;
                               });

            console.log('🏷️ TAG_DEBUG: Final comparison result:', {
                arraysEqual: arraysEqual,
                currentEmpty: currentTags.length === 0,
                newEmpty: newTags.length === 0
            });

            if (arraysEqual) {
                console.log('🏷️ TAG_DEBUG: Tags match exactly - returning matches_database');
                return 'matches_database';
            } else if (currentTags.length === 0) {
                console.log('🏷️ TAG_DEBUG: Current tags empty - returning database_empty');
                return 'database_empty';
            } else {
                console.log('🏷️ TAG_DEBUG: Tags differ - returning database_wrong');
                return 'database_wrong';
            }
        }

        // Check for exact matches first (for non-tag fields)
        if (isExactMatch(currentValue, actualNewValue)) {
            console.log('🔍 EXACT_MATCH_DEBUG: Exact match found:', currentValue, '===', actualNewValue);
            return 'matches_database';
        }

        // Check if database is empty and we have data from ANY source
        if (isEmpty(currentValue) && !isEmpty(actualNewValue)) {
            return 'database_empty';
        }

        // Check if both sources agree but differ from database
        if (source === 'google_books + open_library' && !isEmpty(currentValue) && !isEmpty(actualNewValue)) {
            if (!isExactMatch(currentValue, actualNewValue)) {
                console.log('🔍 DATABASE_WRONG_DEBUG: Database wrong detected:', currentValue, '!==', actualNewValue);
                return 'database_wrong';
            }
        }

        return null; // No special database state
    }

    // Update status badges to show completion instead of "Checking..."
    function updateStatusBadges(sourcesChecked) {
        console.log('🔄 Updating status badges for sources:', sourcesChecked);

        // Update Google Books status
        if (sourcesChecked.includes('google_books')) {
            $('#google-books-status-badge').html('<span class="badge badge-success">✓ Google Books</span>');
            console.log('✅ Updated Google Books status to success');
        } else {
            $('#google-books-status-badge').html('<span class="badge badge-secondary">Google Books</span>');
            console.log('⚪ Updated Google Books status to not checked');
        }

        // Update OpenLibrary status - handle both ID variations
        if (sourcesChecked.includes('open_library')) {
            // Try both possible IDs (with and without hyphen)
            const openLibraryElement = $('#open-library-status-badge').length > 0 ?
                $('#open-library-status-badge') : $('#openlibrary-status-badge');
            openLibraryElement.html('<span class="badge badge-success">✓ OpenLibrary</span>');
            console.log('✅ Updated OpenLibrary status to success');
        } else {
            // Try both possible IDs (with and without hyphen)
            const openLibraryElement = $('#open-library-status-badge').length > 0 ?
                $('#open-library-status-badge') : $('#openlibrary-status-badge');
            openLibraryElement.html('<span class="badge badge-secondary">OpenLibrary</span>');
            console.log('⚪ Updated OpenLibrary status to not checked');
        }

        // CRITICAL FIX: Add Amazon status badge handling
        // Amazon is checked separately via AJAX, so we start it as "Checking..." and update later
        $('#amazon-status-badge').html('<span class="badge badge-info">Amazon - Checking...</span>');
        console.log('🔄 Set Amazon status to checking');
    }

    // Auto-select fields with single source and beneficial updates
    function autoSelectBeneficialFields(fields) {
        console.log('🎯 Auto-selecting beneficial fields...');
        let autoSelectedCount = 0;

        // Wait for DOM to be ready
        setTimeout(() => {
            Object.keys(fields).forEach(fieldName => {
                const field = fields[fieldName];

                // Skip fields with no new data
                if (!field.new_data) {
                    console.log(`⏭️ Skipping ${fieldName} - no new data`);
                    return;
                }

                // Handle both single source and multi-source fields
                if (!field.new_data.options) {
                    // Single source field
                    const isUnknown = field.new_data.status === 'unknown';
                    const isPendingAmazon = field.new_data.status === 'pending_amazon_data';

                    if (!isUnknown && !isPendingAmazon) {
                        const benefitLevel = determineBenefitLevel(field.current_value, field.new_data.value, false);
                        const isCurrentEmpty = isEmpty(field.current_value);

                        console.log(`🔍 Checking ${fieldName}:`, {
                            currentValue: field.current_value,
                            newValue: field.new_data.value,
                            benefitLevel,
                            isCurrentEmpty,
                            shouldAutoSelect: benefitLevel === 'beneficial' || isCurrentEmpty
                        });

                        // Auto-select beneficial fields or fields where current value is empty
                        if (benefitLevel === 'beneficial' || isCurrentEmpty) {
                            const checkbox = $(`#field_${fieldName}`);
                            if (checkbox.length > 0 && !checkbox.prop('disabled')) {
                                checkbox.prop('checked', true).trigger('change');
                                autoSelectedCount++;
                                console.log(`✅ Auto-selected ${fieldName}`);
                            }
                        } else {
                            console.log(`⚪ Not auto-selecting ${fieldName} - not beneficial enough`);
                        }
                    } else {
                        console.log(`⏭️ Skipping ${fieldName} - unknown or pending Amazon data`);
                    }
                } else {
                    // Multi-source field - auto-select best beneficial option
                    let bestOptionIndex = -1;
                    let highestConfidence = 0;
                    let foundBeneficial = false;

                    field.new_data.options.forEach((option, index) => {
                        const benefitLevel = determineBenefitLevel(field.current_value, option.value, option.is_unknown);
                        console.log(`🔍 Checking ${fieldName} option ${index}:`, {
                            value: option.value,
                            confidence: option.confidence,
                            benefitLevel,
                            isUnknown: option.is_unknown
                        });

                        if (benefitLevel === 'beneficial' && option.confidence >= highestConfidence) {
                            foundBeneficial = true;
                            bestOptionIndex = index;
                            highestConfidence = option.confidence;
                        }
                    });

                    if (foundBeneficial && bestOptionIndex >= 0) {
                        const checkbox = $(`#field_${fieldName}`);
                        if (checkbox.length > 0 && !checkbox.prop('disabled')) {
                            checkbox.prop('checked', true).trigger('change');
                            // Auto-select the best option
                            $(`input[name="field_${fieldName}_option"][value="${bestOptionIndex}"]`).prop('checked', true);
                            autoSelectedCount++;
                            console.log(`✅ Auto-selected ${fieldName} with option ${bestOptionIndex}`);
                        }
                    } else {
                        console.log(`⚪ Not auto-selecting ${fieldName} - no beneficial options`);
                    }
                }
            });

            console.log(`🎯 Auto-selected ${autoSelectedCount} fields total`);

            // Update the apply button state
            updateApplyButtonState();
        }, 200);
    }

    // Helper function to update apply button state
    function updateApplyButtonState() {
        const checkedCount = $('.field-checkbox:checked').length;
        const $applyBtn = $('#apply-enrichment-btn');

        if (checkedCount > 0) {
            $applyBtn.prop('disabled', false);
            $applyBtn.html(`<i class="fas fa-save"></i> Apply Selected Changes (${checkedCount})`);
        } else {
            $applyBtn.prop('disabled', true);
            $applyBtn.html('<i class="fas fa-save"></i> Apply Selected Changes');
        }
    }

    // Make functions globally available
    window.openDataEnrichmentModal = openDataEnrichmentModal;
    window.fetchEnrichmentData = fetchEnrichmentData;
    window.displayEnrichmentResults = displayEnrichmentResults;
    window.fetchAmazonDataForFields = fetchAmazonDataForFields;
    window.updateEnrichmentDataWithAmazon = updateEnrichmentDataWithAmazon;
    window.displayEnrichmentFields = displayEnrichmentFields;
    window.createSingleSourceField = createSingleSourceField;
    window.determineDatabaseState = determineDatabaseState;
    window.updateStatusBadges = updateStatusBadges;
    window.autoSelectBeneficialFields = autoSelectBeneficialFields;
    window.fetchBookISBNsFromDatabase = fetchBookISBNsFromDatabase;
}
