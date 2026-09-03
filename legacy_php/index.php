<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/config.php';

$featured = $conn->query("SELECT p.*, s.shop_name, c.category_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.status = 'available' ORDER BY p.created_at DESC LIMIT 8");
$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.category_id AND status='available') as product_count FROM categories c ORDER BY c.category_name");
$workshops = $conn->query("SELECT * FROM workshops WHERE status = 'active' ORDER BY rating DESC LIMIT 4");
$hotDeals = $conn->query("SELECT hd.*, s.shop_name FROM hot_deals hd LEFT JOIN shops s ON hd.shop_id = s.shop_id WHERE hd.status = 'active' AND CURDATE() >= hd.start_date AND CURDATE() <= hd.end_date ORDER BY hd.priority ASC, hd.created_at DESC");
$wishIds = getUserWishlistIds();

$stat_partners = $conn->query("SELECT COUNT(*) as total FROM shops WHERE status='active'")->fetch_assoc()['total']
              + $conn->query("SELECT COUNT(*) as total FROM workshops WHERE status='active'")->fetch_assoc()['total'];
$stat_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE status='available'")->fetch_assoc()['total'];
$stat_customers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='customer' AND status='active'")->fetch_assoc()['total'];

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="section-v2 hero-v2">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="hero-label-badge"><i class="fas fa-bolt me-1"></i> SPARK THE DIFFERENCE</span>
                <h1 class="hero-title-v2">No guess, No mess just the <span class="highlight">Exact part</span> you need, Is our feed.</h1>
                <p class="hero-desc-v2">Shop thousands of quality-tested auto parts from trusted sellers. Engine parts, brakes, filters, body parts &amp; more &mdash; all with verified quality and fast delivery.</p>
                <div class="hero-actions">
                    <a href="products.php" class="btn-modern btn-outline-modern"><i class="fas fa-shopping-bag me-2"></i>Browse Parts</a>
                    <?php if ($logged_in && $current_user['role'] === 'shopkeeper'): ?>
                        <a href="shopkeeper/dashboard.php" class="btn-modern btn-primary-modern"><i class="fas fa-store me-2"></i>Start Selling</a>
                    <?php else: ?>
                        <a href="register-shopkeeper.php" class="btn-modern btn-primary-modern"><i class="fas fa-store me-2"></i>Start Selling</a>
                    <?php endif; ?>
                    <a href="register-workshop.php" class="btn-modern btn-outline-modern"><i class="fas fa-tools me-2"></i>Register Your Workshop</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat-item">
                        <h3><span class="counter"><?php echo number_format($stat_partners); ?></span><span class="counter-sign">+</span></h3>
                        <p>Trusted Partners</p>
                    </div>
                    <div class="hero-stat-item">
                        <h3><span class="counter"><?php echo number_format($stat_products); ?></span><span class="counter-sign">+</span></h3>
                        <p>Quality Products</p>
                    </div>
                    <div class="hero-stat-item">
                        <h3><span class="counter"><?php echo number_format($stat_customers); ?></span><span class="counter-sign">+</span></h3>
                        <p>Happy Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex">
                <div class="hero-image-wrap">
                    <img src="<?php echo SITE_URL; ?>/uploads/hero-mechanic.png" alt="Mechanic Working" class="hero-main-img">
                    <div class="hero-float-badge badge-1">
                        <i class="fas fa-cog"></i> Engine Parts
                    </div>
                    <div class="hero-float-badge badge-2">
                        <i class="fas fa-shield-alt"></i> Verified Quality
                    </div>
                    <div class="hero-float-badge badge-3">
                        <i class="fas fa-shipping-fast"></i> Fast Delivery
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Wrench n Parts Section -->
<section class="section-v2 bg-soft">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-badge">WHY <?php echo strtoupper(SITE_NAME); ?></span>
            <h2 class="section-heading">Everything you need to keep cars running</h2>
            <p class="section-subheading mx-auto">A complete marketplace for buyers and sellers of spare parts</p>
        </div>
        <div class="row g-4 stagger-children animate-on-scroll">
            <div class="col-lg col-md-4 col-6">
                <div class="feature-v2">
                    <div class="feature-icon"><i class="fas fa-th-large"></i></div>
                    <h6>Huge Catalog</h6>
                    <p>Find what you need across thousands of auto parts</p>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6">
                <div class="feature-v2">
                    <div class="feature-icon"><i class="fas fa-check-circle"></i></div>
                    <h6>Verified Sellers</h6>
                    <p>Every seller is verified for quality and reliability</p>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6">
                <div class="feature-v2">
                    <div class="feature-icon"><i class="fas fa-shipping-fast"></i></div>
                    <h6>Fast Delivery</h6>
                    <p>Get your parts delivered to your doorstep</p>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6">
                <div class="feature-v2">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <h6>Secure Payments</h6>
                    <p>Payment protection for worry-free transactions</p>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6">
                <div class="feature-v2">
                    <div class="feature-icon"><i class="fas fa-tools"></i></div>
                    <h6>Trusted Workshops</h6>
                    <p>Find certified mechanics near you for repairs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hot Deals Carousel -->
