<?php
$page_title = 'My Orders';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

if (isset($_POST['cancel_order'])) {
    verifyCsrf();
    $order_id = intval($_POST['order_id']);
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ? AND customer_id = ? AND order_status = 'pending'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        setFlash('success', 'Order #' . $order_id . ' has been cancelled.');
    } else {
        setFlash('danger', 'Could not cancel this order.');
    }
    $stmt->close();
    redirect(SITE_URL . '/customer/orders.php');
}

$orders = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$orders->bind_param("i", $user_id);
$orders->execute();
$orders_result = $orders->get_result();

$order_items = null;
$order_detail = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $detail_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?");
    $detail_stmt->bind_param("ii", $view_id, $user_id);
    $detail_stmt->execute();
    $order_detail = $detail_stmt->get_result()->fetch_assoc();
    $detail_stmt->close();

    if ($order_detail) {
        $items_stmt = $conn->prepare("SELECT oi.*, p.product_name, p.product_image, p.shop_id, s.shop_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE oi.order_id = ?");
        $items_stmt->bind_param("i", $view_id);
        $items_stmt->execute();
        $order_items = $items_stmt->get_result();
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if ($order_detail): ?>
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="cust-welcome-banner">
            <div class="cust-welcome-left">
                <a href="<?php echo SITE_URL; ?>/customer/orders.php" style="color:#dc3545;text-decoration:none;font-size:0.85rem;font-weight:600;"><i class="fas fa-arrow-left me-1"></i>Back to Orders</a>
                <h1 class="cust-welcome-title" style="margin-top:8px;">Order #<?php echo $order_detail['order_id']; ?></h1>
                <p class="cust-welcome-desc">Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order_detail['created_at'])); ?></p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
            <div class="cust-empty-state" style="padding:20px;">
                <div class="cust-empty-title" style="margin-bottom:4px;">Order Status</div>
                <span class="dash-badge dash-badge-<?php echo $order_detail['order_status'] === 'delivered' ? 'green' : ($order_detail['order_status'] === 'cancelled' ? 'red' : 'blue'); ?>"><?php echo ucfirst($order_detail['order_status']); ?></span>
            </div>
            <div class="cust-empty-state" style="padding:20px;">
                <div class="cust-empty-title" style="margin-bottom:4px;">Payment Status</div>
                <span class="dash-badge dash-badge-<?php echo $order_detail['payment_status'] === 'paid' ? 'green' : 'orange'; ?>"><?php echo ucfirst($order_detail['payment_status']); ?></span>
            </div>
            <div class="cust-empty-state" style="padding:20px;">
                <div class="cust-empty-title" style="margin-bottom:4px;">Total Amount</div>
                <div style="font-size:1.3rem;font-weight:800;color:#dc3545;"><?php echo formatCurrency($order_detail['total_amount']); ?></div>
            </div>
        </div>

        <div class="cust-section">
            <div class="cust-section-header">
                <h2 class="cust-section-title">Order Items</h2>
            </div>
            <div class="cust-empty-state">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($order_items && $order_items->num_rows > 0): ?>
                                <?php while ($item = $order_items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <?php if (!empty($item['product_image'])): ?>
                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $item['product_image']; ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                                        <i class="fas fa-cog text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']); ?></strong>
                                            <?php if (!empty($item['shop_id'])): ?><br><a href="<?php echo SITE_URL; ?>/customer/shop-profile.php?id=<?php echo $item['shop_id']; ?>" style="font-size:.75rem;color:#667eea;text-decoration:none;"><i class="fas fa-store" style="margin-right:3px;"></i><?php echo htmlspecialchars($item['shop_name'] ?? 'Shop'); ?></a><?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo formatCurrency($item['price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="cust-welcome-banner">
            <div class="cust-welcome-left">
                <h1 class="cust-welcome-title">My Orders</h1>
                <p class="cust-welcome-desc">View and manage all your orders</p>
            </div>
        </div>

        <div class="cust-section">
            <div class="cust-empty-state">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders_result->num_rows > 0): ?>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                    <td><span class="dash-badge dash-badge-<?php echo $order['payment_status'] === 'paid' ? 'green' : 'orange'; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                    <td><span class="dash-badge dash-badge-<?php echo $order['order_status'] === 'delivered' ? 'green' : ($order['order_status'] === 'cancelled' ? 'red' : 'blue'); ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                    <td>
                                        <a href="?view=<?php echo $order['order_id']; ?>" class="dash-btn-action dash-btn-outline btn-sm-modern" title="View Details" style="padding:6px 12px;font-size:0.78rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($order['order_status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel order #<?php echo $order['order_id']; ?>?')">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" name="cancel_order" class="dash-btn-action dash-btn-outline btn-sm-modern" style="padding:6px 12px;font-size:0.78rem;border-color:#dc3545;color:#dc3545;" title="Cancel Order">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="cust-empty-icon" style="margin:0 auto 16px;"><i class="fas fa-inbox"></i></div>
                                        <h5 class="cust-empty-title">No orders found</h5>
                                        <p class="cust-empty-desc">Start shopping to see your orders here</p>
                                        <a href="<?php echo SITE_URL; ?>/products.php" class="cust-btn-workshop"><i class="fas fa-shopping-bag me-2"></i>Browse Products</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$orders->close();
require_once __DIR__ . '/footer.php';
?>