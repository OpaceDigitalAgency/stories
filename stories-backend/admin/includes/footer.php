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
        .admin-footer {
            background-color: white;
            border-top: 1px solid var(--gray-200);
            padding: 0;
            margin-top: 3rem;
            width: 100%;
        }

        .footer-content-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
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
        });
    </script>
</body>
</html>
