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

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8mb4")) {
    die("Ошибка установки кодировки: " . $conn->error);
}

// Получаем список сотрудников
$employees = [];
$emp_result = $conn->query("SELECT id, name FROM employees ORDER BY name");
if ($emp_result && $emp_result->num_rows > 0) {
    while ($row = $emp_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Обработчик удаления 
if (isset($_POST['delete_schedule'])) {
    $id = intval($_POST['schedule_id']);
    $stmt = $conn->prepare("DELETE FROM work_schedule WHERE schedule_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = $stmt->affected_rows ? "Запись графика успешно удалена" : "Ошибка при удалении";
    $stmt->close();
}

//Обработчик сохранения
if (isset($_POST['save_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $employee_id = intval($_POST['employee_id']);
    $start_datetime = trim($_POST['start_datetime']);
    $end_datetime = trim($_POST['end_datetime']);
    $actual_start_datetime = !empty($_POST['actual_start_datetime']) ? trim($_POST['actual_start_datetime']) : null;
    $actual_end_datetime = !empty($_POST['actual_end_datetime']) ? trim($_POST['actual_end_datetime']) : null;
    $status = $_POST['status'];

    if (empty($employee_id) || empty($start_datetime) || empty($end_datetime)) {
        $error = "Обязательные поля: ID сотрудника, время начала и окончания";
    } else {
        // Проверка пересечения смен
        $stmt = $conn->prepare("SELECT COUNT(*) FROM work_schedule 
            WHERE employee_id = ? AND schedule_id != ? AND 
            ((start_datetime <= ? AND end_datetime > ?) OR (start_datetime < ? AND end_datetime >= ?))");
        $stmt->bind_param("iissss", $employee_id, $schedule_id, $start_datetime, $start_datetime, $end_datetime, $end_datetime);
        $stmt->execute();
        $stmt->bind_result($overlap_count);
        $stmt->fetch();
        $stmt->close();

        if ($overlap_count > 0) {
            $error = "Ошибка: новая смена пересекается с существующей сменой сотрудника";
        } else {
            $stmt = $conn->prepare("UPDATE work_schedule SET employee_id=?, start_datetime=?, end_datetime=?, actual_start_datetime=?, actual_end_datetime=?, status=? WHERE schedule_id=?");
            $stmt->bind_param("isssssi", $employee_id, $start_datetime, $end_datetime, $actual_start_datetime, $actual_end_datetime, $status, $schedule_id);
            if ($stmt->execute()) {
                $message = "Данные графика успешно обновлены";
            } else {
                $error = "Ошибка при сохранении: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Обработчик добавления
if (isset($_POST['add_schedule'])) {
    $employee_id = intval($_POST['new_employee_id']);
    $start_datetime = !empty($_POST['new_start_datetime']) ? date('Y-m-d H:i:s', strtotime($_POST['new_start_datetime'])) : null;
    $end_datetime = !empty($_POST['new_end_datetime']) ? date('Y-m-d H:i:s', strtotime($_POST['new_end_datetime'])) : null;
    $actual_start_datetime = !empty($_POST['new_actual_start_datetime']) ? date('Y-m-d H:i:s', strtotime($_POST['new_actual_start_datetime'])) : null;
    $actual_end_datetime = !empty($_POST['new_actual_end_datetime']) ? date('Y-m-d H:i:s', strtotime($_POST['new_actual_end_datetime'])) : null;
    $status = trim($_POST['new_status']);

    if (empty($employee_id) || empty($start_datetime) || empty($end_datetime)) {
        $error = "Обязательные поля: ID сотрудника, время начала и окончания";
    } else {
        // Проверка пересечения смен
        $stmt = $conn->prepare("SELECT COUNT(*) FROM work_schedule 
            WHERE employee_id=? AND ((start_datetime <= ? AND end_datetime > ?) OR (start_datetime < ? AND end_datetime >= ?))");
        $stmt->bind_param("issss", $employee_id, $start_datetime, $start_datetime, $end_datetime, $end_datetime);
        $stmt->execute();
        $stmt->bind_result($overlap_count);
        $stmt->fetch();
        $stmt->close();

        if ($overlap_count > 0) {
            $error = "Ошибка: новая смена пересекается с существующей сменой сотрудника";
        } else {
            $stmt = $conn->prepare("INSERT INTO work_schedule (employee_id, start_datetime, end_datetime, actual_start_datetime, actual_end_datetime, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $employee_id, $start_datetime, $end_datetime, $actual_start_datetime, $actual_end_datetime, $status);
            if ($stmt->execute()) {
                $message = "Новый график успешно добавлен";
            } else {
                $error = "Ошибка добавления: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Фильтр 
$filter_employee_id = $_GET['filter_employee_id'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_actual_start_date = $_GET['filter_actual_start_date'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filter_employee_id !== '') {
    $where[] = 'ws.employee_id=?';
    $params[] = $filter_employee_id;
    $types .= 'i';
}
if ($filter_start_date !== '') {
    $where[] = 'DATE(ws.start_datetime)=?';
    $params[] = $filter_start_date;
    $types .= 's';
}
if ($filter_actual_start_date !== '') {
    $where[] = 'DATE(ws.actual_start_datetime)=?';
    $params[] = $filter_actual_start_date;
    $types .= 's';
}

$sql = "SELECT ws.*, e.name AS employee_name FROM work_schedule ws LEFT JOIN employees e ON ws.employee_id=e.id";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY ws.start_datetime DESC";

$stmt = $conn->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dragon | Админ График работы</title>
<link rel="stylesheet" href="StyleHeaderFooter.css">
<link rel="icon" href="img/лого.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.table td, .table th { vertical-align: middle; min-width: 140px; }
.small-input { min-width: 140px; }
main { background-color: #FFF0DF; min-height: 86vh; width: 100%; }
.action-buttons { white-space: nowrap; }
.add-schedule-form, .filter-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<header>
    <div class="icon">
        <a href="Glavnay.html"><img class="logo" src="img/лого.png" alt="логотип"></a>
    </div>
    <div class="choice">
        <a id="PageMenu" href="Menu.html">Меню</a>
        <a id="PageBronirovanie" href="Bronirovanie.html">Бронирование</a>
        <a id="PageShow" href="Show.html">Шоу</a>
        <a id="PageONas" href="ONas.html">О нас</a>
        <a id="PageAvtorizacia" href="Avtorizacia.html">
            <img class="avtorisation" src="img/авторизация.png" alt="авторизация">
        </a>
    </div>
</header>

<main class="container-fluid px-4 mt-4">
<h2>Управление графиком работы</h2>

<!-- Фильтр -->
<div class="filter-form">
    <h4>Фильтр смен</h4>
    <form method="GET" class="row g-2">
        <div class="col-md-3">
            <label class="form-label">Сотрудник</label>
            <select class="form-select" name="filter_employee_id">
                <option value="">Все</option>
                <?php foreach ($employees as $employee): ?>
                <option value="<?= $employee['id'] ?>" <?= $filter_employee_id==$employee['id']?'selected':'' ?>>
                    ID <?= $employee['id'] ?> — <?= htmlspecialchars($employee['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Дата начала</label>
            <input type="date" class="form-control" name="filter_start_date" value="<?= htmlspecialchars($filter_start_date) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Факт. дата начала</label>
            <input type="date" class="form-control" name="filter_actual_start_date" value="<?= htmlspecialchars($filter_actual_start_date) ?>">
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Применить фильтр</button>
        </div>
    </form>
</div>

<!-- Форма добавления -->
<div class="add-schedule-form">
    <h4>Добавить новый график работы</h4>
    <form method="POST">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Сотрудник *</label>
                <select class="form-select" name="new_employee_id" required>
                    <option value="">Выберите сотрудника</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee['id'] ?>">
                            ID <?= $employee['id'] ?> — <?= htmlspecialchars($employee['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Время начала *</label>
                <input type="datetime-local" class="form-control" name="new_start_datetime" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Время окончания *</label>
                <input type="datetime-local" class="form-control" name="new_end_datetime" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Фактическое начало</label>
                <input type="datetime-local" class="form-control" name="new_actual_start_datetime">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Фактическое окончание</label>
                <input type="datetime-local" class="form-control" name="new_actual_end_datetime">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Статус</label>
                <select class="form-select" name="new_status">
                    <option value="Подтверждена">Подтверждена</option>
                    <option value="Отменена">Отменена</option>
                    <option value="Завершена">Завершена</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" name="add_schedule" class="btn btn-success w-100">Добавить график</button>
            </div>
        </div>
    </form>
</div>

<!-- Сообщения -->
<?php if (isset($message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Таблица смен -->
<?php if (empty($schedules)): ?>
<div class="alert alert-info">Записи графика работы не найдены.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-bordered align-middle">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Сотрудник</th>
<th>Время начала</th>
<th>Время окончания</th>
<th>Факт. начало</th>
<th>Факт. окончание</th>
<th>Статус</th>
<th>Действия</th>
</tr>
</thead>
<tbody>
<?php foreach ($schedules as $schedule): ?>
<tr>
    <form method="POST">
        <td><?= htmlspecialchars($schedule['schedule_id']) ?>
            <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
        </td>
        <td>
            <select class="form-select small-input" name="employee_id" required>
                <?php foreach ($employees as $employee): ?>
                <option value="<?= $employee['id'] ?>" <?= $schedule['employee_id']==$employee['id']?'selected':'' ?>>
                    ID <?= $employee['id'] ?> — <?= htmlspecialchars($employee['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="datetime-local" class="form-control small-input" name="start_datetime" value="<?= date('Y-m-d\TH:i', strtotime($schedule['start_datetime'])) ?>" required></td>
        <td><input type="datetime-local" class="form-control small-input" name="end_datetime" value="<?= date('Y-m-d\TH:i', strtotime($schedule['end_datetime'])) ?>" required></td>
        <td><input type="datetime-local" class="form-control small-input" name="actual_start_datetime" value="<?= $schedule['actual_start_datetime']?date('Y-m-d\TH:i', strtotime($schedule['actual_start_datetime'])):'' ?>"></td>
        <td><input type="datetime-local" class="form-control small-input" name="actual_end_datetime" value="<?= $schedule['actual_end_datetime']?date('Y-m-d\TH:i', strtotime($schedule['actual_end_datetime'])):'' ?>"></td>
        <td>
            <select class="form-select small-input" name="status">
                <option value="Подтверждена" <?= $schedule['status']=='Подтверждена'?'selected':'' ?>>Подтверждена</option>
                <option value="Отменена" <?= $schedule['status']=='Отменена'?'selected':'' ?>>Отменена</option>
                <option value="Завершена" <?= $schedule['status']=='Завершена'?'selected':'' ?>>Завершена</option>
            </select>
        </td>
        <td class="action-buttons">
            <button type="submit" name="save_schedule" class="btn btn-sm btn-primary">Сохранить</button>
            <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?= $schedule['schedule_id'] ?>">Удалить</button>
        </td>
    </form>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

</main>

<footer class="mt-4">
<p id="footer">Заведение "Dragon" | Заведение "Дракон" в Дзержинске, 2024</p>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="ScriptPages.js"></script>
<script>
// Обработчик удаления
$(document).on('click', '.delete-btn', function() {
    const scheduleId = $(this).data('id');
    if (confirm('Вы уверены, что хотите удалить эту запись графика?')) {
        const form = $('<form method="POST"></form>');
        form.append('<input type="hidden" name="delete_schedule" value="1">');
        form.append('<input type="hidden" name="schedule_id" value="' + scheduleId + '">');
        $('body').append(form);
        form.submit();
    }
});
</script>
</body>
</html>