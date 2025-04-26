<?php
//  file: admin/opcache_reset.php  (put anywhere under the site)
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache reset OK';
} else {
    echo 'OPcache not enabled';
}
