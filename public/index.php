<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pulse Tasks | PHP Todo + Timers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="bg-orb orb-a"></div>
<div class="bg-orb orb-b"></div>

<main class="app-shell">
    <header class="topbar">
        <div>
            <p class="eyebrow">Full Stack Todo Platform</p>
            <h1>Pulse Tasks</h1>
        </div>
        <div class="topbar-actions">
            <button id="completeAllBtn" class="btn btn-outline">Complete All</button>
            <button id="clearCompletedBtn" class="btn btn-danger">Clear Completed</button>
        </div>
    </header>

    <section class="stats-grid" id="statsGrid">
        <article class="stat-card">
            <h3>Total</h3>
            <p id="statTotal">0</p>
        </article>
        <article class="stat-card">
            <h3>Pending</h3>
            <p id="statPending">0</p>
        </article>
        <article class="stat-card">
            <h3>Completed</h3>
            <p id="statCompleted">0</p>
        </article>
        <article class="stat-card">
            <h3>Overdue</h3>
            <p id="statOverdue">0</p>
        </article>
    </section>

    <section class="panel">
        <h2>Create Task</h2>
        <form id="taskForm" class="task-form">
            <div class="field full">
                <label for="title">Title</label>
                <input id="title" name="title" required placeholder="Design API contract">
            </div>
            <div class="field full">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Notes, requirements, blockers..."></textarea>
            </div>
            <div class="field">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="field">
                <label for="dueDate">Due date</label>
                <input id="dueDate" name="due_date" type="date">
            </div>
            <div class="field">
                <label for="tags">Tags</label>
                <input id="tags" name="tags" placeholder="backend,urgent">
            </div>
            <div class="field">
                <label for="timerMinutes">Timer (minutes)</label>
                <input id="timerMinutes" name="timer_minutes" type="number" min="0" value="0">
            </div>
            <div class="actions full">
                <button class="btn btn-primary" type="submit">Add Task</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="list-toolbar">
            <h2>Task Board</h2>
            <div class="toolbar-controls">
                <input id="searchInput" placeholder="Search tasks...">
                <select id="statusFilter">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="today">Due Today</option>
                    <option value="overdue">Overdue</option>
                </select>
                <select id="sortBy">
                    <option value="created_at_desc">Newest</option>
                    <option value="due_date_asc">Due Date (Soonest)</option>
                    <option value="due_date_desc">Due Date (Latest)</option>
                    <option value="priority">Priority</option>
                    <option value="title">Title</option>
                </select>
            </div>
        </div>

        <div id="taskList" class="task-list"></div>
    </section>
</main>

<template id="taskTemplate">
    <article class="task-card">
        <div class="task-head">
            <div class="task-meta">
                <span class="priority-chip"></span>
                <span class="date-chip"></span>
            </div>
            <label class="check-wrap">
                <input type="checkbox" class="task-check">
                <span>Done</span>
            </label>
        </div>

        <h3 class="task-title"></h3>
        <p class="task-desc"></p>
        <p class="task-tags"></p>

        <div class="timer-block">
            <div class="timer-readout">00:00</div>
            <div class="timer-controls">
                <button class="btn btn-small timer-start">Start</button>
                <button class="btn btn-small btn-outline timer-pause">Pause</button>
                <button class="btn btn-small btn-ghost timer-reset">Reset</button>
                <button class="btn btn-small btn-outline timer-set">Set</button>
            </div>
        </div>

        <div class="task-actions">
            <button class="btn btn-small edit-task">Edit</button>
            <button class="btn btn-small btn-danger delete-task">Delete</button>
        </div>
    </article>
</template>

<script src="app.js"></script>
</body>
</html>
