<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <?php echo $item && isset($item['id']) ? 'Edit ' : 'Create '; ?><?php echo htmlspecialchars($entityName); ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php'; ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php?action=' . ($item && isset($item['id']) ? 'edit&id=' . $item['id'] : 'create'); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?php foreach ($fields as $field): ?>
                    <?php if ($field['form'] ?? true): ?>
                        <div class="mb-3">
                            <label for="<?php echo $field['name']; ?>" class="form-label <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>">
                                <?php echo htmlspecialchars($field['label']); ?>
                            </label>
                            
                            <?php
                                // Get field value
                                $value = '';
                                if ($item && isset($item['id'])) {
                                    $value = $item['attributes'][$field['name']] ?? '';
                                } else {
                                    $value = $field['default'] ?? '';
                                }
                                
                                // Render field based on type
                                switch ($field['type']):
                                    case 'text':
                            ?>
                                <input type="text" class="form-control" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo htmlspecialchars($value); ?>" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                            <?php
                                        break;
                                    case 'textarea':
                            ?>
                                <textarea class="form-control" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" rows="3" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>><?php echo htmlspecialchars($value); ?></textarea>
                            <?php
                                        break;
                                    case 'richtext':
                            ?>
                                <textarea class="form-control rich-text-editor" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" rows="10" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>><?php echo htmlspecialchars($value); ?></textarea>
                            <?php
                                        break;
                                    case 'number':
                            ?>
                                <input type="number" class="form-control" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo htmlspecialchars($value); ?>" step="<?php echo $field['step'] ?? 'any'; ?>" <?php echo isset($field['min']) ? 'min="' . $field['min'] . '"' : ''; ?> <?php echo isset($field['max']) ? 'max="' . $field['max'] . '"' : ''; ?> <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                            <?php
                                        break;
                                    case 'date':
                            ?>
                                <input type="text" class="form-control date-picker" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo htmlspecialchars($value); ?>" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                            <?php
                                        break;
                                    case 'datetime':
                            ?>
                                <input type="text" class="form-control datetime-picker" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo htmlspecialchars($value); ?>" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                            <?php
                                        break;
                                    case 'boolean':
                            ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="1" <?php echo $value ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="<?php echo $field['name']; ?>">
                                        <?php echo htmlspecialchars($field['checkboxLabel'] ?? 'Yes'); ?>
                                    </label>
                                </div>
                            <?php
                                        break;
                                    case 'select':
                            ?>
                                <select class="form-select" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                                    <option value="">-- Select <?php echo htmlspecialchars($field['label']); ?> --</option>
                                    <?php foreach ($field['options'] as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option['value']); ?>" <?php echo $value == $option['value'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php
                                        break;
                                    case 'multiselect':
                            ?>
                                <select class="form-select" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>[]" multiple <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                                    <?php foreach ($field['options'] as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option['value']); ?>" <?php echo is_array($value) && in_array($option['value'], $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php
                                        break;
                                    case 'tags':
                                        $tagsValue = is_array($value) ? implode(',', $value) : $value;
                            ?>
                                <input type="text" class="form-control tags-input" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo htmlspecialchars($tagsValue); ?>" data-role="tagsinput" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                            <?php
                                        break;
                                    case 'image':
                            ?>
                                <div class="mb-2">
                                    <?php if ($value && isset($value['data']['attributes']['url'])): ?>
                                        <img src="<?php echo htmlspecialchars($value['data']['attributes']['url']); ?>" alt="<?php echo htmlspecialchars($value['data']['attributes']['alternativeText'] ?? ''); ?>" class="media-preview mb-2">
                                    <?php endif; ?>
                                </div>
                                <input type="file" class="form-control media-upload" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" accept="image/*" data-preview="#<?php echo $field['name']; ?>_preview" <?php echo in_array($field['name'], $requiredFields) && (!$value || !isset($value['data']['attributes']['url'])) ? 'required' : ''; ?>>
                                <img id="<?php echo $field['name']; ?>_preview" class="media-preview mt-2" style="display: none;">
                            <?php
                                        break;
                                    case 'relation':
                                        $relationValue = '';
                                        if ($value && isset($value['data'])) {
                                            if (is_array($value['data']) && isset($value['data'][0])) {
                                                $relationValue = $value['data'][0]['id'];
                                            } else {
                                                $relationValue = $value['data']['id'];
                                            }
                                        }
                            ?>
                                <select class="form-select" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                                    <option value="">-- Select <?php echo htmlspecialchars($field['label']); ?> --</option>
                                    <?php foreach ($field['options'] as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option['id']); ?>" <?php echo $relationValue == $option['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option['name'] ?? $option['title'] ?? $option['id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php
                                        break;
                                    default:
                            ?>
                                <input type="text" class="form-control" id="<?php echo $field['name']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo htmlspecialchars($value); ?>" <?php echo in_array($field['name'], $requiredFields) ? 'required' : ''; ?>>
                            <?php
                                        break;
                                endswitch;
                            ?>
                            
                            <?php if (isset($field['help'])): ?>
                                <div class="form-text"><?php echo htmlspecialchars($field['help']); ?></div>
                            <?php endif; ?>
                            
                            <div class="invalid-feedback">
                                Please provide a valid <?php echo strtolower($field['label']); ?>.
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo ADMIN_URL . '/' . strtolower($entityName) . '.php'; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $item && isset($item['id']) ? 'Update' : 'Create'; ?> <?php echo htmlspecialchars($entityName); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>