<?php
$page_title = 'Management Dashboard';
require_once __DIR__ . '/../includes/config.php';
requireRole('management');

$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['rev'];
$totalOrders = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];
$totalUsers = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$activeProducts = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE status = 'available'")->fetch_assoc()['cnt'];
$activeWorkshops = $conn->query("SELECT COUNT(*) as cnt FROM workshops WHERE status = 'active'")->fetch_assoc()['cnt'];

$monthlyRevenue = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month_label, 
           DATE_FORMAT(created_at, '%b %Y') as month_name,
           COALESCE(SUM(total_amount), 0) as revenue,
           COUNT(*) as order_count
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
    ORDER BY month_label ASC
")->fetch_all(MYSQLI_ASSOC);

$topProducts = $conn->query("
    SELECT p.product_name, p.price, c.category_name,
           SUM(oi.quantity) as total_sold,
           SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    GROUP BY p.product_id, p.product_name, p.price, c.category_name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$ordersByStatus = $conn->query("
    SELECT order_status, COUNT(*) as cnt 
    FROM orders 
    GROUP BY order_status 
    ORDER BY cnt DESC
")->fetch_all(MYSQLI_ASSOC);

$maxRevenue = 0;
foreach ($monthlyRevenue as $m) { if ($m['revenue'] > $maxRevenue) $maxRevenue = $m['revenue']; }

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mgmt-wrap{max-width:1360px;margin:0 auto;padding:28px 16px 50px}
.mgmt-banner{background:linear-gradient(135deg,#0f0c29 0%,#302b63 50%,#24243e 100%);border-radius:20px;padding:36px 40px;color:#fff;position:relative;overflow:hidden;margin-bottom:28px}
.mgmt-banner::before{content:'';position:absolute;top:-40%;right:-10%;width:320px;height:320px;background:radial-gradient(circle,rgba(102,126,234,.3) 0%,transparent 70%);border-radius:50%}
.mgmt-banner::after{content:'';position:absolute;bottom:-50%;left:15%;width:280px;height:280px;background:radial-gradient(circle,rgba(233,69,96,.2) 0%,transparent 70%);border-radius:50%}
.mgmt-banner h1{font-size:1.8rem;font-weight:800;margin:0 0 6px;position:relative;z-index:1}
.mgmt-banner p{font-size:.95rem;opacity:.8;margin:0;position:relative;z-index:1}
.mgmt-banner .mgmt-date{display:inline-block;margin-top:14px;background:rgba(255,255,255,.12);backdrop-filter:blur(4px);padding:7px 18px;border-radius:30px;font-size:.82rem;font-weight:500;position:relative;z-index:1}

.mgmt-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:18px;margin-bottom:28px}
.mgmt-stat{background:#fff;border-radius:16px;padding:24px;position:relative;overflow:hidden;border:1px solid rgba(0,0,0,.04);box-shadow:0 2px 12px rgba(0,0,0,.05);transition:transform .25s,box-shadow .25s}
.mgmt-stat:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(0,0,0,.1)}
.mgmt-stat .mgmt-stat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:14px}
.mgmt-stat .mgmt-stat-val{font-size:1.8rem;font-weight:800;line-height:1.1;margin-bottom:4px;letter-spacing:-.5px}
.mgmt-stat .mgmt-stat-label{font-size:.78rem;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.6px}
.mgmt-stat::after{content:'';position:absolute;top:0;right:0;width:80px;height:80px;border-radius:0 16px 0 80px;opacity:.06}
.ms-purple .mgmt-stat-icon{background:linear-gradient(135deg,#ede9fe,#ddd6fe);color:#7c3aed}
.ms-purple .mgmt-stat-val{color:#7c3aed}
.ms-purple::after{background:#7c3aed}
.ms-pink .mgmt-stat-icon{background:linear-gradient(135deg,#fce7f3,#fbcfe8);color:#db2777}
.ms-pink .mgmt-stat-val{color:#db2777}
.ms-pink::after{background:#db2777}
.ms-blue .mgmt-stat-icon{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#2563eb}
.ms-blue .mgmt-stat-val{color:#2563eb}
.ms-blue::after{background:#2563eb}
.ms-green .mgmt-stat-icon{background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669}
.ms-green .mgmt-stat-val{color:#059669}
.ms-green::after{background:#059669}
.ms-amber .mgmt-stat-icon{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#d97706}
.ms-amber .mgmt-stat-val{color:#d97706}
.ms-amber::after{background:#d97706}

.mgmt-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px}
.mgmt-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);overflow:hidden}
.mgmt-card-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc 0%,#fff 100%)}
.mgmt-card-head h5{margin:0;font-size:1rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.mgmt-card-head h5 i{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:1.1rem}
.mgmt-card-body{padding:20px 24px}

.mgmt-bar-chart{display:flex;flex-direction:column;gap:14px;padding:8px 0}
.mgmt-bar-row{display:flex;align-items:center;gap:14px}
.mgmt-bar-label{width:90px;font-size:.82rem;font-weight:600;color:#555;flex-shrink:0;text-align:right}
.mgmt-bar-track{flex:1;height:32px;background:#f1f5f9;border-radius:10px;overflow:hidden;position:relative}
.mgmt-bar-fill{height:100%;border-radius:10px;display:flex;align-items:center;padding:0 12px;font-size:.75rem;font-weight:700;color:#fff;transition:width .8s cubic-bezier(.4,0,.2,1);min-width:fit-content}
.mgmt-bar-val{font-size:.82rem;font-weight:700;color:#333;width:80px;text-align:right;flex-shrink:0}

.mgmt-role-chips{display:flex;flex-wrap:wrap;gap:10px;padding:10px 0}
.mgmt-role-chip{display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:12px;background:#f8f9fa;border:1px solid #f0f0f0;transition:all .2s;flex:1;min-width:120px}
.mgmt-role-chip:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.06)}
.mgmt-role-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.mgmt-role-chip .role-count{font-size:1.3rem;font-weight:800;line-height:1}
.mgmt-role-chip .role-name{font-size:.78rem;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.3px}

.mgmt-top-item{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid #f5f5f5}
.mgmt-top-item:last-child{border-bottom:none}
.mgmt-top-rank{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;flex-shrink:0}
.mgmt-top-rank.r1{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#d97706}
.mgmt-top-rank.r2{background:linear-gradient(135deg,#e2e8f0,#cbd5e1);color:#475569}
.mgmt-top-rank.r3{background:linear-gradient(135deg,#fed7aa,#fdba74);color:#c2410c}
.mgmt-top-rank.r4,.mgmt-top-rank.r5{background:#f1f5f9;color:#94a3b8}
.mgmt-top-info{flex:1}
.mgmt-top-info .name{font-weight:700;font-size:.9rem;color:#1a1a2e}
.mgmt-top-info .cat{font-size:.78rem;color:#999;margin-top:2px}
.mgmt-top-sales{text-align:right}
.mgmt-top-sales .qty{font-size:1.1rem;font-weight:800;color:#1a1a2e}
.mgmt-top-sales .rev{font-size:.78rem;color:#059669;font-weight:600}

.mgmt-empty{text-align:center;padding:40px 20px;color:#bbb}
.mgmt-empty i{font-size:2.5rem;margin-bottom:10px;display:block}

@media(max-width:1100px){.mgmt-stats{grid-template-columns:repeat(3,1fr)}.mgmt-grid{grid-template-columns:1fr}}
@media(max-width:768px){.mgmt-stats{grid-template-columns:1fr 1fr}.mgmt-banner{padding:28px 22px}.mgmt-banner h1{font-size:1.4rem}}
@media(max-width:480px){.mgmt-stats{grid-template-columns:1fr}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="mgmt-wrap">

            <div class="mgmt-banner">
                <h1>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</h1>
                <p>Here's your platform overview at a glance.</p>
                <span class="mgmt-date"><i class="fas fa-calendar-alt me-1"></i><?php echo date('l, F j, Y'); ?></span>
            </div>

            <div class="mgmt-stats">
                <div class="mgmt-stat ms-purple">
                    <div class="mgmt-stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="mgmt-stat-val"><?php echo formatCurrency($totalRevenue); ?></div>
                    <div class="mgmt-stat-label">Total Revenue</div>
                </div>
                <div class="mgmt-stat ms-pink">
                    <div class="mgmt-stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="mgmt-stat-val"><?php echo number_format($totalOrders); ?></div>
                    <div class="mgmt-stat-label">Total Orders</div>
                </div>
                <div class="mgmt-stat ms-blue">
                    <div class="mgmt-stat-icon"><i class="fas fa-users"></i></div>
                    <div class="mgmt-stat-val"><?php echo number_format($totalUsers); ?></div>
                    <div class="mgmt-stat-label">Total Users</div>
                </div>
                <div class="mgmt-stat ms-green">
                    <div class="mgmt-stat-icon"><i class="fas fa-box-open"></i></div>
                    <div class="mgmt-stat-val"><?php echo number_format($activeProducts); ?></div>
                    <div class="mgmt-stat-label">Active Products</div>
                </div>
                <div class="mgmt-stat ms-amber">
                    <div class="mgmt-stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="mgmt-stat-val"><?php echo number_format($activeWorkshops); ?></div>
                    <div class="mgmt-stat-label">Active Workshops</div>
                </div>
            </div>

            <div class="mgmt-grid">
                <!-- Revenue Breakdown -->
                <div class="mgmt-card">
                    <div class="mgmt-card-head">
                        <h5><i class="fas fa-chart-bar me-2"></i>Revenue (Last 6 Months)</h5>
                    </div>
                    <div class="mgmt-card-body">
                        <?php if (empty($monthlyRevenue)): ?>
                            <div class="mgmt-empty"><i class="fas fa-chart-line"></i><p>No revenue data yet.</p></div>
                        <?php else: ?>
                            <div class="mgmt-bar-chart">
                                <?php foreach ($monthlyRevenue as $m):
                                    $pct = $maxRevenue > 0 ? ($m['revenue'] / $maxRevenue) * 100 : 0;
                                    $colors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#f5576c'];
                                    $ci = array_search($m, $monthlyRevenue) % count($colors);
                                ?>
                                    <div class="mgmt-bar-row">
                                        <span class="mgmt-bar-label"><?php echo $m['month_name']; ?></span>
                                        <div class="mgmt-bar-track">
                                            <div class="mgmt-bar-fill" style="width:<?php echo max($pct, 5); ?>%;background:linear-gradient(90deg,<?php echo $colors[$ci]; ?>,<?php echo $colors[($ci+1)%count($colors)]; ?>);">
                                                <?php if ($pct > 20) echo formatCurrency($m['revenue']); ?>
                                            </div>
                                        </div>
                                        <span class="mgmt-bar-val"><?php echo formatCurrency($m['revenue']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Orders by Status -->
                <div class="mgmt-card">
                    <div class="mgmt-card-head">
                        <h5><i class="fas fa-layer-group me-2"></i>Orders by Status</h5>
                    </div>
                    <div class="mgmt-card-body">
                        <?php if (empty($ordersByStatus)): ?>
                            <div class="mgmt-empty"><i class="fas fa-inbox"></i><p>No orders yet.</p></div>
                        <?php else: ?>
                            <div class="mgmt-bar-chart">
                                <?php foreach ($ordersByStatus as $s):
                                    $pct = $totalOrders > 0 ? ($s['cnt'] / $totalOrders) * 100 : 0;
                                    $colorMap = ['delivered'=>'#059669','confirmed'=>'#22c55e','processing'=>'#d97706','pending'=>'#f59e0b','shipped'=>'#2563eb','cancelled'=>'#dc2626','refunded'=>'#7c3aed'];
                                    $clr = $colorMap[$s['order_status']] ?? '#94a3b8';
                                ?>
                                    <div class="mgmt-bar-row">
                                        <span class="mgmt-bar-label"><?php echo ucfirst($s['order_status']); ?></span>
                                        <div class="mgmt-bar-track">
                                            <div class="mgmt-bar-fill" style="width:<?php echo max($pct, 4); ?>%;background:<?php echo $clr; ?>;"><?php echo round($pct, 1); ?>%</div>
                                        </div>
                                        <span class="mgmt-bar-val"><?php echo number_format($s['cnt']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mgmt-grid">
                <!-- Top Products -->
                <div class="mgmt-card">
                    <div class="mgmt-card-head">
                        <h5><i class="fas fa-trophy me-2"></i>Top Selling Products</h5>
                    </div>
                    <div class="mgmt-card-body">
                        <?php if (empty($topProducts)): ?>
                            <div class="mgmt-empty"><i class="fas fa-box"></i><p>No sales data yet.</p></div>
                        <?php else: ?>
                            <?php foreach ($topProducts as $idx => $p): ?>
                                <div class="mgmt-top-item">
                                    <div class="mgmt-top-rank r<?php echo $idx + 1; ?>"><?php echo $idx + 1; ?></div>
                                    <div class="mgmt-top-info">
                                        <div class="name"><?php echo htmlspecialchars($p['product_name']); ?></div>
                                        <div class="cat"><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="mgmt-top-sales">
                                        <div class="qty"><?php echo number_format($p['total_sold']); ?> sold</div>
                                        <div class="rev"><?php echo formatCurrency($p['total_revenue']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
