<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/../includes/equipment_service.php';

requireAdmin();
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $code = inputString($_POST, 'code', 2048, true);
        $candidates = equipmentScanCandidates($code);
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));
        $stmt = $pdo->prepare("SELECT equipment.id, equipment.inventory_number FROM equipment JOIN equipment_issues ON equipment_issues.equipment_id = equipment.id AND equipment_issues.status = 'issued' WHERE equipment.status = 'issued' AND (equipment.qr_token IN ($placeholders) OR equipment.barcode IN ($placeholders) OR equipment.inventory_number IN ($placeholders) OR equipment.serial_number IN ($placeholders)) ORDER BY equipment_issues.id DESC LIMIT 1");
        $stmt->execute(array_merge($candidates, $candidates, $candidates, $candidates));
        $item = $stmt->fetch();
        if (!$item) { throw new DomainException('Активная выдача для этого кода не найдена'); }
        returnEquipment($pdo, (int)$item['id'], 'good', 'Возврат через сканер');
        $message = 'Оборудование возвращено: ' . $item['inventory_number'];
        $messageType = 'success';
    } catch (InvalidArgumentException|DomainException $error) {
        $message = $error->getMessage();
        $messageType = 'danger';
    } catch (Throwable $error) {
        publicError($error);
    }
}

include '../includes/app_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="mb-1">Возврат по сканеру</h1><div class="text-muted">Сканируйте штрихкод, инвентарный или серийный номер</div></div><a href="index.php" class="btn btn-outline-dark">Назад</a></div>
<div class="card border-0 shadow-sm"><div class="card-body">
<?php if ($message): ?><div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>
<form method="POST"><?= csrfField() ?><label class="form-label">Код оборудования</label><input type="text" name="code" maxlength="2048" class="form-control form-control-lg mb-3" autocomplete="off" autofocus required><button class="btn btn-success btn-lg w-100">Вернуть оборудование</button></form>
</div></div>
<script>(()=>{const input=document.querySelector('input[name="code"]');const form=input?.closest('form');if(!input||!form)return;input.focus();let timer=null;input.addEventListener('input',()=>{clearTimeout(timer);const value=input.value.trim();if(!/(?:^|[?&])token=|\/qr\.php\?|^[a-f0-9]{32}$/i.test(value))return;timer=setTimeout(()=>form.requestSubmit(),350);});})();</script>
<?php include '../includes/app_footer.php'; ?>