<?php if ($hotDeals && $hotDeals->num_rows > 0): ?>
<style>
/* ============================================
   HOT DEALS CAROUSEL - Theme-Aware
   ============================================ */
.hd-section {
    padding: 36px 0 44px;
    background: var(--bg-light);
    position: relative;
    overflow: hidden;
}
[data-theme="dark"] .hd-section {
    background: var(--bg-light);
}
.hd-section::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    opacity: 0.3;
}
.hd-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}
.hd-header {
    text-align: center;
    margin-bottom: 28px;
}
.hd-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--accent-light);
    color: var(--accent);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 6px 16px;
    border-radius: 50px;
    border: 1px solid var(--accent-light);
    margin-bottom: 14px;
}
.hd-header-badge i {
    animation: hd-pulse 2s infinite;
}
@keyframes hd-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.hd-header h2 {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-dark);
    margin: 0 0 6px;
    letter-spacing: -0.5px;
}
.hd-header h2 span {
    background: linear-gradient(135deg, #ff4757, #ff6b81);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hd-header p {
    color: var(--text-muted);
    font-size: 0.92rem;
    margin: 0;
}

/* Carousel Wrapper */
.hd-carousel {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
}
.hd-track {
    display: flex;
    transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}
.hd-slide {
    min-width: 100%;
    padding: 0 4px;
}
.hd-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 320px;
    background: var(--bg-white);
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-lg);
    transition: box-shadow 0.3s;
}
[data-theme="dark"] .hd-card {
    background: var(--bg-white);
    border-color: var(--border);
    box-shadow: var(--shadow-lg);
}

