CREATE DATABASE IF NOT EXISTS todo_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE todo_app;

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    due_date DATE NULL,
    tags VARCHAR(255) NULL,
    completed TINYINT(1) NOT NULL DEFAULT 0,
    timer_total_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    timer_remaining_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    timer_status ENUM('stopped', 'running', 'paused') NOT NULL DEFAULT 'stopped',
    timer_started_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_completed (completed),
    INDEX idx_due_date (due_date),
    INDEX idx_priority (priority),
    INDEX idx_timer_status (timer_status)
);
