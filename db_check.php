<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');

$configFile = BASE_PATH . '/config/database.php';
$config = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'monitoring_system',
    'user' => 'root',
    'password' => '',
    'debug' => false,
];

echo "Database check\n";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "Config file: " . $configFile . "\n";
echo "Config exists: " . (is_file($configFile) ? 'yes' : 'no') . "\n";

if (is_file($configFile)) {
    $customConfig = require $configFile;

    if (is_array($customConfig)) {
        $config = array_merge($config, $customConfig);
    } else {
        echo "Config error: config/database.php must return array\n";
        exit;
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

echo "PHP version: " . PHP_VERSION . "\n";
echo "PDO MySQL loaded: " . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
echo "Host: " . $host . "\n";
echo "Port: " . ($port ?: '-') . "\n";
echo "Database: " . $config['dbname'] . "\n";
echo "User: " . $config['user'] . "\n";
echo "Password empty: " . ($config['password'] === '' ? 'yes' : 'no') . "\n";
echo "DSN: " . $dsn . "\n";

try {
    $pdo = new PDO($dsn, $config['user'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connection: OK\n";
} catch (Throwable $e) {
    echo "Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
