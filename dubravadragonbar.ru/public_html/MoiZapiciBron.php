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

// Получаем бронирования пользователя
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT id, client_name, phone, table_number, visit_date, time_from, time_to, comment, Status
    FROM bookings
    WHERE user_id = ?
    ORDER BY visit_date DESC, time_from DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dragon | Мои записи о бронировании</title>
    <link rel="stylesheet" href="StyleMoiZapiciBron.css">
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
            <h2>Мои бронирования</h2>

            <?php if (empty($bookings)): ?>
                <div class="alert alert-info">У вас пока нет бронирований.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Клиент</th>
                            <th>Телефон</th>
                            <th>Стол</th>
                            <th>Дата</th>
                            <th>Время с</th>
                            <th>Время до</th>
                            <th>Статус</th>
                            <th>Комментарий</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $index => $booking): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($booking['client_name']) ?></td>
                                <td><?= htmlspecialchars($booking['phone']) ?></td>
                                <td><?= htmlspecialchars($booking['table_number']) ?></td>
                                <td><?= htmlspecialchars($booking['visit_date']) ?></td>
                                <td><?= htmlspecialchars($booking['time_from']) ?></td>
                                <td><?= htmlspecialchars($booking['time_to']) ?></td>
                                <td>
    <span class="status-badge <?=
        $booking['Status'] === 'Подтверждено' ? 'status-confirmed' :
        ($booking['Status'] === 'Не подтверждено' || $booking['Status'] === 'Отмена' ? 'status-cancelled' : 'status-other')
    ?>">
        <?= htmlspecialchars($booking['Status']) ?>
    </span>
</td>
                <td><?= htmlspecialchars($booking['comment']) ?></td>
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
