<?php
// reports.php - Admin can download employee-wise reports (CSV & PDF)
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Get filter parameters
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$day = isset($_GET['day']) ? intval($_GET['day']) : 0;

// Build query filters
$where = "";
$params = [];
$types = "";

if ($employee_id > 0) {
    $where .= " AND t.employee_id = ?";
    $params[] = $employee_id;
    $types .= "i";
}

if ($month > 0) {
    $where .= " AND MONTH(t.due_date) = ? AND YEAR(t.due_date) = ?";
    $params[] = $month;
    $params[] = $year;
    $types .= "ii";
}

if ($day > 0) {
    $where .= " AND DAY(t.due_date) = ?";
    $params[] = $day;
    $types .= "i";
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employee_report_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee', 'Role', 'Task Title', 'Description', 'Status', 'Due Date', 'Completed At']);
    
    $query = "SELECT u.name, u.role, t.title, t.description, t.status, t.due_date, t.completed_at FROM tasks t JOIN users u ON t.employee_id=u.id WHERE 1=1 " . $where . " ORDER BY u.name, t.due_date DESC";
    
    if ($types) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $tasks = $stmt->get_result();
    } else {
        $tasks = $conn->query($query);
    }
    
    while ($row = $tasks->fetch_assoc()) {
        fputcsv($out, [$row['name'], $row['role'], $row['title'], $row['description'], $row['status'], $row['due_date'], $row['completed_at']]);
    }
    fclose($out);
    exit;
}

// PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    ob_start();
    
    // Get employee name for title
    $emp_name = 'All Employees';
    if ($employee_id > 0) {
        $stmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $emp_result = $stmt->get_result();
        if ($emp_result->num_rows > 0) {
            $emp_row = $emp_result->fetch_assoc();
            $emp_name = $emp_row['name'];
        }
        $stmt->close();
    }
    
    $date_filter = '';
    if ($month > 0) {
        $date_filter .= ' - ' . date('F Y', mktime(0, 0, 0, $month, 1, $year));
    }
    if ($day > 0) {
        $date_filter = ' - Day ' . $day . $date_filter;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Employee Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 15px;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                color: #2c3e50;
            }
            .header p {
                margin: 5px 0;
                color: #666;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th {
                background-color: #2c3e50;
                color: white;
                padding: 10px;
                text-align: left;
                font-weight: bold;
            }
            td {
                padding: 8px;
                border-bottom: 1px solid #ddd;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-weight: bold;
                font-size: 11px;
            }
            .status-pending {
                background-color: #ffc107;
                color: #000;
            }
            .status-completed {
                background-color: #28a745;
                color: white;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                color: #666;
                font-size: 10px;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }
            .summary {
                margin-top: 20px;
                background-color: #ecf0f1;
                padding: 12px;
                border-left: 4px solid #2c3e50;
            }
            .summary p {
                margin: 5px 0;
                color: #333;
            }
            .description {
                font-size: 11px;
                color: #555;
                padding: 5px;
                background-color: #f0f0f0;
                border-radius: 3px;
                word-wrap: break-word;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Employee Report<?php echo htmlspecialchars($date_filter); ?></h1>
            <p>Employee: <?php echo htmlspecialchars($emp_name); ?></p>
            <p>Generated on: <?php echo date('F d, Y \a\t h:i A'); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Employee</th>
                    <th style="width: 10%;">Role</th>
                    <th style="width: 20%;">Task Title</th>
                    <th style="width: 25%;">Description</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 12%;">Due Date</th>
                    <th style="width: 11%;">Completed At</th>
                </tr>
            </thead>
            <tbody>
    <?php
    $query = "SELECT u.id, u.name, u.role, t.title, t.description, t.status, t.due_date, t.completed_at FROM tasks t JOIN users u ON t.employee_id=u.id WHERE 1=1 " . $where . " ORDER BY u.name, t.due_date DESC";
    
    if ($types) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $tasks = $stmt->get_result();
    } else {
        $tasks = $conn->query($query);
    }
    
    $total_tasks = 0;
    $completed_tasks = 0;
    
    if ($tasks->num_rows > 0) {
        while ($row = $tasks->fetch_assoc()):
            $total_tasks++;
            if ($row['status'] == 'completed') {
                $completed_tasks++;
            }
            $status_class = $row['status'] == 'completed' ? 'status-completed' : 'status-pending';
            $status_text = ucfirst($row['status']);
            $due_date = date('M d, Y', strtotime($row['due_date']));
            $completed_at = $row['completed_at'] ? date('M d, Y', strtotime($row['completed_at'])) : 'Pending';
            $description = !empty($row['description']) ? substr($row['description'], 0, 100) . (strlen($row['description']) > 100 ? '...' : '') : 'N/A';
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><div class="description"><?php echo htmlspecialchars($description); ?></div></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                    <td><?php echo $due_date; ?></td>
                    <td><?php echo $completed_at; ?></td>
                </tr>
            <?php
        endwhile;
    } else {
        echo '<tr><td colspan="7" style="text-align: center; color: #999;">No task records found</td></tr>';
    }
    ?>
            </tbody>
        </table>
        
        <?php if ($total_tasks > 0): ?>
        <div class="summary">
            <p><strong>Summary:</strong></p>
            <p>Total Tasks: <?php echo $total_tasks; ?> | Completed: <?php echo $completed_tasks; ?> | Pending: <?php echo ($total_tasks - $completed_tasks); ?></p>
            <p>Completion Rate: <?php echo round(($completed_tasks / $total_tasks) * 100, 2); ?>%</p>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>This is an automatically generated report. For more information, contact the administration.</p>
            <p>&copy; <?php echo date('Y'); ?> Employee Management System. All rights reserved.</p>
        </div>
    </body>
    </html>
    <?php
    
    $html = ob_get_clean();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="employee_report_' . date('Y-m-d_His') . '.pdf"');
    
    // Try DomPDF
    $pdf_file = dirname(__FILE__) . '/../vendor/autoload.php';
    if (file_exists($pdf_file)) {
        require_once $pdf_file;
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            echo $dompdf->output();
        } catch (Exception $e) {
            // Fallback to HTML
            require_once '../includes/pdf_generator.php';
            generateSimplePDF($html, 'employee_report_' . date('Y-m-d_His'));
        }
    } else {
        require_once '../includes/pdf_generator.php';
        generateSimplePDF($html, 'employee_report_' . date('Y-m-d_His'));
    }
    exit;
}

