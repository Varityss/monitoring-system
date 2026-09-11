<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
requireAdmin(); requirePostRequest(); verifyCsrfToken();
try {
    $id = inputPositiveInt($_GET, 'id');
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
    $lock->execute([$id]);
    if (!$lock->fetch()) { throw new DomainException('Пользователь не найден'); }
    $pdo->prepare('UPDATE users SET is_active = 1, session_version = session_version + 1 WHERE id = ?')->execute([$id]);
    logActivity($pdo, 'activate', 'user', $id, 'Пользователь активирован');
    $pdo->commit();
    redirect('users/index.php');
} catch (DomainException $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(404);
    exit(e($error->getMessage()));
} catch (Throwable $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    publicError($error);
}