/* Left Content */
.hd-content {
    padding: 32px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 2;
}
.hd-top-badges {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.hd-discount-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: linear-gradient(135deg, #ff4757, #ff6b81);
    color: #fff;
    font-size: 0.82rem;
    font-weight: 800;
    padding: 7px 16px;
    border-radius: 8px;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(255,71,87,0.3);
}
.hd-limited-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,165,0,0.1);
    color: #e69500;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid rgba(255,165,0,0.2);
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
[data-theme="dark"] .hd-limited-badge {
    background: rgba(255,165,0,0.12);
    color: #ffa500;
}
.hd-category-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: var(--bg-gray);
    color: var(--text-muted);
    font-size: 0.72rem;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 8px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.hd-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-dark);
    line-height: 1.2;
    margin: 0 0 10px;
    letter-spacing: -0.3px;
}
.hd-description {
    color: var(--text-body);
    font-size: 0.85rem;
    line-height: 1.5;
    margin: 0 0 16px;
}
.hd-prices {
    display: flex;
    align-items: baseline;
    gap: 14px;
    margin-bottom: 20px;
}
.hd-price-current {
    font-size: 1.8rem;
    font-weight: 900;
    color: var(--accent);
    letter-spacing: -1px;
}
.hd-price-original {
    font-size: 1.1rem;
    color: var(--text-muted);
    text-decoration: line-through;
    font-weight: 500;
}
.hd-coupon-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
}
.hd-coupon-box {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-gray);
    border: 1px dashed var(--border);
    border-radius: 8px;
    padding: 8px 14px;
    cursor: pointer;
    transition: all 0.3s;
}
.hd-coupon-box:hover {
    background: var(--accent-light);
    border-color: var(--accent);
}
.hd-coupon-label {
    color: var(--text-muted);
    font-size: 0.78rem;
    font-weight: 500;
}
.hd-coupon-code {
    color: var(--accent);
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: 1px;
}
.hd-coupon-copy {
    color: var(--text-muted);
    font-size: 0.85rem;
    transition: color 0.3s;
}
.hd-coupon-box:hover .hd-coupon-copy {
    color: var(--accent);
}
.hd-copied-msg {
    color: var(--success);
    font-size: 0.78rem;
    font-weight: 600;
    display: none;
}
.hd-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.hd-shop-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #dc3545, #ff4757);
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 20px rgba(220,53,69,0.3);
    position: relative;
    overflow: hidden;
}
.hd-shop-btn::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: left 0.5s;
}
.hd-shop-btn:hover::before {
    left: 100%;
}
.hd-shop-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(220,53,69,0.45);
    color: #fff;
}
.hd-stock-info {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 500;
}
.hd-stock-dot {
    width: 6px; height: 6px;
    background: #ff4757;
    border-radius: 50%;
    animation: hd-blink 1.5s infinite;
}
@keyframes hd-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Right Image */
.hd-image-side {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: var(--bg-gray);
    padding: 20px;
}
[data-theme="dark"] .hd-image-side {
    background: var(--bg-gray);
}
.hd-image-wrapper {
    position: relative;
    z-index: 2;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hd-product-img {
    width: 100%;
    height: 100%;
    max-height: 320px;
    object-fit: contain;
    filter: drop-shadow(0 10px 25px rgba(0,0,0,0.12));
    transition: transform 0.5s ease;
}
[data-theme="dark"] .hd-product-img {
    filter: drop-shadow(0 15px 35px rgba(0,0,0,0.4));
}
.hd-card:hover .hd-product-img {
    transform: scale(1.05) rotate(-1deg);
}
.hd-image-placeholder {
    width: 200px;
    height: 200px;
    background: var(--border-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: var(--border);
}
.hd-image-discount-float {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 3;
    background: linear-gradient(135deg, #ff4757, #ff6b81);
    color: #fff;
    padding: 14px 18px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(255,71,87,0.4);
    animation: hd-float 3s ease-in-out infinite;
}
@keyframes hd-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
.hd-float-num {
    display: block;
    font-size: 1.6rem;
    font-weight: 900;
    line-height: 1;
}
.hd-float-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 1px;
    margin-top: 2px;
    opacity: 0.85;
}

/* Countdown Timer */
.hd-timer {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 18px;
}
.hd-timer-box {
    background: var(--bg-gray);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 6px 8px;
    text-align: center;
    min-width: 46px;
}
[data-theme="dark"] .hd-timer-box {
    background: var(--bg-gray);
    border-color: var(--border);
}
.hd-timer-num {
    display: block;
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--text-dark);
    line-height: 1;
}
.hd-timer-lbl {
    display: block;
    font-size: 0.6rem;
    color: var(--text-muted);
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-top: 3px;
}
.hd-timer-sep {
    color: var(--text-muted);
    font-size: 1rem;
    font-weight: 700;
    margin-top: -8px;
}

/* Navigation */
.hd-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 44px; height: 44px;
    background: var(--bg-white);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--text-dark);
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    opacity: 0;
    box-shadow: var(--shadow-sm);
}
.hd-carousel:hover .hd-nav-btn {
    opacity: 1;
}
.hd-nav-btn:hover {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 4px 15px rgba(220,53,69,0.35);
}
.hd-nav-prev { left: 14px; }
.hd-nav-next { right: 14px; }

/* Dots */
.hd-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}
.hd-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--border);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    padding: 0;
}
.hd-dot.active {
    background: var(--accent);
    width: 28px;
    border-radius: 5px;
    box-shadow: 0 0 10px var(--accent-glow);
}
.hd-dot:hover:not(.active) {
    background: var(--text-muted);
}

/* Progress Bar */
.hd-progress-wrap {
    display: flex;
    justify-content: center;
    margin-top: 16px;
}
.hd-progress {
    width: 120px;
    height: 3px;
    background: var(--border);
    border-radius: 3px;
    overflow: hidden;
}
.hd-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #dc3545, #ff6b81);
    border-radius: 3px;
    width: 0%;
    transition: width 0.1s linear;
}

