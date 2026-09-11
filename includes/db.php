<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$config = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'monitoring_system',
    'user' => 'root',
    'password' => '',
    'debug' => false,
];

$configFile = BASE_PATH . '/config/database.php';

if (is_file($configFile)) {
    $customConfig = require $configFile;

    if (is_array($customConfig)) {
        $config = array_merge($config, $customConfig);
    }
}

$environmentConfig = [
    'host' => getenv('APP_DB_HOST'),
    'port' => getenv('APP_DB_PORT'),
    'dbname' => getenv('APP_DB_NAME'),
    'user' => getenv('APP_DB_USER'),
    'password' => getenv('APP_DB_PASSWORD'),
    'debug' => getenv('APP_DEBUG'),
];
foreach ($environmentConfig as $key => $value) {
    if ($value !== false && ($value !== '' || $key === 'password')) {
        $config[$key] = $key === 'debug'
            ? filter_var($value, FILTER_VALIDATE_BOOL)
            : $value;
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
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {

    error_log('Database connection error: ' . $e->getMessage());

    http_response_code(500);

    if (!empty($config['debug'])) {
        die('Database connection error: ' . e($e->getMessage()));
    }

    die('Database connection error');

}
