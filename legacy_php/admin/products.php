<?php
$page_title = 'Manage Products';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$catFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (p.product_name LIKE ? OR p.brand LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}
if ($catFilter > 0) {
    $where .= " AND p.category_id = ?";
    $params[] = $catFilter;
    $types .= 'i';
}

$countQuery = "SELECT COUNT(*) as total FROM products p $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT p.*, s.shop_name, c.category_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id LEFT JOIN categories c ON p.category_id = c.category_id $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-box"></i> Manage Products</h2>
                <p class="admin-page-subtitle">Browse all products across the platform</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-box"></i> <?php echo $totalRows; ?> products</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by product name or brand..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <select name="category" style="min-width:180px;">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $catFilter == $cat['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="products.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($products)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-box"></i>
                            <h4>No products found</h4>
                            <p>Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Shop</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $i => $product): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($product['product_name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                                    <div class="cell-info-sub"><?php echo htmlspecialchars($product['brand'] ?? 'General'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['shop_name'] ?? 'N/A'); ?></td>
                                        <td><span class="role-badge role-customer"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span></td>
                                        <td>
                                            <strong><?php echo formatCurrency($product['price']); ?></strong>
                                            <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                                                <br><small style="color:#059669;font-weight:600;"><?php echo formatCurrency($product['discount_price']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['stock'] > 10): ?>
                                                <span class="status-pill status-active"><?php echo $product['stock']; ?> in stock</span>
                                            <?php elseif ($product['stock'] > 0): ?>
                                                <span class="status-pill status-pending"><?php echo $product['stock']; ?> left</span>
                                            <?php else: ?>
                                                <span class="status-pill status-banned">Out of stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($product['status'] ?? 'available') === 'available'): ?>
                                                <span class="status-pill status-active">Available</span>
                                            <?php else: ?>
                                                <span class="status-pill status-inactive"><?php echo ucfirst($product['status'] ?? 'available'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $catFilter; ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $catFilter; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $catFilter; ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