/* Responsive */
@media (max-width: 991px) {
    .hd-card {
        grid-template-columns: 1fr;
        min-height: auto;
    }
    .hd-image-side {
        min-height: 200px;
        order: -1;
    }
    .hd-content {
        padding: 24px 20px 28px;
    }
    .hd-title { font-size: 1.3rem; }
    .hd-price-current { font-size: 1.5rem; }
}
@media (max-width: 575px) {
    .hd-section { padding: 28px 0 36px; }
    .hd-header h2 { font-size: 1.3rem; }
    .hd-content { padding: 20px 16px 24px; }
    .hd-title { font-size: 1.2rem; }
    .hd-prices { gap: 10px; }
    .hd-price-current { font-size: 1.3rem; }
    .hd-nav-btn { display: none; }
    .hd-top-badges { gap: 6px; }
    .hd-timer-box { min-width: 40px; padding: 5px 7px; }
    .hd-timer-num { font-size: 0.9rem; }
}
</style>

<section class="hd-section">
    <div class="hd-container">
        <div class="hd-header">
            <div class="hd-header-badge"><i class="fas fa-fire"></i> HOT DEALS</div>
            <h2>Exclusive <span>Deals</span> on Auto Parts</h2>
            <p>Limited-time offers on premium car parts &mdash; grab them before they're gone!</p>
        </div>

        <div class="hd-carousel" id="hdCarousel">
            <div class="hd-track" id="hdTrack">
                <?php $dealIdx = 0; while ($deal = $hotDeals->fetch_assoc()): ?>
                <div class="hd-slide" data-end="<?php echo htmlspecialchars($deal['end_date']); ?>">
                    <div class="hd-card">
                        <div class="hd-content">
                            <div class="hd-top-badges">
                                <?php if (!empty($deal['discount_text'])): ?>
                                    <span class="hd-discount-badge"><i class="fas fa-percent"></i> <?php echo htmlspecialchars($deal['discount_text']); ?></span>
                                <?php endif; ?>
                                <span class="hd-limited-badge"><i class="fas fa-clock"></i> Limited Time</span>
                                <?php if (!empty($deal['category'])): ?>
                                    <span class="hd-category-tag"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($deal['category']); ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 class="hd-title"><?php echo htmlspecialchars($deal['title']); ?></h3>

                            <?php if (!empty($deal['description'])): ?>
                                <p class="hd-description"><?php echo htmlspecialchars($deal['description']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($deal['deal_price'])): ?>
                            <div class="hd-prices">
                                <span class="hd-price-current">Rs. <?php echo number_format($deal['deal_price']); ?></span>
                                <?php if (!empty($deal['original_price']) && $deal['original_price'] > $deal['deal_price']): ?>
                                    <span class="hd-price-original">Rs. <?php echo number_format($deal['original_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($deal['coupon_code'])): ?>
                            <div class="hd-coupon-row">
                                <div class="hd-coupon-box" onclick="hdCopyCoupon(this, '<?php echo htmlspecialchars($deal['coupon_code']); ?>')">
                                    <i class="fas fa-tag hd-coupon-copy"></i>
                                    <span class="hd-coupon-label">Use code:</span>
                                    <span class="hd-coupon-code"><?php echo htmlspecialchars($deal['coupon_code']); ?></span>
                                    <i class="fas fa-copy hd-coupon-copy"></i>
                                </div>
                                <span class="hd-copied-msg"><i class="fas fa-check"></i> Copied!</span>
                            </div>
                            <?php endif; ?>

                            <div class="hd-timer" data-end="<?php echo htmlspecialchars($deal['end_date']); ?>">
                                <div class="hd-timer-box hd-timer-days"><span class="hd-timer-num" data-unit="days">00</span><span class="hd-timer-lbl">DAYS</span></div>
                                <span class="hd-timer-sep hd-timer-days">:</span>
                                <div class="hd-timer-box"><span class="hd-timer-num" data-unit="hours">00</span><span class="hd-timer-lbl">HRS</span></div>
                                <span class="hd-timer-sep">:</span>
                                <div class="hd-timer-box"><span class="hd-timer-num" data-unit="minutes">00</span><span class="hd-timer-lbl">MIN</span></div>
                                <span class="hd-timer-sep">:</span>
                                <div class="hd-timer-box"><span class="hd-timer-num" data-unit="seconds">00</span><span class="hd-timer-lbl">SEC</span></div>
                            </div>

                            <div class="hd-actions">
                                <a href="<?php echo htmlspecialchars($deal['button_link']); ?>" class="hd-shop-btn">
                                    <i class="fas fa-shopping-cart"></i> <?php echo htmlspecialchars($deal['button_text'] ?? 'Shop Now'); ?> <i class="fas fa-arrow-right"></i>
                                </a>
                                <div class="hd-stock-info">
                                    <span class="hd-stock-dot"></span> Limited stock available
                                </div>
                            </div>
                        </div>

                        <div class="hd-image-side">
                            <div class="hd-image-bg"></div>
                            <?php if (!empty($deal['banner_image'])): ?>
                                <div class="hd-image-wrapper">
                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($deal['banner_image']); ?>" alt="<?php echo htmlspecialchars($deal['title']); ?>" class="hd-product-img" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="hd-image-placeholder"><i class="fas fa-cogs"></i></div>
                            <?php endif; ?>
                            <?php if (!empty($deal['discount_text'])): ?>
                            <div class="hd-image-discount-float">
                                <span class="hd-float-num"><?php echo htmlspecialchars($deal['discount_text']); ?></span>
                                <span class="hd-float-label">OFF</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php $dealIdx++; endwhile; ?>
            </div>

            <?php if ($hotDeals->num_rows > 1): ?>
            <button class="hd-nav-btn hd-nav-prev" id="hdPrev"><i class="fas fa-chevron-left"></i></button>
            <button class="hd-nav-btn hd-nav-next" id="hdNext"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
        </div>

        <?php if ($hotDeals->num_rows > 1): ?>
        <div class="hd-dots" id="hdDots"></div>
        <div class="hd-progress-wrap">
            <div class="hd-progress"><div class="hd-progress-fill" id="hdProgressFill"></div></div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    var track = document.getElementById('hdTrack');
    var carousel = document.getElementById('hdCarousel');
    if (!track || !carousel) return;

    var slides = track.querySelectorAll('.hd-slide');
    var total = slides.length;
    if (total === 0) return;

    var current = 0;
    var interval = 2000;
    var timer = null;
    var progressTimer = null;
    var progressStart = 0;
    var isPaused = false;
    var dotsContainer = document.getElementById('hdDots');
    var progressFill = document.getElementById('hdProgressFill');

    // Create dots
    if (dotsContainer) {
        for (var i = 0; i < total; i++) {
            var dot = document.createElement('button');
            dot.className = 'hd-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('data-index', i);
            dot.addEventListener('click', function() {
                goTo(parseInt(this.getAttribute('data-index')));
                resetTimer();
            });
            dotsContainer.appendChild(dot);
        }
    }

    function goTo(index) {
        current = ((index % total) + total) % total;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';

        // Update dots
        if (dotsContainer) {
            var dots = dotsContainer.querySelectorAll('.hd-dot');
            dots.forEach(function(d, idx) {
                d.classList.toggle('active', idx === current);
            });
        }

        // Update countdowns for current slide
        updateCountdowns();
    }

    function next() {
        goTo(current + 1);
    }

    function prev() {
        goTo(current - 1);
    }

    function startProgress() {
        if (!progressFill) return;
        progressStart = Date.now();
        progressFill.style.transition = 'none';
        progressFill.style.width = '0%';
        requestAnimationFrame(function() {
            progressFill.style.transition = 'width ' + interval + 'ms linear';
            progressFill.style.width = '100%';
        });
    }

    function startTimer() {
        stopTimer();
        startProgress();
        timer = setInterval(function() {
            if (!isPaused) next();
        }, interval);
    }

    function stopTimer() {
        if (timer) clearInterval(timer);
        timer = null;
        if (progressFill) {
            progressFill.style.transition = 'none';
            progressFill.style.width = '0%';
        }
    }

    function resetTimer() {
        stopTimer();
        startTimer();
    }

    // Pause on hover
    carousel.addEventListener('mouseenter', function() {
        isPaused = true;
        if (progressFill) {
            var elapsed = Date.now() - progressStart;
            var currentWidth = Math.min((elapsed / interval) * 100, 100);
            progressFill.style.transition = 'none';
            progressFill.style.width = currentWidth + '%';
        }
    });

    carousel.addEventListener('mouseleave', function() {
        isPaused = false;
        resetTimer();
    });

    // Nav buttons
    var prevBtn = document.getElementById('hdPrev');
    var nextBtn = document.getElementById('hdNext');
    if (prevBtn) prevBtn.addEventListener('click', function() { prev(); resetTimer(); });
    if (nextBtn) nextBtn.addEventListener('click', function() { next(); resetTimer(); });

    // Touch support
    var touchStartX = 0;
    carousel.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        isPaused = true;
    }, { passive: true });

    carousel.addEventListener('touchend', function(e) {
        var diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) next();
            else prev();
            resetTimer();
        }
        isPaused = false;
    }, { passive: true });

    // Countdown timers
    function updateCountdowns() {
        var activeSlide = slides[current];
        var timerEl = activeSlide.querySelector('.hd-timer');
        if (!timerEl) return;
        var endDate = timerEl.getAttribute('data-end');
        if (!endDate) return;

        var target = new Date(endDate + 'T23:59:59+05:00').getTime();
        var now = new Date().getTime();
        var diff = target - now;

        if (diff <= 0) {
            timerEl.querySelectorAll('.hd-timer-num').forEach(function(n) { n.textContent = '00'; });
            return;
        }

        // Show days only when > 7 days remaining, otherwise use total hours
        var d = Math.floor(diff / (1000 * 60 * 60 * 24));
        var daysEl = timerEl.querySelector('.hd-timer-days');
        if (d > 7) {
            if (daysEl) daysEl.style.display = '';
            var h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var totalH = h;
        } else {
            if (daysEl) daysEl.style.display = 'none';
            var totalH = Math.floor(diff / (1000 * 60 * 60));
            d = 0;
        }
        var m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        var s = Math.floor((diff % (1000 * 60)) / 1000);

        var nums = timerEl.querySelectorAll('.hd-timer-num');
        nums.forEach(function(n) {
            var unit = n.getAttribute('data-unit');
            var val = 0;
            if (unit === 'days') val = d;
            else if (unit === 'hours') val = totalH;
            else if (unit === 'minutes') val = m;
            else if (unit === 'seconds') val = s;
            n.textContent = val < 10 ? '0' + val : val;
        });
    }

    // Update countdown every second
    setInterval(updateCountdowns, 1000);
    updateCountdowns();

    // Start
    startTimer();
})();
</script>

