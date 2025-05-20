$(document).ready(function() {
    // Handle scrape reviews button clicks
    $(document).on('click', '[data-scrape-reviews]', function(e) {
        e.preventDefault();
        const bookId = $(this).data('book-id');
        const url = `book-scrape-reviews.php?book_id=${bookId}`;

        // Create modal if it doesn't exist
        if (!$('#scrapeReviewsModal').length) {
            $('body').append(`
                <div class="modal fade" id="scrapeReviewsModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Scrape Reviews</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <iframe style="width: 100%; height: 600px; border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }

        // Load the URL in the iframe
        $('#scrapeReviewsModal iframe').attr('src', url);
        $('#scrapeReviewsModal').modal('show');
    });
});