<?php
// Router for PHP built-in server to serve Laravel's public directory
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false; // serve the requested resource as-is
}
require_once __DIR__ . '/public/index.php';
