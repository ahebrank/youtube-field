<?php
require_once('youtube.class.php');

$y = new Youtube('API_KEY', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

echo $y->since_upload();
echo $y->views();

echo "\n";