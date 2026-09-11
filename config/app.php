<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$runtimeConfig = [];
$runtimeFile = BASE_PATH . '/config/runtime.php';
if (is_file($runtimeFile)) {
    $loadedRuntime = require $runtimeFile;
    if (is_array($loadedRuntime)) {
        $runtimeConfig = $loadedRuntime;
    }
}

if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectDir = '/' . basename(BASE_PATH);
    $baseUrl = '';

    if ($scriptName === $projectDir . '/index.php' || strpos($scriptName, $projectDir . '/') === 0) {
        $baseUrl = $projectDir;
    }

    define('BASE_URL', rtrim((string)($runtimeConfig['base_url'] ?? $baseUrl), '/'));
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', (string)($runtimeConfig['timezone'] ?? 'Asia/Oral'));
}

if (!defined('APP_URL')) {
    define('APP_URL', rtrim((string)($runtimeConfig['app_url'] ?? getenv('APP_URL') ?: ''), '/'));
}

if (!defined('DOCUMENT_STORAGE_PATH')) {
    $configuredDocumentPath = getenv('DOCUMENT_STORAGE_PATH');
    $documentPath = $runtimeConfig['document_storage_path']
        ?? ($configuredDocumentPath !== false && $configuredDocumentPath !== '' ? $configuredDocumentPath : BASE_PATH . '/storage/documents');
    define('DOCUMENT_STORAGE_PATH', rtrim((string)$documentPath, '/\\'));
}

if (!defined('SESSION_IDLE_TIMEOUT')) {
    define('SESSION_IDLE_TIMEOUT', 30 * 60);
}

if (!defined('SESSION_ABSOLUTE_TIMEOUT')) {
    define('SESSION_ABSOLUTE_TIMEOUT', 12 * 60 * 60);
}

date_default_timezone_set(APP_TIMEZONE);

ini_set('display_errors', '0');
