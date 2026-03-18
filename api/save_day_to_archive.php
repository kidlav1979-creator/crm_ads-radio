<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $date = $input['date'] ?? null;
    $groups = $input['groups'] ?? null;

    if (!$date || !$groups) {
        throw new Exception('Некорректные данные: отсутствует дата или контент плейлиста');
    }

    // 1. Сохраняем структуру (вместе с вашим ручным порядком) в базу данных
    $jsonData = json_encode($groups, JSON_UNESCAPED_UNICODE);
    $sql = "INSERT INTO playlists_archive (archive_date, playlist_data) 
            VALUES (:date, :data_insert) 
            ON DUPLICATE KEY UPDATE playlist_data = :data_update, created_at = NOW()";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'date'        => $date,
        'data_insert' => $jsonData,
        'data_update' => $jsonData
    ]);

    // 2. ГЕНЕРАЦИЯ КОНТЕНТА ДЛЯ JAZLER
    $timeMap = [
        '1_русский'   => ['07:30', '09:30', '11:30', '13:30', '15:30', '17:30', '19:30', '21:30', '23:30'],
        '1_казахский' => ['07:15', '09:15', '11:15', '13:15', '15:15', '17:15', '19:15', '21:15', '23:15'],
        '2_русский'   => ['06:30', '08:30', '10:30', '12:30', '14:30', '16:30', '18:30', '20:30', '22:30'],
        '2_казахский' => ['06:15', '08:15', '10:15', '12:15', '14:15', '16:15', '18:15', '20:15', '22:15']
    ];

    // Жестко задаем очередность выгрузки языковых блоков в txt-файл
    $blockOrder = ['1_русский', '1_казахский', '2_русский', '2_казахский'];

    $playlistContent = "";

    // Проходим строго по нужному порядку блоков
    foreach ($blockOrder as $key) {
        // Если в этом блоке есть перетащенные ролики
        if (!empty($groups[$key]['items'])) {
            $times = $timeMap[$key];
            
            // Проходим по роликам в том самом порядке, как они стоят на экране
            foreach ($groups[$key]['items'] as $item) {
                // И для каждого ролика выписываем все его таймкоды подряд
                foreach ($times as $time) {
                    $playlistContent .= "{$time},{$item['code']}, {$item['track_name']}\r\n";
                }
            }
        }
    }

    $filename = str_replace('-', '', $date) . ".txt";

    echo json_encode([
        'status' => 'success',
        'filename' => $filename,
        'content' => $playlistContent
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}