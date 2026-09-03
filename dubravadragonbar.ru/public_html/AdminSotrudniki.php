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

// Удаление
if (isset($_POST['delete_employee'])) {
    $id = intval($_POST['employee_id']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    $message = $stmt->execute() ? "Сотрудник успешно удалён" : "Ошибка при удалении: ".$conn->error;
}

// Сохранение изменений 
if (isset($_POST['save_employee'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $position = trim($_POST['position']);
    $salary_type = $_POST['salary_type'];

    // Преобразуем пустые значения в NULL
    $hourly_rate = isset($_POST['hourly_rate']) && $_POST['hourly_rate'] !== '' ? floatval($_POST['hourly_rate']) : null;
    $fixed_salary = isset($_POST['fixed_salary']) && $_POST['fixed_salary'] !== '' ? floatval($_POST['fixed_salary']) : null;

    // Валидация обязательных полей
    if (empty($name)) {
        $error = "Имя сотрудника обязательно для заполнения";
    } else {
        $sql = "UPDATE employees 
                SET name=?, phone=?, email=?, position=?, salary_type=?, hourly_rate=?, fixed_salary=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Используем привязку с null-safe значениями
            $stmt->bind_param(
                "sssssddi", 
                $name, 
                $phone, 
                $email, 
                $position, 
                $salary_type, 
                $hourly_rate, 
                $fixed_salary, 
                $id
            );

            if ($stmt->execute()) {
                $message = "Данные сотрудника успешно обновлены";
            } else {
                $error = "Ошибка при сохранении: " . $stmt->error;
            }
        } else {
            $error = "Ошибка подготовки запроса: " . $conn->error;
        }
    }
}

//  Добавление нового 
if (isset($_POST['add_employee'])) {
    $name = trim($_POST['new_name']);
    $phone = trim($_POST['new_phone']);
    $email = trim($_POST['new_email']);
    $position = trim($_POST['new_position']);
    $salary_type = $_POST['new_salary_type'];
    $hourly_rate = !empty($_POST['new_hourly_rate']) ? floatval($_POST['new_hourly_rate']) : null;
    $fixed_salary = !empty($_POST['new_fixed_salary']) ? floatval($_POST['new_fixed_salary']) : null;

    if (empty($name)) {
        $error = "Имя сотрудника обязательно для заполнения";
    } else {
        $stmt = $conn->prepare("INSERT INTO employees (name, phone, email, position, salary_type, hourly_rate, fixed_salary) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssddd", $name, $phone, $email, $position, $salary_type, $hourly_rate, $fixed_salary);
        $message = $stmt->execute() ? "Новый сотрудник успешно добавлен" : "Ошибка при добавлении: ".$stmt->error;
    }
}

// Фильтры 
$filter_name = trim($_GET['filter_name'] ?? '');
$filter_salary_type = trim($_GET['filter_salary_type'] ?? '');

// Формируем WHERE и параметры
$where = [];
$params = [];
$types = '';

if ($filter_name !== '') {
    $where[] = "(name LIKE ? OR id=?)";
    $params[] = "%$filter_name%";
    $params[] = intval($filter_name);
    $types .= "si";
}

if ($filter_salary_type !== '') {
    $where[] = "salary_type=?";
    $params[] = $filter_salary_type; // теперь прямое совпадение с ENUM
    $types .= "s";
}

$sql = "SELECT * FROM employees";
if ($where) $sql .= " WHERE ".implode(" AND ", $where);
$sql .= " ORDER BY name";

$stmt = $conn->prepare($sql);
if ($stmt && $where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) $employees[] = $row;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dragon | Сотрудники</title>
<link rel="stylesheet" href="StyleHeaderFooter.css">
<link rel="icon" href="img/лого.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.table td, .table th { vertical-align: middle; min-width: 120px; }
.small-input { min-width: 120px; }
main { background-color: #FFF0DF; min-height: 86vh; width: 100%; }
.action-buttons { white-space: nowrap; }
.add-employee-form, .filter-form { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
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
<h2>Управление сотрудниками</h2>

<!-- Фильтр -->
<div class="filter-form">
<form method="GET" class="row g-3">
    <div class="col-md-4">
        <label>Имя или ID</label>
        <input class="form-control" name="filter_name" value="<?= htmlspecialchars($filter_name) ?>" placeholder="Введите имя или ID">
    </div>
    <div class="col-md-4">
        <label>Тип зарплаты</label>
        <select class="form-select" name="filter_salary_type">
            <option value="">Все</option>
            <option value="Фиксированная" <?= $filter_salary_type==='Фиксированная'?'selected':'' ?>>Фиксированная</option>
            <option value="Почасовая" <?= $filter_salary_type==='Почасовая'?'selected':'' ?>>Почасовая</option>
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Применить фильтр</button>
    </div>
</form>
</div>

<!-- Форма добавления нового сотрудника -->
<div class="add-employee-form">
<h4>Добавить нового сотрудника</h4>
<form method="POST">
<div class="row">
<div class="col-md-3 mb-3"><label>Имя *</label><input class="form-control" name="new_name" required></div>
<div class="col-md-3 mb-3"><label>Телефон</label><input class="form-control" name="new_phone"></div>
<div class="col-md-3 mb-3"><label>Почта</label><input type="email" class="form-control" name="new_email"></div>
<div class="col-md-3 mb-3"><label>Должность</label><input class="form-control" name="new_position"></div>
</div>
<div class="row">
<div class="col-md-3 mb-3">
<label>Тип заработной платы</label>
<select class="form-select" name="new_salary_type">
<option value="Фиксированная">Фиксированная</option>
<option value="Почасовая">Почасовая</option>
</select>
</div>
<div class="col-md-3 mb-3"><label>Почасовая плата (руб/час)</label><input type="number" step="0.01" class="form-control" name="new_hourly_rate"></div>
<div class="col-md-3 mb-3"><label>Фиксированная плата (руб/мес)</label><input type="number" step="0.01" class="form-control" name="new_fixed_salary"></div>
<div class="col-md-3 mb-3"><label>&nbsp;</label><button type="submit" name="add_employee" class="btn btn-success w-100">Добавить сотрудника</button></div>
</div>
</form>
</div>

<?php if(isset($message)): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if(empty($employees)): ?>
<div class="alert alert-info">Сотрудники не найдены.</div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-striped table-bordered align-middle">
<thead class="table-dark">
<tr><th>ID</th><th>Имя</th><th>Телефон</th><th>Почта</th><th>Должность</th><th>Тип зарплаты</th><th>Почасовая плата</th><th>Фиксированная плата</th><th>Действия</th></tr>
</thead>
<tbody>
<?php foreach($employees as $emp): ?>
<tr>
<form method="POST">
<td><?= $emp['id'] ?><input type="hidden" name="id" value="<?= $emp['id'] ?>"></td>
<td><input class="form-control small-input" name="name" value="<?= htmlspecialchars($emp['name']) ?>" required></td>
<td><input class="form-control small-input" name="phone" value="<?= htmlspecialchars($emp['phone']??'') ?>"></td>
<td><input class="form-control small-input" name="email" value="<?= htmlspecialchars($emp['email']??'') ?>"></td>
<td><input class="form-control small-input" name="position" value="<?= htmlspecialchars($emp['position']??'') ?>"></td>
<td>
<select class="form-select small-input" name="salary_type">
<option value="Фиксированная" <?= $emp['salary_type']==='Фиксированная'?'selected':'' ?>>Фиксированная</option>
<option value="Почасовая" <?= $emp['salary_type']==='Почасовая'?'selected':'' ?>>Почасовая</option>
</select>
</td>
<td><input type="number" step="0.01" class="form-control small-input" name="hourly_rate" value="<?= !is_null($emp['hourly_rate']) ? number_format($emp['hourly_rate'],2,'.','') : '' ?>"></td>
<td><input type="number" step="0.01" class="form-control small-input" name="fixed_salary" value="<?= !is_null($emp['fixed_salary']) ? number_format($emp['fixed_salary'],2,'.','') : '' ?>"></td>
<td class="action-buttons">
<button type="submit" name="save_employee" class="btn btn-sm btn-primary">Сохранить</button>
<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?= $emp['id'] ?>">Удалить</button>
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
<p id="footer">Заведение "Dragon" | Заведение "Дракон" , Дзержинск, 2024</p>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="ScriptPages.js"></script>
<script>
// Удаление сотрудника
$(document).on('click','.delete-btn',function(){
    const id=$(this).data('id');
    if(confirm('Вы уверены, что хотите удалить этого сотрудника?')){
        const form=$('<form method="POST"></form>');
        form.append('<input type="hidden" name="delete_employee" value="1">');
        form.append('<input type="hidden" name="employee_id" value="'+id+'">');
        $('body').append(form);
        form.submit();
    }
});
</script>
</body>
</html>