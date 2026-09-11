<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';

requireAdmin();
$id = inputPositiveInt($_GET, 'id');
$stmt = $pdo->prepare('SELECT id, login, full_name FROM users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) { http_response_code(404); exit('Пользователь не найден'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        verifyCurrentPassword($pdo, $_POST['current_admin_password'] ?? null);
        $temporaryPassword = bin2hex(random_bytes(8));
        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE users SET password = ?, must_change_password = 1, password_changed_at = NOW(), session_version = session_version + 1 WHERE id = ?');
        $update->execute([password_hash($temporaryPassword, PASSWORD_DEFAULT), $id]);
        logActivity($pdo, 'reset_password', 'user', $id, 'Администратор сбросил пароль; активные сессии отозваны');
        $pdo->commit();
        $_SESSION['temporary_password_notice'] = ['login' => $target['login'], 'password' => $temporaryPassword];
        redirect('users/index.php');
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        publicError($exception);
    }
}

include '../includes/app_header.php';
?>
<div class="card border-0 shadow-sm"><div class="card-body"><h1 class="h3">Сброс пароля</h1><p>Пользователь: <strong><?= e($target['full_name'] ?: $target['login']) ?></strong></p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="POST"><?= csrfField() ?><div class="mb-3"><label class="form-label">Ваш пароль администратора</label><input type="password" name="current_admin_password" class="form-control" autocomplete="current-password" required></div><button class="btn btn-danger">Создать одноразовый временный пароль</button> <a href="index.php" class="btn btn-outline-secondary">Отмена</a></form>
</div></div>
<?php include '../includes/app_footer.php'; ?>
