// Book Validation JavaScript
// Prevents multiple script loading with a guard
if (typeof window.bookValidationLoaded === 'undefined') {
    window.bookValidationLoaded = true;

    $(document).ready(function() {
        // Auto-validate all ISBNs on page load (unless disabled)
        let autoValidationEnabled = true;
        if (autoValidationEnabled) {
            autoValidateAllISBNs();
        }

        // Check Goodreads status for all books after a delay to ensure elements exist
        setTimeout(function() {
            checkAllGoodreadsStatus();
        }, 2000); // 2 second delay to allow auto-validation to create the elements

        // ISBN Validation Tab Handlers
        $('.select-all-checkbox').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.item-checkbox').prop('checked', isChecked);
        });

        // Use event delegation for dynamically created fix buttons
        $(document).on('click', '.fix-isbn-btn', function() {
            const bookId = $(this).data('book-id');
            const bookTitle = $(this).data('book-title');
            const author = $(this).data('author');
            const publisher = $(this).data('publisher');
            const pubDate = $(this).data('pub-date');
            const format = $(this).data('format');

            const $button = $(this);
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Searching...');

            // Make AJAX call to get ISBN suggestions
            $.ajax({
                url: 'book-validation-ajax.php',
                method: 'POST',
                data: {
                    action: 'fix_isbn',
                    book_id: bookId
                },
                success: function(response) {
                    if (typeof response === 'object' && response.status === 'success') {
                        // Create a proper selection interface with current book data
                        showISBNSelectionModal(response.suggestions, {
                            title: response.current_title,
                            author: response.current_author,
                            publisher: response.current_publisher,
                            year: response.current_year,
                            format: format
                        }, bookId);
                    } else {
                        alert('Error: ' + (response.message || 'Failed to find ISBN suggestions'));
                    }
                    $button.prop('disabled', false).html('<i class="fas fa-wrench"></i> Fix');
                },
                error: function(xhr, status, error) {
                    alert('Error searching for ISBN: ' + error);
                    $button.prop('disabled', false).html('<i class="fas fa-wrench"></i> Fix');
                }
            });
        });

        // Use event delegation for dynamically created enrich data buttons
        $(document).on('click', '.enrich-data-btn', function() {
            const bookId = $(this).data('book-id');
            const bookTitle = $(this).data('book-title');
            const author = $(this).data('author');
            const currentISBN = $(this).data('current-isbn');

            // Open the data enrichment modal
            openDataEnrichmentModal(bookId, bookTitle, author, currentISBN);
        });

        // Handle disable/enable auto-validation
        $('#disable-auto-validation').click(function() {
            autoValidationEnabled = false;
            $(this).hide();
            $('#manual-validation').show();
            $('.alert-info').removeClass('alert-info').addClass('alert-warning');
            $('.alert-warning i').removeClass('fa-info-circle').addClass('fa-exclamation-triangle');
            $('.alert-warning').find('i:first').after(' Auto-validation disabled.');
        });

        $('#manual-validation').click(function() {
            autoValidateAllISBNs();
        });

        // Test enrichment with first book
        $('#test-enrichment').click(function() {
            const $firstRow = $('#isbn-validation-table tbody tr:first');
            if ($firstRow.length > 0) {
                const bookId = $firstRow.find('.item-checkbox').val();
                const bookTitle = $firstRow.find('td:nth-child(2)').text().trim();

                // Try to get author from the book data (we'll need to make an AJAX call for this)
                $.ajax({
                    url: 'book-validation-ajax.php',
                    method: 'POST',
                    data: {
                        action: 'get_book_data',
                        book_id: bookId
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            const book = response.book;
                            openDataEnrichmentModal(bookId, book.title, book.author, book.isbn13 || book.isbn || '');
                        } else {
                            // Fallback with basic data
                            openDataEnrichmentModal(bookId, bookTitle, 'Unknown Author', '');
                        }
                    },
                    error: function() {
                        // Fallback with basic data
                        openDataEnrichmentModal(bookId, bookTitle, 'Unknown Author', '');
                    }
                });
            } else {
                alert('No books found to test enrichment with.');
            }
        });

        // Test AJAX endpoint
        $('#test-ajax').click(function() {
            $.ajax({
                url: 'book-import-validate/ajax/data-enrichment-ajax.php',
                method: 'POST',
                data: {
                    action: 'test'
                },
                dataType: 'json',
                success: function(response) {
                    alert('AJAX Test Success: ' + response.message);
                    console.log('AJAX response:', response);
                },
                error: function(xhr, status, error) {
                    alert('AJAX Test Failed: ' + error);
                    console.error('AJAX Error:', { xhr, status, error, responseText: xhr.responseText });
                }
            });
        });

        // Debug Goodreads status
        $('#debug-goodreads').click(function() {
            console.log('=== GOODREADS DEBUG ===');
            console.log('Total .goodreads-status elements found:', $('.goodreads-status').length);

            $('.goodreads-status').each(function(index) {
                const $element = $(this);
                console.log(`Element ${index}:`, {
                    html: $element[0].outerHTML,
                    isbn: $element.data('isbn'),
                    bookId: $element.data('book-id'),
                    visible: $element.is(':visible'),
                    parent: $element.parent()[0].tagName
                });
            });

            // Force re-check Goodreads status
            console.log('Re-running Goodreads status check...');
            checkAllGoodreadsStatus();
        });

        // Data Enrichment Tab Handlers
        $('#enrichBookSelection').change(function() {
            const selection = $(this).val();
            if (selection === 'specific') {
                $('.enrich-specific').show();
            } else {
                $('.enrich-specific').hide();
            }
        });

        // Function to auto-validate all ISBNs on page load
        function autoValidateAllISBNs() {
            const $progress = $('#validation-progress');
            const $progressBar = $progress.find('.progress-bar');
            const $progressText = $progress.find('small');

            // Get all book rows - use ID selector for the table
            const $rows = $('#isbn-validation-table tbody tr');
            const totalBooks = $rows.length;
            let completedBooks = 0;

            if (totalBooks === 0) {
                alert('No books found to validate.');
                return;
            }

            // Show progress bar
            $progress.show();
            $progressBar.css('width', '0%');
            $progressText.text('Auto-validating ISBNs...');

            // Process each book
            $rows.each(function(index) {
                const $row = $(this);
                const bookId = $row.find('.item-checkbox').val(); // Use the correct checkbox class
                const $statusCell = $row.find('td:nth-child(7)'); // Status column (adjust for new columns: checkbox, title, isbn, publisher, date, format, status)

                // Add a small delay to avoid overwhelming the APIs
                setTimeout(() => {
                    // Show loading state
                    $statusCell.html('<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Checking...</span>');

                    // Make AJAX call to validate this book
                    $.ajax({
                        url: 'book-validation-ajax.php',
                        method: 'POST',
                        data: {
                            action: 'validate_isbn',
                            book_id: bookId
                        },
                        success: function(response) {
                            // jQuery automatically parses JSON when Content-Type is application/json
                            if (typeof response === 'object' && response.status === 'success') {
                                const validation = response.validation;

                                // Preserve existing Goodreads status if it exists
                                const existingGoodreadsStatus = $statusCell.find('.goodreads-status');
                                let goodreadsHtml = '';
                                if (existingGoodreadsStatus.length > 0) {
                                    goodreadsHtml = '<br>' + existingGoodreadsStatus[0].outerHTML;
                                } else {
                                    // Add new Goodreads status element if it doesn't exist
                                    const isbn = $row.find('td:nth-child(3)').text().trim(); // ISBN column
                                    goodreadsHtml = `<br><span class="goodreads-status badge badge-secondary" data-book-id="${bookId}" data-isbn="${isbn}"><i class="fas fa-spinner fa-spin"></i> Checking...</span>`;
                                }

                                $statusCell.html(`<span class="badge badge-${validation.class}" title="${validation.message}"><i class="fas fa-${validation.icon}"></i> ${validation.status.charAt(0).toUpperCase() + validation.status.slice(1)}</span>${goodreadsHtml}`);

                                // Update Fix button if needed - preserve existing Enrich buttons
                                const $actionsCell = $row.find('td:last-child');
                                const bookTitle = $row.find('td:nth-child(2)').text().trim(); // Title column
                                const detailsButton = `<a href="book-import-validate-new.php?action=validate_book&book_id=${bookId}" class="btn btn-sm btn-info" title="View detailed validation data"><i class="fas fa-search"></i></a>`;

                                // Preserve existing Enrich button if it exists
                                const existingEnrichBtn = $actionsCell.find('.enrich-data-btn');
                                const enrichButton = existingEnrichBtn.length > 0 ? ' ' + existingEnrichBtn[0].outerHTML : '';

                                if (validation.status === 'invalid' || validation.status === 'mismatch') {
                                    if (!$actionsCell.find('.fix-isbn-btn').length) {
                                        const author = 'Unknown'; // We'll need to get this from somewhere else
                                        $actionsCell.html(detailsButton + ' <button class="btn btn-sm btn-warning fix-isbn-btn" data-book-id="' + bookId + '" data-book-title="' + bookTitle + '" data-author="' + author + '"><i class="fas fa-wrench"></i> Fix</button>' + enrichButton);
                                    }
                                } else {
                                    // Show Details button and preserve Enrich button for valid ISBNs
                                    $actionsCell.html(detailsButton + enrichButton);
                                }
                            } else {
                                $statusCell.html('<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Error</span>');
                            }

                            // Update progress
                            completedBooks++;
                            const progress = Math.round((completedBooks / totalBooks) * 100);
                            $progressBar.css('width', progress + '%');
                            $progressText.text(`Validated ${completedBooks} of ${totalBooks} books...`);

                            // Check if all done
                            if (completedBooks === totalBooks) {
                                setTimeout(() => {
                                    $progress.hide();
                                    $progressText.text('Auto-validation complete!');
                                    // Now check Goodreads status for all books
                                    setTimeout(() => {
                                        checkAllGoodreadsStatus();
                                    }, 500); // Small delay to ensure DOM is updated
                                }, 1000);
                            }
                        },
                        error: function() {
                            $statusCell.html('<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Error</span>');

                            // Update progress even on error
                            completedBooks++;
                            const progress = Math.round((completedBooks / totalBooks) * 100);
                            $progressBar.css('width', progress + '%');
                            $progressText.text(`Validated ${completedBooks} of ${totalBooks} books...`);

                            if (completedBooks === totalBooks) {
                                setTimeout(() => {
                                    $progress.hide();
                                    // Check Goodreads status even if some validations failed
                                    setTimeout(() => {
                                        checkAllGoodreadsStatus();
                                    }, 500);
                                }, 1000);
                            }
                        }
                    });
                }, index * 200); // 200ms delay between each request
            });
        }

        // Function to check Goodreads status for all books
        function checkAllGoodreadsStatus() {
            $('.goodreads-status').each(function(index) {
                const $statusElement = $(this);
                const isbn = $statusElement.data('isbn');

                if (!isbn) {
                    $statusElement.html('<span class="badge badge-secondary">No ISBN</span>');
                    return;
                }

                // Add delay to avoid overwhelming Goodreads
                setTimeout(() => {
                    $.ajax({
                        url: 'book-import-validate/ajax/data-enrichment-ajax.php',
                        method: 'POST',
                        data: {
                            action: 'check_goodreads_isbn',
                            isbn: isbn
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Goodreads response for ISBN ' + isbn + ':', response);
                            if (response.success && response.exists) {
                                $statusElement.html('<span class="badge" style="background-color: #28a745; color: white; border: none;"><i class="fas fa-book"></i> Goodreads</span>');
                            } else if (response.success) {
                                $statusElement.html('<span class="badge" style="background-color: #dc3545; color: white; border: none;"><i class="fas fa-times"></i> Not on Goodreads</span>');
                            } else {
                                $statusElement.html('<span class="badge" style="background-color: #ffc107; color: white; border: none;"><i class="fas fa-exclamation-triangle"></i> Error</span>');
                                console.error('Goodreads validation error:', response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Goodreads AJAX error for ISBN ' + isbn + ':', { xhr, status, error });
                            $statusElement.html('<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Error</span>');
                        }
                    });
                }, index * 500); // 500ms delay between each request
            });
        }

        // Function to show ISBN selection modal
        function showISBNSelectionModal(suggestions, currentBook, bookId) {
            if (suggestions.length === 0) {
                alert('No ISBN suggestions found for this book.');
                return;
            }

            const suggestionsToShow = suggestions.slice(0, 5); // Show top 5 matches

            // Create modal content with current book info
            let modalContent = `
                <div style="max-width: 700px;">
                    <h4>Select Correct ISBN for "${currentBook.title}"</h4>
                    <div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff;">
                        <strong>Current Book Data:</strong><br>
                        <strong>Publisher:</strong> ${currentBook.publisher || 'Unknown'} |
                        <strong>Year:</strong> ${currentBook.year || 'Unknown'} |
                        <strong>Format:</strong> ${currentBook.format || 'Unknown'}
                    </div>
                    <p>Found ${suggestionsToShow.length} possible matches. Select the correct one:</p>
                    <div style="max-height: 400px; overflow-y: auto;">
            `;

            suggestionsToShow.forEach((suggestion, index) => {
                const isbn10 = suggestion.isbn || 'N/A';
                const isbn13 = suggestion.isbn13 || 'N/A';
                const publisher = suggestion.publisher || 'Unknown';
                const pubYear = suggestion.publication_date || suggestion.publication_year || 'Unknown';
                const format = suggestion.format || 'Unknown';
                const matchScore = suggestion.match_score || 0;
                const matchReasons = suggestion.match_reasons || 'No specific reasons';

                // Color code based on match score
                let borderColor = '#ddd';
                let bgColor = '#fff';
                if (matchScore >= 150) {
                    borderColor = '#28a745'; // Green for high confidence
                    bgColor = '#f8fff9';
                } else if (matchScore >= 100) {
                    borderColor = '#ffc107'; // Yellow for medium confidence
                    bgColor = '#fffef8';
                }

                modalContent += `
                    <div style="border: 2px solid ${borderColor}; background: ${bgColor}; margin: 10px 0; padding: 15px; border-radius: 5px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h5 style="margin: 0;">${suggestion.title}</h5>
                            <span style="background: ${borderColor}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                                Score: ${matchScore}
                            </span>
                        </div>
                        <p><strong>Author:</strong> ${suggestion.author}</p>
                        <p><strong>Publisher:</strong> ${publisher} | <strong>Year:</strong> ${pubYear} | <strong>Format:</strong> ${format}</p>
                        <p><strong>ISBN-10:</strong> ${isbn10} | <strong>ISBN-13:</strong> ${isbn13}</p>
                        <p><strong>Match reasons:</strong> <em>${matchReasons}</em></p>
                        <button class="btn btn-primary" onclick="selectISBN('${isbn13}', '${isbn10}', ${bookId})">
                            Select This ISBN
                        </button>
                    </div>
                `;
            });

            modalContent += `
                    </div>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-secondary" onclick="closeISBNModal()">Cancel</button>
                    </div>
                </div>
            `;

            // Create and show modal
            const modal = document.createElement('div');
            modal.id = 'isbn-selection-modal';
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.5); z-index: 1000;
                display: flex; align-items: center; justify-content: center;
            `;

            const modalDialog = document.createElement('div');
            modalDialog.style.cssText = `
                background: white; padding: 20px; border-radius: 10px;
                max-width: 90%; max-height: 90%; overflow-y: auto;
            `;
            modalDialog.innerHTML = modalContent;

            modal.appendChild(modalDialog);
            document.body.appendChild(modal);
        }

        // Function to select an ISBN and update the database
        window.selectISBN = function(isbn13, isbn10, bookId) {
            // Use the best available ISBN (prefer ISBN-13)
            const selectedISBN = (isbn13 && isbn13 !== 'N/A') ? isbn13 : isbn10;

            if (!selectedISBN || selectedISBN === 'N/A') {
                alert('No valid ISBN selected.');
                return;
            }

            // Make AJAX call to update the ISBN
            $.ajax({
                url: 'book-validation-ajax.php',
                method: 'POST',
                data: {
                    action: 'update_isbn',
                    book_id: bookId,
                    isbn: selectedISBN
                },
                success: function(response) {
                    if (typeof response === 'object' && response.status === 'success') {
                        alert('ISBN updated successfully!');
                        closeISBNModal();
                        // Refresh the page to show updated data
                        window.location.reload();
                    } else {
                        alert('Error updating ISBN: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error updating ISBN: ' + error);
                }
            });
        };

        // Function to close the ISBN selection modal
        window.closeISBNModal = function() {
            const modal = document.getElementById('isbn-selection-modal');
            if (modal) {
                modal.remove();
            }
        };
    });
}
