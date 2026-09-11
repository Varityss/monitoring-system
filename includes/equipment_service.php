<?php

require_once __DIR__ . '/functions.php';

function issueRequestEquipment(PDO $pdo, int $requestId, array $postedEquipmentIds): array
{
    $pdo->beginTransaction();
    try {
        $requestStmt = $pdo->prepare("SELECT requests.*, users.full_name FROM requests JOIN users ON users.id = requests.user_id WHERE requests.id = ? FOR UPDATE");
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch();
        if (!$request) { throw new DomainException('Заявка не найдена'); }
        if ($request['status'] !== 'pending') { throw new DomainException('Заявка уже обработана'); }

        $requiredCount = (int)$request['requested_count'];
        if ($requiredCount < 1 || $requiredCount > 100) { throw new DomainException('Недопустимое количество оборудования'); }
        $equipmentType = $request['equipment_type'] ?: 'Ноутбук';

        if ($postedEquipmentIds) {
            if (count($postedEquipmentIds) !== $requiredCount) { throw new DomainException('Количество оборудования не совпадает с заявкой'); }
            $placeholders = implode(',', array_fill(0, count($postedEquipmentIds), '?'));
            $equipmentStmt = $pdo->prepare("SELECT * FROM equipment WHERE id IN ($placeholders) ORDER BY id FOR UPDATE");
            $equipmentStmt->execute($postedEquipmentIds);
        } elseif ($request['equipment_selection_type'] === 'manual') {
            $equipmentStmt = $pdo->prepare("SELECT equipment.* FROM request_equipment JOIN equipment ON equipment.id = request_equipment.equipment_id WHERE request_equipment.request_id = ? ORDER BY equipment.id FOR UPDATE");
            $equipmentStmt->execute([$requestId]);
        } else {
            $equipmentStmt = $pdo->prepare("SELECT * FROM equipment WHERE status = 'available' AND archived = 0 AND type = ? ORDER BY id LIMIT $requiredCount FOR UPDATE");
            $equipmentStmt->execute([$equipmentType]);
        }

        $equipmentList = $equipmentStmt->fetchAll();
        if (count($equipmentList) !== $requiredCount) { throw new DomainException('Недостаточно подходящего свободного оборудования'); }
        foreach ($equipmentList as $equipment) {
            if ($equipment['status'] !== 'available' || (int)$equipment['archived'] !== 0 || $equipment['type'] !== $equipmentType) {
                throw new DomainException('Оборудование недоступно или имеет неверный тип');
            }
        }

        $pdo->prepare('DELETE FROM request_equipment WHERE request_id = ?')->execute([$requestId]);
        $insertRelation = $pdo->prepare("INSERT INTO request_equipment (request_id, equipment_id, issued_at, status) VALUES (?, ?, NOW(), 'issued')");
        $insertIssue = $pdo->prepare("INSERT INTO equipment_issues (equipment_id, request_id, issued_to_snapshot, inventory_number_snapshot, serial_number_snapshot, model_snapshot, issued_by, issued_at, status, operation_key) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'issued', ?)");
        $updateEquipment = $pdo->prepare("UPDATE equipment SET status = 'issued' WHERE id = ? AND status = 'available' AND archived = 0");
        $operationKey = bin2hex(random_bytes(16));
        foreach ($equipmentList as $equipment) {
            $insertRelation->execute([$requestId, (int)$equipment['id']]);
            $insertIssue->execute([(int)$equipment['id'], $requestId, $request['full_name'], $equipment['inventory_number'], $equipment['serial_number'], $equipment['model'], currentUserId(), $operationKey]);
            $updateEquipment->execute([(int)$equipment['id']]);
            if ($updateEquipment->rowCount() !== 1) { throw new DomainException('Состояние оборудования изменилось. Повторите операцию.'); }
        }

        $updateRequest = $pdo->prepare("UPDATE requests SET status = 'issued', recipient_name_snapshot = ? WHERE id = ? AND status = 'pending'");
        $updateRequest->execute([$request['full_name'], $requestId]);
        if ($updateRequest->rowCount() !== 1) { throw new DomainException('Состояние заявки изменилось.'); }
        logActivity($pdo, 'issue', 'request', $requestId, 'Выдано позиций: ' . count($equipmentList));
        $pdo->commit();
        return $equipmentList;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $error;
    }
}

