<?php

require_once __DIR__ . '/../includes/session.php';
require '../includes/auth.php';

requireAdmin();
require_once '../includes/db.php';
$currentTime = time();
$stmt = $pdo->query("
    SELECT requests.*, users.login

    FROM requests

    LEFT JOIN users
    ON requests.user_id = users.id

    ORDER BY requests.id DESC
");

$requests = $stmt->fetchAll();

foreach ($requests as &$requestRow) {
    $endTimestamp = requestEndTimestamp($requestRow);
    if (in_array($requestRow['status'], ['approved', 'issued'], true) && $endTimestamp !== null && $currentTime > $endTimestamp) {
        $requestRow['status'] = 'overdue';
    }
}
unset($requestRow);

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <h1>
        Заявки
    </h1>

    <a
        href="create.php"
        class="btn btn-primary"
    >
        Новая заявка
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Преподаватель</th>

                        <th>Кабинет</th>

                        <th>Ноутбуков</th>

                        <th>Дата</th>

                        <th>Время</th>
                        <th>
    Осталось
</th>


                        <th>Статус</th>
                        <th width="220">
                               Действия
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($requests as $request): ?>

                    <tr>

                        <td>
                            <?= $request['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($request['login']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($request['cabinet']) ?>
                        </td>

                        <td>
                            <?= $request['requested_count'] ?>
                        </td>

                        <td>
                            <?= $request['lesson_date'] ?>
                        </td>

                        <td>
                            <?= $request['start_time'] ?>
                            —
                            <?= $request['end_time'] ?>
                        </td>
                        <td>

<?php

$currentTime = time();

$endTime = requestEndTimestamp($request);

if (

    in_array($request['status'], ['completed', 'returned'], true)

) {

    echo '<span class="text-muted">Завершено</span>';

}

elseif (

    $request['status'] === 'rejected'

) {

    echo '<span class="text-muted">Отклонено</span>';

}

elseif (

    $request['status'] === 'overdue'

) {

    echo '<span class="text-danger fw-semibold">Просрочено</span>';

}

elseif ($endTime === null) {

    echo '<span class="text-muted">—</span>';

}

elseif ($currentTime > $endTime) {

    echo '<span class="text-danger fw-semibold">Время вышло</span>';

}

else {

    $secondsLeft = $endTime - $currentTime;

    $hours = floor($secondsLeft / 3600);

    $minutes = floor(($secondsLeft % 3600) / 60);

    echo
        '<span class="text-success fw-semibold">' .
        $hours . ' ч. ' .
        $minutes . ' мин.' .
        '</span>';
}

?>

</td>

                        <td>

                           <?php if ($request['status'] === 'pending'): ?>

    <span class="badge bg-warning text-dark">
        Ожидание
    </span>

<?php elseif (in_array($request['status'], ['approved', 'issued'], true)): ?>

    <span class="badge bg-success">
        Активна
    </span>

<?php elseif ($request['status'] === 'overdue'): ?>

    <?php

    $secondsOverdue = $endTime === null ? 0 : $currentTime - $endTime;

    $hours = floor($secondsOverdue / 3600);

    $minutes = floor(($secondsOverdue % 3600) / 60);

    ?>

    <span class="text-danger fw-bold">

        - <?= $hours ?> ч.
        <?= $minutes ?> мин.

    </span>

<?php elseif (in_array($request['status'], ['completed', 'returned'], true)): ?>

    <span class="badge bg-secondary">
        Завершена
    </span>

<?php else: ?>

    <span class="badge bg-dark">
        Отклонена
    </span>

<?php endif; ?>

                        </td>
                        <td>

                        <a
                 href="view.php?id=<?= $request['id'] ?>"
                 class="btn btn-sm btn-outline-primary"
            >
                  Открыть
            </a>

            <?php if (

    in_array($request['status'], ['approved', 'issued'], true)
    || $request['status'] === 'overdue'

): ?>

    <form method="POST" action="complete.php?id=<?= (int)$request['id'] ?>" class="d-flex gap-1" onsubmit="return confirm('Подтвердить массовый возврат оборудования?')">
        <?= csrfField() ?>
        <input type="hidden" name="return_notes" value="Массовый возврат подтверждён администратором">
        <button class="btn btn-sm btn-success">Завершить</button>
    </form>

<?php endif; ?>

    <?php if ($request['status'] === 'pending'): ?>

        <div class="d-flex gap-2">

            <form method="POST" action="approve.php?id=<?= (int)$request['id'] ?>"><?= csrfField() ?><button class="btn btn-sm btn-success">Одобрить</button></form>
            <form method="POST" action="reject.php?id=<?= (int)$request['id'] ?>" onsubmit="return confirm('Отклонить заявку?')"><?= csrfField() ?><button class="btn btn-sm btn-danger">Отклонить</button></form>

            

        </div>

    <?php else: ?>

        <span class="text-muted">
            —
        </span>

    <?php endif; ?>

</td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
(() => {
    const csrfToken = <?= json_encode(csrfToken()) ?>;
    const actionSelector = [
        'a[href^="approve.php?id="]',
        'a[href^="reject.php?id="]',
        'a[href^="complete.php?id="]'
    ].join(',');

    document.addEventListener('click', (event) => {
        const link = event.target.closest(actionSelector);

        if (!link) {
            return;
        }

        if (
            link.getAttribute('href').startsWith('reject.php')
            && !confirm('РћС‚РєР»РѕРЅРёС‚СЊ Р·Р°СЏРІРєСѓ?')
        ) {
            event.preventDefault();
            return;
        }

        event.preventDefault();

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = link.getAttribute('href');

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken;

        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    });
})();
</script>

<?php include '../includes/app_footer.php'; ?>
