<?php

session_start();
require '../includes/auth.php';

requireAdmin();
require '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_type = trim($_POST['request_type'] ?? 'lesson');
    $cabinet = trim($_POST['cabinet']);
    $requested_count = (int) $_POST['requested_count'];
    $lesson_date = $_POST['lesson_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $comment = trim($_POST['comment']);

    $startDateTime = strtotime($lesson_date . ' ' . $start_time);
    $endDateTime = strtotime($lesson_date . ' ' . $end_time);

    if ($requested_count < 1) {
        $error = 'Укажите количество оборудования';
    } elseif ($startDateTime === false || $endDateTime === false) {
        $error = 'Проверьте дату и время';
    } elseif ($startDateTime < time()) {
        $error = 'Нельзя выбрать прошедшую дату или время';
    } elseif ($endDateTime <= $startDateTime) {
        $error = 'Время окончания должно быть позже времени начала';
    }

    if (!$error) {

        $stmt = $pdo->prepare("
        INSERT INTO requests (

            user_id,
            request_type,
            cabinet,
            requested_count,
            lesson_date,
            start_time,
            end_time,
            issued_from,
            issued_until,
            comment

        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        $stmt->execute([

            $_SESSION['user_id'],
            $request_type,
            $cabinet,
            $requested_count,
            $lesson_date,
            $start_time,
            $end_time,
            $lesson_date . ' ' . $start_time,
            $lesson_date . ' ' . $end_time,
            $comment

        ]);

        logActivity($pdo, 'create', 'request', (int)$pdo->lastInsertId(), 'Администратор создал заявку');

        redirect('requests/index.php');

    }
}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            Новая заявка
        </h1>

        <div class="text-muted">
            Бронирование ноутбуков
        </div>

    </div>

    <a
        href="index.php"
        class="btn btn-outline-dark"
    >
        Назад
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Тип заявки
                </label>

                <select name="request_type" class="form-select" required>
                    <option value="lesson">Занятие</option>
                    <option value="event">Мероприятие</option>
                    <option value="competition">Соревнование</option>
                    <option value="exam">Экзамен</option>
                    <option value="other">Другое</option>
                </select>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Кабинет
                    </label>

                    <input
                        type="text"
                        name="cabinet"
                        class="form-control"
                        required
                    >

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Количество ноутбуков
                    </label>

                    <input
                        type="number"
                        name="requested_count"
                        class="form-control"
                        min="1"
                        required
                    >

                </div>

            </div>

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Дата занятия
                    </label>

                    <input
                        type="date"
                        name="lesson_date"
                        class="form-control"
                        required
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Время начала
                    </label>

                    <input
                        type="time"
                        name="start_time"
                        class="form-control"
                        required
                    >

                </div>

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Время окончания
                    </label>

                    <input
                        type="time"
                        name="end_time"
                        class="form-control"
                        required
                    >

                </div>

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Комментарий
                </label>

                <textarea
                    name="comment"
                    class="form-control"
                    rows="4"
                ></textarea>

            </div>

            <button class="btn btn-primary">
                Создать заявку
            </button>

        </form>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
