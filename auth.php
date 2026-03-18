<?php
/**
 * Файл конфигурации и авторизации CRM
 * База данных: userl4415_crm_test
 */

session_start();

// 1. НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ
$dbConfig = [
    'host'     => 'localhost',
    'dbname'   => 'userl4415_crm_test', // Тестовая база
    'user'     => 'kidlav1979',         // Твой логин
    'password' => 'tankucji3BA',        // Твой пароль
    'charset'  => 'utf8mb4'
];

// 2. НАСТРОЙКИ ДОСТУПА В CRM (Панель управления)
$admin_user = 'admin'; 
// Хеш пароля (в данном примере пароль: password123)
$admin_password_hash = '$2y$10$8W3Y6uL2kS7.pA8fMvGTe.tU5C6qWnFhMvE6v6R5b4l5d6e7f8g9h'; 

try {
    // Создаем соединение PDO
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $options);

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// 3. ФУНКЦИИ АВТОРИЗАЦИИ

function is_authenticated() {
    return isset($_SESSION['crm_logged_in']) && $_SESSION['crm_logged_in'] === true;
}

function login($username, $password) {
    global $admin_user, $admin_password_hash;
    
    if ($username === $admin_user && password_verify($password, $admin_password_hash)) {
        $_SESSION['crm_logged_in'] = true;
        $_SESSION['user_name'] = $username;
        return true;
    }
    return false;
}

function logout() {
    unset($_SESSION['crm_logged_in']);
    unset($_SESSION['user_name']);
    session_destroy();
    header("Location: index.html");
    exit;
}
?>