<?php
$page_title = 'My Wishlist';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $pid = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $pid);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Product removed from wishlist.');
    redirect(SITE_URL . '/customer/wishlist.php');
}

if (isset($_GET['add_cart']) && is_numeric($_GET['add_cart'])) {
    $pid = intval($_GET['add_cart']);
    $check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $pid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $pid");
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $ins->bind_param("ii", $user_id, $pid);
        $ins->execute();
        $ins->close();
    }
    $check->close();
    setFlash('success', 'Product added to cart!');
    redirect(SITE_URL . '/customer/wishlist.php');
}

$wishlist = $conn->prepare("SELECT w.*, p.product_name, p.price, p.product_image, p.stock, p.brand, p.discount_price, s.shop_name FROM wishlist w LEFT JOIN products p ON w.product_id = p.product_id LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE w.user_id = ? ORDER BY w.created_at DESC");
$wishlist->bind_param("i", $user_id);
$wishlist->execute();
$wishlist_result = $wishlist->get_result();

require_once __DIR__ . '/header.php';
?>

<div class="container-fluid px-4 py-4">
    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="cust-welcome-banner">
        <div class="cust-welcome-left">
            <h1 class="cust-welcome-title">My Wishlist</h1>
            <p class="cust-welcome-desc">Products you've saved for later</p>
        </div>
        <div class="cust-welcome-actions">
            <a href="<?php echo SITE_URL; ?>/products.php" class="cust-btn-workshop"><i class="fas fa-shopping-bag me-1"></i>Browse Products</a>
        </div>
    </div>

    <?php if ($wishlist_result->num_rows > 0): ?>
        <div class="cust-section">
            <div class="cust-products-grid">
                <?php while ($item = $wishlist_result->fetch_assoc()): ?>
                    <div class="cust-product-card" style="position:relative;">
                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $item['product_id']; ?>" style="text-decoration:none;color:inherit;">
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($item['product_image']) ? $item['product_image'] : 'no-image.png'; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="cust-product-img">
                            <div class="cust-product-body">
                                <div class="cust-product-shop"><?php echo htmlspecialchars($item['brand'] ?? 'General'); ?> | <?php echo htmlspecialchars($item['shop_name'] ?? ''); ?></div>
                                <div class="cust-product-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></div>
                                <div>
                                    <?php if ($item['discount_price'] && $item['discount_price'] < $item['price']): ?>
                                        <span class="cust-product-price"><?php echo formatCurrency($item['discount_price']); ?></span>
                                        <span class="cust-product-old-price"><?php echo formatCurrency($item['price']); ?></span>
                                    <?php else: ?>
                                        <span class="cust-product-price"><?php echo formatCurrency($item['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <div style="padding:0 14px 14px;display:flex;gap:8px;">
                            <?php if ($item['stock'] > 0): ?>
                                <a href="?add_cart=<?php echo $item['product_id']; ?>" class="cust-btn-workshop" style="flex:1;justify-content:center;padding:8px 12px;font-size:0.8rem;text-decoration:none;">
                                    <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                </a>
                            <?php endif; ?>
                            <a href="?remove=<?php echo $item['product_id']; ?>" style="width:36px;height:36px;border:1.5px solid #dc3545;color:#dc3545;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:0.85rem;flex-shrink:0;" onclick="return confirm('Remove this item from your wishlist?')" title="Remove">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="cust-section">
            <div class="cust-empty-state">
                <div class="cust-empty-icon"><i class="fas fa-heart-broken"></i></div>
                <h3 class="cust-empty-title">Your wishlist is empty</h3>
                <p class="cust-empty-desc">Browse products and save your favorites</p>
                <a href="<?php echo SITE_URL; ?>/products.php" class="cust-btn-workshop"><i class="fas fa-shopping-bag me-2"></i>Browse Products</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?php echo SITE_URL; ?>/js/wishlist.js"></script>
<?php
$wishlist->close();
require_once __DIR__ . '/footer.php';
?>