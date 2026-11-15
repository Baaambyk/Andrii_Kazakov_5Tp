<?php
require __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$roomId = (int) ($_GET['room'] ?? 0);
$date = (string) ($_GET['date'] ?? '');
$exclude = isset($_GET['exclude']) ? (int) $_GET['exclude'] : null;

$parsedDate = DateTime::createFromFormat('Y-m-d', $date);

if ($roomId <= 0 || $parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Nieprawidłowe parametry zapytania.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $rooms->find($roomId);

if ($room === null || !(int) $room['is_active']) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Sala nie istnieje lub jest nieaktywna.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($date < date('Y-m-d')) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Nie można rezerwować terminów z przeszłości.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok'    => true,
    'date'  => $date,
    'open'  => '08:00',
    'close' => '22:00',
    'room'  => [
        'id'       => (int) $room['id'],
        'name'     => $room['name'],
        'capacity' => (int) $room['capacity'],
        'price'    => (float) $room['price_per_hour'],
    ],
    'busy'  => $rooms->busySlots($roomId, $date, $exclude),
], JSON_UNESCAPED_UNICODE);
