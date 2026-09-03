<?php
$page_title = 'Inventory Management';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();

    if ($_POST['action'] === 'update_single') {
        $product_id = (int)$_POST['product_id'];
        $new_stock = (int)$_POST['new_stock'];
        if ($new_stock < 0) $new_stock = 0;
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE product_id = ? AND shop_id = ?");
        $stmt->bind_param("iii", $new_stock, $product_id, $shop_id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Stock updated successfully!');
        redirect(SITE_URL . '/shopkeeper/inventory.php');
    }

    if ($_POST['action'] === 'bulk_update') {
        $product_ids = $_POST['bulk_product_id'] ?? [];
        $new_stocks = $_POST['bulk_stock'] ?? [];
        $updated = 0;
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = (int)$product_ids[$i];
            $stk = (int)$new_stocks[$i];
            if ($stk < 0) $stk = 0;
            $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE product_id = ? AND shop_id = ?");
            $stmt->bind_param("iii", $stk, $pid, $shop_id);
            $stmt->execute();
            if ($stmt->affected_rows >= 0) $updated++;
            $stmt->close();
        }
        setFlash('success', "$updated product stock levels updated!");
        redirect(SITE_URL . '/shopkeeper/inventory.php');
    }
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_low = isset($_GET['low_stock']);

$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.shop_id = ?";
$params = [$shop_id];
$types = "i";

if ($search) {
    $sql .= " AND (p.product_name LIKE ? OR p.brand LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($filter_low) {
    $sql .= " AND p.stock < 5";
}

$sql .= " ORDER BY p.stock ASC, p.product_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_products = count($products);
$low_stock_count = 0;
$out_of_stock = 0;
$total_stock_value = 0;

foreach ($products as $p) {
    if ($p['stock'] < 5) $low_stock_count++;
    if ($p['stock'] == 0) $out_of_stock++;
    $total_stock_value += $p['stock'] * $p['price'];
}

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
            <a class="dash-nav-link active" href="inventory.php"><i class="fas fa-warehouse"></i>Inventory</a>
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
            <h2 class="fw-bold mb-0"><i class="fas fa-warehouse me-2"></i>Inventory</h2>
            <div class="dash-header-actions">
                <button class="dash-btn-action dash-btn-primary" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                    <i class="fas fa-layer-group me-1"></i>Bulk Update
                </button>
            </div>
        </div>

        <div class="dash-stats">
            <div class="dash-stat-card">
                <div class="dash-stat-label">Total Products</div>
                <div class="dash-stat-number stat-red"><?php echo $total_products; ?></div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-label">Low Stock (< 5)</div>
                <div class="dash-stat-number stat-yellow"><?php echo $low_stock_count; ?></div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-label">In Stock</div>
                <div class="dash-stat-number stat-green"><?php echo $total_products - $out_of_stock; ?></div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-label">Stock Value</div>
                <div class="dash-stat-number stat-blue"><?php echo formatCurrency($total_stock_value); ?></div>
            </div>
        </div>

        <div class="dash-card mb-4">
            <div class="dash-card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Search Products</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or brand..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="low_stock" id="lowStockFilter" <?php echo $filter_low ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="lowStockFilter">
                                <span class="text-danger">Show Low Stock Only</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="dash-btn-action dash-btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="dash-card">
                <div class="dash-card-body text-center py-5">
                    <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No products found</h5>
                </div>
            </div>
        <?php else: ?>
            <div class="dash-card">
                <div class="dash-card-body p-0">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Current Stock</th>
                                    <th>Stock Value</th>
                                    <th>Status</th>
                                    <th>Quick Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $prod): ?>
                                    <tr <?php echo $prod['stock'] < 5 ? 'class="table-warning"' : ''; ?>>
                                        <td class="fw-bold"><?php echo htmlspecialchars($prod['product_name']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($prod['category_name'] ?? 'N/A'); ?></small></td>
                                        <td><?php echo htmlspecialchars($prod['brand']); ?></td>
                                        <td class="product-price"><?php echo formatCurrency($prod['price']); ?></td>
                                        <td>
                                            <?php if ($prod['stock'] == 0): ?>
                                                <span class="dash-badge dash-badge-red"><?php echo $prod['stock']; ?></span>
                                            <?php elseif ($prod['stock'] < 5): ?>
                                                <span class="dash-badge dash-badge-orange"><?php echo $prod['stock']; ?></span>
                                            <?php else: ?>
                                                <span class="dash-badge dash-badge-green"><?php echo $prod['stock']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo formatCurrency($prod['stock'] * $prod['price']); ?></strong></td>
                                        <td>
                                            <?php if ($prod['stock'] == 0): ?>
                                                <span class="stock-out"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>
                                            <?php elseif ($prod['stock'] < 5): ?>
                                                <span class="stock-low"><i class="fas fa-exclamation-triangle me-1"></i>Low Stock</span>
                                            <?php else: ?>
                                                <span class="stock-available"><i class="fas fa-check-circle me-1"></i>In Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                                <form method="POST" class="d-flex align-items-center gap-1">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="update_single">
                                                <input type="hidden" name="product_id" value="<?php echo $prod['product_id']; ?>">
                                                <input type="number" name="new_stock" value="<?php echo $prod['stock']; ?>" min="0" class="form-control form-control-sm" style="width:80px;">
                                                <button type="submit" class="dash-btn-action dash-btn-outline btn-sm-modern" title="Update" style="padding:6px 10px;"><i class="fas fa-check"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="bulk_update">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-layer-group me-2"></i>Bulk Stock Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Update stock quantities for multiple products at once.</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>New Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $all_products = $conn->prepare("SELECT product_id, product_name, stock FROM products WHERE shop_id = ? ORDER BY product_name ASC");
                                $all_products->bind_param("i", $shop_id);
                                $all_products->execute();
                                $all_prods = $all_products->get_result()->fetch_all(MYSQLI_ASSOC);
                                $all_products->close();
                                foreach ($all_prods as $ap):
                                ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($ap['product_name']); ?></td>
                                        <td>
                                            <?php if ($ap['stock'] < 5): ?>
                                                <span class="text-danger fw-bold"><?php echo $ap['stock']; ?></span>
                                            <?php else: ?>
                                                <?php echo $ap['stock']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="hidden" name="bulk_product_id[]" value="<?php echo $ap['product_id']; ?>">
                                            <input type="number" name="bulk_stock[]" value="<?php echo $ap['stock']; ?>" min="0" class="form-control form-control-sm" style="width:100px;">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save All Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
