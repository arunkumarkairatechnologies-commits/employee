<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';
?>
<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="mb-4">
                <h2 class="mb-1"><i class="fas fa-chart-pie me-2"></i>My Dashboard</h2>
                <p class="text-muted mb-0">Track your tasks and performance.</p>
            </div>
            
            <?php
            // Stats
            $my_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE employee_id={$_SESSION['user_id']}")->fetch_assoc()['cnt'];
            $pending_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE employee_id={$_SESSION['user_id']} AND status='pending'")->fetch_assoc()['cnt'];
                $completed_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE employee_id={$_SESSION['user_id']} AND status='completed'")->fetch_assoc()['cnt'];
                $attendance_days = $conn->query("SELECT COUNT(*) AS cnt FROM attendance WHERE user_id={$_SESSION['user_id']}")->fetch_assoc()['cnt'];
                $pending_leaves = $conn->query("SELECT COUNT(*) AS cnt FROM leaves WHERE user_id={$_SESSION['user_id']} AND status='pending'")->fetch_assoc()['cnt'] ?? 0;
                $unread_notifs = $conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id={$_SESSION['user_id']} AND is_read=0")->fetch_assoc()['cnt'] ?? 0;
                $completion_rate = ($my_tasks > 0) ? (int)round(($completed_tasks / $my_tasks) * 100) : 0;
                ?>
                
                <!-- Dashboard Cards -->
                <div class="row mb-5 mt-2">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="modern-stat-card shadow-sm h-100 bg-vibrant-1 text-white">
                            <div class="modern-stat-icon text-primary">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="modern-stat-title">My Tasks</div>
                            <h3 class="modern-stat-value"><?php echo sprintf("%02d", $my_tasks); ?></h3>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="modern-stat-card shadow-sm h-100 bg-vibrant-5 text-white">
                            <div class="modern-stat-icon text-danger" style="color: #ef4444;">
                                <i class="fas fa-clock"></i>
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
                    
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="modern-stat-card shadow-sm h-100 bg-vibrant-2 text-white">
                            <div class="modern-stat-icon text-purple" style="color: #8b5cf6;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="modern-stat-title">Attendance Days</div>
                            <h3 class="modern-stat-value"><?php echo sprintf("%02d", $attendance_days); ?></h3>
                        </div>
                    </div>
                </div>
                


                <div class="row g-3 mb-4">
                    <div class="col-lg-12">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-calendar-day me-2 text-primary"></i>Upcoming Tasks</h5>
                                <a href="../user/my_tasks.php" class="btn btn-sm btn-outline-secondary rounded-pill">View all</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="bg-light text-muted" style="font-size: 0.85rem;">
                                            <tr>
                                                <th class="ps-4">Task</th>
                                                <th>Due</th>
                                                <th class="pe-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $upcoming = $conn->query("SELECT id, title, due_date, status
                                                                     FROM tasks
                                                                     WHERE employee_id={$_SESSION['user_id']} AND status='pending'
                                                                     ORDER BY due_date ASC
                                                                     LIMIT 8");
                                            if ($upcoming && $upcoming->num_rows > 0) {
                                                while ($t = $upcoming->fetch_assoc()):
                                                    $due = new DateTime($t['due_date']);
                                                    $now = new DateTime('today');
                                                    $is_overdue = $due < $now;
                                                    ?>
                                                    <tr>
                                                        <td class="ps-4 py-3 fw-medium">
                                                            <a class="text-decoration-none text-dark hover-link" href="../user/view_task.php?id=<?php echo (int)$t['id']; ?>">
                                                                <i class="fas fa-tasks me-2 text-primary"></i><?php echo htmlspecialchars($t['title']); ?>
                                                            </a>
                                                        </td>
                                                        <td class="py-3">
                                                            <span class="<?php echo $is_overdue ? 'text-danger fw-semibold' : 'text-muted'; ?>">
                                                                <i class="far fa-clock me-1"></i><?php echo date('M d, Y', strtotime($t['due_date'])); ?>
                                                            </span>
                                                        </td>
                                                        <td class="pe-4 py-3">
                                                            <span class="badge rounded-pill bg-danger text-white px-3 py-2">
                                                                <i class="fas fa-clock me-1"></i>Pending
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile;
                                            } else {
                                                echo '<tr><td colspan="3" class="text-center text-muted py-4">No upcoming pending tasks.</td></tr>';
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
<script>
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
