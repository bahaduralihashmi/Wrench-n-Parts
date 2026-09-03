<?php
$page_title = 'Shop Profile';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$shop_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$shop_id) { redirect(SITE_URL . '/customer/dashboard.php'); }

$shop = $conn->prepare("SELECT s.*, u.name AS owner_name, u.email AS owner_email FROM shops s LEFT JOIN users u ON s.user_id = u.user_id WHERE s.shop_id = ? AND s.status IN ('active','approved')");
$shop->bind_param("i", $shop_id);
$shop->execute();
$shop_data = $shop->get_result()->fetch_assoc();
$shop->close();

if (!$shop_data) { setFlash('danger', 'Shop not found.'); redirect(SITE_URL . '/customer/dashboard.php'); }

$products = $conn->prepare("SELECT * FROM products WHERE shop_id = ? AND status = 'available' ORDER BY created_at DESC LIMIT 12");
$products->bind_param("i", $shop_id);
$products->execute();
$products_result = $products->get_result();

$product_count = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE shop_id = ? AND status = 'available'");
$product_count->bind_param("i", $shop_id);
$product_count->execute();
$total_products = $product_count->get_result()->fetch_assoc()['total'];
$product_count->close();

$order_count = $conn->prepare("SELECT COUNT(DISTINCT o.order_id) as total FROM orders o INNER JOIN order_items oi ON o.order_id = oi.order_id INNER JOIN products p ON oi.product_id = p.product_id WHERE p.shop_id = ?");
$order_count->bind_param("i", $shop_id);
$order_count->execute();
$total_orders = $order_count->get_result()->fetch_assoc()['total'];
$order_count->close();

require_once __DIR__ . '/header.php';
?>

