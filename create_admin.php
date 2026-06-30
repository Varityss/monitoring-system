<?php

require 'includes/db.php';

$login = 'admin';

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE login = ?
");

$stmt->execute([$login]);

if ((int)$stmt->fetchColumn() > 0) {
    echo 'Admin already exists';
    exit;
}

$password = password_hash(
    'admin123',
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare("
    INSERT INTO users (
        login,
        full_name,
        password,
        role,
        is_active
    )
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([

    $login,
    'Administrator',
    $password,
    'admin',
    1

]);

echo 'Admin created';
