<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $month = $_GET['month'] ?? date('Y-m');
    $day   = (int)($_GET['day'] ?? 0);

    if ($day < 1 || $day > 31) throw new Exception('Неверный день');

    $sql = "
        SELECT 
            g.track_code as code,
            t.title AS track_name,
            t.duration,
            g.block_num,
            LOWER(TRIM(g.language)) as lang,
            g.vocal
        FROM ads_schedule_grid g
        JOIN audio_tracks t ON g.track_id = t.id
        WHERE g.month = :month 
          AND SUBSTRING(g.days_mask, :day, 1) = '1'
        ORDER BY g.block_num ASC, g.language DESC, t.title ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['month' => $month, 'day' => $day]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    foreach ($rows as $r) {
        // Ключ будет например "1_русский"
        $key = $r['block_num'] . '_' . $r['lang'];
        if (!isset($groups[$key])) {
            $groups[$key] = ['items' => [], 'total_sec' => 0];
        }
        $groups[$key]['items'][] = $r;

        if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $r['duration'], $m)) {
            $groups[$key]['total_sec'] += ($m[1]*3600 + $m[2]*60 + $m[3]);
        }
    }

    // Форматируем время в ММ:СС для JS
    foreach ($groups as &$g) {
        $g['total_formatted'] = sprintf('%02d:%02d', ($g['total_sec']/60), ($g['total_sec']%60));
    }

    echo json_encode($groups);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}