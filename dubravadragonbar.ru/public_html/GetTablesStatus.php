<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'o91012r4_bb';
$username = 'o91012r4_bb';
$password = 'EkaterinaDiplomDragon1.';

//получение даты из GET
$dateInput = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
    $date = date('Y-m-d');
} else {
    $date = $dateInput;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //список столов
    $tables = $pdo->query(
        "SELECT table_number, seats FROM tables"
    )->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($tables as $table) {
        $stmt = $pdo->prepare(
            "SELECT time_from, time_to
             FROM bookings
             WHERE table_number = :table
               AND visit_date = :date
             ORDER BY time_from"
        );

        $stmt->execute([
            ':table' => $table['table_number'],
            ':date' => $date
        ]);

        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //время из бд в чч:мм
        $formattedBookings = [];
        foreach ($bookings as $booking) {
            $formattedBookings[] = [
                'time_from' => substr($booking['time_from'], 0, 5), 
                'time_to' => substr($booking['time_to'], 0, 5) 
            ];
        }

        $result[] = [
            'table_number' => $table['table_number'],
            'seats' => $table['seats'],
            'bookings' => $formattedBookings
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $errorResponse = [
        'error' => true,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ];
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
}
?>
