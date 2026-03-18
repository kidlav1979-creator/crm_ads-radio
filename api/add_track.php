<?php
/**
 * api/add_track.php
 * Добавление ролика с защитой от дубликатов и валидацией
 */
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

// Отключаем вывод ошибок, чтобы не ломать JSON
ini_set('display_errors', 0);

$input = json_decode(file_get_contents('php://input'), true);

// 1. Базовая проверка на пустоту
if (empty($input['title']) || empty($input['duration'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Название и длительность обязательны']);
    exit;
}

$title = trim($input['title']);
$code = trim($input['code'] ?? '');
$duration = trim($input['duration']);
$language = $input['language'] ?? 'русский';

try {
    // 2. Защита: Проверка на дубликат названия
    $checkTitle = $pdo->prepare("SELECT id FROM audio_tracks WHERE title = ?");
    $checkTitle->execute([$title]);
    if ($checkTitle->fetch()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ролик с таким названием уже существует']);
        exit;
    }

    // 3. Защита: Проверка формата и дубликата кода (PL-XXXXX)
    if (!empty($code)) {
        // Проверка формата: PL- и ровно 5 цифр
        if (!preg_match('/^PL-\d{5}$/', $code)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Код должен быть в формате PL-12345 (PL- и 5 цифр)']);
            exit;
        }

        $checkCode = $pdo->prepare("SELECT id FROM audio_tracks WHERE code = ?");
        $checkCode->execute([$code]);
        if ($checkCode->fetch()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Этот код (PL) уже присвоен другому ролику']);
            exit;
        }
    }

    // 4. Защита: Валидация формата времени (ЧЧ:ММ:СС)
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Неверный формат времени. Используйте ЧЧ:ММ:СС']);
        exit;
    }

    // 5. Запись в базу
    $sql = "INSERT INTO audio_tracks (code, title, language, duration, created_at, updated_at) 
            VALUES (:code, :title, :language, :duration, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':code'     => $code,
        ':title'    => $title,
        ':language' => $language,
        ':duration' => $duration
    ]);

    echo json_encode([
        'status' => 'success', 
        'id' => $pdo->lastInsertId(),
        'message' => 'Ролик успешно добавлен'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка БД: ' . $e->getMessage()]);
}