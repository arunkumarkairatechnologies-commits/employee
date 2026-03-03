<?php
// my_tasks.php - Employee can view and complete assigned tasks
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
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

// Mark task as completed (form submission with file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task']) && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $user_attachment = null;
    
    // Handle file upload
    if (isset($_FILES['user_attachment']) && $_FILES['user_attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_user_' . basename($_FILES['user_attachment']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['user_attachment']['tmp_name'], $target_path)) {
            $user_attachment = $file_name;
        }
    }

    $stmt = $conn->prepare('UPDATE tasks SET status = "completed", completed_at = NOW(), user_attachment = ? WHERE id = ? AND employee_id = ?');
    $stmt->bind_param('sii', $user_attachment, $task_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        // Notify admin
        $admin_res = $conn->query("SELECT id FROM users WHERE role='admin'");
        while ($admin = $admin_res->fetch_assoc()) {
            $msg = 'Employee ' . $_SESSION['user_name'] . ' completed a task with attachment.';
            $notif = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
            $notif->bind_param('is', $admin['id'], $msg);
            $notif->execute();
            $notif->close();
        }
        $_SESSION['success_msg'] = 'Task completed successfully!';
    }
    $stmt->close();
    header("Location: my_tasks.php");
    exit;
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold"><i class="fas fa-list-check me-2 text-primary"></i>My Tasks</h2>
            </div>

            <?php
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $valid_status = ['pending', 'completed'];
            if (!in_array($status, $valid_status, true)) {
                $status = '';
            }
            ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form class="row g-2 align-items-end" method="GET" action="">
                        <div class="col-lg-7">
                            <label class="form-label fw-600">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Task title or description...">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label fw-600">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-lg-1 d-grid">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Go</button>
                        </div>
                        <div class="col-12">
                            <a class="small text-decoration-none" href="my_tasks.php"><i class="fas fa-rotate-left me-1"></i>Reset filters</a>
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
                                    <th>Description</th>
                                    <th>Due Date</th>
                                    <th>Admin Attachment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "SELECT t.*, u.name as assigned_name, (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM users WHERE FIND_IN_SET(id, t.observer_id) > 0) as observer_name
                                    FROM tasks t
                                    LEFT JOIN users u ON t.employee_id = u.id
                                    WHERE (t.employee_id = ? OR FIND_IN_SET(?, t.observer_id) > 0)";
                            $params = [$_SESSION['user_id'], $_SESSION['user_id']];
                            $types = 'ii';

                            if ($q !== '') {
                                $like = '%' . $q . '%';
                                $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
                                $params[] = $like;
                                $params[] = $like;
                                $types .= 'ss';
                            }

                            if ($status !== '') {
                                $sql .= " AND t.status = ?";
                                $params[] = $status;
                                $types .= 's';
                            }

                            $sql .= " ORDER BY due_date DESC";
                            $stmt = $conn->prepare($sql);
                            if ($stmt === false) {
                                $tasks = false;
                            } else {
                                bindParams($stmt, $types, $params);
                                $stmt->execute();
                                $tasks = $stmt->get_result();
                            }

                            if ($tasks && $tasks->num_rows > 0) {
                                while ($row = $tasks->fetch_assoc()): 
                                    $due_date = new DateTime($row['due_date']);
                                    $today = new DateTime();
                                    $is_overdue = $due_date < $today && $row['status'] == 'pending';
                                    $is_observer = (in_array($_SESSION['user_id'], explode(',', $row['observer_id'])) && $row['employee_id'] != $_SESSION['user_id']);
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="view_task.php?id=<?php echo $row['id']; ?>" class="text-decoration-none shadow-sm d-inline-block p-1 bg-light rounded text-primary fw-bold hover-link">
                                                <i class="fas fa-tasks me-1"></i> <?php echo htmlspecialchars($row['title']); ?>
                                            </a>
                                            <?php if($is_observer): ?>
                                                <span class="badge bg-info ms-2"><i class="fas fa-eye me-1"></i>Observer</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars(substr($row['description'], 0, 50)); ?></small></td>
                                        <td>
                                            <small <?php echo $is_overdue ? 'class="text-danger fw-bold"' : 'class="text-muted"'; ?>>
                                                <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                                <?php if($is_overdue): ?><i class="fas fa-exclamation-circle ms-1"></i><?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($row['attachment']): ?>
                                                <a href="../assets/uploads/<?php echo htmlspecialchars($row['attachment']); ?>" target="_blank" class="badge bg-secondary">
                                                    <i class="fas fa-paperclip me-1"></i>View File
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted">No attachment</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'pending' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($is_observer): ?>
                                                <div class="text-muted"><small>No action available for observers</small></div>
                                            <?php elseif($row['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#completeTaskModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-check me-1"></i>Complete
                                                </button>
                                                
                                                <!-- Modal for completing task -->
                                                <div class="modal fade" id="completeTaskModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Complete Task: <?php echo htmlspecialchars($row['title']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="complete_task" value="1">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Attach File (Optional)</label>
                                                                        <input type="file" name="user_attachment" class="form-control">
                                                                        <small class="text-muted">Upload any required documents for this task.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i>Mark as Completed</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-success w-100">Completed</span>
                                                    <?php if($row['user_attachment']): ?>
                                                        <a href="../assets/uploads/<?php echo htmlspecialchars($row['user_attachment']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-file me-1"></i>Your File
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            } else {
                                echo '<tr><td colspan="6" class="text-center text-muted py-4">No tasks assigned yet</td></tr>';
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
