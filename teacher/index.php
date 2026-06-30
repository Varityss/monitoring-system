<?php

session_start();

require '../includes/db.php';
require '../includes/auth.php';

requireTeacher();

$stmt = $pdo->prepare("
    SELECT *
    FROM requests
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$_SESSION['user_id']]);

$requests = $stmt->fetchAll();

$statusCounts = [
    'pending' => 0,
    'active' => 0,
    'returned' => 0,
];

foreach ($requests as $request) {
    if ($request['status'] === 'pending') {
        $statusCounts['pending']++;
    } elseif (in_array($request['status'], ['approved', 'issued', 'overdue'], true)) {
        $statusCounts['active']++;
    } elseif (in_array($request['status'], ['completed', 'returned'], true)) {
        $statusCounts['returned']++;
    }
}

function teacherStatusBadge(string $status): string
{
    if ($status === 'pending') {
        return '<span class="badge bg-warning text-dark">' . e(t('status.pending')) . '</span>';
    }

    if (in_array($status, ['approved', 'issued'], true)) {
        return '<span class="badge bg-success">' . e(t('status.active')) . '</span>';
    }

    if ($status === 'overdue') {
        return '<span class="badge bg-danger">' . e(t('status.overdue')) . '</span>';
    }

    if (in_array($status, ['completed', 'returned'], true)) {
        return '<span class="badge bg-secondary">' . e(t('status.returned')) . '</span>';
    }

    return '<span class="badge bg-dark">' . e(t('status.rejected')) . '</span>';
}

?>

<!DOCTYPE html>
<html lang="<?= e(currentLanguage()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('teacher.requests')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= assetUrl('assets/css/style.css') ?>">
</head>

<body>

<div class="teacher-shell">
    <div class="teacher-topbar">
        <div>
            <div class="page-kicker"><?= e(t('teacher.request_console')) ?></div>
            <h1 class="h3 mb-1"><?= e(t('teacher.requests')) ?></h1>
            <div class="text-muted"><?= e($_SESSION['user_login']) ?></div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= languageSwitcher() ?>
            <a href="../logout.php" class="btn btn-outline-dark">
                <?= e(t('nav.logout')) ?>
            </a>
        </div>
    </div>

    <div class="metric-strip">
        <div class="metric-cell">
            <div class="metric-label"><?= e(t('nav.requests')) ?></div>
            <div class="metric-value"><?= count($requests) ?></div>
        </div>
        <div class="metric-cell">
            <div class="metric-label"><?= e(t('status.pending')) ?></div>
            <div class="metric-value"><?= $statusCounts['pending'] ?></div>
        </div>
        <div class="metric-cell">
            <div class="metric-label"><?= e(t('status.active')) ?></div>
            <div class="metric-value"><?= $statusCounts['active'] ?></div>
        </div>
    </div>

    <a href="create_request.php" class="btn btn-primary w-100 mb-4 py-3">
        <?= e(t('teacher.new_request')) ?>
    </a>

    <?php if (!$requests): ?>
        <div class="empty-state">
            <h2 class="h5"><?= e(t('teacher.empty')) ?></h2>
            <div class="text-muted"><?= e(t('teacher.empty_hint')) ?></div>
        </div>
    <?php endif; ?>

    <?php foreach ($requests as $request): ?>
        <?php
            $place = $request['cabinet'] ?: $request['location'];
            $date = $request['lesson_date'] ?: substr((string)$request['issued_from'], 0, 10);
            $start = $request['start_time'] ?: substr((string)$request['issued_from'], 11, 5);
            $end = $request['end_time'] ?: substr((string)$request['issued_until'], 11, 5);
        ?>

        <div class="card request-card mb-3">
            <div class="card-body">
                <div class="request-grid">
                    <div>
                        <div class="muted-label"><?= e(t('teacher.equipment')) ?></div>
                        <div class="fw-bold">
                            <?= (int)$request['requested_count'] ?>
                            <?= e(t('teacher.laptops')) ?>
                        </div>
                    </div>

                    <div class="text-md-end">
                        <?= teacherStatusBadge($request['status']) ?>
                    </div>
                </div>

                <hr>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="muted-label"><?= e($request['cabinet'] ? t('teacher.cabinet') : t('teacher.location')) ?></div>
                        <div><?= e($place ?: '-') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="muted-label"><?= e(t('teacher.period')) ?></div>
                        <div><?= e(trim($date . ' ' . $start . ' - ' . $end)) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="muted-label">ID</div>
                        <div>#<?= (int)$request['id'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
