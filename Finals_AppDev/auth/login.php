<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    redirect($role === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/store/index.php');
}

$pageTitle = 'Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please enter both email and password.';
    } elseif (!isValidEmail($email)) {
        $_SESSION['error'] = 'Invalid email format.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, password, role, is_verified FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                if ($user['is_verified'] == 1) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    logAudit($conn, $user['id'], 'User Login', 'User successfully logged in.');
                    $_SESSION['success'] = 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!';
                    
                    redirect($user['role'] === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/store/index.php');
                } else {
                    $_SESSION['error'] = 'Please verify your email address before logging in.';
                }
            } else {
                $_SESSION['error'] = 'Invalid email or password.';
            }
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
        }
        mysqli_stmt_close($stmt);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center slide-up my-5">
    <div class="col-md-5">
        <div class="card auth-card bg-card text-light border-secondary shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="<?php echo htmlspecialchars(LOGO_PATH); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" class="auth-logo mb-3" style="max-height: 80px;">
                    <h3 class="card-title text-gold section-title">Welcome Back</h3>
                    <p class="text-muted">Sign in to your account</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control bg-dark text-light border-secondary" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control bg-dark text-light border-secondary" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gold w-100 py-2 fw-bold">Sign In</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0 text-muted">Don't have an account? <a href="register.php" class="text-gold text-decoration-none">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
