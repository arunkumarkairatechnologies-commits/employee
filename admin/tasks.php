<?php
// admin/tasks.php - Admin can view and change status of all tasks
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

function bindParams($stmt, $types, &$params) {
    $bind = [];
    $bind[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
}

// Change task status
if (isset($_POST['change_status']) && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $stmt = $conn->prepare('UPDATE tasks SET status = "completed", completed_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $task_id);
    if ($stmt->execute()) {
        // Notify employee
        $emp_id = $conn->query("SELECT employee_id FROM tasks WHERE id=$task_id")->fetch_assoc()['employee_id'];
        $msg = 'Your task has been marked completed by admin.';
        $notif = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
        $notif->bind_param('is', $emp_id, $msg);
        $notif->execute();
        $notif->close();
    }
    $stmt->close();
}

// Archive task
if (isset($_POST['archive_task']) && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $stmt = $conn->prepare('UPDATE tasks SET is_archived = 1 WHERE id = ?');
    $stmt->bind_param('i', $task_id);
    $stmt->execute();
    $stmt->close();
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-list-check me-2"></i>All Tasks</h2>
                <a href="add_task.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>New Task</a>
            </div>

            <?php
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
            $archived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;
            $valid_status = ['pending', 'completed'];
            if (!in_array($status, $valid_status, true)) {
                $status = '';
            }
            ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form class="row g-2 align-items-end" method="GET" action="">
                        <div class="col-lg-3">
                            <label class="form-label fw-600">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search tasks...">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label fw-600">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-600">Employee</label>
                            <select class="form-select" name="employee_id">
                                <option value="0">All employees</option>
                                <?php
                                $emp_res = $conn->query("SELECT id, name FROM users WHERE role='employee' ORDER BY name ASC");
                                if ($emp_res) {
                                    while ($e = $emp_res->fetch_assoc()): ?>
                                        <option value="<?php echo (int)$e['id']; ?>" <?php echo $employee_id === (int)$e['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($e['name']); ?>
                                        </option>
                                    <?php endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label fw-600">List View</label>
                            <select class="form-select" name="archived">
                                <option value="0" <?php echo $archived === 0 ? 'selected' : ''; ?>>Default Active View</option>
                                <option value="1" <?php echo $archived === 1 ? 'selected' : ''; ?>>Archived / Cleared Records</option>
                            </select>
                        </div>
                        <div class="col-lg-1 d-grid">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Go</button>
                        </div>
                        <div class="col-12">
                            <a class="small text-decoration-none" href="tasks.php"><i class="fas fa-rotate-left me-1"></i>Reset filters</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Employee</th>
                                    <th>Observer</th>
                                    <th>Admin Attachment</th>
                                    <th>User Attachment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "SELECT t.*, u.name AS emp_name, u.profile_image, (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM users WHERE FIND_IN_SET(id, t.observer_id) > 0) AS observer_name
                                    FROM tasks t
                                    JOIN users u ON t.employee_id = u.id";
                            $where = [];
                            $params = [];
                            $types = '';

                            if ($q !== '') {
                                $like = '%' . $q . '%';
                                $where[] = "(t.title LIKE ? OR t.description LIKE ? OR u.name LIKE ? OR EXISTS (SELECT 1 FROM users o WHERE FIND_IN_SET(o.id, t.observer_id) > 0 AND o.name LIKE ?))";
                                $params[] = $like;
                                $params[] = $like;
                                $params[] = $like;
                                $params[] = $like;
                                $types .= 'ssss';
                            }

                            if ($status !== '') {
                                $where[] = "t.status = ?";
                                $params[] = $status;
                                $types .= 's';
                            }

                            if ($employee_id > 0) {
                                $where[] = "t.employee_id = ?";
                                $params[] = $employee_id;
                                $types .= 'i';
                            }

                            $where[] = "t.is_archived = ?";
                            $params[] = $archived;
                            $types .= 'i';

                            if (count($where) > 0) {
                                $sql .= " WHERE " . implode(" AND ", $where);
                            }
                            $sql .= " ORDER BY t.due_date DESC";

                            $stmt = $conn->prepare($sql);
                            if ($stmt === false) {
                                $tasks = false;
                            } else {
                                if ($types !== '') {
                                    bindParams($stmt, $types, $params);
                                }
                                $stmt->execute();
                                $tasks = $stmt->get_result();
                            }

                            if ($tasks && $tasks->num_rows > 0) {
                                while ($row = $tasks->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <a href="view_task.php?id=<?php echo $row['id']; ?>" class="text-decoration-none shadow-sm d-inline-block p-1 bg-light rounded text-primary fw-bold hover-link">
                                                <i class="fas fa-tasks me-1"></i> <?php echo htmlspecialchars($row['title']); ?>
                                            </a><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($row['description'], 0, 50)); ?></small></td>
                                        <td><small><?php echo date('M d, Y', strtotime($row['due_date'])); ?></small></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <small><?php echo htmlspecialchars($row['emp_name']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <small><?php echo $row['observer_name'] ? htmlspecialchars($row['observer_name']) : '-'; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($row['attachment']): ?>
                                                <a href="../assets/uploads/<?php echo htmlspecialchars($row['attachment']); ?>" target="_blank" class="badge bg-secondary">
                                                    <i class="fas fa-paperclip me-1"></i>File
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['user_attachment']): ?>
                                                <a href="../assets/uploads/<?php echo htmlspecialchars($row['user_attachment']); ?>" target="_blank" class="badge bg-info">
                                                    <i class="fas fa-file-alt me-1"></i>User File
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'pending' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'pending'): ?>
                                                <form method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="change_status" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Complete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <?php if($row['is_archived'] == 0): ?>
                                                <form method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="archive_task" class="btn btn-warning btn-sm shadow-sm" title="Clear / Hide this task" onclick="return confirm('Clear this from the front view? It will be moved to the Archive.');">
                                                        <i class="fas fa-archive"></i> Clear
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Archived</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            } else {
                                echo '<tr><td colspan="8" class="text-center text-muted py-4">No tasks found</td></tr>';
                            }
                            if (isset($stmt) && $stmt) { $stmt->close(); }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
