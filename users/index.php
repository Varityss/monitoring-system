<?php

session_start();

require '../includes/auth.php';

require '../includes/db.php';

requireAdmin();

$stmt = $pdo->query("
    SELECT *
    FROM users

    ORDER BY id DESC
");

$users = $stmt->fetchAll();

include '../includes/app_header.php';

?>


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

                        <a
                            class="dropdown-item text-danger"
                            href="toogle.php?id=<?= $user['id'] ?>"
                            onclick="return confirm('Деактивировать аккаунт?')"
                        >
                            Деактивировать
                        </a>

                    </li>

                <?php else: ?>

                    <li>

                        <a
                            class="dropdown-item text-success"
                            href="activate.php?id=<?= $user['id'] ?>"
                        >
                            Активировать
                        </a>

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