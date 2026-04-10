<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$payload = readJsonBody();
$action = (string)($payload['action'] ?? '');

if ($action === 'complete_all') {
    $count = $repository->completeAll();
    jsonResponse(['message' => 'Pending tasks completed', 'updated_count' => $count]);
}

if ($action === 'clear_completed') {
    $count = $repository->clearCompleted();
    jsonResponse(['message' => 'Completed tasks removed', 'deleted_count' => $count]);
}

jsonResponse(['error' => 'Invalid bulk action'], 422);
