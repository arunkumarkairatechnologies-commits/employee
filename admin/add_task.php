<?php
// add_task.php - Admin can assign tasks to employees
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $employee_ids = isset($_POST['employee_ids']) ? $_POST['employee_ids'] : []; // Array of employee IDs
    $observer_ids = isset($_POST['observer_ids']) ? $_POST['observer_ids'] : [];
    $observer_id_str = !empty($observer_ids) ? implode(',', $observer_ids) : null;
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($due_date) || empty($employee_ids)) {
        $error = 'Title, Description, Due Date, and Employees are required.';
    } else {
        // Handle file upload
        $attachment_name = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_name = time() . '_' . basename($_FILES['attachment']['name']);
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
                $attachment_name = $file_name;
            }
        }

        $success_count = 0;
        $error_count = 0;

        foreach ($employee_ids as $emp_id) {
            $stmt = $conn->prepare('INSERT INTO tasks (title, description, due_date, employee_id, observer_id, attachment) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssiss', $title, $description, $due_date, $emp_id, $observer_id_str, $attachment_name);
            if ($stmt->execute()) {
                // Notify employee
                $msg = 'New task assigned: ' . $title;
                $notif = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
                $notif->bind_param('is', $emp_id, $msg);
                $notif->execute();
                
                // Notify observers if set
                if (!empty($observer_ids)) {
                    foreach ($observer_ids as $obs_id) {
                        if ($obs_id != $emp_id) {
                            $obs_msg = 'You have been added as an observer to task: ' . $title;
                            $notif->bind_param('is', $obs_id, $obs_msg);
                            $notif->execute();
                        }
                    }
                }
                
                $notif->close();
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }

        if ($success_count > 0) {
            $success = "Task has been successfully assigned.";
        }
        if ($error_count > 0) {
            $error = "Failed to assign task to $error_count employee(s).";
        }
    }
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Assign New Task</h2>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><strong>Error!</strong> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($success)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        alert("<?php echo addslashes($success); ?>");
                    });
                </script>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><strong>Success!</strong> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-600"><i class="fas fa-heading me-2"></i>Task Title *</label>
                                    <input type="text" name="title" class="form-control form-control-lg" placeholder="Enter task title" required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-600"><i class="fas fa-calendar me-2"></i>Due Date *</label>
                                    <input type="date" name="due_date" class="form-control form-control-lg" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-600"><i class="fas fa-users me-2"></i>Assign To (Multiple allowed) *</label>
                                    <select name="employee_ids[]" class="form-select form-select-lg" multiple required style="height: 120px;">
                                        <?php
                                        $emps = $conn->query("SELECT id, name FROM users WHERE role='employee' ORDER BY name ASC");
                                        while ($emp = $emps->fetch_assoc()): ?>
                                            <option value="<?php echo $emp['id']; ?>">
                                                <?php echo htmlspecialchars($emp['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Hold CTRL (or CMD on Mac) to select multiple employees.</small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-600"><i class="fas fa-paperclip me-2"></i>Attachment (Optional)</label>
                                    <input type="file" name="attachment" class="form-control form-control-lg">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-600"><i class="fas fa-eye me-2"></i>Observer (Optional)</label>
                                    <select name="observer_ids[]" class="form-select form-select-lg" multiple style="height: 120px;">
                                        <?php
                                        // Usually observers can be anyone but often an employee. Let's list everyone or just employees. Let's do employees + admins.
                                        $users = $conn->query("SELECT id, name, role FROM users ORDER BY name ASC");
                                        while ($u = $users->fetch_assoc()): ?>
                                            <option value="<?php echo $u['id']; ?>">
                                                <?php echo htmlspecialchars($u['name']) . ' (' . ucfirst($u['role']) . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Hold CTRL (or CMD on Mac) to select multiple users.</small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-600"><i class="fas fa-file-alt me-2"></i>Description *</label>
                                    <textarea name="description" class="form-control" placeholder="Enter task description" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Assign Task
                            </button>
                            <a href="tasks.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
