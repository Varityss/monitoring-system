<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login']);
    $full_name = trim($_POST['full_name']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (
        empty($login)
        ||
        empty($full_name)
        ||
        empty($password)
        ||
        empty($role)
    ) {

        $error = 'Заполните все поля';

    } else {

        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE login = ?
        ");

        $check->execute([$login]);

        if ($check->fetch()) {

            $error = 'Логин уже существует';

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    login,
                    full_name,
                    password,
                    role
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $login,
                $full_name,
                $hashedPassword,
                $role
            ]);

            logActivity($pdo, 'create', 'user', (int)$pdo->lastInsertId(), 'Создан пользователь ' . $login);

            redirect('users/index.php');

        }

    }

}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <h1>
        Создание пользователя
    </h1>

    <a
        href="index.php"
        class="btn btn-outline-dark"
    >
        Назад
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <?php if ($error): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Логин
                </label>

                <input
                    type="text"
                    name="login"
                    class="form-control"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    ФИО
                </label>

                <input
                    type="text"
                    name="full_name"
                    class="form-control"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Пароль
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    required
                >

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Роль
                </label>

                <select
                    name="role"
                    class="form-select"
                    required
                >

                    <option value="teacher">
                        Teacher
                    </option>

                    <option value="admin">
                        Admin
                    </option>

                </select>

            </div>

            <button class="btn btn-primary">

                Создать пользователя

            </button>

        </form>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
