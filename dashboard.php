<?php

session_start();

require 'includes/auth.php';
require 'includes/db.php';

requireAdmin();

$equipmentStats = [
    'total' => 0,
    'available' => 0,
    'issued' => 0,
    'repair' => 0,
];

$equipmentRows = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM equipment
    WHERE archived = 0
    GROUP BY status
")->fetchAll();

foreach ($equipmentRows as $row) {
    $equipmentStats[$row['status']] = (int)$row['total'];
    $equipmentStats['total'] += (int)$row['total'];
}

$requestStats = [
    'pending' => 0,
    'issued' => 0,
    'returned' => 0,
];

$requestRows = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM requests
    GROUP BY status
")->fetchAll();

foreach ($requestRows as $row) {
    $requestStats[$row['status']] = (int)$row['total'];
}

$recentRequests = $pdo->query("
    SELECT requests.*, users.login
    FROM requests
    LEFT JOIN users ON requests.user_id = users.id
    ORDER BY requests.id DESC
    LIMIT 5
")->fetchAll();

$recentLogs = $pdo->query("
    SELECT activity_logs.*, users.login
    FROM activity_logs
    LEFT JOIN users ON activity_logs.user_id = users.id
    ORDER BY activity_logs.id DESC
    LIMIT 5
")->fetchAll();

include 'includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Dashboard</h1>
        <div class="text-muted">
            Добро пожаловать, <?= e($_SESSION['user_login']) ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Всего оборудования</div>
                <div class="display-6 fw-bold"><?= $equipmentStats['total'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Доступно</div>
                <div class="display-6 fw-bold text-success"><?= $equipmentStats['available'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Выдано</div>
                <div class="display-6 fw-bold text-warning"><?= $equipmentStats['issued'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">На ремонте</div>
                <div class="display-6 fw-bold text-danger"><?= $equipmentStats['repair'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Последние заявки</h4>
                    <a href="requests/index.php" class="btn btn-sm btn-outline-primary">Все заявки</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Количество</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr>
                                    <td>
                                        <a href="requests/view.php?id=<?= $request['id'] ?>">
                                            #<?= $request['id'] ?>
                                        </a>
                                    </td>
                                    <td><?= e($request['login']) ?></td>
                                    <td><?= (int)$request['requested_count'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= e(requestStatusLabel($request['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <span class="badge bg-warning text-dark w-100 py-2">
                            Ожидает: <?= $requestStats['pending'] ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <span class="badge bg-success w-100 py-2">
                            Выдано: <?= $requestStats['issued'] + ($requestStats['approved'] ?? 0) ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <span class="badge bg-secondary w-100 py-2">
                            Возвращено: <?= $requestStats['returned'] + ($requestStats['completed'] ?? 0) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h4 class="mb-3">Последние действия</h4>

                <?php if ($recentLogs): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="list-group-item px-0">
                                <div class="fw-semibold">
                                    <?= e($log['description'] ?: $log['action']) ?>
                                </div>
                                <div class="small text-muted">
                                    <?= e($log['login'] ?: 'Система') ?>
                                    ·
                                    <?= e($log['created_at']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">
                        Журнал пока пуст.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/app_footer.php'; ?>
