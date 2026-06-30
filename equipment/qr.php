<?php
session_start();

require '../includes/auth.php';

requireAdmin();
require '../includes/db.php';

if (!isset($_GET['token'])) {

    die('QR token отсутствует');

}

$token = $_GET['token'];

$stmt = $pdo->prepare("
    SELECT *
    FROM equipment
    WHERE qr_token = ?
    LIMIT 1
");

$stmt->execute([$token]);

$equipment = $stmt->fetch();

if (!$equipment) {

    die('Оборудование не найдено');

}

?>

<!DOCTYPE html>
<html lang="ru">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        QR Equipment
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <h1 class="mb-3">
                <?= htmlspecialchars($equipment['inventory_number']) ?>
            </h1>

            <div class="text-muted mb-4">
                <?= htmlspecialchars($equipment['model']) ?>
            </div>

            <div class="row mb-3">

                <div class="col-md-4 text-muted">
                    Серийный номер
                </div>

                <div class="col-md-8">
                    <?= htmlspecialchars($equipment['serial_number']) ?>
                </div>

            </div>

            <div class="row mb-3">

                <div class="col-md-4 text-muted">
                    Тип
                </div>

                <div class="col-md-8">
                    <?= htmlspecialchars($equipment['type']) ?>
                </div>

            </div>

            <div class="row mb-3">

                <div class="col-md-4 text-muted">
                    Кабинет
                </div>

                <div class="col-md-8">
                    <?= htmlspecialchars($equipment['cabinet']) ?>
                </div>

            </div>

            <div class="row mb-3">

                <div class="col-md-4 text-muted">
                    Статус
                </div>

                <div class="col-md-8">

                    <?php if ($equipment['status'] === 'available'): ?>

                        <span class="badge bg-success">
                            Доступно
                        </span>

                    <?php else: ?>

                        <span class="badge bg-primary">
                            Выдано
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
