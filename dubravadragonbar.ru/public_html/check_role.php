<?php
session_start();

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "auth" => false
    ]);
    exit;
}

echo json_encode([
    "auth" => true,
    "role" => $_SESSION['role'] ?? 'user'
]);