<?php
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

$path = str_replace(dirname($scriptName), '', $requestUri);
$path = trim($path, '/');
$path = explode('?', $path)[0];

if (empty($path)) {
    include 'public/index.php';
    exit;
}

if (preg_match('/^[a-zA-Z0-9]{6}$/', $path)) {
    $_GET['code'] = $path;
    include 'public/redirect.php';
    exit;
}

$cleanPath = explode('?', $_SERVER['REQUEST_URI'])[0];
$filePath = __DIR__ . $cleanPath;
if (file_exists($filePath) && is_file($filePath)) {
    return false;
}

http_response_code(404);
echo "404 - Page not found";
