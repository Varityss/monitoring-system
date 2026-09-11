<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';
require_once __DIR__ . '/../includes/equipment_service.php';

requireAdmin();
$id = inputPositiveInt($_GET, 'id');
$stmt = $pdo->prepare('SELECT * FROM equipment WHERE id = ? AND archived = 0 LIMIT 1');
$stmt->execute([$id]);
$equipment = $stmt->fetch();
if (!$equipment) { http_response_code(404); exit('Оборудование не найдено'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        issueEquipmentDirect(
            $pdo,
            $id,
            inputString($_POST, 'issued_to', 255, true),
            inputString($_POST, 'cabinet', 100, false),
            inputString($_POST, 'notes', 1000, false)
        );
        redirect('equipment/view.php?id=' . $id);
    } catch (InvalidArgumentException|DomainException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        publicError($exception);
    }
}

include '../includes/app_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="mb-1">Выдача оборудования</h1><div class="text-muted"><?= e($equipment['inventory_number']) ?></div></div><a href="view.php?id=<?= $id ?>" class="btn btn-outline-dark">Назад</a></div>
<div class="card border-0 shadow-sm"><div class="card-body">
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="POST">
<?= csrfField() ?>
<div class="mb-3"><label class="form-label">Кому выдаётся</label><input type="text" name="issued_to" maxlength="255" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Куда выдаётся</label><input type="text" name="cabinet" maxlength="100" class="form-control"></div>
<div class="mb-4"><label class="form-label">Основание / заметки</label><textarea name="notes" maxlength="1000" class="form-control" rows="4"></textarea></div>
<button class="btn btn-primary">Выдать оборудование</button>
</form></div></div>
<?php include '../includes/app_footer.php'; ?>
