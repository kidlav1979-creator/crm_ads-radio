<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';
header('Content-Type: application/json; charset=utf-8');

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

try {
    // Выбираем данные из новой оптимизированной таблицы
    $sql = "SELECT 
                g.id as schedule_id,
                g.track_code,
                t.title as track_name,
                g.block_num,
                g.language,
                g.vocal,
                g.days_mask
            FROM ads_schedule_grid g
            JOIN audio_tracks t ON g.track_id = t.id
            WHERE g.month = :month
            ORDER BY g.block_num ASC, g.language DESC, t.title ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month' => $month]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'tracks' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}