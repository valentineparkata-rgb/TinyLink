<?php
include __DIR__ . '/../db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (!$apiKey) {
    http_response_code(401);
    echo json_encode(["error" => "API key required"]);
    exit;
}

$stmt = $db->prepare("SELECT id FROM users WHERE api_key = ? AND is_admin = true");
$stmt->execute([$apiKey]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid API key"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$longUrl = trim($data['url'] ?? '');
$customCode = trim($data['short_code'] ?? '');

if (!$longUrl) {
    http_response_code(400);
    echo json_encode(["error" => "URL is required"]);
    exit;
}

if (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid URL format"]);
    exit;
}

$stmt = $db->prepare("SELECT short_code FROM links WHERE long_url = ?");
$stmt->execute([$longUrl]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $shortCode = $existing['short_code'];
} else {
    if ($customCode) {
        if (!preg_match('/^[a-zA-Z0-9]{3,10}$/', $customCode)) {
            http_response_code(400);
            echo json_encode(["error" => "Short code must be 3-10 alphanumeric characters"]);
            exit;
        }
        
        $stmt = $db->prepare("SELECT id FROM links WHERE short_code = ?");
        $stmt->execute([$customCode]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(["error" => "Short code already exists"]);
            exit;
        }
        $shortCode = $customCode;
    } else {
        $shortCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    }
    
    $stmt = $db->prepare("INSERT INTO links (user_id, long_url, short_code) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $longUrl, $shortCode]);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$shortUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . $shortCode;

echo json_encode([
    "success" => true,
    "short_code" => $shortCode,
    "short_url" => $shortUrl,
    "long_url" => $longUrl
]);
