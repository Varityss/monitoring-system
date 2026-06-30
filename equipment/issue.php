<?php

session_start();
require '../includes/auth.php';

requireAdmin();
require '../includes/db.php';



$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM equipment
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$equipment = $stmt->fetch();

if (!$equipment) {

    redirect('equipment/index.php');

}

if ($equipment['status'] !== 'available') {

    die('Оборудование недоступно для выдачи');

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $issued_to = trim($_POST['issued_to']);
    $cabinet = trim($_POST['cabinet']);
    $notes = trim($_POST['notes']);

    $issue = $pdo->prepare("
        INSERT INTO equipment_issues (

            equipment_id,
            request_id,
            issued_to,
            cabinet,
            issued_by,
            notes,
            status

        )
        VALUES (?, ?, ?, ?, ?, 'issued')
    ");

    $issue->execute([

        $id,
        0,
        $issued_to,
        $cabinet,
        $_SESSION['user_id'],
        $notes

    ]);

    $update = $pdo->prepare("
    UPDATE equipment

    SET

        status = 'issued',
        cabinet = ?

    WHERE id = ?
");

$update->execute([

    $cabinet,
    $id

]);

    

    logActivity($pdo, 'issue', 'equipment', $id, 'Оборудование выдано: ' . $equipment['inventory_number']);

    redirect('equipment/view.php?id=' . $id);
}

include '../includes/app_header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="mb-1">
            Выдача оборудования
        </h1>

        <div class="text-muted">
            <?= htmlspecialchars($equipment['inventory_number']) ?>
        </div>

    </div>

    <a
        href="view.php?id=<?= $equipment['id'] ?>"
        class="btn btn-outline-dark"
    >
        Назад
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Кому выдается
                </label>

                <input
                    type="text"
                    name="issued_to"
                    class="form-control"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Кабинет
                </label>

                <input
                    type="text"
                    name="cabinet"
                    class="form-control"
                >

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Заметки
                </label>

                <textarea
                    name="notes"
                    class="form-control"
                    rows="4"
                ></textarea>

            </div>

            <button class="btn btn-primary">
                Выдать оборудование
            </button>

        </form>

    </div>

</div>

<?php include '../includes/app_footer.php'; ?>
