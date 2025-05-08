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
        <div class="footer-content-compact">
            <div class="footer-info">
                <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteCopyright); ?></span>
                <span class="footer-version">v<?php echo htmlspecialchars($siteVersion); ?></span>
            </div>

            <div class="footer-links-compact">
                <a href="/docs/comprehensive-system-architecture-new.php" target="_blank">Documentation</a>
                <a href="/docs/KNOWN_ISSUES_AND_FIXES.md" target="_blank">Known Issues</a>
                <a href="/public/optimize_image.php" target="_blank">Image Optimizer</a>
                <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>">Support</a>
            </div>
        </div>
    </footer>

    <style>
        /* Full width footer without fixed positioning to avoid overlapping */
        .admin-footer {
            background-color: white;
            border-top: 1px solid var(--gray-200);
            padding: 0;
            margin-top: 3rem;
            width: 100%;
            position: relative;
            left: 0;
            right: 0;
            box-sizing: border-box;
            z-index: 10; /* Lower z-index to avoid conflicts with sticky save bar */
        }

        /* Add padding to the bottom of the page when sticky save bar is present */
        body.has-sticky-save-bar .admin-footer {
            margin-bottom: 70px; /* Height of the sticky save bar */
        }

        .footer-content-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            width: 100%;
            box-sizing: border-box;
        }

        .footer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .footer-version {
            background-color: var(--gray-200);
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
        }

        .footer-links-compact {
            display: flex;
            gap: 1.5rem;
        }

        .footer-links-compact a {
            color: var(--primary);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links-compact a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .footer-content-compact {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1rem;
            }

            .footer-links-compact {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>

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

            // Detect if page has sticky save bar and add class to body
            if ($('.sticky-save-bar, .sticky-action-bar').length > 0) {
                $('body').addClass('has-sticky-save-bar');
            }
        });
    </script>
</body>
</html>
