<?php
/**
 * AI Image Generator Component
 *
 * This component provides a "Generate with AI" button that can be included in any admin page
 * with image uploads. It uses the AI prompt templates to generate images based on content.
 *
 * Usage:
 * 1. Include this file in your admin page
 * 2. Call the renderAiImageGenerator() function with the following parameters:
 *    - $contentType: The type of content (story, blog_post, author, game, ai_tool, directory, general)
 *    - $contentData: An array of data about the content (title, description, etc.)
 *    - $targetField: The ID of the input field where the generated image URL should be placed
 *    - $previewElement: The ID of the image element where the preview should be shown
 *
 * Example:
 * <?php
 * require_once '../includes/ai-image-generator-component.php';
 * renderAiImageGenerator('story', [
 *     'title' => $story['title'],
 *     'excerpt' => $story['excerpt'],
 *     'age_group' => $story['age_group']
 * ], 'cover_image', 'cover_image_preview');
 * ?>
 */

/**
 * Render the AI Image Generator component
 *
 * @param string $contentType The type of content (story, blog_post, author, game, ai_tool, directory, general)
 * @param array $contentData An array of data about the content (title, description, etc.)
 * @param string $targetField The ID of the input field where the generated image URL should be placed
 * @param string $previewElement The ID of the image element where the preview should be shown
 * @return void
 */
