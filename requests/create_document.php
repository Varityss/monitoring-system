<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/generate_document.php';

requireAdmin();
requirePostRequest();
verifyCsrfToken();

try {
    $requestId = inputPositiveInt($_GET, 'id');
    $stmt = $pdo->prepare('SELECT status FROM requests WHERE id = ?');
    $stmt->execute([$requestId]);
    $status = $stmt->fetchColumn();
    if ($status === false) {
        throw new DomainException('Заявка не найдена');
    }
    if (!in_array($status, ['approved', 'issued', 'overdue'], true)) {
        throw new DomainException('Акт можно создать только для выданной заявки');
    }

    generateRequestDocument($pdo, $requestId);
    logActivity($pdo, 'create_document', 'request', $requestId, 'Акт выдачи сформирован повторно');
    redirect('requests/view.php?id=' . $requestId);
} catch (InvalidArgumentException|DomainException $error) {
    http_response_code(409);
    exit(e($error->getMessage()));
} catch (Throwable $error) {
    publicError($error);
}
