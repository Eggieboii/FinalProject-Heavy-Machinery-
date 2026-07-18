    </div><!-- /.container -->
</main>

<!-- Footer -->
<footer class="site-footer mt-auto">
    <div class="container">
        <div class="row py-4">
            <!-- Brand Column -->
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="d-flex align-items-center mb-2">
                    <img src="<?php echo LOGO_PATH; ?>" alt="Logo" class="footer-logo me-2">
                    <span class="footer-brand"><?php echo GROUP_NAME; ?> <span class="text-gold">Machinery</span></span>
                </div>
                <p class="text-muted small mb-0"><?php echo SITE_TAGLINE; ?></p>
            </div>
            
            <!-- Quick Links -->
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="text-gold mb-3">Quick Links</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-chevron-right me-1"></i>Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/store/index.php"><i class="bi bi-chevron-right me-1"></i>Store</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/about.php"><i class="bi bi-chevron-right me-1"></i>About Us</a></li>
                    <?php if (!isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/auth/register.php"><i class="bi bi-chevron-right me-1"></i>Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Contact -->
            <div class="col-md-4">
                <h6 class="text-gold mb-3">Contact Us</h6>
                <ul class="list-unstyled text-muted small">
                    <li class="mb-1"><i class="bi bi-envelope me-2"></i>info@cupofjudes.com</li>
                    <li class="mb-1"><i class="bi bi-telephone me-2"></i>(+63) 912-345-6789</li>
                    <li><i class="bi bi-geo-alt me-2"></i>Manila, Philippines</li>
                </ul>
            </div>
        </div>
        
        <!-- Disclaimer Banner -->
        <div class="disclaimer-banner">
            <div class="d-flex align-items-center justify-content-center">
                <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                <span><?php echo DISCLAIMER_TEXT; ?></span>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="text-center py-3 border-top border-secondary">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                <span class="mx-2">|</span>
                Made by <span class="text-gold"><?php echo GROUP_NAME; ?></span>
            </small>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>
