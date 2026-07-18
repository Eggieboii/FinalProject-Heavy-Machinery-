<?php
// ============================================================
// Utility Functions - Cup of Jude's Machinery
// ============================================================

/**
 * Sanitize user input
 */
function sanitize($str) {
    $str = trim($str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    return $str;
}

/**
 * Log an action to the audit log
 */
function logAudit($conn, $userId, $action, $details = '') {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "isss", $userId, $action, $details, $ipAddress);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Format a price with dollar sign and 2 decimal places
 */
function formatPrice($price) {
    return '$' . number_format((float)$price, 2);
}

/**
 * Generate a random token for email verification
 */
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 */
function isValidPhone($phone) {
    // Allow digits, spaces, dashes, plus sign, parentheses
    return preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $phone);
}

/**
 * Validate password strength
 * Requires at least 8 characters
 */
function isValidPassword($password) {
    return strlen($password) >= 8;
}

/**
 * Send verification email
 * Uses PHP's built-in mail() function
 */
function sendVerificationEmail($email, $fullName, $token) {
    $verifyUrl = BASE_URL . '/auth/verify.php?token=' . $token;
    
    $subject = "Verify Your Email - " . SITE_NAME;
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #1a1a2e; color: #e0e0e0; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #16213e; padding: 30px; border-radius: 10px; border: 1px solid #c8a951;'>
            <h2 style='color: #c8a951; text-align: center;'>Welcome to " . SITE_NAME . "!</h2>
            <p>Hello " . htmlspecialchars($fullName) . ",</p>
            <p>Thank you for registering with us. Please click the button below to verify your email address:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . $verifyUrl . "' style='background-color: #c8a951; color: #1a1a2e; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Verify Email</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; color: #c8a951;'>" . $verifyUrl . "</p>
            <hr style='border-color: #2a3a4a;'>
            <p style='font-size: 12px; color: #a0a0a0; text-align: center;'>" . DISCLAIMER_TEXT . "</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <noreply@cupofjudes.com>\r\n";
    
    return mail($email, $subject, $message, $headers);
}

/**
 * Display flash message (success or error) from session
 */
function displayMessages() {
    // Handle all session message key patterns for success
    $successKeys = ['success_msg', 'success', 'success_message'];
    foreach ($successKeys as $key) {
        if (isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo $_SESSION[$key];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION[$key]);
        }
    }
    // Handle all session message key patterns for errors
    $errorKeys = ['error_msg', 'error', 'error_message'];
    foreach ($errorKeys as $key) {
        if (isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo $_SESSION[$key];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION[$key]);
        }
    }
}

/**
 * Get a user-friendly time ago string
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
