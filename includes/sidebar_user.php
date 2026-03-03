<?php
// includes/sidebar_user.php
?>
<div class="sidebar-user">
    <div class="sidebar-header">
        <a href="../user/dashboard.php" class="sidebar-brand">
            <div class="sidebar-logo-circle me-2">
                <img src="../logo.png" alt="Insight CRM Logo">
            </div>
            <span class="badge bg-primary ms-1" style="font-size: 0.5rem; padding: 2px 4px;">USER</span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <a href="../user/dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>Dashboard
        </a>

        <div class="sidebar-category">WORKSPACES</div>

        <a href="../user/my_tasks.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_tasks.php' ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i>My Tasks
        </a>
        <div class="sidebar-category">TRACKING</div>

        <a href="../user/checkin.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'checkin.php' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i>Check-In/Out
        </a>
        <a href="../user/attendance.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>Attendance Log
        </a>
        <a href="../user/leaves.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'leaves.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-minus"></i>Leaves
        </a>

        <div class="sidebar-category">CONNECT</div>

        <a href="../user/announcements.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>Announcements
        </a>
        <a href="../user/hub.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'hub.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>Social Hub
        </a>
        <a href="../user/chat.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i>Messages
        </a>

        <div class="sidebar-category">SETTINGS</div>

        <a href="../user/edit_user.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'edit_user.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-edit"></i>Profile Settings
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <?php 
            $default_img = '../assets/img/user.png';
            $image_to_use = $default_img;
            
            if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])) {
                $upload_path = '../assets/img/' . $_SESSION['profile_image'];
                $abs_path = dirname(dirname(__FILE__)) . '/assets/img/' . $_SESSION['profile_image'];
                if (file_exists($abs_path)) {
                    $image_to_use = $upload_path;
                }
            }
            ?>
            <img src="<?php echo htmlspecialchars($image_to_use); ?>" 
                 alt="Profile" class="profile-img" onerror="this.src='https://via.placeholder.com/40'">
            <div class="profile-info">
                <p class="profile-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Employee'; ?></p>
                <p class="profile-role"><?php echo isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : 'Employee'; ?></p>
            </div>
        </div>
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>
