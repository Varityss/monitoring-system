<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (!is_dir(DOCUMENT_STORAGE_PATH) && !mkdir(DOCUMENT_STORAGE_PATH, 0700, true) && !is_dir(DOCUMENT_STORAGE_PATH)) {
    throw new RuntimeException('Не удалось создать закрытое хранилище документов');
}

$documents = $pdo->query("SELECT id, file_name FROM request_documents WHERE storage_key IS NULL OR storage_key = ''")->fetchAll();
$update = $pdo->prepare('UPDATE request_documents SET storage_key = ?, file_name = ? WHERE id = ? AND (storage_key IS NULL OR storage_key = \'\')');
$moved = 0;
$missing = 0;

foreach ($documents as $document) {
    $legacyName = is_string($document['file_name']) ? basename($document['file_name']) : '';
    $source = dirname(__DIR__, 2) . '/uploads/documents/' . $legacyName;
    if ($legacyName === '' || !is_file($source)) {
        fwrite(STDERR, 'Missing legacy document #' . (int)$document['id'] . "\n");
        $missing++;
        continue;
    }

    $storageKey = 'legacy_' . (int)$document['id'] . '_' . bin2hex(random_bytes(16)) . '.html';
    $destination = DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $storageKey;
    if (!copy($source, $destination)) {
        throw new RuntimeException('Не удалось скопировать документ #' . (int)$document['id']);
    }

    try {
        $update->execute([$storageKey, $storageKey, (int)$document['id']]);
        if ($update->rowCount() !== 1) {
            unlink($destination);
            continue;
        }
        $moved++;
    } catch (Throwable $error) {
        unlink($destination);
        throw $error;
    }
}

echo 'Documents moved: ' . $moved . "\n";
echo 'Documents missing: ' . $missing . "\n";
