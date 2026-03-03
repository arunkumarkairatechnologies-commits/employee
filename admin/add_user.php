<?php
// add_user.php - Admin can add new employees
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $designation = trim($_POST['designation']);
    $role = strtolower(trim($_POST['role']));
    $password = $_POST['password'];
    $profile_image = '';

    // Validate required fields (require role to be entered manually)
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Name, Email, Password, and Role are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } else {
        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $target_dir = '../assets/img/';
            $target_file = $target_dir . basename($_FILES['profile_image']['name']);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES['profile_image']['tmp_name']);
            if ($check && in_array($imageFileType, ['jpg','jpeg','png','gif'])) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = basename($_FILES['profile_image']['name']);
                } else {
                    $error = 'Image upload failed.';
                }
            } else {
                $error = 'Invalid image file.';
            }
        }
        // Check if email exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already exists.';
        }
        $stmt->close();
        // Insert employee
        if (!isset($error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, phone, address, designation, password, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssssss', $name, $email, $phone, $address, $designation, $hashed_password, $role, $profile_image);
            if ($stmt->execute()) {
                $success = 'Employee added successfully.';
            } else {
                $error = 'Failed to add employee.';
            }
            $stmt->close();
        }
    }
}
?>
<div class="page-container">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="text-center mb-4 mt-4">
            <h2 class="fw-bold"><i class="fas fa-user-plus me-2 text-primary"></i>Add Employee</h2>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"> <?php echo $error; ?> </div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"> <?php echo $success; ?> </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Phone</label>
                                    <input type="text" name="phone" class="form-control" pattern="[0-9]{10}" maxlength="10" title="Phone number must be exactly 10 digits">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Designation</label>
                                    <input type="text" name="designation" class="form-control" placeholder="e.g., Senior Developer, Manager">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option value="">-- Select Role --</option>
                                        <option value="employee">Employee</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Profile Image</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(event)">
                            </div>
                            
                            <div class="mb-4 text-center">
                                <img id="image_preview" src="#" alt="Image Preview" style="display:none; max-width:150px; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 0 auto;">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold">Add Employee</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill py-2 fw-bold">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

<script>
function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('image_preview');
        output.src = reader.result;
        output.style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
