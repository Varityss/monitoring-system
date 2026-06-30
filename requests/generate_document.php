<?php

function generateRequestDocument($pdo, $request_id)
{

    $stmt = $pdo->prepare("

        SELECT requests.*, users.full_name

        FROM requests

        LEFT JOIN users
        ON requests.user_id = users.id

        WHERE requests.id = ?

    ");

    $stmt->execute([$request_id]);

    $request = $stmt->fetch();

    if (!$request) {

        return false;

    }

    $equipmentStmt = $pdo->prepare("

        SELECT equipment.*

        FROM request_equipment

        LEFT JOIN equipment
        ON request_equipment.equipment_id = equipment.id

        WHERE request_equipment.request_id = ?

    ");

    $equipmentStmt->execute([$request_id]);

    $equipmentList = $equipmentStmt->fetchAll();

    $types = [

        'lesson' => 'Учебное занятие',

        'work' => 'Рабочее использование',

        'competition' => 'Соревнование',

        'home' => 'Временная выдача домой'

    ];

    ob_start();

?>

<!DOCTYPE html>

<html lang="ru">

<head>

<meta charset="UTF-8">

<style>

body {

    font-family: Arial, sans-serif;

    padding: 40px;

    color: black;

}

h1 {

    text-align: center;

    margin-bottom: 40px;

}

table {

    width: 100%;

    border-collapse: collapse;

    margin-top: 20px;

}

table th,
table td {

    border: 1px solid black;

    padding: 10px;

}

.signature-area {

    margin-top: 80px;

    display: flex;

    justify-content: space-between;

}

.signature {

    width: 40%;

    text-align: center;

}

.line {

    border-top: 1px solid black;

    margin-top: 60px;

    padding-top: 10px;

}

</style>

</head>

<body>

<h1>
    АКТ ВЫДАЧИ ОБОРУДОВАНИЯ
</h1>

<p>

Я,

<strong>

<?= htmlspecialchars($request['full_name']) ?>

</strong>,

получил оборудование во временное пользование
и принимаю ответственность за его сохранность.

</p>

<p>

<strong>
Тип заявки:
</strong>

<?= $types[$request['request_type']] ?? 'Другое' ?>

</p>

<?php if (!empty($request['cabinet'])): ?>

<p>

<strong>
Кабинет:
</strong>

<?= htmlspecialchars($request['cabinet']) ?>

</p>

<?php endif; ?>

<?php if (!empty($request['location'])): ?>

<p>

<strong>
Дислокация:
</strong>

<?= htmlspecialchars($request['location']) ?>

</p>

<?php endif; ?>

<table>

<thead>

<tr>

<th>№</th>

<th>Инвентарный</th>

<th>Серийный</th>

<th>Модель</th>

</tr>

</thead>

<tbody>

<?php foreach ($equipmentList as $index => $item): ?>

<tr>

<td>
<?= $index + 1 ?>
</td>

<td>
<?= htmlspecialchars($item['inventory_number']) ?>
</td>

<td>
<?= htmlspecialchars($item['serial_number']) ?>
</td>

<td>
<?= htmlspecialchars($item['model']) ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<div class="signature-area">

<div class="signature">

<div class="line">
Получатель
</div>

</div>

<div class="signature">

<div class="line">
Ответственный
</div>

</div>

</div>

</body>

</html>

<?php

    $html = ob_get_clean();

    $file_name = 'request_' . $request_id . '.html';

    $file_path = '../uploads/documents/' . $file_name;

    file_put_contents($file_path, $html);

    $insertStmt = $pdo->prepare("

        INSERT INTO request_documents (

            request_id,
            file_name,
            original_name

        )

        VALUES (?, ?, ?)

    ");

    $insertStmt->execute([

        $request_id,
        $file_name,
        'Акт выдачи #' . $request_id

    ]);

    return true;

}