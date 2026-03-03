<?php
// admin/leaves.php - Admin can manage employee leaves requests
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['leave_id'])) {
    $leave_id = intval($_POST['leave_id']);
    $action = $_POST['action'];
    
    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE leaves SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $leave_id);
        if ($stmt->execute()) {
            $message = "Leave application " . ucfirst($action) . " successfully.";
            
            // Notify user
            $user_id_query = $conn->query("SELECT user_id FROM leaves WHERE id = $leave_id")->fetch_assoc();
            if ($user_id_query) {
                $uid = $user_id_query['user_id'];
                $msg = "Your leave application has been " . $action . ".";
                $notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notif->bind_param("is", $uid, $msg);
                $notif->execute();
                $notif->close();
            }
        } else {
            $error = "Error updating leave status.";
        }
        $stmt->close();
    }
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-calendar-minus me-2"></i>Manage Leaves</h2>
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
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Employee</th>
                                    <th>Dates</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $leaves = $conn->query("SELECT l.*, u.name as employee_name FROM leaves l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
                            if ($leaves->num_rows > 0) {
                                while ($row = $leaves->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td class="ps-4"><strong><?php echo htmlspecialchars($row['employee_name']); ?></strong></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($row['start_date'])); ?> 
                                            <i class="fas fa-arrow-right mx-1 text-muted"></i> 
                                            <?php echo date('M d, Y', strtotime($row['end_date'])); ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($row['reason']); ?></small></td>
                                        <td>
                                            <?php 
                                            $badge_class = 'danger';
                                            if ($row['status'] == 'approved') $badge_class = 'success';
                                            if ($row['status'] == 'rejected') $badge_class = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if($row['status'] == 'pending'): ?>
                                                <form method="POST" class="d-inline-block">
                                                    <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" class="btn btn-sm btn-success shadow-sm" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline-block">
                                                    <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action" value="rejected">
                                                    <button type="submit" class="btn btn-sm btn-danger shadow-sm" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary shadow-sm" disabled>
                                                    <i class="fas <?php echo $row['status'] == 'approved' ? 'fa-check' : 'fa-times'; ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted py-4">No leave requests found</td></tr>';
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
<?php include '../includes/footer.php'; ?>
