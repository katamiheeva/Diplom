<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["auth" => false]);
    exit;
}

// Если роли и имени ещё нет в сессии, можно достать из БД
$host = 'localhost';
$dbname = 'o91012r4_bb';
$username = 'o91012r4_bb';
$password = 'EkaterinaDiplomDragon1.';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["auth" => false]);
    exit;
}
$conn->set_charset("utf8");

$stmt = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    echo json_encode(["auth" => false]);
    exit;
}

// Возвращаем и имя, и роль, и auth = true
echo json_encode([
    "auth" => true,
    "full_name" => $user['full_name'],
    "role" => $user['role']
]);