<?php
session_start();
require_once '../config/db.php';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $password = $_POST['password'];
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $role = 'employee';

    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Name, Email, and Password are required.';
        header('Location: register.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Password and Confirm Password must match.';
        header('Location: register.php');
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = 'Email already registered.';
        header('Location: register.php');
        exit;
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare('INSERT INTO users (name, email, phone, address, designation, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssss', $name, $email, $phone, $address, $designation, $hashed_password, $role);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Registration successful. Please login.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = 'Registration failed.';
        header('Location: register.php');
        exit;
    }
}
?>
<?php
$page_title = 'Register - EMS';
include '../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-shell">
        <div class="card auth-card border-0">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="auth-brand">
                        <div class="logo-circle">
                            <img src="../logo.png" alt="Insight CRM Logo">
                        </div>
                        <h1>Create account</h1>
                        <p class="mb-3">Register as an employee to access tasks, attendance, leaves, and announcements.</p>
                        <div class="d-flex flex-column gap-2 mt-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border"><i class="fas fa-id-badge me-1"></i>Profile</span>
                                <span class="badge bg-light text-dark border"><i class="fas fa-list-check me-1"></i>Tasks</span>
                                <span class="badge bg-light text-dark border"><i class="fas fa-calendar-check me-1"></i>Attendance</span>
                            </div>
                            <small class="text-white-50">Note: admin accounts are created by administrators only.</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="auth-form">
                        <div class="mb-4">
                            <h3 class="fw-bold text-dark mb-1">Register</h3>
                            <div class="auth-helper">Fill your details to create an employee account.</div>
                        </div>

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error!</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-600">Full name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                        <input type="text" name="name" class="form-control" placeholder="Your name" required autocomplete="name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-600">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control" placeholder="name@company.com" required autocomplete="email">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-600">Phone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                                        <input type="text" name="phone" class="form-control" placeholder="Optional" autocomplete="tel">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-600">Designation</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-briefcase text-muted"></i></span>
                                        <input type="text" name="designation" class="form-control" placeholder="e.g., Developer" autocomplete="organization-title">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-600">Address</label>
                                    <textarea name="address" class="form-control" placeholder="Optional" rows="2" style="border-left:1px solid var(--border-color) !important;"></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-600">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                        <input id="reg_password" type="password" name="password" class="form-control" placeholder="Create password" required autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleRegPassword" aria-label="Toggle password visibility">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-600">Confirm password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                        <input id="reg_confirm_password" type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required autocomplete="new-password">
                                    </div>
                                    <div id="pwMatchHint" class="form-text"></div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 mt-4">
                                <i class="fas fa-user-check me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-top d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                            <div class="auth-helper">
                                Already have an account?
                                <a class="auth-link" href="login.php">Login</a>
                            </div>
                            <div class="auth-helper text-muted">Employee accounts only</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const btn = document.getElementById('toggleRegPassword');
        const input = document.getElementById('reg_password');
        if (btn && input) {
            btn.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        }

        const pw = document.getElementById('reg_password');
        const cpw = document.getElementById('reg_confirm_password');
        const hint = document.getElementById('pwMatchHint');
        const form = document.getElementById('registerForm');

        function validateMatch() {
            if (!pw || !cpw || !hint) return true;
            if (cpw.value.length === 0) {
                hint.textContent = '';
                hint.className = 'form-text';
                return true;
            }
            const ok = pw.value === cpw.value;
            hint.textContent = ok ? 'Passwords match.' : 'Passwords do not match.';
            hint.className = ok ? 'form-text text-success' : 'form-text text-danger';
            return ok;
        }

        if (pw && cpw) {
            pw.addEventListener('input', validateMatch);
            cpw.addEventListener('input', validateMatch);
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                if (!validateMatch()) {
                    e.preventDefault();
                    cpw.focus();
                }
            });
        }
    })();
</script>

<?php include '../includes/footer.php'; ?>
