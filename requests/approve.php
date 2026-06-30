<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

require __DIR__ . '/generate_document.php';

requireAdmin();
requirePostRequest();
verifyCsrfToken();

$request_id = (int)$_GET['id'];

$stmt = $pdo->prepare("

    SELECT *

    FROM requests

    WHERE id = ?

");

$stmt->execute([$request_id]);

$request = $stmt->fetch();

if (!$request) {

    die('Заявка не найдена');

}

if ($request['status'] !== 'pending') {

    die('Заявка уже обработана');

}

$equipmentList = [];
$postedEquipmentIds = array_values(array_unique(array_filter(array_map(
    'intval',
    $_POST['equipment_ids'] ?? []
))));

if (!empty($postedEquipmentIds)) {

    $placeholders = implode(',', array_fill(0, count($postedEquipmentIds), '?'));

    $equipmentStmt = $pdo->prepare("

        SELECT *

        FROM equipment

        WHERE id IN ($placeholders)
        AND status = 'available'
        AND archived = 0

        ORDER BY id ASC

    ");

    $equipmentStmt->execute($postedEquipmentIds);

    $equipmentList = $equipmentStmt->fetchAll();

} elseif ($request['equipment_selection_type'] === 'auto') {

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

} else {

    $equipmentStmt = $pdo->prepare("

        SELECT equipment.*

        FROM request_equipment

        LEFT JOIN equipment
        ON request_equipment.equipment_id = equipment.id

        WHERE request_equipment.request_id = ?
        AND equipment.status = 'available'
        AND equipment.archived = 0

    ");

    $equipmentStmt->execute([$request_id]);

    $equipmentList = $equipmentStmt->fetchAll();

}

if (!empty($postedEquipmentIds) && count($equipmentList) !== (int)$request['requested_count']) {

    die('Количество отсканированного оборудования не совпадает с заявкой');

}

if (count($equipmentList) < $request['requested_count']) {

    die('Недостаточно свободного оборудования');

}

try {

    $pdo->beginTransaction();

    $clearRelations = $pdo->prepare("
        DELETE FROM request_equipment
        WHERE request_id = ?
    ");

    $clearRelations->execute([$request_id]);

    $updateRequest = $pdo->prepare("

        UPDATE requests

        SET status = 'issued'

        WHERE id = ?

    ");

    $updateRequest->execute([$request_id]);

    foreach ($equipmentList as $equipment) {

        $insertRelation = $pdo->prepare("

            INSERT INTO request_equipment (

                request_id,
                equipment_id,
                issued_at,
                status

            )

            VALUES (?, ?, NOW(), 'issued')

        ");

        $insertRelation->execute([

            $request_id,
            $equipment['id']

        ]);

        $updateEquipment = $pdo->prepare("

            UPDATE equipment

            SET status = 'issued'

            WHERE id = ?

        ");

        $updateEquipment->execute([

            $equipment['id']

        ]);

        $insertIssue = $pdo->prepare("

            INSERT INTO equipment_issues (

                equipment_id,
                request_id,
                issued_at,
                status

            )

            VALUES (?, ?, NOW(), 'issued')

        ");

        $insertIssue->execute([

            $equipment['id'],
            $request_id

        ]);

    }

    generateRequestDocument($pdo, $request_id);

    logActivity($pdo, 'issue', 'request', $request_id, 'Заявка выдана, позиций: ' . count($equipmentList));

    $pdo->commit();

    redirect('requests/view.php?id=' . $request_id);

} catch (Exception $e) {

    $pdo->rollBack();

    die($e->getMessage());

}
