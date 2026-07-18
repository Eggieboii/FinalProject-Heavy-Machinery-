<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="fade-in py-4">
    <div class="text-center mb-5">
        <h1 class="section-title d-inline-block text-light position-relative">About Us</h1>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-lg-10">
            <div class="card bg-card border-secondary text-light p-4 p-md-5 text-center shadow">
                <i class="bi bi-building text-gold display-1 mb-4"></i>
                <h2 class="text-gold mb-3"><?php echo htmlspecialchars(SITE_NAME); ?></h2>
                <h4 class="fw-light text-muted mb-4"><?php echo htmlspecialchars(SITE_TAGLINE ?? 'Premium construction equipment for your toughest jobs.'); ?></h4>
                <p class="lead">
                    We are dedicated to providing the highest quality heavy machinery and construction equipment. 
                    With years of industry expertise, we stand behind every product we sell.
                </p>
            </div>
        </div>
    </div>

    <div class="mb-5">
        <h3 class="section-title text-light mb-4">Meet Our Team</h3>
        <div class="row g-4 justify-content-center">
            <?php 
            $members = defined('GROUP_MEMBERS') ? unserialize(GROUP_MEMBERS) : ['Admin'];
            foreach ($members as $member): 
                $name = is_array($member) ? $member['name'] : $member;
                $initial = strtoupper(substr($name, 0, 1));
            ?>
                <div class="col-md-4 col-sm-6 text-center">
                    <div class="card team-card bg-card border-secondary p-4 h-100">
                        <div class="team-avatar bg-dark border border-gold rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow" style="width: 100px; height: 100px;">
                            <span class="text-gold display-4 fw-bold"><?php echo htmlspecialchars($initial); ?></span>
                        </div>
                        <h4 class="text-light mb-1"><?php echo htmlspecialchars($name); ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card bg-dark border-gold text-light p-4 mb-5 shadow">
        <div class="row align-items-center">
            <div class="col-md-8 mb-3 mb-md-0">
                <h3 class="text-gold mb-2">Get In Touch</h3>
                <p class="mb-1"><i class="bi bi-geo-alt me-2 text-gold"></i> 123 Machinery Ave, Industrial District, City</p>
                <p class="mb-1"><i class="bi bi-envelope me-2 text-gold"></i> contact@cupofjudes.com</p>
                <p class="mb-0"><i class="bi bi-telephone me-2 text-gold"></i> (555) 123-4567</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?php echo BASE_URL; ?>/store/index.php" class="btn btn-gold btn-lg">Browse Products</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
