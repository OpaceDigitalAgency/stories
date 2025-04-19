</main>
        </div>
    </div>

    <!-- Notification Container -->
    <div class="notification-container position-fixed top-0 end-0 p-3"></div>

    <!-- Temporarily remove all JS includes except a minimal test script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM loaded, attaching minimal submit listener");
            const form = document.querySelector('form.needs-validation');
            if (form) {
                console.log("Found form:", form.id);
                form.addEventListener('submit', function(event) {
                    console.log("[MINIMAL HANDLER] Submit event triggered!");
                    event.preventDefault(); // Prevent actual submission
                });
            } else {
                console.warn("Form with class 'needs-validation' not found.");
            }
        });
    </script>
</body>
</html>