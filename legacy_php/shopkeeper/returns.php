<?php
$page_title = 'Return Requests';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_return'])) {
    verifyCsrf();
    $return_id = (int)$_POST['return_id'];
    $new_status = sanitize($_POST['return_status']);
    $valid = ['pending', 'approved', 'rejected', 'completed'];
    if (in_array($new_status, $valid)) {
        $check = $conn->prepare("SELECT return_id FROM product_returns WHERE return_id = ? AND shop_id = ?");
        $check->bind_param("ii", $return_id, $shop_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $upd = $conn->prepare("UPDATE product_returns SET return_status = ? WHERE return_id = ? AND shop_id = ?");
            $upd->bind_param("sii", $new_status, $return_id, $shop_id);
            $upd->execute();
            $upd->close();

            $cust = $conn->prepare("SELECT customer_id FROM product_returns WHERE return_id = ?");
            $cust->bind_param("i", $return_id);
            $cust->execute();
            $cust_row = $cust->get_result()->fetch_assoc();
            $cust->close();

            if ($cust_row) {
                $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                $title = "Return Request Updated";
                $message = "Your return request #$return_id has been " . strtolower($new_status) . ".";
                $link = SITE_URL . "/customer/returns.php";
                $notif->bind_param("isss", $cust_row['customer_id'], $title, $message, $link);
                $notif->execute();
                $notif->close();
            }

            setFlash('success', "Return #$return_id marked as $new_status.");
        } else {
            setFlash('danger', 'You are not authorized to update this return.');
        }
        $check->close();
    }
    redirect(SITE_URL . '/shopkeeper/returns.php');
}

$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT r.*, p.product_name, p.product_image, o.order_id, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
    FROM product_returns r
    LEFT JOIN products p ON r.product_id = p.product_id
    LEFT JOIN orders o ON r.order_id = o.order_id
    LEFT JOIN users u ON r.customer_id = u.user_id
    WHERE r.shop_id = ?
";
$params = [$shop_id];
$types = "i";
if ($filter_status) {
    $sql .= " AND r.return_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
$sql .= " ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$count_stmt = $conn->prepare("SELECT return_status, COUNT(*) as cnt FROM product_returns WHERE shop_id = ? GROUP BY return_status");
$count_stmt->bind_param("i", $shop_id);
$count_stmt->execute();
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
foreach ($count_stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $c) {
    $counts[$c['return_status']] = (int)$c['cnt'];
}
$count_stmt->close();
$total_returns = array_sum($counts);

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
            <a class="dash-nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a>
            <a class="dash-nav-link" href="inventory.php"><i class="fas fa-warehouse"></i>Inventory</a>
            <a class="dash-nav-link" href="hot-deals.php"><i class="fas fa-fire"></i>Hot Deals</a>
            <a class="dash-nav-link active" href="returns.php"><i class="fas fa-undo-alt"></i>Returns</a>
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
            <h2 class="fw-bold mb-0"><i class="fas fa-undo-alt me-2"></i>Return Requests</h2>
            <div class="dash-header-actions">
                <span class="dash-badge dash-badge-blue"><?php echo $total_returns; ?> return<?php echo $total_returns !== 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background:rgba(255,193,7,.15);color:#d97706;"><i class="fas fa-clock"></i></div>
                    <div class="dash-stat-info"><h3><?php echo $counts['pending']; ?></h3><span>Pending</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background:rgba(25,135,84,.15);color:#198754;"><i class="fas fa-check-circle"></i></div>
                    <div class="dash-stat-info"><h3><?php echo $counts['approved']; ?></h3><span>Approved</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background:rgba(220,53,69,.15);color:#dc3545;"><i class="fas fa-times-circle"></i></div>
                    <div class="dash-stat-info"><h3><?php echo $counts['rejected']; ?></h3><span>Rejected</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background:rgba(13,110,253,.15);color:#0d6efd;"><i class="fas fa-flag-checkered"></i></div>
                    <div class="dash-stat-info"><h3><?php echo $counts['completed']; ?></h3><span>Completed</span></div>
                </div>
            </div>
        </div>

        <div class="dash-card mb-4">
            <div class="dash-card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label fw-bold">Filter by Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <?php foreach (['pending', 'approved', 'rejected', 'completed'] as $st): ?>
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

        <?php if (empty($returns)): ?>
            <div class="dash-card">
                <div class="dash-card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No return requests</h5>
                    <p class="text-muted">Customer return requests will appear here.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($returns as $ret): ?>
                <div class="dash-card mb-3">
                    <div class="dash-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    Return #<?php echo $ret['return_id']; ?>
                                    <span class="dash-badge dash-badge-<?php echo $ret['return_status'] === 'approved' ? 'green' : ($ret['return_status'] === 'rejected' ? 'red' : ($ret['return_status'] === 'completed' ? 'blue' : 'orange')); ?> ms-2"><?php echo ucfirst($ret['return_status']); ?></span>
                                </h5>
                                <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($ret['created_at'])); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="dash-badge dash-badge-gray">Order #<?php echo $ret['order_id']; ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($ret['product_image']) ? $ret['product_image'] : 'no-image.png'; ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:10px;background:#f5f5f5;">
                            <div>
                                <strong><?php echo htmlspecialchars($ret['product_name'] ?? 'Product'); ?></strong><br>
                                <small class="text-muted">Product ID: #<?php echo $ret['product_id']; ?></small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Customer</small>
                                <strong><?php echo htmlspecialchars($ret['customer_name'] ?? 'N/A'); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($ret['customer_email'] ?? ''); ?></small><br>
                                <?php if ($ret['customer_phone']): ?><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($ret['customer_phone']); ?></small><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Condition</small>
                                <span class="dash-badge dash-badge-gray"><?php echo ucfirst(str_replace('_', ' ', $ret['condition_desc'])); ?></span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Refund Amount</small>
                                <h5 class="product-price mb-0"><?php echo formatCurrency($ret['refund_amount']); ?></h5>
                            </div>
                        </div>

                        <div class="bg-light rounded p-3 mb-3">
                            <small class="fw-bold text-muted d-block mb-1"><i class="fas fa-comment me-1"></i>Reason:</small>
                            <small><?php echo htmlspecialchars($ret['reason']); ?></small>
                        </div>

                        <form method="POST" class="d-flex align-items-center gap-2">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="update_return" value="1">
                            <input type="hidden" name="return_id" value="<?php echo $ret['return_id']; ?>">
                            <select name="return_status" class="form-select form-select-sm" style="width:auto;">
                                <?php foreach (['pending', 'approved', 'rejected', 'completed'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $ret['return_status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="dash-btn-action dash-btn-primary btn-sm-modern"><i class="fas fa-check me-1"></i>Update Status</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
