<?php
/**
 * API для переключения конкретного дня в маске
 */
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['schedule_id'], $data['day'], $data['status'])) {
        throw new Exception("Недостаточно данных для обновления");
    }

    $id = (int)$data['schedule_id'];
    $dayIndex = (int)$data['day'] - 1; // Индекс в строке (0-30)
    $newStatus = $data['status'] ? '1' : '0';

    // SQL-запрос использует функцию INSERT() для замены ОДНОГО символа в строке по позиции
    // INSERT(str, pos, len, newstr)
    $sql = "UPDATE ads_schedule_grid 
            SET days_mask = INSERT(days_mask, :pos, 1, :val) 
            WHERE id = :id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'pos' => $dayIndex + 1, // В MySQL позиция начинается с 1
        'val' => $newStatus,
        'id'  => $id
    ]);

    echo json_encode(['status' => 'success', 'new_status' => $newStatus]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}