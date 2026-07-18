<?php
// ============================================================
// Site Constants - Cup of Jude's Machinery
// ============================================================

// Site Information
define('SITE_NAME', "Cup of Jude's Machinery");
define('GROUP_NAME', "Cup of Jude's");
define('SITE_TAGLINE', 'A company that sells Heavy Machinery legally and equally.');

// Base URL - Change this when deploying to hosting
// For local development (XAMPP), use something like: http://localhost/Finals_AppDev
// For production hosting, use your actual domain
define('BASE_URL', 'http://localhost/Finals_AppDev');

// File paths
define('LOGO_PATH', BASE_URL . '/assets/images/logo.png');
define('PRODUCT_IMG_PATH', BASE_URL . '/assets/images/products/');
define('UPLOAD_DIR', __DIR__ . '/../assets/images/products/');

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disclaimer text (required by project notes)
define('DISCLAIMER_TEXT', 'DISCLAIMER: This website is for educational purposes only and is a requirement for a final project. No actual products are being sold, and no real transactions take place on this website.');

// Group members - Update with your actual group members
define('GROUP_MEMBERS', serialize([
    'Gamis, Jude',
    'Catalonia, Marco'
]));
