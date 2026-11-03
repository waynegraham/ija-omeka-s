<?php
// Loopback-only test server; never deploy this router.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$theme = dirname(__DIR__, 2);
$core = getenv('OMEKA_PATH') ?: dirname(__DIR__, 4);
if ($path === '/thumbnail.svg') {
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="250" viewBox="0 0 200 250"><rect width="200" height="250" fill="#e9e4d7"/><path d="M30 50h140M30 80h140M30 110h140M30 140h100" stroke="#585754" stroke-width="5"/></svg>';
    return;
}
if ($path === '/items/search') $path = '/search.html';
[$root, $relative] = str_starts_with($path, '/theme/asset/')
    ? [$theme . '/asset', substr($path, 13)]
    : (str_starts_with($path, '/application/asset/')
        ? [$core . '/application/asset', substr($path, 19)]
        : [__DIR__ . '/generated', ltrim($path, '/')]);
$file = realpath($root . '/' . $relative);
if (!$file || !str_starts_with($file, realpath($root) . '/') || !is_file($file)) {
    http_response_code(404);
    return;
}
$types = ['html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'woff2' => 'font/woff2'];
header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
readfile($file);
