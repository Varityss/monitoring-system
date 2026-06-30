<?php

session_start();

require '../includes/auth.php';
require '../includes/db.php';

requireAdmin();

$request_id = (int)$_GET['id'];

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

    die('Заявка не найдена');

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

?>
<!DOCTYPE html>

<html lang="ru">

<head>

    <meta charset="UTF-8">

    <title>
        Акт выдачи
    </title>

    <style>

        body {

            font-family: Arial, sans-serif;

            width: 210mm;

            margin: auto;

            padding: 20mm;

            background: white;

            color: black;

        }

        h1 {

            text-align: center;

            margin-bottom: 40px;

        }

        .block {

            margin-bottom: 25px;

            line-height: 1.7;

            font-size: 16px;

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

            text-align: left;

        }

        .signatures {

            margin-top: 80px;

            display: flex;

            justify-content: space-between;

        }

        .signature-block {

            width: 40%;

        }

        .line {

            border-top: 1px solid black;

            margin-top: 50px;

            padding-top: 10px;

            text-align: center;

        }

        .stamp {

            margin-top: 60px;

            text-align: right;

        }

        .print-btn {

            position: fixed;

            top: 20px;

            right: 20px;

        }

        @media print {

            .print-btn {

                display: none;

            }

        }

    </style>

</head>

<body>
<div style="display:flex; align-items:center; margin-bottom:40px;">

    <img
        src="../assets/img/logo.png"
        style="
            width:90px;
            margin-right:25px;
        "
    >

    <div>

        <div style="font-size:22px; font-weight:bold;">

            ЧУ «ЕВРОПЕЙСКИЙ ВЫСШИЙ КОЛЛЕДЖ
ЦИФРОВЫХ ТЕХНОЛОГИЙ И ПРЕДПРИНИМАТЕЛЬСТВА»

        </div>

        <div style="margin-top:8px; color:#555;">

            Система учета оборудования

        </div>

    </div>

</div>
<button
    class="print-btn"
    onclick="window.print()"
>
    Печать / PDF
</button>

<h1>
    АКТ ВЫДАЧИ ОБОРУДОВАНИЯ
</h1>

<div class="block">

    Я,

    <strong>

        <?= htmlspecialchars($request['full_name']) ?>

    </strong>,

    получил оборудование во временное пользование
    и принимаю на себя ответственность за его сохранность
    и возврат в надлежащем состоянии.

</div>

<div class="block">

    <strong>
        Тип заявки:
    </strong>

    <?php

$types = [

    'lesson' => 'Учебное занятие',

    'work' => 'Рабочее использование',

    'competition' => 'Соревнование',

    'home' => 'Временная выдача домой'

];

echo $types[$request['request_type']]
    ?? 'Другое';

?>

</div>

<?php if (!empty($request['cabinet'])): ?>

<div class="block">

    <strong>
        Кабинет:
    </strong>

    <?= htmlspecialchars($request['cabinet']) ?>

</div>

<?php endif; ?>

<?php if (!empty($request['location'])): ?>

<div class="block">

    <strong>
        Дислокация:
    </strong>

    <?= htmlspecialchars($request['location']) ?>

</div>

<?php endif; ?>

<div class="block">

    <strong>
        Количество оборудование:
    </strong>

    <?= $request['requested_count'] ?>

</div>

<?php if (!empty($request['purpose'])): ?>

<div class="block">

    <strong>
        Цель использования:
    </strong>

    <br><br>

    <?= nl2br(htmlspecialchars($request['purpose'])) ?>

</div>

<?php endif; ?>

<table>

    <thead>

        <tr>

            <th>
                №
            </th>

            <th>
                Инвентарный номер
            </th>

            <th>
                Серийный номер
            </th>

            <th>
                Модель
            </th>

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

<div class="block" style="margin-top:40px;">

    Обязуюсь вернуть оборудование
    в установленный срок и обеспечить его сохранность.

</div>

<div class="signatures">

    <div class="signature-block">

        <div class="line">

            Получатель

        </div>

    </div>

    <div class="signature-block">

        <div class="line">

            Ответственный

        </div>

    </div>

</div>

<div class="stamp">

    <img
        src="../assets/img/stamp.png"
        style="
            width:180px;
            opacity:0.9;
        "
    >

</div>

</body>

</html>