<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';

requireAdmin();
$id = inputPositiveInt($_GET, 'id');
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { http_response_code(404); exit('Пользователь не найден'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $login = inputString($_POST, 'login', 50, true);
        if (!preg_match('/^[\p{L}\p{N}._-]{3,50}$/u', $login)) { throw new InvalidArgumentException('Логин содержит недопустимые символы'); }
        $fullName = inputString($_POST, 'full_name', 100, true);
        $role = inputEnum($_POST, 'role', ['teacher', 'admin']);
        $roleChanged = $role !== $user['role'];
        $pdo->beginTransaction();
        $lockTarget = $pdo->prepare('SELECT role, is_active FROM users WHERE id = ? FOR UPDATE');
        $lockTarget->execute([$id]);
        $lockedUser = $lockTarget->fetch();
        if (!$lockedUser) { throw new DomainException('Пользователь не найден'); }
        $roleChanged = $role !== $lockedUser['role'];
        if ($roleChanged) {
            verifyCurrentPassword($pdo, $_POST['current_admin_password'] ?? null);
            if ($lockedUser['role'] === 'admin' && $role !== 'admin' && (int)$lockedUser['is_active'] === 1) {
                $activeAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchAll();
                if (count($activeAdmins) <= 1) { throw new DomainException('Нельзя понизить последнего активного администратора'); }
            }
        }
        $stmt = $pdo->prepare('UPDATE users SET login = ?, full_name = ?, role = ?, session_version = session_version + ? WHERE id = ?');
        $stmt->execute([$login, $fullName, $role, $roleChanged ? 1 : 0, $id]);
        logActivity($pdo, 'update', 'user', $id, 'Изменён пользователь ' . $login . '; роль: ' . $lockedUser['role'] . ' → ' . $role);
        $pdo->commit();
        redirect('users/index.php');
    } catch (InvalidArgumentException|DomainException $exception) {
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
<div class="card shadow-sm border-0"><div class="card-body"><h2 class="mb-4">Изменение пользователя</h2>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="POST"><?= csrfField() ?>
<div class="mb-3"><label class="form-label">Логин</label><input type="text" name="login" maxlength="50" class="form-control" value="<?= e($user['login']) ?>" required></div>
<div class="mb-3"><label class="form-label">ФИО</label><input type="text" name="full_name" maxlength="100" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
<div class="mb-3"><label class="form-label">Роль</label><select name="role" class="form-select"><option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option><option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option></select></div>
<div class="mb-4"><label class="form-label">Ваш пароль администратора</label><input type="password" name="current_admin_password" class="form-control" autocomplete="current-password"><div class="form-text">Обязателен при изменении роли.</div></div>
<button class="btn btn-primary">Сохранить</button>
</form></div></div>
<?php include '../includes/app_footer.php'; ?>
