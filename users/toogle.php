<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    UPDATE users
    SET is_active = NOT is_active
    WHERE id = ?
");

$stmt->execute([$id]);

logActivity($pdo, 'toggle_active', 'user', $id, 'Изменен статус активности пользователя');

redirect('users/index.php');
