<?php
// user/leaves.php - Employee can apply for leaves and view leave history
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];
    
    if (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date cannot be earlier than start date.";
    } else {
        $stmt = $conn->prepare("INSERT INTO leaves (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_SESSION['user_id'], $start_date, $end_date, $reason);
        if ($stmt->execute()) {
            $message = "Leave application submitted successfully.";
            
            // Notify admin
            $admin_res = $conn->query("SELECT id FROM users WHERE role='admin'");
            if ($admin_res && $admin_res->num_rows > 0) {
                while($admin = $admin_res->fetch_assoc()) {
                    $msg = 'Employee ' . $_SESSION['user_name'] . ' has applied for leave.';
                    $notif = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
                    $notif->bind_param('is', $admin['id'], $msg);
                    $notif->execute();
                    $notif->close();
                }
            }
        } else {
            $error = "An error occurred while submitting your application.";
        }
        $stmt->close();
    }
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-calendar-minus me-2"></i>My Leaves</h2>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h5 class="card-title mb-0">Apply for Leave</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea name="reason" class="form-control" rows="4" required placeholder="State your reason for leave..."></textarea>
                                </div>
                                <button type="submit" name="apply_leave" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h5 class="card-title mb-0">Leave History</h5>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Dates</th>
                                            <th>Reason</th>
                                            <th>Applied On</th>
                                            <th class="pe-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $leaves = $conn->query("SELECT * FROM leaves WHERE user_id={$_SESSION['user_id']} ORDER BY created_at DESC");
                                    if ($leaves->num_rows > 0) {
                                        while ($row = $leaves->fetch_assoc()): 
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <?php echo date('M d, Y', strtotime($row['start_date'])); ?> 
                                                    <i class="fas fa-arrow-right mx-1 text-muted"></i> 
                                                    <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                                </td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($row['reason']); ?></small></td>
                                                <td><small class="text-muted"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></td>
                                                <td class="pe-4">
                                                    <?php 
                                                    $badge_class = 'danger';
                                                    if ($row['status'] == 'approved') $badge_class = 'success';
                                                    if ($row['status'] == 'rejected') $badge_class = 'danger';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile;
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center text-muted py-4">No leave history found</td></tr>';
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
<?php include '../includes/footer.php'; ?>
