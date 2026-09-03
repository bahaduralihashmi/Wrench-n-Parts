<?php
$page_title = 'Shop Profits';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$profits = $conn->query("
    SELECT 
        s.shop_id,
        s.shop_name,
        COUNT(DISTINCT p.product_id) AS total_products,
        COUNT(DISTINCT o.order_id) AS total_orders,
        COALESCE(SUM(oi.quantity * oi.price), 0) AS revenue
    FROM shops s
    LEFT JOIN products p ON s.shop_id = p.shop_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.order_status IN ('delivered','shipped','confirmed')
    WHERE s.status = 'active'
    GROUP BY s.shop_id, s.shop_name
    ORDER BY revenue DESC
")->fetch_all(MYSQLI_ASSOC);

$total_revenue = array_sum(array_column($profits, 'revenue'));
$total_orders = array_sum(array_column($profits, 'total_orders'));
$total_products = array_sum(array_column($profits, 'total_products'));

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-chart-line"></i> Shop Profits</h2>
                <p class="admin-page-subtitle">Revenue breakdown per shop (excludes cancelled orders)</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-dollar-sign"></i> <?php echo formatCurrency($total_revenue); ?></span>
            </div>
        </div>

        <div class="admin-stat-grid">
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-green"><i class="fas fa-dollar-sign"></i></div>
                <span class="stat-label">Total Revenue</span>
                <span class="stat-number"><?php echo formatCurrency($total_revenue); ?></span>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-blue"><i class="fas fa-store"></i></div>
                <span class="stat-label">Active Shops</span>
                <span class="stat-number"><?php echo count($profits); ?></span>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-cyan"><i class="fas fa-shopping-cart"></i></div>
                <span class="stat-label">Total Orders</span>
                <span class="stat-number"><?php echo number_format($total_orders); ?></span>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon stat-icon-pink"><i class="fas fa-box"></i></div>
                <span class="stat-label">Total Products</span>
                <span class="stat-number"><?php echo number_format($total_products); ?></span>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="fas fa-chart-bar"></i> Revenue by Shop</h5>
            </div>
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($profits)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-chart-line"></i>
                            <h4>No shop data available</h4>
                            <p>No active shops with revenue data</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Shop</th>
                                    <th>Products</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($profits as $i => $row): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($row['shop_name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($row['shop_name']); ?></div>
                                                    <div class="cell-info-sub">#<?php echo $row['shop_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($row['total_products']); ?></td>
                                        <td><?php echo number_format($row['total_orders']); ?></td>
                                        <td><strong style="color:#059669;"><?php echo formatCurrency($row['revenue']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
