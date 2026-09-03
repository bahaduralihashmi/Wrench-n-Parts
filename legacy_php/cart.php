<?php
$page_title = 'Cart';
require_once __DIR__ . '/includes/config.php';
requireLogin();
if ($current_user['role'] !== 'customer') { redirect(SITE_URL); }

if (isset($_GET['add'])) {
    $pid = intval($_GET['add']);
    $uid = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $uid, $pid);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        $upd = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
        $upd->bind_param("ii", $uid, $pid);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
        $ins->bind_param("ii", $uid, $pid);
        $ins->execute();
    }
    $check->close();
    setFlash('success', 'Added to cart!');
    header("Location: " . SITE_URL . "/cart.php");
    exit;
}

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT c.*, p.product_name, p.price, p.discount_price, p.stock, p.product_image, s.shop_name FROM cart c JOIN products p ON c.product_id = p.product_id LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$cart = $stmt->get_result();
$stmt->close();

$taxRate = floatval(getSystemSetting('tax_rate', '8.5')) / 100;
$shippingFee = floatval(getSystemSetting('shipping_fee', '5.99'));

$subtotal = 0;
$cartItems = [];
while ($item = $cart->fetch_assoc()) {
    $usePrice = ($item['discount_price'] && $item['discount_price'] > 0 && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
    $item['_use_price'] = $usePrice;
    $item['_item_total'] = $usePrice * $item['quantity'];
    $subtotal += $item['_item_total'];
    $cartItems[] = $item;
}
$tax = $subtotal * $taxRate;
$shipping = $subtotal > 0 ? $shippingFee : 0;
$total = $subtotal + $tax + $shipping;

require_once __DIR__ . '/includes/header.php';
?>
<div class="container section-v2 page-enter">
    <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h2>
    <?php if (empty($cartItems)): ?>
        <div id="cart-empty" class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Your cart is empty</h5>
            <a href="products.php" class="btn-modern btn-primary-modern mt-2"><i class="fas fa-shopping-bag me-2"></i>Browse Products</a>
        </div>
    <?php else: ?>
    <div id="cart-empty" style="display:none;" class="text-center py-5">
        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">Your cart is empty</h5>
        <a href="products.php" class="btn-modern btn-primary-modern mt-2"><i class="fas fa-shopping-bag me-2"></i>Browse Products</a>
    </div>
    <div id="cart-content">
    <div class="row">
        <div class="col-md-8">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                        <tr data-cart-id="<?php echo $item['cart_id']; ?>"
                            data-price="<?php echo $item['_use_price']; ?>"
                            data-orig-price="<?php echo $item['price']; ?>"
                            data-stock="<?php echo $item['stock']; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-3 bg-light rounded d-flex align-items-center justify-content-center" style="width:60px;height:60px;">
                                        <?php if ($item['product_image']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($item['product_image']); ?>" alt="" style="max-height:50px;object-fit:contain;">
                                        <?php else: ?>
                                            <i class="fas fa-cog text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['shop_name']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($item['_use_price'] < $item['price']): ?>
                                    <span class="product-price"><?php echo formatCurrency($item['_use_price']); ?></span>
                                    <br><small style="text-decoration:line-through;color:#999;"><?php echo formatCurrency($item['price']); ?></small>
                                <?php else: ?>
                                    <?php echo formatCurrency($item['price']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center" style="width:120px;">
                                    <button type="button" class="btn-modern btn-sm-modern btn-outline-modern cart-qty-btn" data-action="decrease"><i class="fas fa-minus"></i></button>
                                    <input type="number" class="form-control form-control-sm cart-qty-input mx-1" style="width:50px;text-align:center;"
                                        data-cart-id="<?php echo $item['cart_id']; ?>"
                                        value="<?php echo $item['quantity']; ?>"
                                        min="1" max="<?php echo $item['stock']; ?>">
                                    <button type="button" class="btn-modern btn-sm-modern btn-outline-modern cart-qty-btn" data-action="increase"><i class="fas fa-plus"></i></button>
                                </div>
                            </td>
                            <td class="fw-bold cart-item-total"><?php echo formatCurrency($item['_item_total']); ?></td>
                            <td><button type="button" class="btn-modern btn-sm-modern btn-outline-modern cart-remove-btn" data-cart-id="<?php echo $item['cart_id']; ?>"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="products.php" class="btn-modern btn-outline-modern"><i class="fas fa-arrow-left me-2"></i>Continue Shopping</a>
        </div>
        <div class="col-md-4">
            <div class="dash-card">
                <div class="dash-card-header"><h5 class="mb-0">Order Summary</h5></div>
                <div class="dash-card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span id="cart-subtotal"><?php echo formatCurrency($subtotal); ?></span></div>
                    <div class="d-flex justify-content-between mb-2"><span id="cart-tax-label">Tax (<?php echo $taxRate * 100; ?>%)</span><span id="cart-tax"><?php echo formatCurrency($tax); ?></span></div>
                    <div class="d-flex justify-content-between mb-3"><span>Shipping</span><span id="cart-shipping"><?php echo formatCurrency($shipping); ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3"><strong>Total</strong><strong class="product-price" id="cart-total"><?php echo formatCurrency($total); ?></strong></div>
                    <a href="checkout.php" class="btn-modern btn-primary-modern w-100"><i class="fas fa-credit-card me-2"></i>Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </div>
    </div>
    <?php endif; ?>
</div>
<script src="<?php echo SITE_URL; ?>/js/cart.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
