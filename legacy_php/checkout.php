<?php
$page_title = 'Checkout';
require_once __DIR__ . '/includes/config.php';
requireLogin();
if ($current_user['role'] !== 'customer') redirect(SITE_URL);

$uid = $_SESSION['user_id'];
$cartItems = [];
$stmt = $conn->prepare("SELECT c.*, p.product_name, p.price, p.discount_price, p.stock, p.shop_id FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$q = $stmt->get_result();
while ($row = $q->fetch_assoc()) $cartItems[] = $row;
$stmt->close();

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty.');
    redirect(SITE_URL . '/cart.php');
}

$subtotal = 0;
foreach ($cartItems as &$item) {
    $item['_use_price'] = ($item['discount_price'] && $item['discount_price'] > 0 && $item['discount_price'] < $item['price']) ? $item['discount_price'] : $item['price'];
    $subtotal += $item['_use_price'] * $item['quantity'];
}
unset($item);
$taxRate = floatval(getSystemSetting('tax_rate', '8.5')) / 100;
$shippingFee = floatval(getSystemSetting('shipping_fee', '5.99'));
$tax = $subtotal * $taxRate;
$shipping = $shippingFee;
$total = $subtotal + $tax + $shipping;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $address = sanitizeSQL($_POST['address']);
    $phone = sanitizeSQL($_POST['phone']);
    $payment = sanitizeSQL($_POST['payment_method']);
    $notes = sanitizeSQL($_POST['notes'] ?? '');

    $valid_payments = ['cod'];
    if (!in_array($payment, $valid_payments)) $payment = 'cod';

    $out_of_stock = [];
    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock']) {
            $out_of_stock[] = htmlspecialchars($item['product_name']) . ' (available: ' . $item['stock'] . ')';
        }
    }
    if (!empty($out_of_stock)) {
        setFlash('danger', 'Insufficient stock for: ' . implode(', ', $out_of_stock));
        redirect(SITE_URL . '/checkout.php');
    }

    $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, shipping_address, contact_phone, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssss", $uid, $total, $address, $phone, $payment, $notes);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    foreach ($cartItems as $item) {
        $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $ins->bind_param("iidd", $order_id, $item['product_id'], $item['quantity'], $item['_use_price']);
        $ins->execute();
        $ins->close();

        $upd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
        $upd->bind_param("ii", $item['quantity'], $item['product_id']);
        $upd->execute();
        $upd->close();
    }

    $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $del->bind_param("i", $uid);
    $del->execute();
    $del->close();

    if ($payment === 'cod') {
        setFlash('success', 'Order #' . $order_id . ' placed successfully! Pay on delivery.');
    } else {
        setFlash('success', 'Order #' . $order_id . ' placed! Payment gateway integration coming soon. Treating as COD for now.');
    }
    redirect(SITE_URL . '/customer/orders.php');
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
.ck-page{background:#f5f7fb;min-height:100vh;padding:30px 0 60px}
.ck-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:28px}
.ck-header-left{display:flex;align-items:center;gap:12px}
.ck-header-icon{width:44px;height:44px;background:linear-gradient(135deg,#dc3545,#ff6b6b);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;box-shadow:0 4px 15px rgba(220,53,69,0.3)}
.ck-header h2{font-size:1.6rem;font-weight:800;color:#1a1a2e;margin:0}
.ck-grid{display:grid;grid-template-columns:1.1fr 0.9fr;gap:28px;align-items:start}

/* Form Card */
.ck-form-card{background:#fff;border-radius:18px;border:1px solid #eee;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.04)}
.ck-form-section{padding:28px 30px;border-bottom:1px solid #f0f0f0}
.ck-form-section:last-child{border-bottom:none}
.ck-section-title{display:flex;align-items:center;gap:10px;font-size:1rem;font-weight:700;color:#1a1a2e;margin-bottom:20px}
.ck-section-title i{color:#dc3545;font-size:0.9rem}
.ck-field{margin-bottom:18px}
.ck-field:last-child{margin-bottom:0}
.ck-field label{display:block;font-size:0.8rem;font-weight:600;color:#555;margin-bottom:6px;letter-spacing:0.3px}
.ck-field input,.ck-field textarea,.ck-field select{width:100%;padding:12px 16px;border:1.5px solid #e8eaed;border-radius:10px;font-size:0.9rem;font-family:inherit;transition:all 0.2s ease;background:#fafbfc;outline:none}
.ck-field input:focus,.ck-field textarea:focus{border-color:#dc3545;box-shadow:0 0 0 4px rgba(220,53,69,0.08);background:#fff}
.ck-field input:disabled{background:#f5f5f5;color:#888;cursor:not-allowed}
.ck-field textarea{resize:vertical;min-height:80px}
.ck-required{color:#dc3545}

/* Payment Options */
.ck-payment-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.ck-pay-option{position:relative}
.ck-pay-option input{position:absolute;opacity:0;width:0;height:0}
.ck-pay-label{display:flex;align-items:center;gap:10px;padding:14px 16px;border:1.5px solid #e8eaed;border-radius:12px;cursor:pointer;transition:all 0.2s ease;background:#fafbfc}
.ck-pay-label:hover{border-color:#ccc;background:#fff}
.ck-pay-option input:checked + .ck-pay-label{border-color:#dc3545;background:rgba(220,53,69,0.04)}
.ck-pay-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.85rem;flex-shrink:0}
.ck-pay-cod .ck-pay-icon{background:#e8f5e9;color:#2e7d32}
.ck-pay-card .ck-pay-icon{background:#e3f2fd;color:#1565c0}
.ck-pay-upi .ck-pay-icon{background:#f3e5f5;color:#7b1fa2}
.ck-pay-net .ck-pay-icon{background:#fff3e0;color:#e65100}
.ck-pay-option input:checked + .ck-pay-label .ck-pay-icon{box-shadow:0 2px 8px rgba(220,53,69,0.2)}
.ck-pay-text{font-size:0.85rem;font-weight:600;color:#333}
.ck-pay-sub{font-size:0.7rem;color:#999;font-weight:400}

/* Submit Button */
.ck-submit{width:100%;padding:16px;background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:none;border-radius:14px;font-size:1rem;font-weight:700;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 6px 20px rgba(220,53,69,0.3);font-family:inherit;margin-top:8px}
.ck-submit:hover{background:linear-gradient(135deg,#ff4757,#dc3545);transform:translateY(-2px);box-shadow:0 10px 30px rgba(220,53,69,0.4)}
.ck-submit:active{transform:translateY(0)}

/* Order Summary */
.ck-summary{background:#fff;border-radius:18px;border:1px solid #eee;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.04);position:sticky;top:90px}
.ck-summary-header{padding:22px 26px;background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;display:flex;align-items:center;gap:10px}
.ck-summary-header i{font-size:1rem}
.ck-summary-header h5{margin:0;font-size:1rem;font-weight:700}
.ck-summary-body{padding:22px 26px}
.ck-product-item{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #f5f5f5}
.ck-product-item:last-child{border-bottom:none}
.ck-product-name{font-size:0.88rem;color:#333;font-weight:500}
.ck-product-qty{font-size:0.75rem;color:#999}
.ck-product-price{font-size:0.88rem;font-weight:700;color:#1a1a2e}
.ck-summary-row{display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem;color:#666}
.ck-summary-row span:last-child{font-weight:600;color:#333}
.ck-summary-divider{border:none;border-top:1px dashed #e8eaed;margin:12px 0}
.ck-summary-total{display:flex;justify-content:space-between;padding:14px 0 0;font-size:1.05rem;font-weight:800;color:#1a1a2e}
.ck-summary-total span:last-child{color:#dc3545;font-size:1.15rem}
.ck-trust{display:flex;flex-direction:column;gap:10px;padding:18px 26px;border-top:1px solid #f0f0f0;background:#fafbfc}
.ck-trust-item{display:flex;align-items:center;gap:8px;font-size:0.78rem;color:#888}
.ck-trust-item i{color:#dc3545;font-size:0.72rem;width:16px;text-align:center}

@media(max-width:768px){
    .ck-grid{grid-template-columns:1fr}
    .ck-summary{position:static}
    .ck-payment-grid{grid-template-columns:1fr}
    .ck-form-section{padding:22px 20px}
    .ck-summary-body{padding:18px 20px}
}
</style>

<section class="ck-page">
    <div class="container">
        <div class="ck-header">
            <div class="ck-header-left">
                <div class="ck-header-icon"><i class="fas fa-lock"></i></div>
                <h2>Secure Checkout</h2>
            </div>
            <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-outline-secondary" style="border-radius:10px;font-weight:600;font-size:0.88rem;padding:10px 20px;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
        </div>
        <div class="ck-grid">
            <div class="ck-form-card">
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <div class="ck-form-section">
                        <div class="ck-section-title"><i class="fas fa-user"></i> Personal Information</div>
                        <div class="ck-field">
                            <label>Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($current_user['name']); ?>" disabled>
                        </div>
                        <div class="ck-field">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled>
                        </div>
                    </div>
                    <div class="ck-form-section">
                        <div class="ck-section-title"><i class="fas fa-map-marker-alt"></i> Delivery Details</div>
                        <div class="ck-field">
                            <label>Delivery Address <span class="ck-required">*</span></label>
                            <textarea name="address" required placeholder="Street address, city, area..."><?php echo htmlspecialchars($current_user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="ck-field">
                            <label>Contact Phone <span class="ck-required">*</span></label>
                            <input type="text" name="phone" required value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" placeholder="Your phone number">
                        </div>
                    </div>
                    <div class="ck-form-section">
                        <div class="ck-section-title"><i class="fas fa-credit-card"></i> Payment Method</div>
                        <div class="ck-field">
                            <div class="ck-pay-option ck-pay-cod" style="max-width:320px">
                                <input type="radio" name="payment_method" value="cod" id="pay_cod" checked>
                                <label class="ck-pay-label" for="pay_cod">
                                    <span class="ck-pay-icon"><i class="fas fa-money-bill-wave"></i></span>
                                    <span><span class="ck-pay-text">Cash on Delivery</span><br><span class="ck-pay-sub">Pay when you receive</span></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="ck-form-section">
                        <div class="ck-section-title"><i class="fas fa-sticky-note"></i> Order Notes</div>
                        <div class="ck-field">
                            <textarea name="notes" rows="2" placeholder="Any special delivery instructions..."></textarea>
                        </div>
                        <button type="submit" class="ck-submit">
                            <i class="fas fa-lock"></i>
                            <span>Place Order &mdash; <?php echo formatCurrency($total); ?></span>
                        </button>
                    </div>
                </form>
            </div>
            <div>
                <div class="ck-summary">
                    <div class="ck-summary-header">
                        <i class="fas fa-shopping-bag"></i>
                        <h5>Order Summary</h5>
                    </div>
                    <div class="ck-summary-body">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="ck-product-item">
                            <div>
                                <div class="ck-product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="ck-product-qty">Qty: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="ck-product-price"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></div>
                        </div>
                        <?php endforeach; ?>
                        <hr class="ck-summary-divider">
                        <div class="ck-summary-row"><span>Subtotal</span><span><?php echo formatCurrency($subtotal); ?></span></div>
                        <div class="ck-summary-row"><span>Tax (<?php echo floatval(getSystemSetting('tax_rate', '8.5')); ?>%)</span><span><?php echo formatCurrency($tax); ?></span></div>
                        <div class="ck-summary-row"><span>Delivery</span><span><?php echo $shipping > 0 ? formatCurrency($shipping) : 'Free'; ?></span></div>
                        <hr class="ck-summary-divider">
                        <div class="ck-summary-total"><span>Total</span><span><?php echo formatCurrency($total); ?></span></div>
                    </div>
                    <div class="ck-trust">
                        <div class="ck-trust-item"><i class="fas fa-shield-alt"></i> Secure 256-bit SSL encryption</div>
                        <div class="ck-trust-item"><i class="fas fa-undo"></i> 7-day easy return policy</div>
                        <div class="ck-trust-item"><i class="fas fa-truck"></i> Fast delivery across Pakistan</div>
                        <div class="ck-trust-item"><i class="fas fa-headset"></i> 24/7 customer support</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
