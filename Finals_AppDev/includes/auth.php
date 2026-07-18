<?php
// ============================================================
// Authentication Helpers - Cup of Jude's Machinery
// ============================================================

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if the current user is a buyer
 */
function isBuyer() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'buyer';
}

/**
 * Get the current logged-in user's ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current logged-in user's name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get the current logged-in user's role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get the current logged-in user's email
 */
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error_msg'] = 'Please log in to access this page.';
        header('Location:' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Require admin role - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error_msg'] = 'Access denied. Admin privileges required.';
        header('Location:' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Require buyer role - redirect if not buyer
 */
function requireBuyer() {
    requireLogin();
    if (!isBuyer()) {
        $_SESSION['error_msg'] = 'Access denied.';
        header('Location:' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Get the cart item count for the current user
 */
function getCartCount($conn) {
    if (!isLoggedIn()) {
        return 0;
    }
    $userId = getCurrentUserId();
    $stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['total'] ?? 0;
}