<?php endif; ?>

<script>
(function(){
    var currentCount = <?php echo $hotDeals ? $hotDeals->num_rows : 0; ?>;
    setInterval(function(){
        fetch('<?php echo SITE_URL; ?>/api/hot-deals-count.php')
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.count !== currentCount){
                currentCount = d.count;
                location.reload();
            }
        });
    }, 3000);
})();
</script>

<!-- Categories Section -->
<section class="section-v2" style="padding-top:20px;padding-bottom:30px;">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-badge">PART CATALOG</span>
            <h2 class="section-heading">Browse popular part categories</h2>
        </div>
        <div class="row g-4 stagger-children animate-on-scroll">
            <?php
            $catIcons = [
                'Air Conditioning & Heating' => 'fas fa-snowflake',
                'Belts, Chains & Hoses' => 'fas fa-chain',
                'Body & Interior' => 'fas fa-car-side',
                'Brake, Suspension & Steering' => 'fas fa-compact-disc',
                'Car Care & Detailing' => 'fas fa-spray-can',
                'Cooling, Fuel & Air Systems' => 'fas fa-fan',
                'Electrical & Electronics' => 'fas fa-car-battery',
                'Engine & Performance' => 'fas fa-cogs',
                'Exhaust System' => 'fas fa-wind',
                'Filters' => 'fas fa-filter',
                'Ignition & Starting System' => 'fas fa-key',
                'Lighting & Visibility' => 'fas fa-lightbulb',
                'Maintenance, Fluids & Accessories' => 'fas fa-oil-can',
                'Tools & Equipment' => 'fas fa-tools',
                'Transmission & Drivetrain' => 'fas fa-cogs',
                'Wheels & Tires' => 'fas fa-circle-notch'
            ];
            $catColors = ['#1a1a2e', '#c0392b', '#2980b9', '#8e44ad', '#27ae60', '#e67e22', '#2c3e50', '#f39c12', '#16a085', '#d35400', '#7f8c8d', '#34495e', '#e74c3c', '#3498db', '#9b59b6', '#1abc9c'];
            $allCats = [];
            while ($cat = $categories->fetch_assoc()) $allCats[] = $cat;
            $i = 0;
            foreach ($allCats as $cat):
                if ($i === 4) echo '</div><div class="row g-4" id="extraCats" style="display:none;">';
            ?>
            <div class="col-lg-3 col-md-4 col-6">
                <a href="products.php?category=<?php echo $cat['category_id']; ?>" class="cat-card-v2">
                    <div class="cat-img-wrap">
                        <?php if ($cat['category_image']): ?>
                            <img src="uploads/<?php echo $cat['category_image']; ?>" alt="<?php echo $cat['category_name']; ?>" loading="lazy">
                        <?php else: ?>
                            <i class="<?php echo $catIcons[$cat['category_name']] ?? 'fas fa-cog'; ?>" style="color:rgba(255,255,255,0.8);"></i>
                        <?php endif; ?>
                        <div class="cat-overlay">
                            <i class="<?php echo $catIcons[$cat['category_name']] ?? 'fas fa-cog'; ?>"></i>
                        </div>
                    </div>
                    <div class="cat-body">
                        <h6><?php echo $cat['category_name']; ?></h6>
                        <small><?php echo $cat['product_count']; ?> products</small>
                    </div>
                </a>
            </div>
            <?php $i++; endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <button onclick="toggleCats()" id="toggleCatsBtn" style="background:none;border:2px solid #dc3545;color:#dc3545;padding:10px 28px;border-radius:30px;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:8px;">
                <span id="toggleCatsText">View More Categories</span>
                <i class="fas fa-arrow-down" id="toggleCatsIcon"></i>
            </button>
        </div>
        <style>
        #extraCats { transition: all 0.4s ease; }
        #toggleCatsBtn:hover { background:#dc3545 !important; color:#fff !important; }
        </style>
        <script>
        function toggleCats() {
            var el = document.getElementById('extraCats');
            var txt = document.getElementById('toggleCatsText');
            var ico = document.getElementById('toggleCatsIcon');
            if (el.style.display === 'none') {
                el.style.display = '';
                txt.textContent = 'Show Less Categories';
                ico.className = 'fas fa-arrow-up';
            } else {
                el.style.display = 'none';
                txt.textContent = 'View More Categories';
                ico.className = 'fas fa-arrow-down';
            }
        }
        </script>
    </div>
