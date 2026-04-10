# Pulse Tasks (PHP + MySQL)

A full stack todo application with advanced management features and per-task timers.

## Features

- Create, edit, complete, and delete tasks
- Priority levels (low, medium, high)
- Due dates with overdue and due-today logic
- Tags and task descriptions
- Search, status filtering, and sorting
- Bulk actions:
  - Complete all pending
  - Clear all completed
- Per-task timer support:
  - Set custom duration
  - Start, pause, reset
  - Live countdown in UI
  - Timer state persisted in MySQL

## Project Structure

- public/index.php: Main frontend page
- public/app.js: Client-side app logic
- public/styles.css: UI styles
- public/api/*.php: Backend API endpoints
- src/*.php: Shared backend classes and helpers
- database/schema.sql: MySQL schema

## Requirements

- PHP 8.1+
- MySQL 8+

## Setup

1. Create database and table:

```sql
SOURCE database/schema.sql;
```

2. Configure environment:

```bash
cp .env.example .env
```

Edit .env with your database credentials.

3. Serve app from public folder:

```bash
php -S localhost:8080 -t public
```

4. Open in browser:

http://localhost:8080

## API Endpoints

- GET /api/tasks.php?search=&status=&sort=
- POST /api/tasks.php
- GET /api/task.php?id={id}
- PUT /api/task.php?id={id}
- PATCH /api/task.php?id={id}
- DELETE /api/task.php?id={id}
- POST /api/bulk.php
- POST /api/timer.php

### Example Timer Request

```json
{
  "id": 12,
  "action": "set",
  "seconds": 1500
}
```

Supported actions: set, start, pause, reset
# sss-todo-app
