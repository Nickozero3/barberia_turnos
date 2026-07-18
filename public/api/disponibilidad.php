<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$serviceId = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT) ?: 0;
$barberId = filter_input(INPUT_GET, 'barber_id', FILTER_VALIDATE_INT);
$barberId = $barberId === false || $barberId === null ? 0 : $barberId;
$date = trim((string) ($_GET['date'] ?? ''));

if ($serviceId < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(422);
    echo json_encode(['message' => 'Faltan datos para buscar horarios.', 'slots' => []]);
    exit;
}

try {
    echo json_encode(['slots' => available_slots(db(), $serviceId, $barberId, $date)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['message' => 'No se pudo consultar la disponibilidad.', 'slots' => []]);
}
