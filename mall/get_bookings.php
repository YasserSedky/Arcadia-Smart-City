<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';

header('Content-Type: application/json');

$venue_id = isset($_GET['venue_id']) ? (int)$_GET['venue_id'] : 0;
$date = $_GET['date'] ?? date('Y-m-d');

try {
    $pdo = Database::getInstance();

    $stmt = $pdo->prepare("
        SELECT TIME_FORMAT(starts_at, '%H:%i') as start_time,
               TIME_FORMAT(ends_at, '%H:%i') as end_time
        FROM mall_bookings 
        WHERE venue_id = ? 
        AND DATE(starts_at) = ? 
        AND status = 'scheduled'
        ORDER BY starts_at
    ");
    $stmt->execute([$venue_id, $date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($bookings);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
