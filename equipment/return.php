<?php

session_start();
require '../includes/auth.php';

requireAdmin();
require '../includes/db.php';



$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM equipment
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$equipment = $stmt->fetch();

if (!$equipment) {

    redirect('equipment/index.php');

}

$issue = $pdo->prepare("
    SELECT *
    FROM equipment_issues

    WHERE equipment_id = ?
    AND status = 'issued'

    ORDER BY id DESC
    LIMIT 1
");

$issue->execute([$id]);

$activeIssue = $issue->fetch();

if (!$activeIssue) {

    die('Активная выдача не найдена');

}

$closeIssue = $pdo->prepare("
    UPDATE equipment_issues

    SET

        status = 'returned',
        returned_at = NOW()

    WHERE id = ?
");

$closeIssue->execute([

    $activeIssue['id']

]);

$updateEquipment = $pdo->prepare("
    UPDATE equipment

    SET status = 'available'

    WHERE id = ?
");

$updateEquipment->execute([$id]);

logActivity($pdo, 'return', 'equipment', $id, 'Оборудование возвращено: ' . $equipment['inventory_number']);

redirect('equipment/view.php?id=' . $id);
