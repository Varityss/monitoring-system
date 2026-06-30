<?php

session_start();
require '../includes/auth.php';

requireAdmin();

require '../includes/db.php';



$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    UPDATE equipment
    SET archived = 1
    WHERE id = ?
");

$stmt->execute([$id]);

logActivity($pdo, 'archive', 'equipment', $id, 'Оборудование отправлено в архив');

redirect('equipment/index.php');
