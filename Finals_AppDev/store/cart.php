<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update' && isset($_POST['cart_id'], $_POST['quantity'])) {
        $cartId = (int)$_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];

        // Get product stock
        $stmtStock = $conn->prepare("SELECT p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
        $stmtStock->bind_param("ii", $cartId, $userId);
        $stmtStock->execute();
        $stockResult = $stmtStock->get_result();

        if ($stockResult->num_rows > 0) {
            $stock = $stockResult->fetch_assoc()['stock_quantity'];
            if ($quantity >= 1 && $quantity <= $stock) {
                $stmtUpdate = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmtUpdate->bind_param("iii", $quantity, $cartId, $userId);
                $stmtUpdate->execute();
                $_SESSION['success_message'] = "Cart updated.";
            } else {
                $_SESSION['error_message'] = "Invalid quantity.";
            }
        }
    } elseif ($action === 'remove' && isset($_POST['cart_id'])) {
        $cartId = (int)$_POST['cart_id'];
        $stmtDel = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmtDel->bind_param("ii", $cartId, $userId);
        $stmtDel->execute();
        $_SESSION['success_message'] = "Item removed from cart.";
    } elseif ($action === 'clear') {
        $stmtClear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmtClear->bind_param("i", $userId);
        $stmtClear->execute();
        $_SESSION['success_message'] = "Cart cleared.";
    }
    redirect(BASE_URL . '/store/cart.php');
}

// Fetch cart items
$stmtCart = $conn->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image, p.stock_quantity, cat.name AS category_name 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    JOIN categories cat ON p.category_id = cat.id 
    WHERE c.user_id = ? 
    ORDER BY c.added_at DESC
");
$stmtCart->bind_param("i", $userId);
$stmtCart->execute();
$cartItems = $stmtCart->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="page-header mb-5">
        <h1 class="section-title text-gold fade-in">Shopping Cart</h1>
    </div>

    <?php displayMessages(); ?>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state text-center py-5 fade-in">
            <i class="bi bi-cart-x display-1 text-gold mb-3"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
            <a href="<?= BASE_URL ?>/store/index.php" class="btn btn-gold">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4 slide-up">
            <div class="col-lg-8">
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-0">
                        <?php 
                        $grandTotal = 0;
                        foreach ($cartItems as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $grandTotal += $subtotal;
                        ?>
                            <div class="cart-item d-flex flex-column flex-md-row align-items-center p-4 border-bottom border-secondary">
                                <img src="<?= PRODUCT_IMG_PATH . htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img rounded me-md-4 mb-3 mb-md-0" style="width: 100px; height: 100px; object-fit: cover;">
                                
                                <div class="flex-grow-1 text-center text-md-start mb-3 mb-md-0">
                                    <h5 class="text-gold mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($item['category_name']) ?></span>
                                    <p class="text-light fw-bold mb-0"><?= formatPrice($item['price']) ?></p>
                                </div>

                                <div class="d-flex flex-column align-items-center ms-md-4 mb-3 mb-md-0">
                                    <form method="POST" class="d-flex align-items-center quantity-input">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary qty-decrease" onclick="this.parentNode.querySelector('input[type=number]').stepDown()" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>-</button>
                                        <input type="number" name="quantity" class="form-control form-control-sm bg-dark text-light border-secondary text-center qty-input mx-2" style="width: 60px;" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary qty-increase" onclick="this.parentNode.querySelector('input[type=number]').stepUp()" <?= $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : '' ?>>+</button>
                                    </form>
                                    <?php if($item['quantity'] > $item['stock_quantity']): ?>
                                        <small class="text-danger mt-1">Only <?= $item['stock_quantity'] ?> left!</small>
                                    <?php endif; ?>
                                </div>

                                <div class="ms-md-4 text-center text-md-end" style="min-width: 120px;">
                                    <p class="fw-bold text-light mb-2"><?= formatPrice($subtotal) ?></p>
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none"><i class="bi bi-x-circle me-1"></i>Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-dark border-secondary cart-summary sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <h5 class="text-gold border-bottom border-secondary pb-3 mb-4">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span>Subtotal (<?= count($cartItems) ?> items)</span>
                            <span><?= formatPrice($grandTotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span>Shipping</span>
                            <span>Calculated at checkout</span>
                        </div>
                        
                        <hr class="border-secondary my-4">
                        
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="text-light mb-0">Total</h5>
                            <h5 class="total-price text-gold mb-0"><?= formatPrice($grandTotal) ?></h5>
                        </div>

                        <a href="<?= BASE_URL ?>/store/checkout.php" class="btn btn-gold w-100 py-3 fw-bold mb-3">Proceed to Checkout</a>
                        
                        <form method="POST" class="m-0 mb-3" onsubmit="return confirm('Are you sure you want to clear your cart?');">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-gold-outline w-100 py-2">Clear Cart</button>
                        </form>

                        <div class="text-center">
                            <a href="<?= BASE_URL ?>/store/index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
