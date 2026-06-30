<?php

session_start();
require '../includes/auth.php';

requireAdmin();

require '../includes/db.php';

$lastEquipment = $pdo->query("
    SELECT inventory_number
    FROM equipment
    ORDER BY id DESC
    LIMIT 1
")->fetch();

$generatedInventory = 'NB-0001';

if ($lastEquipment) {

    preg_match('/(\d+)/', $lastEquipment['inventory_number'], $matches);

    if (isset($matches[1])) {

        $number = (int)$matches[1] + 1;

        $generatedInventory = 'NB-' . str_pad($number, 4, '0', STR_PAD_LEFT);

    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inventory_number = trim($_POST['inventory_number']);
    $barcode = trim($_POST['barcode'] ?? '');
    $serial_number = trim($_POST['serial_number']);
    $model = trim($_POST['model']);
    $type = trim($_POST['type']);
    $cabinet = trim($_POST['cabinet']);
    $condition_status = trim($_POST['condition_status']);
    $notes = trim($_POST['notes']);

    $qr_token = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare("
        INSERT INTO equipment (

            inventory_number,
            barcode,
            serial_number,
            model,
            type,
            cabinet,
            condition_status,
            notes,
            qr_token

        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([

        $inventory_number,
        $barcode !== '' ? $barcode : null,
        $serial_number,
        $model,
        $type,
        $cabinet,
        $condition_status,
        $notes,
        $qr_token

    ]);

    logActivity($pdo, 'create', 'equipment', (int)$pdo->lastInsertId(), 'Добавлено оборудование ' . $inventory_number);

    redirect('equipment/index.php');
}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <h1>
        Добавить оборудование
    </h1>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Инвентарный номер
                    </label>

                    <div class="input-group">

                        <input
                            type="text"
                            name="inventory_number"
                            class="form-control"
                            value="<?= htmlspecialchars($generatedInventory) ?>"
                            required
                        >

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            onclick="generateInventory()"
                        >
                            +
                        </button>

                    </div>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Серийный номер
                    </label>

                    <input
                        type="text"
                        name="serial_number"
                        class="form-control"
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
                    autocomplete="off"
                    placeholder="Сканируйте штрихкод в это поле"
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

                        <option value="Ноутбук">
                            Ноутбук
                        </option>

                        <option value="Проектор">
                            Проектор
                        </option>

                        <option value="Монитор">
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
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Состояние
                    </label>

                    <select
                        name="condition_status"
                        class="form-select"
                    >

                        <option value="good">
                            Хорошее
                        </option>

                        <option value="repair">
                            Требует ремонта
                        </option>

                        <option value="broken">
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
                    rows="4"
                ></textarea>

            </div>

            <button class="btn btn-primary">
                Сохранить оборудование
            </button>

        </form>

    </div>

</div>

<script>

function generateInventory() {

    const input = document.querySelector('[name="inventory_number"]');

    let current = input.value;

    let match = current.match(/(\d+)/);

    if (!match) return;

    let number = parseInt(match[1]) + 1;

    input.value = 'NB-' + String(number).padStart(4, '0');

}

</script>

<?php include '../includes/app_footer.php'; ?>
