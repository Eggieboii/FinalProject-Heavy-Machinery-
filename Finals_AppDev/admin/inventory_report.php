<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Inventory Report';
logAudit($conn, getCurrentUserId(), 'Viewed Inventory Report');

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) FROM products");
$stmt->execute();
$totalProducts = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT SUM(stock_quantity) FROM products");
$stmt->execute();
$totalStockItems = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity BETWEEN 1 AND 10");
$stmt->execute();
$lowStock = $stmt->get_result()->fetch_row()[0];

$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity = 0");
$stmt->execute();
$outOfStock = $stmt->get_result()->fetch_row()[0];

// Table data
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.stock_quantity ASC");
$stmt->execute();
$inventory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 fade-in">
    <h2 class="section-title text-gold mb-4">Inventory Report</h2>

    <div class="row g-4 mb-4">
        <div class="col-md-3 slide-up">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-3">
                <div class="stat-number fs-4 fw-bold text-gold"><?= htmlspecialchars($totalProducts) ?></div>
                <div class="stat-label text-muted small">Total Products</div>
            </div>
        </div>
        <div class="col-md-3 slide-up" style="animation-delay: 0.1s;">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-3">
                <div class="stat-number fs-4 fw-bold text-gold"><?= htmlspecialchars($totalStockItems ?? 0) ?></div>
                <div class="stat-label text-muted small">Total Stock Items</div>
            </div>
        </div>
        <div class="col-md-3 slide-up" style="animation-delay: 0.2s;">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-3">
                <div class="stat-number fs-4 fw-bold text-warning"><?= htmlspecialchars($lowStock) ?></div>
                <div class="stat-label text-muted small">Low Stock</div>
            </div>
        </div>
        <div class="col-md-3 slide-up" style="animation-delay: 0.3s;">
            <div class="stat-card card bg-dark text-light border-secondary text-center p-3">
                <div class="stat-number fs-4 fw-bold text-danger"><?= htmlspecialchars($outOfStock) ?></div>
                <div class="stat-label text-muted small">Out of Stock</div>
            </div>
        </div>
    </div>

    <div class="card bg-dark text-light border-secondary slide-up" style="animation-delay: 0.4s;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock Qty</th>
                            <th>Status</th>
                            <th>Stock Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['category_name']) ?></td>
                            <td><?= htmlspecialchars(formatPrice($item['price'])) ?></td>
                            <td><?= htmlspecialchars($item['stock_quantity']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($item['status'])) ?></td>
                            <td>
                                <?php
                                $stock = (int)$item['stock_quantity'];
                                if($stock > 10) {
                                    echo '<span class="badge badge-stock-in">In Stock</span>';
                                } elseif($stock > 0) {
                                    echo '<span class="badge badge-stock-low">Low Stock</span>';
                                } else {
                                    echo '<span class="badge badge-stock-out">Out of Stock</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inventory)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No inventory data found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
