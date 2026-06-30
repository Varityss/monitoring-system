<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

$id = (int)$_GET['id'];

$newPassword = '12345678';

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE users
    SET password = ?
    WHERE id = ?
");

$stmt->execute([
    $hash,
    $id
]);

logActivity($pdo, 'reset_password', 'user', $id, 'Сброшен пароль пользователя');

redirect('users/index.php');
