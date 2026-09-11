<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/../includes/equipment_service.php';

requireAdmin();
requirePostRequest();
verifyCsrfToken();

try {
    $requestId = inputPositiveInt($_GET, 'id');
    $notes = inputString($_POST, 'return_notes', 500, true);
    returnRequestEquipment($pdo, $requestId, $notes);
    redirect('requests/view.php?id=' . $requestId);
} catch (InvalidArgumentException|DomainException $error) {
    http_response_code(409);
    exit(e($error->getMessage()));
} catch (Throwable $error) {
    publicError($error);
}
