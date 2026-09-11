<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/../includes/equipment_service.php';

requireAdmin();
requirePostRequest();
verifyCsrfToken();

try {
    $id = inputPositiveInt($_GET, 'id');
    $condition = inputEnum($_POST, 'condition_status', ['good', 'broken', 'repair']);
    $notes = inputString($_POST, 'return_notes', 500, false);
    returnEquipment($pdo, $id, $condition, $notes);
    redirect('equipment/view.php?id=' . $id);
} catch (InvalidArgumentException|DomainException $error) {
    http_response_code(409);
    exit(e($error->getMessage()));
} catch (Throwable $error) {
    publicError($error);
}
