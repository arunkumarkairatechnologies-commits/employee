<?php
// attendance.php - Employee can view own attendance history
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
            <h2 class="mb-4"><i class="fas fa-history me-2"></i>My Attendance History</h2>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                        <th>Date</th>
                                        <th>Check-In</th>
                                        <th>Check-Out</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $att = $conn->query("SELECT * FROM attendance WHERE user_id={$_SESSION['user_id']} ORDER BY date DESC");
                                if ($att->num_rows > 0) {
                                    while ($row = $att->fetch_assoc()): 
                                        $check_in = $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : 'N/A';
                                        $check_out = $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'N/A';
                                        
                                        // Calculate duration
                                        $duration = 'Leave';
                                        if ($row['check_in'] && $row['check_out']) {
                                            $start = new DateTime($row['check_in']);
                                            $end = new DateTime($row['check_out']);
                                            $diff = $start->diff($end);
                                            $hours = $diff->h + ($diff->days * 24);
                                            $minutes = $diff->i;
                                            $total_hours = $hours + ($minutes / 60);
                                            
                                            if ($total_hours >= 8) {
                                                $duration = 'Full Day';
                                            } elseif ($total_hours >= 4) {
                                                $duration = 'Half Day';
                                            } else {
                                                $duration = 'Half Day';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><small><?php echo date('M d, Y', strtotime($row['date'])); ?></small></td>
                                            <td><span class="badge bg-success"><?php echo $check_in; ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo $check_out; ?></span></td>
                                            <td><small class="text-muted"><?php echo $duration; ?></small></td>
                                        </tr>
                                    <?php endwhile;
                                } else {
                                    echo '<tr><td colspan="4" class="text-center text-muted py-4">No attendance records</td></tr>';
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
<?php include '../includes/footer.php'; ?>
<script>
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
