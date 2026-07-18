<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Our Store';

// Handle Add to Cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!isLoggedIn() || !isBuyer()) {
        $_SESSION['error_message'] = "Please login as a buyer to add items to cart.";
        redirect(BASE_URL . '/auth/login.php');
    }

    $productId = (int)$_POST['product_id'];
    $userId = getCurrentUserId();

    // Check product and stock
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        if ($product['stock_quantity'] > 0) {
            // Check if already in cart
            $stmtCart = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmtCart->bind_param("ii", $userId, $productId);
            $stmtCart->execute();
            $cartResult = $stmtCart->get_result();

            if ($cartResult->num_rows > 0) {
                $cartItem = $cartResult->fetch_assoc();
                $newQty = $cartItem['quantity'] + 1;
                if ($newQty <= $product['stock_quantity']) {
                    $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $updateStmt->bind_param("ii", $newQty, $cartItem['id']);
                    $updateStmt->execute();
                    $_SESSION['success_message'] = "Product quantity updated in cart.";
                    logAudit($conn, $userId, 'Added product to cart', "Updated quantity for product ID: $productId to $newQty");
                } else {
                    $_SESSION['error_message'] = "Cannot exceed available stock.";
                }
            } else {
                $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, 1, NOW())");
                $insertStmt->bind_param("ii", $userId, $productId);
                $insertStmt->execute();
                $_SESSION['success_message'] = "Product added to cart.";
                logAudit($conn, $userId, 'Added product to cart', "Added product ID: $productId");
            }
        } else {
            $_SESSION['error_message'] = "Product is out of stock.";
        }
    } else {
        $_SESSION['error_message'] = "Product not found.";
    }
    redirect(BASE_URL . '/store/index.php' . (isset($_GET['category']) ? '?category=' . urlencode($_GET['category']) : ''));
}

// Fetch categories
$stmtCat = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
$stmtCat->execute();
$categories = $stmtCat->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch products
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
if ($selectedCategory > 0) {
    $stmtProd = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.status = 'active' ORDER BY p.created_at DESC");
    $stmtProd->bind_param("i", $selectedCategory);
} else {
    $stmtProd = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC");
}
$stmtProd->execute();
$products = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="page-header mb-5 text-center">
        <h1 class="section-title text-gold fade-in">Our Store</h1>
    </div>

    <?php displayMessages(); ?>

    <!-- Category Filter Pills -->
    <div class="mb-4 text-center fade-in">
        <a href="<?= BASE_URL ?>/store/index.php" class="btn <?= $selectedCategory === 0 ? 'btn-gold' : 'btn-gold-outline' ?> mb-2">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/store/index.php?category=<?= $cat['id'] ?>" class="btn <?= $selectedCategory === (int)$cat['id'] ? 'btn-gold' : 'btn-gold-outline' ?> mb-2">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Products -->
    <div class="row g-4 slide-up">
        <?php if (empty($products)): ?>
            <div class="col-12 empty-state text-center py-5">
                <i class="bi bi-box-seam display-1 text-gold mb-3"></i>
                <h3>No products found</h3>
                <p class="text-muted">Check back later or browse other categories.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 product-card border-0 bg-dark text-light shadow">
                        <div class="img-overflow-hidden position-relative">
                            <img src="<?= PRODUCT_IMG_PATH . htmlspecialchars($product['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 250px; object-fit: cover;">
                            <span class="badge badge-category position-absolute top-0 start-0 m-3 bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="badge badge-stock badge-stock-in position-absolute top-0 end-0 m-3 bg-success">In Stock</span>
                            <?php else: ?>
                                <span class="badge badge-stock badge-stock-out position-absolute top-0 end-0 m-3 bg-danger">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="product-name text-gold mb-2"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars(mb_strimwidth($product['description'], 0, 80, "...")) ?></p>
                            <p class="product-price h4 mb-0"><?= formatPrice($product['price']) ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-top border-secondary pt-3 pb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?= BASE_URL ?>/store/product.php?id=<?= $product['id'] ?>" class="btn btn-gold-outline flex-grow-1 me-2">View Details</a>
                                <?php if (isLoggedIn() && isBuyer()): ?>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <form method="POST" class="m-0 flex-grow-1">
                                            <input type="hidden" name="action" value="add_to_cart">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-gold w-100"><i class="bi bi-cart-plus"></i> Add</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary flex-grow-1 disabled">Out of Stock</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary flex-grow-1">Login to Buy</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
