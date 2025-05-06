// Show progress indicator when optimizing images
document.addEventListener("DOMContentLoaded", function() {
    // Get all buttons that trigger optimization
    const optimizeButtons = document.querySelectorAll("a[href*=\"optimize_image.php\"]");

    optimizeButtons.forEach(button => {
        button.addEventListener("click", function(e) {
            // Show the progress overlay
            const overlay = document.getElementById("progressOverlay");
            overlay.style.visibility = "visible";
            overlay.style.opacity = "1";

            // Set appropriate message based on button text
            const title = document.getElementById("progressTitle");
            const message = document.getElementById("progressMessage");

            if (this.textContent.includes("All Media")) {
                title.textContent = "Optimizing All Media";
                message.textContent = "This may take several minutes. Please do not close this page.";
            } else {
                title.textContent = "Optimizing Image";
                message.textContent = "Please wait while we optimize your image.";
            }
        });
    });

    // Handle upload tabs
    const tabs = document.querySelectorAll(".upload-tab");
    const tabContents = document.querySelectorAll(".upload-tab-content");

    tabs.forEach(tab => {
        tab.addEventListener("click", function() {
            const tabId = this.getAttribute("data-tab");

            // Update active tab
            tabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");

            // Show corresponding content
            tabContents.forEach(content => {
                if (content.id === tabId + "-upload") {
                    content.style.display = "block";
                } else {
                    content.style.display = "none";
                }
            });
        });
    });

    // Handle bulk upload
    const dropzone = document.getElementById("bulk-dropzone");
    const fileInput = document.getElementById("bulk-file-input");

    if (dropzone && fileInput) {
        dropzone.addEventListener("dragover", function(e) {
            e.preventDefault();
            this.style.borderColor = "var(--primary)";
            this.style.background = "var(--gray-100)";
        });

        dropzone.addEventListener("dragleave", function(e) {
            e.preventDefault();
            this.style.borderColor = "var(--border-color)";
            this.style.background = "var(--gray-50)";
        });

        dropzone.addEventListener("drop", function(e) {
            e.preventDefault();
            this.style.borderColor = "var(--border-color)";
            this.style.background = "var(--gray-50)";

            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        fileInput.addEventListener("change", function() {
            handleFiles(this.files);
        });
    }

    function handleFiles(files) {
        const overlay = document.getElementById("progressOverlay");
        const title = document.getElementById("progressTitle");
        const message = document.getElementById("progressMessage");

        overlay.style.visibility = "visible";
        overlay.style.opacity = "1";
        title.textContent = "Uploading Files";
        message.textContent = `Uploading ${files.length} file${files.length !== 1 ? 's' : ''}...`;

        // Create FormData and append files
        const formData = new FormData();
        Array.from(files).forEach(file => {
            formData.append("files[]", file);
        });

        // Upload files
        fetch("upload-bulk.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Error uploading files: " + data.error);
                overlay.style.visibility = "hidden";
                overlay.style.opacity = "0";
            }
        })
        .catch(error => {
            alert("Error uploading files: " + error);
            overlay.style.visibility = "hidden";
            overlay.style.opacity = "0";
        });
    }
});