<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo htmlspecialchars($entityNamePlural); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=create'; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add <?php echo htmlspecialchars($entityName); ?>
            </a>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php'; ?>" method="get" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex">
                        <label for="pageSize" class="col-form-label me-2">Show:</label>
                        <select class="form-select" id="pageSize" name="pageSize" onchange="this.form.submit()">
                            <option value="10" <?php echo $pagination['pageSize'] == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $pagination['pageSize'] == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $pagination['pageSize'] == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $pagination['pageSize'] == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php'; ?>" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Items List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="alert alert-info">
                    No <?php echo htmlspecialchars(strtolower($entityNamePlural)); ?> found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <?php foreach ($fields as $field): ?>
                                    <?php if ($field['list'] ?? true): ?>
                                        <th>
                                            <?php if (in_array($field['name'], $sortableFields ?? [])): ?>
                                                <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?sort=' . $field['name'] . '&direction=' . ($sort['field'] == $field['name'] && $sort['direction'] == 'asc' ? 'desc' : 'asc') . '&search=' . urlencode($search) . '&pageSize=' . $pagination['pageSize']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($field['label']); ?>
                                                    <?php if ($sort['field'] == $field['name']): ?>
                                                        <i class="fas fa-sort-<?php echo $sort['direction'] == 'asc' ? 'up' : 'down'; ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($field['label']); ?>
                                            <?php endif; ?>
                                        </th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <th class="table-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <?php foreach ($fields as $field): ?>
                                        <?php if ($field['list'] ?? true): ?>
                                            <td>
                                                <?php
                                                    // Get field value with proper fallback
                                                    $value = isset($item['attributes']) && isset($item['attributes'][$field['name']])
                                                        ? $item['attributes'][$field['name']]
                                                        : ($item[$field['name']] ?? '');
                                                    
                                                    // Format value based on field type
                                                    switch ($field['type']) {
                                                        case 'boolean':
                                                            echo $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
                                                            break;
                                                        case 'date':
                                                            echo $value ? date('M d, Y', strtotime($value)) : '<span class="text-muted">Not set</span>';
                                                            break;
                                                        case 'datetime':
                                                            echo $value ? date('M d, Y H:i', strtotime($value)) : '<span class="text-muted">Not set</span>';
                                                            break;
                                                        case 'image':
                                                            if ($value && isset($value['data']['attributes']['url'])) {
                                                                echo '<img src="' . htmlspecialchars($value['data']['attributes']['url']) . '" alt="' . htmlspecialchars($value['data']['attributes']['alternativeText'] ?? '') . '" class="media-thumbnail">';
                                                            } else if ($value && isset($value['url'])) {
                                                                echo '<img src="' . htmlspecialchars($value['url']) . '" alt="' . htmlspecialchars($value['alternativeText'] ?? '') . '" class="media-thumbnail">';
                                                            }
                                                            break;
                                                        case 'relation':
                                                            if (isset($value['data']) && !empty($value['data'])) {
                                                                if (is_array($value['data']) && isset($value['data'][0])) {
                                                                    // Handle array of relations
                                                                    $relName = $value['data'][0]['attributes']['name'] ??
                                                                              $value['data'][0]['attributes']['title'] ??
                                                                              $value['data'][0]['name'] ??
                                                                              $value['data'][0]['title'] ??
                                                                              'Unnamed';
                                                                    echo htmlspecialchars($relName);
                                                                } else {
                                                                    // Handle single relation
                                                                    $relName = $value['data']['attributes']['name'] ??
                                                                              $value['data']['attributes']['title'] ??
                                                                              $value['data']['name'] ??
                                                                              $value['data']['title'] ??
                                                                              'Unnamed';
                                                                    echo htmlspecialchars($relName);
                                                                }
                                                            } else if (is_array($value) && !empty($value)) {
                                                                // Direct array without data wrapper
                                                                if (isset($value[0])) {
                                                                    $relName = $value[0]['attributes']['name'] ??
                                                                              $value[0]['attributes']['title'] ??
                                                                              $value[0]['name'] ??
                                                                              $value[0]['title'] ??
                                                                              'Unnamed';
                                                                    echo htmlspecialchars($relName);
                                                                } else {
                                                                    $relName = $value['attributes']['name'] ??
                                                                              $value['attributes']['title'] ??
                                                                              $value['name'] ??
                                                                              $value['title'] ??
                                                                              'Unnamed';
                                                                    echo htmlspecialchars($relName);
                                                                }
                                                            } else {
                                                                echo '<span class="text-muted">No data</span>';
                                                            }
                                                            break;
                                                        case 'array':
                                                            if (is_array($value)) {
                                                                echo count($value) . ' ' . (count($value) == 1 ? 'item' : 'items');
                                                            } else if (is_string($value) && !empty($value)) {
                                                                echo htmlspecialchars($value);
                                                            } else {
                                                                echo '<span class="text-muted">Empty</span>';
                                                            }
                                                            break;
                                                        default:
                                                            // Truncate long text
                                                            if (is_string($value) && strlen($value) > 100) {
                                                                echo htmlspecialchars(substr($value, 0, 100)) . '...';
                                                            } else if (is_string($value) && !empty($value)) {
                                                                echo htmlspecialchars($value);
                                                            } else if (is_numeric($value)) {
                                                                echo htmlspecialchars((string)$value);
                                                            } else {
                                                                echo '<span class="text-muted">Not set</span>';
                                                            }
                                                            break;
                                                    }
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <td class="table-actions">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=view&id=' . $item['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=edit&id=' . $item['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=delete&id=' . $item['id']; ?>" class="btn btn-sm btn-danger delete-confirm" data-bs-toggle="tooltip" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['pageCount'] > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($pagination['page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?page=' . ($pagination['page'] - 1) . '&sort=' . $sort['field'] . '&direction=' . $sort['direction'] . '&search=' . urlencode($search) . '&pageSize=' . $pagination['pageSize']; ?>">
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
                                    <a class="page-link" href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?page=1&sort=' . $sort['field'] . '&direction=' . $sort['direction'] . '&search=' . urlencode($search) . '&pageSize=' . $pagination['pageSize']; ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?page=' . $i . '&sort=' . $sort['field'] . '&direction=' . $sort['direction'] . '&search=' . urlencode($search) . '&pageSize=' . $pagination['pageSize']; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $pagination['pageCount']): ?>
                                <?php if ($endPage < $pagination['pageCount'] - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?page=' . $pagination['pageCount'] . '&sort=' . $sort['field'] . '&direction=' . $sort['direction'] . '&search=' . urlencode($search) . '&pageSize=' . $pagination['pageSize']; ?>"><?php echo $pagination['pageCount']; ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($pagination['page'] < $pagination['pageCount']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?page=' . ($pagination['page'] + 1) . '&sort=' . $sort['field'] . '&direction=' . $sort['direction'] . '&search=' . urlencode($search) . '&pageSize=' . $pagination['pageSize']; ?>">
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