<style>
.shop-profile-hero{background:linear-gradient(135deg,#dc3545 0%,#b71c1c 100%);border-radius:20px;padding:40px;color:#fff;margin-bottom:30px;position:relative;overflow:hidden;display:flex;gap:24px;align-items:center}
.shop-profile-hero::before{content:'';position:absolute;top:-40%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,.12),transparent);border-radius:50%}
.shop-profile-hero::after{content:'';position:absolute;bottom:-30%;left:10%;width:200px;height:200px;background:radial-gradient(circle,rgba(255,255,255,.08),transparent);border-radius:50%}
.shop-avatar{width:100px;height:100px;border-radius:20px;object-fit:cover;border:3px solid rgba(255,255,255,.3);background:rgba(255,255,255,.15);flex-shrink:0}
.shop-hero-info{flex:1;position:relative;z-index:1}
.shop-hero-info h1{font-size:1.6rem;font-weight:800;margin-bottom:4px}
.shop-hero-info .shop-loc{font-size:.88rem;opacity:.9;display:flex;align-items:center;gap:6px;margin-bottom:12px}
.shop-hero-stats{display:flex;gap:20px}
.shop-hero-stat{text-align:center}
.shop-hero-stat h3{font-size:1.3rem;font-weight:800;margin:0}
.shop-hero-stat p{font-size:.72rem;opacity:.8;margin:2px 0 0}
.shop-chat-btn{padding:10px 24px;background:rgba(255,255,255,.2);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.3);border-radius:10px;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.shop-chat-btn:hover{background:rgba(255,255,255,.35);transform:translateY(-2px)}
.shop-section{background:#fff;border-radius:16px;padding:24px;border:1px solid #f0f0f0;box-shadow:0 2px 16px rgba(0,0,0,.04);margin-bottom:24px;transition:all .3s}
[data-theme="dark"] .shop-section{background:#1a1a2e;border-color:#2a2a3e}
.shop-section h2{font-size:1.1rem;font-weight:700;color:#1a1a2e;margin-bottom:18px;display:flex;align-items:center;gap:10px}
[data-theme="dark"] .shop-section h2{color:#e8e8f0}
.shop-section h2 i{width:32px;height:32px;background:linear-gradient(135deg,#dc3545,#b71c1c);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem}
.shop-about{font-size:.9rem;color:#555;line-height:1.7}
[data-theme="dark"] .shop-about{color:#999}
.shop-info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.shop-info-card{background:#f8f9fa;border-radius:12px;padding:16px;text-align:center;transition:all .3s}
[data-theme="dark"] .shop-info-card{background:#0f172a}
.shop-info-card i{font-size:1.3rem;color:#dc3545;margin-bottom:8px}
.shop-info-card h4{font-size:.82rem;font-weight:600;color:#444;margin:0}
[data-theme="dark"] .shop-info-card h4{color:#ccc}
.shop-info-card p{font-size:.78rem;color:#888;margin:4px 0 0}
[data-theme="dark"] .shop-info-card p{color:#999}
.shop-products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.shop-product-card{background:#f8f9fa;border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;transition:all .3s;border:1px solid #f0f0f0}
[data-theme="dark"] .shop-product-card{background:#0f172a;border-color:#2a2a3e}
.shop-product-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.08)}
.shop-product-card img{width:100%;height:140px;object-fit:cover}
.shop-product-card .sp-info{padding:12px}
.shop-product-card .sp-name{font-size:.85rem;font-weight:600;color:#1a1a2e;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
[data-theme="dark"] .shop-product-card .sp-name{color:#e8e8f0}
.shop-product-card .sp-price{font-size:.9rem;font-weight:700;color:#dc3545}
.shop-product-card .sp-old{font-size:.75rem;color:#aaa;text-decoration:line-through;margin-left:6px}
.cnc-gallery{display:flex;gap:12px;flex-wrap:wrap}
.cnc-gallery img{width:120px;height:80px;object-fit:cover;border-radius:10px;border:2px solid #f0f0f0;cursor:pointer;transition:transform .2s}
[data-theme="dark"] .cnc-gallery img{border-color:#2a2a3e}
.cnc-gallery img:hover{transform:scale(1.05)}
@media(max-width:768px){
    .shop-profile-hero{flex-direction:column;text-align:center;padding:28px 20px}
    .shop-hero-stats{justify-content:center}
    .shop-info-grid{grid-template-columns:1fr}
    .shop-products-grid{grid-template-columns:repeat(2,1fr)}
}
</style>

<div class="container-fluid px-4 py-4">
    <div class="shop-profile-hero">
        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($shop_data['logo']) ? $shop_data['logo'] : 'no-image.png'; ?>" class="shop-avatar" alt="">
        <div class="shop-hero-info">
            <h1><?php echo htmlspecialchars($shop_data['shop_name']); ?></h1>
            <div class="shop-loc"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($shop_data['location'] ?? 'Location not set'); ?></div>
            <div class="shop-hero-stats">
                <div class="shop-hero-stat"><h3><?php echo $total_products; ?></h3><p>Products</p></div>
                <div class="shop-hero-stat"><h3><?php echo $total_orders; ?></h3><p>Orders</p></div>
                <div class="shop-hero-stat"><h3><i class="fas fa-star" style="font-size:.9rem;"></i> 4.8</h3><p>Rating</p></div>
            </div>
        </div>
        <a href="<?php echo SITE_URL; ?>/customer/shop-chat.php?shop_id=<?php echo $shop_id; ?>" class="shop-chat-btn"><i class="fas fa-comments"></i> Chat with Shop</a>
    </div>

    <div class="shop-section">
        <h2><i class="fas fa-info-circle"></i> About Shop</h2>
        <div class="shop-about"><?php echo nl2br(htmlspecialchars($shop_data['description'] ?? 'No description available.')); ?></div>
    </div>

    <div class="shop-section">
        <h2><i class="fas fa-info-circle"></i> Shop Details</h2>
        <div class="shop-info-grid">
            <div class="shop-info-card"><i class="fas fa-phone"></i><h4>Contact</h4><p><?php echo htmlspecialchars($shop_data['contact'] ?? 'N/A'); ?></p></div>
            <div class="shop-info-card"><i class="fas fa-map-pin"></i><h4>Location</h4><p><?php echo htmlspecialchars($shop_data['location'] ?? 'N/A'); ?></p></div>
            <div class="shop-info-card"><i class="fas fa-user"></i><h4>Owner</h4><p><?php echo htmlspecialchars($shop_data['owner_name'] ?? 'N/A'); ?></p></div>
        </div>
    </div>

    <?php if (!empty($shop_data['cnc_front']) || !empty($shop_data['cnc_back'])): ?>
    <div class="shop-section">
        <h2><i class="fas fa-id-card"></i> Verification Documents</h2>
        <div class="cnc-gallery">
            <?php if (!empty($shop_data['cnc_front'])): ?><img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop_data['cnc_front']); ?>" alt="CNC Front"><?php endif; ?>
            <?php if (!empty($shop_data['cnc_back'])): ?><img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop_data['cnc_back']); ?>" alt="CNC Back"><?php endif; ?>
            <?php if (!empty($shop_data['certificate'])): ?><img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop_data['certificate']); ?>" alt="Certificate"><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="shop-section">
        <h2><i class="fas fa-box-open"></i> Products (<?php echo $total_products; ?>)</h2>
        <?php if ($products_result->num_rows > 0): ?>
            <div class="shop-products-grid">
                <?php while ($p = $products_result->fetch_assoc()): ?>
                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $p['product_id']; ?>" class="shop-product-card">
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($p['product_image']) ? $p['product_image'] : 'no-image.png'; ?>" alt="">
                        <div class="sp-info">
                            <div class="sp-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
                            <div>
                                <span class="sp-price"><?php echo formatCurrency($p['discount_price'] && $p['discount_price'] < $p['price'] ? $p['discount_price'] : $p['price']); ?></span>
                                <?php if ($p['discount_price'] && $p['discount_price'] < $p['price']): ?><span class="sp-old"><?php echo formatCurrency($p['price']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:#aaa;"><i class="fas fa-box-open" style="font-size:2rem;margin-bottom:10px;display:block;"></i>No products listed yet</div>
        <?php endif; ?>
    </div>
</div>

<?php
$products->close();
require_once __DIR__ . '/footer.php';
?>
