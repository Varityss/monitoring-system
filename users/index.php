<?php

require_once __DIR__ . '/../includes/session.php';

require '../includes/auth.php';

require_once '../includes/db.php';

requireAdmin();

$stmt = $pdo->query("
    SELECT *
    FROM users

    ORDER BY id DESC
");

$users = $stmt->fetchAll();
$temporaryPasswordNotice = $_SESSION['temporary_password_notice'] ?? null;
unset($_SESSION['temporary_password_notice']);

include '../includes/app_header.php';

?>

<?php if (is_array($temporaryPasswordNotice)): ?>
    <div class="alert alert-warning">
        Временный пароль для <strong><?= e($temporaryPasswordNotice['login'] ?? '') ?></strong>:
        <code><?= e($temporaryPasswordNotice['password'] ?? '') ?></code>.
        Он показывается один раз; передайте его пользователю безопасным способом.
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <h1>
        Пользователи
    </h1>

    <a
        href="create.php"
        class="btn btn-primary"
    >
        Добавить пользователя
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Логин</th>

                        <th>ФИО</th>

                        <th>Роль</th>

                        <th>Статус</th>

                        <th width="180">
                            Действия
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <?= $user['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user['login']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </td>

                        <td>

                            <?php if ($user['role'] === 'admin'): ?>

                                <span class="badge bg-danger">
                                    Admin
                                </span>

                            <?php else: ?>

                                <span class="badge bg-primary">
                                    Teacher
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

    <?php if ($user['is_active'] == 1): ?>

        <span class="badge bg-success">
            Активен
        </span>

    <?php else: ?>

        <span class="badge bg-secondary">
            Отключен
        </span>

    <?php endif; ?>

<td>

    <div class="d-flex gap-2">

        <a
            href="edit.php?id=<?= $user['id'] ?>"
            class="btn btn-sm btn-dark"
        >
            Профиль
        </a>

        <div class="dropdown">

            <button
                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
            >
                Действия
            </button>

            <ul class="dropdown-menu dropdown-menu-end">

                <li>

                    <a
                        class="dropdown-item"
                        href="history.php?id=<?= $user['id'] ?>"
                    >
                        История
                    </a>

                </li>

                <li>

                    <a
                        class="dropdown-item"
                        href="reset_password.php?id=<?= $user['id'] ?>"
                    >
                        Сброс пароля
                    </a>

                </li>

                <li>
                    <hr class="dropdown-divider">
                </li>

                <?php if ($user['is_active'] == 1): ?>

                    <li>

                        <form method="POST" action="toogle.php?id=<?= (int)$user['id'] ?>" onsubmit="return confirm('Деактивировать аккаунт?')">
                            <?= csrfField() ?>
                            <button class="dropdown-item text-danger">Деактивировать</button>
                        </form>

                    </li>

                <?php else: ?>

                    <li>

                        <form method="POST" action="activate.php?id=<?= (int)$user['id'] ?>">
                            <?= csrfField() ?>
                            <button class="dropdown-item text-success">Активировать</button>
                        </form>

                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</td>
                        

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
