<?php
$page_title = 'Manage Orders';
require_once __DIR__ . '/../includes/config.php';
requireRole('shopkeeper');

$shop = null;
$stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shop) {
    setFlash('warning', 'Please set up your shop first.');
    redirect(SITE_URL . '/shopkeeper/profile.php');
}

$shop_id = $shop['shop_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verifyCsrf();
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitizeSQL($_POST['order_status']);
    $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $check = $conn->prepare("SELECT o.order_id FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN products p ON oi.product_id = p.product_id WHERE o.order_id = ? AND p.shop_id = ?");
        $check->bind_param("ii", $order_id, $shop_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $status_msg = ucfirst($new_status);
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                $title = "Order #$order_id Updated";
                $message = "Your order #$order_id has been " . strtolower($status_msg) . ".";
                $link = SITE_URL . "/customer/orders.php";
                $stmt->bind_param("isss", $row['customer_id'], $title, $message, $link);
                $stmt->execute();
                $stmt->close();
            }

            setFlash('success', "Order #$order_id status updated to $new_status.");
        } else {
            setFlash('danger', 'You are not authorized to update this order.');
        }
        $check->close();
    }
    redirect(SITE_URL . '/shopkeeper/orders.php');
}

$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT o.order_id, o.order_status, o.total_amount, o.payment_method, o.payment_status, o.contact_phone, o.shipping_address, o.created_at,
           u.name as customer_name, u.email as customer_email,
           GROUP_CONCAT(CONCAT(p.product_name, ' x', oi.quantity, ' @ ', FORMAT(oi.price, 2)) SEPARATOR '|||') as items_detail,
           SUM(oi.price * oi.quantity) as shop_total,
           COUNT(oi.item_id) as item_count
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN users u ON o.customer_id = u.user_id
    WHERE p.shop_id = ?
";
$params = [$shop_id];
$types = "i";

if ($filter_status) {
    $sql .= " AND o.order_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " GROUP BY o.order_id ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
$status_next = [
    'pending' => 'confirmed',
    'confirmed' => 'processing',
    'processing' => 'shipped',
    'shipped' => 'delivered'
];

require_once __DIR__ . '/../includes/header.php';
?>

<button class="admin-sidebar-toggle" id="skSidebarToggle" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.toggle('show');document.getElementById('skOverlay').classList.toggle('active')">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="skOverlay" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.remove('show');this.classList.remove('active')"></div>
<div class="dash-layout">
    <div class="dash-sidebar">
        <div class="dash-sidebar-brand">
            <div class="dash-brand-icon">SK</div>
            <div>
                <div class="dash-brand-text">Shopkeeper</div>
                <small style="color:#888;font-size:0.75rem;"><?php echo htmlspecialchars($shop['shop_name']); ?></small>
            </div>
        </div>
        <div class="dash-sidebar-label">Menu</div>
        <nav class="dash-nav">
            <a class="dash-nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a class="dash-nav-link" href="products.php"><i class="fas fa-boxes-stacked"></i>Products</a>
            <a class="dash-nav-link active" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a>
            <a class="dash-nav-link" href="inventory.php"><i class="fas fa-warehouse"></i>Inventory</a>
            <a class="dash-nav-link" href="hot-deals.php"><i class="fas fa-fire"></i>Hot Deals</a>
            <a class="dash-nav-link" href="returns.php"><i class="fas fa-undo-alt"></i>Returns</a>
            <a class="dash-nav-link" href="chat.php"><i class="fas fa-comments"></i>Chat</a>
            <a class="dash-nav-link" href="profile.php"><i class="fas fa-user-cog"></i>Profile</a>
        </nav>
        <div class="dash-sidebar-footer">
            <a class="dash-nav-link logout" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>
    <div class="dash-main">
        <a href="<?php echo SITE_URL; ?>/shopkeeper/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="dash-header">
            <h2 class="fw-bold mb-0"><i class="fas fa-shopping-cart me-2"></i>Orders</h2>
            <div class="dash-header-actions">
                <span class="dash-badge dash-badge-blue"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <div class="dash-card mb-4">
            <div class="dash-card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label fw-bold">Filter by Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo $filter_status === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="dash-btn-action dash-btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="dash-card">
                <div class="dash-card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No orders found</h5>
                    <p class="text-muted">Orders containing your products will appear here.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php $items = explode('|||', $order['items_detail']); ?>
                <div class="dash-card mb-3">
                    <div class="dash-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    Order #<?php echo $order['order_id']; ?>
                                    <span class="dash-badge dash-badge-<?php echo $order['order_status'] === 'delivered' ? 'green' : ($order['order_status'] === 'cancelled' ? 'red' : 'blue'); ?> ms-2"><?php echo ucfirst($order['order_status']); ?></span>
                                </h5>
                                <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="text-end">
                                <h5 class="product-price mb-0"><?php echo formatCurrency($order['shop_total']); ?></h5>
                                <small class="text-muted"><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?></small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Customer</small>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small><br>
                                <small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['contact_phone']); ?></small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Delivery Address</small>
                                <small><?php echo htmlspecialchars($order['shipping_address']); ?></small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Payment</small>
                                <span class="dash-badge dash-badge-gray"><?php echo strtoupper($order['payment_method']); ?></span>
                                <span class="dash-badge dash-badge-<?php echo $order['payment_status'] === 'paid' ? 'green' : 'orange'; ?>"><?php echo ucfirst($order['payment_status']); ?></span>
                            </div>
                        </div>

                        <div class="bg-light rounded p-3 mb-3">
                            <small class="fw-bold text-muted d-block mb-2">Items from your shop:</small>
                            <?php foreach ($items as $item): ?>
                                <div class="d-flex justify-content-between mb-1">
                                    <small><?php echo htmlspecialchars($item); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (isset($status_next[$order['order_status']])): ?>
                            <div class="order-action-row">
                                <form method="POST" class="order-action-form">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="order_status" class="form-select form-select-sm" style="width:auto;">
                                        <?php foreach ($statuses as $st): ?>
                                            <option value="<?php echo $st; ?>" <?php echo $order['order_status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="dash-btn-action dash-btn-primary btn-sm-modern"><i class="fas fa-check me-1"></i>Update</button>
                                </form>
                                <form method="POST" class="order-action-form-next">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <input type="hidden" name="order_status" value="<?php echo $status_next[$order['order_status']]; ?>">
                                    <button type="submit" class="dash-btn-action dash-btn-outline btn-sm-modern">
                                        <i class="fas fa-arrow-right me-1"></i>Mark as <?php echo ucfirst($status_next[$order['order_status']]); ?>
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($order['order_status'] === 'cancelled' || $order['order_status'] === 'delivered'): ?>
                            <div class="text-muted"><small><i class="fas fa-info-circle me-1"></i>Order is <?php echo $order['order_status']; ?>. No further status changes.</small></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
