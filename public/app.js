const API = {
    tasks: "api/tasks.php",
    task: "api/task.php",
    bulk: "api/bulk.php",
    timer: "api/timer.php",
};

const state = {
    tasks: [],
    filters: {
        search: "",
        status: "all",
        sort: "created_at_desc",
    },
};

const refs = {
    taskForm: document.getElementById("taskForm"),
    taskList: document.getElementById("taskList"),
    template: document.getElementById("taskTemplate"),
    searchInput: document.getElementById("searchInput"),
    statusFilter: document.getElementById("statusFilter"),
    sortBy: document.getElementById("sortBy"),
    completeAllBtn: document.getElementById("completeAllBtn"),
    clearCompletedBtn: document.getElementById("clearCompletedBtn"),
    statTotal: document.getElementById("statTotal"),
    statPending: document.getElementById("statPending"),
    statCompleted: document.getElementById("statCompleted"),
    statOverdue: document.getElementById("statOverdue"),
};

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            "Content-Type": "application/json",
        },
        ...options,
    });

    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.error || "Request failed");
    }

    return result;
}

function toSeconds(minutesText) {
    const value = Number(minutesText);
    if (!Number.isFinite(value) || value < 0) {
        return 0;
    }
    return Math.floor(value * 60);
}

function formatTimer(seconds) {
    const safe = Math.max(0, Number(seconds) || 0);
    const mins = String(Math.floor(safe / 60)).padStart(2, "0");
    const secs = String(safe % 60).padStart(2, "0");
    return `${mins}:${secs}`;
}

function dueChip(task) {
    if (!task.due_date) {
        return "No due date";
    }
    const due = new Date(task.due_date + "T00:00:00");
    const now = new Date();
    now.setHours(0, 0, 0, 0);

    if (due.getTime() < now.getTime() && !Number(task.completed)) {
        return "Overdue";
    }

    if (due.getTime() === now.getTime()) {
        return "Due today";
    }

    return `Due ${task.due_date}`;
}

function getLiveRemaining(task) {
    if (task.timer_status !== "running" || !task.timer_started_at) {
        return Number(task.timer_remaining_seconds) || 0;
    }

    const started = new Date(task.timer_started_at.replace(" ", "T")).getTime();
    if (Number.isNaN(started)) {
        return Number(task.timer_remaining_seconds) || 0;
    }

    const elapsed = Math.floor((Date.now() - started) / 1000);
    return Math.max(0, (Number(task.timer_remaining_seconds) || 0) - elapsed);
}

function computeStats(tasks) {
    const now = new Date();
    now.setHours(0, 0, 0, 0);

    const total = tasks.length;
    const completed = tasks.filter((task) => Number(task.completed) === 1).length;
    const pending = total - completed;
    const overdue = tasks.filter((task) => {
        if (Number(task.completed) === 1 || !task.due_date) {
            return false;
        }
        const due = new Date(task.due_date + "T00:00:00");
        return due.getTime() < now.getTime();
    }).length;

    refs.statTotal.textContent = String(total);
    refs.statPending.textContent = String(pending);
    refs.statCompleted.textContent = String(completed);
    refs.statOverdue.textContent = String(overdue);
}

function renderTasks() {
    refs.taskList.innerHTML = "";

    if (!state.tasks.length) {
        const empty = document.createElement("div");
        empty.className = "empty";
        empty.textContent = "No tasks match your filters.";
        refs.taskList.appendChild(empty);
        computeStats([]);
        return;
    }

    state.tasks.forEach((task) => {
        const fragment = refs.template.content.cloneNode(true);
        const card = fragment.querySelector(".task-card");
        card.dataset.id = String(task.id);

        const title = fragment.querySelector(".task-title");
        const description = fragment.querySelector(".task-desc");
        const tags = fragment.querySelector(".task-tags");
        const priorityChip = fragment.querySelector(".priority-chip");
        const dateChip = fragment.querySelector(".date-chip");
        const checkbox = fragment.querySelector(".task-check");
        const timerReadout = fragment.querySelector(".timer-readout");

        title.textContent = task.title;
        description.textContent = task.description || "No description";
        tags.textContent = task.tags ? `#${task.tags}` : "#no-tags";
        priorityChip.textContent = task.priority;
        dateChip.textContent = dueChip(task);
        checkbox.checked = Number(task.completed) === 1;

        card.classList.toggle("completed", checkbox.checked);

        timerReadout.textContent = formatTimer(getLiveRemaining(task));

        fragment.querySelector(".edit-task").addEventListener("click", () => editTask(task));
        fragment.querySelector(".delete-task").addEventListener("click", () => deleteTask(task.id));
        checkbox.addEventListener("change", (event) => {
            toggleComplete(task, event.target.checked);
        });

        fragment.querySelector(".timer-start").addEventListener("click", () => timerAction(task.id, "start"));
        fragment.querySelector(".timer-pause").addEventListener("click", () => timerAction(task.id, "pause"));
        fragment.querySelector(".timer-reset").addEventListener("click", () => timerAction(task.id, "reset"));
        fragment.querySelector(".timer-set").addEventListener("click", () => setTimer(task));

        refs.taskList.appendChild(fragment);
    });

    computeStats(state.tasks);
}

