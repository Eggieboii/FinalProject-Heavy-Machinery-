<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';

// Fetch stats
$stmt = $conn->prepare("SELECT COUNT(*) FROM products");
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders");
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role='buyer'");
$stmt->execute();
$totalBuyers = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('paid','completed','shipped')");
$stmt->execute();
$revenue = $stmt->get_result()->fetch_row()[0];

// Fetch recent orders
$stmt = $conn->prepare("SELECT o.id, u.full_name, o.total_amount, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 fade-in">
    <div class="dashboard-welcome mb-4 p-4 rounded bg-dark text-light border border-secondary shadow-sm">
        <h2>Welcome back, <?= htmlspecialchars(getCurrentUserName()) ?>!</h2>
        <p class="text-muted mb-0">Here's what's happening in your store today.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3 slide-up">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-4">
                <i class="bi bi-box-seam stat-icon text-gold fs-1 mb-2"></i>
                <div class="stat-number fs-3 fw-bold text-gold"><?= htmlspecialchars($totalProducts) ?></div>
                <div class="stat-label text-muted">Total Products</div>
            </div>
        </div>
        <div class="col-md-3 slide-up" style="animation-delay: 0.1s;">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-4">
                <i class="bi bi-receipt stat-icon text-gold fs-1 mb-2"></i>
                <div class="stat-number fs-3 fw-bold text-gold"><?= htmlspecialchars($totalOrders) ?></div>
                <div class="stat-label text-muted">Total Orders</div>
            </div>
        </div>
        <div class="col-md-3 slide-up" style="animation-delay: 0.2s;">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-4">
                <i class="bi bi-people stat-icon text-gold fs-1 mb-2"></i>
                <div class="stat-number fs-3 fw-bold text-gold"><?= htmlspecialchars($totalBuyers) ?></div>
                <div class="stat-label text-muted">Total Buyers</div>
            </div>
        </div>
        <div class="col-md-3 slide-up" style="animation-delay: 0.3s;">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-4">
                <i class="bi bi-currency-dollar stat-icon text-gold fs-1 mb-2"></i>
                <div class="stat-number fs-3 fw-bold text-gold"><?= htmlspecialchars(formatPrice($revenue)) ?></div>
                <div class="stat-label text-muted">Revenue</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8 slide-up" style="animation-delay: 0.4s;">
            <div class="card bg-dark text-light border-secondary h-100">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-gold"><i class="bi bi-clock-history me-2"></i>Recent Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No recent orders found.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['id']) ?></td>
                                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                                        <td><?= htmlspecialchars(formatPrice($order['total_amount'])) ?></td>
                                        <td><span class="badge badge-gold"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></td>
                                        <td><?= htmlspecialchars(timeAgo($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 slide-up" style="animation-delay: 0.5s;">
            <div class="card bg-dark text-light border-secondary h-100">
                <div class="card-header border-secondary">
                    <h5 class="mb-0 text-gold"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="users.php" class="btn btn-gold-outline text-start"><i class="bi bi-people-fill me-2"></i>Manage Users</a>
                        <a href="products.php" class="btn btn-gold-outline text-start"><i class="bi bi-box-seam-fill me-2"></i>Manage Products</a>
                        <a href="inventory_report.php" class="btn btn-gold-outline text-start"><i class="bi bi-clipboard-data-fill me-2"></i>Inventory Report</a>
                        <a href="audit_log.php" class="btn btn-gold-outline text-start"><i class="bi bi-journal-text me-2"></i>Audit Log</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
