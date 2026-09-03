<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
    </script>
    <meta name="base-url" content="<?php echo SITE_URL; ?>">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#dc3545">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/uploads/logo.png">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/customer-dashboard.css?v=<?php echo filemtime(__DIR__ . '/../css/customer-dashboard.css'); ?>" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/chatbot-response.css?v=<?php echo filemtime(__DIR__ . '/../css/chatbot-response.css'); ?>" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/responsive.css?v=<?php echo filemtime(__DIR__ . '/../css/responsive.css'); ?>" rel="stylesheet">
</head>
<body class="cust-body">

<nav class="cust-navbar">
    <div class="container-fluid px-4">
        <div class="cust-nav-left">
            <a class="cust-brand" href="<?php echo SITE_URL; ?>/index.php">
                <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Wrench n Parts" class="cust-brand-logo" style="height:auto!important;width:auto!important;max-height:50px!important;max-width:180px!important;object-fit:contain!important;">
                <div class="cust-brand-text">
                    <span class="cust-brand-name">Wrench n Parts</span>
                    <span class="cust-brand-tag">DRIVE WITH CONFIDENCE</span>
                </div>
            </a>
        </div>

        <div class="cust-nav-right">
            <button id="theme-toggle" class="cust-nav-icon-btn" title="Toggle theme">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
            <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="cust-nav-icon-link">
                <i class="fas fa-truck"></i>
                <span>Track Order</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/customer/returns.php" class="cust-nav-icon-link">
                <i class="fas fa-undo-alt"></i>
                <span>Returns</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/customer/wishlist.php" class="cust-nav-icon-link">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/workshop-finder.php" class="cust-nav-icon-link">
                <i class="fas fa-tools"></i>
                <span>Workshops</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/customer/profile.php" class="cust-nav-icon-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/cart.php" class="cust-nav-icon-link cust-cart-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
                <?php $cc = getCartCount(); if ($cc > 0): ?>
                    <span class="cust-cart-badge"><?php echo $cc; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/logout.php" class="cust-nav-icon-link" style="color:#dc3545;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>

        <!-- Mobile Hamburger Button -->
        <button class="cust-hamburger" id="custHamburger" onclick="var m=document.getElementById('custMobileMenu');var o=document.getElementById('custSlideOverlay');m.classList.toggle('open');o.classList.toggle('active');this.classList.toggle('active')">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Mobile Slide Menu (outside nav for z-index) -->
<div class="cust-slide-menu" id="custMobileMenu">
    <div class="cust-slide-header">
        <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Logo" style="height:36px;">
        <span style="font-weight:700;color:#fff;">Menu</span>
        <button onclick="document.getElementById('custMobileMenu').classList.remove('open');document.getElementById('custHamburger').classList.remove('active');document.getElementById('custSlideOverlay').classList.remove('active')" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="cust-slide-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="<?php echo SITE_URL; ?>/products.php" class="cust-slide-link"><i class="fas fa-box"></i> Browse Parts</a>
    <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="cust-slide-link"><i class="fas fa-truck"></i> Track Order</a>
    <a href="<?php echo SITE_URL; ?>/customer/returns.php" class="cust-slide-link"><i class="fas fa-undo-alt"></i> Returns</a>
    <a href="<?php echo SITE_URL; ?>/customer/wishlist.php" class="cust-slide-link"><i class="fas fa-heart"></i> Wishlist</a>
    <a href="<?php echo SITE_URL; ?>/workshop-finder.php" class="cust-slide-link"><i class="fas fa-tools"></i> Workshops</a>
    <a href="<?php echo SITE_URL; ?>/cart.php" class="cust-slide-link"><i class="fas fa-shopping-cart"></i> Cart <?php $cc2 = getCartCount(); if ($cc2 > 0): ?><span style="background:#dc3545;color:#fff;border-radius:50%;padding:1px 6px;font-size:0.7rem;margin-left:4px;"><?php echo $cc2; ?></span><?php endif; ?></a>
    <a href="<?php echo SITE_URL; ?>/customer/profile.php" class="cust-slide-link"><i class="fas fa-user"></i> Profile</a>
    <div style="border-top:1px solid rgba(255,255,255,0.1);margin:8px 0;"></div>
    <a href="<?php echo SITE_URL; ?>/logout.php" class="cust-slide-link" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="cust-slide-overlay" id="custSlideOverlay" onclick="document.getElementById('custMobileMenu').classList.remove('open');document.getElementById('custHamburger').classList.remove('active');this.classList.remove('active')"></div>

<div class="cust-mobile-nav">
    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="cust-mobile-link active">
        <i class="fas fa-home"></i><span>Home</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/products.php" class="cust-mobile-link">
        <i class="fas fa-box"></i><span>Parts</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="cust-mobile-link">
        <i class="fas fa-truck"></i><span>Orders</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/customer/returns.php" class="cust-mobile-link">
        <i class="fas fa-undo-alt"></i><span>Returns</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/customer/wishlist.php" class="cust-mobile-link">
        <i class="fas fa-heart"></i><span>Wishlist</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/cart.php" class="cust-mobile-link">
        <i class="fas fa-shopping-cart"></i><span>Cart</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/customer/profile.php" class="cust-mobile-link">
        <i class="fas fa-user"></i><span>Profile</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/logout.php" class="cust-mobile-link" style="color:#dc3545;">
        <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
</div>
