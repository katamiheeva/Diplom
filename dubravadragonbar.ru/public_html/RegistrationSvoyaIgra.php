<?php
session_start();
header('Content-Type: application/json');

// убираем кэш
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Для записи на игру необходимо авторизоваться'
    ]);
    exit;
}

$host = 'localhost';
$dbname = 'o91012r4_bb';
$username = 'o91012r4_bb';
$password = 'EkaterinaDiplomDragon1.';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения: ' . $mysqli->connect_error]);
    exit();
}

$mysqli->set_charset('utf8');

$user_id = $_SESSION['user_id'];

// Данные из формы
$team_name = $mysqli->real_escape_string($_POST['NameComanda']);
$people_count = intval($_POST['ColvoChel']);
$phone_number = $mysqli->real_escape_string($_POST['PhoneNumber']);
$game_date = $mysqli->real_escape_string($_POST['Date']);
$order_notes = $mysqli->real_escape_string($_POST['Zakaz']);
$serving_time = $mysqli->real_escape_string($_POST['TimePodacha']);

$errors = [];

if (empty($team_name)) {
    $errors[] = 'Введите название команды';
}
if (empty($people_count) || $people_count <= 0) {
    $errors[] = 'Укажите корректное количество человек';
}
if (empty($phone_number)) {
    $errors[] = 'Введите номер телефона';
} else {
    // Очищаем номер от лишних символов
    $phone_number_clean = preg_replace('/\D/', '', $phone_number);
    if (!preg_match('/^\d{10,11}$/', $phone_number_clean)) {
        $errors[] = 'Неверный формат номера телефона';
    } else {
        $phone_number = $phone_number_clean;
    }
}
if (empty($game_date)) {
    $errors[] = 'Введите дату игры';
} else {
    $date_parts = explode('.', $game_date);
    if (count($date_parts) !== 3) {
        $errors[] = 'Дата должна быть в формате дд.мм.гггг';
    } else {
        $day = (int)$date_parts[0];
        $month = (int)$date_parts[1];
        $year = (int)$date_parts[2];
        if (!checkdate($month, $day, $year)) {
            $errors[] = 'Такой даты не существует';
        } else {
            // Форматируем дату для БД (гггг-мм-дд)
            $game_date_formatted = "$year-$month-$day";
        }
    }
}
if (empty($serving_time)) {
    $errors[] = 'Введите время подачи';
} elseif (!preg_match('/^\d{2}:\d{2}$/', $serving_time)) {
    $errors[] = 'Время должно быть в формате чч:мм';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode('; ', $errors)
    ]);
    $mysqli->close();
    exit();
}

$stmt = $mysqli->prepare("
    INSERT INTO game_registrations
    (team_name, people_count, phone_number, game_date, order_notes, serving_time, user_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка подготовки запроса: ' . $mysqli->error
    ]);
    $mysqli->close();
    exit();
}

$stmt->bind_param("sissssi", $team_name, $people_count, $phone_number, $game_date_formatted, $order_notes, $serving_time, $user_id);

// Выполняем запрос
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо за регистрацию! Дождитесь звонка подтверждения.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при регистрации: ' . $stmt->error
    ]);
}

$stmt->close();
$mysqli->close();
?>
