<?php

session_start();

require '../includes/db.php';
require '../includes/auth.php';

requireTeacher();

$equipmentStmt = $pdo->query("
    SELECT *
    FROM equipment
    WHERE status = 'available'
    AND archived = 0
    ORDER BY inventory_number ASC
");

$equipmentList = $equipmentStmt->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestType = $_POST['request_type'] ?? 'lesson';
    $selectionType = $_POST['equipment_selection_type'] ?? 'auto';
    $requestedCount = max(0, (int)($_POST['requested_count'] ?? 0));

    if ($requestedCount < 1) {
        $error = t('error.count_required');
    }

    $cabinet = $requestType === 'lesson' ? trim($_POST['cabinet'] ?? '') : null;
    $location = $requestType !== 'lesson' ? trim($_POST['location'] ?? '') : null;
    $issuedFrom = null;
    $issuedUntil = null;
    $formattedDate = null;
    $startTime = null;
    $endTime = null;

    if (!$error && $requestType === 'lesson') {
        $lessonDate = DateTime::createFromFormat('d.m.Y', $_POST['lesson_date'] ?? '');

        if (!$lessonDate) {
            $error = t('error.bad_date');
        } else {
            $formattedDate = $lessonDate->format('Y-m-d');
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            $issuedFrom = $formattedDate . ' ' . $startTime;
            $issuedUntil = $formattedDate . ' ' . $endTime;

            if (strtotime($issuedFrom) < time()) {
                $error = t('error.past_time');
            } elseif (strtotime($issuedUntil) <= strtotime($issuedFrom)) {
                $error = t('error.end_after_start');
            }
        }
    } elseif (!$error) {
        $issuedFrom = !empty($_POST['issued_from']) ? $_POST['issued_from'] : null;
        $issuedUntil = !empty($_POST['issued_until']) ? $_POST['issued_until'] : null;

        if ($issuedFrom && $issuedUntil && strtotime($issuedUntil) <= strtotime($issuedFrom)) {
            $error = t('error.end_after_start');
        }
    }

    $selectedEquipmentIds = [];

    if (!$error && $selectionType === 'manual') {
        $selectedEquipmentIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $_POST['equipment_ids'] ?? []
        ))));

        if (count($selectedEquipmentIds) !== $requestedCount) {
            $error = t('error.manual_count');
        } else {
            $placeholders = implode(',', array_fill(0, count($selectedEquipmentIds), '?'));
            $availableStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM equipment
                WHERE id IN ($placeholders)
                AND status = 'available'
                AND archived = 0
            ");

            $availableStmt->execute($selectedEquipmentIds);

            if ((int)$availableStmt->fetchColumn() !== $requestedCount) {
                $error = t('error.equipment_unavailable');
            }
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("
            INSERT INTO requests (
                user_id,
                request_type,
                equipment_selection_type,
                requested_count,
                cabinet,
                location,
                lesson_date,
                start_time,
                end_time,
                issued_from,
                issued_until,
                purpose,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $requestType,
            $selectionType,
            $requestedCount,
            $cabinet,
            $location,
            $formattedDate,
            $startTime,
            $endTime,
            $issuedFrom,
            $issuedUntil,
            $_POST['purpose'] ?? ''
        ]);

        $requestId = (int)$pdo->lastInsertId();

        logActivity($pdo, 'create', 'request', $requestId, 'Request created');

        if ($selectionType === 'manual' && $selectedEquipmentIds) {
            $insertEquipment = $pdo->prepare("
                INSERT INTO request_equipment (
                    request_id,
                    equipment_id
                )
                VALUES (?, ?)
            ");

            foreach ($selectedEquipmentIds as $equipmentId) {
                $insertEquipment->execute([$requestId, $equipmentId]);
            }
        }

        redirect('teacher/index.php');
    }
}

?>

<!DOCTYPE html>
<html lang="<?= e(currentLanguage()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('request.new')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?= assetUrl('assets/css/style.css') ?>">
</head>

<body>

