<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

if (!isset($_GET['id'])) {

    redirect('users/index.php');

}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    UPDATE users
    SET is_active = 1
    WHERE id = ?
");

$stmt->execute([$id]);

logActivity($pdo, 'activate', 'user', $id, 'Пользователь активирован');

redirect('users/index.php');
