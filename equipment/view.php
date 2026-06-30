<?php

session_start();
require '../includes/auth.php';

requireAdmin();
require '../includes/db.php';



$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM equipment
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$historyStmt = $pdo->prepare("
    SELECT *
    FROM equipment_issues

    WHERE equipment_id = ?

    ORDER BY id DESC
");

$historyStmt->execute([$id]);

$history = $historyStmt->fetchAll();

$equipment = $stmt->fetch();

if (!$equipment) {

    redirect('equipment/index.php');

}

if (empty($equipment['qr_token'])) {

    $equipment['qr_token'] = bin2hex(random_bytes(16));

    $updateQrToken = $pdo->prepare("
        UPDATE equipment
        SET qr_token = ?
        WHERE id = ?
    ");

    $updateQrToken->execute([
        $equipment['qr_token'],
        $equipment['id']
    ]);

}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            <?= htmlspecialchars($equipment['inventory_number']) ?>
        </h1>

        <div class="text-muted">
            <?= htmlspecialchars($equipment['model']) ?>
        </div>

    </div>

    <div class="d-flex gap-2">

        <a
            href="edit.php?id=<?= $equipment['id'] ?>"
            class="btn btn-outline-secondary"
        >
            Изменить
        </a>

        <?php if ($equipment['status'] === 'available'): ?>

    <a
        href="issue.php?id=<?= $equipment['id'] ?>"
        class="btn btn-primary"
    >
        Выдать
    </a>

<?php else: ?>

    <a
        href="return.php?id=<?= $equipment['id'] ?>"
        class="btn btn-success"
        onclick="return confirm('Подтвердить возврат оборудования?')"
    >
        Вернуть
    </a>

<?php endif; ?>
        <a
            href="index.php"
            class="btn btn-outline-dark"
        >
            Назад
        </a>

    </div>

</div>

<div class="row">

    <div class="col-md-8">

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-body">

                <h4 class="mb-4">
                    Информация
                </h4>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Инвентарный номер
                    </div>

                    <div class="col-md-8">
                        <?= htmlspecialchars($equipment['inventory_number']) ?>
                    </div>

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
                        Штрихкод
                    </div>

                    <div class="col-md-8">
                        <?= e($equipment['barcode'] ?? '') ?: '<span class="text-muted">Не указан</span>' ?>
                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Тип техники
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

                        <?php elseif ($equipment['status'] === 'issued'): ?>

                            <span class="badge bg-primary">
                                Выдано
                            </span>

                        <?php else: ?>

                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($equipment['status']) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4 text-muted">
                        Состояние
                    </div>

                    <div class="col-md-8">

                        <?php if ($equipment['condition_status'] === 'good'): ?>

                            <span class="badge bg-success">
                                Хорошее
                            </span>

                        <?php elseif ($equipment['condition_status'] === 'repair'): ?>

                            <span class="badge bg-warning text-dark">
                                Ремонт
                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger">
                                Сломано
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4 text-muted">
                        Заметки
                    </div>

                    <div class="col-md-8">

                        <?php if ($equipment['notes']): ?>

                            <?= nl2br(htmlspecialchars($equipment['notes'])) ?>

                        <?php else: ?>

                            <span class="text-muted">
                                Нет заметок
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card border-0 shadow-sm mb-4">

    <div class="card-body text-center">

        <h5 class="mb-4">
            QR Код
        </h5>

        <?php

        $scheme =
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                ? 'https'
                : 'http';

        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        $qrUrl =
            $scheme . '://' .
            $_SERVER['HTTP_HOST'] .
            $basePath .
            '/qr.php?token=' .
            urlencode($equipment['qr_token']);

        $qrImage =
            'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' .
            urlencode($qrUrl);

        ?>

        <img
            src="<?= $qrImage ?>"
            class="img-fluid border rounded p-2 bg-white mb-3"
            style="max-width: 250px;"
        >

        <div class="small text-muted mb-3">

            <?= htmlspecialchars($equipment['qr_token']) ?>

        </div>

        <div class="d-grid gap-2">

            <a
                href="<?= $qrImage ?>"
                target="_blank"
                class="btn btn-outline-primary btn-sm"
            >
                Открыть QR
            </a>

            <a
                href="<?= $qrUrl ?>"
                target="_blank"
                class="btn btn-outline-dark btn-sm"
            >
                Открыть страницу
            </a>
я
        </div>

    </div>

</div>

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <h5 class="mb-3">
                    Система
                </h5>

                <div class="small text-muted mb-2">
                    ID: <?= $equipment['id'] ?>
                </div>

                <div class="small text-muted">
                    Создано:
                    <?= $equipment['created_at'] ?>
                </div>

            </div>

        </div>

    </div>

</div>
<div class="card border-0 shadow-sm mt-4">

    <div class="card-body">

        <h4 class="mb-4">
            История оборудования
        </h4>

        <?php if (count($history) > 0): ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Кому выдано
                            </th>

                            <th>
                                Кабинет
                            </th>

                            <th>
                                Выдано
                            </th>

                            <th>
                                Возвращено
                            </th>

                            <th>
                                Статус
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($history as $issue): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($issue['issued_to']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($issue['cabinet']) ?>
                            </td>

                            <td>
                                <?= $issue['issued_at'] ?>
                            </td>

                            <td>

                                <?php if ($issue['returned_at']): ?>

                                    <?= $issue['returned_at'] ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Не возвращено
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($issue['status'] === 'issued'): ?>

                                    <span class="badge bg-primary">
                                        Выдано
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-success">
                                        Возвращено
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="text-muted">
                История отсутствует
            </div>

        <?php endif; ?>

    </div>

</div>
<?php include '../includes/app_footer.php'; ?>
