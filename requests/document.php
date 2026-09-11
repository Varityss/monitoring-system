<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/input.php';

requireLogin();
$requestId = inputPositiveInt($_GET, 'id');
$stmt = $pdo->prepare("SELECT request_documents.storage_key, request_documents.file_name, requests.user_id FROM request_documents JOIN requests ON requests.id = request_documents.request_id WHERE request_documents.request_id = ? ORDER BY request_documents.id DESC LIMIT 1");
$stmt->execute([$requestId]);
$document = $stmt->fetch();
if (!$document) { http_response_code(404); exit('Акт не найден'); }
if (($_SESSION['user_role'] ?? '') !== 'admin' && (int)$document['user_id'] !== currentUserId()) { http_response_code(403); exit('Доступ запрещён'); }
$storageKey = $document['storage_key'] ?: $document['file_name'];
if (!is_string($storageKey) || basename($storageKey) !== $storageKey) { http_response_code(404); exit('Акт не найден'); }
$path = DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $storageKey;
if (!is_file($path)) { http_response_code(404); exit('Файл акта не найден'); }
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src 'self'; base-uri 'none'; form-action 'none'");
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="request_' . $requestId . '.html"');
header('Content-Length: ' . filesize($path));
readfile($path);