<div class="teacher-shell">
    <div class="teacher-topbar">
        <div>
            <div class="page-kicker"><?= e(t('teacher.request_console')) ?></div>
            <h1 class="h3 mb-1"><?= e(t('request.new')) ?></h1>
            <div class="text-muted"><?= e(t('app.subtitle')) ?></div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= languageSwitcher() ?>
            <a href="index.php" class="btn btn-outline-dark">
                <?= e(t('teacher.requests')) ?>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label class="form-label"><?= e(t('request.type')) ?></label>
                        <select name="request_type" id="request_type" class="form-select form-select-lg" required>
                            <option value="lesson"><?= e(t('request.lesson')) ?></option>
                            <option value="work"><?= e(t('request.work')) ?></option>
                            <option value="competition"><?= e(t('request.competition')) ?></option>
                            <option value="home"><?= e(t('request.home')) ?></option>
                        </select>
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label"><?= e(t('request.selection')) ?></label>
                        <select name="equipment_selection_type" id="equipment_selection_type" class="form-select form-select-lg">
                            <option value="auto"><?= e(t('request.auto')) ?></option>
                            <option value="manual"><?= e(t('request.manual')) ?></option>
                        </select>
                    </div>
                </div>

                <div id="manual_equipment_block" class="mt-4" style="display:none;">
                    <label class="form-label"><?= e(t('request.select_equipment')) ?></label>
                    <div class="border rounded p-3 bg-white equipment-scroll">
                        <div class="small text-muted mb-2"><?= e(t('request.equipment_format')) ?></div>

                        <?php foreach ($equipmentList as $equipment): ?>
                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="equipment_ids[]"
                                    value="<?= (int)$equipment['id'] ?>"
                                    id="eq<?= (int)$equipment['id'] ?>"
                                >
                                <label class="form-check-label" for="eq<?= (int)$equipment['id'] ?>">
                                    <strong><?= e($equipment['inventory_number']) ?></strong>
                                    -
                                    <?= e($equipment['model']) ?>
                                    -
                                    <?= e($equipment['cabinet']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="lesson_fields" class="mt-4">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label"><?= e(t('teacher.cabinet')) ?></label>
                            <input type="text" name="cabinet" class="form-control form-control-lg">
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label"><?= e(t('request.lesson_date')) ?></label>
                            <input type="text" name="lesson_date" id="lesson_date" class="form-control form-control-lg" required>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label"><?= e(t('request.start')) ?></label>
                            <input type="text" name="start_time" id="start_time" class="form-control form-control-lg" required>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label"><?= e(t('request.end')) ?></label>
                            <input type="text" name="end_time" id="end_time" class="form-control form-control-lg" required>
                        </div>
                    </div>
                </div>

                <div id="extended_fields" class="mt-4" style="display:none;">
                    <div class="row g-3">
                        <div class="col-lg-12">
                            <label class="form-label"><?= e(t('teacher.location')) ?></label>
                            <input type="text" name="location" class="form-control form-control-lg">
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label"><?= e(t('request.issue_from')) ?></label>
                            <input type="datetime-local" name="issued_from" class="form-control form-control-lg">
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label"><?= e(t('request.issue_until')) ?></label>
                            <input type="datetime-local" name="issued_until" class="form-control form-control-lg">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-lg-4">
                        <label class="form-label"><?= e(t('teacher.request_count')) ?></label>
                        <input type="number" min="1" name="requested_count" class="form-control form-control-lg" required>
                    </div>

                    <div class="col-lg-8">
                        <label class="form-label"><?= e(t('request.purpose')) ?></label>
                        <textarea name="purpose" class="form-control" rows="4"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                    <?= e(t('request.submit')) ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/kz.js"></script>
<script>
const lang = <?= json_encode(currentLanguage()) ?>;
const flatpickrLocale = {
    ru: flatpickr.l10ns.ru,
    kk: flatpickr.l10ns.kz
}[lang] || undefined;

flatpickr('#lesson_date', {
    locale: flatpickrLocale,
    dateFormat: 'd.m.Y',
    minDate: 'today'
});

flatpickr('#start_time', {
    locale: flatpickrLocale,
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    minuteIncrement: 5
});

flatpickr('#end_time', {
    locale: flatpickrLocale,
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    minuteIncrement: 5
});

const lessonDateInput = document.getElementById('lesson_date');
const startTimeInput = document.getElementById('start_time');

function validatePastTime() {
    const selectedDate = lessonDateInput.value;

    if (!selectedDate) {
        return;
    }

    const today = new Date();
    const todayFormatted = [
        String(today.getDate()).padStart(2, '0'),
        String(today.getMonth() + 1).padStart(2, '0'),
        today.getFullYear()
    ].join('.');

    if (selectedDate !== todayFormatted) {
        startTimeInput._flatpickr.set('minTime', null);
        return;
    }

    const currentTime = [
        String(today.getHours()).padStart(2, '0'),
        String(today.getMinutes()).padStart(2, '0')
    ].join(':');

    startTimeInput._flatpickr.set('minTime', currentTime);
}

lessonDateInput.addEventListener('change', validatePastTime);
validatePastTime();

const requestType = document.getElementById('request_type');
const lessonFields = document.getElementById('lesson_fields');
const extendedFields = document.getElementById('extended_fields');

function updateRequestType() {
    const isLesson = requestType.value === 'lesson';

    lessonFields.style.display = isLesson ? 'block' : 'none';
    extendedFields.style.display = isLesson ? 'none' : 'block';

    lessonFields.querySelectorAll('input').forEach((input) => {
        input.disabled = !isLesson;
    });

    extendedFields.querySelectorAll('input').forEach((input) => {
        input.disabled = isLesson;
    });
}

requestType.addEventListener('change', updateRequestType);
updateRequestType();

const equipmentType = document.getElementById('equipment_selection_type');
const manualBlock = document.getElementById('manual_equipment_block');

function updateEquipmentSelection() {
    manualBlock.style.display = equipmentType.value === 'manual' ? 'block' : 'none';
}

equipmentType.addEventListener('change', updateEquipmentSelection);
updateEquipmentSelection();
</script>

</body>
</html>
