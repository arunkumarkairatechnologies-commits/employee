<?php
// attendance.php - Admin can view employee attendance
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

// Filter by employee or month
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$where = '';
if ($filter_user) $where .= " AND a.user_id=$filter_user ";
if ($filter_month) $where .= " AND DATE_FORMAT(a.date,'%Y-%m')='$filter_month' ";
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <h2 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Employee Attendance</h2>
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-user me-2"></i>Select Employee</label>
                                <select name="user_id" class="form-select">
                                    <option value="">All Employees</option>
                                    <?php
                                    $emps = $conn->query("SELECT id, name FROM users WHERE role='employee'");
                                    while ($emp = $emps->fetch_assoc()): ?>
                                        <option value="<?php echo $emp['id']; ?>" <?php if($filter_user==$emp['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($emp['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-calendar-alt me-2"></i>Select Month</label>
                                <input type="month" name="month" class="form-control" value="<?php echo $filter_month; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
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
                                        <th>Employee</th>
                                        <th>Date</th>
                                        <th>Check-In</th>
                                        <th>Check-Out</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $att = $conn->query("SELECT a.*, u.name FROM attendance a JOIN users u ON a.user_id=u.id WHERE 1 $where ORDER BY a.date DESC");
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
                                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                            <td><small><?php echo date('M d, Y', strtotime($row['date'])); ?></small></td>
                                            <td><span class="badge bg-success"><?php echo $check_in; ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo $check_out; ?></span></td>
                                            <td><small class="text-muted"><?php echo $duration; ?></small></td>
                                        </tr>
                                    <?php endwhile;
                                } else {
                                    echo '<tr><td colspan="5" class="text-center text-muted py-4">No attendance records found</td></tr>';
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

<!-- Improved UI: Badges, tooltips, responsive table -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<style>
    .table th, .table td {
        vertical-align: middle;
    }
</style>
