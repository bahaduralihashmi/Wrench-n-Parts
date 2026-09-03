<?php
$page_title = 'Analytics';
require_once __DIR__ . '/../includes/config.php';
requireRole('management');

$totalUsers = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$totalProducts = $conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
$totalOrders = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];
$totalWorkshops = $conn->query("SELECT COUNT(*) as cnt FROM workshops")->fetch_assoc()['cnt'];
$totalAppointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments")->fetch_assoc()['cnt'];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['rev'];
$totalReviews = $conn->query("SELECT COUNT(*) as cnt FROM reviews")->fetch_assoc()['cnt'];

$monthlyRevenue = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month_label,
           DATE_FORMAT(created_at, '%b %Y') as month_name,
           COALESCE(SUM(total_amount), 0) as revenue,
           COUNT(*) as order_count,
           COALESCE(AVG(total_amount), 0) as avg_order
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month_label, month_name
    ORDER BY month_label ASC
")->fetch_all(MYSQLI_ASSOC);

$usersByRole = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role ORDER BY cnt DESC")->fetch_all(MYSQLI_ASSOC);
$usersByMonth = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month_label, DATE_FORMAT(created_at, '%b %Y') as month_name, COUNT(*) as user_count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month_label, month_name ORDER BY month_label ASC")->fetch_all(MYSQLI_ASSOC);

$productsByCategory = $conn->query("SELECT c.category_name, COUNT(p.product_id) as product_count, COALESCE(AVG(p.price), 0) as avg_price, COALESCE(SUM(p.stock), 0) as total_stock FROM categories c LEFT JOIN products p ON c.category_id = p.category_id GROUP BY c.category_id, c.category_name ORDER BY product_count DESC")->fetch_all(MYSQLI_ASSOC);

$totalProductStock = $conn->query("SELECT COALESCE(SUM(stock), 0) as total FROM products")->fetch_assoc()['total'];
$avgProductPrice = $conn->query("SELECT COALESCE(AVG(price), 0) as avg_price FROM products")->fetch_assoc()['avg_price'];
$avgWorkshopRating = $conn->query("SELECT COALESCE(AVG(rating), 0) as avg_rating, SUM(total_reviews) as total_reviews FROM workshops WHERE status = 'active'")->fetch_assoc();

$workshopAppointmentStats = $conn->query("SELECT SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress FROM appointments")->fetch_assoc();

$totalChatbotLogs = $conn->query("SELECT COUNT(*) as cnt FROM chatbot_logs")->fetch_assoc()['cnt'];
$uniqueChatbotUsers = $conn->query("SELECT COUNT(DISTINCT user_id) as cnt FROM chatbot_logs WHERE user_id IS NOT NULL")->fetch_assoc()['cnt'];
$topChatQuestions = $conn->query("SELECT question, COUNT(*) as ask_count FROM chatbot_logs GROUP BY question ORDER BY ask_count DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$chatbotActivityByMonth = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month_label, DATE_FORMAT(created_at, '%b %Y') as month_name, COUNT(*) as query_count FROM chatbot_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month_label, month_name ORDER BY month_label ASC")->fetch_all(MYSQLI_ASSOC);

