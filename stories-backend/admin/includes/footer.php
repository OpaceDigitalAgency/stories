<?php
/**
 * Common Footer Include
 *
 * This file contains the common footer elements for all admin pages.
 * It should be included at the bottom of each admin page.
 *
 * Usage:
 * include '../includes/footer.php';
 */

// Include the configuration if not already included
if (!function_exists('get_config')) {
    $configPath = dirname(dirname(dirname(__FILE__))) . '/includes/config.php';
    if (file_exists($configPath)) {
        include_once $configPath;
    }
}

// Get site information from config
$siteName = get_config('site.name', 'Stories from the Web');
$siteVersion = get_config('site.version', '2.1');
$siteCopyright = get_config('site.footer.copyright', 'Stories from the Web. All rights reserved.');
$contactEmail = get_config('site.contact.email', 'support@storiesfromtheweb.org');
?>
    <footer class="admin-footer" role="contentinfo">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-heading"><?php echo htmlspecialchars($siteName); ?></h3>
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteCopyright); ?></p>
                    <p class="text-muted">Version <?php echo htmlspecialchars($siteVersion); ?> - Enhanced Admin Dashboard</p>
                </div>

                <div class="footer-section">
                    <h3 class="footer-heading">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="/docs/comprehensive-system-architecture-new.php" target="_blank">System Documentation</a></li>
                        <li><a href="/docs/KNOWN_ISSUES_AND_FIXES.md" target="_blank">Known Issues & Fixes</a></li>
                        <li><a href="/public/optimize_image.php" target="_blank">Image Optimization Tool</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 class="footer-heading">Support</h3>
                    <ul class="footer-links">
                        <li><a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>">Email Support</a></li>
                        <li><a href="https://github.com/OpaceDigitalAgency/stories/issues" target="_blank">Report an Issue</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>Made with <span aria-hidden="true">❤️</span><span class="visually-hidden">love</span> by the <?php echo htmlspecialchars($siteName); ?> team</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap initialization script -->
    <script>
        $(document).ready(function() {
            // Initialize all dropdowns
            $('.dropdown-toggle').dropdown();

            // Initialize all tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Initialize all popovers
            $('[data-toggle="popover"]').popover();

            // Initialize all modals
            $('.modal').modal({
                show: false
            });

            // Fix for modals not showing
            $(document).on('click', '[data-toggle="modal"]', function() {
                var target = $(this).data('target');
                $(target).modal('show');
            });
        });
    </script>
</body>
</html>
