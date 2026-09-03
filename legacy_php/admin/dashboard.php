<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$total_products = $conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
$active_workshops = $conn->query("SELECT COUNT(*) as cnt FROM workshops WHERE status = 'active'")->fetch_assoc()['cnt'];
$pending_approvals = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE status = 'pending'")->fetch_assoc()['cnt'];

$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_orders = $conn->query("SELECT o.*, u.name FROM orders o LEFT JOIN users u ON o.customer_id = u.user_id ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<style>
    /* Welcome Banner */
    .welcome-banner {
        background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        border-radius: 20px;
        padding: 40px 44px;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
        color: #fff;
        box-shadow: 0 10px 40px rgba(15,12,41,0.25);
    }
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -80px;
        right: -40px;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(233,69,96,0.3) 0%, transparent 70%);
        border-radius: 50%;
        animation: float1 6s ease-in-out infinite;
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        bottom: -60px;
        left: 15%;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(102,126,234,0.25) 0%, transparent 70%);
        border-radius: 50%;
        animation: float2 8s ease-in-out infinite;
    }
    @keyframes float1 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-20px, 15px); }
    }
    @keyframes float2 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(15px, -10px); }
    }
    .welcome-content { position: relative; z-index: 1; }
    .welcome-badge {
        display: inline-block;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.15);
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 14px;
        color: rgba(255,255,255,0.8);
    }
    .welcome-banner h1 {
        font-size: 28px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }
    .welcome-banner h1 span {
        background: linear-gradient(135deg, #e94560, #ff6b81);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .welcome-banner p {
        font-size: 15px;
        opacity: 0.75;
        margin: 0;
        font-weight: 400;
    }
    .welcome-icon {
        font-size: 64px;
        position: absolute;
        right: 44px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.08;
        z-index: 1;
    }

    /* Section Card */
    .section-card {
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: box-shadow 0.3s;
    }
    .section-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 26px;
        border-bottom: 1px solid #f5f5f5;
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
    }
    .section-header h3 {
        font-size: 17px;
        font-weight: 700;
        margin: 0;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-header .view-all {
        font-size: 13px;
        font-weight: 600;
        color: #e94560;
        text-decoration: none;
        padding: 6px 14px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .section-header .view-all:hover {
        background: rgba(233,69,96,0.06);
    }

    /* Dash Table */
    .dash-table { width: 100%; border-collapse: collapse; }
    .dash-table thead th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #999;
        font-weight: 700;
        padding: 13px 22px;
        background: #fafbfc;
        border-bottom: 1px solid #f0f0f0;
    }
    .dash-table tbody td {
        padding: 15px 22px;
        font-size: 14px;
        color: #333;
        border-bottom: 1px solid #f8f8f8;
        vertical-align: middle;
    }
    .dash-table tbody tr:last-child td { border-bottom: none; }
    .dash-table tbody tr { transition: background 0.15s; }
    .dash-table tbody tr:hover { background: #fafbfc; }

    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 14px;
        color: #fff;
        flex-shrink: 0;
        transition: transform 0.25s;
    }
    .dash-table tbody tr:hover .user-avatar { transform: scale(1.08); }
    .avatar-1 { background: linear-gradient(135deg, #667eea, #764ba2); }
    .avatar-2 { background: linear-gradient(135deg, #f093fb, #f5576c); }
    .avatar-3 { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .avatar-4 { background: linear-gradient(135deg, #43e97b, #38f9d7); }
    .avatar-5 { background: linear-gradient(135deg, #fa709a, #fee140); }

    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .role-admin { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed; }
    .role-management { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
    .role-shopkeeper { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
    .role-workshop { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .role-customer { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #64748b; }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
    }
    .status-active { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
    .status-pending { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .status-inactive { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }

    .order-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
    }
    .order-paid { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
    .order-pending { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .order-failed { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
    .order-refunded { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }

    .empty-state {
        text-align: center;
        padding: 45px 20px;
        color: #ccc;
    }
    .empty-state i { font-size: 40px; margin-bottom: 14px; opacity: 0.25; }

    /* Quick Links */
    .quick-links-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
        gap: 14px;
        padding: 22px 26px;
    }
    .quick-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 18px;
        border-radius: 14px;
        text-decoration: none;
        color: #333;
        background: #fafbfc;
        border: 1px solid #f0f0f0;
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
    }
    .quick-link:hover {
        border-color: #e94560;
        color: #e94560;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(233,69,96,0.1);
    }
    .quick-link .ql-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex-shrink: 0;
        transition: transform 0.3s;
    }
    .quick-link:hover .ql-icon { transform: scale(1.1) rotate(-3deg); }
    .ql-icon-users    { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed; }
    .ql-icon-products { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #db2777; }
    .ql-icon-orders   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
    .ql-icon-workshops { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .ql-icon-shops    { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
    .ql-icon-settings { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #64748b; }
    .ql-icon-hotdeals { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
    .ql-icon-profits  { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
    .quick-link .ql-text { font-size: 13px; font-weight: 600; }

    .bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    @media (max-width: 992px) { .bottom-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .welcome-banner { padding: 28px 22px; }
        .welcome-banner h1 { font-size: 22px; }
        .welcome-icon { display: none; }
        .quick-links-grid { grid-template-columns: 1fr 1fr; padding: 16px; }
    }
    @media (max-width: 480px) { .quick-links-grid { grid-template-columns: 1fr; } }
</style>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">

        <div class="welcome-banner">
            <div class="welcome-content">
                <div class="welcome-badge"><i class="fas fa-shield-alt me-1"></i> Admin Panel</div>
                <h1>Welcome back, <span><?php echo htmlspecialchars($current_user['name']); ?></span>!</h1>
                <p>Here's what's happening on your platform today.</p>
            </div>
            <div class="welcome-icon"><i class="fas fa-chart-pie"></i></div>
        </div>

        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-blue"><i class="fas fa-users"></i></div>
                <span class="stat-label">Total Users</span>
                <span class="stat-number"><?php echo number_format($total_users); ?></span>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-pink"><i class="fas fa-box-open"></i></div>
                <span class="stat-label">Total Products</span>
                <span class="stat-number"><?php echo number_format($total_products); ?></span>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-yellow"><i class="fas fa-wrench"></i></div>
                <span class="stat-label">Active Workshops</span>
                <span class="stat-number"><?php echo number_format($active_workshops); ?></span>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-red"><i class="fas fa-clock"></i></div>
                <span class="stat-label">Pending Approvals</span>
                <span class="stat-number"><?php echo number_format($pending_approvals); ?></span>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-user-friends" style="color:#667eea"></i> Recent Users</h3>
                    <a href="users.php" class="view-all">View All <i class="fas fa-arrow-right" style="font-size:11px;margin-left:4px;"></i></a>
                </div>
                <?php if (empty($recent_users)): ?>
                    <div class="empty-state"><i class="fas fa-users d-block"></i><p>No users yet</p></div>
                <?php else: ?>
                    <table class="dash-table">
                        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_users as $i => $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar avatar-<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                            <div>
                                                <div style="font-weight:600;color:#1a1a2e;"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div style="font-size:12px;color:#aaa;"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td><span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                    <td style="white-space:nowrap;color:#aaa;font-size:13px;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-receipt" style="color:#e94560"></i> Recent Orders</h3>
                    <a href="orders.php" class="view-all">View All <i class="fas fa-arrow-right" style="font-size:11px;margin-left:4px;"></i></a>
                </div>
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state"><i class="fas fa-receipt d-block"></i><p>No orders yet</p></div>
                <?php else: ?>
                    <table class="dash-table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td style="font-weight:700;color:#1a1a2e;">#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></td>
                                    <td style="font-weight:700;"><?php echo formatCurrency($order['total_amount']); ?></td>
                                    <td><span class="order-status-badge order-<?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                    <td style="white-space:nowrap;color:#aaa;font-size:13px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-th-large" style="color:#43e97b"></i> Quick Navigation</h3>
            </div>
            <div class="quick-links-grid">
                <a href="users.php" class="quick-link">
                    <div class="ql-icon ql-icon-users"><i class="fas fa-users"></i></div>
                    <span class="ql-text">Manage Users</span>
                </a>
                <a href="shops.php" class="quick-link">
                    <div class="ql-icon ql-icon-shops"><i class="fas fa-store"></i></div>
                    <span class="ql-text">Manage Shops</span>
                </a>
                <a href="workshops.php" class="quick-link">
                    <div class="ql-icon ql-icon-workshops"><i class="fas fa-wrench"></i></div>
                    <span class="ql-text">Manage Workshops</span>
                </a>
                <a href="products.php" class="quick-link">
                    <div class="ql-icon ql-icon-products"><i class="fas fa-box"></i></div>
                    <span class="ql-text">Manage Products</span>
                </a>
                <a href="orders.php" class="quick-link">
                    <div class="ql-icon ql-icon-orders"><i class="fas fa-shopping-cart"></i></div>
                    <span class="ql-text">Manage Orders</span>
                </a>
                <a href="hot-deals.php" class="quick-link">
                    <div class="ql-icon ql-icon-hotdeals"><i class="fas fa-fire"></i></div>
                    <span class="ql-text">Hot Deals</span>
                </a>
                <a href="shop-profits.php" class="quick-link">
                    <div class="ql-icon ql-icon-profits"><i class="fas fa-chart-line"></i></div>
                    <span class="ql-text">Shop Profits</span>
                </a>
                <a href="settings.php" class="quick-link">
                    <div class="ql-icon ql-icon-settings"><i class="fas fa-cog"></i></div>
                    <span class="ql-text">Settings</span>
                </a>
            </div>
        </div>

    </main>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
