<?php

session_start();

require 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE login = ?
    ");

    $stmt->execute([$login]);

    $user = $stmt->fetch();

    if (
        $user
        && $user['is_active'] == 1
        && password_verify($password, $user['password'])
    ) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_role'] = $user['role'];

        logActivity($pdo, 'login', 'user', (int)$user['id'], 'User login', (int)$user['id']);

        if (in_array($user['role'], ['teacher', 'user'], true)) {
            redirect('teacher/index.php');
        }

        redirect('dashboard.php');
    }

    $error = ($user && $user['is_active'] != 1)
        ? t('login.inactive')
        : t('login.invalid');
}

?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body p-4">
                <div class="page-kicker"><?= e(t('app.subtitle')) ?></div>
                <h1 class="h3 mb-4"><?= e(t('login.title')) ?></h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?= e(t('login.login')) ?></label>
                        <input type="text" name="login" class="form-control form-control-lg" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><?= e(t('login.password')) ?></label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>

                    <button class="btn btn-primary btn-lg w-100">
                        <?= e(t('login.submit')) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