function rejectRequest(PDO $pdo, int $requestId): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT status FROM requests WHERE id = ? FOR UPDATE');
        $stmt->execute([$requestId]);
        $status = $stmt->fetchColumn();
        if ($status === false) { throw new DomainException('Заявка не найдена'); }
        if ($status !== 'pending') { throw new DomainException('Заявка уже обработана'); }
        $update = $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $update->execute([$requestId]);
        if ($update->rowCount() !== 1) { throw new DomainException('Состояние заявки изменилось.'); }
        logActivity($pdo, 'reject', 'request', $requestId, 'Заявка отклонена');
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $error;
    }
}

function issueEquipmentDirect(PDO $pdo, int $equipmentId, string $issuedTo, ?string $destination, ?string $notes): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM equipment WHERE id = ? FOR UPDATE');
        $stmt->execute([$equipmentId]);
        $equipment = $stmt->fetch();
        if (!$equipment) { throw new DomainException('Оборудование не найдено'); }
        if ($equipment['status'] !== 'available' || (int)$equipment['archived'] !== 0) { throw new DomainException('Оборудование недоступно для выдачи'); }
        $operationKey = bin2hex(random_bytes(16));
        $issue = $pdo->prepare("INSERT INTO equipment_issues (equipment_id, request_id, issued_to, issued_to_snapshot, inventory_number_snapshot, serial_number_snapshot, model_snapshot, cabinet, issued_by, notes, issued_at, status, operation_key) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'issued', ?)");
        $issue->execute([$equipmentId, $issuedTo, $issuedTo, $equipment['inventory_number'], $equipment['serial_number'], $equipment['model'], $destination, currentUserId(), $notes, $operationKey]);
        $update = $pdo->prepare("UPDATE equipment SET status = 'issued' WHERE id = ? AND status = 'available' AND archived = 0");
        $update->execute([$equipmentId]);
        if ($update->rowCount() !== 1) { throw new DomainException('Состояние оборудования изменилось.'); }
        logActivity($pdo, 'issue', 'equipment', $equipmentId, 'Прямая выдача: ' . $equipment['inventory_number']);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $error;
    }
}

function returnEquipment(PDO $pdo, int $equipmentId, string $condition = 'good', ?string $notes = null): ?int
{
    $pdo->beginTransaction();
    try {
        $equipmentStmt = $pdo->prepare('SELECT * FROM equipment WHERE id = ? FOR UPDATE');
        $equipmentStmt->execute([$equipmentId]);
        $equipment = $equipmentStmt->fetch();
        if (!$equipment) { throw new DomainException('Оборудование не найдено'); }

        $issueStmt = $pdo->prepare("SELECT * FROM equipment_issues WHERE equipment_id = ? AND status = 'issued' ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $issueStmt->execute([$equipmentId]);
        $issue = $issueStmt->fetch();
        if (!$issue) { throw new DomainException('Активная выдача не найдена'); }

        $newStatus = $condition === 'good' ? 'available' : 'repair';
        $pdo->prepare("UPDATE equipment_issues SET status = 'returned', returned_at = NOW(), returned_by = ?, return_condition = ?, return_notes = ? WHERE id = ? AND status = 'issued'")->execute([currentUserId(), $condition, $notes, (int)$issue['id']]);
        $pdo->prepare('UPDATE equipment SET status = ?, condition_status = ? WHERE id = ?')->execute([$newStatus, $condition, $equipmentId]);

        $requestId = $issue['request_id'] !== null ? (int)$issue['request_id'] : null;
        if ($requestId) {
            $pdo->prepare("UPDATE request_equipment SET status = 'returned', returned_at = NOW() WHERE request_id = ? AND equipment_id = ? AND status = 'issued'")->execute([$requestId, $equipmentId]);
            $remaining = $pdo->prepare("SELECT COUNT(*) FROM request_equipment WHERE request_id = ? AND status = 'issued'");
            $remaining->execute([$requestId]);
            if ((int)$remaining->fetchColumn() === 0) {
                $pdo->prepare("UPDATE requests SET status = 'returned' WHERE id = ? AND status IN ('approved', 'issued', 'overdue')")->execute([$requestId]);
            }
        }
        logActivity($pdo, 'return', 'equipment', $equipmentId, 'Возврат: ' . $equipment['inventory_number'] . '; состояние: ' . $condition);
        $pdo->commit();
        return $requestId;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $error;
    }
}

function archiveEquipment(PDO $pdo, int $equipmentId): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT status, archived FROM equipment WHERE id = ? FOR UPDATE');
        $stmt->execute([$equipmentId]);
        $equipment = $stmt->fetch();
        if (!$equipment) { throw new DomainException('Оборудование не найдено'); }
        if ($equipment['status'] === 'issued') { throw new DomainException('Нельзя архивировать выданное оборудование'); }
        $active = $pdo->prepare("SELECT COUNT(*) FROM equipment_issues WHERE equipment_id = ? AND status = 'issued'");
        $active->execute([$equipmentId]);
        if ((int)$active->fetchColumn() > 0) { throw new DomainException('Сначала закройте активную выдачу'); }
        $pdo->prepare('UPDATE equipment SET archived = 1 WHERE id = ?')->execute([$equipmentId]);
        logActivity($pdo, 'archive', 'equipment', $equipmentId, 'Оборудование отправлено в архив');
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $error;
    }
}