include '../includes/header.php';

// Fetch all employees for dropdown
$employees = [];
$emp_result = $conn->query("SELECT id, name FROM users WHERE id != (SELECT id FROM users WHERE id = {$_SESSION['user_id']}) OR id = {$_SESSION['user_id']} ORDER BY name ASC");
while ($emp_row = $emp_result->fetch_assoc()) {
    $employees[] = $emp_row;
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="container-xxl py-4">
            <h2 class="mb-4"><i class="fas fa-file-alt me-2"></i>Employee Reports</h2>
            
            <!-- Filter Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filter Reports</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Select Employee</label>
                            <select name="employee_id" class="form-select">
                                <option value="0">All Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo ($employee_id == $emp['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select">
                                <option value="0">All Months</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($month == $m) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Day</label>
                            <select name="day" class="form-select">
                                <option value="0">All Days</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?php echo $d; ?>" <?php echo ($day == $d) ? 'selected' : ''; ?>>
                                        <?php echo str_pad($d, 2, '0', STR_PAD_LEFT); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Export Buttons -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <a href="reports.php?employee_id=<?php echo $employee_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&day=<?php echo $day; ?>&export=csv" class="btn btn-success">
                            <i class="fas fa-download me-1"></i>Download CSV
                        </a>
                        <a href="reports.php?employee_id=<?php echo $employee_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&day=<?php echo $day; ?>&export=pdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-1"></i>Download PDF
                        </a>
                        <?php if ($employee_id > 0 || $month > 0 || $day > 0): ?>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i>Reset Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Reports Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Role</th>
                                    <th>Task Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Completed At</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $query = "SELECT u.name, u.role, t.title, t.description, t.status, t.due_date, t.completed_at FROM tasks t JOIN users u ON t.employee_id=u.id WHERE 1=1 " . $where . " ORDER BY u.name, t.due_date DESC";
                            
                            if ($types) {
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $tasks = $stmt->get_result();
                            } else {
                                $tasks = $conn->query($query);
                            }
                            
                            if ($tasks->num_rows > 0) {
                                while ($row = $tasks->fetch_assoc()): 
                                    $description = !empty($row['description']) ? htmlspecialchars($row['description']) : 'N/A';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['role']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><small><?php echo $description; ?></small></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'pending' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo date('M d, Y', strtotime($row['due_date'])); ?></small></td>
                                        <td><small class="text-muted"><?php echo $row['completed_at'] ? date('M d, Y', strtotime($row['completed_at'])) : 'Pending'; ?></small></td>
                                    </tr>
                                <?php endwhile;
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted py-4">No task records found for the selected filters</td></tr>';
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
