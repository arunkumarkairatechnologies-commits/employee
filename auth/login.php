<?php
session_start();
require_once '../config/db.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email and Password are required.';
        header('Location: login.php');
        exit;
    }

    // Fetch user
    $stmt = $conn->prepare('SELECT id, name, email, password, role, profile_image FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Normalize role (trim and lowercase) and set session
            $normalized_role = strtolower(trim($user['role']));
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $normalized_role;
            $_SESSION['profile_image'] = $user['profile_image'];
            // Redirect by normalized role
            if ($normalized_role === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../user/dashboard.php');
            }
            exit;
        } else {
            $_SESSION['error'] = 'Invalid credentials.';
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Invalid credentials.';
        header('Location: login.php');
        exit;
    }
}
?>
<?php
$page_title = 'Login - EMS';
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
                        <p class="mb-3">Sign in to manage tasks, attendance, leaves, and announcements with a clean dashboard.</p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge bg-light text-dark border"><i class="fas fa-shield-halved me-1"></i>Secure</span>
                            <span class="badge bg-light text-dark border"><i class="fas fa-bolt me-1"></i>Fast</span>
                            <span class="badge bg-light text-dark border"><i class="fas fa-mobile-screen me-1"></i>Responsive</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="auth-form">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h3 class="fw-bold text-dark mb-1">Welcome back</h3>
                                <div class="auth-helper">Use your email and password to continue.</div>
                            </div>
                            <a href="../" class="btn btn-sm btn-outline-secondary rounded-pill d-none d-lg-inline-flex">
                                <i class="fas fa-house me-2"></i>Home
                            </a>
                        </div>

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error!</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Success!</strong> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-600">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" name="email" class="form-control" placeholder="name@company.com" required autocomplete="username">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                    <input id="login_password" type="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword" aria-label="Toggle password visibility">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Tip: click the eye icon to show/hide password.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 rounded-3 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-top d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                            <div class="auth-helper">
                                Don’t have an account?
                                <a class="auth-link" href="register.php">Create one</a>
                            </div>
                            <div class="auth-helper text-muted">Employee Management System</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const btn = document.getElementById('toggleLoginPassword');
        const input = document.getElementById('login_password');
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    })();
</script>

<?php include '../includes/footer.php'; ?>
