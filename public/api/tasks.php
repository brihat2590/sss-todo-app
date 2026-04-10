<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tasks = $repository->all([
        'search' => (string)($_GET['search'] ?? ''),
        'status' => (string)($_GET['status'] ?? 'all'),
        'sort' => (string)($_GET['sort'] ?? 'created_at_desc'),
    ]);

    jsonResponse(['data' => $tasks]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = readJsonBody();
    $result = $repository->create($payload);

    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 422);
    }

    jsonResponse(['data' => $result], 201);
}

jsonResponse(['error' => 'Method not allowed'], 405);
