<!-- Help Modal -->
                <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="helpModalLabel">Help & Documentation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="accordion" id="helpAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingGeneral">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral" aria-expanded="true" aria-controls="collapseGeneral">
                                                General Information
                                            </button>
                                        </h2>
                                        <div id="collapseGeneral" class="accordion-collapse collapse show" aria-labelledby="headingGeneral" data-bs-parent="#helpAccordion">
                                            <div class="accordion-body">
                                                <p>Welcome to the Stories from the Web Admin Panel. This interface allows you to manage all content and features of your website.</p>
                                                <p>Use the navigation menu at the top to access different sections of the admin panel.</p>
                                                <p>For more detailed help, please refer to the specific section below or contact the system administrator.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingContent">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">
                                                Content Management
                                            </button>
                                        </h2>
                                        <div id="collapseContent" class="accordion-collapse collapse" aria-labelledby="headingContent" data-bs-parent="#helpAccordion">
                                            <div class="accordion-body">
                                                <h5>Stories</h5>
                                                <p>Manage all stories published on the website. You can add, edit, and delete stories, as well as manage their metadata, tags, and associated media.</p>
                                                
                                                <h5>Authors</h5>
                                                <p>Manage author profiles including their bio, contact information, and associated stories.</p>
                                                
                                                <h5>Blog Posts</h5>
                                                <p>Manage blog content including articles, news, and updates.</p>
                                                
                                                <h5>Tags</h5>
                                                <p>Manage the taxonomy system used to categorize and organize content across the website.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingFeatures">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFeatures" aria-expanded="false" aria-controls="collapseFeatures">
                                                Features Management
                                            </button>
                                        </h2>
                                        <div id="collapseFeatures" class="accordion-collapse collapse" aria-labelledby="headingFeatures" data-bs-parent="#helpAccordion">
                                            <div class="accordion-body">
                                                <h5>Directory Items</h5>
                                                <p>Manage listings in the website directory, including resources, services, and other relevant information.</p>
                                                
                                                <h5>Games</h5>
                                                <p>Manage interactive games and activities available on the website.</p>
                                                
                                                <h5>AI Tools</h5>
                                                <p>Manage AI-powered tools and features that enhance the user experience.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingMedia">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMedia" aria-expanded="false" aria-controls="collapseMedia">
                                                Media Management
                                            </button>
                                        </h2>
                                        <div id="collapseMedia" class="accordion-collapse collapse" aria-labelledby="headingMedia" data-bs-parent="#helpAccordion">
                                            <div class="accordion-body">
                                                <p>The Media section allows you to upload, organize, and manage all media files used across the website, including images, documents, and other files.</p>
                                                <p>Key features include:</p>
                                                <ul>
                                                    <li>Upload new media files</li>
                                                    <li>Organize media into folders</li>
                                                    <li>Edit metadata such as alt text and descriptions</li>
                                                    <li>Delete unused media</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <a href="#" class="btn btn-primary">View Full Documentation</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modal -->
                <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p id="confirmationMessage">Are you sure you want to proceed with this action?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmAction">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div class="loading-overlay d-none">
                    <div class="spinner-container">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 loading-message">Processing your request...</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Notification Container -->
    <div class="notification-container position-fixed top-0 end-0 p-3"></div>

    <!-- Core JS Libraries (loaded in the correct order) -->
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Bootstrap Tags Input -->
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/bootstrap-tagsinput.min.js"></script>
    
    <!-- CKEditor -->
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/ckeditor.js"></script>
    
    <!-- Flatpickr -->
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/flatpickr.min.js"></script>
    
    <!-- Chart.js -->
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/chart.min.js"></script>
    
    <!-- Custom JS (loaded last to ensure all dependencies are available) -->
    <script src="<?php echo ADMIN_ASSETS_URL; ?>/assets/js/admin.js"></script>
</body>
</html>