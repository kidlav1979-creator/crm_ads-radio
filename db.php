<?php
/**
 * Файл централизованного подключения к базе данных
 */

// 1. НАСТРОЙКИ ПОДКЛЮЧЕНИЯ (взяты из auth.php)
$dbConfig = [
    'host'     => 'localhost',
    'dbname'   => 'userl4415_crm_test', //
    'user'     => 'kidlav1979',         //
    'password' => 'tankucji3BA',        //
    'charset'  => 'utf8mb4'             //
];

try {
    // 2. ИНИЦИАЛИЗАЦИЯ СОЕДИНЕНИЯ PDO
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Включает выброс исключений при ошибках
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Устанавливает формат данных по умолчанию как ассоциативный массив
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Отключает эмуляцию подготовленных выражений для безопасности
    ];
    
    // Создаем глобальный объект подключения $pdo
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $options); //

} catch (PDOException $e) {
    // В случае ошибки выводим сообщение (в рабочих API лучше логировать в файл)
    die("Ошибка подключения к базе данных: " . $e->getMessage()); //
}