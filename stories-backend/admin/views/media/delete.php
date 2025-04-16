<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Delete Media</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL; ?>/media.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Media Library
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-danger">
                <h4 class="alert-heading">Warning!</h4>
                <p>Are you sure you want to delete this media file? This action cannot be undone.</p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Media Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>ID</h6>
                                <p class="p-2 bg-light rounded"><?php echo htmlspecialchars($media['id']); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Entity Type</h6>
                                <p class="p-2 bg-light rounded"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $media['entity_type']))); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Entity ID</h6>
                                <p class="p-2 bg-light rounded"><?php echo htmlspecialchars($media['entity_id']); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Media Type</h6>
                                <p class="p-2 bg-light rounded"><?php echo htmlspecialchars(ucfirst($media['type'])); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Alt Text</h6>
                                <p class="p-2 bg-light rounded"><?php echo htmlspecialchars($media['alt_text'] ?? 'Not set'); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Dimensions</h6>
                                <p class="p-2 bg-light rounded"><?php echo htmlspecialchars($media['width'] ?? '0'); ?> x <?php echo htmlspecialchars($media['height'] ?? '0'); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Created At</h6>
                                <p class="p-2 bg-light rounded"><?php echo date('F d, Y H:i:s', strtotime($media['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Media Preview</h5>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?php echo htmlspecialchars($media['url']); ?>" alt="<?php echo htmlspecialchars($media['alt_text'] ?? ''); ?>" class="img-fluid rounded" style="max-height: 300px;">
                            
                            <div class="mt-3">
                                <a href="<?php echo htmlspecialchars($media['url']); ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-external-link-alt"></i> View Full Size
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form action="<?php echo ADMIN_URL; ?>/media.php?action=delete&id=<?php echo $media['id']; ?>" method="post">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo ADMIN_URL; ?>/media.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Media
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>