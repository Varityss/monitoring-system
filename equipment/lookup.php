<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

header('Content-Type: application/json; charset=utf-8');

$code = trim($_GET['code'] ?? '');

if ($code === '') {
    echo json_encode([
        'ok' => false,
        'message' => 'Код не указан'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$scanCandidates = equipmentScanCandidates($code);
$scanPlaceholders = implode(', ', array_fill(0, count($scanCandidates), '?'));

$stmt = $pdo->prepare("
    SELECT id, inventory_number, serial_number, barcode, model, cabinet, status
    FROM equipment
    WHERE archived = 0
    AND (
        qr_token IN ($scanPlaceholders)
        OR barcode IN ($scanPlaceholders)
        OR inventory_number IN ($scanPlaceholders)
        OR serial_number IN ($scanPlaceholders)
    )
    LIMIT 1
");

$stmt->execute(array_merge(
    $scanCandidates,
    $scanCandidates,
    $scanCandidates,
    $scanCandidates
));

$equipment = $stmt->fetch();

if (!$equipment) {
    echo json_encode([
        'ok' => false,
        'message' => 'Оборудование с таким кодом не найдено'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($equipment['status'] !== 'available') {
    echo json_encode([
        'ok' => false,
        'message' => 'Оборудование найдено, но недоступно для выдачи: ' . equipmentStatusLabel($equipment['status'])
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'equipment' => [
        'id' => (int)$equipment['id'],
        'inventory_number' => $equipment['inventory_number'],
        'serial_number' => $equipment['serial_number'],
        'barcode' => $equipment['barcode'],
        'model' => $equipment['model'],
        'cabinet' => $equipment['cabinet'],
        'status' => $equipment['status']
    ]
], JSON_UNESCAPED_UNICODE);
