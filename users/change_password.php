<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';

requireLogin();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $current = $_POST['current_password'] ?? null;
        $new = $_POST['new_password'] ?? null;
        $confirm = $_POST['confirm_password'] ?? null;
        if (!is_string($current) || !is_string($new) || !is_string($confirm)) { throw new InvalidArgumentException('Некорректные данные'); }
        if (strlen($new) < 12 || strlen($new) > 4096) { throw new InvalidArgumentException('Новый пароль должен содержать не менее 12 символов'); }
        if ($new !== $confirm) { throw new InvalidArgumentException('Подтверждение пароля не совпадает'); }
        if (in_array($new, ['admin123', '12345678'], true)) { throw new InvalidArgumentException('Этот пароль запрещён'); }

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? FOR UPDATE');
        $pdo->beginTransaction();
        $stmt->execute([currentUserId()]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) { throw new InvalidArgumentException('Текущий пароль неверен'); }
        if (password_verify($new, $hash)) { throw new InvalidArgumentException('Новый пароль должен отличаться от текущего'); }
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0, password_changed_at = NOW(), session_version = session_version + 1 WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), currentUserId()]);
        $version = (int)$pdo->query('SELECT session_version FROM users WHERE id = ' . (int)currentUserId())->fetchColumn();
        logActivity($pdo, 'change_password', 'user', currentUserId(), 'Пользователь изменил пароль');
        $pdo->commit();
        $_SESSION['session_version'] = $version;
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
        unset($_SESSION['csrf_token']);
        session_regenerate_id(true);
        redirect(($_SESSION['user_role'] ?? '') === 'admin' ? 'dashboard.php' : 'teacher/index.php');
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        publicError($exception);
    }
}

include '../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-6"><div class="card"><div class="card-body p-4">
    <h1 class="h3 mb-3">Смена пароля</h1>
    <p class="text-muted">Используйте уникальный пароль длиной не менее 12 символов.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="POST">
        <?= csrfField() ?>
        <div class="mb-3"><label class="form-label">Текущий пароль</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
        <div class="mb-3"><label class="form-label">Новый пароль</label><input type="password" name="new_password" class="form-control" minlength="12" autocomplete="new-password" required></div>
        <div class="mb-4"><label class="form-label">Повторите новый пароль</label><input type="password" name="confirm_password" class="form-control" minlength="12" autocomplete="new-password" required></div>
        <button class="btn btn-primary w-100">Сохранить новый пароль</button>
    </form>
</div></div></div></div>
<?php include '../includes/footer.php'; ?>
