<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle Add to Cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!isLoggedIn() || !isBuyer()) {
        $_SESSION['error_message'] = "Please login as a buyer to add items to cart.";
        redirect(BASE_URL . '/auth/login.php');
    }

    $userId = getCurrentUserId();
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Check product and stock
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        if ($product['stock_quantity'] > 0 && $quantity > 0 && $quantity <= $product['stock_quantity']) {
            // Check if already in cart
            $stmtCart = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmtCart->bind_param("ii", $userId, $productId);
            $stmtCart->execute();
            $cartResult = $stmtCart->get_result();

            if ($cartResult->num_rows > 0) {
                $cartItem = $cartResult->fetch_assoc();
                $newQty = $cartItem['quantity'] + $quantity;
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
                $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
                $insertStmt->bind_param("iii", $userId, $productId, $quantity);
                $insertStmt->execute();
                $_SESSION['success_message'] = "Product added to cart.";
                logAudit($conn, $userId, 'Added product to cart', "Added $quantity of product ID: $productId");
            }
        } else {
            $_SESSION['error_message'] = "Invalid quantity or product is out of stock.";
        }
    } else {
        $_SESSION['error_message'] = "Product not found.";
    }
    redirect(BASE_URL . '/store/product.php?id=' . $productId);
}

// Fetch product details
$stmtProd = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 'active'");
$stmtProd->bind_param("i", $productId);
$stmtProd->execute();
$result = $stmtProd->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Product not found.";
    redirect(BASE_URL . '/store/index.php');
}

$product = $result->fetch_assoc();
$pageTitle = $product['name'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4 fade-in">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-gold text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/store/index.php" class="text-gold text-decoration-none">Store</a></li>
            <li class="breadcrumb-item active text-light" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <?php displayMessages(); ?>

    <div class="row g-5 slide-up">
        <div class="col-md-6">
            <div class="border border-secondary rounded overflow-hidden shadow-lg h-100 bg-dark d-flex align-items-center justify-content-center p-3">
                <img src="<?= PRODUCT_IMG_PATH . htmlspecialchars($product['image']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']) ?>" style="max-height: 500px; object-fit: contain;">
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-dark border-0 text-light h-100">
                <div class="card-body p-0">
                    <span class="badge bg-gold text-dark mb-3 px-3 py-2 rounded-pill"><?= htmlspecialchars($product['category_name']) ?></span>
                    <h2 class="text-gold fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h2>
                    <p class="product-price display-6 fw-bold mb-4"><?= formatPrice($product['price']) ?></p>
                    
                    <div class="mb-4">
                        <h5 class="text-uppercase text-muted fs-6 tracking-wide mb-3">Description</h5>
                        <p class="lh-lg"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>

                    <div class="divider-gold my-4"></div>

                    <div class="mb-4">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <p class="text-success mb-2"><i class="bi bi-check-circle-fill me-2"></i><?= $product['stock_quantity'] ?> units in stock</p>
                        <?php else: ?>
                            <p class="text-danger mb-2"><i class="bi bi-x-circle-fill me-2"></i>Out of Stock</p>
                        <?php endif; ?>
                    </div>

                    <?php if (isLoggedIn() && isBuyer()): ?>
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <form method="POST" class="d-flex flex-column flex-sm-row gap-3">
                                <input type="hidden" name="action" value="add_to_cart">
                                <div class="input-group" style="max-width: 150px;">
                                    <span class="input-group-text bg-secondary border-secondary text-light">Qty</span>
                                    <input type="number" name="quantity" class="form-control bg-dark text-light border-secondary text-center" value="1" min="1" max="<?= $product['stock_quantity'] ?>">
                                </div>
                                <button type="submit" class="btn btn-gold flex-grow-1 py-2 text-uppercase fw-bold"><i class="bi bi-cart-plus me-2"></i>Add to Cart</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary w-100 py-3 text-uppercase fw-bold">Login to Purchase</a>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top border-secondary">
                        <a href="<?= BASE_URL ?>/store/index.php" class="text-gold text-decoration-none"><i class="bi bi-arrow-left me-2"></i>Back to Store</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
