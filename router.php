<?php
// Router for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// API routes
if (strpos($uri, '/api/') === 0) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Default: serve index.html
if ($uri === '/') {
    require __DIR__ . '/public/index.html';
    return true;
}

// Serve other public files
$file = __DIR__ . '/public' . $uri;
if (file_exists($file)) {
    return false;
}

http_response_code(404);
echo 'Not found';
