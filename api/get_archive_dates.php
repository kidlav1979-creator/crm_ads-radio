<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/db.php';
$month = $_GET['month'] . '%';
$stmt = $pdo->prepare("SELECT archive_date FROM playlists_archive WHERE archive_date LIKE ?");
$stmt->execute([$month]);
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));