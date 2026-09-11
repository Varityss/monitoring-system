<?php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/input.php';

final class LoginRateLimitException extends Exception
{
}

if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/auth.php';
    requireLogin();
    redirect(($_SESSION['user_role'] ?? '') === 'admin' ? 'dashboard.php' : 'teacher/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $login = inputString($_POST, 'login', 50, true);
        $passwordValue = $_POST['password'] ?? null;
        if (!is_string($passwordValue) || $passwordValue === '' || strlen($passwordValue) > 4096) {
            throw new InvalidArgumentException(t('login.invalid'));
        }

        $loginKey = hash('sha256', mb_strtolower($login));
        $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $rateStmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM login_attempts WHERE login_key = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE) AS login_attempts, (SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE) AS ip_attempts");
        $rateStmt->execute([$loginKey, $ipHash]);
        $rate = $rateStmt->fetch();
        if ((int)$rate['login_attempts'] >= 5 || (int)$rate['ip_attempts'] >= 20) {
            http_response_code(429);
            throw new LoginRateLimitException('Слишком много попыток. Повторите вход через 15 минут.');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user || (int)$user['is_active'] !== 1 || !password_verify($passwordValue, $user['password'])) {
            $attempt = $pdo->prepare('INSERT INTO login_attempts (login_key, ip_hash) VALUES (?, ?)');
            $attempt->execute([$loginKey, $ipHash]);
            usleep(random_int(150000, 350000));
            throw new InvalidArgumentException(t('login.invalid'));
        }

        $mustChange = (int)$user['must_change_password'] === 1
            || password_verify('admin123', $user['password'])
            || password_verify('12345678', $user['password']);
        if ($mustChange && (int)$user['must_change_password'] !== 1) {
            $pdo->prepare('UPDATE users SET must_change_password = 1, session_version = session_version + 1 WHERE id = ?')->execute([(int)$user['id']]);
            $user['session_version'] = (int)$user['session_version'] + 1;
        }

        $pdo->prepare('DELETE FROM login_attempts WHERE login_key = ? OR ip_hash = ?')->execute([$loginKey, $ipHash]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['session_version'] = (int)$user['session_version'];
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
        unset($_SESSION['csrf_token']);
        logActivity($pdo, 'login', 'user', (int)$user['id'], 'Вход пользователя', (int)$user['id']);

        if ($mustChange) {
            redirect('users/change_password.php');
        }
        redirect(in_array($user['role'], ['teacher', 'user'], true) ? 'teacher/index.php' : 'dashboard.php');
    } catch (InvalidArgumentException|LoginRateLimitException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        publicError($exception);
    }
}

include 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card"><div class="card-body p-4">
            <div class="page-kicker"><?= e(t('app.subtitle')) ?></div>
            <h1 class="h3 mb-4"><?= e(t('login.title')) ?></h1>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3"><label class="form-label"><?= e(t('login.login')) ?></label><input type="text" name="login" class="form-control form-control-lg" maxlength="50" autocomplete="username" required></div>
                <div class="mb-4"><label class="form-label"><?= e(t('login.password')) ?></label><input type="password" name="password" class="form-control form-control-lg" autocomplete="current-password" required></div>
                <button class="btn btn-primary btn-lg w-100"><?= e(t('login.submit')) ?></button>
            </form>
        </div></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
