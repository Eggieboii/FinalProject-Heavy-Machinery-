<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();

// Fetch cart items
$stmtCart = $conn->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock_quantity 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ? 
");
$stmtCart->bind_param("i", $userId);
$stmtCart->execute();
$cartItems = $stmtCart->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    $_SESSION['error_message'] = "Your cart is empty. Please add items before checking out.";
    redirect(BASE_URL . '/store/cart.php');
}

// Fetch user details for pre-filling
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();

// Calculate initial total
$grandTotal = 0;
foreach ($cartItems as $item) {
    $grandTotal += $item['price'] * $item['quantity'];
}

// Handle POST checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    $contactNumber = sanitize($_POST['contact_number'] ?? '');

    if (empty($shippingAddress) || empty($contactNumber)) {
        $_SESSION['error_message'] = "Please provide both shipping address and contact number.";
    } else {
        // Re-verify stock and calculate final total from DB prices
        $finalTotal = 0;
        $stockValid = true;
        $stockErrorMsg = "";

        foreach ($cartItems as $item) {
            // Check stock again right before order creation
            if ($item['quantity'] > $item['stock_quantity']) {
                $stockValid = false;
                $stockErrorMsg = "Not enough stock for " . htmlspecialchars($item['name']) . ". Available: " . $item['stock_quantity'];
                break;
            }
            $finalTotal += $item['price'] * $item['quantity'];
        }

        if (!$stockValid) {
            $_SESSION['error_message'] = $stockErrorMsg;
            redirect(BASE_URL . '/store/cart.php');
        }

        // Start Transaction
        $conn->begin_transaction();
        try {
            // Create Order
            $stmtOrder = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, created_at) VALUES (?, ?, 'pending', ?, NOW())");
            $stmtOrder->bind_param("ids", $userId, $finalTotal, $shippingAddress);
            $stmtOrder->execute();
            $orderId = $conn->insert_id;

            // Create Order Items
            $stmtOrderItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmtOrderItem->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                $stmtOrderItem->execute();
            }

            // Commit Transaction
            $conn->commit();

            $_SESSION['pending_order_id'] = $orderId;
            logAudit($conn, $userId, 'Created order', "Created order #$orderId with total amount " . formatPrice($finalTotal));
            
            // Optionally update user profile with new address/contact if empty
            if (empty($user['address']) || empty($user['contact_number'])) {
                $updateUser = $conn->prepare("UPDATE users SET address = IF(address IS NULL OR address = '', ?, address), contact_number = IF(contact_number IS NULL OR contact_number = '', ?, contact_number) WHERE id = ?");
                $updateUser->bind_param("ssi", $shippingAddress, $contactNumber, $userId);
                $updateUser->execute();
            }

            redirect(BASE_URL . '/store/payment.php');
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "An error occurred while creating your order. Please try again.";
        }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="page-header mb-5">
        <h1 class="section-title text-gold fade-in">Checkout</h1>
    </div>

    <?php displayMessages(); ?>

    <form method="POST" class="row g-4 slide-up">
        <div class="col-md-7">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-transparent border-bottom border-secondary p-4">
                    <h5 class="text-gold mb-0"><i class="bi bi-truck me-2"></i>Shipping Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-light">Full Name</label>
                        <input type="text" class="form-control bg-secondary text-light border-0" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Email Address</label>
                        <input type="email" class="form-control bg-secondary text-light border-0" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="contact_number" class="form-label text-light">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control bg-dark text-light border-secondary focus-gold" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="shipping_address" class="form-label text-light">Shipping Address <span class="text-danger">*</span></label>
                        <textarea name="shipping_address" id="shipping_address" class="form-control bg-dark text-light border-secondary focus-gold" rows="4" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card bg-dark border-secondary h-100 cart-summary">
                <div class="card-header bg-transparent border-bottom border-secondary p-4">
                    <h5 class="text-gold mb-0"><i class="bi bi-receipt me-2"></i>Order Summary</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-light"><?= htmlspecialchars($item['name']) ?></span>
                                    <span class="text-muted ms-2">x<?= $item['quantity'] ?></span>
                                </div>
                                <span class="text-light fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <hr class="border-secondary my-4">
                    
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-light mb-0">Total</h4>
                        <h4 class="total-price text-gold mb-0"><?= formatPrice($grandTotal) ?></h4>
                    </div>

                    <button type="submit" class="btn btn-gold w-100 py-3 fw-bold fs-5 shadow-lg">Proceed to Payment <i class="bi bi-arrow-right ms-2"></i></button>
                    
                    <div class="text-center mt-3">
                        <a href="<?= BASE_URL ?>/store/cart.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
