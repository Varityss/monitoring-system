<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/db.php';

$issueIds = [2, 3, 4];
$requestIds = [1, 2];
$operationKey = 'legacy-reconcile-20260911';
$technicalNote = 'Техническое закрытие старой записи после сверки завершённой заявки; дата взята из requests.updated_at';

$pdo->beginTransaction();
try {
    $issues = $pdo->query(
        "SELECT ei.id, ei.status, ei.return_notes, e.status AS equipment_status, r.status AS request_status
         FROM equipment_issues ei
         JOIN equipment e ON e.id = ei.equipment_id
         JOIN requests r ON r.id = ei.request_id
         WHERE ei.id IN (2, 3, 4)
         ORDER BY ei.id
         FOR UPDATE"
    )->fetchAll();
    if (count($issues) !== count($issueIds)) {
        throw new RuntimeException('Не найдены все три согласованные старые выдачи');
    }
    foreach ($issues as $issue) {
        if ($issue['status'] === 'returned' && $issue['return_notes'] === $technicalNote) {
            continue;
        }
        if ($issue['status'] !== 'issued' || $issue['equipment_status'] !== 'available' || $issue['request_status'] !== 'completed') {
            throw new RuntimeException('Состояние выдачи #' . (int)$issue['id'] . ' изменилось; автоматическая корректировка остановлена');
        }
    }

    $relations = $pdo->query(
        "SELECT re.id, re.status, r.status AS request_status
         FROM request_equipment re
         JOIN requests r ON r.id = re.request_id
         WHERE re.request_id IN (1, 2)
         ORDER BY re.id
         FOR UPDATE"
    )->fetchAll();
    if (count($relations) !== 8) {
        throw new RuntimeException('Количество старых связей заявок изменилось; ожидалось 8');
    }
    foreach ($relations as $relation) {
        if ($relation['status'] === 'returned') {
            continue;
        }
        if ($relation['status'] !== 'reserved' || $relation['request_status'] !== 'completed') {
            throw new RuntimeException('Состояние связи #' . (int)$relation['id'] . ' изменилось; автоматическая корректировка остановлена');
        }
    }

    $updateIssues = $pdo->prepare(
        "UPDATE equipment_issues ei
         JOIN requests r ON r.id = ei.request_id
         JOIN equipment e ON e.id = ei.equipment_id
         SET ei.status = 'returned',
             ei.returned_at = COALESCE(ei.returned_at, r.updated_at),
             ei.return_condition = COALESCE(ei.return_condition, e.condition_status, 'good'),
             ei.return_notes = ?,
             ei.inventory_number_snapshot = COALESCE(ei.inventory_number_snapshot, e.inventory_number),
             ei.serial_number_snapshot = COALESCE(ei.serial_number_snapshot, e.serial_number),
             ei.model_snapshot = COALESCE(ei.model_snapshot, e.model)
         WHERE ei.id IN (2, 3, 4)
           AND ei.status = 'issued'
           AND r.status = 'completed'
           AND e.status = 'available'"
    );
    $updateIssues->execute([$technicalNote]);

    $updatedRelations = $pdo->exec(
        "UPDATE request_equipment re
         JOIN requests r ON r.id = re.request_id
         SET re.status = 'returned',
             re.returned_at = COALESCE(re.returned_at, r.updated_at)
         WHERE re.request_id IN (1, 2)
           AND re.status = 'reserved'
           AND r.status = 'completed'"
    );

    $alreadyLogged = $pdo->prepare('SELECT COUNT(*) FROM activity_logs WHERE operation_key = ?');
    $alreadyLogged->execute([$operationKey]);
    if ((int)$alreadyLogged->fetchColumn() === 0) {
        $log = $pdo->prepare(
            "INSERT INTO activity_logs
             (user_id, action, entity_type, entity_id, description, details_json, operation_key)
             VALUES
             (NULL, 'reconcile_legacy', 'request', 1, ?, ?, ?),
             (NULL, 'reconcile_legacy', 'request', 2, ?, ?, ?)"
        );
        $details = json_encode(
            ['approved_issue_ids' => $issueIds, 'approved_request_ids' => $requestIds],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $log->execute([
            'Согласованы старые выдачи №2–4 и связи завершённой заявки',
            $details,
            $operationKey,
            'Согласованы старые связи завершённой заявки',
            $details,
            $operationKey,
        ]);
    }

    $pdo->commit();
    echo 'Legacy issues updated: ' . $updateIssues->rowCount() . "\n";
    echo 'Legacy relations updated: ' . $updatedRelations . "\n";
    echo "Legacy reconciliation complete\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
