<?php
// Local preview only: reproduce the deployed /storage/app/public URL mapping.
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$base = str_starts_with($path, '/storage/app/public/') ? $root . '/storage/app/public' : $root . '/public';
$relative = str_starts_with($path, '/storage/app/public/') ? substr($path, strlen('/storage/app/public')) : $path;
$file = realpath($base . $relative);
if ($file && str_starts_with($file, realpath($base) . DIRECTORY_SEPARATOR) && is_file($file)) {
    $types = ['css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'gif' => 'image/gif'];
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if (isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
        readfile($file);
        return;
    }
}
require $root . '/public/index.php';