async function loadTasks() {
    const params = new URLSearchParams({
        search: state.filters.search,
        status: state.filters.status,
        sort: state.filters.sort,
    });

    const result = await apiRequest(`${API.tasks}?${params.toString()}`);
    state.tasks = result.data || [];
    renderTasks();
}

async function createTask(event) {
    event.preventDefault();
    const formData = new FormData(refs.taskForm);

    const payload = {
        title: String(formData.get("title") || "").trim(),
        description: String(formData.get("description") || "").trim(),
        priority: String(formData.get("priority") || "medium"),
        due_date: String(formData.get("due_date") || ""),
        tags: String(formData.get("tags") || "").trim(),
        timer_total_seconds: toSeconds(formData.get("timer_minutes") || "0"),
    };

    try {
        await apiRequest(API.tasks, {
            method: "POST",
            body: JSON.stringify(payload),
        });

        refs.taskForm.reset();
        document.getElementById("priority").value = "medium";
        document.getElementById("timerMinutes").value = "0";
        await loadTasks();
    } catch (error) {
        alert(error.message);
    }
}

async function toggleComplete(task, completed) {
    try {
        await apiRequest(`${API.task}?id=${task.id}`, {
            method: "PATCH",
            body: JSON.stringify({ completed: completed ? 1 : 0 }),
        });
        await loadTasks();
    } catch (error) {
        alert(error.message);
    }
}

async function editTask(task) {
    const title = prompt("Edit title", task.title);
    if (title === null) {
        return;
    }

    const description = prompt("Edit description", task.description || "");
    if (description === null) {
        return;
    }

    const priority = prompt("Priority: low, medium, high", task.priority || "medium");
    if (priority === null) {
        return;
    }

    const dueDate = prompt("Due date (YYYY-MM-DD)", task.due_date || "");
    if (dueDate === null) {
        return;
    }

    const tags = prompt("Tags (comma separated)", task.tags || "");
    if (tags === null) {
        return;
    }

    try {
        await apiRequest(`${API.task}?id=${task.id}`, {
            method: "PUT",
            body: JSON.stringify({
                title: title.trim(),
                description: description.trim(),
                priority: priority.trim().toLowerCase(),
                due_date: dueDate.trim(),
                tags: tags.trim(),
            }),
        });

        await loadTasks();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteTask(taskId) {
    const confirmed = confirm("Delete this task permanently?");
    if (!confirmed) {
        return;
    }

    try {
        await apiRequest(`${API.task}?id=${taskId}`, {
            method: "DELETE",
        });

        await loadTasks();
    } catch (error) {
        alert(error.message);
    }
}

async function timerAction(taskId, action, seconds = null) {
    try {
        await apiRequest(API.timer, {
            method: "POST",
            body: JSON.stringify({ id: taskId, action, seconds }),
        });

        await loadTasks();
    } catch (error) {
        alert(error.message);
    }
}

async function setTimer(task) {
    const initialMinutes = Math.round((Number(task.timer_total_seconds) || 0) / 60);
    const entered = prompt("Set timer minutes", String(initialMinutes));

    if (entered === null) {
        return;
    }

    const minutes = Number(entered);
    if (!Number.isFinite(minutes) || minutes < 0) {
        alert("Please enter a valid number of minutes.");
        return;
    }

    await timerAction(task.id, "set", Math.floor(minutes * 60));
}

async function bulkAction(action) {
    try {
        await apiRequest(API.bulk, {
            method: "POST",
            body: JSON.stringify({ action }),
        });
        await loadTasks();
    } catch (error) {
        alert(error.message);
    }
}

function applyLiveTick() {
    const cards = refs.taskList.querySelectorAll(".task-card");
    cards.forEach((card) => {
        const id = Number(card.dataset.id);
        const task = state.tasks.find((item) => Number(item.id) === id);
        if (!task) {
            return;
        }

        const timer = card.querySelector(".timer-readout");
        timer.textContent = formatTimer(getLiveRemaining(task));
    });
}

function setupEvents() {
    refs.taskForm.addEventListener("submit", createTask);

    refs.searchInput.addEventListener("input", (event) => {
        state.filters.search = event.target.value.trim();
        loadTasks();
    });

    refs.statusFilter.addEventListener("change", (event) => {
        state.filters.status = event.target.value;
        loadTasks();
    });

    refs.sortBy.addEventListener("change", (event) => {
        state.filters.sort = event.target.value;
        loadTasks();
    });

    refs.completeAllBtn.addEventListener("click", () => {
        bulkAction("complete_all");
    });

    refs.clearCompletedBtn.addEventListener("click", () => {
        const confirmed = confirm("Clear every completed task?");
        if (confirmed) {
            bulkAction("clear_completed");
        }
    });
}

async function bootstrap() {
    setupEvents();
    await loadTasks();

    setInterval(() => {
        applyLiveTick();
    }, 1000);

    setInterval(() => {
        loadTasks();
    }, 15000);
}

bootstrap();
