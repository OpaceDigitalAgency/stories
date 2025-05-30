<?php
echo "Discovery system deployment test<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "Discovery process file exists: " . (file_exists('book-discovery-process.php') ? 'YES' : 'NO') . "<br>";
echo "Discovery folder exists: " . (is_dir('book-discovery') ? 'YES' : 'NO') . "<br>";

if (is_dir('book-discovery')) {
    echo "Discovery folder contents:<br>";
    $files = scandir('book-discovery');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
}
?>