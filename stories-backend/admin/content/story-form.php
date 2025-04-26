tories-backend/admin/content/story-form.php</path>
<content lines="324-378">
            <?php 
            // Display remaining additional fields
            foreach ($additionalFields as $field): 
                if (in_array($field, ['slug', 'featured', 'is_sponsored', 'published_at', 'review_count', 'average_rating'])) continue;
                
                $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                $isIntField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'int') !== false || strpos($columnInfo[$field]['Type'], 'tinyint') !== false);
                $isDecimalField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'decimal') !== false || strpos($columnInfo[$field]['Type'], 'float') !== false || strpos($columnInfo[$field]['Type'], 'double') !== false);
                $isEnumField = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'enum') !== false;
                $isBooleanField = isset($columnInfo[$field]) && (
                    (strpos($columnInfo[$field]['Type'], 'tinyint(1)') !== false) || 
                    (strpos($field, 'is_') === 0) || 
                    (strpos($field, 'has_') === 0) || 
                    (strpos($field, 'needs_') === 0)
                );
                
                // Extract enum values if it's an enum field
                $enumValues = [];
                if ($isEnumField && preg_match('/enum\((.*)\)/', $columnInfo[$field]['Type'], $matches)) {
                    $enumString = $matches[1];
                    preg_match_all("/'([^']*)'/", $enumString, $enumMatches);
                    $enumValues = $enumMatches[1];
                }
            ?>
                <?php if ($isBooleanField): ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="1"
                               <?php echo (isset($story[$field]) && $story[$field] == 1) ? 'checked' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                    
                    <?php if ($isDateTime): ?>
                        <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo isset($story[$field]) ? date('Y-m-d\TH:i', strtotime($story[$field])) : date('Y-m-d\TH:i'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                        <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                    <?php elseif ($isEnumField && !empty($enumValues)): ?>
                        <select id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" <?php echo $isRequired ? 'required' : ''; ?>>
                            <option value="">Select <?php echo ucfirst(str_replace('_', ' ', $field)); ?></option>
                            <?php foreach ($enumValues as $value): ?>
                                <option value="<?php echo $value; ?>"
                                        <?php echo (isset($story[$field]) && $story[$field] == $value) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($isIntField): ?>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" min="0" step="1"
                               value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php elseif ($isDecimalField): ?>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" min="0" step="0.1"
                               value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php else: ?>
                        <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>