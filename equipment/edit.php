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

$equipment = $stmt->fetch();

if (!$equipment) {

    redirect('equipment/index.php');

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inventory_number = trim($_POST['inventory_number']);
    $barcode = trim($_POST['barcode'] ?? '');
    $serial_number = trim($_POST['serial_number']);
    $model = trim($_POST['model']);
    $type = trim($_POST['type']);
    $cabinet = trim($_POST['cabinet']);
    $status = trim($_POST['status']);
    $condition_status = trim($_POST['condition_status']);
    $notes = trim($_POST['notes']);

    $update = $pdo->prepare("
        UPDATE equipment
        SET

            inventory_number = ?,
            barcode = ?,
            serial_number = ?,
            model = ?,
            type = ?,
            cabinet = ?,
            status = ?,
            condition_status = ?,
            notes = ?

        WHERE id = ?
    ");

    $update->execute([

        $inventory_number,
        $barcode !== '' ? $barcode : null,
        $serial_number,
        $model,
        $type,
        $cabinet,
        $status,
        $condition_status,
        $notes,
        $id

    ]);

    logActivity($pdo, 'update', 'equipment', $id, 'Изменено оборудование ' . $inventory_number);

    redirect('equipment/view.php?id=' . $id);
}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            Изменить оборудование
        </h1>

        <div class="text-muted">
            <?= htmlspecialchars($equipment['inventory_number']) ?>
        </div>

    </div>

    <a
        href="view.php?id=<?= $equipment['id'] ?>"
        class="btn btn-outline-dark"
    >
        Назад
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Инвентарный номер
                    </label>

                    <input
                        type="text"
                        name="inventory_number"
                        class="form-control"
                        value="<?= htmlspecialchars($equipment['inventory_number']) ?>"
                        required
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Серийный номер
                    </label>

                    <input
                        type="text"
                        name="serial_number"
                        class="form-control"
                        value="<?= htmlspecialchars($equipment['serial_number']) ?>"
                        required
                    >

                </div>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Штрихкод
                </label>

                <input
                    type="text"
                    name="barcode"
                    class="form-control"
                    value="<?= e($equipment['barcode'] ?? '') ?>"
                    autocomplete="off"
                >

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Модель
                    </label>

                    <input
                        type="text"
                        name="model"
                        class="form-control"
                        value="<?= htmlspecialchars($equipment['model']) ?>"
                        required
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Тип техники
                    </label>

                    <select
                        name="type"
                        class="form-select"
                    >

                        <option
                            value="Ноутбук"
                            <?= $equipment['type'] === 'Ноутбук' ? 'selected' : '' ?>
                        >
                            Ноутбук
                        </option>

                        <option
                            value="Проектор"
                            <?= $equipment['type'] === 'Проектор' ? 'selected' : '' ?>
                        >
                            Проектор
                        </option>

                        <option
                            value="Монитор"
                            <?= $equipment['type'] === 'Монитор' ? 'selected' : '' ?>
                        >
                            Монитор
                        </option>

                    </select>

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Кабинет
                    </label>

                    <input
                        type="text"
                        name="cabinet"
                        class="form-control"
                        value="<?= htmlspecialchars($equipment['cabinet']) ?>"
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Статус
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option
                            value="available"
                            <?= $equipment['status'] === 'available' ? 'selected' : '' ?>
                        >
                            Доступно
                        </option>

                        <option
                            value="issued"
                            <?= $equipment['status'] === 'issued' ? 'selected' : '' ?>
                        >
                            Выдано
                        </option>

                        <option
                            value="repair"
                            <?= $equipment['status'] === 'repair' ? 'selected' : '' ?>
                        >
                            Ремонт
                        </option>

                    </select>

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Состояние
                    </label>

                    <select
                        name="condition_status"
                        class="form-select"
                    >

                        <option
                            value="good"
                            <?= $equipment['condition_status'] === 'good' ? 'selected' : '' ?>
                        >
                            Хорошее
                        </option>

                        <option
                            value="repair"
                            <?= $equipment['condition_status'] === 'repair' ? 'selected' : '' ?>
                        >
                            Требует ремонта
                        </option>

                        <option
                            value="broken"
                            <?= $equipment['condition_status'] === 'broken' ? 'selected' : '' ?>
                        >
                            Сломано
                        </option>

                    </select>

                </div>

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Заметки
                </label>

                <textarea
                    name="notes"
                    class="form-control"
                    rows="5"
                ><?= htmlspecialchars($equipment['notes']) ?></textarea>

            </div>

            <button class="btn btn-primary">
                Сохранить изменения
            </button>

        </form>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
