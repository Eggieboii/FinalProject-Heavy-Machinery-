<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$pageTitle = 'Register';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = sanitize($_POST['address'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');

    $errors = [];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($address) || empty($contact_number)) {
        $errors[] = 'All fields are required.';
    }
    
    if (!isValidEmail($email)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (!isValidPassword($password)) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!isValidPhone($contact_number)) {
        $errors[] = 'Invalid contact number format.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Email is already registered.';
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = generateToken();
        $role = 'buyer';
        $is_verified = 0;
        $now = date('Y-m-d H:i:s');

        $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, address, contact_number, role, is_verified, verification_token, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssisss", $full_name, $email, $hashed_password, $address, $contact_number, $role, $is_verified, $verification_token, $now, $now);
        
        if (mysqli_stmt_execute($stmt)) {
            $userId = mysqli_insert_id($conn);
            logAudit($conn, $userId, 'New User Registration', 'User registered successfully.');
            
            sendVerificationEmail($email, $full_name, $verification_token);
            
            $localNotice = '';
            if (strpos(BASE_URL, 'localhost') !== false) {
                $localNotice = '<br><br><span class="text-warning fw-bold">Local Dev Notice:</span> Since mail servers are disabled locally, you can click <a href="' . BASE_URL . '/auth/verify.php?token=' . $verification_token . '" class="text-gold fw-bold text-decoration-underline">here to verify your account</a>.';
            }
            $_SESSION['success'] = 'Registration successful! Please check your email to verify your account.' . $localNotice;
            redirect(BASE_URL . '/auth/login.php');
        } else {
            $_SESSION['error'] = 'Registration failed. Please try again later.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center slide-up my-5">
    <div class="col-md-8">
        <div class="card auth-card bg-card text-light border-secondary shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h3 class="card-title text-gold section-title">Create an Account</h3>
                    <p class="text-muted">Join <?php echo htmlspecialchars(SITE_NAME); ?> today</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control bg-dark text-light border-secondary" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control bg-dark text-light border-secondary" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control bg-dark text-light border-secondary" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control bg-dark text-light border-secondary" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($contact_number ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="address" class="form-label">Full Address</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gold w-100 py-2 fw-bold">Register</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0 text-muted">Already have an account? <a href="login.php" class="text-gold text-decoration-none">Sign In here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
