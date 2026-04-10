<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$payload = readJsonBody();
$id = (int)($payload['id'] ?? 0);
$action = (string)($payload['action'] ?? '');
$seconds = isset($payload['seconds']) ? (int)$payload['seconds'] : null;

if ($id <= 0) {
    jsonResponse(['error' => 'Valid task id is required'], 422);
}

$result = $repository->timerAction($id, $action, $seconds);
if (isset($result['error'])) {
    $status = $result['error'] === 'Task not found' ? 404 : 422;
    jsonResponse(['error' => $result['error']], $status);
}

jsonResponse(['data' => $result]);
