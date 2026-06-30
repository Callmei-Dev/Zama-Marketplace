<?php
// includes/db.php
declare(strict_types=1);

$DB_HOST = 'sql107.infinityfree.com';
$DB_NAME = 'if0_42072524_zama_marketplace';
$DB_USER = 'if0_42072524';
$DB_PASS = 'nz3AMOoQ0xqwder'; 
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

