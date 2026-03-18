<?php
require_once __DIR__ . '/../auth.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (strlen($query) >= 2) {
        $sql = "SELECT id, code, title, language, duration 
                FROM audio_tracks 
                WHERE title LIKE :tQ OR code LIKE :cQ 
                ORDER BY created_at DESC 
                LIMIT 15";
        $stmt = $pdo->prepare($sql);
        $searchTerm = "%$query%";
        $stmt->execute([
            ':tQ' => $searchTerm,
            ':cQ' => $searchTerm
        ]);
    } else {
        $sql = "SELECT id, code, title, language, duration 
                FROM audio_tracks 
                ORDER BY created_at DESC 
                LIMIT 10";
        $stmt = $pdo->query($sql);
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results ? $results : []);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}