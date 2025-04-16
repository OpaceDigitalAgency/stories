<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Media Library</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL; ?>/media.php?action=upload" class="btn btn-sm btn-primary">
                <i class="fas fa-upload"></i> Upload Media
            </a>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo ADMIN_URL; ?>/media.php" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="entity_type" class="form-label">Entity Type</label>
                    <select class="form-select" id="entity_type" name="entity_type" onchange="this.form.submit()">
                        <option value="">All Entity Types</option>
                        <?php foreach ($entityTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filters['entity_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Media Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="">All Media Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filters['type'] === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="pageSize" class="form-label">Show</label>
                    <select class="form-select" id="pageSize" name="pageSize" onchange="this.form.submit()">
                        <option value="20" <?php echo $pagination['pageSize'] == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $pagination['pageSize'] == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $pagination['pageSize'] == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="<?php echo ADMIN_URL; ?>/media.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Media Grid -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($media)): ?>
                <div class="alert alert-info">
                    No media files found.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($media as $item): ?>
                        <div class="col-md-3 col-sm-4 col-6 mb-4">
                            <div class="card h-100">
                                <div class="card-img-top position-relative" style="height: 150px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($item['url']); ?>" alt="<?php echo htmlspecialchars($item['alt_text'] ?? ''); ?>" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate"><?php echo htmlspecialchars($item['alt_text'] ?? 'Unnamed'); ?></h6>
                                    <p class="card-text small mb-1">
                                        <strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($item['type'])); ?>
                                    </p>
                                    <p class="card-text small mb-1">
                                        <strong>Entity:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['entity_type']))); ?> #<?php echo htmlspecialchars($item['entity_id']); ?>
                                    </p>
                                    <p class="card-text small mb-1">
                                        <strong>Dimensions:</strong> <?php echo htmlspecialchars($item['width'] ?? '0'); ?> x <?php echo htmlspecialchars($item['height'] ?? '0'); ?>
                                    </p>
                                    <p class="card-text small mb-1">
                                        <strong>Date:</strong> <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo ADMIN_URL; ?>/media.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger delete-confirm" data-bs-toggle="tooltip" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['pageCount'] > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL; ?>/media.php?page=<?php echo ($pagination['page'] - 1); ?>&entity_type=<?php echo urlencode($filters['entity_type']); ?>&type=<?php echo urlencode($filters['type']); ?>&pageSize=<?php echo $pagination['pageSize']; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-chevron-left"></i> Previous</span>
                                </li>
                            <?php endif; ?>

                            <?php
                                $startPage = max(1, $pagination['page'] - 2);
                                $endPage = min($pagination['pageCount'], $startPage + 4);
                                if ($endPage - $startPage < 4) {
                                    $startPage = max(1, $endPage - 4);
                                }
                            ?>

                            <?php if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL; ?>/media.php?page=1&entity_type=<?php echo urlencode($filters['entity_type']); ?>&type=<?php echo urlencode($filters['type']); ?>&pageSize=<?php echo $pagination['pageSize']; ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo ADMIN_URL; ?>/media.php?page=<?php echo $i; ?>&entity_type=<?php echo urlencode($filters['entity_type']); ?>&type=<?php echo urlencode($filters['type']); ?>&pageSize=<?php echo $pagination['pageSize']; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $pagination['pageCount']): ?>
                                <?php if ($endPage < $pagination['pageCount'] - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL; ?>/media.php?page=<?php echo $pagination['pageCount']; ?>&entity_type=<?php echo urlencode($filters['entity_type']); ?>&type=<?php echo urlencode($filters['type']); ?>&pageSize=<?php echo $pagination['pageSize']; ?>"><?php echo $pagination['pageCount']; ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($pagination['page'] < $pagination['pageCount']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL; ?>/media.php?page=<?php echo ($pagination['page'] + 1); ?>&entity_type=<?php echo urlencode($filters['entity_type']); ?>&type=<?php echo urlencode($filters['type']); ?>&pageSize=<?php echo $pagination['pageSize']; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Next <i class="fas fa-chevron-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>