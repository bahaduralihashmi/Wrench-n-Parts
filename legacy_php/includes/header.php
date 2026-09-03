<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo SITE_URL; ?>">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#dc3545">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/uploads/logo.png">
    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
    </script>
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>" rel="stylesheet" id="theme-css">
    <?php
    $isAdmin = isset($_SERVER['PHP_SELF']) && (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/management/') !== false);
    if ($isAdmin):
    ?>
    <link href="<?php echo SITE_URL; ?>/css/admin.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?php echo SITE_URL; ?>/css/responsive.css?v=<?php echo filemtime(__DIR__ . '/../css/responsive.css'); ?>" rel="stylesheet">
</head>
<body>

<header class="wnp-header" id="wnpHeader">
    <div class="container">
        <div class="wnp-header-inner">
            <!-- Logo + Brand Name -->
            <a class="wnp-logo" href="<?php echo SITE_URL; ?>/index.php" id="wnpLogoLink">
                <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Wrench n Parts" class="wnp-logo-img" id="wnpLogoImg">
                <span class="wnp-logo-text">Wrench <span class="wnp-logo-accent">n</span> Parts</span>
            </a>

            <!-- Right Side -->
            <div class="wnp-header-right">
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="wnp-icon-btn" title="Toggle theme">
                    <i class="fas fa-moon"></i>
                    <i class="fas fa-sun"></i>
                </button>

                <?php if ($logged_in): ?>
                    <!-- Wishlist -->
                    <a class="wnp-icon-btn" href="<?php echo SITE_URL; ?>/customer/wishlist.php" title="Wishlist">
                        <i class="fas fa-heart"></i>
                        <?php $wc = getWishlistCountForUser(); if ($wc > 0): ?>
                            <span class="wnp-num-badge"><?php echo $wc; ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Cart -->
                    <a class="wnp-icon-btn" href="<?php echo SITE_URL; ?>/cart.php" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php $cc = getCartCount(); if ($cc > 0): ?>
                            <span class="wnp-num-badge"><?php echo $cc; ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- User Menu -->
                    <div class="wnp-user-menu">
                        <button class="wnp-user-trigger" data-bs-toggle="dropdown">
                            <span class="wnp-user-circle"><?php echo strtoupper(substr($current_user['name'], 0, 1)); ?></span>
                            <span class="wnp-user-text"><?php echo htmlspecialchars($current_user['name']); ?></span>
                            <i class="fas fa-angle-down"></i>
                        </button>
                        <?php
                        $dashMap = [
                            'customer' => 'customer/dashboard.php',
                            'shopkeeper' => 'shopkeeper/dashboard.php',
                            'workshop' => 'workshop/dashboard.php',
                            'admin' => 'admin/dashboard.php',
                            'management' => 'management/dashboard.php'
                        ];
                        $profileMap = [
                            'customer' => 'customer/profile.php',
                            'shopkeeper' => 'shopkeeper/profile.php',
                            'workshop' => 'workshop/profile.php',
                            'admin' => 'admin/settings.php',
                            'management' => 'admin/settings.php'
                        ];
                        $dashUrl = SITE_URL . '/' . ($dashMap[$current_user['role']] ?? 'index.php');
                        $profileUrl = SITE_URL . '/' . ($profileMap[$current_user['role']] ?? 'customer/profile.php');
                        ?>
                        <ul class="dropdown-menu dropdown-menu-end wnp-user-list">
                            <li class="wnp-list-header">
                                <strong><?php echo htmlspecialchars($current_user['name']); ?></strong>
                                <small><?php echo ucfirst($current_user['role']); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="wnp-list-item" href="<?php echo $dashUrl; ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
                            <li><a class="wnp-list-item" href="<?php echo $profileUrl; ?>"><i class="fas fa-user"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="wnp-list-item wnp-list-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Login Buttons -->
                    <div class="wnp-login-group">
                        <a href="<?php echo SITE_URL; ?>/login.php?role=customer" class="wnp-login-link">Customer</a>
                        <a href="<?php echo SITE_URL; ?>/login.php?role=shopkeeper" class="wnp-login-link">Shopkeeper</a>
                        <a href="<?php echo SITE_URL; ?>/login.php?role=workshop" class="wnp-login-btn">Workshop</a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Toggle -->
                <button class="wnp-mobile-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#wnpMobileMenu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="collapse wnp-mobile-menu" id="wnpMobileMenu">
            <?php if ($logged_in): ?>
                <div class="wnp-mobile-user">
                    <span class="wnp-user-circle"><?php echo strtoupper(substr($current_user['name'], 0, 1)); ?></span>
                    <div>
                        <strong><?php echo htmlspecialchars($current_user['name']); ?></strong>
                        <small><?php echo ucfirst($current_user['role']); ?></small>
                    </div>
                </div>
                <a href="<?php echo $dashUrl; ?>" class="wnp-mobile-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="<?php echo SITE_URL; ?>/cart.php" class="wnp-mobile-link"><i class="fas fa-shopping-cart"></i> Cart</a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="wnp-mobile-link wnp-mobile-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/login.php?role=customer" class="wnp-mobile-link"><i class="fas fa-user"></i> Customer Login</a>
                <a href="<?php echo SITE_URL; ?>/login.php?role=shopkeeper" class="wnp-mobile-link"><i class="fas fa-store"></i> Shopkeeper Login</a>
                <a href="<?php echo SITE_URL; ?>/login.php?role=workshop" class="wnp-mobile-link"><i class="fas fa-tools"></i> Workshop Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Logo Video Overlay -->
<div id="logoOverlay" class="wnp-logo-overlay">
    <video id="logoVideo" class="wnp-logo-video" muted loop playsinline preload="auto">
        <source src="<?php echo SITE_URL; ?>/uploads/team/<?php echo rawurlencode('video animation.mp4'); ?>" type="video/mp4">
    </video>
    <button class="wnp-logo-close" id="logoClose"><i class="fas fa-times"></i></button>
</div>

<script>
(function() {
    var logoLink = document.getElementById('wnpLogoLink');
    var video = document.getElementById('logoVideo');
    var overlay = document.getElementById('logoOverlay');
    var closeBtn = document.getElementById('logoClose');
    if (!logoLink || !video || !overlay) return;

    var isHome = window.location.pathname.endsWith('/index.php') || window.location.pathname === '/Wrench_n_Parts/' || window.location.pathname === '/Wrench_n_Parts';
    if (!isHome) return;

    logoLink.addEventListener('click', function(e) {
        e.preventDefault();
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        video.currentTime = 0;
        video.play().catch(function(){});
    });

    function closeLogo() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        video.pause();
        video.currentTime = 0;
    }

    if (closeBtn) closeBtn.addEventListener('click', closeLogo);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeLogo();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) closeLogo();
    });
})();
</script>

<?php
$flash = getFlash();
if ($flash): ?>
<div class="flash-container">
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show alert-modern" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>