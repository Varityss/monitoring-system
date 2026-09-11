<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
requireAdmin(); requirePostRequest(); verifyCsrfToken();
try {
    $id = inputPositiveInt($_GET, 'id');
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT role, is_active FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) { throw new DomainException('Пользователь не найден'); }
    if ($user['role'] === 'admin' && (int)$user['is_active'] === 1) {
        $adminRows = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchAll();
        if (count($adminRows) <= 1) { throw new DomainException('Нельзя отключить последнего активного администратора'); }
    }
    $newState = (int)$user['is_active'] === 1 ? 0 : 1;
    $pdo->prepare('UPDATE users SET is_active = ?, session_version = session_version + 1 WHERE id = ?')->execute([$newState, $id]);
    logActivity($pdo, 'toggle_active', 'user', $id, $newState ? 'Пользователь активирован' : 'Пользователь деактивирован');
    $pdo->commit();
    redirect('users/index.php');
} catch (DomainException $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(409); exit(e($error->getMessage()));
} catch (Throwable $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    publicError($error);
}
