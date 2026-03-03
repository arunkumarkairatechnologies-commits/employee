<?php
// checkin.php - Employee daily check-in/check-out with Indian time and improved UI
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

// Indian Timezone
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');
$user_id = $_SESSION['user_id'];
$current_time = date('h:i:s A');

// Handle AJAX check-in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $now = date('H:i:s');
    if ($action === 'checkin') {
        // Only one check-in per day
        $stmt = $conn->prepare('INSERT IGNORE INTO attendance (user_id, date, check_in) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $user_id, $today, $now);
        $stmt->execute();
        $stmt->close();
        exit('success');
    } elseif ($action === 'checkout') {
        // Only one check-out per day
        $stmt = $conn->prepare('UPDATE attendance SET check_out=? WHERE user_id=? AND date=? AND check_out IS NULL');
        $stmt->bind_param('sis', $now, $user_id, $today);
        $stmt->execute();
        $stmt->close();
        exit('success');
    }
}
// Get today's attendance
$att = $conn->query("SELECT * FROM attendance WHERE user_id=$user_id AND date='$today'")->fetch_assoc();
?>
<div class="page-container">
    <?php include '../includes/sidebar_user.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <h2 class="mb-4"><i class="fas fa-clock me-2"></i>Daily Check-In / Check-Out</h2>
            
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="mb-4">
                                    <p class="mb-2">
                                        <strong><i class="fas fa-calendar-alt me-2"></i>Date:</strong><br>
                                        <span class="badge bg-info text-dark fs-6 mt-2"><?php echo $today; ?></span>
                                    </p>
                                </div>
                                <div class="mb-4">
                                    <p class="mb-2">
                                        <strong><i class="fas fa-hourglass-start me-2"></i>Current Time (IST):</strong><br>
                                        <span class="badge bg-secondary fs-6 mt-2" id="current-time"><?php echo $current_time; ?></span>
                                    </p>
                                </div>
                                <div class="mb-4">
                                    <p class="mb-2">
                                        <strong><i class="fas fa-sign-in-alt me-2"></i>Check-In:</strong><br>
                                        <span class="badge bg-<?php echo isset($att['check_in']) ? 'success' : 'danger'; ?> fs-6 mt-2">
                                            <?php echo isset($att['check_in']) ? date('h:i A', strtotime($att['check_in'])) : 'Not checked in'; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="mb-4">
                                    <p class="mb-2">
                                        <strong><i class="fas fa-sign-out-alt me-2"></i>Check-Out:</strong><br>
                                        <span class="badge bg-<?php echo isset($att['check_out']) ? 'primary' : 'danger'; ?> fs-6 mt-2">
                                            <?php echo isset($att['check_out']) ? date('h:i A', strtotime($att['check_out'])) : 'Not checked out'; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-lg-6 text-center">
                                <button id="checkin-btn" class="btn btn-success btn-lg mb-3 w-75 rounded-pill shadow-sm" <?php echo isset($att['check_in']) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-arrow-right-to-bracket me-2"></i>Check-In
                                </button>
                                <button id="checkout-btn" class="btn btn-primary btn-lg w-75 rounded-pill shadow-sm" <?php echo (isset($att['check_in']) && !isset($att['check_out'])) ? '' : 'disabled'; ?>>
                                    <i class="fas fa-arrow-left-from-bracket me-2"></i>Check-Out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tips Card -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-gradient text-white border-0">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Attendance Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item border-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Check-in only once per day (Indian Time).
                            </li>
                            <li class="list-group-item border-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Check-out only after check-in.
                            </li>
                            <li class="list-group-item border-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Contact admin for attendance corrections.
                            </li>
                            <li class="list-group-item border-0">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Regular attendance is essential for performance.
                            </li>
                        </ul>
                    </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="../assets/js/ajax.js"></script>
<?php include '../includes/footer.php'; ?>
