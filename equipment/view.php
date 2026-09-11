<?php

require_once __DIR__ . '/../includes/session.php';
require '../includes/auth.php';
require_once '../includes/input.php';

requireAdmin();
require_once '../includes/db.php';



try {
    $id = inputPositiveInt($_GET, 'id');
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit('Некорректный идентификатор');
}

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

$equipment['qr_token'] = is_string($equipment['qr_token']) ? $equipment['qr_token'] : '';

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

<?php elseif ($equipment['status'] === 'issued'): ?>

    <form method="POST" action="return.php?id=<?= (int)$equipment['id'] ?>" class="d-flex gap-2" onsubmit="return confirm('Подтвердить возврат оборудования?')">
        <?= csrfField() ?>
        <input type="hidden" name="condition_status" value="good">
        <input type="hidden" name="return_notes" value="Возврат из карточки оборудования">
        <button class="btn btn-success">Вернуть исправным</button>
    </form>

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

        $relativeQrUrl = url('equipment/qr.php?token=' . urlencode($equipment['qr_token']));
        $qrUrl = APP_URL !== ''
            ? APP_URL . $relativeQrUrl
            : $relativeQrUrl;
        $qrImage = APP_URL !== ''
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrUrl)
            : null;

        ?>

        <?php if ($qrImage !== null): ?>
            <img
                src="<?= e($qrImage) ?>"
                class="img-fluid border rounded p-2 bg-white mb-3"
                style="max-width: 250px;"
                alt="QR-код оборудования"
            >
        <?php else: ?>
            <div class="alert alert-warning small">Для генерации QR задайте APP_URL в окружении сервера.</div>
        <?php endif; ?>

        <div class="small text-muted mb-3">

            <?= htmlspecialchars($equipment['qr_token']) ?>

        </div>

        <div class="d-grid gap-2">

            <?php if ($qrImage !== null): ?>
                <a href="<?= e($qrImage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                    Открыть QR
                </a>
            <?php endif; ?>

            <a
                href="<?= e($qrUrl) ?>"
                target="_blank"
                rel="noopener noreferrer"
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
