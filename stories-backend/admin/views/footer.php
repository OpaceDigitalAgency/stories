</main>
        </div>
    </div>

    <!-- Notification Container -->
    <div class="notification-container position-fixed top-0 end-0 p-3"></div>

    <!-- jQuery (loaded first with timeout and error handling) -->
    <script>
        // Function to load jQuery with timeout
        function loadJQuery(url, timeout, callback) {
            var script = document.createElement('script');
            var timer = setTimeout(function() {
                // If the script takes too long to load, trigger the error handler
                script.onerror();
            }, timeout);
            
            script.onload = function() {
                clearTimeout(timer);
                console.log('jQuery loaded successfully from: ' + url);
                if (typeof callback === 'function') callback(true);
            };
            
            script.onerror = function() {
                clearTimeout(timer);
                console.error('jQuery failed to load from: ' + url);
                if (typeof callback === 'function') callback(false);
            };
            
            script.src = url;
            document.head.appendChild(script);
        }
        
        // Try to load jQuery from primary source with a 5-second timeout
        loadJQuery('https://code.jquery.com/jquery-3.6.0.min.js', 5000, function(success) {
            if (!success) {
                console.log('Trying fallback jQuery source...');
                // Try fallback source with a 5-second timeout
                loadJQuery('https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', 5000, function(success) {
                    if (!success) {
                        console.log('Trying second fallback jQuery source...');
                        // Try second fallback source with a 5-second timeout
                        loadJQuery('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js', 5000, function(success) {
                            if (success) {
                                document.dispatchEvent(new Event('jqueryLoaded'));
                            } else {
                                console.error('All jQuery sources failed to load');
                            }
                        });
                    } else {
                        document.dispatchEvent(new Event('jqueryLoaded'));
                    }
                });
            } else {
                document.dispatchEvent(new Event('jqueryLoaded'));
            }
        });
    </script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CKEditor -->
    <!-- <script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script> -->
    
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Bootstrap Tags Input -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS (loaded last to ensure all dependencies are available) -->
    <script src="<?php echo ADMIN_URL; ?>/assets/js/admin.js"></script>
</body>
</html>