</section>

<!-- Popular Products Section -->
<section class="section-v2" style="padding-top:30px;">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-badge">BEST SELLERS</span>
            <h2 class="section-heading">Popular spare parts</h2>
            <p class="section-subheading mx-auto">Our best-selling parts from trusted shops</p>
        </div>
        <div class="row g-4 stagger-children animate-on-scroll">
            <?php while ($prod = $featured->fetch_assoc()): ?>
            <div class="col-lg-3 col-md-6">
                <div class="product-v2">
                    <div class="product-img-wrap">
                        <?php if ($prod['product_image']): ?>
                            <img src="uploads/<?php echo $prod['product_image']; ?>" alt="<?php echo $prod['product_name']; ?>">
                        <?php else: ?>
                            <div class="placeholder-icon"><i class="fas fa-cog"></i></div>
                        <?php endif; ?>
                        <?php if ($prod['stock'] > 0): ?>
                            <span class="product-badge in-stock">In Stock</span>
                        <?php else: ?>
                            <span class="product-badge out-stock">Sold Out</span>
                        <?php endif; ?>
                        <button type="button" class="product-wishlist <?php echo in_array($prod['product_id'], $wishIds) ? 'active' : ''; ?>" onclick="toggleWishlist(<?php echo $prod['product_id']; ?>, this)"><i class="<?php echo in_array($prod['product_id'], $wishIds) ? 'fas' : 'far'; ?> fa-heart"></i></button>
                    </div>
                    <div class="product-info">
                        <span class="product-category"><?php echo $prod['category_name'] ?? 'General'; ?></span>
                        <h6 class="product-name-v2"><?php echo $prod['product_name']; ?></h6>
                        <p class="product-brand-v2"><?php echo $prod['brand'] ?? ''; ?></p>
                        <div class="product-meta">
                            <span class="product-price-v2"><?php echo formatCurrency($prod['price']); ?></span>
                            <a href="products.php?id=<?php echo $prod['product_id']; ?>" class="product-view-btn"><i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center mt-5">
            <a href="products.php" class="btn-modern btn-outline-modern">View All Products <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section-v2 bg-soft">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-badge">HOW IT WORKS</span>
            <h2 class="section-heading">Get started in three steps</h2>
        </div>
        <div class="row g-4 stagger-children animate-on-scroll">
            <div class="col-md-4">
                <div class="step-v2">
                    <div class="step-num">1</div>
                    <h5>Create an account</h5>
                    <p>Sign up as a customer, shopkeeper, or workshop owner in seconds. Start exploring our marketplace.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-v2">
                    <div class="step-num">2</div>
                    <h5>Find your part</h5>
                    <p>Use powerful search to find exactly which vehicle you need for your car.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-v2">
                    <div class="step-num">3</div>
                    <h5>Order &amp; track</h5>
                    <p>Place your order, and track your delivery all the way to your doorstep.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Join As Section -->
