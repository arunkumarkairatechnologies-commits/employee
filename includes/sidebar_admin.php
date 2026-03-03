<?php
// includes/sidebar_admin.php
?>
<div class="sidebar-admin">
    <div class="sidebar-header">
        <a href="../admin/dashboard.php" class="sidebar-brand">
            <div class="sidebar-logo-circle me-2">
                <img src="../logo.png" alt="Insight CRM Logo">
            </div>
            <span class="badge bg-success ms-1" style="font-size: 0.5rem; padding: 2px 4px;">ADMIN</span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <a href="../admin/dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>Home
        </a>

        <div class="sidebar-category">MANAGEMENT</div>
        
        <a href="../admin/users.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>Manage Users
        </a>
        <a href="../admin/add_user.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_user.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-plus"></i>Add Employee
        </a>

        <div class="sidebar-category">OPERATIONS</div>

        <a href="../admin/tasks.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
            <i class="fas fa-list-check"></i>View Tasks
        </a>
        <a href="../admin/add_task.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_task.php' ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i>Assign Task
        </a>
        <div class="sidebar-category">RECORDS</div>

        <a href="../admin/attendance.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>Attendance
        </a>
        <a href="../admin/leaves.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'leaves.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-minus"></i>Manage Leaves
        </a>
        <a href="../admin/reports.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>Reports
        </a>
        
        <div class="sidebar-category">COMMUNICATION</div>

        <a href="../admin/announcements.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>Announcements
        </a>
        <a href="../admin/hub.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'hub.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>Social Hub
        </a>
        <a href="../admin/chat.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i>Messages
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <?php 
            $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
            $user_img = '../assets/img/admin.png';
            ?>
            <img src="<?php echo file_exists($_SERVER['DOCUMENT_ROOT'] . str_replace('\\', '/', $user_img)) ? $user_img : 'https://ui-avatars.com/api/?name='.urlencode($user_name).'&background=random'; ?>" 
                 alt="Profile" class="profile-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=random'">
            <div class="profile-info">
                <p class="profile-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?></p>
                <p class="profile-role">Administrator</p>
            </div>
        </div>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>
