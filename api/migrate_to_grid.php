<?php
require_once 'db.php';

try {
    // 1. Получаем все старые записи, сгруппированные по ключевым признакам
    $sql = "SELECT 
                s.track_id, 
                t.playlist_code, -- Предположим, код хранится тут
                DATE_FORMAT(s.air_date, '%Y-%m') as month,
                s.block_num,
                s.language,
                s.vocal,
                GROUP_CONCAT(DAY(s.air_date)) as days
            FROM ads_scheduling s
            JOIN audio_tracks t ON s.track_id = t.id
            GROUP BY s.track_id, month, s.block_num, s.language, s.vocal";

    $stmt = $pdo->query($sql);
    $oldData = $stmt->fetchAll();

    $pdo->beginTransaction();

    foreach ($oldData as $row) {
        // Создаем пустую маску из 31 нуля
        $mask = str_repeat('0', 31);
        $days = explode(',', $row['days']);
        
        // Расставляем единицы на нужные дни
        foreach ($days as $day) {
            $idx = (int)$day - 1;
            if ($idx >= 0 && $idx < 31) {
                $mask[$idx] = '1';
            }
        }

        // Код ролика (золотой стандарт)
        $code = $row['playlist_code'] ?: 'PL-' . $row['track_id'];

        // Вставляем в новую таблицу
        $insertSql = "INSERT INTO ads_schedule_grid 
                      (track_code, track_id, month, block_num, language, vocal, days_mask) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($insertSql)->execute([
            $code,
            $row['track_id'],
            $row['month'],
            $row['block_num'],
            $row['language'],
            $row['vocal'],
            $mask
        ]);
    }

    $pdo->commit();
    echo "Миграция завершена! Схлопнуто строк: " . count($oldData);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Ошибка: " . $e->getMessage());
}