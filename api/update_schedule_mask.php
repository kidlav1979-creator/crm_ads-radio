<?php
/**
 * API для обновления маски дней (0/1) в сетке
 */
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['schedule_id']) || !isset($data['days_mask'])) {
        throw new Exception("Недостаточно данных для обновления");
    }

    $sql = "UPDATE ads_schedule_grid SET days_mask = :mask WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'mask' => $data['days_mask'],
        'id'   => (int)$data['schedule_id']
    ]);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}