<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/db.php';

$targetCollation = 'utf8mb4_unicode_ci';
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
if ($databaseName === '') {
    throw new RuntimeException('Database is not selected');
}

$quotedDatabase = '`' . str_replace('`', '``', $databaseName) . '`';
$pdo->exec("ALTER DATABASE $quotedDatabase CHARACTER SET utf8mb4 COLLATE $targetCollation");

$stmt = $pdo->prepare(
    "SELECT TABLE_NAME, TABLE_COLLATION
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
     ORDER BY TABLE_NAME"
);
$stmt->execute();

$converted = 0;
foreach ($stmt->fetchAll() as $table) {
    if ((string)$table['TABLE_COLLATION'] === $targetCollation) {
        continue;
    }

    $tableName = (string)$table['TABLE_NAME'];
    $quotedTable = '`' . str_replace('`', '``', $tableName) . '`';
    $pdo->exec("ALTER TABLE $quotedTable CONVERT TO CHARACTER SET utf8mb4 COLLATE $targetCollation");
    echo "Converted $tableName to $targetCollation\n";
    $converted++;
}

echo "MariaDB compatibility complete; tables converted: $converted\n";