$maxRevenueMonth = 0;
foreach ($monthlyRevenue as $m) { if ($m['revenue'] > $maxRevenueMonth) $maxRevenueMonth = $m['revenue']; }
$maxUsersMonth = 0;
foreach ($usersByMonth as $u) { if ($u['user_count'] > $maxUsersMonth) $maxUsersMonth = $u['user_count']; }

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mgmt-wrap{max-width:1360px;margin:0 auto;padding:28px 16px 50px}
.mgmt-stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px}
.mg-as{background:#fff;border-radius:16px;padding:24px;position:relative;overflow:hidden;border:1px solid rgba(0,0,0,.04);box-shadow:0 2px 12px rgba(0,0,0,.05);transition:transform .25s,box-shadow .25s}
.mg-as:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.mg-as::after{content:'';position:absolute;top:0;right:0;width:70px;height:70px;border-radius:0 16px 0 70px;opacity:.06}
.mg-as .icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:12px}
.mg-as .val{font-size:1.6rem;font-weight:800;line-height:1.1;letter-spacing:-.5px}
.mg-as .lbl{font-size:.72rem;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-top:4px}
.mg-as:nth-child(1)::after{background:#7c3aed}.mg-as:nth-child(1) .icon{background:linear-gradient(135deg,#ede9fe,#ddd6fe);color:#7c3aed}.mg-as:nth-child(1) .val{color:#7c3aed}
.mg-as:nth-child(2)::after{background:#059669}.mg-as:nth-child(2) .icon{background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669}.mg-as:nth-child(2) .val{color:#059669}
.mg-as:nth-child(3)::after{background:#2563eb}.mg-as:nth-child(3) .icon{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#2563eb}.mg-as:nth-child(3) .val{color:#2563eb}
.mg-as:nth-child(4)::after{background:#d97706}.mg-as:nth-child(4) .icon{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#d97706}.mg-as:nth-child(4) .val{color:#d97706}

.mg-as2 .mg-as:nth-child(5)::after{background:#db2777}.mg-as2 .mg-as:nth-child(5) .icon{background:linear-gradient(135deg,#fce7f3,#fbcfe8);color:#db2777}.mg-as2 .mg-as:nth-child(5) .val{color:#db2777}
.mg-as2 .mg-as:nth-child(6)::after{background:#2563eb}.mg-as2 .mg-as:nth-child(6) .icon{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#2563eb}.mg-as2 .mg-as:nth-child(6) .val{color:#2563eb}
.mg-as2 .mg-as:nth-child(7)::after{background:#f59e0b}.mg-as2 .mg-as:nth-child(7) .icon{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#f59e0b}.mg-as2 .mg-as:nth-child(7) .val{color:#f59e0b}
.mg-as2 .mg-as:nth-child(8)::after{background:#e94560}.mg-as2 .mg-as:nth-child(8) .icon{background:linear-gradient(135deg,#fee2e2,#fecaca);color:#e94560}.mg-as2 .mg-as:nth-child(8) .val{color:#e94560}

.mg-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px}
.mg-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);overflow:hidden}
.mg-card-h{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc 0%,#fff 100%)}
.mg-card-h h5{margin:0;font-size:1rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.mg-card-h h5 i{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.mg-card-b{padding:20px 24px}
.mg-full{margin-bottom:22px}

.mg-bar{display:flex;flex-direction:column;gap:10px}
.mg-bar-r{display:flex;align-items:center;gap:10px}
.mg-bar-l{width:100px;font-size:.82rem;font-weight:600;color:#555;flex-shrink:0;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mg-bar-t{flex:1;height:28px;background:#f1f5f9;border-radius:10px;overflow:hidden}
.mg-bar-f{height:100%;border-radius:10px;display:flex;align-items:center;padding:0 10px;font-size:.7rem;font-weight:700;color:#fff;transition:width .8s cubic-bezier(.4,0,.2,1);min-width:fit-content}
.mg-bar-v{font-size:.82rem;font-weight:700;color:#333;width:70px;text-align:right;flex-shrink:0}

.mg-mini-stat{text-align:center;padding:16px}
.mg-mini-stat h3{font-size:1.8rem;font-weight:800;margin:0 0 4px}
.mg-mini-stat small{color:#999;font-size:.78rem;font-weight:600}

.mg-role-chips{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
.mg-role-chip{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;background:#f8f9fa;border:1px solid #f0f0f0;flex:1;min-width:100px;transition:all .2s}
.mg-role-chip:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.06)}
.mg-role-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.mg-role-chip .rc{font-size:1.2rem;font-weight:800;line-height:1}
.mg-role-chip .rn{font-size:.72rem;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.3px}

.mg-empty{text-align:center;padding:40px 20px;color:#bbb}
.mg-empty i{font-size:2.5rem;margin-bottom:10px;display:block}

.mg-appoint-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5}
.mg-appoint-row:last-child{border-bottom:none}
.mg-appoint-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.mg-appoint-name{flex:1;font-weight:600;font-size:.88rem}
.mg-appoint-val{font-weight:800;font-size:1rem}
.mg-appoint-pct{font-size:.78rem;color:#888;width:50px;text-align:right}

@media(max-width:1100px){.mg-stats-row,.mg-as2{grid-template-columns:repeat(2,1fr)}.mg-grid{grid-template-columns:1fr}}
@media(max-width:768px){.mg-stats-row{grid-template-columns:1fr 1fr}.mg-as2{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.mg-stats-row,.mg-as2{grid-template-columns:1fr}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="mgmt-wrap">

            <div class="admin-header" style="margin-bottom:24px;">
                <div>
                    <h3 class="admin-page-title"><i class="fas fa-chart-pie me-2"></i>Analytics</h3>
                    <p class="admin-page-subtitle">Comprehensive platform insights and metrics</p>
                </div>
            </div>

            <div class="mgmt-stats-row">
                <div class="mg-as"><div class="icon"><i class="fas fa-dollar-sign"></i></div><div class="val"><?php echo formatCurrency($totalRevenue); ?></div><div class="lbl">Total Revenue</div></div>
                <div class="mg-as"><div class="icon"><i class="fas fa-shopping-bag"></i></div><div class="val"><?php echo number_format($totalOrders); ?></div><div class="lbl">Total Orders</div></div>
                <div class="mg-as"><div class="icon"><i class="fas fa-users"></i></div><div class="val"><?php echo number_format($totalUsers); ?></div><div class="lbl">Total Users</div></div>
                <div class="mg-as"><div class="icon"><i class="fas fa-box-open"></i></div><div class="val"><?php echo number_format($totalProducts); ?></div><div class="lbl">Total Products</div></div>
            </div>

            <div class="mgmt-stats-row mg-as2">
                <div class="mg-as"><div class="icon"><i class="fas fa-tools"></i></div><div class="val"><?php echo number_format($totalWorkshops); ?></div><div class="lbl">Workshops</div></div>
                <div class="mg-as"><div class="icon"><i class="fas fa-calendar-check"></i></div><div class="val"><?php echo number_format($totalAppointments); ?></div><div class="lbl">Appointments</div></div>
                <div class="mg-as"><div class="icon"><i class="fas fa-star"></i></div><div class="val"><?php echo number_format($avgWorkshopRating['avg_rating'], 1); ?></div><div class="lbl">Avg Rating</div></div>
                <div class="mg-as"><div class="icon"><i class="fas fa-robot"></i></div><div class="val"><?php echo number_format($totalChatbotLogs); ?></div><div class="lbl">Chatbot Queries</div></div>
            </div>

            <!-- Revenue Trends -->
            <div class="mg-card mg-full">
                <div class="mg-card-h"><h5><i class="fas fa-chart-bar me-2"></i>Revenue Trends (Monthly)</h5></div>
                <div class="mg-card-b">
                    <?php if (empty($monthlyRevenue)): ?>
                        <div class="mg-empty"><i class="fas fa-chart-line"></i><p>No revenue data available.</p></div>
                    <?php else: ?>
                        <div class="mg-bar">
                            <?php
                            $monthColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#f5576c','#ff9a9e','#38f9d7','#a78bfa','#818cf8','#fbbf24','#34d399'];
                            foreach ($monthlyRevenue as $mi => $m):
                                $pct = $maxRevenueMonth > 0 ? ($m['revenue'] / $maxRevenueMonth) * 100 : 0;
                                $clr = $monthColors[$mi % count($monthColors)];
                            ?>
                                <div class="mg-bar-r">
                                    <span class="mg-bar-l"><?php echo $m['month_name']; ?></span>
                                    <div class="mg-bar-t">
                                        <div class="mg-bar-f" style="width:<?php echo max($pct,4); ?>%;background:linear-gradient(90deg,<?php echo $clr; ?>,<?php echo $monthColors[($mi+3)%count($monthColors)]; ?>);">
                                            <?php echo $m['order_count']; ?> orders
                                        </div>
                                    </div>
                                    <span class="mg-bar-v"><?php echo formatCurrency($m['revenue']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:16px;padding:14px 18px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px;display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-weight:700;font-size:.88rem;color:#166534;"><i class="fas fa-check-circle me-1"></i>Total (<?php echo count($monthlyRevenue); ?> months)</span>
                            <span style="font-weight:800;font-size:1.1rem;color:#059669;"><?php echo formatCurrency($totalRevenue); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mg-grid">
                <!-- User Growth -->
                <div class="mg-card">
                    <div class="mg-card-h"><h5><i class="fas fa-users me-2"></i>User Growth</h5></div>
                    <div class="mg-card-b">
                        <?php
                        $roleColors = ['#7c3aed','#2563eb','#059669','#d97706','#64748b'];
                        ?>
                        <div class="mg-role-chips">
                            <?php foreach ($usersByRole as $ri => $r):
                                $clr = $roleColors[$ri % count($roleColors)];
                                $pct = $totalUsers > 0 ? round(($r['cnt'] / $totalUsers) * 100, 1) : 0;
                            ?>
                                <div class="mg-role-chip">
                                    <div class="mg-role-dot" style="background:<?php echo $clr; ?>;"></div>
                                    <div><div class="rc" style="color:<?php echo $clr; ?>;"><?php echo $r['cnt']; ?></div><div class="rn"><?php echo ucfirst($r['role']); ?> (<?php echo $pct; ?>%)</div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($usersByMonth)): ?>
                            <div style="margin-top:12px;">
                                <div class="mg-bar">
                                    <?php foreach ($usersByMonth as $um):
                                        $pct = $maxUsersMonth > 0 ? ($um['user_count'] / $maxUsersMonth) * 100 : 0;
                                    ?>
                                        <div class="mg-bar-r">
                                            <span class="mg-bar-l"><?php echo $um['month_name']; ?></span>
                                            <div class="mg-bar-t">
                                                <div class="mg-bar-f" style="width:<?php echo max($pct,5); ?>%;background:linear-gradient(90deg,#667eea,#764ba2);"><?php echo $um['user_count']; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Statistics -->
                <div class="mg-card">
                    <div class="mg-card-h"><h5><i class="fas fa-box me-2"></i>Product Statistics</h5></div>
                    <div class="mg-card-b">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:12px;"><h3 style="color:#7c3aed;"><?php echo number_format($totalProducts); ?></h3><small>Products</small></div>
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:12px;"><h3 style="color:#059669;"><?php echo formatCurrency($avgProductPrice); ?></h3><small>Avg Price</small></div>
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-radius:12px;"><h3 style="color:#2563eb;"><?php echo number_format($totalProductStock); ?></h3><small>Total Stock</small></div>
                        </div>
                        <?php if (!empty($productsByCategory)): ?>
                            <div class="mg-bar">
                                <?php
                                $catMax = max(array_column($productsByCategory, 'product_count'));
                                $catColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#f5576c','#ff9a9e','#38f9d7','#fee140','#a78bfa'];
                                foreach ($productsByCategory as $ci => $pc):
                                    $pct = $catMax > 0 ? ($pc['product_count'] / $catMax) * 100 : 0;
                                ?>
                                    <div class="mg-bar-r">
                                        <span class="mg-bar-l"><?php echo htmlspecialchars($pc['category_name']); ?></span>
                                        <div class="mg-bar-t">
                                            <div class="mg-bar-f" style="width:<?php echo max($pct,5); ?>%;background:<?php echo $catColors[$ci % count($catColors)]; ?>;"><?php echo $pc['product_count']; ?></div>
                                        </div>
                                        <span class="mg-bar-v"><?php echo formatCurrency($pc['avg_price']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mg-grid">
                <!-- Workshop Stats -->
                <div class="mg-card">
                    <div class="mg-card-h"><h5><i class="fas fa-tools me-2"></i>Workshop & Appointments</h5></div>
                    <div class="mg-card-b">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);border-radius:12px;"><h3 style="color:#2563eb;"><?php echo number_format($totalWorkshops); ?></h3><small>Workshops</small></div>
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;"><h3 style="color:#d97706;"><i class="fas fa-star" style="font-size:.8rem;"></i> <?php echo number_format($avgWorkshopRating['avg_rating'], 1); ?></h3><small>Avg Rating</small></div>
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#fce7f3,#fbcfe8);border-radius:12px;"><h3 style="color:#db2777;"><?php echo number_format($totalAppointments); ?></h3><small>Appointments</small></div>
                        </div>
                        <?php
                        $aptStats = [
                            ['name'=>'Completed','val'=>$workshopAppointmentStats['completed'] ?? 0,'color'=>'#059669','bg'=>'#d1fae5'],
                            ['name'=>'Approved','val'=>$workshopAppointmentStats['approved'] ?? 0,'color'=>'#2563eb','bg'=>'#dbeafe'],
                            ['name'=>'In Progress','val'=>$workshopAppointmentStats['in_progress'] ?? 0,'color'=>'#d97706','bg'=>'#fef3c7'],
                            ['name'=>'Pending','val'=>$workshopAppointmentStats['pending'] ?? 0,'color'=>'#f59e0b','bg'=>'#fef9c3'],
                            ['name'=>'Cancelled','val'=>$workshopAppointmentStats['cancelled'] ?? 0,'color'=>'#dc2626','bg'=>'#fee2e2'],
                        ];
                        foreach ($aptStats as $a):
                            $pct = $totalAppointments > 0 ? round(($a['val'] / $totalAppointments) * 100, 1) : 0;
                        ?>
                            <div class="mg-appoint-row">
                                <div class="mg-appoint-dot" style="background:<?php echo $a['color']; ?>;"></div>
                                <span class="mg-appoint-name"><?php echo $a['name']; ?></span>
                                <span class="mg-appoint-val" style="color:<?php echo $a['color']; ?>;"><?php echo number_format($a['val']); ?></span>
                                <span class="mg-appoint-pct"><?php echo $pct; ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Chatbot Stats -->
                <div class="mg-card">
                    <div class="mg-card-h"><h5><i class="fas fa-robot me-2"></i>Chatbot Usage</h5></div>
                    <div class="mg-card-b">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#fee2e2,#fecaca);border-radius:12px;"><h3 style="color:#dc2626;"><?php echo number_format($totalChatbotLogs); ?></h3><small>Total Queries</small></div>
                            <div class="mg-mini-stat" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:12px;"><h3 style="color:#7c3aed;"><?php echo number_format($uniqueChatbotUsers); ?></h3><small>Unique Users</small></div>
                        </div>
                        <?php if (!empty($chatbotActivityByMonth)): ?>
                            <h6 style="font-size:.78rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Query Activity</h6>
                            <div class="mg-bar">
                                <?php
                                $maxChat = max(array_column($chatbotActivityByMonth, 'query_count'));
                                foreach ($chatbotActivityByMonth as $ca):
                                    $pct = $maxChat > 0 ? ($ca['query_count'] / $maxChat) * 100 : 0;
                                ?>
                                    <div class="mg-bar-r">
                                        <span class="mg-bar-l"><?php echo $ca['month_name']; ?></span>
                                        <div class="mg-bar-t">
                                            <div class="mg-bar-f" style="width:<?php echo max($pct,5); ?>%;background:linear-gradient(90deg,#e94560,#ff6b81);"><?php echo $ca['query_count']; ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($topChatQuestions)): ?>
                            <h6 style="font-size:.78rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 10px;">Top Questions</h6>
                            <?php foreach ($topChatQuestions as $idx => $tq): ?>
                                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;<?php echo $idx < count($topChatQuestions)-1 ? 'border-bottom:1px solid #f5f5f5;' : ''; ?>">
                                    <span style="width:24px;height:24px;border-radius:7px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#888;flex-shrink:0;"><?php echo $idx+1; ?></span>
                                    <span style="flex:1;font-size:.85rem;color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($tq['question']); ?></span>
                                    <span style="font-weight:700;font-size:.82rem;color:#333;flex-shrink:0;"><?php echo $tq['ask_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
