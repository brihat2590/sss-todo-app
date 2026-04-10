<?php

declare(strict_types=1);

use App\Database;
use App\TaskRepository;

require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/TaskRepository.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/env.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

loadEnvFile(__DIR__ . '/../../.env');
$config = require __DIR__ . '/../../src/config.php';
$database = new Database($config);
$repository = new TaskRepository($database->pdo());
