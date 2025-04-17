<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <?php echo htmlspecialchars($entityName); ?> Details
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL . '/' . $activeMenu . '.php'; ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="<?php echo ADMIN_URL . '/' . $activeMenu . '.php?action=edit&id=' . $item['id']; ?>" class="btn btn-sm btn-primary me-2">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="<?php echo ADMIN_URL . '/' . $activeMenu . '.php?action=delete&id=' . $item['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
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
                                    // Get field value with proper fallback
                                    $value = isset($item['attributes']) && isset($item['attributes'][$field['name']])
                                        ? $item['attributes'][$field['name']]
                                        : ($item[$field['name']] ?? '');
                                    
                                    // Format value based on field type
                                    switch ($field['type']):
                                        case 'boolean':
                                            echo $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
                                            break;
                                        case 'date':
                                            echo $value ? date('F d, Y', strtotime($value)) : '<em class="text-muted">Not set</em>';
                                            break;
                                        case 'datetime':
                                            echo $value ? date('F d, Y H:i', strtotime($value)) : '<em class="text-muted">Not set</em>';
                                            break;
                                        case 'richtext':
                                        case 'textarea':
                                            echo $value ? nl2br(htmlspecialchars($value)) : '<em class="text-muted">Not set</em>';
                                            break;
                                        case 'image':
                                            if ($value && isset($value['data']['attributes']['url'])) {
                                                echo '<img src="' . htmlspecialchars($value['data']['attributes']['url']) . '" alt="' . htmlspecialchars($value['data']['attributes']['alternativeText'] ?? '') . '" class="img-fluid rounded" style="max-height: 200px;">';
                                            } else if ($value && isset($value['url'])) {
                                                echo '<img src="' . htmlspecialchars($value['url']) . '" alt="' . htmlspecialchars($value['alternativeText'] ?? '') . '" class="img-fluid rounded" style="max-height: 200px;">';
                                            } else {
                                                echo '<em class="text-muted">No image</em>';
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
                                                echo '<em class="text-muted">Not set</em>';
                                            }
                                            break;
                                        case 'array':
                                            if (is_array($value) && !empty($value)) {
                                                echo '<ul class="mb-0">';
                                                foreach ($value as $arrayItem) {
                                                    if (is_array($arrayItem)) {
                                                        $displayValue = $arrayItem['name'] ?? $arrayItem['title'] ??
                                                                       ($arrayItem['attributes']['name'] ?? $arrayItem['attributes']['title'] ??
                                                                       json_encode($arrayItem));
                                                        echo '<li>' . htmlspecialchars($displayValue) . '</li>';
                                                    } else {
                                                        echo '<li>' . htmlspecialchars($arrayItem) . '</li>';
                                                    }
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<em class="text-muted">None</em>';
                                            }
                                            break;
                                        case 'tags':
                                            if (is_array($value) && !empty($value)) {
                                                foreach ($value as $tag) {
                                                    if (is_array($tag)) {
                                                        $tagName = $tag['name'] ?? $tag['title'] ??
                                                                 ($tag['attributes']['name'] ?? $tag['attributes']['title'] ?? 'Unnamed');
                                                        echo '<span class="badge bg-primary me-1">' . htmlspecialchars($tagName) . '</span>';
                                                    } else {
                                                        echo '<span class="badge bg-primary me-1">' . htmlspecialchars($tag) . '</span>';
                                                    }
                                                }
                                            } else {
                                                echo '<em class="text-muted">No tags</em>';
                                            }
                                            break;
                                        default:
                                            if (is_string($value) && !empty($value)) {
                                                echo htmlspecialchars($value);
                                            } else if (is_numeric($value)) {
                                                echo htmlspecialchars((string)$value);
                                            } else {
                                                echo '<em class="text-muted">Not set</em>';
                                            }
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
                                    <td><?php echo htmlspecialchars($item['id'] ?? 'Unknown'); ?></td>
                                </tr>
                                
                                <?php
                                // Get created date from attributes or direct property
                                $createdAt = isset($item['attributes']) && isset($item['attributes']['createdAt'])
                                    ? $item['attributes']['createdAt']
                                    : ($item['createdAt'] ?? null);
                                
                                if ($createdAt):
                                ?>
                                <tr>
                                    <th>Created At</th>
                                    <td><?php echo date('F d, Y H:i:s', strtotime($createdAt)); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php
                                // Get updated date from attributes or direct property
                                $updatedAt = isset($item['attributes']) && isset($item['attributes']['updatedAt'])
                                    ? $item['attributes']['updatedAt']
                                    : ($item['updatedAt'] ?? null);
                                
                                if ($updatedAt):
                                ?>
                                <tr>
                                    <th>Updated At</th>
                                    <td><?php echo date('F d, Y H:i:s', strtotime($updatedAt)); ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php
                                // Get published date from attributes or direct property
                                $publishedAt = isset($item['attributes']) && isset($item['attributes']['publishedAt'])
                                    ? $item['attributes']['publishedAt']
                                    : ($item['publishedAt'] ?? null);
                                
                                if ($publishedAt):
                                ?>
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