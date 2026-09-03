<?php
$host = 'localhost';
$dbname = 'o91012r4_bb';
$username = 'o91012r4_bb';
$password = 'EkaterinaDiplomDragon1.';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка БД");
}

$conn->set_charset("utf8");

$full_name = $_POST['username'];
$login = $_POST['login'];
$password = $_POST['password'];

if (!$full_name || !$login || !$password) {
    echo "Заполните все поля";
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Логин уже существует";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
INSERT INTO users (username, password_hash, full_name, role, created_at) 
VALUES (?, ?, ?, 'user', NOW())
");

$stmt->bind_param("sss", $login, $hash, $full_name);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "Ошибка";
}