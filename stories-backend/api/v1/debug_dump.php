<?php
// Simply output the raw source of api.php so we can be 100% sure it's deployed
echo '<pre>';
echo htmlspecialchars(file_get_contents(__DIR__ . '/api.php'));
echo '</pre>';