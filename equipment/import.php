<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

requireAdmin();

$error = '';
$message = '';
$previewRows = [];
$previewToken = '';

function importCell(mixed $value, int $maxLength): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException('Значение в Excel длиннее допустимого');
    }

    return $value;
}

function normalizedEquipmentType(?string $type): string
{
    if ($type === null) {
        throw new InvalidArgumentException('Не указан тип оборудования');
    }

    return mb_strtolower($type) === 'ноутбук' ? 'Ноутбук' : $type;
}

function nextInventoryNumber(PDO $pdo): string
{
    do {
        $value = 'EQ-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT 1 FROM equipment WHERE inventory_number = ? LIMIT 1');
        $stmt->execute([$value]);
    } while ($stmt->fetchColumn());

    return $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $action = inputEnum($_POST, 'action', ['preview', 'confirm']);

        if ($action === 'preview') {
            if (
                !isset($_FILES['excel'])
                || !is_array($_FILES['excel'])
                || ($_FILES['excel']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            ) {
                throw new InvalidArgumentException('Не удалось загрузить Excel-файл');
            }

            $upload = $_FILES['excel'];
            if (($upload['size'] ?? 0) < 1 || (int)$upload['size'] > 10 * 1024 * 1024) {
                throw new InvalidArgumentException('Размер файла должен быть не больше 10 МБ');
            }

            $extension = strtolower(pathinfo((string)($upload['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($extension, ['xlsx', 'xls'], true)) {
                throw new InvalidArgumentException('Разрешены только файлы XLSX и XLS');
            }

            $tmpName = (string)$upload['tmp_name'];
            if (!is_uploaded_file($tmpName)) {
                throw new InvalidArgumentException('Загруженный файл не прошёл проверку');
            }

            $readerType = IOFactory::identify($tmpName);
            if (!in_array($readerType, ['Xlsx', 'Xls'], true)) {
                throw new InvalidArgumentException('Содержимое файла не соответствует Excel');
            }

            $reader = IOFactory::createReader($readerType);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($tmpName)->getActiveSheet();
            if ($sheet->getHighestDataRow() > 2001) {
                throw new InvalidArgumentException('В одном импорте допускается не больше 2000 строк');
            }

            $rawRows = $sheet->toArray(null, false, false, false);
            $seenSerials = [];
            $seenBarcodes = [];
            $candidateRows = [];

            foreach ($rawRows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $type = importCell($row[0] ?? null, 100);
                $model = importCell($row[1] ?? null, 255);
                $collegeNumber = importCell($row[2] ?? null, 255);
                $serial = importCell($row[3] ?? null, 255);
                $barcode = importCell($row[5] ?? null, 255);

                if ($type === null && $model === null && $collegeNumber === null && $serial === null && $barcode === null) {
                    continue;
                }
                if ($model === null) {
                    throw new InvalidArgumentException('Строка ' . ($index + 1) . ': не указана модель');
                }
                if ($serial === null && $barcode === null && $collegeNumber === null) {
                    throw new InvalidArgumentException('Строка ' . ($index + 1) . ': нужен серийный, штрихкод или инвентарный номер колледжа');
                }

                $type = normalizedEquipmentType($type);
                $serialKey = $serial === null ? null : mb_strtolower($serial);
                $barcodeKey = $barcode === null ? null : mb_strtolower($barcode);
                if (($serialKey !== null && isset($seenSerials[$serialKey])) || ($barcodeKey !== null && isset($seenBarcodes[$barcodeKey]))) {
                    throw new InvalidArgumentException('Строка ' . ($index + 1) . ': дубль серийного номера или штрихкода внутри файла');
                }
                if ($serialKey !== null) {
                    $seenSerials[$serialKey] = true;
                }
                if ($barcodeKey !== null) {
                    $seenBarcodes[$barcodeKey] = true;
                }

                $candidateRows[] = [
                    'type' => $type,
                    'model' => $model,
                    'college_inventory_number' => $collegeNumber,
                    'serial_number' => $serial,
                    'barcode' => $barcode,
                    'cabinet' => $type === 'Ноутбук' ? '408' : '411',
                ];
            }

            if (!$candidateRows) {
                throw new InvalidArgumentException('В файле нет строк для импорта');
            }

            $previewToken = bin2hex(random_bytes(24));
            $_SESSION['equipment_import'] = [
                'token' => $previewToken,
                'user_id' => currentUserId(),
                'created_at' => time(),
                'rows' => $candidateRows,
            ];
            $previewRows = $candidateRows;
        } else {
            $batch = $_SESSION['equipment_import'] ?? null;
            $submittedToken = inputString($_POST, 'preview_token', 64, true);
            if (
                !is_array($batch)
                || !isset($batch['token'], $batch['rows'], $batch['created_at'], $batch['user_id'])
                || !is_string($batch['token'])
                || !hash_equals($batch['token'], $submittedToken)
                || (int)$batch['user_id'] !== currentUserId()
                || time() - (int)$batch['created_at'] > 1800
            ) {
                unset($_SESSION['equipment_import']);
                throw new InvalidArgumentException('Предпросмотр устарел. Загрузите файл ещё раз');
            }

            $pdo->beginTransaction();
            $duplicateCheck = $pdo->prepare(
                "SELECT id FROM equipment
                 WHERE (serial_number IS NOT NULL AND serial_number = ?)
                    OR (barcode IS NOT NULL AND barcode = ?)
                 LIMIT 1 FOR UPDATE"
            );
            $insert = $pdo->prepare(
                'INSERT INTO equipment
                 (inventory_number, college_inventory_number, serial_number, barcode, type, model, cabinet, qr_token, status, condition_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'available\', \'good\')'
            );

            $imported = 0;
            $skipped = 0;
            foreach ($batch['rows'] as $row) {
                $duplicateCheck->execute([$row['serial_number'], $row['barcode']]);
                if ($duplicateCheck->fetch()) {
                    $skipped++;
                    continue;
                }

                $insert->execute([
                    nextInventoryNumber($pdo),
                    $row['college_inventory_number'],
                    $row['serial_number'],
                    $row['barcode'],
                    $row['type'],
                    $row['model'],
                    $row['cabinet'],
                    bin2hex(random_bytes(32)),
                ]);
                $imported++;
            }

            logActivity($pdo, 'import', 'equipment', null, 'Импорт Excel: добавлено ' . $imported . ', пропущено дублей ' . $skipped);
            $pdo->commit();
            unset($_SESSION['equipment_import']);
            $message = 'Импортировано: ' . $imported . '. Пропущено дублей: ' . $skipped . '.';
        }
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        publicError($exception);
    }
}

include '../includes/app_header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Импорт оборудования</h2>
                <div class="text-muted">Excel → база оборудования</div>
            </div>
            <a href="index.php" class="btn btn-outline-dark">Назад</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

        <?php if ($previewRows): ?>
            <div class="alert alert-warning">
                Проверьте данные перед записью. Ноутбуки будут помещены в кабинет 408, остальное оборудование — в 411.
            </div>
            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Тип</th><th>Модель</th><th>Инв. № колледжа</th><th>Серийный №</th><th>Штрихкод</th><th>Кабинет</th></tr></thead>
                    <tbody>
                    <?php foreach ($previewRows as $row): ?>
                        <tr>
                            <td><?= e($row['type']) ?></td>
                            <td><?= e($row['model']) ?></td>
                            <td><?= e($row['college_inventory_number']) ?></td>
                            <td><?= e($row['serial_number']) ?></td>
                            <td><?= e($row['barcode']) ?></td>
                            <td><?= e($row['cabinet']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="preview_token" value="<?= e($previewToken) ?>">
                <button class="btn btn-success">Подтвердить импорт <?= count($previewRows) ?> позиций</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                Столбцы: Тип | Модель | Инвентарный № колледжа | Серийный номер | Кабинет (игнорируется) | Штрихкод.
                Перед записью система покажет все строки.
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="preview">
                <div class="mb-4">
                    <label class="form-label">Excel-файл</label>
                    <input type="file" name="excel" class="form-control" accept=".xlsx,.xls" required>
                </div>
                <button class="btn btn-primary">Проверить файл</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/app_footer.php'; ?>
