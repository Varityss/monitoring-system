<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/db.php';

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function addColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!columnExists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "Added $table.$column\n";
    }
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

addColumn($pdo, 'users', 'session_version', "int NOT NULL DEFAULT 1");
addColumn($pdo, 'users', 'must_change_password', "tinyint(1) NOT NULL DEFAULT 0");
addColumn($pdo, 'users', 'password_changed_at', "datetime DEFAULT NULL");
addColumn($pdo, 'requests', 'equipment_type', "varchar(100) NOT NULL DEFAULT 'Ноутбук'");
addColumn($pdo, 'requests', 'recipient_name_snapshot', "varchar(255) DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'issued_to_snapshot', "varchar(255) DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'inventory_number_snapshot', "varchar(255) DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'serial_number_snapshot', "varchar(255) DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'model_snapshot', "varchar(255) DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'returned_by', "int DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'return_condition', "varchar(100) DEFAULT NULL");
addColumn($pdo, 'equipment_issues', 'return_notes', "text");
addColumn($pdo, 'equipment_issues', 'operation_key', "varchar(64) DEFAULT NULL");
addColumn($pdo, 'request_documents', 'storage_key', "varchar(255) DEFAULT NULL");
addColumn($pdo, 'activity_logs', 'details_json', "json DEFAULT NULL");
addColumn($pdo, 'activity_logs', 'operation_key', "varchar(64) DEFAULT NULL");

$requestNullable = $pdo->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment_issues' AND COLUMN_NAME = 'request_id'")->fetchColumn();
if ($requestNullable === 'NO') {
    $pdo->exec('ALTER TABLE equipment_issues MODIFY request_id int DEFAULT NULL');
    echo "Made equipment_issues.request_id nullable\n";
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS login_attempts (
        id bigint NOT NULL AUTO_INCREMENT,
        login_key char(64) NOT NULL,
        ip_hash char(64) NOT NULL,
        attempted_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY login_time (login_key, attempted_at),
        KEY ip_time (ip_hash, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$duplicateRequestEquipment = $pdo->query('SELECT request_id, equipment_id FROM request_equipment GROUP BY request_id, equipment_id HAVING COUNT(*) > 1 LIMIT 1')->fetch();
if ($duplicateRequestEquipment) {
    throw new RuntimeException('Найдены повторные позиции внутри заявки');
}
if (!indexExists($pdo, 'request_equipment', 'request_equipment_unique')) {
    $pdo->exec('ALTER TABLE request_equipment ADD UNIQUE KEY request_equipment_unique (request_id, equipment_id)');
    echo "Added request equipment uniqueness constraint\n";
}

$roomUpdate = $pdo->exec("UPDATE equipment SET cabinet = CASE WHEN LOWER(TRIM(type)) = 'ноутбук' THEN '408' ELSE '411' END WHERE cabinet <> CASE WHEN LOWER(TRIM(type)) = 'ноутбук' THEN '408' ELSE '411' END OR cabinet IS NULL");
echo 'Equipment rooms normalized: ' . $roomUpdate . "\n";

$knownDefaults = ['admin123', '12345678'];
$admins = $pdo->query("SELECT id, password FROM users WHERE role = 'admin'")->fetchAll();
$forceChange = $pdo->prepare('UPDATE users SET must_change_password = 1, session_version = session_version + 1 WHERE id = ? AND must_change_password = 0');
foreach ($admins as $admin) {
    foreach ($knownDefaults as $defaultPassword) {
        if (password_verify($defaultPassword, $admin['password'])) {
            $forceChange->execute([(int)$admin['id']]);
            if ($forceChange->rowCount() === 1) {
                echo 'Forced password change for administrator #' . (int)$admin['id'] . "\n";
            }
            break;
        }
    }
}

echo "Security hardening migration complete\n";
