<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            Delete <?php echo htmlspecialchars($entityName); ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL . '/' . $activeMenu . '.php'; ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-danger">
                <h4 class="alert-heading">Warning!</h4>
                <p>Are you sure you want to delete this <?php echo htmlspecialchars(strtolower($activeMenu)); ?>? This action cannot be undone.</p>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo htmlspecialchars($entityName); ?> Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                            // Get the main field to display
                            $mainField = null;
                            foreach ($fields as $field) {
                                if ($field['main'] ?? false) {
                                    $mainField = $field;
                                    break;
                                }
                            }
                            
                            // If no main field is defined, use the first field
                            if (!$mainField && !empty($fields)) {
                                $mainField = $fields[0];
                            }
                            
                            // Display the main field
                            if ($mainField):
                                $value = isset($item['attributes']) && isset($item['attributes'][$mainField['name']])
                                    ? $item['attributes'][$mainField['name']]
                                    : ($item[$mainField['name']] ?? '');
                        ?>
                            <div class="col-md-12 mb-3">
                                <h5><?php echo htmlspecialchars($mainField['label']); ?></h5>
                                <div class="p-2 bg-light rounded">
                                    <?php
                                        // Format value based on field type
                                        switch ($mainField['type']):
                                            case 'boolean':
                                                echo $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
                                                break;
                                            case 'date':
                                                echo $value ? date('F d, Y', strtotime($value)) : '<em>Not set</em>';
                                                break;
                                            case 'datetime':
                                                echo $value ? date('F d, Y H:i', strtotime($value)) : '<em>Not set</em>';
                                                break;
                                            case 'image':
                                                if ($value && isset($value['data']['attributes']['url'])) {
                                                    echo '<img src="' . htmlspecialchars($value['data']['attributes']['url']) . '" alt="' . htmlspecialchars($value['data']['attributes']['alternativeText'] ?? '') . '" class="img-fluid rounded" style="max-height: 200px;">';
                                                } else {
                                                    echo '<em>No image</em>';
                                                }
                                                break;
                                            case 'relation':
                                                if (isset($value['data']) && !empty($value['data'])) {
                                                    if (is_array($value['data']) && isset($value['data'][0])) {
                                                        echo htmlspecialchars($value['data'][0]['attributes']['name'] ?? $value['data'][0]['attributes']['title'] ?? '');
                                                    } else {
                                                        echo htmlspecialchars($value['data']['attributes']['name'] ?? $value['data']['attributes']['title'] ?? '');
                                                    }
                                                } else {
                                                    echo '<em>Not set</em>';
                                                }
                                                break;
                                            default:
                                                echo $value ? htmlspecialchars($value) : '<em>Not set</em>';
                                                break;
                                        endswitch;
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Display ID and timestamps -->
                        <div class="col-md-4 mb-3">
                            <h5>ID</h5>
                            <div class="p-2 bg-light rounded">
                                <?php echo htmlspecialchars($item['id']); ?>
                            </div>
                        </div>
                        
                        <?php if (isset($item['attributes']['createdAt'])): ?>
                        <div class="col-md-4 mb-3">
                            <h5>Created At</h5>
                            <div class="p-2 bg-light rounded">
                                <?php echo date('F d, Y H:i:s', strtotime($item['attributes']['createdAt'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($item['attributes']['updatedAt'])): ?>
                        <div class="col-md-4 mb-3">
                            <h5>Updated At</h5>
                            <div class="p-2 bg-light rounded">
                                <?php echo date('F d, Y H:i:s', strtotime($item['attributes']['updatedAt'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form action="<?php echo ADMIN_URL . '/' . $activeMenu . '.php?action=delete&id=' . $item['id']; ?>" method="post">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo ADMIN_URL . '/' . $activeMenu . '.php'; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete <?php echo htmlspecialchars($entityName); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>