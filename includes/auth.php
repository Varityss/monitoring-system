<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

function clearAuthentication(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_login'],
        $_SESSION['user_role'],
        $_SESSION['session_version'],
        $_SESSION['authenticated_at'],
        $_SESSION['last_activity']
    );
}

function requireLogin(): void
{
    global $pdo;

    if (empty($_SESSION['user_id']) || (!is_int($_SESSION['user_id']) && !ctype_digit((string)$_SESSION['user_id']))) {
        clearAuthentication();
        redirect('login.php');
    }

    $now = time();
    $authenticatedAt = (int)($_SESSION['authenticated_at'] ?? 0);
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($authenticatedAt < 1 || $lastActivity < 1 || $now - $lastActivity > SESSION_IDLE_TIMEOUT || $now - $authenticatedAt > SESSION_ABSOLUTE_TIMEOUT) {
        clearAuthentication();
        session_regenerate_id(true);
        redirect('login.php?expired=1');
    }

    $stmt = $pdo->prepare('SELECT id, login, role, is_active, session_version, must_change_password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['is_active'] !== 1 || (int)$user['session_version'] !== (int)($_SESSION['session_version'] ?? -1)) {
        clearAuthentication();
        session_regenerate_id(true);
        redirect('login.php?revoked=1');
    }

    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = $now;

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ((int)$user['must_change_password'] === 1 && !in_array($script, ['change_password.php', 'logout.php'], true)) {
        redirect('users/change_password.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['user_role'] ?? null) !== 'admin') {
        http_response_code(403);
        exit('Доступ запрещён');
    }
}

function requireTeacher(): void
{
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? null, ['teacher', 'user'], true)) {
        redirect('dashboard.php');
    }
}

function verifyCurrentPassword(PDO $pdo, mixed $password): void
{
    if (!is_string($password) || $password === '' || strlen($password) > 4096) {
        throw new InvalidArgumentException('Требуется подтверждение паролем администратора');
    }
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? AND is_active = 1');
    $stmt->execute([currentUserId()]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($password, $hash)) {
        throw new InvalidArgumentException('Пароль администратора неверен');
    }
}
