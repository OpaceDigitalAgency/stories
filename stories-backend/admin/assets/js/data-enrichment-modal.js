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
        // CRITICAL FIX: Always fetch Amazon data for ISBN validation, regardless of Amazon-derived fields
        if (!currentBookISBN) {
            console.log('📦 No ISBN available for Amazon validation');
            // Update Amazon status to show no ISBN
            $('#amazon-status-badge').html('<span class="badge badge-secondary">No ISBN</span>');
            return;
        }

        // Check if we have Amazon-derived fields that need data (for buying options)
        const amazonFields = ['purchase_links', 'format', 'price_range'];
        const hasAmazonFields = amazonFields.some(fieldName =>
            fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived'
        );

        console.log('📦 Amazon data fetch - ISBN available:', currentBookISBN);
        console.log('📦 Amazon data fetch - has Amazon fields:', hasAmazonFields);
        console.log('📦 Amazon data fetch - will fetch for ISBN validation regardless of Amazon fields');

        console.log('📦 Starting AJAX fetch for Amazon data. ISBN:', window.currentBookISBN);

        // Show loading indicators for Amazon fields
        amazonFields.forEach(fieldName => {
            if (fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived') {
                const $fieldDiv = $(`.enrichment-field[data-field="${fieldName}"]`);
                const $badge = $fieldDiv.find('.badge:contains("Amazon")');
                $badge.removeClass('badge-warning').addClass('badge-info').text('Amazon (Loading...)');
            }
        });

        // Fetch Amazon data with extended timeout
        $.ajax({
            url: 'book-import-validate/ajax/data-enrichment-ajax.php',
            type: 'POST',
            data: {
                action: 'get_amazon_data',
                isbn: window.currentBookISBN,
                book_id: window.currentBookId  // Pass book ID for duplicate prevention
            },
            timeout: 60000, // 60 second timeout for Amazon scraping
            dataType: 'json',
            success: function(res) {
            console.log('📦 Amazon AJAX response received:', res);
            console.log('📦 URGENT_DEBUG: Response success:', res.success);
            console.log('📦 URGENT_DEBUG: Response data:', res.data);
            console.log('📦 URGENT_DEBUG: Data keys:', res.data ? Object.keys(res.data) : 'no data');
            console.log('📦 URGENT_DEBUG: Format in data:', res.data?.format);
            console.log('📦 URGENT_DEBUG: Price range in data:', res.data?.price_range);

            if (res.success && res.data && Object.keys(res.data).length > 0) {
                // Integrate Amazon data into the enrichment fields
                updateEnrichmentDataWithAmazon(res.data);
            } else {
                console.log('📦 No Amazon data found or empty response');
                console.log('📦 Debug info:', res.debug);

                // CRITICAL FIX: Update Amazon status badge to show no data
                $('#amazon-status-badge').html('<span class="badge badge-secondary">No Data</span>');

                // Update badges to show no data found for Amazon-derived fields
                amazonFields.forEach(fieldName => {
                    if (fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived') {
                        const $fieldDiv = $(`.enrichment-field[data-field="${fieldName}"]`);
                        const $badge = $fieldDiv.find('.badge:contains("Amazon")');
                        $badge.removeClass('badge-info').addClass('badge-secondary').text('Amazon (No data)');

                        // Update the "New Value" text to show no data found
                        const $newValueDiv = $fieldDiv.find('.new-value');
                        $newValueDiv.text('No Amazon data found');
                    }
                });

                // URGENT DEBUG: Check if fields exist but Amazon data failed
                console.log('📦 URGENT_DEBUG: Checking existing fields for format and price_range...');
                const formatField = window.currentEnrichmentData?.fields?.format;
                const priceField = window.currentEnrichmentData?.fields?.price_range;
                console.log('📦 URGENT_DEBUG: Format field exists:', !!formatField, formatField);
                console.log('📦 URGENT_DEBUG: Price range field exists:', !!priceField, priceField);
            }
            },
            error: function(xhr, status, error) {
            console.error('📦 Amazon AJAX error:', { xhr, status, error });
            console.error('📦 Response text:', xhr.responseText);

            // CRITICAL FIX: Update Amazon status badge to show error
            $('#amazon-status-badge').html('<span class="badge badge-danger">Amazon Error</span>');

            // Update badges to show error for Amazon-derived fields
            amazonFields.forEach(fieldName => {
                if (fields[fieldName] && fields[fieldName].new_data && fields[fieldName].new_data.source === 'amazon_derived') {
                    const $fieldDiv = $(`.enrichment-field[data-field="${fieldName}"]`);
                    const $badge = $fieldDiv.find('.badge:contains("Amazon")');
                    $badge.removeClass('badge-info').addClass('badge-danger').text('Amazon (Error)');
                }
            });
            }
        });
    }

    function updateEnrichmentDataWithAmazon(amazonData) {
        console.log('📦 updateEnrichmentDataWithAmazon called with:', amazonData);
        console.log('📦 Amazon data keys:', Object.keys(amazonData));
        console.log('📦 Looking for format and price_range fields...');
        console.log('📦 Format field in Amazon data:', amazonData.format);
        console.log('📦 Price Range field in Amazon data:', amazonData.price_range);

        // CRITICAL DEBUG: Check if format and price_range are missing from Amazon data
        if (!amazonData.format) {
            console.log('🚨 CRITICAL: format field is MISSING from Amazon data!');
        } else {
            console.log('✅ format field found in Amazon data:', amazonData.format);
        }

        if (!amazonData.price_range) {
            console.log('🚨 CRITICAL: price_range field is MISSING from Amazon data!');
        } else {
            console.log('✅ price_range field found in Amazon data:', amazonData.price_range);
        }

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

            console.log('🚨 CRITICAL_DEBUG: Amazon data keys being processed:', Object.keys(amazonData));
            console.log('🚨 CRITICAL_DEBUG: Looking for format and price_range in Amazon data...');
            console.log('🚨 CRITICAL_DEBUG: amazonData.format exists:', !!amazonData.format);
            console.log('🚨 CRITICAL_DEBUG: amazonData.price_range exists:', !!amazonData.price_range);

            Object.keys(amazonData).forEach(fieldName => {
                const amazonFieldData = amazonData[fieldName];
                const existingField = window.currentEnrichmentData.fields[fieldName];

                console.log(`📦 Processing Amazon field: ${fieldName}`, {
                    amazonData: amazonFieldData,
                    existingField: existingField
                });

                // CRITICAL DEBUG: Special logging for format and price_range
                if (fieldName === 'format' || fieldName === 'price_range') {
                    console.log(`🚨 CRITICAL_DEBUG: Processing ${fieldName} field`);
                    console.log(`🚨 CRITICAL_DEBUG: ${fieldName} amazonFieldData:`, amazonFieldData);
                    console.log(`🚨 CRITICAL_DEBUG: ${fieldName} existingField:`, existingField);
                }

                // URGENT DEBUG: Check field status before and after update
                console.log(`📦 URGENT_DEBUG: ${fieldName} field status BEFORE update:`, existingField?.new_data?.status);
                console.log(`📦 URGENT_DEBUG: ${fieldName} Amazon data status:`, amazonFieldData?.new_data?.status);

                // CRITICAL DEBUG: Check if this is a pending Amazon field
                if (fieldName === 'purchase_links' || fieldName === 'format' || fieldName === 'price_range') {
                    console.log(`📦 AMAZON_FIELD_DEBUG: ${fieldName} - existingField exists:`, !!existingField);
                    console.log(`📦 AMAZON_FIELD_DEBUG: ${fieldName} - has new_data:`, !!existingField?.new_data);
                    console.log(`📦 AMAZON_FIELD_DEBUG: ${fieldName} - current status:`, existingField?.new_data?.status);
                    console.log(`📦 AMAZON_FIELD_DEBUG: ${fieldName} - is pending Amazon:`, existingField?.new_data?.status === 'pending_amazon_data');
                    console.log(`📦 AMAZON_FIELD_DEBUG: ${fieldName} - Amazon data value:`, amazonFieldData?.new_data?.value);
                }

                // Skip Amazon data with "unknown" values or "12+" values - don't process these at all
                if (amazonFieldData.new_data.value === 'Unknown' ||
                    amazonFieldData.new_data.value === 'unknown' ||
                    (typeof amazonFieldData.new_data.value === 'string' && amazonFieldData.new_data.value.includes('12+'))) {
                    console.log(`📦 Skipping Amazon field ${fieldName} - value is unknown or contains 12+:`, amazonFieldData.new_data.value);
                    return;
                }

                // CRITICAL FIX: Handle Amazon ISBN data as validation source
                if ((fieldName === 'isbn' || fieldName === 'isbn13')) {
                    console.log(`📦 AMAZON_VALIDATION: Processing Amazon ${fieldName} field`);

                    // Check if this ISBN field already exists in the enrichment data
                    if (existingField) {
                        console.log(`📦 AMAZON_VALIDATION: ${fieldName} field already exists - adding Amazon as validation source`);

                        // Always add Amazon as a validation source for ISBN fields
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

                            // Add Amazon as additional validation source
                            existingField.new_data.options.push({
                                value: amazonFieldData.new_data.value,
                                source: amazonFieldData.new_data.source,
                                confidence: amazonFieldData.new_data.confidence,
                                label: amazonFieldData.label || existingField.label,
                                validation_source: amazonFieldData.new_data.validation_source,
                                matches_current: amazonFieldData.new_data.matches_current
                            });

                            // Update source to include Amazon
                            if (!existingField.new_data.source.includes('amazon')) {
                                existingField.new_data.source += ' + amazon';
                            }
                            console.log(`📦 AMAZON_VALIDATION: Added Amazon as validation source for ${fieldName}`);
                            return; // Skip creating new field
                        } else {
                            // Field exists but has no new_data - create new_data with Amazon
                            existingField.new_data = {
                                value: amazonFieldData.new_data.value,
                                source: amazonFieldData.new_data.source,
                                confidence: amazonFieldData.new_data.confidence,
                                validation_source: amazonFieldData.new_data.validation_source,
                                matches_current: amazonFieldData.new_data.matches_current
                            };
                            console.log(`📦 AMAZON_VALIDATION: Added Amazon data to existing ${fieldName} field with no new_data`);
                            return; // Skip creating new field
                        }
                    } else {
                        // Field doesn't exist in enrichment data - create it with Amazon data
                        console.log(`📦 AMAZON_VALIDATION: Creating new ${fieldName} field with Amazon data`);
                        // Continue to create new field below
                    }
                }

                if (existingField) {
                    // Field already exists - check if it has new_data or is just showing current value
                    console.log(`📦 Field ${fieldName} already exists:`, {
                        hasNewData: !!existingField.new_data,
                        currentValue: existingField.current_value,
                        amazonValue: amazonFieldData.new_data.value
                    });

                    // CRITICAL FIX: For ISBN fields, ALWAYS add Amazon as validation source even if values match
                    if (fieldName === 'isbn' || fieldName === 'isbn13') {
                        console.log(`📦 AMAZON_VALIDATION: Processing ${fieldName} field - always add Amazon as validation source`);
                        // Continue processing - don't skip even if values match
                    } else {
                        // For non-ISBN fields, check if this is a pending Amazon field that needs updating
                        if (existingField.new_data && existingField.new_data.status === 'pending_amazon_data') {
                            console.log(`📦 CRITICAL_FIX: Field ${fieldName} is pending Amazon data - will update regardless of value match`);
                            // Don't skip - we need to update the status even if values match
                        } else if (existingField.current_value === amazonFieldData.new_data.value) {
                            console.log(`📦 Skipping Amazon field ${fieldName} - same as current value and not pending Amazon data`);
                            return;
                        }
                    }

                    if (existingField.new_data) {
                        // Field has new_data from other sources - merge Amazon as additional source
                        console.log(`📦 Merging Amazon data with existing field: ${fieldName}`);

                        // CRITICAL DEBUG: Log the exact path being taken
                        if (fieldName === 'purchase_links' || fieldName === 'format' || fieldName === 'price_range') {
                            console.log(`📦 PATH_DEBUG: ${fieldName} - has options:`, !!existingField.new_data.options);
                            console.log(`📦 PATH_DEBUG: ${fieldName} - status:`, existingField.new_data.status);
                            console.log(`📦 PATH_DEBUG: ${fieldName} - will take pending_amazon_data path:`, existingField.new_data.status === 'pending_amazon_data');
                        }

                        if (existingField.new_data.options) {
                            // Field already has multiple sources - add Amazon as another option
                            console.log(`📦 Adding Amazon as additional option to multi-source field: ${fieldName}`);
                            existingField.new_data.options.push({
                                value: amazonFieldData.new_data.value,
                                source: amazonFieldData.new_data.source,
                                confidence: amazonFieldData.new_data.confidence,
                                label: amazonFieldData.label || existingField.label,
                                original_value: amazonFieldData.new_data.original_value,
                                status: 'ready' // CRITICAL FIX: Set status to ready for Amazon options
                            });

                            // Update source to include Amazon
                            const currentSources = existingField.new_data.source || '';
                            if (!currentSources.includes('amazon')) {
                                existingField.new_data.source = currentSources + ' + amazon';
                            }
                        } else if (fieldName === 'tags') {
                            // CRITICAL FIX: Special handling for tags - merge instead of creating options
                            console.log(`📦 TAGS_MERGE: Merging Amazon tags with existing tags for ${fieldName}`);
                            console.log(`📦 TAGS_MERGE: Existing value:`, existingField.new_data.value);
                            console.log(`📦 TAGS_MERGE: Amazon value:`, amazonFieldData.new_data.value);

                            // Parse existing tags
                            let existingTags = [];
                            if (typeof existingField.new_data.value === 'string') {
                                existingTags = existingField.new_data.value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                            } else if (Array.isArray(existingField.new_data.value)) {
                                existingTags = existingField.new_data.value;
                            }

                            // Parse Amazon tags
                            let amazonTags = [];
                            if (typeof amazonFieldData.new_data.value === 'string') {
                                amazonTags = amazonFieldData.new_data.value.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                            } else if (Array.isArray(amazonFieldData.new_data.value)) {
                                amazonTags = amazonFieldData.new_data.value;
                            }

                            // Merge and deduplicate tags (case-insensitive)
                            const allTags = [...existingTags, ...amazonTags];
                            const uniqueTags = [];
                            const seenTags = new Set();

                            for (const tag of allTags) {
                                const lowerTag = tag.toLowerCase().trim();
                                if (!seenTags.has(lowerTag) && lowerTag.length > 0) {
                                    seenTags.add(lowerTag);
                                    uniqueTags.push(tag.trim());
                                }
                            }

                            console.log(`📦 TAGS_MERGE: Merged result:`, uniqueTags);

                            // Update the field with merged tags
                            existingField.new_data.value = uniqueTags.join(', ');
                            existingField.new_data.source = 'google_books + open_library + amazon';

                        } else if (existingField.new_data.status === 'pending_amazon_data') {
                        // CRITICAL FIX: Field was pending Amazon data - replace with actual Amazon data
                        console.log(`📦 URGENT_FIX: Field ${fieldName} was pending Amazon data - replacing with Amazon data`);
                        console.log(`📦 URGENT_FIX: Before replacement - status:`, existingField.new_data.status);
                        console.log(`📦 URGENT_FIX: Amazon data to replace with:`, amazonFieldData.new_data);

                        existingField.new_data = {
                            ...amazonFieldData.new_data,
                            status: 'ready' // CRITICAL FIX: Set status to ready when Amazon data arrives
                        };

                        console.log(`📦 URGENT_FIX: After replacement - status:`, existingField.new_data.status);
                        console.log(`📦 URGENT_FIX: After replacement - full new_data:`, existingField.new_data);
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
                                    original_value: amazonFieldData.new_data.original_value,
                                    status: 'ready' // CRITICAL FIX: Set status to ready for Amazon options
                                }
                            ],
                            source: (originalData.source || 'unknown') + ' + amazon'
                        };
                        }
                    } else {
                        // Field exists but has no new_data - add Amazon data
                        console.log(`📦 Field ${fieldName} exists but has no new_data - adding Amazon data`);
                        existingField.new_data = {
                            ...amazonFieldData.new_data,
                            status: 'ready' // CRITICAL FIX: Set status to ready when Amazon data arrives
                        };
                    }
                } else {
                    // Field doesn't exist or has no data - add Amazon data as new field
                    console.log(`📦 Adding new Amazon field: ${fieldName}`);

                    window.currentEnrichmentData.fields[fieldName] = {
                        label: amazonFieldData.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
                        current_value: existingField?.current_value || null,
                        new_data: {
                            ...amazonFieldData.new_data,
                            status: 'ready' // CRITICAL FIX: Set status to ready when Amazon data arrives
                        }
                    };
                }

                console.log(`📦 Final field structure for ${fieldName}:`, window.currentEnrichmentData.fields[fieldName]);

                // URGENT DEBUG: Check field status after update
                console.log(`📦 URGENT_DEBUG: ${fieldName} field status AFTER update:`, window.currentEnrichmentData.fields[fieldName]?.new_data?.status);
            });

            // CRITICAL FIX: Re-render the enrichment fields to include the new Amazon data
            console.log('📦 CRITICAL_FIX: Re-rendering fields after Amazon data integration');
            console.log('📦 CRITICAL_FIX: Fields being passed to displayEnrichmentFields:', Object.keys(window.currentEnrichmentData.fields));
            console.log('📦 CRITICAL_FIX: Purchase links field after update:', window.currentEnrichmentData.fields.purchase_links);
            console.log('📦 CRITICAL_FIX: Format field after update:', window.currentEnrichmentData.fields.format);
            console.log('📦 CRITICAL_FIX: Price range field after update:', window.currentEnrichmentData.fields.price_range);

            // CRITICAL FIX: Force re-evaluation of database states for Amazon-derived fields
            console.log('📦 CRITICAL_FIX: Re-evaluating database states after Amazon integration');
            ['purchase_links', 'format', 'price_range'].forEach(fieldName => {
                const field = window.currentEnrichmentData.fields[fieldName];
                if (field && field.new_data && field.new_data.source === 'amazon_derived' && field.new_data.status === 'ready') {
                    console.log(`📦 CRITICAL_FIX: Re-evaluating database state for ${fieldName}`);
                    // Force update the field display to recalculate database state
                    setTimeout(() => {
                        updateFieldDisplay(fieldName, null, false);
                    }, 100);
                }
            });

            displayEnrichmentFields(window.currentEnrichmentData.fields);

            // CRITICAL FIX: Update Amazon status badge to show completion
            $('#amazon-status-badge')
                .removeClass('badge-info badge-warning badge-danger')
                .addClass('badge-success')
                .html('✓ Amazon - Data Found')
                .show();
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

                // URGENT DEBUG: Check field status when rendering
                console.log(`📦 RENDER_DEBUG: Rendering field ${fieldName} with status:`, field.new_data.status, 'isPendingAmazon:', isPendingAmazon);
                if (fieldName === 'format' || fieldName === 'price_range') {
                    console.log(`📦 RENDER_DEBUG: ${fieldName} full field data:`, field);
                }

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

                // URGENT DEBUG: Check field status when rendering (remaining fields)
                console.log(`📦 RENDER_DEBUG: Rendering remaining field ${fieldName} with status:`, field.new_data.status, 'isPendingAmazon:', isPendingAmazon);
                if (fieldName === 'format' || fieldName === 'price_range') {
                    console.log(`📦 RENDER_DEBUG: ${fieldName} remaining field data:`, field);
                }

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
            console.log('🔍 DEBUG: Total enrichment fields in DOM:', $('.enrichment-field').length);
            console.log('🔍 DEBUG: Container HTML length:', $('#enrichment-fields').html()?.length || 0);
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

            // Update the age range field display immediately
            updateFieldDisplay('age_range');

            // Always sync reading level when age range source changes (regardless of checkbox state)
            setTimeout(() => {
                const selectedAgeRange = getSelectedFieldValue('age_range');
                console.log('🔄 New age range source value:', selectedAgeRange);

                if (selectedAgeRange && ageToReadingMap[selectedAgeRange]) {
                    const expectedReading = ageToReadingMap[selectedAgeRange];
                    console.log('🔄 Syncing reading level to:', expectedReading);
                    // Update reading level display to show mapped value
                    updateFieldDisplay('reading_level', expectedReading);
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

                    // Just log for now - don't try to update display
                    console.log('🔄 Would update age range to:', expectedAge);
                } else {
                    console.log('🔄 No mapping found for reading level:', selectedReadingLevel);
                    console.log('🔄 Exact match check:', readingToAgeMap[selectedReadingLevel]);

                    // Try partial matching for reading levels
                    const partialMatch = Object.keys(readingToAgeMap).find(key =>
                        selectedReadingLevel && key.toLowerCase().includes(selectedReadingLevel.toLowerCase())
                    );
                    if (partialMatch) {
                        console.log('🔄 Found partial match:', partialMatch, '→', readingToAgeMap[partialMatch]);
                        console.log('🔄 Would update age range to:', readingToAgeMap[partialMatch]);
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

            // Update both field displays with auto-check
            setTimeout(() => {
                updateFieldDisplay('age_range', null, true);
                // CRITICAL: Update reading level with the mapped value
                const ageRangeValue = getSelectedFieldValue('age_range');
                const mappedReadingLevel = ageToReadingMap[ageRangeValue];
                if (mappedReadingLevel) {
                    console.log('🔄 Mapping age range', ageRangeValue, 'to reading level', mappedReadingLevel);
                    updateFieldDisplay('reading_level', mappedReadingLevel, true);
                } else {
                    updateFieldDisplay('reading_level', null, true);
                }
            }, 50);
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

            // Update both field displays with auto-check
            setTimeout(() => {
                updateFieldDisplay('age_range', null, true);
                updateFieldDisplay('reading_level', null, true);
            }, 50);
        });

        // Listen for checkbox changes on any field to update display
        $(document).on('change', 'input[type="checkbox"].field-checkbox', function() {
            const fieldName = $(this).val();
            const isChecked = $(this).is(':checked');
            console.log('🔄 Field checkbox changed:', fieldName, 'checked:', isChecked);

            // CRITICAL FIX: Handle age range unchecking to revert reading level
            if (fieldName === 'age_range' && !isChecked) {
                console.log('🔄 Age range unchecked - reverting reading level to original state');
                // Uncheck reading level checkbox
                const readingLevelCheckbox = $('input[type="checkbox"][value="reading_level"]');
                readingLevelCheckbox.prop('checked', false);
                // Clear any selected radio buttons for age range
                $('input[name*="age_range"][type="radio"]').prop('checked', false);
                // Update both displays
                updateFieldDisplay('age_range');
                updateFieldDisplay('reading_level');
            } else {
                updateFieldDisplay(fieldName);
            }
        });

        // Listen for radio button changes on any field to update display
        $(document).on('change', 'input[type="radio"][name*="field_"][name*="_option"]', function() {
            const fieldName = $(this).attr('name').match(/field_(.+)_option/)?.[1];
            if (fieldName) {
                console.log('🔄 Field radio changed:', fieldName, 'option:', $(this).val());
                updateFieldDisplay(fieldName, null, true); // Enable auto-check
            }
        });

        // CRITICAL FIX: Also listen for ANY radio button change in multi-source fields
        $(document).on('change', 'input[type="radio"]', function() {
            const radioName = $(this).attr('name') || '';
            console.log('🔄 Any radio changed:', radioName, 'checked:', $(this).is(':checked'));

            // Check if this is a field option radio button
            if (radioName.includes('field_') && radioName.includes('_option')) {
                const fieldName = radioName.match(/field_(.+)_option/)?.[1];
                if (fieldName) {
                    console.log('🔄 Detected field radio change for:', fieldName);
                    setTimeout(() => {
                        updateFieldDisplay(fieldName, null, true);

                        // CRITICAL: If age_range radio changed, also update reading_level
                        if (fieldName === 'age_range') {
                            const ageRangeValue = getSelectedFieldValue('age_range');
                            const mappedReadingLevel = ageToReadingMap[ageRangeValue];
                            if (mappedReadingLevel) {
                                console.log('🔄 Radio change: Mapping age range', ageRangeValue, 'to reading level', mappedReadingLevel);
                                updateFieldDisplay('reading_level', mappedReadingLevel, true);
                            } else {
                                console.log('🔄 Radio change: No mapping found for age range', ageRangeValue);
                                updateFieldDisplay('reading_level', null, true);
                            }
                        }
                    }, 100);
                }
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

        // Check if field checkbox is checked first
        const fieldCheckbox = $(`.enrichment-field[data-field="${fieldName}"] input[type="checkbox"][value="${fieldName}"]`);
        const isChecked = fieldCheckbox.length ? fieldCheckbox.is(':checked') : false;

        if (!isChecked) {
            console.log('🔍 Field checkbox not checked, returning current value:', fieldData.current_value);
            return fieldData.current_value;
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

    /**
     * Update the visual display of a field when source selection changes
     * @param {string} fieldName - The field name to update
     * @param {string} overrideValue - Optional value to use instead of getting from selection
     * @param {boolean} autoCheck - Whether to automatically check the checkbox if value differs from database
     */
    function updateFieldDisplay(fieldName, overrideValue = null, autoCheck = false) {
        console.log('🎨 Updating field display for:', fieldName, 'override:', overrideValue, 'autoCheck:', autoCheck);

        const fieldContainer = $(`.enrichment-field[data-field="${fieldName}"]`);
        if (!fieldContainer.length) {
            console.log('🎨 Field container not found for:', fieldName);
            return;
        }

        const fieldData = window.currentEnrichmentData?.fields?.[fieldName];
        if (!fieldData) {
            console.log('🎨 No field data for:', fieldName);
            return;
        }

        const fieldCheckbox = fieldContainer.find(`input[type="checkbox"][value="${fieldName}"]`);
        const currentValue = fieldData.current_value;

        // Determine the value to display
        let displayValue = overrideValue;
        if (!displayValue) {
            // For multi-option fields, get the selected option value
            const checkedOption = $(`input[name="field_${fieldName}_option"]:checked`);
            if (checkedOption.length > 0) {
                const optionIndex = parseInt(checkedOption.val());
                if (fieldData.new_data && fieldData.new_data.options && fieldData.new_data.options[optionIndex]) {
                    displayValue = fieldData.new_data.options[optionIndex].value;
                    console.log('🎨 Using multi-option value:', displayValue, 'from option', optionIndex);
                }
            }

            // Fallback to single-source field value
            if (!displayValue && fieldData.new_data && fieldData.new_data.value !== undefined) {
                displayValue = fieldData.new_data.value;
                console.log('🎨 Using single-source value:', displayValue);
            }

            // Final fallback to current value
            if (!displayValue) {
                displayValue = currentValue;
                console.log('🎨 Using current value as fallback:', displayValue);
            }
        }

        console.log('🎨 Display value determined:', displayValue, 'current:', currentValue);

        // Determine if this value differs from database - CRITICAL FIX: Use isExactMatch for sophisticated comparison
        const valuesMatch = isExactMatch(currentValue, displayValue);
        const valuesDiffer = !valuesMatch;
        console.log('🎨 Values differ:', valuesDiffer, 'isExactMatch result:', valuesMatch);
        console.log('🎨 Comparison details:', {
            currentValue: currentValue,
            displayValue: displayValue,
            currentType: typeof currentValue,
            displayType: typeof displayValue,
            fieldName: fieldName
        });

        // CRITICAL FIX: For reading_level, also check if age_range has any source selected (synchronized fields)
        let shouldAutoCheck = autoCheck && valuesDiffer && fieldCheckbox.length;

        // Special case: if this is reading_level, auto-check it when:
        // 1. Age range checkbox is checked, OR
        // 2. Any age range source is selected (radio button)
        if (fieldName === 'reading_level') {
            const ageRangeCheckbox = $(`.enrichment-field[data-field="age_range"] input[type="checkbox"][value="age_range"]`);
            const ageRangeRadioSelected = $(`input[name*="age_range"][type="radio"]:checked`).length > 0;

            if (valuesDiffer && (
                (ageRangeCheckbox.length && ageRangeCheckbox.is(':checked')) ||
                ageRangeRadioSelected
            )) {
                shouldAutoCheck = true;
                console.log('🎨 Auto-checking reading_level because age_range is active (checkbox checked or radio selected) and values differ');
                console.log('🎨 Age range checkbox checked:', ageRangeCheckbox.is(':checked'), 'Radio selected:', ageRangeRadioSelected);
            }
        }

        // Auto-check checkbox if conditions are met
        if (shouldAutoCheck) {
            fieldCheckbox.prop('checked', true);
            console.log('🎨 Auto-checked checkbox for field:', fieldName);
        }

        // Check current checkbox state
        const isChecked = fieldCheckbox.length ? fieldCheckbox.is(':checked') : false;
        console.log('🎨 Field checkbox state after auto-check:', isChecked);

        // Update the "New Value" display - find the badge after "New Value:"
        const newValueBadge = fieldContainer.find('strong:contains("New Value:")').next('.badge');
        if (newValueBadge.length && displayValue) {
            newValueBadge.text(displayValue);
            console.log('🎨 Updated new value badge to:', displayValue);
        }

        // Determine database state based on whether checkbox is checked and values
        let databaseState = null;

        if (isChecked) {
            // Field is checked - compare display value with current value
            if (valuesDiffer) {
                databaseState = isEmpty(currentValue) ? 'database_empty' : 'database_wrong';
            } else {
                databaseState = 'matches_database';
            }
        } else {
            // Field is unchecked - always matches database (showing current value)
            databaseState = 'matches_database';
        }

        console.log('🎨 Database state:', databaseState, 'isChecked:', isChecked, 'valuesDiffer:', valuesDiffer);

        // CRITICAL FIX: Auto-uncheck checkbox if values match database (should be disabled)
        if (databaseState === 'matches_database' && isChecked) {
            console.log('🎨 CRITICAL_FIX: Values match database - unchecking and disabling field');
            fieldCheckbox.prop('checked', false);
            fieldCheckbox.prop('disabled', true);
            isChecked = false; // Update the local variable
        } else if (databaseState !== 'matches_database' && fieldCheckbox.prop('disabled')) {
            // Re-enable checkbox if it was previously disabled but now has different values
            console.log('🎨 CRITICAL_FIX: Values differ from database - re-enabling field');
            fieldCheckbox.prop('disabled', false);
        }

        // Remove existing state classes
        fieldContainer.removeClass('disabled-field matches-database database-wrong database-empty');

        // Apply appropriate styling based on state
        if (!isChecked) {
            // Checkbox unchecked - field is disabled, show as matching database
            fieldContainer.addClass('disabled-field matches-database');
            console.log('🎨 Field unchecked - added disabled-field and matches-database classes');
        } else {
            // Checkbox checked - field is enabled, remove disabled styling
            // CRITICAL FIX: Remove all disabled styling when checkbox is checked
            fieldContainer.find('.text-muted').removeClass('text-muted');
            fieldContainer.find('.disabled-field').removeClass('disabled-field');

            if (databaseState === 'matches_database') {
                fieldContainer.addClass('matches-database');
                console.log('🎨 Field checked and matches database');
            } else if (databaseState === 'database_wrong') {
                fieldContainer.addClass('database-wrong');
                console.log('🎨 Field checked - database wrong');
            } else if (databaseState === 'database_empty') {
                fieldContainer.addClass('database-empty');
                console.log('🎨 Field checked - database empty');
            }
            console.log('🎨 Field enabled - removed all disabled styling');
        }

        // Update confidence badge in the label - find the LAST badge (confidence percentage)
        const allBadges = fieldContainer.find('.form-check-label .badge');
        const confidenceBadge = allBadges.filter(function() {
            const text = $(this).text();
            return text.includes('%') || text.includes('Wrong') || text.includes('New');
        }).last();

        if (confidenceBadge.length) {
            if (databaseState === 'matches_database') {
                confidenceBadge.removeClass('badge-info badge-warning badge-danger').addClass('badge-success').text('(100%)');
            } else if (databaseState === 'database_wrong') {
                confidenceBadge.removeClass('badge-info badge-success badge-danger').addClass('badge-warning').text('(Wrong)');
            } else if (databaseState === 'database_empty') {
                confidenceBadge.removeClass('badge-info badge-warning badge-danger').addClass('badge-success').text('(New)');
            }
            console.log('🎨 Updated confidence badge for database state:', databaseState);
        }

        // Update the database state message box
        updateDatabaseStateMessage(fieldContainer, databaseState, currentValue, displayValue);
    }

    /**
     * Update the database state message box at the bottom of a field
     */
    function updateDatabaseStateMessage(fieldContainer, databaseState, currentValue, displayValue) {
        // Find existing state message box
        let stateMessageBox = fieldContainer.find('.mt-2.p-2.bg-light.border.rounded').last();

        // Remove existing state message if it exists
        if (stateMessageBox.length) {
            stateMessageBox.remove();
        }

        // Create new state message based on database state
        let stateHtml = '';

        switch (databaseState) {
            case 'matches_database':
                stateHtml = `
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
                stateHtml = `
                    <div class="mt-2 p-2 bg-light border border-warning rounded">
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Database Wrong</strong>
                            <span class="badge badge-warning ml-1">Update</span>
                        </div>
                        <small class="text-muted">New value differs from database - update recommended</small>
                    </div>
                `;
                break;
            case 'database_empty':
                stateHtml = `
                    <div class="mt-2 p-2 bg-light border border-success rounded">
                        <div class="text-success">
                            <i class="fas fa-plus-circle"></i> <strong>Database Empty</strong>
                            <span class="badge badge-success ml-1">Add</span>
                        </div>
                        <small class="text-muted">Adding missing data to database</small>
                    </div>
                `;
                break;
        }

        if (stateHtml) {
            fieldContainer.append(stateHtml);
        }
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
        console.log('🔄 Would update reading level display to:', expectedReading);
    }

    // Legacy function - keeping for compatibility but simplified
    function syncAgeRangeField(expectedAge, isRevertMode = false) {
        console.log('🔄 syncAgeRangeField called with:', expectedAge, 'revert mode:', isRevertMode);
        console.log('🔄 Would update age range display to:', expectedAge);
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
     * Normalize tag for comparison - handles common variations
     * @param {string} tag - Tag to normalize
     * @returns {string} - Normalized tag
     */
    function normalizeTagForComparison(tag) {
        if (!tag || typeof tag !== 'string') return tag;

        return tag
            // Fix common spacing issues
            .replace(/\s{2,}/g, ' ')  // Multiple spaces to single space
            .replace(/people\s+places/gi, 'people & places')  // "people  places" → "people & places"
            .replace(/people\s*&\s*places/gi, 'people & places')  // Normalize "people&places" variations
            // Add other common normalizations as needed
            .trim();
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
            console.log('🏷️ TAGS_DEBUG: ===== STARTING TAG COMPARISON =====');
            console.log('🏷️ TAGS_DEBUG: Raw input values:', {
                currentValue: currentValue,
                currentType: typeof currentValue,
                currentLength: currentValue?.length,
                actualNewValue: actualNewValue,
                newType: typeof actualNewValue,
                newLength: actualNewValue?.length,
                source: source
            });

            // Normalize both values to arrays for comparison
            let currentTags = [];
            let newTags = [];

            // CRITICAL FIX: Process current value (database value)
            console.log('🏷️ TAGS_DEBUG: Processing current value (database)...');
            if (Array.isArray(currentValue)) {
                const normalizedTags = currentValue
                    .map(tag => normalizeTagForComparison(tag.toLowerCase().trim()))
                    .filter(tag => tag.length > 0);

                // CRITICAL FIX: Remove duplicates after normalization
                currentTags = [...new Set(normalizedTags)].sort();
                console.log('🏷️ TAGS_DEBUG: Current value is array (before dedup):', normalizedTags);
                console.log('🏷️ TAGS_DEBUG: Current value is array (after dedup):', currentTags);
            } else if (typeof currentValue === 'string') {
                console.log('🏷️ TAGS_DEBUG: Current value is string, checking format...');

                // Check if it's a comma-separated list
                if (currentValue.includes(',')) {
                    currentTags = currentValue.split(',')
                        .map(tag => normalizeTagForComparison(tag.toLowerCase().trim()))
                        .filter(tag => tag.length > 0)
                        .sort();
                    console.log('🏷️ TAGS_DEBUG: Split comma-separated current value:', currentTags);
                } else {
                    // CRITICAL FIX: Use intelligent splitting for concatenated strings
                    console.log('🏷️ TAGS_DEBUG: Attempting to split concatenated current value:', currentValue);
                    const splitTags = splitConcatenatedTags(currentValue);
                    console.log('🏷️ TAGS_DEBUG: Split result:', splitTags);

                    if (splitTags.length > 1) {
                        currentTags = splitTags.map(tag => normalizeTagForComparison(tag.toLowerCase().trim())).filter(tag => tag.length > 0).sort();
                        console.log('🏷️ TAGS_DEBUG: Successfully split concatenated current value:', {
                            original: currentValue,
                            split: splitTags,
                            normalized: currentTags
                        });
                    } else {
                        // Single tag or unrecognized format
                        currentTags = [normalizeTagForComparison(currentValue.toLowerCase().trim())].filter(tag => tag.length > 0);
                        console.log('🏷️ TAGS_DEBUG: Treating as single tag:', currentTags);
                    }
                }
            } else {
                console.log('🏷️ TAGS_DEBUG: Current value is neither array nor string:', typeof currentValue);
                currentTags = [];
            }

            // CRITICAL FIX: Process new value (API response)
            console.log('🏷️ TAGS_DEBUG: Processing new value (API response)...');
            if (Array.isArray(actualNewValue)) {
                const normalizedTags = actualNewValue
                    .map(tag => normalizeTagForComparison(tag.toLowerCase().trim()))
                    .filter(tag => tag.length > 0);

                // CRITICAL FIX: Remove duplicates after normalization
                newTags = [...new Set(normalizedTags)].sort();
                console.log('🏷️ TAGS_DEBUG: New value is array (before dedup):', normalizedTags);
                console.log('🏷️ TAGS_DEBUG: New value is array (after dedup):', newTags);
            } else if (typeof actualNewValue === 'string') {
                console.log('🏷️ TAGS_DEBUG: New value is string, checking format...');

                // CRITICAL FIX: Check for duplicate content (the issue you mentioned)
                if (actualNewValue.includes(',') && actualNewValue.includes('People Places')) {
                    console.log('🏷️ TAGS_DEBUG: ⚠️ DETECTED DUPLICATE CONTENT in new value!');
                    console.log('🏷️ TAGS_DEBUG: Raw new value with duplicates:', actualNewValue);

                    // Split by comma first, then clean up duplicates
                    const commaSplit = actualNewValue.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                    console.log('🏷️ TAGS_DEBUG: After comma split:', commaSplit);

                    // Remove duplicates and normalize
                    const uniqueTags = [...new Set(commaSplit.map(tag => normalizeTagForComparison(tag.toLowerCase().trim())))];
                    newTags = uniqueTags.filter(tag => tag.length > 0).sort();
                    console.log('🏷️ TAGS_DEBUG: After deduplication:', newTags);

                } else if (actualNewValue.includes(',')) {
                    const splitTags = actualNewValue.split(',')
                        .map(tag => normalizeTagForComparison(tag.toLowerCase().trim()))
                        .filter(tag => tag.length > 0);

                    // CRITICAL FIX: Remove duplicates after normalization
                    newTags = [...new Set(splitTags)].sort();
                    console.log('🏷️ TAGS_DEBUG: Split comma-separated new value (before dedup):', splitTags);
                    console.log('🏷️ TAGS_DEBUG: Split comma-separated new value (after dedup):', newTags);
                } else {
                    // Single tag or concatenated string
                    console.log('🏷️ TAGS_DEBUG: Attempting to split concatenated new value:', actualNewValue);
                    const splitTags = splitConcatenatedTags(actualNewValue);
                    console.log('🏷️ TAGS_DEBUG: Split result for new value:', splitTags);

                    if (splitTags.length > 1) {
                        const normalizedTags = splitTags.map(tag => normalizeTagForComparison(tag.toLowerCase().trim())).filter(tag => tag.length > 0);
                        // CRITICAL FIX: Remove duplicates after normalization
                        newTags = [...new Set(normalizedTags)].sort();
                        console.log('🏷️ TAGS_DEBUG: Successfully split concatenated new value (before dedup):', normalizedTags);
                        console.log('🏷️ TAGS_DEBUG: Successfully split concatenated new value (after dedup):', newTags);
                    } else {
                        newTags = [normalizeTagForComparison(actualNewValue.toLowerCase().trim())].filter(tag => tag.length > 0);
                        console.log('🏷️ TAGS_DEBUG: Treating new value as single tag:', newTags);
                    }
                }
            } else {
                console.log('🏷️ TAGS_DEBUG: New value is neither array nor string:', typeof actualNewValue);
                newTags = [];
            }

            console.log('🏷️ TAGS_DEBUG: ===== NORMALIZED TAGS FOR COMPARISON =====');
            console.log('🏷️ TAGS_DEBUG: Current tags (database):', {
                tags: currentTags,
                count: currentTags.length,
                joined: currentTags.join(', ')
            });
            console.log('🏷️ TAGS_DEBUG: New tags (API):', {
                tags: newTags,
                count: newTags.length,
                joined: newTags.join(', ')
            });

            // CRITICAL FIX: Order-independent comparison
            console.log('🏷️ TAGS_DEBUG: ===== STARTING ORDER-INDEPENDENT COMPARISON =====');

            // Check if arrays contain the same elements (order-independent)
            const arraysEqual = currentTags.length === newTags.length &&
                               currentTags.every(tag => newTags.includes(tag)) &&
                               newTags.every(tag => currentTags.includes(tag));

            console.log('🏷️ TAGS_DEBUG: Order-independent comparison result:', {
                lengthsMatch: currentTags.length === newTags.length,
                allCurrentInNew: currentTags.every(tag => newTags.includes(tag)),
                allNewInCurrent: newTags.every(tag => currentTags.includes(tag)),
                finalResult: arraysEqual
            });

            if (!arraysEqual) {
                // Show detailed differences for debugging
                const onlyInCurrent = currentTags.filter(tag => !newTags.includes(tag));
                const onlyInNew = newTags.filter(tag => !currentTags.includes(tag));
                console.log('🏷️ TAGS_DEBUG: ===== DIFFERENCES FOUND =====');
                console.log('🏷️ TAGS_DEBUG: Only in current (database):', onlyInCurrent);
                console.log('🏷️ TAGS_DEBUG: Only in new (API):', onlyInNew);

                // Try fuzzy matching for similar tags
                console.log('🏷️ TAGS_DEBUG: Checking for fuzzy matches...');
                onlyInCurrent.forEach(currentTag => {
                    onlyInNew.forEach(newTag => {
                        const similarity = calculateTagSimilarity(currentTag, newTag);
                        if (similarity > 0.8) {
                            console.log(`🏷️ TAGS_DEBUG: Fuzzy match found: "${currentTag}" ≈ "${newTag}" (${Math.round(similarity * 100)}%)`);
                        }
                    });
                });
            }

            // LEGACY: Keep the old concatenated string handling for edge cases
            if (currentTags.length === 1 && newTags.length === 1) {
                const currentStr = currentTags[0];
                const newStr = newTags[0];

                // If both are long strings, they might be concatenated tags that weren't split properly
                if (currentStr.length > 30 && newStr.length > 30) {
                    console.log('🏷️ TAGS_DEBUG: ===== LEGACY CONCATENATED STRING HANDLING =====');
                    console.log('🏷️ TAGS_DEBUG: Detected long concatenated strings, checking content similarity');

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

            // FINAL RESULT: Use the order-independent comparison result
            console.log('🏷️ TAGS_DEBUG: ===== FINAL COMPARISON RESULT =====');
            console.log('🏷️ TAGS_DEBUG: Final comparison result:', {
                arraysEqual: arraysEqual,
                currentEmpty: currentTags.length === 0,
                newEmpty: newTags.length === 0
            });

            if (arraysEqual) {
                console.log('🏷️ TAGS_DEBUG: ✅ Tags match exactly - returning matches_database');
                return 'matches_database';
            } else if (currentTags.length === 0) {
                console.log('🏷️ TAGS_DEBUG: ➕ Current tags empty - returning database_empty');
                return 'database_empty';
            } else {
                console.log('🏷️ TAGS_DEBUG: ❌ Tags differ - returning database_wrong');
                return 'database_wrong';
            }
        }

        // Check for exact matches first (for non-tag fields)
        if (isExactMatch(currentValue, actualNewValue)) {
            console.log('🔍 EXACT_MATCH_DEBUG: Exact match found:', currentValue, '===', actualNewValue);
            return 'matches_database';
        }

        // CRITICAL DEBUG: Special logging for purchase_links field
        if (fieldName === 'purchase_links') {
            console.log('🛒 PURCHASE_LINKS_DEBUG: Database state check for purchase_links:', {
                fieldName: fieldName,
                currentValue: currentValue,
                actualNewValue: actualNewValue,
                source: source,
                currentEmpty: isEmpty(currentValue),
                newEmpty: isEmpty(actualNewValue)
            });

            // CRITICAL DEBUG: Test the exact match function with detailed logging
            console.log('🛒 PURCHASE_LINKS_DEBUG: About to call isExactMatch...');
            console.log('🛒 PURCHASE_LINKS_DEBUG: currentValue type:', typeof currentValue);
            console.log('🛒 PURCHASE_LINKS_DEBUG: actualNewValue type:', typeof actualNewValue);

            const exactMatchResult = isExactMatch(currentValue, actualNewValue);
            console.log('🛒 PURCHASE_LINKS_DEBUG: isExactMatch result:', exactMatchResult);
        }

        // Check if database is empty and we have data from ANY source
        if (isEmpty(currentValue) && !isEmpty(actualNewValue)) {
            return 'database_empty';
        }

        // Check if sources differ from database (including Amazon)
        if ((source === 'google_books + open_library' || source === 'amazon_derived') && !isEmpty(currentValue) && !isEmpty(actualNewValue)) {
            if (!isExactMatch(currentValue, actualNewValue)) {
                console.log('🔍 DATABASE_WRONG_DEBUG: Database wrong detected for source', source, ':', currentValue, '!==', actualNewValue);
                return 'database_wrong';
            } else {
                // Values match exactly - return matches_database
                console.log('🔍 DATABASE_MATCH_DEBUG: Values match exactly for source', source);
                return 'matches_database';
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

    /**
     * Split concatenated tag strings intelligently
     * Handles cases like "AfricaAlgeriaBerbersChildren's Fiction..."
     */
    function splitConcatenatedTags(str) {
        if (!str || typeof str !== 'string') return [];

        // First, handle common patterns in concatenated genre strings
        let processed = str
            // Add spaces before capital letters (camelCase)
            .replace(/([a-z])([A-Z])/g, '$1|$2')
            // Handle specific patterns like "People & Places"
            .replace(/&/g, ' and ')
            // Handle apostrophes in "Children's Fiction"
            .replace(/'/g, ' ')
            // Split on the pipe separators we added
            .split('|')
            // Clean each tag
            .map(tag => tag.trim())
            .filter(tag => tag.length > 0);

        // Further clean up and filter
        return processed
            .map(tag => {
                // Capitalize first letter of each word
                return tag.replace(/\b\w/g, l => l.toUpperCase());
            })
            .filter(tag => tag.length > 2) // Remove very short tags
            .filter((tag, index, arr) => arr.indexOf(tag) === index); // Remove duplicates
    }

    /**
     * Calculate similarity between two tag strings for fuzzy matching
     * Returns a value between 0 and 1 (1 = identical)
     */
    function calculateTagSimilarity(str1, str2) {
        if (!str1 || !str2) return 0;
        if (str1 === str2) return 1;

        // Normalize strings
        const s1 = str1.toLowerCase().trim();
        const s2 = str2.toLowerCase().trim();

        // Calculate Levenshtein distance
        const matrix = [];
        for (let i = 0; i <= s2.length; i++) {
            matrix[i] = [i];
        }
        for (let j = 0; j <= s1.length; j++) {
            matrix[0][j] = j;
        }
        for (let i = 1; i <= s2.length; i++) {
            for (let j = 1; j <= s1.length; j++) {
                if (s2.charAt(i - 1) === s1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }

        const distance = matrix[s2.length][s1.length];
        const maxLength = Math.max(s1.length, s2.length);
        return maxLength === 0 ? 1 : (maxLength - distance) / maxLength;
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
    window.splitConcatenatedTags = splitConcatenatedTags;
}
