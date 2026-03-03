<?php
// admin/edit_user.php - Admins can edit employee profiles
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Get user ID from URL parameter
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Fetch user data
$stmt = $conn->prepare('SELECT id, name, email, phone, address, designation, role, profile_image, date_of_birth, joining_date FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Trim the role value
if ($user && isset($user['role'])) {
    $user['role'] = trim($user['role']);
}

if (!$user) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $designation = trim($_POST['designation']);
    $role = strtolower(trim($_POST['role']));
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : null;
    $profile_image = $user['profile_image'];

    // Validate required fields (allow any role text entered manually)
    if (empty($name) || empty($email)) {
        $error = 'Name and Email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } elseif (empty($role)) {
        $error = 'Role is required.';
    } else {
        // Check if email is already used by another user
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already exists.';
        }
        $stmt->close();

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir = '../assets/img/';
            $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES['profile_image']['tmp_name']);
            
            if ($check && in_array($imageFileType, ['jpg','jpeg','png','gif'])) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    // Delete old image if it exists
                    if ($user['profile_image']) {
                        $old_file = $target_dir . $user['profile_image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    $profile_image = $file_name;
                } else {
                    $error = 'Image upload failed.';
                }
            } else {
                $error = 'Invalid image file.';
            }
        }

        // Update user profile
        if (empty($error)) {
            $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ?, address = ?, designation = ?, role = ?, date_of_birth = ?, joining_date = ?, profile_image = ? WHERE id = ?');
            $stmt->bind_param('sssssssssi', $name, $email, $phone, $address, $designation, $role, $date_of_birth, $joining_date, $profile_image, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Employee updated successfully.';
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['address'] = $address;
                $user['designation'] = $designation;
                $user['role'] = $role;
                $user['date_of_birth'] = $date_of_birth;
                $user['joining_date'] = $joining_date;
                $user['profile_image'] = $profile_image;
            } else {
                $error = 'Failed to update employee: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="text-center mb-4 mt-4">
            <h2 class="fw-bold"><i class="fas fa-user-edit me-2 text-primary"></i>Edit Employee</h2>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" pattern="[0-9]{10}" maxlength="10" title="Phone number must be exactly 10 digits">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Designation</label>
                                    <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($user['designation'] ?? ''); ?>" placeholder="e.g., Senior Developer, Manager">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Joining Date</label>
                                    <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($user['joining_date'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="employee" <?php echo (isset($user['role']) && trim($user['role']) === 'employee') ? 'selected' : ''; ?>>Employee</option>
                                    <option value="admin" <?php echo (isset($user['role']) && trim($user['role']) === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Profile Image</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(event)">
                                <small class="form-text text-muted">Leave empty to keep current image</small>
                            </div>

                            <div class="mb-4 text-center">
                                <?php
                                $profile_img = !empty($user['profile_image']) ? '../assets/img/' . htmlspecialchars($user['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random';
                                ?>
                                <img id="image_preview" src="<?php echo $profile_img; ?>" alt="Profile Preview" style="max-width:150px; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 0 auto;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random'">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold">Update Employee</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill py-2 fw-bold">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('image_preview');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
