<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

if (!isset($_GET['id'])) {

    redirect('users/index.php');

}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE id = ?
");

$stmt->execute([$id]);

$user = $stmt->fetch();

if (!$user) {

    die('Пользователь не найден');

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("
        UPDATE users
        SET login = ?,
            full_name = ?,
            role = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $login,
        $full_name,
        $role,
        $id
    ]);

    logActivity($pdo, 'update', 'user', $id, 'Изменен пользователь ' . $login);

    redirect('users/index.php');

}

include '../includes/app_header.php';

?>

<div class="card shadow-sm border-0">

    <div class="card-body">

        <h2 class="mb-4">
            Изменение пользователя
        </h2>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Логин
                </label>

                <input
                    type="text"
                    name="login"
                    class="form-control"
                    value="<?= htmlspecialchars($user['login']) ?>"
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
                    value="<?= htmlspecialchars($user['full_name']) ?>"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Роль
                </label>

                <select
                    name="role"
                    class="form-select"
                >

                    <option
                        value="teacher"
                        <?= $user['role'] === 'teacher' ? 'selected' : '' ?>
                    >
                        Teacher
                    </option>

                    <option
                        value="admin"
                        <?= $user['role'] === 'admin' ? 'selected' : '' ?>
                    >
                        Admin
                    </option>

                </select>

            </div>

            <button class="btn btn-primary">
                Сохранить
            </button>

        </form>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