<section class="section-v2">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-badge">JOIN AS</span>
            <h2 class="section-heading">Choose your role</h2>
        </div>
        <div class="row g-4 stagger-children animate-on-scroll">
            <div class="col-md-4">
                <div class="role-v2 role-customer">
                    <span class="role-badge">FOR CUSTOMERS</span>
                    <h4>Shop as a customer</h4>
                    <p>Browse thousands of auto parts from trusted sellers. Compare prices and get the best deals.</p>
                    <ul class="role-features-v2">
                        <li><i class="fas fa-check"></i> Access to thousands of parts</li>
                        <li><i class="fas fa-check"></i> Price comparison tools</li>
                        <li><i class="fas fa-check"></i> Order tracking</li>
                    </ul>
                    <a href="register.php" class="btn-role-action">Customer Register</a>
                    <a href="login.php?role=customer" class="role-link">Already have an account? Login</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="role-v2 role-shopkeeper">
                    <span class="role-badge">FOR SELLERS</span>
                    <h4>Sell as a shopkeeper</h4>
                    <p>List your inventory, reach more customers, and grow your auto parts business online.</p>
                    <ul class="role-features-v2">
                        <li><i class="fas fa-check"></i> Reach more customers</li>
                        <li><i class="fas fa-check"></i> Inventory management</li>
                        <li><i class="fas fa-check"></i> Sales analytics</li>
                    </ul>
                    <a href="register-shopkeeper.php" class="btn-role-action">Apply to Sell</a>
                    <a href="login.php?role=shopkeeper" class="role-link">Already have an account? Login</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="role-v2 role-workshop">
                    <span class="role-badge">FOR WORKSHOPS</span>
                    <h4>Register your workshop</h4>
                    <p>Connect with customers needing repairs. Manage bookings and grow your workshop business.</p>
                    <ul class="role-features-v2">
                        <li><i class="fas fa-check"></i> Online appointment booking</li>
                        <li><i class="fas fa-check"></i> Customer management</li>
                        <li><i class="fas fa-check"></i> Service showcase</li>
                    </ul>
                    <a href="register-workshop.php" class="btn-role-action">Register Workshop</a>
                    <a href="login.php?role=workshop" class="role-link">Already have an account? Login</a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.animate-on-scroll, .animate-on-scroll-left, .animate-on-scroll-right, .animate-on-scroll-scale, .stagger-children').forEach(el => observer.observe(el));
});
</script>
<script src="js/wishlist.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
