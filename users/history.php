<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

$stmt = $pdo->query("

    SELECT requests.*, users.login

    FROM requests

    LEFT JOIN users
    ON requests.user_id = users.id

    ORDER BY requests.id DESC

");

$requests = $stmt->fetchAll();

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            История заявок
        </h1>

        <div class="text-muted">

            Все заявки системы

        </div>

    </div>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <?php if (count($requests) > 0): ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Пользователь
                            </th>

                            <th>
                                Тип
                            </th>

                            <th>
                                Ноутбуков
                            </th>

                            <th>
                                Дата
                            </th>

                            <th>
                                Статус
                            </th>

                            <th width="180">
                                Действия
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($requests as $request): ?>

                        <tr>

                            <td>

                                <strong>

                                    #<?= $request['id'] ?>

                                </strong>

                            </td>

                            <td>

                                <?= htmlspecialchars($request['login']) ?>

                            </td>

                            <td>

                                <?php

                                $types = [

                                    'lesson' => 'Урок',
                                    'work' => 'Работа',
                                    'competition' => 'Соревнование',
                                    'event' => 'Мероприятие',
                                    'home' => 'Домой'

                                ];

                                echo $types[$request['request_type']]
                                    ?? 'Другое';

                                ?>

                            </td>

                            <td>

                                <?= $request['requested_count'] ?>

                            </td>

                            <td>

                                <?= date(
                                    'd.m.Y H:i',
                                    strtotime($request['created_at'])
                                ) ?>

                            </td>

                            <td>

                                <?php if ($request['status'] === 'pending'): ?>

                                    <span class="badge bg-warning text-dark">
                                        Ожидание
                                    </span>

                                <?php elseif (in_array($request['status'], ['approved', 'issued'], true)): ?>

                                    <span class="badge bg-success">
                                        Выдано
                                    </span>

                                <?php elseif (in_array($request['status'], ['completed', 'returned'], true)): ?>

                                    <span class="badge bg-secondary">
                                        Завершено
                                    </span>

                                <?php elseif ($request['status'] === 'rejected'): ?>

                                    <span class="badge bg-danger">
                                        Отклонено
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-dark">

                                        <?= htmlspecialchars($request['status']) ?>

                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="d-flex gap-2">

                                    <a
                                        href="../requests/view.php?id=<?= $request['id'] ?>"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Открыть
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-secondary mb-0">

                Заявок пока нет

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
