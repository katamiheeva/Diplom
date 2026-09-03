<?php
session_start();
header('Content-Type: application/json');

//отключение хэш
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$host = 'localhost';
$dbname = 'o91012r4_bb';
$username = 'o91012r4_bb';
$password = 'EkaterinaDiplomDragon1.';

$response = ['success' => false, 'messages' => []];

//проверка авторизации
if (!isset($_SESSION['user_id'])) {
    $response['messages'][] = 'Для бронирования необходимо авторизоваться';
    echo json_encode($response);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $client_name = trim($_POST['ClientsName'] ?? '');
    $phone_number = trim($_POST['PhoneNumber'] ?? '');
    $table_number = trim($_POST['StolNumber'] ?? '');
    $visit_date = trim($_POST['Date'] ?? '');
    $time_from = trim($_POST['TimeNach'] ?? '');
    $time_to = trim($_POST['TimeCon'] ?? '');
    $comment = trim($_POST['Commentariy'] ?? '');

    $phone_number = preg_replace('/\D/', '', $phone_number);

    $errors = [];

    if (!$client_name) $errors[] = 'Введите имя';
    if (!$phone_number) $errors[] = 'Введите номер телефона';
    if (!$table_number) $errors[] = 'Введите номер стола';
    if (!$visit_date) $errors[] = 'Введите дату';
    if (!$time_from) $errors[] = 'Введите время начала';
    if (!$time_to) $errors[] = 'Введите время окончания';

    if (!preg_match('/^\d{10,11}$/', $phone_number))
        $errors[] = 'Неверный формат номера телефона';

    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $visit_date))
        $errors[] = 'Дата должна быть в формате дд.мм.гггг';

    if (!preg_match('/^\d{2}:\d{2}$/', $time_from))
        $errors[] = 'Время начала должно быть чч:мм';

    if (!preg_match('/^\d{2}:\d{2}$/', $time_to))
        $errors[] = 'Время окончания должно быть чч:мм';

    if ($time_from && $time_from < '16:00')
        $errors[] = 'Рабочий день начинается с 16:00';

    if ($errors) {
        $response['messages'] = $errors;
        echo json_encode($response);
        exit;
    }

    //проверка даты
    $date_parts = explode('.', $visit_date);
    $day = (int)$date_parts[0];
    $month = (int)$date_parts[1];
    $year = (int)$date_parts[2];

    if (!checkdate($month, $day, $year)) {
        $response['messages'][] = 'Такой даты не существует';
        echo json_encode($response);
        exit;
    }

    $visit_date_formatted = "$year-$month-$day";

    //переход через полночь
    $is_overnight = false;
    if ($time_to <= $time_from) {
        if ($time_to <= '04:00') {
            $is_overnight = true;
        } else {
            $response['messages'][] =
                'Время окончания должно быть позже начала или до 04:00 следующего дня. Проверьте время работы заведения на странице "О нас"!';
            echo json_encode($response);
            exit;
        }
    }

    //занятость стола
    $checkSql = "
        SELECT COUNT(*) FROM bookings
        WHERE table_number = :table
        AND visit_date = :date
        AND (
            (time_from <= :to AND time_to >= :from)
        )
    ";

    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([
        ':table' => $table_number,
        ':date' => $visit_date_formatted,
        ':from' => $time_from,
        ':to' => $time_to
    ]);

    if ($stmt->fetchColumn() > 0) {
        $response['messages'][] = 'Этот стол уже занят на выбранное время!';
        echo json_encode($response);
        exit;
    }

    //запись бронир в бд
    $insertSql = "
        INSERT INTO bookings
        (client_name, phone, table_number, visit_date, time_from, time_to, comment, user_id)
        VALUES
        (:name, :phone, :table, :date, :from, :to, :comment, :user_id)
    ";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        ':name' => $client_name,
        ':phone' => $phone_number,
        ':table' => $table_number,
        ':date' => $visit_date_formatted,
        ':from' => $time_from,
        ':to' => $time_to,
        ':comment' => $comment,
        ':user_id' => $_SESSION['user_id']
    ]);

    $response['success'] = true;
    $response['messages'][] = 'Бронирование успешно оформлено! Ожидайте обновление статуса в личном кабинете.';

} catch (PDOException $e) {
    $response['messages'][] = "Ошибка базы данных: " . $e->getMessage();
}

echo json_encode($response);