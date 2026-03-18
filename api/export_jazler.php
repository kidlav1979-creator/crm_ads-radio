<?php
require_once '../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'preview';
$date = $data['date'] ?? null;

try {
    // 1. Получение истории экспортов (последние 10)
    if ($action === 'get_history') {
        $stmt = $pdo->query("SELECT id, filename, export_date, created_at FROM export_history ORDER BY created_at DESC LIMIT 10");
        echo json_encode(['status' => 'success', 'history' => $stmt->fetchAll()]);
        exit;
    }

    // 2. Скачивание уже существующего файла из истории
    if ($action === 'download_existing') {
        $stmt = $pdo->prepare("SELECT filename, content FROM export_history WHERE id = ?");
        $stmt->execute([$data['id']]);
        $file = $stmt->fetch();
        if (!$file) throw new Exception('Файл не найден в базе');
        echo json_encode(['status' => 'success', 'filename' => $file['filename'], 'content' => $file['content']]);
        exit;
    }

    // 3. Генерация контента (для Предпросмотра или Экспорта)
    if (!$date) throw new Exception('Дата не выбрана');

    // Сетка вещания согласно ТЗ
    $timeMap = [
        '1' => [
            'русский'   => ['07:30', '09:30', '11:30', '13:30', '15:30', '17:30', '19:30', '21:30', '23:30'],
            'казахский' => ['07:15', '09:15', '11:15', '13:15', '15:15', '17:15', '19:15', '21:15', '23:15']
        ],
        '2' => [
            'русский'   => ['06:30', '08:30', '10:30', '12:30', '14:30', '16:30', '18:30', '20:30', '22:30'],
            'казахский' => ['06:15', '08:15', '10:15', '12:15', '14:15', '16:15', '18:15', '20:15', '22:15']
        ]
    ];

    // Выборка из архива
    $stmt = $pdo->prepare("SELECT block_num, language, code, title FROM playlists_archive WHERE archive_date = ? ORDER BY block_num, language, id");
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) throw new Exception('В архиве нет записей на эту дату');

    $outputLines = [];
    foreach ($rows as $row) {
        $block = $row['block_num'];
        $lang = $row['language'];
        
        if (isset($timeMap[$block][$lang])) {
            foreach ($timeMap[$block][$lang] as $time) {
                // Формат Jazler: время,код,название
                $outputLines[] = "{$time},{$row['code']},{$row['title']}";
            }
        }
    }

    $fileContent = implode("\r\n", $outputLines);
    $fileName = str_replace('-', '', $date) . ".txt"; // Формат ггггммдд.txt

    // СОХРАНЯЕМ В БАЗУ ТОЛЬКО ПРИ ЭКСПОРТЕ
    if ($action === 'export') {
        $ins = $pdo->prepare("INSERT INTO export_history (filename, export_date, content) VALUES (?, ?, ?)");
        $ins->execute([$fileName, $date, $fileContent]);
    }

    echo json_encode([
        'status' => 'success',
        'filename' => $fileName,
        'content' => $fileContent,
        'is_preview' => ($action === 'preview')
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}