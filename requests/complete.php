<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();
requirePostRequest();
verifyCsrfToken();

$requestId = (int) $_GET['id'];

$requestStmt = $pdo->prepare("
    SELECT *
    FROM requests
    WHERE id = ?
    LIMIT 1
");

$requestStmt->execute([$requestId]);

$request = $requestStmt->fetch();

if (!$request) {
    die('Request not found');
}

if (!in_array($request['status'], ['approved', 'issued', 'overdue'], true)) {
    die('Request is not active');
}

$equipmentStmt = $pdo->prepare("
    SELECT equipment_id
    FROM request_equipment
    WHERE request_id = ?
    AND status = 'issued'
");

$equipmentStmt->execute([$requestId]);

$equipmentList = $equipmentStmt->fetchAll();

try {
    $pdo->beginTransaction();

    foreach ($equipmentList as $equipment) {
        $equipmentId = (int)$equipment['equipment_id'];

        $updateEquipment = $pdo->prepare("
            UPDATE equipment
            SET status = 'available'
            WHERE id = ?
        ");

        $updateEquipment->execute([$equipmentId]);

        $issueStmt = $pdo->prepare("
            UPDATE equipment_issues
            SET
                status = 'returned',
                returned_at = NOW()
            WHERE equipment_id = ?
            AND request_id = ?
            AND status = 'issued'
        ");

        $issueStmt->execute([
            $equipmentId,
            $requestId
        ]);

        $relationStmt = $pdo->prepare("
            UPDATE request_equipment
            SET
                status = 'returned',
                returned_at = NOW()
            WHERE request_id = ?
            AND equipment_id = ?
            AND status = 'issued'
        ");

        $relationStmt->execute([
            $requestId,
            $equipmentId
        ]);
    }

    $completeStmt = $pdo->prepare("
        UPDATE requests
        SET status = 'returned'
        WHERE id = ?
    ");

    $completeStmt->execute([$requestId]);

    logActivity($pdo, 'return', 'request', $requestId, 'Request returned');

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    die($e->getMessage());
}

redirect('requests/view.php?id=' . $requestId);
