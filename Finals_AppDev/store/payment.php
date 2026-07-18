<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$orderId = isset($_SESSION['pending_order_id']) ? (int)$_SESSION['pending_order_id'] : (isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0);

if ($orderId === 0) {
    $_SESSION['error_message'] = "No pending order found.";
    redirect(BASE_URL . '/store/index.php');
}

// Fetch Order to ensure it belongs to user and is pending
$stmtOrder = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
$stmtOrder->bind_param("ii", $orderId, $userId);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error_message'] = "Invalid or already processed order.";
    redirect(BASE_URL . '/store/index.php');
}

$order = $orderResult->fetch_assoc();

// Fetch Order Items
$stmtItems = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$orderItems = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

$paymentSuccess = false;
$successOrderId = 0;
$paymentMethodUsed = '';

// Handle Payment Confirmation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    
    $validMethods = ['Cash on Delivery', 'Bank Transfer', 'GCash', 'Credit/Debit Card'];
    
    if (!in_array($paymentMethod, $validMethods)) {
        $_SESSION['error_message'] = "Please select a valid payment method.";
    } else {
        $conn->begin_transaction();
        try {
            // Update Order Status
            $stmtUpdateOrder = $conn->prepare("UPDATE orders SET payment_method = ?, status = 'paid' WHERE id = ?");
            $stmtUpdateOrder->bind_param("si", $paymentMethod, $orderId);
            $stmtUpdateOrder->execute();

            // Deduct Stock
            $stmtUpdateStock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            foreach ($orderItems as $item) {
                $stmtUpdateStock->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmtUpdateStock->execute();
            }

            // Clear User Cart
            $stmtClearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmtClearCart->bind_param("i", $userId);
            $stmtClearCart->execute();

            $conn->commit();

            logAudit($conn, $userId, 'Payment confirmed', "Payment confirmed for order #$orderId via $paymentMethod");
            unset($_SESSION['pending_order_id']);
            
            $paymentSuccess = true;
            $successOrderId = $orderId;
            $paymentMethodUsed = $paymentMethod;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "An error occurred during payment processing. Please try again.";
        }
    }
}

