<?php

/**
 * A small HTTP server for CurlTransportTest, run with PHP's built-in server.
 */

declare(strict_types=1);

$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/redirect') {
    header('Location: /echo', true, 302);
    echo 'moved';
    return;
}
if ($path === '/slow') {
    sleep(3);
    echo '{}';
    return;
}
if ($path === '/bytes') {
    header('Content-Type: image/png');
    header('X-Image-Width: 2');
    echo "\x89PNG\r\n\x1a\n";
    return;
}
if (preg_match('#^/status/(\d{3})$#', $path, $match)) {
    http_response_code((int) $match[1]);
    header('Content-Type: application/json');
    echo json_encode(['status' => (int) $match[1], 'message' => 'as requested']);
    return;
}

header('Content-Type: application/json');
header('X-Request-Id: fixture-1');
echo json_encode([
    'method'        => $_SERVER['REQUEST_METHOD'],
    'query'         => $_GET,
    'fields'        => $_POST,
    'files'         => array_map(static fn (array $file): array => ['name' => $file['name'], 'size' => $file['size'], 'type' => $file['type']], $_FILES),
    'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'accept'        => $_SERVER['HTTP_ACCEPT'] ?? null,
    'content_type'  => $_SERVER['CONTENT_TYPE'] ?? null,
]);
