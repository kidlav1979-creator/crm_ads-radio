<?php
// Отключаем вывод HTML-ошибок, чтобы не ломать JSON
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    // Подключаем базу (путь относительно api/)
    require_once __DIR__ . '/../db.php';
    
    // Получаем данные
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
        if (isset($input['dates']) && is_string($input['dates'])) {
            $input['dates'] = json_decode($input['dates'], true);
        }
    }

    $track_id = $input['track_id'] ?? null;
    $dates    = $input['dates'] ?? [];
    $block    = (int)($input['block_num'] ?? 1);
    $lang     = $input['language'] ?? 'русский';
    $vocal    = $input['vocal'] ?? 'мужской';

    if (!$track_id || empty($dates)) {
        echo json_encode(['status' => 'error', 'message' => 'Не выбраны ролики или даты']);
        exit;
    }

    // 1. Формируем месяц (гггг-мм) и битовую маску (31 символ)
    $month = date('Y-m', strtotime($dates[0]));
    $mask = str_repeat('0', 31);
    
    foreach ($dates as $dateStr) {
        $d = (int)date('j', strtotime($dateStr));
        if ($d >= 1 && $d <= 31) {
            $mask[$d-1] = '1';
        }
    }

    // 2. Получаем track_code из базы
    $trackCode = 'PL-' . $track_id; 
    $st = $pdo->prepare("SELECT code FROM audio_tracks WHERE id = ?");
    $st->execute([$track_id]);
    $res = $st->fetchColumn();
    if ($res) { $trackCode = $res; }

    // 3. Сохранение в базу
    // ВНИМАНИЕ: Здесь важно, чтобы количество ключей в execute совпало с :метками в SQL
    $sql = "INSERT INTO ads_schedule_grid 
            (track_code, track_id, month, block_num, language, vocal, days_mask) 
            VALUES (:code, :tid, :month, :block, :lang, :vocal, :mask)
            ON DUPLICATE KEY UPDATE days_mask = :mask_update";
            
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        'code'         => $trackCode,
        'tid'          => $track_id,
        'month'        => $month,
        'block'        => $block,
        'lang'         => $lang,
        'vocal'        => $vocal,
        'mask'         => $mask,          // Для INSERT
        'mask_update'  => $mask           // Для UPDATE (та самая недостающая метка)
    ]);

    echo json_encode(['status' => 'success', 'message' => 'График успешно обновлен']);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Ошибка БД: ' . $e->getMessage()
    ]);
}