$pageTitle = 'Payment';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <?php displayMessages(); ?>

    <?php if ($paymentSuccess): ?>
        <div class="row justify-content-center fade-in">
            <div class="col-md-8 col-lg-6">
                <div class="card bg-dark border-secondary text-center p-5 shadow-lg order-success">
                    <div class="checkmark text-success mb-4">
                        <i class="bi bi-check-circle-fill" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="text-gold fw-bold mb-3">Order Placed Successfully!</h2>
                    <p class="text-light fs-5 mb-2">Your order #<?= str_pad($successOrderId, 6, '0', STR_PAD_LEFT) ?> has been confirmed.</p>
                    <p class="text-muted mb-2">Payment Method: <span class="text-light fw-bold"><?= htmlspecialchars($paymentMethodUsed) ?></span></p>
                    <p class="text-muted mb-5">Total: <span class="text-gold fw-bold fs-4"><?= formatPrice($order['total_amount']) ?></span></p>
                    
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="<?= BASE_URL ?>/store/index.php" class="btn btn-gold py-2 px-4">Continue Shopping</a>
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-gold-outline py-2 px-4">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="page-header mb-5 text-center">
            <h1 class="section-title text-gold fade-in">Complete Your Payment</h1>
            <p class="text-muted">Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></p>
        </div>

        <form method="POST" class="row justify-content-center g-4 slide-up">
            <input type="hidden" name="action" value="confirm_payment">
            
            <div class="col-md-5">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-header bg-transparent border-bottom border-secondary p-4">
                        <h5 class="text-gold mb-0"><i class="bi bi-receipt-cutoff me-2"></i>Order Summary</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-unstyled mb-4">
                            <?php foreach ($orderItems as $item): ?>
                                <li class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary">
                                    <div>
                                        <div class="text-light fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="text-muted small">Qty: <?= $item['quantity'] ?></div>
                                    </div>
                                    <span class="text-light"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center bg-secondary rounded p-3">
                            <h5 class="text-light mb-0">Total Amount</h5>
                            <h4 class="text-gold mb-0 fw-bold"><?= formatPrice($order['total_amount']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-header bg-transparent border-bottom border-secondary p-4">
                        <h5 class="text-gold mb-0"><i class="bi bi-wallet2 me-2"></i>Select Payment Method</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="payment-methods">
                            <!-- Cash on Delivery -->
                            <label class="payment-method-label w-100 mb-3 cursor-pointer">
                                <input type="radio" name="payment_method" value="Cash on Delivery" class="d-none" required>
                                <div class="card bg-dark border-secondary payment-card transition-all">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="payment-icon bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                            <i class="bi bi-cash text-gold fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="text-light mb-1">Cash on Delivery</h6>
                                            <small class="text-muted">Pay when your order arrives</small>
                                        </div>
                                        <div class="payment-radio ms-3">
                                            <i class="bi bi-circle text-muted fs-4"></i>
                                            <i class="bi bi-check-circle-fill text-gold fs-4 d-none"></i>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- Bank Transfer -->
                            <label class="payment-method-label w-100 mb-3 cursor-pointer">
                                <input type="radio" name="payment_method" value="Bank Transfer" class="d-none">
                                <div class="card bg-dark border-secondary payment-card transition-all">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="payment-icon bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                            <i class="bi bi-bank text-gold fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="text-light mb-1">Bank Transfer</h6>
                                            <small class="text-muted">Direct transfer to our account</small>
                                        </div>
                                        <div class="payment-radio ms-3">
                                            <i class="bi bi-circle text-muted fs-4"></i>
                                            <i class="bi bi-check-circle-fill text-gold fs-4 d-none"></i>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- GCash -->
                            <label class="payment-method-label w-100 mb-3 cursor-pointer">
                                <input type="radio" name="payment_method" value="GCash" class="d-none">
                                <div class="card bg-dark border-secondary payment-card transition-all">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="payment-icon bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                            <i class="bi bi-phone text-gold fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="text-light mb-1">GCash</h6>
                                            <small class="text-muted">Pay securely via GCash E-Wallet</small>
                                        </div>
                                        <div class="payment-radio ms-3">
                                            <i class="bi bi-circle text-muted fs-4"></i>
                                            <i class="bi bi-check-circle-fill text-gold fs-4 d-none"></i>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- Credit/Debit Card -->
                            <label class="payment-method-label w-100 mb-4 cursor-pointer">
                                <input type="radio" name="payment_method" value="Credit/Debit Card" class="d-none">
                                <div class="card bg-dark border-secondary payment-card transition-all">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="payment-icon bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                            <i class="bi bi-credit-card text-gold fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="text-light mb-1">Credit/Debit Card</h6>
                                            <small class="text-muted">Visa, Mastercard, AMEX</small>
                                        </div>
                                        <div class="payment-radio ms-3">
                                            <i class="bi bi-circle text-muted fs-4"></i>
                                            <i class="bi bi-check-circle-fill text-gold fs-4 d-none"></i>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Added inline script for custom radio behavior -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const labels = document.querySelectorAll('.payment-method-label');
                                labels.forEach(label => {
                                    const radio = label.querySelector('input[type="radio"]');
                                    radio.addEventListener('change', function() {
                                        labels.forEach(l => {
                                            const card = l.querySelector('.payment-card');
                                            card.classList.remove('border-gold');
                                            card.classList.add('border-secondary');
                                            l.querySelector('.bi-circle').classList.remove('d-none');
                                            l.querySelector('.bi-check-circle-fill').classList.add('d-none');
                                        });
                                        if (this.checked) {
                                            const card = label.querySelector('.payment-card');
                                            card.classList.remove('border-secondary');
                                            card.classList.add('border-gold');
                                            label.querySelector('.bi-circle').classList.add('d-none');
                                            label.querySelector('.bi-check-circle-fill').classList.remove('d-none');
                                        }
                                    });
                                });
                            });
                        </script>
                        <style>
                            .cursor-pointer { cursor: pointer; }
                            .transition-all { transition: all 0.3s ease; }
                            .border-gold { border-color: #d4af37 !important; background-color: rgba(212, 175, 55, 0.05) !important; }
                        </style>

                        <button type="submit" class="btn btn-gold w-100 py-3 fw-bold fs-5 shadow-lg mb-3"><i class="bi bi-shield-lock me-2"></i>Confirm Payment</button>
                        
                        <div class="text-center">
                            <a href="<?= BASE_URL ?>/store/cart.php" class="text-muted text-decoration-none">Cancel and return to cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
