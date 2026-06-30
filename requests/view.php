<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("

    SELECT requests.*, users.login

    FROM requests

    LEFT JOIN users
    ON requests.user_id = users.id

    WHERE requests.id = ?

");

$stmt->execute([$id]);

$request = $stmt->fetch();

$documentStmt = $pdo->prepare("

    SELECT *

    FROM request_documents

    WHERE request_id = ?

    ORDER BY id DESC

    LIMIT 1

");

$documentStmt->execute([$id]);

$document = $documentStmt->fetch();

if (!$request) {

    die('Заявка не найдена');

}

$equipmentStmt = $pdo->prepare("

    SELECT *

    FROM equipment

    WHERE status = 'available'
    AND archived = 0

    ORDER BY id ASC

    LIMIT ?

");

$equipmentStmt->bindValue(

    1,
    (int)$request['requested_count'],
    PDO::PARAM_INT

);

$equipmentStmt->execute();

$equipmentList = $equipmentStmt->fetchAll();

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            Заявка #<?= $request['id'] ?>
        </h1>

        <div class="text-muted">

            <?= htmlspecialchars($request['login']) ?>

        </div>

    </div>

    <a
        href="index.php"
        class="btn btn-outline-dark"
    >
        Назад
    </a>

</div>

<div class="row g-4">

    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <h4 class="mb-4">
                    Информация
                </h4>

                <div class="mb-3">

                    <small class="text-muted d-block">
                        Тип заявки
                    </small>

                    <strong>

                        <?php

                        $types = [

                            'lesson' => 'Урок',
                            'work' => 'Работа',
                            'competition' => 'Соревнование',
                            'home' => 'Домой'

                        ];

                        echo $types[$request['request_type']] ?? 'Другое';

                        ?>

                    </strong>

                </div>

                <div class="mb-3">

                    <small class="text-muted d-block">
                        Количество ноутбуков
                    </small>

                    <strong>
                        <?= $request['requested_count'] ?>
                    </strong>

                </div>

                <?php if (!empty($request['cabinet'])): ?>

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Кабинет
                        </small>

                        <strong>
                            <?= htmlspecialchars($request['cabinet']) ?>
                        </strong>

                    </div>

                <?php endif; ?>

                <?php if (!empty($request['location'])): ?>

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Дислокация
                        </small>

                        <strong>
                            <?= htmlspecialchars($request['location']) ?>
                        </strong>

                    </div>

                <?php endif; ?>

                <?php if (!empty($request['lesson_date'])): ?>

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Дата занятия
                        </small>

                        <strong>
                            <?= htmlspecialchars($request['lesson_date']) ?>
                        </strong>

                    </div>

                <?php endif; ?>

                <?php if (!empty($request['start_time'])): ?>

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Время
                        </small>

                        <strong>

                            <?= htmlspecialchars($request['start_time']) ?>

                            —

                            <?= htmlspecialchars($request['end_time']) ?>

                        </strong>

                    </div>

                <?php endif; ?>

                <?php if (!empty($request['issued_from'])): ?>

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Период использования
                        </small>

                        <strong>

                            <?= htmlspecialchars($request['issued_from']) ?>

                            —

                            <?= htmlspecialchars($request['issued_until']) ?>

                        </strong>

                    </div>

                <?php endif; ?>

                <?php if (!empty($request['purpose'])): ?>

                    <div class="mb-3">

                        <small class="text-muted d-block">
                            Цель использования
                        </small>

                        <div>
                            <?= nl2br(htmlspecialchars($request['purpose'])) ?>
                        </div>

                    </div>

                <?php endif; ?>

                <div class="mb-3">

                    <small class="text-muted d-block">
                        Комментарий
                    </small>

                    <div>

                        <?= !empty($request['comment'])
                            ? nl2br(htmlspecialchars($request['comment']))
                            : 'Нет комментария'
                        ?>

                    </div>

                </div>

                <div>

                    <small class="text-muted d-block">
                        Статус
                    </small>

                    <?php if ($request['status'] === 'pending'): ?>

                        <span class="badge bg-warning text-dark">
                            Ожидание
                        </span>

                    <?php elseif (in_array($request['status'], ['approved', 'issued'], true)): ?>

                        <span class="badge bg-success">
                            Выдана
                        </span>

                    <?php elseif (in_array($request['status'], ['completed', 'returned'], true)): ?>

                        <span class="badge bg-secondary">
                            Возвращена
                        </span>

                    <?php elseif ($request['status'] === 'rejected'): ?>

                        <span class="badge bg-danger">
                            Отклонена
                        </span>

                    <?php else: ?>

                        <span class="badge bg-dark">
                            <?= e(requestStatusLabel($request['status'])) ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h4 class="mb-0">
                        Предлагаемое оборудование
                    </h4>

                    <span class="badge bg-primary">

                        <?= count($equipmentList) ?>

                    </span>

                </div>

                <?php if (count($equipmentList) > 0): ?>

                    <?php if ($request['status'] === 'pending'): ?>

                        <div class="border rounded p-3 mb-4 bg-light">

                            <label class="form-label">
                                Сканирование оборудования для выдачи
                            </label>

                            <input
                                type="text"
                                id="issue-scan-input"
                                class="form-control"
                                placeholder="Сканируйте штрихкод, инвентарный или серийный номер"
                                autocomplete="off"
                            >

                            <div id="issue-scan-message" class="small mt-2 text-muted">
                                Нужно выбрать: <?= (int)$request['requested_count'] ?>
                            </div>

                            <form
                                method="POST"
                                action="approve.php?id=<?= $request['id'] ?>"
                                id="issue-scan-form"
                                class="mt-3"
                            >
                                <?= csrfField() ?>

                                <div class="table-responsive">

                                    <table class="table table-sm align-middle mb-3">

                                        <thead>
                                            <tr>
                                                <th>Инвентарный</th>
                                                <th>Модель</th>
                                                <th>Кабинет</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody id="issue-scan-list"></tbody>

                                    </table>

                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-success w-100"
                                    id="issue-scan-submit"
                                    disabled
                                >
                                    Подтвердить выдачу отсканированного оборудования
                                </button>

                            </form>

                        </div>

                    <?php endif; ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        Инвентарный
                                    </th>

                                    <th>
                                        Модель
                                    </th>

                                    <th>
                                        Кабинет
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($equipmentList as $item): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars($item['inventory_number']) ?>
                                        </strong>

                                        <div class="small text-muted">

                                            <?= htmlspecialchars($item['serial_number']) ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($item['model']) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($item['cabinet']) ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>
<div class="d-grid mt-4">

    <?php if ($request['status'] === 'pending'): ?>

        <a
            href="approve.php?id=<?= $request['id'] ?>"
            class="btn btn-success btn-lg"
            onclick="return confirm('Выдать оборудование?')"
        >
            Выдать оборудование
        </a>

    <?php elseif (in_array($request['status'], ['approved', 'issued'], true)): ?>

        <div class="alert alert-success mb-0 text-center">

            <strong>
                Оборудование выдано
            </strong>

        </div>

    <?php elseif (in_array($request['status'], ['completed', 'returned'], true)): ?>

        <div class="alert alert-secondary mb-0 text-center">

            Заявка завершена

        </div>

    <?php endif; ?>

</div>

                <?php else: ?>

                    <div class="alert alert-warning mb-0">

                        Нет доступного оборудования

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<?php if ($document): ?>

<div class="card border-0 shadow-sm mt-4">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h4 class="mb-1">
                    Документ заявки
                </h4>

                <div class="text-muted">

                    <?= htmlspecialchars($document['original_name']) ?>

                </div>

            </div>

            <div class="d-flex gap-2">

                <a
                    href="document.php?id=<?= $id ?>"
                    target="_blank"
                    class="btn btn-primary"
                >
                    Открыть
                </a>

                <a
                    href="document.php?id=<?= $id ?>"
                    download
                    class="btn btn-outline-dark"
                >
                    Скачать
                </a>

            </div>

        </div>

    </div>

</div>

<?php endif; ?>

<script>
(() => {
    const input = document.getElementById('issue-scan-input');
    const list = document.getElementById('issue-scan-list');
    const form = document.getElementById('issue-scan-form');
    const submit = document.getElementById('issue-scan-submit');
    const message = document.getElementById('issue-scan-message');
    const requiredCount = <?= (int)$request['requested_count'] ?>;

    if (!input || !list || !form || !submit || !message) {
        return;
    }

    const selected = new Map();

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function render() {
        list.innerHTML = '';

        selected.forEach((item, id) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <strong>${escapeHtml(item.inventory_number)}</strong>
                    <input type="hidden" name="equipment_ids[]" value="${id}">
                    <div class="small text-muted">${escapeHtml(item.barcode || item.serial_number)}</div>
                </td>
                <td>${escapeHtml(item.model)}</td>
                <td>${escapeHtml(item.cabinet)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${id}">
                        Убрать
                    </button>
                </td>
            `;
            list.appendChild(row);
        });

        submit.disabled = selected.size !== requiredCount;
        message.className = selected.size === requiredCount
            ? 'small mt-2 text-success'
            : 'small mt-2 text-muted';
        message.textContent = `Выбрано: ${selected.size} из ${requiredCount}`;
    }

    async function lookup(code) {
        const response = await fetch(`../equipment/lookup.php?code=${encodeURIComponent(code)}`);
        return response.json();
    }

    let scanTimer = null;

    function looksLikeQrScan(value) {
        return /(?:^|[?&])token=/.test(value)
            || /\/qr\.php\?/i.test(value)
            || /^[a-f0-9]{32}$/i.test(value);
    }

    input.addEventListener('input', () => {
        clearTimeout(scanTimer);

        const code = input.value.trim();

        if (!looksLikeQrScan(code)) {
            return;
        }

        scanTimer = setTimeout(() => {
            if (input.value.trim() !== '') {
                input.dispatchEvent(new KeyboardEvent('keydown', {
                    key: 'Enter',
                    bubbles: true
                }));
            }
        }, 350);
    });

    input.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        const code = input.value.trim();
        input.value = '';

        if (!code) {
            return;
        }

        if (selected.size >= requiredCount) {
            message.className = 'small mt-2 text-warning';
            message.textContent = 'Нужное количество уже выбрано';
            return;
        }

        try {
            const result = await lookup(code);

            if (!result.ok) {
                message.className = 'small mt-2 text-danger';
                message.textContent = result.message;
                return;
            }

            const equipment = result.equipment;

            if (selected.has(String(equipment.id))) {
                message.className = 'small mt-2 text-warning';
                message.textContent = 'Это оборудование уже добавлено';
                return;
            }

            selected.set(String(equipment.id), equipment);
            render();
        } catch (error) {
            message.className = 'small mt-2 text-danger';
            message.textContent = 'Не удалось выполнить поиск оборудования';
        }
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove]');

        if (!button) {
            return;
        }

        selected.delete(button.dataset.remove);
        render();
        input.focus();
    });

    input.focus();
    render();
})();
</script>

<script>
(() => {
    const csrfToken = <?= json_encode(csrfToken()) ?>;

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="approve.php?id="]');

        if (!link) {
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
