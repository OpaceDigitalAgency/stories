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
?>
    <footer class="admin-footer" role="contentinfo">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-heading">Stories from the Web</h3>
                    <p>&copy; <?php echo date('Y'); ?> Stories from the Web. All rights reserved.</p>
                    <p class="text-muted">Version 2.1 - Enhanced Admin Dashboard</p>
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
                        <li><a href="mailto:support@storiesfromtheweb.org">Email Support</a></li>
                        <li><a href="https://github.com/OpaceDigitalAgency/stories/issues" target="_blank">Report an Issue</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>Made with <span aria-hidden="true">❤️</span><span class="visually-hidden">love</span> by the Stories from the Web team</p>
            </div>
        </div>
    </footer>
</body>
</html>
