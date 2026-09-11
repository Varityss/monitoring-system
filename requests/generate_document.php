<?php

function generateRequestDocument(PDO $pdo, int $requestId): bool
{
    $stmt = $pdo->prepare("SELECT requests.*, COALESCE(requests.recipient_name_snapshot, users.full_name, users.login) AS recipient_name FROM requests LEFT JOIN users ON users.id = requests.user_id WHERE requests.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if (!$request) { return false; }

    $equipmentStmt = $pdo->prepare("SELECT COALESCE(inventory_number_snapshot, equipment.inventory_number) AS inventory_number, COALESCE(serial_number_snapshot, equipment.serial_number) AS serial_number, COALESCE(model_snapshot, equipment.model) AS model FROM equipment_issues LEFT JOIN equipment ON equipment.id = equipment_issues.equipment_id WHERE equipment_issues.request_id = ? ORDER BY equipment_issues.id");
    $equipmentStmt->execute([$requestId]);
    $equipment = $equipmentStmt->fetchAll();
    if (!$equipment) { throw new RuntimeException('Нельзя сформировать акт без выданного оборудования'); }

    $types = ['lesson' => 'Учебное занятие', 'work' => 'Рабочее использование', 'competition' => 'Соревнование', 'home' => 'Временная выдача домой'];
    $h = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $rows = '';
    foreach ($equipment as $index => $item) {
        $rows .= '<tr><td>' . ($index + 1) . '</td><td>' . $h($item['inventory_number']) . '</td><td>' . $h($item['serial_number']) . '</td><td>' . $h($item['model']) . '</td></tr>';
    }
    $place = $request['cabinet'] ?: $request['location'];
    $html = '<!doctype html><html lang="ru"><head><meta charset="UTF-8"><title>Акт выдачи #' . $requestId . '</title><style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;color:#111}h1{text-align:center}table{width:100%;border-collapse:collapse;margin-top:25px}th,td{border:1px solid #222;padding:8px}.signatures{display:flex;justify-content:space-between;margin-top:70px}.line{width:40%;border-top:1px solid #222;padding-top:8px;text-align:center}</style></head><body><h1>АКТ ВЫДАЧИ ОБОРУДОВАНИЯ</h1><p>Получатель: <strong>' . $h($request['recipient_name']) . '</strong></p><p>Тип заявки: ' . $h($types[$request['request_type']] ?? $request['request_type']) . '</p><p>Место использования: ' . $h($place) . '</p><p>Срок: ' . $h($request['issued_from']) . ' — ' . $h($request['issued_until']) . '</p><p>Цель: ' . nl2br($h($request['purpose'])) . '</p><table><thead><tr><th>№</th><th>Инвентарный номер</th><th>Серийный номер</th><th>Модель</th></tr></thead><tbody>' . $rows . '</tbody></table><div class="signatures"><div class="line">Получатель</div><div class="line">Ответственный</div></div></body></html>';

    if (!is_dir(DOCUMENT_STORAGE_PATH) && !mkdir(DOCUMENT_STORAGE_PATH, 0700, true) && !is_dir(DOCUMENT_STORAGE_PATH)) { throw new RuntimeException('Не удалось создать закрытое хранилище документов'); }
    $storageKey = 'request_' . $requestId . '_' . bin2hex(random_bytes(16)) . '.html';
    $path = DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $storageKey;
    if (file_put_contents($path, $html, LOCK_EX) === false) { throw new RuntimeException('Не удалось сохранить акт'); }
    try {
        $insert = $pdo->prepare('INSERT INTO request_documents (request_id, file_name, storage_key, original_name) VALUES (?, ?, ?, ?)');
        $insert->execute([$requestId, $storageKey, $storageKey, 'Акт выдачи #' . $requestId]);
    } catch (Throwable $error) {
        unlink($path);
        throw $error;
    }
    return true;
}
