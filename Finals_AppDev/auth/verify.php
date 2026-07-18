<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Verify Email';
require_once __DIR__ . '/../includes/header.php';

$token = sanitize($_GET['token'] ?? '');
$verificationSuccess = false;

if (!empty($token)) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET is_verified = 1, verification_token = NULL, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($updateStmt, "i", $user['id']);
        
        if (mysqli_stmt_execute($updateStmt)) {
            $verificationSuccess = true;
            logAudit($conn, $user['id'], 'Email Verified', 'User successfully verified their email address.');
            $_SESSION['success'] = 'Your email has been successfully verified! You can now log in.';
        } else {
            $_SESSION['error'] = 'An error occurred during verification. Please try again.';
        }
        mysqli_stmt_close($updateStmt);
    } else {
        $_SESSION['error'] = 'Invalid or expired verification token.';
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = 'No verification token provided.';
}

?>

<div class="row justify-content-center slide-up my-5">
    <div class="col-md-6 text-center">
        <div class="card auth-card bg-card text-light border-secondary shadow-lg">
            <div class="card-body p-5">
                <?php if ($verificationSuccess): ?>
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    <h3 class="card-title text-gold mt-4 mb-3">Verification Successful!</h3>
                    <p class="text-muted mb-4">Your account is now active and ready to use.</p>
                    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-gold px-5 py-2 fw-bold">Go to Login</a>
                <?php else: ?>
                    <i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
                    <h3 class="card-title text-gold mt-4 mb-3">Verification Failed</h3>
                    <p class="text-muted mb-4">The verification link is invalid or has expired.</p>
                    <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary px-5 py-2">Return Home</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
