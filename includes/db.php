<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$config = [
    'host' => '127.0.0.1',
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

try {

    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
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
