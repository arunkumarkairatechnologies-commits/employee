<?php
// admin/users.php - List and manage all users
session_start();
require_once '../config/db.php';

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Prevent deleting yourself
    if ($delete_id !== $_SESSION['user_id']) {
        // Fetch user to delete their image
        $stmt = $conn->prepare('SELECT profile_image FROM users WHERE id = ?');
        $stmt->bind_param('i', $delete_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        // Delete the user
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $delete_id);
        $stmt->execute();
        $stmt->close();

        // Delete profile image if exists
        if ($user_data && $user_data['profile_image']) {
            $img_path = '../assets/img/' . $user_data['profile_image'];
            if (file_exists($img_path)) {
                unlink($img_path);
            }
        }

        header('Location: users.php');
        exit;
    }
}

// Fetch all users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = 'SELECT id, name, email, phone, designation, role, profile_image FROM users';
if ($search) {
    $search_term = '%' . $search . '%';
    $query .= ' WHERE name LIKE ? OR email LIKE ? OR role LIKE ? OR designation LIKE ?';
    $stmt = $conn->prepare($query . ' ORDER BY name ASC');
    $stmt->bind_param('ssss', $search_term, $search_term, $search_term, $search_term);
} else {
    $stmt = $conn->prepare($query . ' ORDER BY name ASC');
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    // Trim the role value to remove any whitespace
    if (isset($row['role']) && !empty($row['role'])) {
        $row['role'] = trim($row['role']);
    } else {
        $row['role'] = 'employee'; // Default to employee if role is empty
    }
    $users[] = $row;
}
$stmt->close();

include '../includes/header.php';
?>

<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <h2 class="mt-4">Manage Users</h2>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <form method="GET" class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or role..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <?php if ($search): ?>
                        <a href="users.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Designation</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?php
                                    $profile_img = !empty($user['profile_image']) ? '../assets/img/' . htmlspecialchars($user['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random';
                                    ?>
                                    <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random'">
                                </td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['designation'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $role = (isset($user['role']) && !empty($user['role'])) ? trim($user['role']) : 'employee';
                                    
                                    // Text transform - capitalize for display
                                    $role_display = ucfirst(strtolower($role));
                                    
                                    $badge_colors = [
                                        'admin' => '#dc3545',
                                        'employee' => '#6c757d',
                                        'web development' => '#0d6efd',
                                        'designers' => '#198754',
                                        'smm' => '#fd7e14',
                                        'seo' => '#ffc107',
                                        'crm' => '#17a2b8',
                                        'video editing' => '#6610f2'
                                    ];
                                    
                                    // Normalize role key to lowercase for lookup
                                    $role_key = strtolower($role);
                                    $bg_color = isset($badge_colors[$role_key]) ? $badge_colors[$role_key] : '#6c757d';
                                    ?>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($bg_color); ?>; font-size: 0.85rem; padding: 0.4rem 0.7rem; color: white; display: inline-block;">
                                        <?php echo htmlspecialchars($role_display); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <a href="users.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Current</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No users found.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
