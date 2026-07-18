<?php
// ============================================================
// Shared Header - Cup of Jude's Machinery
// ============================================================
// $pageTitle must be set before including this file

if (!isset($pageTitle)) {
    $pageTitle = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo SITE_TAGLINE; ?>">
    <title><?php echo htmlspecialchars($pageTitle) . ' | ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/design.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="main-navbar">
    <div class="container">
        <!-- Logo & Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/index.php">
            <img src="<?php echo LOGO_PATH; ?>" alt="<?php echo SITE_NAME; ?> Logo" class="brand-logo me-2">
            <span class="brand-text"><?php echo GROUP_NAME; ?><span class="brand-accent"> Machinery</span></span>
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Nav Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/store/index.php">
                        <i class="bi bi-shop me-1"></i>Store
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/pages/about.php">
                        <i class="bi bi-info-circle me-1"></i>About
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <!-- Admin Links -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-gear me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php"><i class="bi bi-people me-2"></i>Manage Users</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/products.php"><i class="bi bi-box-seam me-2"></i>Manage Products</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/inventory_report.php"><i class="bi bi-clipboard-data me-2"></i>Inventory Report</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/audit_log.php"><i class="bi bi-journal-text me-2"></i>Audit Log</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (isBuyer()): ?>
                        <!-- Cart Link -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>/store/cart.php">
                                <i class="bi bi-cart3 me-1"></i>Cart
                                <?php
                                $cartCount = getCartCount($conn);
                                if ($cartCount > 0):
                                ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark cart-badge">
                                        <?php echo $cartCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-dropdown" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars(getCurrentUserName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted small"><?php echo htmlspecialchars(getCurrentUserEmail()); ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-gold btn-sm ms-2" href="<?php echo BASE_URL; ?>/auth/register.php">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Wrapper -->
<main class="main-content">
    <div class="container py-4">
        <?php displayMessages(); ?>
