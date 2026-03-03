<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="mb-4">
                <h2 class="mb-1"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                <p class="text-muted mb-0">Welcome back! Here's what's happening.</p>
            </div>
            
            <?php
            // Stats
            $emp_count = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='employee'")->fetch_assoc()['cnt'];
            $task_count = $conn->query("SELECT COUNT(*) AS cnt FROM tasks")->fetch_assoc()['cnt'];
            $pending_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status='pending'")->fetch_assoc()['cnt'];
            $completed_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status='completed'")->fetch_assoc()['cnt'];
            $pending_leaves = $conn->query("SELECT COUNT(*) AS cnt FROM leaves WHERE status='pending'")->fetch_assoc()['cnt'] ?? 0;
            $unread_notifs = $conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id={$_SESSION['user_id']} AND is_read=0")->fetch_assoc()['cnt'] ?? 0;
            $completion_rate = ($task_count > 0) ? (int)round(($completed_tasks / $task_count) * 100) : 0;
            ?>
            
            <!-- Dashboard Cards -->
            <div class="row mb-5 mt-2">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="modern-stat-card shadow-sm h-100 bg-vibrant-1 text-white">
                        <div class="modern-stat-icon text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="modern-stat-title">Employees</div>
                        <h3 class="modern-stat-value"><?php echo sprintf("%02d", $emp_count); ?></h3>
                    </div>
                </div>
                    
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="modern-stat-card shadow-sm h-100 bg-vibrant-2 text-white">
                        <div class="modern-stat-icon text-purple" style="color: #8b5cf6;">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="modern-stat-title">Total Tasks</div>
                        <h3 class="modern-stat-value"><?php echo sprintf("%02d", $task_count); ?></h3>
                    </div>
                </div>
                    
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="modern-stat-card shadow-sm h-100 bg-vibrant-5 text-white">
                        <div class="modern-stat-icon text-danger" style="color: #ef4444;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="modern-stat-title">Pending Tasks</div>
                        <h3 class="modern-stat-value"><?php echo sprintf("%02d", $pending_tasks); ?></h3>
                    </div>
                </div>
                    
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="modern-stat-card shadow-sm h-100 bg-vibrant-4 text-white">
                        <div class="modern-stat-icon text-success" style="color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="modern-stat-title">Completed Tasks</div>
                        <h3 class="modern-stat-value"><?php echo sprintf("%02d", $completed_tasks); ?></h3>
                    </div>
                </div>
            </div>


                <div class="row g-3 mb-4">
                    <!-- Pending Tasks -->
                    <div class="col-lg-6 d-flex">
                        <div class="card border-0 shadow-sm rounded-4 flex-fill">
                            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-calendar-day me-2 text-primary"></i>Upcoming Pending Tasks</h5>
                                <a href="../admin/tasks.php" class="btn btn-sm btn-outline-secondary rounded-pill">View all</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="bg-light text-muted" style="font-size: 0.85rem;">
                                            <tr>
                                                <th class="ps-4">Task</th>
                                                <th>Employee</th>
                                                <th class="pe-4 text-end">Due</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $upcoming = $conn->query("SELECT t.id, t.title, t.due_date, u.name AS emp_name 
                                                                     FROM tasks t JOIN users u ON t.employee_id = u.id 
                                                                     WHERE t.status='pending' ORDER BY t.due_date ASC LIMIT 5");
                                            if ($upcoming && $upcoming->num_rows > 0) {
                                                while ($t = $upcoming->fetch_assoc()):
                                                    $due = new DateTime($t['due_date']);
                                                    $now = new DateTime('today');
                                                    $is_overdue = $due < $now;
                                                    ?>
                                                    <tr>
                                                        <td class="ps-4 py-3 fw-medium">
                                                            <a class="text-decoration-none text-dark hover-link" href="../admin/view_task.php?id=<?php echo (int)$t['id']; ?>">
                                                                <i class="fas fa-tasks me-2 text-primary"></i><?php echo htmlspecialchars($t['title']); ?>
                                                            </a>
                                                        </td>
                                                        <td class="py-3"><span class="text-muted"><?php echo htmlspecialchars($t['emp_name']); ?></span></td>
                                                        <td class="pe-4 py-3 text-end">
                                                            <span class="badge <?php echo $is_overdue ? 'bg-danger text-white' : 'bg-light text-dark border'; ?> rounded-pill">
                                                                <?php echo date('M d', strtotime($t['due_date'])); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile;
                                            } else {
                                                echo '<tr><td colspan="3" class="text-center text-muted py-4">No pending tasks found.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Leaves -->
                    <div class="col-lg-6 d-flex">
                        <div class="card border-0 shadow-sm rounded-4 flex-fill">
                            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-calendar-minus me-2 text-danger"></i>Recent Leave Requests</h5>
                                <a href="../admin/leaves.php" class="btn btn-sm btn-outline-secondary rounded-pill">Manage</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="bg-light text-muted" style="font-size: 0.85rem;">
                                            <tr>
                                                <th class="ps-4">Employee</th>
                                                <th>Dates</th>
                                                <th class="pe-4 text-end">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $leaves_query = "SELECT l.id, l.start_date, l.end_date, l.status, u.name AS emp_name FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5";
                                            $recent_leaves = $conn->query($leaves_query);
                                            if ($recent_leaves && $recent_leaves->num_rows > 0) {
                                                while ($l = $recent_leaves->fetch_assoc()):
                                                    $bc = 'danger';
                                                    if ($l['status'] == 'approved') $bc = 'success';
                                                    if ($l['status'] == 'rejected') $bc = 'danger';
                                                    ?>
                                                    <tr>
                                                        <td class="ps-4 py-3 fw-medium">
                                                            <i class="fas fa-user-clock me-2 text-muted"></i><?php echo htmlspecialchars($l['emp_name']); ?>
                                                        </td>
                                                        <td class="py-3 text-muted" style="font-size: 0.9rem;">
                                                            <?php echo date('M d', strtotime($l['start_date'])) . ' - ' . date('M d', strtotime($l['end_date'])); ?>
                                                        </td>
                                                        <td class="pe-4 py-3 text-end">
                                                            <span class="badge bg-<?php echo $bc; ?> rounded-pill"><?php echo ucfirst($l['status']); ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile;
                                            } else {
                                                echo '<tr><td colspan="3" class="text-center text-muted py-4">No recent leave requests.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Notifications -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white border-bottom p-4">
                                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-bell me-2 text-primary"></i>Recent Notifications</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="bg-light text-muted" style="font-size: 0.85rem;">
                                            <tr>
                                                <th class="ps-4">Message</th>
                                                <th>Date</th>
                                                <th class="pe-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $notif = $conn->query("SELECT * FROM notifications WHERE user_id={$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 10");
                                        if ($notif->num_rows > 0) {
                                            while ($row = $notif->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 py-3 fw-medium">
                                                        <?php 
                                                            $has_link = isset($row['link']) && !empty($row['link']);
                                                            $open_tag = $has_link ? '<a href="'.htmlspecialchars($row['link']).'" class="text-decoration-none">' : '<div>';
                                                            $close_tag = $has_link ? '</a>' : '</div>';
                                                        ?>
                                                        <?php if(strpos($row['message'], 'New Announcement:') === 0): ?>
                                                            <?php echo $open_tag; ?>
                                                            <div class="text-danger flex align-items-center">
                                                                <i class="fas fa-bullhorn me-2"></i><?php echo htmlspecialchars($row['message']); ?>
                                                            </div>
                                                            <?php echo $close_tag; ?>
                                                        <?php else: ?>
                                                            <?php echo $open_tag; ?>
                                                            <div class="text-dark">
                                                                <?php echo htmlspecialchars($row['message']); ?>
                                                            </div>
                                                            <?php echo $close_tag; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3"><small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></small></td>
                                                    <td class="pe-4 py-3">
                                                        <span class="badge rounded-pill bg-<?php echo $row['is_read'] ? 'light text-muted border' : 'primary'; ?> px-3 py-2">
                                                            <i class="fas <?php echo $row['is_read'] ? 'fa-check' : 'fa-circle'; ?> me-1" style="<?php echo $row['is_read'] ? '' : 'font-size: 0.5rem; vertical-align: middle;'; ?>"></i>
                                                            <?php echo $row['is_read'] ? 'Read' : 'Unread'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile;
                                        } else {
                                            echo '<tr><td colspan="3" class="text-center text-muted py-4">No notifications</td></tr>';
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
