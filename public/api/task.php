<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(['error' => 'Valid task id is required'], 422);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $task = $repository->find($id);
    if (!$task) {
        jsonResponse(['error' => 'Task not found'], 404);
    }

    jsonResponse(['data' => $task]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $payload = readJsonBody();
    $result = $repository->update($id, $payload);

    if (isset($result['error'])) {
        $status = $result['error'] === 'Task not found' ? 404 : 422;
        jsonResponse(['error' => $result['error']], $status);
    }

    jsonResponse(['data' => $result]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $deleted = $repository->delete($id);
    if (!$deleted) {
        jsonResponse(['error' => 'Task not found'], 404);
    }

    jsonResponse(['message' => 'Task deleted']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
