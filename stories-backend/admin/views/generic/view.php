<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <?php echo htmlspecialchars($entityName); ?> Details
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php'; ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=edit&id=' . $item['id']; ?>" class="btn btn-sm btn-primary me-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=delete&id=' . $item['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <?php foreach ($fields as $field): ?>
                    <?php if ($field['view'] ?? true): ?>
                        <div class="col-md-6 mb-3">
                            <h5><?php echo htmlspecialchars($field['label']); ?></h5>
                            <div class="p-2 bg-light rounded">
                                <?php
                                    // Get field value
                                    $value = $item['attributes'][$field['name']] ?? '';
                                    
                                    // Format value based on field type
                                    switch ($field['type']):
                                        case 'boolean':
                                            echo $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
                                            break;
                                        case 'date':
                                            echo $value ? date('F d, Y', strtotime($value)) : '<em>Not set</em>';
                                            break;
                                        case 'datetime':
                                            echo $value ? date('F d, Y H:i', strtotime($value)) : '<em>Not set</em>';
                                            break;
                                        case 'richtext':
                                        case 'textarea':
                                            echo $value ? nl2br(htmlspecialchars($value)) : '<em>Not set</em>';
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
                                        case 'array':
                                            if (is_array($value) && !empty($value)) {
                                                echo '<ul class="mb-0">';
                                                foreach ($value as $item) {
                                                    echo '<li>' . htmlspecialchars($item) . '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<em>None</em>';
                                            }
                                            break;
                                        case 'tags':
                                            if (is_array($value) && !empty($value)) {
                                                foreach ($value as $tag) {
                                                    echo '<span class="badge bg-primary me-1">' . htmlspecialchars($tag) . '</span>';
                                                }
                                            } else {
                                                echo '<em>No tags</em>';
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
                <?php endforeach; ?>
            </div>

            <!-- Metadata -->
            <div class="row mt-4">
                <div class="col-12">
                    <h5>Metadata</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;">ID</th>
                                    <td><?php echo htmlspecialchars($item['id']); ?></td>
                                </tr>
                                <?php if (isset($item['attributes']['createdAt'])): ?>
                                <tr>
                                    <th>Created At</th>
                                    <td><?php echo date('F d, Y H:i:s', strtotime($item['attributes']['createdAt'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($item['attributes']['updatedAt'])): ?>
                                <tr>
                                    <th>Updated At</th>
                                    <td><?php echo date('F d, Y H:i:s', strtotime($item['attributes']['updatedAt'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($item['attributes']['publishedAt'])): ?>
                                <tr>
                                    <th>Published At</th>
                                    <td><?php echo date('F d, Y H:i:s', strtotime($item['attributes']['publishedAt'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>