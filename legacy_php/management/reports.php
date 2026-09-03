<?php
$page_title = 'Reports';
require_once __DIR__ . '/../includes/config.php';
requireRole('management');

$date_from = isset($_GET['date_from']) ? preg_replace('/[^0-9\-]/', '', $_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? preg_replace('/[^0-9\-]/', '', $_GET['date_to']) : date('Y-m-d');

$salesReport = $conn->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue, COALESCE(AVG(total_amount), 0) as avg_order_value FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$salesReport->bind_param("ss", $date_from, $date_to);
$salesReport->execute();
$salesReport = $salesReport->get_result()->fetch_assoc();

$salesByStatus = $conn->prepare("SELECT order_status, COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY order_status ORDER BY cnt DESC");
$salesByStatus->bind_param("ss", $date_from, $date_to);
$salesByStatus->execute();
$salesByStatus = $salesByStatus->get_result()->fetch_all(MYSQLI_ASSOC);

$topSellingProducts = $conn->prepare("SELECT p.product_name, p.price, c.category_name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price) as total_revenue FROM order_items oi JOIN products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id LEFT JOIN categories c ON p.category_id = c.category_id WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.product_id, p.product_name, p.price, c.category_name ORDER BY total_quantity DESC");
$topSellingProducts->bind_param("ss", $date_from, $date_to);
$topSellingProducts->execute();
$topSellingProducts = $topSellingProducts->get_result()->fetch_all(MYSQLI_ASSOC);

$categorySales = $conn->prepare("SELECT c.category_name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price) as total_revenue, COUNT(DISTINCT oi.product_id) as product_count FROM order_items oi JOIN products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id LEFT JOIN categories c ON p.category_id = c.category_id WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY c.category_id, c.category_name ORDER BY total_revenue DESC");
$categorySales->bind_param("ss", $date_from, $date_to);
$categorySales->execute();
$categorySales = $categorySales->get_result()->fetch_all(MYSQLI_ASSOC);

$workshopPerformance = $conn->prepare("SELECT w.workshop_name, w.location, w.rating, COUNT(a.appointment_id) as total_appointments, SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN a.status IN ('pending','approved','in_progress') THEN 1 ELSE 0 END) as active FROM workshops w LEFT JOIN appointments a ON w.workshop_id = a.workshop_id AND DATE(a.created_at) BETWEEN ? AND ? GROUP BY w.workshop_id, w.workshop_name, w.location, w.rating ORDER BY total_appointments DESC");
$workshopPerformance->bind_param("ss", $date_from, $date_to);
$workshopPerformance->execute();
$workshopPerformance = $workshopPerformance->get_result()->fetch_all(MYSQLI_ASSOC);

$maxCatRevenue = 0;
foreach ($categorySales as $c) { if ($c['total_revenue'] > $maxCatRevenue) $maxCatRevenue = $c['total_revenue']; }

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mgmt-wrap{max-width:1360px;margin:0 auto;padding:28px 16px 50px}
.mgmt-filter-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);padding:24px 28px;margin-bottom:24px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap}
.mgmt-filter-card label{font-size:.8rem;font-weight:700;color:#555;display:block;margin-bottom:6px}
.mgmt-filter-card input[type="date"]{border:1.5px solid #e0e0e0;border-radius:12px;padding:11px 16px;font-size:.88rem;font-family:'Inter',sans-serif;transition:all .25s;background:#fafafa}
.mgmt-filter-card input[type="date"]:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);outline:none;background:#fff}
.mgmt-filter-btn{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;padding:12px 28px;border-radius:12px;font-size:.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .3s;box-shadow:0 4px 15px rgba(102,126,234,.3)}
.mgmt-filter-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(102,126,234,.4)}
.mgmt-reset-btn{background:#f5f5f5;color:#777;border:1.5px solid #e8e8e8;padding:12px 22px;border-radius:12px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .25s}
.mgmt-reset-btn:hover{border-color:#ccc;color:#333;background:#eee}

.mgmt-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px}
.mgmt-sum-card{background:#fff;border-radius:16px;padding:26px;position:relative;overflow:hidden;border:1px solid rgba(0,0,0,.04);box-shadow:0 2px 12px rgba(0,0,0,.05);transition:transform .25s,box-shadow .25s}
.mgmt-sum-card:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.mgmt-sum-card::after{content:'';position:absolute;top:0;right:0;width:80px;height:80px;border-radius:0 16px 0 80px;opacity:.06}
.mgmt-sum-card:nth-child(1)::after{background:#667eea}
.mgmt-sum-card:nth-child(2)::after{background:#059669}
.mgmt-sum-card:nth-child(3)::after{background:#d97706}
.mgmt-sum-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:14px}
.mgmt-sum-val{font-size:1.8rem;font-weight:800;line-height:1.1;letter-spacing:-.5px}
.mgmt-sum-label{font-size:.78rem;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.6px;margin-top:4px}

.mgmt-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px}
.mgmt-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);overflow:hidden}
.mgmt-card-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc 0%,#fff 100%)}
.mgmt-card-head h5{margin:0;font-size:1rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.mgmt-card-head h5 i{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:1.1rem}
.mgmt-card-body{padding:20px 24px}
.mgmt-full-card{margin-bottom:22px}

.mgmt-bar-chart{display:flex;flex-direction:column;gap:12px;padding:8px 0}
.mgmt-bar-row{display:flex;align-items:center;gap:12px}
.mgmt-bar-label{width:120px;font-size:.82rem;font-weight:600;color:#555;flex-shrink:0;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mgmt-bar-track{flex:1;height:30px;background:#f1f5f9;border-radius:10px;overflow:hidden}
.mgmt-bar-fill{height:100%;border-radius:10px;display:flex;align-items:center;padding:0 12px;font-size:.72rem;font-weight:700;color:#fff;transition:width .8s cubic-bezier(.4,0,.2,1);min-width:fit-content}
.mgmt-bar-val{font-size:.82rem;font-weight:700;color:#333;width:90px;text-align:right;flex-shrink:0}

.mgmt-rank{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;flex-shrink:0}
.r1{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#d97706}
.r2{background:linear-gradient(135deg,#e2e8f0,#cbd5e1);color:#475569}
.r3{background:linear-gradient(135deg,#fed7aa,#fdba74);color:#c2410c}
.r4,.r5{background:#f1f5f9;color:#94a3b8}

.mgmt-empty{text-align:center;padding:40px 20px;color:#bbb}
.mgmt-empty i{font-size:2.5rem;margin-bottom:10px;display:block}

@media(max-width:1100px){.mgmt-summary{grid-template-columns:repeat(3,1fr)}.mgmt-grid{grid-template-columns:1fr}}
@media(max-width:768px){.mgmt-filter-card{flex-direction:column;align-items:stretch}.mgmt-summary{grid-template-columns:1fr}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="mgmt-wrap">

            <div class="admin-header" style="margin-bottom:24px;">
                <div>
                    <h3 class="admin-page-title"><i class="fas fa-chart-line me-2"></i>Reports</h3>
                    <p class="admin-page-subtitle">Sales and performance data for your selected period</p>
                </div>
            </div>

            <div class="mgmt-filter-card">
                <div style="flex:1;min-width:200px;">
                    <label>From Date</label>
                    <input type="date" class="mgmt-date-input" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" style="width:100%;" id="dateFrom">
                </div>
                <div style="flex:1;min-width:200px;">
                    <label>To Date</label>
                    <input type="date" class="mgmt-date-input" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" style="width:100%;" id="dateTo">
                </div>
                <button class="mgmt-filter-btn" onclick="applyFilter()"><i class="fas fa-search me-1"></i>Generate</button>
                <a href="reports.php" class="mgmt-reset-btn"><i class="fas fa-redo me-1"></i>Reset</a>
            </div>

            <div class="mgmt-summary">
                <div class="mgmt-sum-card">
                    <div class="mgmt-sum-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);color:#7c3aed;"><i class="fas fa-shopping-bag"></i></div>
                    <div class="mgmt-sum-val" style="color:#7c3aed;"><?php echo number_format($salesReport['total_orders']); ?></div>
                    <div class="mgmt-sum-label">Total Orders</div>
                </div>
                <div class="mgmt-sum-card">
                    <div class="mgmt-sum-icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669;"><i class="fas fa-dollar-sign"></i></div>
                    <div class="mgmt-sum-val" style="color:#059669;"><?php echo formatCurrency($salesReport['total_revenue']); ?></div>
                    <div class="mgmt-sum-label">Total Revenue</div>
                </div>
                <div class="mgmt-sum-card">
                    <div class="mgmt-sum-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#d97706;"><i class="fas fa-receipt"></i></div>
                    <div class="mgmt-sum-val" style="color:#d97706;"><?php echo formatCurrency($salesReport['avg_order_value']); ?></div>
                    <div class="mgmt-sum-label">Avg Order Value</div>
                </div>
            </div>

            <div class="mgmt-grid">
                <div class="mgmt-card">
                    <div class="mgmt-card-head"><h5><i class="fas fa-receipt me-2"></i>Sales by Status</h5></div>
                    <div class="mgmt-card-body">
                        <?php if (empty($salesByStatus)): ?>
                            <div class="mgmt-empty"><i class="fas fa-inbox"></i><p>No sales data for this period.</p></div>
                        <?php else: ?>
                            <?php
                            $colorMap = ['delivered'=>'#059669','confirmed'=>'#22c55e','processing'=>'#d97706','pending'=>'#f59e0b','shipped'=>'#2563eb','cancelled'=>'#dc2626','refunded'=>'#7c3aed'];
                            foreach ($salesByStatus as $s):
                                $clr = $colorMap[$s['order_status']] ?? '#94a3b8';
                            ?>
                                <div class="mgmt-bar-row" style="margin-bottom:10px;">
                                    <span class="mgmt-bar-label"><?php echo ucfirst($s['order_status']); ?></span>
                                    <div class="mgmt-bar-track">
                                        <div class="mgmt-bar-fill" style="width:<?php echo max(($s['cnt']/max(array_column($salesByStatus,'cnt')))*100,5); ?>%;background:<?php echo $clr; ?>;"><?php echo $s['cnt']; ?> orders</div>
                                    </div>
                                    <span class="mgmt-bar-val"><?php echo formatCurrency($s['revenue']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mgmt-card">
                    <div class="mgmt-card-head"><h5><i class="fas fa-layer-group me-2"></i>Category Sales</h5></div>
                    <div class="mgmt-card-body">
                        <?php if (empty($categorySales)): ?>
                            <div class="mgmt-empty"><i class="fas fa-tags"></i><p>No category data for this period.</p></div>
                        <?php else: ?>
                            <?php
                            $catColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#f5576c','#ff9a9e','#38f9d7','#fee140','#a78bfa'];
                            foreach ($categorySales as $ci => $cat):
                                $pct = $maxCatRevenue > 0 ? ($cat['total_revenue'] / $maxCatRevenue) * 100 : 0;
                                $clr = $catColors[$ci % count($catColors)];
                            ?>
                                <div class="mgmt-bar-row" style="margin-bottom:10px;">
                                    <span class="mgmt-bar-label"><?php echo htmlspecialchars($cat['category_name'] ?? 'Uncategorized'); ?></span>
                                    <div class="mgmt-bar-track">
                                        <div class="mgmt-bar-fill" style="width:<?php echo max($pct,5); ?>%;background:<?php echo $clr; ?>;"><?php echo $cat['product_count']; ?> products</div>
                                    </div>
                                    <span class="mgmt-bar-val"><?php echo formatCurrency($cat['total_revenue']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mgmt-card mgmt-full-card">
                <div class="mgmt-card-head"><h5><i class="fas fa-trophy me-2"></i>Top Selling Products</h5></div>
                <div class="mgmt-card-body">
                    <?php if (empty($topSellingProducts)): ?>
                        <div class="mgmt-empty"><i class="fas fa-box"></i><p>No product sales data for this period.</p></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead><tr><th style="width:50px;">#</th><th>Product</th><th>Category</th><th class="text-end">Unit Price</th><th class="text-end">Qty Sold</th><th class="text-end">Revenue</th></tr></thead>
                                <tbody>
                                <?php foreach ($topSellingProducts as $idx => $p): ?>
                                    <tr>
                                        <td><span class="mgmt-rank r<?php echo min($idx+1,5); ?>"><?php echo $idx+1; ?></span></td>
                                        <td style="font-weight:700;"><?php echo htmlspecialchars($p['product_name']); ?></td>
                                        <td><small style="color:#999;"><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></small></td>
                                        <td class="text-end"><?php echo formatCurrency($p['price']); ?></td>
                                        <td class="text-end" style="font-weight:700;"><?php echo number_format($p['total_quantity']); ?></td>
                                        <td class="text-end" style="font-weight:700;color:#059669;"><?php echo formatCurrency($p['total_revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mgmt-card mgmt-full-card">
                <div class="mgmt-card-head"><h5><i class="fas fa-tools me-2"></i>Workshop Performance</h5></div>
                <div class="mgmt-card-body">
                    <?php if (empty($workshopPerformance)): ?>
                        <div class="mgmt-empty"><i class="fas fa-wrench"></i><p>No workshop data available.</p></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead><tr><th>Workshop</th><th>Location</th><th class="text-end">Rating</th><th class="text-end">Total</th><th class="text-end">Completed</th><th class="text-end">Active</th><th class="text-end">Cancelled</th><th class="text-end">Rate</th></tr></thead>
                                <tbody>
                                <?php foreach ($workshopPerformance as $ws): ?>
                                    <tr>
                                        <td style="font-weight:700;"><?php echo htmlspecialchars($ws['workshop_name']); ?></td>
                                        <td><small style="color:#999;"><?php echo htmlspecialchars($ws['location'] ?? 'N/A'); ?></small></td>
                                        <td class="text-end"><i class="fas fa-star" style="color:#f59e0b;font-size:.75rem;"></i> <?php echo number_format($ws['rating'], 1); ?></td>
                                        <td class="text-end" style="font-weight:700;"><?php echo number_format($ws['total_appointments']); ?></td>
                                        <td class="text-end" style="font-weight:700;color:#059669;"><?php echo number_format($ws['completed']); ?></td>
                                        <td class="text-end" style="font-weight:700;color:#2563eb;"><?php echo number_format($ws['active']); ?></td>
                                        <td class="text-end" style="font-weight:700;color:#dc2626;"><?php echo number_format($ws['cancelled']); ?></td>
                                        <td class="text-end">
                                            <?php
                                            $rate = $ws['total_appointments'] > 0 ? round(($ws['completed'] / $ws['total_appointments']) * 100, 1) : 0;
                                            $rateColor = $rate >= 70 ? '#059669' : ($rate >= 40 ? '#d97706' : '#dc2626');
                                            ?>
                                            <span style="background:<?php echo $rateColor; ?>15;color:<?php echo $rateColor; ?>;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700;"><?php echo $rate; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function applyFilter(){
    var f=document.getElementById('dateFrom').value;
    var t=document.getElementById('dateTo').value;
    window.location.href='reports.php?date_from='+f+'&date_to='+t;
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
