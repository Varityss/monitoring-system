<?php

require_once __DIR__ . '/../includes/session.php';
require '../includes/auth.php';

requireAdmin();
require_once '../includes/db.php';
require_once '../includes/input.php';



$id = inputPositiveInt($_GET, 'id');
$error = '';

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
    try {
    verifyCsrfToken();
    $inventory_number = inputString($_POST, 'inventory_number', 255, true);
    $barcode = inputString($_POST, 'barcode', 255, false);
    $serial_number = inputString($_POST, 'serial_number', 255, true);
    $model = inputString($_POST, 'model', 255, true);
    $type = inputString($_POST, 'type', 100, true);
    $type = mb_strtolower($type) === 'ноутбук' ? 'Ноутбук' : $type;
    $cabinet = $type === 'Ноутбук' ? '408' : '411';
    $condition_status = inputEnum($_POST, 'condition_status', ['good', 'broken', 'repair']);
    $notes = inputString($_POST, 'notes', 2000, false);

    $pdo->beginTransaction();
    $update = $pdo->prepare("
        UPDATE equipment
        SET

            inventory_number = ?,
            barcode = ?,
            serial_number = ?,
            model = ?,
            type = ?,
            cabinet = ?,
            condition_status = ?,
            notes = ?

        WHERE id = ?
    ");

    $update->execute([

        $inventory_number,
        $barcode,
        $serial_number,
        $model,
        $type,
        $cabinet,
        $condition_status,
        $notes,
        $id

    ]);

    logActivity($pdo, 'update', 'equipment', $id, 'Изменено оборудование ' . $inventory_number);
    $pdo->commit();
    redirect('equipment/view.php?id=' . $id);
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ($exception->getCode() === '23000') {
            $error = 'Инвентарный, серийный номер или штрихкод уже существует';
        } else {
            publicError($exception);
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        publicError($exception);
    }
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

        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>

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
                        id="equipment_type"
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

                        <?php if (!in_array($equipment['type'], ['Ноутбук', 'Проектор', 'Монитор'], true)): ?>
                            <option value="<?= e($equipment['type']) ?>" selected><?= e($equipment['type']) ?></option>
                        <?php endif; ?>

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
                        id="assigned_cabinet"
                        class="form-control"
                        value="<?= htmlspecialchars($equipment['cabinet']) ?>"
                        readonly
                    >
                    <div class="form-text">Назначается автоматически по типу оборудования.</div>

                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Статус учёта</label>
                    <input class="form-control" value="<?= e(equipmentStatusLabel($equipment['status'])) ?>" disabled>
                    <div class="form-text">Статус меняется только операциями выдачи, возврата и архивации.</div>
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

<script>
const equipmentType = document.getElementById('equipment_type');
const assignedCabinet = document.getElementById('assigned_cabinet');
function updateAssignedCabinet() {
    assignedCabinet.value = equipmentType.value.trim().toLocaleLowerCase('ru') === 'ноутбук' ? '408' : '411';
}
equipmentType.addEventListener('change', updateAssignedCabinet);
updateAssignedCabinet();
</script>

<?php include '../includes/app_footer.php'; ?>
