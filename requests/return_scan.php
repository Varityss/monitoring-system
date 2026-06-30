<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfToken();

    $code = trim($_POST['code'] ?? '');

    if ($code === '') {

        $message = 'Сканируйте или введите код оборудования';
        $messageType = 'warning';

    } else {

        $scanCandidates = equipmentScanCandidates($code);
        $scanPlaceholders = implode(', ', array_fill(0, count($scanCandidates), '?'));

        $stmt = $pdo->prepare("
            SELECT
                equipment.*,
                request_equipment.request_id,
                request_equipment.id AS relation_id
            FROM equipment
            INNER JOIN request_equipment
                ON request_equipment.equipment_id = equipment.id
            WHERE equipment.archived = 0
            AND equipment.status = 'issued'
            AND request_equipment.status = 'issued'
            AND (
                equipment.qr_token IN ($scanPlaceholders)
                OR equipment.barcode IN ($scanPlaceholders)
                OR equipment.inventory_number IN ($scanPlaceholders)
                OR equipment.serial_number IN ($scanPlaceholders)
            )
            ORDER BY request_equipment.id DESC
            LIMIT 1
        ");

        $stmt->execute(array_merge(
            $scanCandidates,
            $scanCandidates,
            $scanCandidates,
            $scanCandidates
        ));

        $item = $stmt->fetch();

        if (!$item) {

            $message = 'Активная выдача для этого кода не найдена';
            $messageType = 'danger';

        } else {

            $pdo->beginTransaction();

            $updateRelation = $pdo->prepare("
                UPDATE request_equipment
                SET status = 'returned',
                    returned_at = NOW()
                WHERE id = ?
            ");
            $updateRelation->execute([$item['relation_id']]);

            $updateIssue = $pdo->prepare("
                UPDATE equipment_issues
                SET status = 'returned',
                    returned_at = NOW()
                WHERE equipment_id = ?
                AND request_id = ?
                AND status = 'issued'
            ");
            $updateIssue->execute([
                $item['id'],
                $item['request_id']
            ]);

            $updateEquipment = $pdo->prepare("
                UPDATE equipment
                SET status = 'available'
                WHERE id = ?
            ");
            $updateEquipment->execute([$item['id']]);

            $remainingStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM request_equipment
                WHERE request_id = ?
                AND status = 'issued'
            ");
            $remainingStmt->execute([$item['request_id']]);

            if ((int)$remainingStmt->fetchColumn() === 0) {
                $updateRequest = $pdo->prepare("
                    UPDATE requests
                    SET status = 'returned'
                    WHERE id = ?
                ");
                $updateRequest->execute([$item['request_id']]);
            }

            logActivity(
                $pdo,
                'return',
                'equipment',
                (int)$item['id'],
                'Возврат через сканер, заявка #' . $item['request_id']
            );

            $pdo->commit();

            $message = 'Оборудование возвращено: ' . $item['inventory_number'];
            $messageType = 'success';

        }

    }

}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h1 class="mb-1">Возврат по сканеру</h1>
        <div class="text-muted">Сканируйте штрихкод, инвентарный или серийный номер</div>
    </div>

    <a href="index.php" class="btn btn-outline-dark">Назад</a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <?php if ($message): ?>

            <div class="alert alert-<?= e($messageType) ?>">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <label class="form-label">Код оборудования</label>

            <input
                type="text"
                name="code"
                class="form-control form-control-lg mb-3"
                autocomplete="off"
                autofocus
                required
            >

            <button class="btn btn-success btn-lg w-100">
                Вернуть оборудование
            </button>

        </form>

    </div>

</div>

<script>
(() => {
    const input = document.querySelector('input[name="code"]');
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
