<?php
/**
 * API для удаления строки графика
 */
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['schedule_id'])) {
        throw new Exception("ID записи не передан");
    }

    // Удаляем одну строку из новой таблицы по первичному ключу
    $sql = "DELETE FROM ads_schedule_grid WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => (int)$data['schedule_id']]);

    echo json_encode([
        'status' => 'success', 
        'message' => 'График ролика полностью удален',
        'count' => $stmt->rowCount()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}