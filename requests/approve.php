<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/../includes/equipment_service.php';
require_once __DIR__ . '/generate_document.php';

requireAdmin();
requirePostRequest();
verifyCsrfToken();

try {
    $requestId = inputPositiveInt($_GET, 'id');
    $equipmentIds = inputIdList($_POST, 'equipment_ids', 100);
    issueRequestEquipment($pdo, $requestId, $equipmentIds);
    try {
        generateRequestDocument($pdo, $requestId);
    } catch (Throwable $documentError) {
        error_log('Document generation failed for request #' . $requestId . ': ' . $documentError->getMessage());
    }
    redirect('requests/view.php?id=' . $requestId);
} catch (InvalidArgumentException|DomainException $error) {
    http_response_code(409);
    exit(e($error->getMessage()));
} catch (Throwable $error) {
    publicError($error);
}
