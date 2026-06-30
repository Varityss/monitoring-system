<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';


requireAdmin();

$search = $_GET['search'] ?? '';

$scan = trim($_GET['scan'] ?? '');

$scanError = '';

$status = $_GET['status'] ?? '';

$condition = $_GET['condition'] ?? '';

if ($scan !== '') {

    $scanCandidates = equipmentScanCandidates($scan);
    $scanPlaceholders = implode(', ', array_fill(0, count($scanCandidates), '?'));

    $scanStmt = $pdo->prepare("
        SELECT id
        FROM equipment
        WHERE archived = 0
        AND (
            qr_token IN ($scanPlaceholders)
            OR barcode IN ($scanPlaceholders)
            OR inventory_number IN ($scanPlaceholders)
            OR serial_number IN ($scanPlaceholders)
        )
        LIMIT 1
    ");

    $scanStmt->execute(array_merge(
        $scanCandidates,
        $scanCandidates,
        $scanCandidates,
        $scanCandidates
    ));

    $scanResult = $scanStmt->fetch();

    if ($scanResult) {

        redirect('equipment/view.php?id=' . $scanResult['id']);

    }

    $scanError = 'Оборудование с таким кодом не найдено';

}

$sql = "

    SELECT *

    FROM equipment

    WHERE archived = 0

";

$params = [];

if (!empty($search)) {

    $sql .= "

        AND (

            inventory_number LIKE ?

            OR serial_number LIKE ?

            OR barcode LIKE ?

            OR model LIKE ?

            OR cabinet LIKE ?

        )

    ";

    $searchValue = "%$search%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

}

if (!empty($status)) {

    $sql .= " AND status = ? ";

    $params[] = $status;

}

if (!empty($condition)) {

    $sql .= " AND condition_status = ? ";

    $params[] = $condition;

}

$sql .= " ORDER BY id DESC ";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$equipment = $stmt->fetchAll();

include '../includes/app_header.php';

?>
<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form method="GET" class="row g-3 align-items-end">

            <div class="col-md-9">

                <label class="form-label">
                    Сканировать штрихкод
                </label>

                <input
                    type="text"
                    name="scan"
                    class="form-control form-control-lg"
                    placeholder="Сканируйте или введите штрихкод"
                    autocomplete="off"
                    autofocus
                >

            </div>

            <div class="col-md-3">

                <button class="btn btn-dark btn-lg w-100">
                    Найти
                </button>

            </div>

        </form>

        <?php if ($scanError): ?>

            <div class="alert alert-warning mt-3 mb-0">
                <?= e($scanError) ?>
            </div>

        <?php endif; ?>

    </div>

</div>

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form method="GET">

            <div class="row g-3">

                <div class="col-md-5">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Поиск..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>

                <div class="col-md-3">

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            Все статусы
                        </option>

                        <option
                            value="available"
                            <?= $status === 'available' ? 'selected' : '' ?>
                        >
                            Доступно
                        </option>

                        <option
                            value="issued"
                            <?= $status === 'issued' ? 'selected' : '' ?>
                        >
                            Выдано
                        </option>

                    </select>

                </div>

                <div class="col-md-3">

                    <select
                        name="condition"
                        class="form-select"
                    >

                        <option value="">
                            Все состояния
                        </option>

                        <option
                            value="good"
                            <?= $condition === 'good' ? 'selected' : '' ?>
                        >
                            Хорошее
                        </option>

                        <option
                            value="broken"
                            <?= $condition === 'broken' ? 'selected' : '' ?>
                        >
                            Повреждено
                        </option>

                        <option
                            value="repair"
                            <?= $condition === 'repair' ? 'selected' : '' ?>
                        >
                            Ремонт
                        </option>

                    </select>

                </div>

                <div class="col-md-1">

                    <button class="btn btn-primary w-100">

                        OK

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>
<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            Оборудование
        </h1>

        <div class="text-muted">
            Управление техникой колледжа
        </div>

    </div>

    <div class="d-flex gap-2">

        <a
            href="import.php"
            class="btn btn-success"
        >
            Импорт Excel
        </a>

        <a
            href="create.php"
            class="btn btn-primary"
        >
            Добавить
        </a>

    </div>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>
                            Внутренний №
                        </th>

                        <th>
                            Инвентарный №
                        </th>

                        <th>
                            Серийный номер
                        </th>

                        <th>
                            Штрихкод
                        </th>

                        <th>
                            Тип
                        </th>

                        <th>
                            Модель
                        </th>

                        <th>
                            Дислокация
                        </th>

                        <th>
                            Состояние
                        </th>

                        <th>
                            Статус
                        </th>

                        <th width="220">
                            Действия
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($equipment as $item): ?>

                    <tr>

                        <td>
                            <?= $item['id'] ?>
                        </td>

                        <td>

                            <strong>
                                <?= htmlspecialchars($item['inventory_number']) ?>
                            </strong>

                        </td>

                        <td>

                            <?= htmlspecialchars($item['college_inventory_number']) ?>

                        </td>

                        <td>

                            <span class="text-muted">

                                <?= e($item['serial_number']) ?>

                            </span>

                        </td>

                        <td>

                            <?= e($item['barcode'] ?? '') ?>

                        </td>

                        <td>

                            <?php if ($item['type'] === 'laptop'): ?>

                                <span class="badge bg-primary">
                                    Ноутбук
                                </span>

                            <?php elseif ($item['type'] === 'pc'): ?>

                                <span class="badge bg-dark">
                                    ПК
                                </span>

                            <?php elseif ($item['type'] === 'projector'): ?>

                                <span class="badge bg-warning text-dark">
                                    Проектор
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($item['type']) ?>
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?= htmlspecialchars($item['model']) ?>

                        </td>

                        <td>

                            <strong>

                                <?= htmlspecialchars($item['cabinet']) ?>

                            </strong>

                        </td>

                        <td>

                            <?php if ($item['condition_status'] === 'good'): ?>

                                <span class="badge bg-success">
                                    Хорошее
                                </span>

                            <?php elseif ($item['condition_status'] === 'broken'): ?>

                                <span class="badge bg-danger">
                                    Повреждено
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Неизвестно
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($item['status'] === 'available'): ?>

                                <span class="badge bg-success">
                                    Доступно
                                </span>

                            <?php elseif ($item['status'] === 'issued'): ?>

                                <span class="badge bg-warning text-dark">
                                    Выдано
                                </span>

                            <?php elseif ($item['status'] === 'repair'): ?>

                                <span class="badge bg-danger">
                                    Ремонт
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Архив
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <div class="d-flex gap-2">

                                <a
                                    href="view.php?id=<?= $item['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Открыть
                                </a>

                                <a
                                    href="edit.php?id=<?= $item['id'] ?>"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    Изменить
                                </a>

                                <a
                                    href="archive.php?id=<?= $item['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Архивировать оборудование?')"
                                >
                                    Архив
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
(() => {
    const input = document.querySelector('input[name="scan"]');
    const form = input?.closest('form');

    if (!input || !form) {
        return;
    }

    input.focus();

    let scanTimer = null;

    function looksLikeQrScan(value) {
        return /(?:^|[?&])token=/.test(value)
            || /\/qr\.php\?/i.test(value)
            || /^[a-f0-9]{32}$/i.test(value);
    }

    input.addEventListener('input', () => {
        clearTimeout(scanTimer);

        const value = input.value.trim();

        if (!looksLikeQrScan(value)) {
            return;
        }

        scanTimer = setTimeout(() => {
            if (input.value.trim() !== '') {
                form.requestSubmit();
            }
        }, 350);
    });
})();
</script>

<?php include '../includes/app_footer.php'; ?>
