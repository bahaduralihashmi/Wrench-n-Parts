<?php
$page_title = 'Shopkeeper Dashboard';
require_once __DIR__ . '/../includes/config.php';
requireRole('shopkeeper');

if ($current_user['status'] === 'pending') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pending Approval - Wrench n Parts</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Inter', -apple-system, sans-serif; background: #f5f7fb; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .pending-card { background: #fff; border-radius: 20px; padding: 50px 40px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
            .pending-icon { width: 90px; height: 90px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2.2rem; color: #fff; }
            .pending-card h1 { font-size: 1.5rem; color: #1a1a2e; margin-bottom: 12px; }
            .pending-card p { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px; }
            .pending-card .email { color: #dc3545; font-weight: 600; }
            .pending-steps { text-align: left; background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 28px; }
            .pending-steps h3 { font-size: 0.9rem; color: #333; margin-bottom: 12px; }
            .pending-steps li { color: #555; font-size: 0.85rem; margin-bottom: 8px; list-style: none; display: flex; align-items: center; gap: 8px; }
            .pending-steps li i { color: #27ae60; font-size: 0.8rem; }
            .pending-btn { display: inline-block; padding: 12px 32px; background: #dc3545; color: #fff; border: none; border-radius: 50px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .3s; }
            .pending-btn:hover { background: #c82333; }
        </style>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/responsive.css">
    </head>
    <body>
        <div class="pending-card">
            <div class="pending-icon"><i class="fas fa-clock"></i></div>
            <h1>Account Pending Approval</h1>
            <p>Thank you for registering as a Shopkeeper! Your account (<span class="email"><?php echo htmlspecialchars($current_user['email']); ?></span>) is currently under review by our admin team.</p>
            <div class="pending-steps">
                <h3><i class="fas fa-info-circle"></i> What happens next?</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Our team will review your application</li>
                    <li><i class="fas fa-check-circle"></i> We'll verify your shop details</li>
                    <li><i class="fas fa-check-circle"></i> You'll receive approval within 24-48 hours</li>
                    <li><i class="fas fa-check-circle"></i> Once approved, you can login and manage your shop</li>
                </ul>
            </div>
            <a href="<?php echo SITE_URL; ?>/login.php" class="pending-btn"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$shop = null;
$stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shop) {
    setFlash('warning', 'Please set up your shop first.');
}

$shop_id = $shop ? $shop['shop_id'] : 0;

$total_products = 0;
$total_orders = 0;
$revenue = 0;
$low_stock = 0;

if ($shop_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE shop_id = ?");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $total_products = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT oi.order_id) as cnt FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE p.shop_id = ?");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $total_orders = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi JOIN products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id WHERE p.shop_id = ? AND o.order_status IN ('delivered','shipped','confirmed')");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $revenue = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE shop_id = ? AND stock < 5 AND status = 'available'");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $low_stock = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
}

$recent_orders = [];
if ($shop_id) {
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_status, o.total_amount, o.created_at, u.name as customer_name,
               GROUP_CONCAT(CONCAT(p.product_name, ' x', oi.quantity) SEPARATOR ', ') as items,
               SUM(oi.price * oi.quantity) as shop_total
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        JOIN users u ON o.customer_id = u.user_id
        WHERE p.shop_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.sk-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 16px 60px;
}

/* ── Welcome Banner ── */
.sk-welcome {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 18px;
    padding: 36px 40px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
}
.sk-welcome::before {
    content: '';
    position: absolute;
    width: 260px; height: 260px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    top: -100px; right: -60px;
}
.sk-welcome::after {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
    bottom: -80px; left: 40%;
}
.sk-welcome h2 {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 6px;
}
.sk-welcome p {
    opacity: 0.8;
    margin: 0;
    font-size: 0.95rem;
}
.sk-welcome-date {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 14px 22px;
    text-align: center;
    z-index: 1;
}
.sk-welcome-date .day { font-size: 1.5rem; font-weight: 700; }
.sk-welcome-date .full { font-size: 0.82rem; opacity: 0.75; margin-top: 2px; }

/* ── Stats Cards ── */
.sk-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}
.sk-stat {
    border-radius: 16px;
    padding: 26px 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.sk-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.15);
}
.sk-stat .sk-stat-icon {
    width: 50px; height: 50px;
    border-radius: 14px;
    background: rgba(255,255,255,0.22);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 16px;
}
.sk-stat .sk-stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 4px;
    font-weight: 500;
}
.sk-stat .sk-stat-value {
    font-size: 1.85rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}
.sk-stat-red    { background: linear-gradient(135deg, #e74c3c, #c0392b); }
.sk-stat-green  { background: linear-gradient(135deg, #27ae60, #1e8449); }
.sk-stat-blue   { background: linear-gradient(135deg, #3498db, #2471a3); }
.sk-stat-orange { background: linear-gradient(135deg, #f39c12, #d68910); }

.sk-stat::after {
    content: '';
    position: absolute;
    width: 120px; height: 120px;
    background: rgba(255,255,255,0.07);
    border-radius: 50%;
    bottom: -30px; right: -20px;
}

/* ── Section Cards ── */
.sk-section {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-light, #eee);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 28px;
    transition: box-shadow 0.2s;
}
.sk-section:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.sk-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-light, #f0f0f0);
}
.sk-section-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sk-section-header h5 i {
    color: var(--accent, #3498db);
}
.sk-section-header a {
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
}

/* ── Quick Actions ── */
.sk-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.sk-action {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 22px;
    border-radius: 14px;
    border: 1px solid var(--border-light, #eee);
    background: var(--card-bg, #fff);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}
.sk-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: transparent;
}
.sk-action .sk-action-icon {
    width: 48px; height: 48px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.sk-action .sk-action-text h6 {
    margin: 0;
    font-weight: 700;
    font-size: 0.95rem;
}
.sk-action .sk-action-text small {
    color: #888;
    font-size: 0.78rem;
}
.sk-action-icon.red    { background: #fdeaea; color: #e74c3c; }
.sk-action-icon.green  { background: #e8f8f0; color: #27ae60; }
.sk-action-icon.blue   { background: #e8f0fd; color: #3498db; }

/* ── Table ── */
.sk-table-wrap {
    overflow-x: auto;
}
.sk-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.9rem;
}
.sk-table thead th {
    background: var(--bg-secondary, #f8f9fa);
    padding: 12px 16px;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #666;
    border-bottom: 2px solid var(--border-light, #eee);
}
.sk-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-light, #f0f0f0);
    vertical-align: middle;
}
.sk-table tbody tr {
    transition: background 0.15s;
}
.sk-table tbody tr:hover {
    background: var(--bg-secondary, #f8f9fa);
}
.sk-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}
.sk-badge-delivered  { background: #e8f8f0; color: #1e8449; }
.sk-badge-processing { background: #e8f0fd; color: #2471a3; }
.sk-badge-pending    { background: #fef9e7; color: #b7950b; }
.sk-badge-cancelled  { background: #fdeaea; color: #c0392b; }

/* ── Responsive ── */
@media (max-width: 992px) {
    .sk-stats { grid-template-columns: repeat(2, 1fr); }
    .sk-actions { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .sk-welcome { flex-direction: column; text-align: center; gap: 20px; padding: 28px 20px; }
    .sk-stats { grid-template-columns: 1fr; }
    .sk-section { padding: 20px 16px; }
    .sk-dashboard { padding: 16px 10px 40px; }
    .sk-welcome h2 { font-size: 1.3rem; }
    .sk-stat .sk-stat-value { font-size: 1.5rem; }
}
</style>

<div class="sk-dashboard">

    <!-- Welcome Banner -->
    <div class="sk-welcome">
        <div>
            <h2>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</h2>
            <p><i class="fas fa-store me-1"></i><?php echo $shop ? htmlspecialchars($shop['shop_name']) : 'No shop configured'; ?> &mdash; here's your dashboard overview.</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:center;">
            <div class="sk-welcome-date">
                <div class="day"><?php echo date('d'); ?></div>
                <div class="full"><?php echo date('F Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="sk-stats">
        <div class="sk-stat sk-stat-red">
            <div class="sk-stat-icon"><i class="fas fa-boxes-stacked"></i></div>
            <div class="sk-stat-label">Total Products</div>
            <div class="sk-stat-value"><?php echo $total_products; ?></div>
        </div>
        <div class="sk-stat sk-stat-green">
            <div class="sk-stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="sk-stat-label">Total Orders</div>
            <div class="sk-stat-value"><?php echo $total_orders; ?></div>
        </div>
        <div class="sk-stat sk-stat-blue">
            <div class="sk-stat-icon"><i class="fas fa-indian-rupee-sign"></i></div>
            <div class="sk-stat-label">Revenue</div>
            <div class="sk-stat-value"><?php echo formatCurrency($revenue); ?></div>
        </div>
        <div class="sk-stat sk-stat-orange">
            <div class="sk-stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="sk-stat-label">Low Stock Items</div>
            <div class="sk-stat-value"><?php echo $low_stock; ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="sk-actions">
        <a href="products.php?action=add" class="sk-action">
            <div class="sk-action-icon red"><i class="fas fa-plus-circle"></i></div>
            <div class="sk-action-text">
                <h6>Add Product</h6>
                <small>List a new product in your shop</small>
            </div>
        </a>
        <a href="inventory.php" class="sk-action">
            <div class="sk-action-icon green"><i class="fas fa-warehouse"></i></div>
            <div class="sk-action-text">
                <h6>View Inventory</h6>
                <small>Check stock levels &amp; manage items</small>
            </div>
        </a>
        <a href="orders.php" class="sk-action">
            <div class="sk-action-icon blue"><i class="fas fa-clipboard-list"></i></div>
            <div class="sk-action-text">
                <h6>Manage Orders</h6>
                <small>Process &amp; fulfill customer orders</small>
            </div>
        </a>
    </div>

    <!-- Recent Orders -->
    <div class="sk-section">
        <div class="sk-section-header">
            <h5><i class="fas fa-clock-rotate-left"></i>Recent Orders</h5>
            <a href="orders.php">View All <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <?php if (empty($recent_orders)): ?>
            <p class="text-muted text-center py-4" style="margin:0;">No orders yet.</p>
        <?php else: ?>
            <div class="sk-table-wrap">
                <table class="sk-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($order['items']); ?></small></td>
                                <td><strong><?php echo formatCurrency($order['shop_total']); ?></strong></td>
                                <td>
                                    <span class="sk-badge sk-badge-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>


</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>