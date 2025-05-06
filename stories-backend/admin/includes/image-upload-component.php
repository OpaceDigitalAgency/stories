<?php
/**
 * Image Upload Component
 *
 * A reusable component for image uploads that can be used across admin pages.
 * Features:
 * - Drag and drop functionality
 * - Image preview
 * - Integration with image optimization
 * - Multiple size generation
 * - AI image generation placeholder
 *
 * @param string $fieldName The name of the field to store the image URL
 * @param string $currentValue The current value of the image URL field
 * @param string $label The label for the image upload field
 * @param string $entityType The type of entity (author, story, etc.)
 * @param int|null $entityId The ID of the entity (null for new entities)
 */

function renderImageUploadComponent($fieldName, $currentValue = '', $label = 'Image', $entityType = 'general', $entityId = null) {
    // Generate a unique ID for this instance
    $componentId = 'image-upload-' . $fieldName . '-' . uniqid();

    // Check if the current value is a valid image URL
    $hasImage = !empty($currentValue);

    // Get the image dimensions if available
    $imageDimensions = '';
    if ($hasImage && function_exists('getimagesize')) {
        // Try to get image size from URL or local path
        $size = @getimagesize($currentValue);
        if ($size) {
            $imageDimensions = $size[0] . 'x' . $size[1];
        }
    }
?>
    <div class="form-group image-upload-component" id="<?php echo $componentId; ?>">
        <label class="form-label" for="<?php echo $fieldName; ?>"><?php echo $label; ?></label>

        <!-- Hidden input to store the image URL -->
        <input type="text" id="<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>"
               value="<?php echo htmlspecialchars($currentValue); ?>"
               class="form-control image-url-input"
               <?php echo $hasImage ? 'readonly' : ''; ?>>

        <!-- Image preview area -->
        <div class="image-preview-container <?php echo $hasImage ? 'has-image' : ''; ?>">
            <?php if ($hasImage): ?>
                <div class="image-preview">
                    <img src="<?php echo htmlspecialchars($currentValue); ?>"
                         alt="Preview"
                         id="<?php echo $fieldName; ?>-preview">
                    <div class="image-info">
                        <?php if (!empty($imageDimensions)): ?>
                            <span class="dimensions"><?php echo $imageDimensions; ?></span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-danger remove-image">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="image-preview empty">
                    <div class="placeholder">
                        <i class="fas fa-image"></i>
                        <span>No image selected</span>
                    </div>
                    <img src=""
                         alt="Preview"
                         id="<?php echo $fieldName; ?>-preview"
                         style="display: none;">
                </div>
            <?php endif; ?>
        </div>

        <!-- Upload controls -->
        <div class="upload-controls">
            <div class="dropzone" id="<?php echo $componentId; ?>-dropzone">
                <div class="dropzone-message">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Drag & drop an image here or</span>
                    <label for="<?php echo $componentId; ?>-file" class="btn btn-sm btn-primary">
                        Browse Files
                    </label>
                    <input type="file" id="<?php echo $componentId; ?>-file" name="<?php echo $fieldName; ?>_file" class="file-input" accept="image/*" style="display: none;">
                </div>
            </div>

            <div class="upload-actions">
                <button type="button" class="btn btn-sm btn-secondary select-from-media">
                    <i class="fas fa-photo-video"></i> Select from Media Library
                </button>
                <!-- AI generation button is now added separately via renderAiImageGenerator -->
            </div>
        </div>

        <!-- Progress bar for upload -->
        <div class="upload-progress" style="display: none;">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
        </div>

        <!-- Hidden fields for upload processing -->
        <input type="hidden" class="entity-type" value="<?php echo htmlspecialchars($entityType); ?>">
        <input type="hidden" class="entity-id" value="<?php echo $entityId ? htmlspecialchars($entityId) : '0'; ?>">
    </div>

    <!-- Add component-specific styles -->
    <style>
        .image-upload-component {
            margin-bottom: 20px;
        }

        .image-preview-container {
            margin: 10px 0;
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            background-color: var(--gray-50);
            transition: all 0.3s ease;
        }

        .image-preview-container.has-image {
            border-style: solid;
            border-color: var(--primary);
        }

        .image-preview {
            position: relative;
            padding: 10px;
            text-align: center;
        }

        .image-preview.empty {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: var(--radius-sm);
        }

        .placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--gray-500);
        }

        .placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .image-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding: 5px 10px;
            background-color: var(--gray-100);
            border-radius: var(--radius-sm);
        }

        .dropzone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            margin: 10px 0;
            background-color: var(--gray-50);
            transition: all 0.3s ease;
        }

        .dropzone.dragover {
            background-color: var(--primary-light);
            border-color: var(--primary);
        }

        .dropzone-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: var(--gray-600);
        }

        .dropzone-message i {
            font-size: 2rem;
        }

        .upload-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            justify-content: center;
        }

        .upload-progress {
            margin-top: 10px;
        }

        .progress {
            height: 10px;
            border-radius: var(--radius-sm);
            background-color: var(--gray-200);
            overflow: hidden;
        }

        .progress-bar {
            background-color: var(--primary);
            height: 100%;
            transition: width 0.3s ease;
        }
    </style>
<?php
}
?>
