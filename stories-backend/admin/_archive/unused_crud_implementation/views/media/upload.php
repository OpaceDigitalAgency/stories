<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Upload Media</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL; ?>/media.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Media Library
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?php echo ADMIN_URL; ?>/media.php?action=upload" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="file" class="form-label required">File</label>
                            <input type="file" class="form-control media-upload" id="file" name="file" accept="image/*" data-preview="#file_preview" required>
                            <div class="invalid-feedback">
                                Please select a file to upload.
                            </div>
                            <div class="form-text">
                                Allowed file types: JPG, PNG, GIF, WEBP. Maximum file size: 5MB.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="entity_type" class="form-label required">Entity Type</label>
                            <select class="form-select" id="entity_type" name="entity_type" required>
                                <option value="">-- Select Entity Type --</option>
                                <?php foreach ($entityTypes as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select an entity type.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="entity_id" class="form-label required">Entity ID</label>
                            <input type="number" class="form-control" id="entity_id" name="entity_id" min="1" required>
                            <div class="invalid-feedback">
                                Please enter a valid entity ID.
                            </div>
                            <div class="form-text">
                                The ID of the entity this media belongs to.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label required">Media Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">-- Select Media Type --</option>
                                <?php foreach ($mediaTypes as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a media type.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="alt_text" class="form-label">Alt Text</label>
                            <input type="text" class="form-control" id="alt_text" name="alt_text">
                            <div class="form-text">
                                Alternative text for accessibility.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Preview</label>
                            <div class="border rounded p-3 d-flex justify-content-center align-items-center" style="min-height: 300px;">
                                <img id="file_preview" class="img-fluid" style="max-height: 300px; display: none;">
                                <div id="preview_placeholder" class="text-muted">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <p>Image preview will appear here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo ADMIN_URL; ?>/media.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Media
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show/hide preview placeholder based on file selection
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file');
        const preview = document.getElementById('file_preview');
        const placeholder = document.getElementById('preview_placeholder');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
        });
    });
</script>