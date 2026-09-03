<?php
session_start();

//только для admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: Avtorizacia.html');
    exit;
}

$host = 'localhost';
$dbname = 'o91012r4_bb';
$username = 'o91012r4_bb';
$password = 'EkaterinaDiplomDragon1.';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}
$conn->set_charset("utf8");

//ОБРАБОТКА СОХРАНЕНИЯ УДАЛЕНИЯ

$message = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $submit_type = $_POST['submit_type'] ?? '';

    if ($booking_id > 0) {
        // Удаление
        if ($submit_type === 'delete') {
            $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            if ($stmt->execute()) {
                $message = "Запись успешно удалена!";
            } else {
                $message = "Ошибка при удалении: " . $stmt->error;
            }
            $stmt->close();
        }

        //Сохранение
        if ($submit_type === 'save') {
            $client_name = trim($_POST['client_name']);
            $phone = trim($_POST['phone']);
            $table_number = trim($_POST['table_number']);
            $visit_date = $_POST['visit_date'];

            $time_from = $_POST['time_from'] ?: '00:00';
            if (strlen($time_from) === 5) $time_from .= ':00';
            $time_to = $_POST['time_to'] ?: '00:00';
            if (strlen($time_to) === 5) $time_to .= ':00';

            $comment = trim($_POST['comment']);
            $status = $_POST['status'] ?? 'Не подтверждено';

            $allowed_status = ['Подтверждено','Не подтверждено','Отмена'];
            if (!in_array($status, $allowed_status)) $status = 'Не подтверждено';

            $stmt = $conn->prepare("
                UPDATE bookings
                SET
                    client_name = ?,
                    phone = ?,
                    table_number = ?,
                    visit_date = ?,
                    time_from = ?,
                    time_to = ?,
                    comment = ?,
                    Status = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssssssssi",
                $client_name,
                $phone,
                $table_number,
                $visit_date,
                $time_from,
                $time_to,
                $comment,
                $status,
                $booking_id
            );
            if ($stmt->execute()) {
                $message = "Изменения сохранены!";
            } else {
                $message = "Ошибка при сохранении: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

//ФИЛЬТРЫ

$filter_date = $_GET['filter_date'] ?? '';
$filter_user = $_GET['filter_user'] ?? '';

$sql = "SELECT id, user_id, client_name, phone, table_number, visit_date, time_from, time_to, comment, Status FROM bookings WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_date)) {
    $sql .= " AND visit_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if (!empty($filter_user)) {
    $sql .= " AND user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}

$sql .= " ORDER BY visit_date DESC, time_from DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dragon | Дракон. Админ брони столиков</title>
<link rel="stylesheet" href="StyleHeaderFooter.css">
<link rel="icon" href="img/лого.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.table td, .table th { vertical-align: middle; min-width: 120px; }
.small-input { min-width: 120px; }
main{ background-color: #FFF0DF; min-height: 86vh; width: 100%;}
</style>
</head>
<body>
<header>
    <div class="icon">
        <a href="Glavnay.html"><img class="logo" src="img/лого.png" alt="логотип"></a>
    </div>
    <div class="choice">
        <a href="Menu.html">Меню</a>
        <a href="Bronirovanie.html">Бронирование</a>
        <a href="Show.html">Шоу</a>
        <a href="ONas.html">О нас</a>
        <a href="Avtorizacia.html"><img class="avtorisation" src="img/авторизация.png" alt="авторизация"></a>
    </div>
</header>

<main class="container-fluid px-4 mt-4">
<h2>Управление всеми записями о бронировании</h2>

<!-- Фильтры -->
<form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
        <label>Фильтр по дате</label>
        <input type="date" name="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
    </div>
    <div class="col-md-3">
        <label>Фильтр по User ID</label>
        <input type="number" name="filter_user" class="form-control" value="<?= htmlspecialchars($filter_user) ?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary">Применить фильтр</button>
    </div>
</form>

<!-- вывод сообщений -->
<?php if (!empty($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (empty($bookings)): ?>
<div class="alert alert-info">Бронирований нет.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-bordered align-middle">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>User</th>
<th>Клиент</th>
<th>Телефон</th>
<th>Стол</th>
<th>Дата</th>
<th>Время с</th>
<th>Время до</th>
<th>Комментарий</th>
<th>Статус</th>
<th>Сохранить</th>
<th>Удалить</th>
</tr>
</thead>
<tbody>
<?php foreach ($bookings as $booking): ?>
<tr>
<form method="POST">
<input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

<td><?= $booking['id'] ?></td>
<td><?= $booking['user_id'] ?></td>

<td><input class="form-control small-input" name="client_name" value="<?= htmlspecialchars($booking['client_name']) ?>"></td>
<td><input class="form-control small-input" name="phone" value="<?= htmlspecialchars($booking['phone']) ?>"></td>
<td><input class="form-control small-input" name="table_number" value="<?= htmlspecialchars($booking['table_number']) ?>"></td>
<td><input type="date" class="form-control small-input" name="visit_date" value="<?= htmlspecialchars($booking['visit_date']) ?>"></td>

<td><input type="time" class="form-control small-input" name="time_from" value="<?= !empty($booking['time_from']) && $booking['time_from'] != '00:00:00' ? substr($booking['time_from'],0,5) : '' ?>"></td>
<td><input type="time" class="form-control small-input" name="time_to" value="<?= !empty($booking['time_to']) && $booking['time_to'] != '00:00:00' ? substr($booking['time_to'],0,5) : '' ?>"></td>

<td><input class="form-control small-input" name="comment" value="<?= htmlspecialchars($booking['comment']) ?>"></td>

<td>
<select name="status" class="form-select small-input">
    <option value="Не подтверждено" <?= $booking['Status'] === 'Не подтверждено' ? 'selected' : '' ?>>Не подтверждено</option>
    <option value="Подтверждено" <?= $booking['Status'] === 'Подтверждено' ? 'selected' : '' ?>>Подтверждено</option>
    <option value="Отмена" <?= $booking['Status'] === 'Отмена' ? 'selected' : '' ?>>Отмена</option>
</select>
</td>

<td><button type="submit" name="submit_type" value="save" class="btn btn-primary btn-sm">💾</button></td>
<td><button type="submit" name="submit_type" value="delete" class="btn btn-dark btn-sm" onclick="return confirm('Удалить запись?')">🗑</button></td>

</form>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</main>

<footer class="mt-4">
<p id="footer">Заведение "Dragon Bar" | Заведение "Дракон Бар" в Дзержинске, 2024</p>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="ScriptPages.js"></script>
</body>
</html>