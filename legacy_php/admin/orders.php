<?php
$page_title = 'Manage Orders';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['update_order_status'])) {
    verifyCsrf();
    $oid = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    $valid = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $valid)) { setFlash('danger', 'Invalid status.'); redirect('orders.php'); }
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $oid);
    $stmt->execute();
    $stmt->close();

    $cstmt = $conn->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
    $cstmt->bind_param("i", $oid);
    $cstmt->execute();
    $crow = $cstmt->get_result()->fetch_assoc();
    $cstmt->close();
    if ($crow) {
        $nstmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $title = "Order #$oid Updated";
        $msg = "Your order #$oid has been " . strtolower(ucfirst($status)) . ".";
        $link = SITE_URL . "/customer/orders.php";
        $nstmt->bind_param("isss", $crow['customer_id'], $title, $msg, $link);
        $nstmt->execute();
        $nstmt->close();
    }

    setFlash('success', 'Order status updated successfully.');
    redirect('orders.php');
}

if (isset($_POST['update_payment_status'])) {
    verifyCsrf();
    $oid = intval($_POST['order_id']);
    $pstatus = sanitize($_POST['payment_status']);
    $valid_p = ['pending', 'paid', 'failed', 'refunded'];
    if (!in_array($pstatus, $valid_p)) { setFlash('danger', 'Invalid payment status.'); redirect('orders.php'); }
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $pstatus, $oid);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Payment status updated successfully.');
    redirect('orders.php');
}

$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where .= " AND o.order_status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
if ($search !== '') {
    $where .= " AND (o.order_id = ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = intval($search);
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'iss';
}

$countQuery = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.customer_id = u.user_id $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.customer_id = u.user_id $where ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'order_id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemQuery = "SELECT oi.*, p.product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id IN ($placeholders)";
    $itemStmt = $conn->prepare($itemQuery);
    $itemTypes = str_repeat('i', count($orderIds));
    $itemStmt->bind_param($itemTypes, ...$orderIds);
    $itemStmt->execute();
    $allItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemStmt->close();
    foreach ($allItems as $item) {
        $orderItems[$item['order_id']][] = $item;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-shopping-cart"></i> Manage Orders</h2>
                <p class="admin-page-subtitle">Track and manage all customer orders</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-receipt"></i> <?php echo $totalRows; ?> orders</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by order ID, customer name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <select name="status" style="min-width:160px;">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="orders.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($orders)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>No orders found</h4>
                            <p>Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                        <td>
                                            <div class="cell-info-name"><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                                            <div class="cell-info-sub"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
                                        </td>
                                        <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                        <td><span class="status-pill status-<?php echo sanitize($order['order_status']); ?>"><?php echo ucfirst(sanitize($order['order_status'])); ?></span></td>
                                        <td><span class="status-pill status-<?php echo sanitize($order['payment_status']); ?>"><?php echo ucfirst(sanitize($order['payment_status'])); ?></span></td>
                                        <td><small style="color:#999;"><?php echo timeAgo($order['created_at']); ?></small></td>
                                        <td>
                                            <div class="action-btns" style="justify-content:flex-end;">
                                                <button class="action-btn action-btn-view" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['order_id']; ?>" title="View Details"><i class="fas fa-eye"></i></button>
                                                <button class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['order_id']; ?>" title="Update Status"><i class="fas fa-pen"></i></button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="orderModal<?php echo $order['order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-receipt me-2 text-red"></i> Order #<?php echo $order['order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><div class="detail-label">Customer</div><div class="detail-value"><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value"><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Order Status</div><div class="detail-value"><span class="status-pill status-<?php echo sanitize($order['order_status']); ?>"><?php echo ucfirst(sanitize($order['order_status'])); ?></span></div></div>
                                                        <div class="detail-item"><div class="detail-label">Payment</div><div class="detail-value"><span class="status-pill status-<?php echo sanitize($order['payment_status']); ?>"><?php echo ucfirst(sanitize($order['payment_status'])); ?></span></div></div>
                                                        <div class="detail-item"><div class="detail-label">Total</div><div class="detail-value"><?php echo formatCurrency($order['total_amount']); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div></div>
                                                    </div>
                                                    <?php $items = $orderItems[$order['order_id']] ?? []; if (!empty($items)): ?>
                                                        <h6 style="margin-top:20px;font-weight:700;color:#1a1a2e;"><i class="fas fa-box me-1"></i> Order Items</h6>
                                                        <table class="admin-table" style="margin-top:8px;">
                                                            <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                                                            <tbody>
                                                                <?php foreach ($items as $item): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item['product_name'] ?? 'Deleted Product'); ?></td>
                                                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                                        <td class="text-end"><?php echo formatCurrency($item['price']); ?></td>
                                                                        <td class="text-end"><strong><?php echo formatCurrency($item['quantity'] * $item['price']); ?></strong></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="statusModal<?php echo $order['order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-pen me-2 text-red"></i> Update Order #<?php echo $order['order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" class="mb-3">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                        <label class="form-label fw-bold">Order Status</label>
                                                        <div class="input-group">
                                                            <select name="status" class="form-select">
                                                                <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                                                                    <option value="<?php echo $s; ?>" <?php echo $order['order_status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" name="update_order_status" class="btn btn-danger">Update</button>
                                                        </div>
                                                    </form>
                                                    <hr>
                                                    <form method="POST">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                        <label class="form-label fw-bold">Payment Status</label>
                                                        <div class="input-group">
                                                            <select name="payment_status" class="form-select">
                                                                <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $ps): ?>
                                                                    <option value="<?php echo $ps; ?>" <?php echo $order['payment_status'] === $ps ? 'selected' : ''; ?>><?php echo ucfirst($ps); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" name="update_payment_status" class="btn btn-success">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
