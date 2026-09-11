<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/includes/db.php';

if ($argc !== 4 || $argv[1] !== '--create') {
    exit("Usage: php create_admin.php --create <login> <full-name>\nPassword is read from standard input.\n");
}

$login = trim($argv[2]);
$fullName = trim($argv[3]);
if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $login) || $fullName === '' || mb_strlen($fullName) > 100) {
    exit("Invalid login or full name.\n");
}

if ((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn() > 0) {
    exit("An active administrator already exists.\n");
}

fwrite(STDOUT, 'Password: ');
$password = rtrim((string)fgets(STDIN), "\r\n");
if (mb_strlen($password) < 12) {
    exit("Password must contain at least 12 characters.\n");
}

$stmt = $pdo->prepare("INSERT INTO users (login, full_name, password, role, is_active, password_changed_at) VALUES (?, ?, ?, 'admin', 1, NOW())");
$stmt->execute([$login, $fullName, password_hash($password, PASSWORD_DEFAULT)]);
echo "Administrator created.\n";
