<?php
// Book table template
?>
<form id="books-form" method="post" action="book-import-process.php">
    <div class="bulk-actions mb-3">
        <div class="d-flex gap-2">
            <select class="form-control w-auto" name="bulk_action" id="bulk-action-books">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete Selected</option>
                <option value="validate">Validate ISBNs</option>
                <option value="scrape">Scrape Reviews</option>
            </select>
            <button type="submit" class="btn btn-primary" id="apply-bulk-action-books">Apply</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th width="30"><input type="checkbox" id="select-all-books" class="select-all-checkbox"></th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Status</th>
                    <th>Reviews</th>
                    <th>Rating</th>
                    <th>Missing Data</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No books found. Import some books first.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><input type="checkbox" name="book_ids[]" value="<?php echo $book['id']; ?>" class="book-checkbox"></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo $book['isbn']; ?></td>
                            <td><?php echo $book['status']; ?></td>
                            <td><?php echo $book['reviews']; ?></td>
                            <td><?php echo $book['rating']; ?></td>
                            <td><?php echo $book['missing_data']; ?></td>
                            <td><?php echo $book['actions']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>