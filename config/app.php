<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectDir = '/' . basename(BASE_PATH);
    $baseUrl = '';

    if ($scriptName === $projectDir . '/index.php' || strpos($scriptName, $projectDir . '/') === 0) {
        $baseUrl = $projectDir;
    }

    define('BASE_URL', $baseUrl);
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Asia/Almaty');
}

date_default_timezone_set(APP_TIMEZONE);

ini_set('display_errors', '0');
