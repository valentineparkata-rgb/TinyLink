<?php
include __DIR__ . '/../db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);
$code = trim($data['code'] ?? '');

if (!$code) {
    echo json_encode(["error" => "No code provided"]);
    exit;
}

$stmt = $db->prepare("SELECT long_url FROM links WHERE short_code = ?");
$stmt->execute([$code]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if ($link) {
    echo json_encode(["url" => $link['long_url']]);
} else {
    echo json_encode(["error" => "Invalid code"]);
}
