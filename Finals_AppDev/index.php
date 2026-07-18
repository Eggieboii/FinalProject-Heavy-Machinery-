<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Close the main container from header temporarily to allow full-width hero -->
    </div>
</main>

<div class="hero-section text-light py-5 mb-5 slide-up" style="background: var(--bg-primary);">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title display-3 fw-bold mb-3">
                    Heavy Machinery<br>
                    Built to <span class="text-gold">Last</span>
                </h1>
                <p class="hero-subtitle lead mb-4 text-muted">
                    <?php echo htmlspecialchars(SITE_TAGLINE ?? 'Premium construction equipment for your toughest jobs.'); ?>
                </p>
                <div class="d-flex gap-3">
                    <a href="<?php echo BASE_URL; ?>/store/index.php" class="btn btn-gold btn-lg fw-bold px-4">Browse Store <i class="bi bi-arrow-right"></i></a>
                    <a href="<?php echo BASE_URL; ?>/pages/about.php" class="btn btn-gold-outline btn-lg fw-bold px-4">About Us</a>
                </div>
            </div>
            <div class="col-lg-6 text-center mt-5 mt-lg-0">
                <i class="bi bi-gear-wide-connected text-gold" style="font-size: 15rem; opacity: 0.8;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Reopen main container -->
<main class="flex-shrink-0">
    <div class="container fade-in">

        <section class="mb-5 py-4">
            <div class="text-center mb-5">
                <h2 class="section-title d-inline-block text-light position-relative">Why Choose Us</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 bg-card text-center border-secondary hover-effect p-4">
                        <div class="card-body">
                            <i class="bi bi-shield-check text-gold display-4 mb-3 d-block"></i>
                            <h5 class="card-title text-light">Safety First</h5>
                            <p class="card-text text-muted small">All machinery passes rigorous safety inspections before delivery.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 bg-card text-center border-secondary hover-effect p-4">
                        <div class="card-body">
                            <i class="bi bi-truck text-gold display-4 mb-3 d-block"></i>
                            <h5 class="card-title text-light">Fast Delivery</h5>
                            <p class="card-text text-muted small">Nationwide delivery network ensures your equipment arrives on time.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 bg-card text-center border-secondary hover-effect p-4">
                        <div class="card-body">
                            <i class="bi bi-headset text-gold display-4 mb-3 d-block"></i>
                            <h5 class="card-title text-light">24/7 Support</h5>
                            <p class="card-text text-muted small">Round-the-clock technical support for all your machinery needs.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 bg-card text-center border-secondary hover-effect p-4">
                        <div class="card-body">
                            <i class="bi bi-award text-gold display-4 mb-3 d-block"></i>
                            <h5 class="card-title text-light">Quality Guaranteed</h5>
                            <p class="card-text text-muted small">Premium brands and top-tier equipment for maximum reliability.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title m-0 text-light position-relative">Featured Products</h2>
                <a href="<?php echo BASE_URL; ?>/store/index.php" class="btn btn-sm btn-gold-outline">View All</a>
            </div>
            
            <div class="row g-4">
                <?php
                $stmt = mysqli_prepare($conn, "
                    SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = 'active' 
                    ORDER BY p.created_at DESC 
                    LIMIT 4
                ");
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($product = mysqli_fetch_assoc($result)) {
                        ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="card product-card h-100 bg-card border-secondary">
                                <div class="position-relative">
                                    <span class="badge badge-category position-absolute top-0 start-0 m-2 bg-dark text-gold border border-gold"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <span class="badge badge-stock position-absolute top-0 end-0 m-2 bg-success">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-stock position-absolute top-0 end-0 m-2 bg-danger">Out of Stock</span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $imgSrc = !empty($product['image']) ? PRODUCT_IMG_PATH . '/' . $product['image'] : 'https://via.placeholder.com/300x200?text=No+Image';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="card-img-top p-3" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: contain; background: var(--bg-primary);">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="product-name card-title text-light mb-1 text-truncate" title="<?php echo htmlspecialchars($product['name']); ?>"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="product-price text-gold fw-bold mb-3 fs-5"><?php echo formatPrice($product['price']); ?></p>
                                    <a href="<?php echo BASE_URL; ?>/store/product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-secondary w-100 mt-auto">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="empty-state p-5 text-center text-muted bg-card border border-secondary rounded"><i class="bi bi-box-seam display-1 mb-3 text-secondary"></i><p>No products available at the moment.</p></div></div>';
                }
                mysqli_stmt_close($stmt);
                ?>
            </div>
        </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
