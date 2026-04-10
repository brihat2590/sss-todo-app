<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use PDO;

final class TaskRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        $query = 'SELECT * FROM tasks WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $query .= ' AND (title LIKE :search OR description LIKE :search OR tags LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            switch ($filters['status']) {
                case 'pending':
                    $query .= ' AND completed = 0';
                    break;
                case 'completed':
                    $query .= ' AND completed = 1';
                    break;
                case 'today':
                    $query .= ' AND due_date = CURDATE()';
                    break;
                case 'overdue':
                    $query .= ' AND completed = 0 AND due_date IS NOT NULL AND due_date < CURDATE()';
                    break;
            }
        }

        $query .= ' ORDER BY ' . $this->resolveOrderBy($filters['sort'] ?? null);

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $tasks = $statement->fetchAll();

        return $this->hydrateTimerState($tasks);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            return ['error' => 'Title is required'];
        }

        $timerTotal = max(0, (int)($data['timer_total_seconds'] ?? 0));

        $statement = $this->pdo->prepare(
            'INSERT INTO tasks (title, description, priority, due_date, tags, completed, timer_total_seconds, timer_remaining_seconds, timer_status, timer_started_at)
             VALUES (:title, :description, :priority, :due_date, :tags, :completed, :timer_total_seconds, :timer_remaining_seconds, :timer_status, NULL)'
        );

        $statement->execute([
            'title' => $title,
            'description' => trim((string)($data['description'] ?? '')),
            'priority' => $this->normalizePriority((string)($data['priority'] ?? 'medium')),
            'due_date' => $this->normalizeDate($data['due_date'] ?? null),
            'tags' => trim((string)($data['tags'] ?? '')),
            'completed' => !empty($data['completed']) ? 1 : 0,
            'timer_total_seconds' => $timerTotal,
            'timer_remaining_seconds' => $timerTotal,
            'timer_status' => 'stopped',
        ]);

        return $this->find((int)$this->pdo->lastInsertId()) ?? ['error' => 'Unable to create task'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM tasks WHERE id = :id');
        $statement->execute(['id' => $id]);
        $task = $statement->fetch();

        if (!$task) {
            return null;
        }

        return $this->hydrateTimerState([$task])[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $task = $this->find($id);
        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $title = array_key_exists('title', $data) ? trim((string)$data['title']) : (string)$task['title'];
        if ($title === '') {
            return ['error' => 'Title is required'];
        }

        $timerTotal = array_key_exists('timer_total_seconds', $data)
            ? max(0, (int)$data['timer_total_seconds'])
            : (int)$task['timer_total_seconds'];

        $timerRemaining = (int)$task['timer_remaining_seconds'];
        if ($timerRemaining > $timerTotal) {
            $timerRemaining = $timerTotal;
        }

        $statement = $this->pdo->prepare(
            'UPDATE tasks
             SET title = :title,
                 description = :description,
                 priority = :priority,
                 due_date = :due_date,
                 tags = :tags,
                 completed = :completed,
                 timer_total_seconds = :timer_total_seconds,
                 timer_remaining_seconds = :timer_remaining_seconds,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'title' => $title,
            'description' => array_key_exists('description', $data) ? trim((string)$data['description']) : (string)$task['description'],
            'priority' => array_key_exists('priority', $data)
                ? $this->normalizePriority((string)$data['priority'])
                : (string)$task['priority'],
            'due_date' => array_key_exists('due_date', $data)
                ? $this->normalizeDate($data['due_date'])
                : $task['due_date'],
            'tags' => array_key_exists('tags', $data) ? trim((string)$data['tags']) : (string)$task['tags'],
            'completed' => array_key_exists('completed', $data) ? (!empty($data['completed']) ? 1 : 0) : (int)$task['completed'],
            'timer_total_seconds' => $timerTotal,
            'timer_remaining_seconds' => $timerRemaining,
        ]);

        return $this->find($id) ?? ['error' => 'Unable to update task'];
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function completeAll(): int
    {
        $statement = $this->pdo->prepare('UPDATE tasks SET completed = 1, updated_at = CURRENT_TIMESTAMP WHERE completed = 0');
        $statement->execute();

        return $statement->rowCount();
    }

    public function clearCompleted(): int
    {
        $statement = $this->pdo->prepare('DELETE FROM tasks WHERE completed = 1');
        $statement->execute();

        return $statement->rowCount();
    }

    /**
     * @return array<string, mixed>
     */
    public function timerAction(int $id, string $action, ?int $seconds = null): array
    {
        $task = $this->find($id);
        if (!$task) {
            return ['error' => 'Task not found'];
        }

        switch ($action) {
            case 'set':
                $total = max(0, $seconds ?? 0);
                $statement = $this->pdo->prepare(
                    'UPDATE tasks
                     SET timer_total_seconds = :total,
                         timer_remaining_seconds = :remaining,
                         timer_status = :status,
                         timer_started_at = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'total' => $total,
                    'remaining' => $total,
                    'status' => 'stopped',
                ]);
                break;

            case 'start':
                $remaining = (int)$task['timer_remaining_seconds'];
                if ($remaining <= 0) {
                    $remaining = (int)$task['timer_total_seconds'];
                }

                $statement = $this->pdo->prepare(
                    'UPDATE tasks
                     SET timer_remaining_seconds = :remaining,
                         timer_status = :status,
                         timer_started_at = CURRENT_TIMESTAMP,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'remaining' => $remaining,
                    'status' => 'running',
                ]);
                break;

            case 'pause':
                if ((string)$task['timer_status'] === 'running' && !empty($task['timer_started_at'])) {
                    $elapsed = $this->elapsedSeconds((string)$task['timer_started_at']);
                    $remaining = max(0, (int)$task['timer_remaining_seconds'] - $elapsed);
                } else {
                    $remaining = (int)$task['timer_remaining_seconds'];
                }

                $statement = $this->pdo->prepare(
                    'UPDATE tasks
                     SET timer_remaining_seconds = :remaining,
                         timer_status = :status,
                         timer_started_at = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'remaining' => $remaining,
                    'status' => $remaining === 0 ? 'stopped' : 'paused',
                ]);
                break;

            case 'reset':
                $statement = $this->pdo->prepare(
                    'UPDATE tasks
                     SET timer_remaining_seconds = timer_total_seconds,
                         timer_status = :status,
                         timer_started_at = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $statement->execute([
                    'id' => $id,
                    'status' => 'stopped',
                ]);
                break;

            default:
                return ['error' => 'Invalid timer action'];
        }

        return $this->find($id) ?? ['error' => 'Unable to update timer'];
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     * @return array<int, array<string, mixed>>
     */
    private function hydrateTimerState(array $tasks): array
    {
        foreach ($tasks as &$task) {
            if ((string)$task['timer_status'] === 'running' && !empty($task['timer_started_at'])) {
                $elapsed = $this->elapsedSeconds((string)$task['timer_started_at']);
                $remaining = max(0, (int)$task['timer_remaining_seconds'] - $elapsed);

                if ($remaining === 0) {
                    $this->pdo->prepare(
                        'UPDATE tasks
                         SET timer_remaining_seconds = 0,
                             timer_status = :status,
                             timer_started_at = NULL,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id'
                    )->execute([
                        'id' => (int)$task['id'],
                        'status' => 'stopped',
                    ]);

                    $task['timer_status'] = 'stopped';
                    $task['timer_started_at'] = null;
                }

                $task['timer_remaining_seconds'] = $remaining;
            }
        }

        return $tasks;
    }

    private function elapsedSeconds(string $startedAt): int
    {
        $start = new DateTimeImmutable($startedAt);
        $now = new DateTimeImmutable('now');
        return max(0, $now->getTimestamp() - $start->getTimestamp());
    }

    private function normalizePriority(string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium';
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        return date('Y-m-d', strtotime((string)$value));
    }

    private function resolveOrderBy(?string $sort): string
    {
        return match ($sort) {
            'due_date_asc' => 'due_date IS NULL, due_date ASC, created_at DESC',
            'due_date_desc' => 'due_date IS NULL, due_date DESC, created_at DESC',
            'priority' => "FIELD(priority, 'high', 'medium', 'low'), created_at DESC",
            'title' => 'title ASC, created_at DESC',
            default => 'created_at DESC',
        };
    }
}
