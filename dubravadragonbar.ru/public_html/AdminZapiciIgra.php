<?php
session_start();

// 🔒 Доступ только для admin
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

// ОБРАБОТКА СОХРАНЕНИЯ / УДАЛЕНИЯ

$message = ''; // переменная для уведомлений

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = (int)($_POST['registration_id'] ?? 0);
    $submit_type = $_POST['submit_type'] ?? '';

    if ($registration_id > 0) {
        // 🗑 Удаление
        if ($submit_type === 'delete') {
            $stmt = $conn->prepare("DELETE FROM game_registrations WHERE id = ?");
            $stmt->bind_param("i", $registration_id);
            if ($stmt->execute()) {
                $message = "Запись успешно удалена!";
            } else {
                $message = "Ошибка при удалении: " . $stmt->error;
            }
            $stmt->close();
        }

        // Сохранение изменений
        if ($submit_type === 'save') {
            $team_name = trim($_POST['team_name']);
            $people_count = (int)$_POST['people_count'];
            $phone_number = trim($_POST['phone_number']);
            $game_date = $_POST['game_date'];
            $order_notes = trim($_POST['order_notes']);
            $serving_time = $_POST['serving_time'] ?: '00:00';
            if (strlen($serving_time) === 5) $serving_time .= ':00';
            $status = $_POST['status'] ?? 'Не подтверждено';

            // Проверка допустимых значений ENUM
            $allowed_status = ['Подтверждено', 'Не подтверждено', 'Отмена'];
            if (!in_array($status, $allowed_status)) $status = 'Не подтверждено';

            $stmt = $conn->prepare("
                UPDATE game_registrations
                SET
                    team_name = ?,
                    people_count = ?,
                    phone_number = ?,
                    game_date = ?,
                    order_notes = ?,
                    serving_time = ?,
                    Status = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sisssssi",
                $team_name,
                $people_count,
                $phone_number,
                $game_date,
                $order_notes,
                $serving_time,
                $status,
                $registration_id
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

// ФИЛЬТРЫ

$filter_date = $_GET['filter_date'] ?? '';
$filter_user = $_GET['filter_user'] ?? '';

$sql = "SELECT id, user_id, team_name, people_count, phone_number, game_date, order_notes, serving_time, created_at, Status FROM game_registrations WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_date)) {
    $sql .= " AND game_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if (!empty($filter_user)) {
    $sql .= " AND user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}

$sql .= " ORDER BY game_date DESC, serving_time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dragon | Дракон. Админ записи на "Своя Игра"</title>
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
<h2>Управление записями на игру</h2>

<!-- Фильтры -->
<form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
        <label>Фильтр по дате игры</label>
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

<!-- Вывод сообщений -->
<?php if (!empty($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (empty($registrations)): ?>
<div class="alert alert-info">Записей на игру нет.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-bordered align-middle">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>User</th>
<th>Название команды</th>
<th>Кол-во человек</th>
<th>Телефон</th>
<th>Дата игры</th>
<th>Время подачи</th>
<th>Заказ</th>
<th>Создано</th>
<th>Статус</th>
<th>Сохранить</th>
<th>Удалить</th>
</tr>
</thead>
<tbody>
<?php foreach ($registrations as $reg): ?>
<tr>
<form method="POST">
<input type="hidden" name="registration_id" value="<?= $reg['id'] ?>">

<td><?= $reg['id'] ?></td>
<td><?= $reg['user_id'] ?></td>

<td><input class="form-control small-input" name="team_name" value="<?= htmlspecialchars($reg['team_name']) ?>"></td>
<td><input type="number" class="form-control small-input" name="people_count" value="<?= $reg['people_count'] ?>"></td>
<td><input class="form-control small-input" name="phone_number" value="<?= htmlspecialchars($reg['phone_number']) ?>"></td>
<td><input type="date" class="form-control small-input" name="game_date" value="<?= htmlspecialchars($reg['game_date']) ?>"></td>

<td><input type="time" class="form-control small-input" name="serving_time" value="<?= !empty($reg['serving_time']) && $reg['serving_time'] != '00:00:00' ? substr($reg['serving_time'], 0, 5) : '' ?>"></td>
<td>
    <input class="form-control"
           name="order_notes"
           value="<?= htmlspecialchars($reg['order_notes']) ?>"
           style="min-width: 250px; width: 100%;">
</td>
<td><?= htmlspecialchars($reg['created_at']) ?></td>


<td>
    <select name="status" class="form-select small-input">
        <option value="Не подтверждено" <?= $reg['Status'] === 'Не подтверждено' ? 'selected' : '' ?>>Не подтверждено</option>
        <option value="Подтверждено" <?= $reg['Status'] === 'Подтверждено' ? 'selected' : '' ?>>Подтверждено</option>
        <option value="Отмена" <?= $reg['Status'] === 'Отмена' ? 'selected' : '' ?>>Отмена</option>
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
