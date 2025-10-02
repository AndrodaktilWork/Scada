<?php
// db_connection.php — SolarMonitoringSystem @ 127.0.0.1
$DB_HOST    = '127.0.0.1';              // или 'localhost'
$DB_NAME    = 'SolarMonitoringSystem';
$DB_USER    = 'root';
$DB_PASS    = '456123Abv!';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // хвърля изключения
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // масиви с ключове
    PDO::ATTR_EMULATE_PREPARES   => false,                  // истински prepared statements
    // По желание: фиксирай часова зона, ако ти трябва
    // PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+02:00'",
];

try {
    $conn = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Не показвай детайлни грешки в продукция
    http_response_code(500);
    echo "Връзката с базата данни не можа да бъде осъществена.";
    // Логни подробностите (по избор):
    // error_log('DB connect failed: ' . $e->getMessage());
    exit;
}
