<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';

requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrfToken();
        $requestType = inputEnum($_POST, 'request_type', ['lesson', 'event', 'competition', 'exam', 'other']);
        $cabinet = inputString($_POST, 'cabinet', 100, true);
        $requestedCount = inputPositiveInt($_POST, 'requested_count', 100);
        $lessonDate = strictDate(inputString($_POST, 'lesson_date', 10, true), 'Y-m-d');
        $startTime = inputString($_POST, 'start_time', 5, true);
        $endTime = inputString($_POST, 'end_time', 5, true);
        strictDate($startTime, 'H:i');
        strictDate($endTime, 'H:i');
        $comment = inputString($_POST, 'comment', 2000, false);

        $from = strictDate($lessonDate->format('Y-m-d') . ' ' . $startTime, 'Y-m-d H:i');
        $until = strictDate($lessonDate->format('Y-m-d') . ' ' . $endTime, 'Y-m-d H:i');
        if ($from->getTimestamp() < time()) {
            throw new InvalidArgumentException('Нельзя выбрать прошедшую дату или время');
        }
        if ($until <= $from) {
            throw new InvalidArgumentException('Время окончания должно быть позже времени начала');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "INSERT INTO requests
             (user_id, request_type, equipment_selection_type, equipment_type, cabinet, requested_count,
              lesson_date, start_time, end_time, issued_from, issued_until, purpose, comment, status)
             VALUES (?, ?, 'auto', 'Ноутбук', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([
            currentUserId(),
            $requestType,
            $cabinet,
            $requestedCount,
            $lessonDate->format('Y-m-d'),
            $startTime,
            $endTime,
            $from->format('Y-m-d H:i:s'),
            $until->format('Y-m-d H:i:s'),
            $comment,
            $comment,
        ]);
        $requestId = (int)$pdo->lastInsertId();
        logActivity($pdo, 'create', 'request', $requestId, 'Администратор создал заявку');
        $pdo->commit();
        redirect('requests/index.php');
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        publicError($exception);
    }
}

include '../includes/app_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Новая заявка</h1>
        <div class="text-muted">Бронирование ноутбуков</div>
    </div>
    <a href="index.php" class="btn btn-outline-dark">Назад</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Тип заявки</label>
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
                    <label class="form-label">Кабинет использования</label>
                    <input type="text" name="cabinet" maxlength="100" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Количество ноутбуков</label>
                    <input type="number" name="requested_count" class="form-control" min="1" max="100" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Дата</label>
                    <input type="date" name="lesson_date" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Время начала</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Время окончания</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Комментарий</label>
                <textarea name="comment" maxlength="2000" class="form-control" rows="4"></textarea>
            </div>
            <button class="btn btn-primary">Создать заявку</button>
        </form>
    </div>
</div>

<?php include '../includes/app_footer.php'; ?>
