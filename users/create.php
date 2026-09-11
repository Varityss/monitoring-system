<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';

requireAdmin();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $login = inputString($_POST, 'login', 50, true);
        if (!preg_match('/^[\p{L}\p{N}._-]{3,50}$/u', $login)) { throw new InvalidArgumentException('Логин содержит недопустимые символы'); }
        $fullName = inputString($_POST, 'full_name', 100, true);
        $role = inputEnum($_POST, 'role', ['teacher', 'admin']);
        $password = $_POST['password'] ?? null;
        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 4096) { throw new InvalidArgumentException('Временный пароль должен содержать не менее 12 символов'); }
        if (in_array($password, ['admin123', '12345678'], true)) { throw new InvalidArgumentException('Этот пароль запрещён'); }
        if ($role === 'admin') { verifyCurrentPassword($pdo, $_POST['current_admin_password'] ?? null); }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (login, full_name, password, role, must_change_password) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$login, $fullName, password_hash($password, PASSWORD_DEFAULT), $role]);
        $id = (int)$pdo->lastInsertId();
        logActivity($pdo, 'create', 'user', $id, 'Создан пользователь ' . $login . ' с ролью ' . $role);
        $pdo->commit();
        redirect('users/index.php');
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ($exception->getCode() === '23000') {
            $error = 'Логин уже существует';
        } else {
            publicError($exception);
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        publicError($exception);
    }
}

include '../includes/app_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h1>Создание пользователя</h1><a href="index.php" class="btn btn-outline-dark">Назад</a></div>
<div class="card border-0 shadow-sm"><div class="card-body">
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="POST"><?= csrfField() ?>
<div class="mb-3"><label class="form-label">Логин</label><input type="text" name="login" maxlength="50" class="form-control" required></div>
<div class="mb-3"><label class="form-label">ФИО</label><input type="text" name="full_name" maxlength="100" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Временный пароль</label><input type="password" name="password" minlength="12" class="form-control" autocomplete="new-password" required><div class="form-text">Пользователь обязан заменить его при первом входе.</div></div>
<div class="mb-3"><label class="form-label">Роль</label><select name="role" class="form-select" required><option value="teacher">Teacher</option><option value="admin">Admin</option></select></div>
<div class="mb-4"><label class="form-label">Ваш пароль администратора</label><input type="password" name="current_admin_password" class="form-control" autocomplete="current-password"><div class="form-text">Обязателен при создании другого администратора.</div></div>
<button class="btn btn-primary">Создать пользователя</button>
</form></div></div>
<?php include '../includes/app_footer.php'; ?>
