<?php

session_start();
require '../includes/auth.php';

requireAdmin();
require '../includes/db.php';

requirePostRequest();
verifyCsrfToken();


$id = (int) $_GET['id'];

$requestStmt = $pdo->prepare("
    SELECT status
    FROM requests
    WHERE id = ?
    LIMIT 1
");

$requestStmt->execute([$id]);

$request = $requestStmt->fetch();

if (!$request) {
    die('Р—Р°СЏРІРєР° РЅРµ РЅР°Р№РґРµРЅР°');
}

if ($request['status'] !== 'pending') {
    die('Р—Р°СЏРІРєР° СѓР¶Рµ РѕР±СЂР°Р±РѕС‚Р°РЅР°');
}

$stmt = $pdo->prepare("
    UPDATE requests

    SET status = 'rejected'

    WHERE id = ?
");

$stmt->execute([$id]);

logActivity($pdo, 'reject', 'request', $id, 'Заявка отклонена');

redirect('requests/index.php');
