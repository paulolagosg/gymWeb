<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$requestedPath = __DIR__ . $uri;

if ($uri !== '/' && file_exists($requestedPath) && !is_dir($requestedPath)) {
    return false;
}

require __DIR__ . '/index.php';
