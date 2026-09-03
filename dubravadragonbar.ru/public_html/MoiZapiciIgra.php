<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
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

// Получаем регистрации пользователя
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT id, team_name, people_count, phone_number, game_date, order_notes, serving_time, Status, created_at
    FROM game_registrations
    WHERE user_id = ?
    ORDER BY game_date DESC, serving_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dragon | Мои регистрации на игры</title>
    <link rel="stylesheet" href="StyleMoiZapiciIgra.css">
    <link rel="stylesheet" href="StyleHeaderFooter.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="img/лого.png">
    <style>
    .status-badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: none;
}

.status-confirmed {
    background-color: #d4edda; 
    color: #155724; 
    border: 1px solid #c3e6cb; 
}

.status-cancelled {
    background-color: #f8d7da; 
    color: #721c24; 
    border: 1px solid #f5c6cb; 
}

.status-other {
    background-color: #fff3cd; 
    color: #856404; 
    border: 1px solid #ffeaa7; 
}
    </style>
</head>
<body>
    <header>
        <div class="icon">
            <a id="PageGlavnay" href="Glavnay.html">
                <img class="logo" src="img/лого.png" alt="логотип">
            </a>
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

    <main>
        <div class="container mt-4">
            <h2>Мои регистрации на игры</h2>

            <?php if (empty($registrations)): ?>
                <div class="alert alert-info">У вас пока нет регистраций.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Название команды</th>
                            <th>Кол-во человек</th>
                            <th>Телефон</th>
                            <th>Дата игры</th>
                            <th>Время подачи</th>
                            <th>Заказ</th>
                            <th>Статус</th>
                            <th>Создано</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php foreach ($registrations as $index => $reg): ?>
                    <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($reg['team_name']) ?></td>
                <td><?= htmlspecialchars($reg['people_count']) ?></td>
                <td><?= htmlspecialchars($reg['phone_number']) ?></td>
                <td><?= htmlspecialchars($reg['game_date']) ?></td>
                <td><?= htmlspecialchars($reg['serving_time']) ?></td>
                <td><?= htmlspecialchars($reg['order_notes']) ?></td>
                <td>
    <span class="status-badge <?=
        $reg['Status'] === 'Подтверждено' ? 'status-confirmed' :
        ($reg['Status'] === 'Не подтверждено' || $reg['Status'] === 'Отмена' ? 'status-cancelled' : 'status-other')
    ?>">
        <?= htmlspecialchars($reg['Status']) ?>
    </span>
</td>
                <td><?= htmlspecialchars($reg['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
</main>

<footer>
    <p id="footer">Заведение "Dragon" | Заведение "Дракон" в Дзержинске, 2024</p>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="ScriptPages.js"></script>
<script src="ScriptMoiZapici.js"></script>
</body>
</html>