function renderAiImageGenerator($contentType, $contentData, $targetField, $previewElement) {
    global $db;

    // Get available prompt templates for this content type
    $promptTemplates = [];
    try {
        $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE content_type = ? AND is_active = 1");
        $stmt->execute([$contentType]);
        $promptTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching prompt templates: " . $e->getMessage());
    }

    // If no templates found, include general templates
    if (empty($promptTemplates)) {
        try {
            $stmt = $db->prepare("SELECT * FROM ai_prompt_templates WHERE content_type = 'general' AND is_active = 1");
            $stmt->execute();
            $promptTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching general prompt templates: " . $e->getMessage());
        }
    }

    // Get OpenAI settings
    $openaiConfig = [];
    try {
        $stmt = $db->prepare("SELECT config FROM ai_providers WHERE name = 'openai' AND is_active = 1");
        $stmt->execute();
        $config = $stmt->fetch();
        if ($config) {
            $openaiConfig = json_decode($config['config'], true);
        }
    } catch (Exception $e) {
        error_log("Error fetching OpenAI config: " . $e->getMessage());
    }

    // Check if OpenAI is configured
    $isConfigured = !empty($openaiConfig['api_key']);

    // Encode content data for JavaScript
    $contentDataJson = json_encode($contentData);

    // Render the component
    ?>
    <div class="ai-image-generator-component text-center">
        <button type="button" class="btn btn-primary ai-generate-btn"
                data-toggle="modal"
                data-target="#aiImageGeneratorModal"
                data-content-type="<?php echo htmlspecialchars($contentType); ?>"
                data-content-data='<?php echo htmlspecialchars($contentDataJson); ?>'
                data-target-field="<?php echo htmlspecialchars($targetField); ?>"
                data-preview-element="<?php echo htmlspecialchars($previewElement); ?>"
                <?php echo !$isConfigured ? 'disabled' : ''; ?>>
            <i class="fas fa-magic"></i> Generate with AI
        </button>
        <?php if (!$isConfigured): ?>
            <small class="text-muted d-block mt-1">
                <i class="fas fa-info-circle"></i>
                AI image generation is not configured. Please set up your OpenAI API key in
                <a href="ai-settings.php" target="_blank">AI Settings</a>.
            </small>
        <?php endif; ?>
    </div>

    <?php
    // Only render the modal once per page
    static $modalRendered = false;
    if (!$modalRendered):
        $modalRendered = true;
    ?>
    <!-- AI Image Generator Modal -->
    <div class="modal fade" id="aiImageGeneratorModal" tabindex="-1" role="dialog" aria-labelledby="aiImageGeneratorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aiImageGeneratorModalLabel">Generate Image with AI</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ai-prompt-template">Prompt Template</label>
                        <select id="ai-prompt-template" class="form-control">
                            <option value="">-- Select a template --</option>
                            <?php foreach ($promptTemplates as $template): ?>
                                <option value="<?php echo htmlspecialchars($template['id']); ?>"
                                        data-template="<?php echo htmlspecialchars($template['prompt_template']); ?>">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Select a template to use as a starting point
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="ai-prompt">Prompt</label>
                        <textarea id="ai-prompt" class="form-control" rows="4" placeholder="Enter your prompt here..."></textarea>
                        <small class="form-text text-muted">
                            Be specific and detailed in your prompt for better results
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="ai-size">Image Size</label>
                            <select id="ai-size" class="form-control">
                                <option value="1024x1024">Square (1024x1024)</option>
                                <option value="1024x1792">Portrait (1024x1792)</option>
                                <option value="1792x1024">Landscape (1792x1024)</option>
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="ai-quality">Quality</label>
                            <select id="ai-quality" class="form-control">
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="low">Low</option>
                                <option value="auto">Auto</option>
                            </select>
                            <small class="form-text text-muted">Higher quality = better results but slower</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label for="ai-variations">Variations</label>
                            <select id="ai-variations" class="form-control">
                                <option value="1">1 image</option>
                                <option value="2">2 images</option>
                                <option value="3">3 images</option>
                                <option value="4">4 images</option>
                            </select>
                        </div>
                    </div>

                    <div class="ai-generation-status d-none">
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="text-muted mb-0"><span class="ai-generation-step">Initializing...</span></p>
                            <p class="text-muted mb-0">Time: <span class="ai-generation-time">0s</span></p>
                        </div>
                        <p class="text-center text-muted mt-2">Generating image with AI... This may take up to 30 seconds.</p>
                    </div>

                    <div class="ai-generation-results d-none">
                        <h5 class="mb-3">Generated Images</h5>
                        <div class="row ai-images-container"></div>
                    </div>

                    <div class="ai-generation-error d-none">
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="ai-error-message"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary ai-generate-image-btn">
                        <i class="fas fa-magic"></i> Generate Image
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Store current content data and target fields
        let currentContentType = '';
        let currentContentData = {};
        let currentTargetField = '';
        let currentPreviewElement = '';

        // Handle opening the modal
        $('.ai-generate-btn').click(function() {
            // Get data from button
            currentContentType = $(this).data('content-type');
            currentContentData = $(this).data('content-data');
            currentTargetField = $(this).data('target-field');
            currentPreviewElement = $(this).data('preview-element');

            // Reset the modal
            $('.ai-generation-status').addClass('d-none');
            $('.ai-generation-results').addClass('d-none');
            $('.ai-generation-error').addClass('d-none');
            $('.ai-images-container').empty();

            // Set default prompt for story content type
            if (currentContentType === 'story') {
                const defaultPrompt = "Generate an image for a children's story book in a typical hand-drawn or cartoon illustration form that you would find in traditional story books. Base this on: " +
                    (currentContentData.title || "") +
                    (currentContentData.excerpt ? ". Summary: " + currentContentData.excerpt : "") +
                    (currentContentData.age_group ? ". Target age: " + currentContentData.age_group : "");
                $('#ai-prompt').val(defaultPrompt);
            } else {
                $('#ai-prompt').val('');
            }

            $('#ai-prompt-template').val('');

            // Filter templates based on content type
            $('#ai-prompt-template option').each(function() {
                $(this).show();
            });
        });

        // Handle template selection
        $('#ai-prompt-template').change(function() {
            const templateText = $(this).find('option:selected').data('template');
            if (templateText) {
                // Process template with content data
                let processedTemplate = templateText;

                // Replace simple variables {{variable}}
                processedTemplate = processedTemplate.replace(/\{\{([^}]+)\}\}/g, function(match, key) {
                    key = key.trim();
                    return currentContentData[key] || '';
                });

                // Process conditional blocks {{#if variable}}content{{/if}}
                processedTemplate = processedTemplate.replace(/\{\{#if ([^}]+)\}\}(.*?)\{\{\/if\}\}/g, function(match, key, content) {
                    key = key.trim();
                    if (currentContentData[key]) {
                        return content;
                    }
                    return '';
                });

                $('#ai-prompt').val(processedTemplate);
            }
        });

        // Handle generate button click
        $('.ai-generate-image-btn').click(function() {
            const prompt = $('#ai-prompt').val();
            if (!prompt) {
                alert('Please enter a prompt');
                return;
            }

            // Show loading state
            $('.ai-generation-status').removeClass('d-none');
            $('.ai-generation-results').addClass('d-none');
            $('.ai-generation-error').addClass('d-none');
            $('.ai-generate-image-btn').prop('disabled', true);

            // Reset progress bar
            const progressBar = $('.ai-generation-status .progress-bar');
            progressBar.css('width', '0%').attr('aria-valuenow', 0).text('0%');
            $('.ai-generation-step').text('Initializing...');
            $('.ai-generation-time').text('0s');

            // Start progress simulation
            let startTime = Date.now();
            let progressInterval = setInterval(function() {
                // Calculate elapsed time
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                $('.ai-generation-time').text(elapsedSeconds + 's');

                // Simulate progress - goes up to 95% then waits for actual completion
                let currentProgress = parseInt(progressBar.attr('aria-valuenow'));
                if (currentProgress < 95) {
                    // Different phases of generation
                    if (currentProgress < 20) {
                        $('.ai-generation-step').text('Processing prompt...');
                        currentProgress += 1;
                    } else if (currentProgress < 50) {
                        $('.ai-generation-step').text('Creating image...');
                        currentProgress += 0.7;
                    } else if (currentProgress < 80) {
                        $('.ai-generation-step').text('Refining details...');
                        currentProgress += 0.5;
                    } else {
                        $('.ai-generation-step').text('Finalizing image...');
                        currentProgress += 0.3;
                    }

                    // Update progress bar
                    const newProgress = Math.min(Math.round(currentProgress), 95);
                    progressBar.css('width', newProgress + '%')
                               .attr('aria-valuenow', newProgress)
                               .text(newProgress + '%');
                }

                // Safety timeout after 60 seconds
                if (elapsedSeconds > 60) {
                    clearInterval(progressInterval);
                    $('.ai-generation-step').text('Taking longer than expected...');
                }
            }, 200);

            // Prepare request data
            const data = {
                prompt: prompt,
                size: $('#ai-size').val(),
                quality: $('#ai-quality').val(),
                variations: parseInt($('#ai-variations').val())
                // 'style' parameter removed as it's no longer supported by the OpenAI API
            };

            // Make API request
            $.ajax({
                url: '/api/v1/ai/image.php', // Use relative URL to avoid CORS issues
                type: 'POST',
                contentType: 'application/json',
                crossDomain: true,
                xhrFields: {
                    withCredentials: false
                },
                data: JSON.stringify(data),
                // Add timeout to prevent hanging requests
                timeout: 60000, // 60 seconds
                success: function(response) {
                    // Complete the progress bar
                    clearInterval(progressInterval);
                    const progressBar = $('.ai-generation-status .progress-bar');
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).text('100%');
                    $('.ai-generation-step').text('Complete!');

                    // Hide loading state after a short delay to show completion
                    setTimeout(function() {
                        $('.ai-generation-status').addClass('d-none');
                        $('.ai-generate-image-btn').prop('disabled', false);
                    }, 500);

                    if (response.success) {
                        // Show results
                        $('.ai-generation-results').removeClass('d-none');
                        $('.ai-images-container').empty();

                        // Add main image
                        const filename = response.data.filename || 'ai-generated-image';
                        const altText = response.data.alt || 'AI generated image';
                        const prompt = response.data.prompt || '';

                        if (response.data.type === 'base64') {
                            // Handle base64 image
                            addImageToResults('data:image/png;base64,' + response.data.data, true, response.data.data, filename, altText, prompt);
                        } else if (response.data.type === 'url') {
                            // Handle URL image
                            addImageToResults(response.data.data, true, null, filename, altText, prompt);
                        } else if (response.data.url) {
                            // Legacy format
                            addImageToResults(response.data.url, true, null, filename, altText, prompt);
                        }

                        // Add variations if any
                        if (response.data.variations) {
                            response.data.variations.forEach(function(variation, index) {
                                // Get variation filename and alt text
                                const variationFilename = variation.filename || `${filename}-variation-${index+1}`;
                                const variationAltText = variation.alt || `${altText} (variation ${index+1})`;

                                if (typeof variation === 'string') {
                                    // Legacy format - just a URL
                                    addImageToResults(variation, false, null, variationFilename, variationAltText, prompt);
                                } else if (variation.type === 'base64') {
                                    // Base64 image
                                    addImageToResults('data:image/png;base64,' + variation.data, false, variation.data, variationFilename, variationAltText, prompt);
                                } else if (variation.type === 'url') {
                                    // URL image
                                    addImageToResults(variation.data, false, null, variationFilename, variationAltText, prompt);
                                }
                            });
                        }
                    } else {
                        // Show error
                        $('.ai-generation-error').removeClass('d-none');
                        $('.ai-error-message').text(response.error || 'Unknown error');
                    }
                },
                error: function(xhr, status, error) {
                    // Stop progress simulation
                    clearInterval(progressInterval);

                    // Show error state in progress bar
                    const progressBar = $('.ai-generation-status .progress-bar');
                    progressBar.removeClass('progress-bar-striped progress-bar-animated')
                               .addClass('bg-danger')
                               .css('width', '100%')
                               .attr('aria-valuenow', 100)
                               .text('Error');
                    $('.ai-generation-step').text('Generation failed');

                    // Hide loading state after a short delay
                    setTimeout(function() {
                        $('.ai-generation-status').addClass('d-none');
                        $('.ai-generate-image-btn').prop('disabled', false);

                        // Reset progress bar style for next attempt
                        progressBar.removeClass('bg-danger')
                                   .addClass('progress-bar-striped progress-bar-animated');
                    }, 1000);

                    // Show error with more details
                    $('.ai-generation-error').removeClass('d-none');
                    let errorMsg = 'Request failed: ' + (error || 'Unknown error');

                    // Try to get more detailed error from response
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                errorMsg += ' - ' + response.error;
                            }
                        } catch (e) {
                            // If parsing fails, use the raw response text
                            errorMsg += ' - ' + xhr.responseText;
                        }
                    }

                    // Add status code if available
                    if (xhr.status) {
                        errorMsg += ' (Status: ' + xhr.status + ')';
                    }

                    $('.ai-error-message').text(errorMsg);

                    // Log error to console for debugging
                    console.error('AI Image Generation Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                }
            });
        });

        // Function to add an image to the results
        function addImageToResults(url, isMain, rawBase64, filename, altText, prompt) {
            const col = $('<div class="col-md-6 mb-3"></div>');
            const card = $('<div class="card h-100"></div>');
            const img = $('<img class="card-img-top" src="' + url + '" alt="' + (altText || 'Generated image') + '">');
            const cardBody = $('<div class="card-body"></div>');

            // Add filename and alt text info
            const imageInfo = $('<div class="image-info mb-2"></div>');
            imageInfo.append('<small class="d-block text-muted"><strong>Filename:</strong> ' + (filename || 'ai-generated-image') + '.png</small>');
            imageInfo.append('<small class="d-block text-muted"><strong>Alt Text:</strong> ' + (altText || 'AI generated image') + '</small>');

            // Add prompt if available
            if (prompt) {
                imageInfo.append('<small class="d-block text-muted mt-1"><strong>Prompt:</strong> ' +
                    (prompt.length > 100 ? prompt.substring(0, 100) + '...' : prompt) + '</small>');
            }

            cardBody.append(imageInfo);

            const useBtn = $('<button type="button" class="btn btn-primary btn-sm mr-2 use-image-btn">Use this image</button>');

            // Handle use button click
            useBtn.click(function() {
                // Determine what to store in the target field
                let valueToStore = url;

                // If this is a base64 image and we're in the admin interface,
                // we should convert it to a file and upload it to the server
                if (rawBase64 && url.startsWith('data:image/png;base64,')) {
                    // Show a loading message
                    const originalButtonText = $(this).html();
                    $(this).html('<i class="fas fa-spinner fa-spin"></i> Saving image...');
                    $(this).prop('disabled', true);

                    // Save the button reference for later
                    const button = $(this);

                    // Make an AJAX request to save the base64 image
                    $.ajax({
                        url: '/api/v1/ai/save-base64-image.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            image_data: url,
                            filename: filename || 'ai-generated-image',
                            alt_text: altText || 'AI generated image',
                            prompt: prompt || ''
                        }),
                        success: function(response) {
                            if (response.success) {
                                // Use the returned URL
                                valueToStore = response.data.url;

                                // Update the target field
                                $('#' + currentTargetField).val(valueToStore);

                                // Update the preview if available
                                if (currentPreviewElement) {
                                    $('#' + currentPreviewElement).attr('src', valueToStore);
                                    $('#' + currentPreviewElement).attr('alt', response.data.alt_text);
                                    $('#' + currentPreviewElement).removeClass('d-none');
                                }

                                // Store the filename and alt text in hidden fields if they exist
                                if ($('#' + filenameField).length) {
                                    $('#' + filenameField).val(response.data.filename);
                                }

                                if ($('#' + altTextField).length) {
                                    $('#' + altTextField).val(response.data.alt_text);
                                }

                                // Close the modal
                                $('#aiImageGeneratorModal').modal('hide');
                            } else {
                                alert('Error saving image: ' + (response.error || 'Unknown error'));
                                // Reset button
                                button.html(originalButtonText);
                                button.prop('disabled', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Error saving image: ' + error);
                            // Reset button
                            button.html(originalButtonText);
                            button.prop('disabled', false);
                        }
                    });

                    // Return early to prevent the default behavior
                    return;
                }

                // Set the image URL in the target field
                $('#' + currentTargetField).val(valueToStore);

                // Update the preview if available
                if (currentPreviewElement) {
                    $('#' + currentPreviewElement).attr('src', valueToStore);
                    $('#' + currentPreviewElement).attr('alt', altText || 'AI generated image');
                    $('#' + currentPreviewElement).removeClass('d-none');
                }

                // Store the filename and alt text in hidden fields if they exist
                const filenameField = currentTargetField + '_filename';
                const altTextField = currentTargetField + '_alt';

                if ($('#' + filenameField).length) {
                    $('#' + filenameField).val(filename || 'ai-generated-image');
                }

                if ($('#' + altTextField).length) {
                    $('#' + altTextField).val(altText || 'AI generated image');
                }

                // If there's a title field nearby, suggest using the alt text as the title
                const titleField = currentTargetField.replace('_image', '_title').replace('image', 'title');
                if ($('#' + titleField).length && $('#' + titleField).val() === '') {
                    // Only suggest if the title field is empty
                    if (confirm('Would you like to use a shortened version of the image description as the title?')) {
                        // Create a shorter version of the alt text for the title
                        let shortTitle = altText;
                        if (shortTitle.length > 60) {
                            shortTitle = shortTitle.substring(0, 57) + '...';
                        }
                        $('#' + titleField).val(shortTitle);
                    }
                }

                // Close the modal
                $('#aiImageGeneratorModal').modal('hide');
            });

            cardBody.append(useBtn);

            // Add download button
            const downloadBtn = $('<button type="button" class="btn btn-secondary btn-sm download-image-btn">Download</button>');
            downloadBtn.click(function() {
                // Create a temporary link to download the image
                const a = document.createElement('a');
                a.href = url;
                a.download = (filename || 'ai-generated-image') + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });

            cardBody.append(downloadBtn);

            // If this is the main image, add a badge
            if (isMain) {
                const badge = $('<span class="badge badge-success position-absolute" style="top: 10px; right: 10px;">Primary</span>');
                card.append(badge);
            }

            card.append(img);
            card.append(cardBody);
            col.append(card);
            $('.ai-images-container').append(col);
        }
    });
    </script>
    <style>
        /* Enhanced progress bar styles */
        .ai-generation-status .progress {
            height: 20px;
            border-radius: 10px;
            background-color: #f0f0f0;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .ai-generation-status .progress-bar {
            border-radius: 10px;
            font-weight: bold;
            font-size: 12px;
            line-height: 20px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.3);
            transition: width 0.3s ease;
        }

        .ai-generation-step {
            font-weight: 500;
        }

        .ai-generation-time {
            font-family: monospace;
            font-weight: 500;
        }
    </style>
    <?php endif; // End of modal rendering
}
?>
