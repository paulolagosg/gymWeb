<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$rootPath = __DIR__ . $uri;
$publicPath = __DIR__ . '/private/public' . $uri;

if ($uri !== '/') {
    if (is_file($rootPath)) {
        return false;
    }

    if (is_file($publicPath)) {
        $mimeType = mime_content_type($publicPath) ?: 'application/octet-stream';

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($publicPath));
        readfile($publicPath);
        exit;
    }
}

require __DIR__ . '/index.php';
