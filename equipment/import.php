<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_FILES['excel']['tmp_name'])) {

        $file = $_FILES['excel']['tmp_name'];

        $spreadsheet = IOFactory::load($file);

        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {

            // пропуск заголовков
            if ($index === 0) {
                continue;
            }

            $type = trim($row[0] ?? '');
            $model = trim($row[1] ?? '');
            $college_inventory_number = trim($row[2] ?? '');
            $serial_number = trim($row[3] ?? '');
            $cabinet = trim($row[4] ?? '');
            $barcode = trim($row[5] ?? '');

            // пустые строки пропускаем
            if (
                empty($type) &&
                empty($model) &&
                empty($serial_number)
            ) {
                continue;
            }

            // защита от дублей по серийнику
            $check = $pdo->prepare("
                SELECT id
                FROM equipment
                WHERE serial_number = ?
                OR (barcode IS NOT NULL AND barcode <> '' AND barcode = ?)
            ");

            $check->execute([
                $serial_number,
                $barcode
            ]);

            if ($check->fetch()) {

                $skipped++;
                continue;

            }

            // генерация внутреннего номера
            $inventory_number = 'EQ-' . str_pad(
                rand(1, 999999),
                6,
                '0',
                STR_PAD_LEFT
            );

            $qr_token = bin2hex(random_bytes(16));

            // добавление оборудования
            $stmt = $pdo->prepare("
                INSERT INTO equipment (

                    inventory_number,
                    college_inventory_number,
                    serial_number,
                    barcode,
                    type,
                    model,
                    cabinet,
                    qr_token,
                    status,
                    condition_status

                )
                VALUES (

                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?

                )
            ");

            $stmt->execute([

                $inventory_number,
                $college_inventory_number,
                $serial_number,
                $barcode !== '' ? $barcode : null,
                $type,
                $model,
                $cabinet,
                $qr_token,
                'available',
                'good'

            ]);

            $imported++;

        }

        $message = "
            Импортировано: $imported <br>
            Пропущено дублей: $skipped
        ";

        logActivity($pdo, 'import', 'equipment', null, 'Импорт Excel: добавлено ' . $imported . ', пропущено ' . $skipped);

    }

}

include '../includes/app_header.php';

?>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Импорт оборудования
                </h2>

                <div class="text-muted">
                    Excel → Equipment Database
                </div>

            </div>

            <a
                href="index.php"
                class="btn btn-outline-dark"
            >
                Назад
            </a>

        </div>

        <?php if ($message): ?>

            <div class="alert alert-success">

                <?= $message ?>

            </div>

        <?php endif; ?>

        <div class="alert alert-info">

            Формат Excel:

            <hr>

            Тип | Модель | Инвентарный № | Серийный номер | Кабинет

        </div>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="mb-4">

                <label class="form-label">

                    Excel файл

                </label>

                <input
                    type="file"
                    name="excel"
                    class="form-control"
                    accept=".xlsx,.xls"
                    required
                >

            </div>

            <button class="btn btn-success">

                Импортировать оборудование

            </button>

        </form>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
