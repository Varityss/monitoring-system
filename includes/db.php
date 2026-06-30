<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$config = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'monitoring_system',
    'user' => 'root',
    'password' => '',
];

$configFile = BASE_PATH . '/config/database.php';

if (is_file($configFile)) {
    $customConfig = require $configFile;

    if (is_array($customConfig)) {
        $config = array_merge($config, $customConfig);
    }
}

$host = trim((string)$config['host']);
$port = trim((string)($config['port'] ?? ''));

if ($port === '' && preg_match('/^([^:]+):(\d+)$/', $host, $matches)) {
    $host = $matches[1];
    $port = $matches[2];
}

$dsn = "mysql:host={$host};dbname={$config['dbname']};charset=utf8mb4";

if ($port !== '') {
    $dsn .= ";port={$port}";
}

try {

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['password']
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    http_response_code(500);
    die('Database connection error');

}
