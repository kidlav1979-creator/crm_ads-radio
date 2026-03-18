<?php
require_once __DIR__ . '/../auth.php';
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id = (int)($input['id'] ?? 0);

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID не указан']);
    exit;
}

try {
    // 1. Проверка: сколько раз ролик встречается в расписании
    $stmtUsage = $pdo->prepare("SELECT COUNT(*) FROM ads_schedule_grid WHERE track_id = ?");
    $stmtUsage->execute([$id]);
    $usageCount = $stmtUsage->fetchColumn();

    // Действие: Проверка зависимостей
    if ($action === 'check') {
        echo json_encode(['status' => 'success', 'count' => $usageCount]);
        exit;
    }

    // Действие: Удаление
    if ($action === 'delete') {
        if ($usageCount > 0) {
            echo json_encode(['status' => 'error', 'message' => "Нельзя удалить! Ролик стоит в эфире ($usageCount раз)."]);
        } else {
            $pdo->prepare("DELETE FROM audio_tracks WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Ролик удален']);
        }
        exit;
    }

    // Действие: Обновление
    if ($action === 'update') {
        // 1. Проверка формата времени
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $input['duration'])) {
            echo json_encode(['status' => 'error', 'message' => 'Неверный формат времени. Используйте ЧЧ:ММ:СС']);
            exit;
        }

        // 2. Проверка: не занято ли новое название другим роликом
        $checkTitle = $pdo->prepare("SELECT id FROM audio_tracks WHERE title = ? AND id != ?");
        $checkTitle->execute([trim($input['title']), $id]);
        if ($checkTitle->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Ролик с таким названием уже существует']);
            exit;
        }

        // 3. Проверка формата и уникальности кода
        if (!empty($input['code'])) {
            $formattedCode = trim($input['code']);
            if (!preg_match('/^PL-\d{5}$/', $formattedCode)) {
                echo json_encode(['status' => 'error', 'message' => 'Код должен быть в формате PL-12345 (PL- и 5 цифр)']);
                exit;
            }

            $checkCode = $pdo->prepare("SELECT id FROM audio_tracks WHERE code = ? AND id != ?");
            $checkCode->execute([$formattedCode, $id]);
            if ($checkCode->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Этот код (PL) уже присвоен другому ролику']);
                exit;
            }
        }

        // 4. Защита: если ролик в эфире, нельзя менять длительность
        if ($usageCount > 0) {
            $current = $pdo->prepare("SELECT duration FROM audio_tracks WHERE id = ?");
            $current->execute([$id]);
            if ($current->fetchColumn() !== $input['duration']) {
                echo json_encode(['status' => 'error', 'message' => 'Нельзя менять длительность ролика, который уже в расписании!']);
                exit;
            }
        }

        // 5. Финальное сохранение
        $sql = "UPDATE audio_tracks SET code = ?, title = ?, language = ?, duration = ?, updated_at = NOW() WHERE id = ?";
        $pdo->prepare($sql)->execute([
            trim($input['code']),
            trim($input['title']),
            $input['language'],
            $input['duration'],
            $id
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Данные обновлены']);
        exit; 
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
}