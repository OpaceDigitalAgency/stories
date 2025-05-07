/**
 * CKEditor 5 custom upload adapter for integrating with the Stories media library
 *
 * This adapter allows CKEditor to use the existing media library for image uploads
 * and insertions.
 */

class MediaLibraryUploadAdapter {
    constructor(loader) {
        // CKEditor file loader instance
        this.loader = loader;
    }

    // Starts the upload process
    upload() {
        return this.loader.file
            .then(file => new Promise((resolve, reject) => {
                this._initRequest();
                this._initListeners(resolve, reject, file);
                this._sendRequest(file);
            }));
    }

    // Aborts the upload process
    abort() {
        if (this.xhr) {
            this.xhr.abort();
        }
    }

    // Initialize the XMLHttpRequest object
    _initRequest() {
        const xhr = this.xhr = new XMLHttpRequest();
        xhr.open('POST', '../handlers/upload-image.php', true);
        xhr.responseType = 'json';
    }

    // Initialize XMLHttpRequest listeners
    _initListeners(resolve, reject, file) {
        const xhr = this.xhr;
        const loader = this.loader;
        const genericErrorText = `Couldn't upload file: ${file.name}.`;

        xhr.addEventListener('error', () => reject(genericErrorText));
        xhr.addEventListener('abort', () => reject());
        xhr.addEventListener('load', () => {
            const response = xhr.response;

            if (!response || response.error) {
                return reject(response && response.error ? response.error.message : genericErrorText);
            }

            // If the upload is successful, resolve the upload promise with an object containing
            // at least the "default" URL, pointing to the image on the server.

            // Make sure we have an absolute URL
            let imageUrl = response.url;
            if (imageUrl && imageUrl.indexOf('http') !== 0) {
                // Convert to absolute URL if it's not already
                const host = window.location.host || 'api.storiesfromtheweb.org';
                const protocol = window.location.protocol || 'https:';
                imageUrl = `${protocol}//${host}${imageUrl.startsWith('/') ? '' : '/'}${imageUrl}`;
            }

            console.log('CKEditor image upload successful, URL:', imageUrl);

            // Get alt text from response or use filename as fallback
            const altText = response.alt || file.name.replace(/\.[^/.]+$/, "");

            resolve({
                default: imageUrl,
                // Include alt text for the image
                alt: altText,
                // Include width and height if available
                width: response.width || null,
                height: response.height || null
                // You can include additional URLs if your server provides different image sizes
                // For example:
                // 500: response.urls.medium,
                // 1000: response.urls.large
            });
        });

        // Upload progress when it's supported
        if (xhr.upload) {
            xhr.upload.addEventListener('progress', evt => {
                if (evt.lengthComputable) {
                    loader.uploadTotal = evt.total;
                    loader.uploaded = evt.loaded;
                }
            });
        }
    }

    // Prepare and send the request with the file
    _sendRequest(file) {
        // Create FormData
        const data = new FormData();
        data.append('upload', file);
        data.append('entity_type', 'story');
        data.append('for_editor', 'true');

        // Try to get the story ID from the form if available
        const storyIdInput = document.querySelector('input[name="id"]');
        if (storyIdInput && storyIdInput.value) {
            data.append('entity_id', storyIdInput.value);
        } else {
            // Use a temporary ID if we don't have a story ID yet
            data.append('entity_id', 'temp-' + Date.now());
        }

        // Add alt text (can be updated later)
        data.append('alt_text', file.name.replace(/\.[^/.]+$/, "")); // Use filename without extension as alt text

        // Send the request
        this.xhr.send(data);
    }
}

// Plugin that registers the upload adapter
function MediaLibraryUploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        return new MediaLibraryUploadAdapter(loader);
    };
}