function returnRequestEquipment(PDO $pdo, int $requestId, string $notes): int
{
    $pdo->beginTransaction();
    try {
        $requestStmt = $pdo->prepare('SELECT status FROM requests WHERE id = ? FOR UPDATE');
        $requestStmt->execute([$requestId]);
        $status = $requestStmt->fetchColumn();
        if ($status === false) { throw new DomainException('Заявка не найдена'); }
        if (!in_array($status, ['approved', 'issued', 'overdue'], true)) { throw new DomainException('Заявка не активна'); }

        $items = $pdo->prepare("SELECT equipment_id FROM request_equipment WHERE request_id = ? AND status = 'issued' ORDER BY equipment_id FOR UPDATE");
        $items->execute([$requestId]);
        $equipmentIds = array_map('intval', $items->fetchAll(PDO::FETCH_COLUMN));
        if (!$equipmentIds) { throw new DomainException('В заявке нет выданного оборудования'); }

        $lockEquipment = $pdo->prepare('SELECT status FROM equipment WHERE id = ? FOR UPDATE');
        $closeIssue = $pdo->prepare("UPDATE equipment_issues SET status = 'returned', returned_at = NOW(), returned_by = ?, return_condition = 'good', return_notes = ? WHERE equipment_id = ? AND request_id = ? AND status = 'issued'");
        $closeRelation = $pdo->prepare("UPDATE request_equipment SET status = 'returned', returned_at = NOW() WHERE request_id = ? AND equipment_id = ? AND status = 'issued'");
        $releaseEquipment = $pdo->prepare("UPDATE equipment SET status = 'available', condition_status = 'good' WHERE id = ? AND status = 'issued'");
        foreach ($equipmentIds as $equipmentId) {
            $lockEquipment->execute([$equipmentId]);
            if ($lockEquipment->fetchColumn() !== 'issued') { throw new DomainException('Состояние одной из позиций не соответствует выдаче'); }
            $closeIssue->execute([currentUserId(), $notes, $equipmentId, $requestId]);
            if ($closeIssue->rowCount() !== 1) { throw new DomainException('Активная выдача позиции не найдена'); }
            $closeRelation->execute([$requestId, $equipmentId]);
            $releaseEquipment->execute([$equipmentId]);
        }
        $update = $pdo->prepare("UPDATE requests SET status = 'returned' WHERE id = ? AND status IN ('approved', 'issued', 'overdue')");
        $update->execute([$requestId]);
        if ($update->rowCount() !== 1) { throw new DomainException('Состояние заявки изменилось'); }
        logActivity($pdo, 'return', 'request', $requestId, 'Массовый возврат позиций: ' . count($equipmentIds) . '; причина: ' . $notes);
        $pdo->commit();
        return count($equipmentIds);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $error;
    }
}
