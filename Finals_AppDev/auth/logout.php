<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    logAudit($conn, getCurrentUserId(), 'User Logout', 'User logged out successfully.');
}

session_unset();
session_destroy();
session_start();

$_SESSION['success'] = 'You have been successfully logged out.';
redirect(BASE_URL . '/index.php');
