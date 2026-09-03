<?php
$page_title = 'Hot Deals Management';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['add_deal'])) {
    verifyCsrf();
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $discount_text = sanitize($_POST['discount_text']);
    $coupon_code = sanitize($_POST['coupon_code']);
    $button_text = sanitize($_POST['button_text']);
    $button_link = sanitize($_POST['button_link']);
    $category = sanitize($_POST['category']);
    $priority = intval($_POST['priority']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $status = sanitize($_POST['status']);
    $shop_id = !empty($_POST['shop_id']) ? intval($_POST['shop_id']) : null;
    $created_by = intval($_SESSION['user_id']);

    $banner = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            if ($_FILES['banner_image']['size'] <= 5242880) {
                $filename = 'deal_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], __DIR__ . '/../uploads/' . $filename)) {
                    $banner = $filename;
                }
            }
        }
    }

    if ($shop_id === null) {
        $stmt = $conn->prepare("INSERT INTO hot_deals (shop_id, title, description, banner_image, discount_text, coupon_code, button_text, button_link, category, priority, start_date, end_date, status, created_by) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssisssi", $title, $description, $banner, $discount_text, $coupon_code, $button_text, $button_link, $category, $priority, $start_date, $end_date, $status, $created_by);
    } else {
        $stmt = $conn->prepare("INSERT INTO hot_deals (shop_id, title, description, banner_image, discount_text, coupon_code, button_text, button_link, category, priority, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssisssi", $shop_id, $title, $description, $banner, $discount_text, $coupon_code, $button_text, $button_link, $category, $priority, $start_date, $end_date, $status, $created_by);
    }
    if ($stmt->execute()) {
        setFlash('success', 'Hot deal added successfully.');
    } else {
        setFlash('danger', 'Error adding hot deal.');
    }
    $stmt->close();
    redirect('hot-deals.php');
}

if (isset($_POST['update_deal'])) {
    verifyCsrf();
    $id = intval($_POST['deal_id']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $discount_text = sanitize($_POST['discount_text']);
    $coupon_code = sanitize($_POST['coupon_code']);
    $button_text = sanitize($_POST['button_text']);
    $button_link = sanitize($_POST['button_link']);
    $category = sanitize($_POST['category']);
    $priority = intval($_POST['priority']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $status = sanitize($_POST['status']);
    $shop_id = !empty($_POST['shop_id']) ? intval($_POST['shop_id']) : null;

    $banner = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array(strtolower($ext), $allowed) && $_FILES['banner_image']['size'] <= 5242880) {
            $filename = 'deal_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], __DIR__ . '/../uploads/' . $filename)) {
                $old = $conn->query("SELECT banner_image FROM hot_deals WHERE id = $id")->fetch_assoc();
                if ($old && $old['banner_image'] && file_exists(__DIR__ . '/../uploads/' . $old['banner_image'])) {
                    unlink(__DIR__ . '/../uploads/' . $old['banner_image']);
                }
                $banner = $filename;
            }
        }
    }

    if ($banner !== null) {
        if ($shop_id !== null) {
            $stmt = $conn->prepare("UPDATE hot_deals SET shop_id=?, title=?, description=?, banner_image=?, discount_text=?, coupon_code=?, button_text=?, button_link=?, category=?, priority=?, start_date=?, end_date=?, status=? WHERE id=?");
            $stmt->bind_param("issssssssisiii", $shop_id, $title, $description, $banner, $discount_text, $coupon_code, $button_text, $button_link, $category, $priority, $start_date, $end_date, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE hot_deals SET shop_id=NULL, title=?, description=?, banner_image=?, discount_text=?, coupon_code=?, button_text=?, button_link=?, category=?, priority=?, start_date=?, end_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssisiii", $title, $description, $banner, $discount_text, $coupon_code, $button_text, $button_link, $category, $priority, $start_date, $end_date, $status, $id);
        }
    } else {
        if ($shop_id !== null) {
            $stmt = $conn->prepare("UPDATE hot_deals SET shop_id=?, title=?, description=?, discount_text=?, coupon_code=?, button_text=?, button_link=?, category=?, priority=?, start_date=?, end_date=?, status=? WHERE id=?");
            $stmt->bind_param("issssssssisii", $shop_id, $title, $description, $discount_text, $coupon_code, $button_text, $button_link, $category, $priority, $start_date, $end_date, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE hot_deals SET shop_id=NULL, title=?, description=?, discount_text=?, coupon_code=?, button_text=?, button_link=?, category=?, priority=?, start_date=?, end_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssssisii", $title, $description, $discount_text, $coupon_code, $button_text, $button_link, $category, $priority, $start_date, $end_date, $status, $id);
        }
    }
    if ($stmt->execute()) {
        setFlash('success', 'Hot deal updated successfully.');
    } else {
        setFlash('danger', 'Error updating hot deal.');
    }
    $stmt->close();
    redirect('hot-deals.php');
}

if (isset($_POST['delete_deal'])) {
    verifyCsrf();
    $id = intval($_POST['deal_id']);
    $old = $conn->query("SELECT banner_image FROM hot_deals WHERE id = $id")->fetch_assoc();
    if ($old && $old['banner_image'] && file_exists(__DIR__ . '/../uploads/' . $old['banner_image'])) {
        unlink(__DIR__ . '/../uploads/' . $old['banner_image']);
    }
    $stmt = $conn->prepare("DELETE FROM hot_deals WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Hot deal deleted.');
    redirect('hot-deals.php');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (hd.title LIKE ? OR hd.description LIKE ? OR hd.category LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}
if ($statusFilter !== '') {
    $where .= " AND hd.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$countQuery = "SELECT COUNT(*) as total FROM hot_deals hd $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT hd.*, s.shop_name FROM hot_deals hd LEFT JOIN shops s ON hd.shop_id = s.shop_id $where ORDER BY hd.priority ASC, hd.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$deals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$shops = $conn->query("SELECT shop_id, shop_name FROM shops WHERE status='active' ORDER BY shop_name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-fire"></i> Hot Deals</h2>
                <p class="admin-page-subtitle">Manage promotional banners and deals</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-fire"></i> <?php echo $totalRows; ?> deals</span>
                <button class="dash-btn-action dash-btn-primary" data-bs-toggle="modal" data-bs-target="#addDealModal">
                    <i class="fas fa-plus me-1"></i> Add Deal
                </button>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by title, description, category..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <select name="status" style="min-width:160px;">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="hot-deals.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($deals)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-fire"></i>
                            <h4>No hot deals found</h4>
                            <p>Create your first promotional deal to attract customers</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Banner</th>
                                    <th>Deal</th>
                                    <th>Shop</th>
                                    <th>Discount</th>
                                    <th>Period</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deals as $i => $deal): ?>
                                    <tr>
                                        <td>
                                            <?php if ($deal['banner_image']): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $deal['banner_image']; ?>" alt="" style="width:80px;height:50px;object-fit:cover;border-radius:6px;">
                                            <?php else: ?>
                                                <div style="width:80px;height:50px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-info-name"><?php echo htmlspecialchars($deal['title']); ?></div>
                                                <div class="cell-info-sub">#<?php echo $deal['id']; ?> <?php echo $deal['category'] ? '&middot; ' . htmlspecialchars($deal['category']) : ''; ?></div>
                                            </div>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($deal['shop_name'] ?? 'Admin'); ?></small></td>
                                        <td><span class="status-pill status-active"><?php echo htmlspecialchars($deal['discount_text'] ?: 'N/A'); ?></span></td>
                                        <td>
                                            <small style="color:#666;">
                                                <?php echo date('M d', strtotime($deal['start_date'])); ?> - <?php echo date('M d, Y', strtotime($deal['end_date'])); ?>
                                            </small>
                                        </td>
                                        <td><?php echo $deal['priority']; ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo $deal['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo ucfirst($deal['status']); ?></span>
                                        </td>
                                        <td>
                                            <div class="action-btns" style="justify-content:flex-end;">
                                                <button class="action-btn action-btn-view" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $deal['id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $deal['id']; ?>" title="Edit"><i class="fas fa-pen"></i></button>
                                                <button class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $deal['id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="viewModal<?php echo $deal['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-fire me-2 text-red"></i><?php echo htmlspecialchars($deal['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if ($deal['banner_image']): ?>
                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $deal['banner_image']; ?>" alt="" style="width:100%;max-height:300px;object-fit:cover;border-radius:8px;margin-bottom:16px;">
                                                    <?php endif; ?>
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><div class="detail-label">ID</div><div class="detail-value">#<?php echo $deal['id']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Shop</div><div class="detail-value"><?php echo htmlspecialchars($deal['shop_name'] ?? 'Admin'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Discount</div><div class="detail-value"><?php echo htmlspecialchars($deal['discount_text'] ?: 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Coupon</div><div class="detail-value"><?php echo htmlspecialchars($deal['coupon_code'] ?: 'None'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Category</div><div class="detail-value"><?php echo htmlspecialchars($deal['category'] ?: 'All'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Button</div><div class="detail-value"><?php echo htmlspecialchars($deal['button_text']); ?> &rarr; <?php echo htmlspecialchars($deal['button_link']); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Start</div><div class="detail-value"><?php echo $deal['start_date']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">End</div><div class="detail-value"><?php echo $deal['end_date']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Priority</div><div class="detail-value"><?php echo $deal['priority']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="status-pill status-<?php echo $deal['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo ucfirst($deal['status']); ?></span></div></div>
                                                    </div>
                                                    <?php if ($deal['description']): ?>
                                                        <div class="detail-item" style="margin-top:16px;"><div class="detail-label">Description</div><div class="detail-value"><?php echo htmlspecialchars($deal['description']); ?></div></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="editModal<?php echo $deal['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-pen me-2 text-red"></i> Edit Deal</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-8">
                                                                <label class="form-label fw-bold">Deal Title *</label>
                                                                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($deal['title']); ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label fw-bold">Discount Text</label>
                                                                <input type="text" name="discount_text" class="form-control" placeholder="e.g. 20% OFF" value="<?php echo htmlspecialchars($deal['discount_text']); ?>">
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label fw-bold">Description</label>
                                                                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($deal['description']); ?></textarea>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Banner Image</label>
                                                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                                                                <small class="text-muted">Leave empty to keep current image</small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Shop</label>
                                                                <select name="shop_id" class="form-select">
                                                                    <option value="">Admin (No Shop)</option>
                                                                    <?php foreach ($shops as $s): ?>
                                                                        <option value="<?php echo $s['shop_id']; ?>" <?php echo $deal['shop_id'] == $s['shop_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['shop_name']); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label fw-bold">Coupon Code</label>
                                                                <input type="text" name="coupon_code" class="form-control" placeholder="e.g. SAVE20" value="<?php echo htmlspecialchars($deal['coupon_code']); ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label fw-bold">Button Text</label>
                                                                <input type="text" name="button_text" class="form-control" value="<?php echo htmlspecialchars($deal['button_text']); ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label fw-bold">Button Link</label>
                                                                <input type="text" name="button_link" class="form-control" value="<?php echo htmlspecialchars($deal['button_link']); ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label fw-bold">Category</label>
                                                                <input type="text" name="category" class="form-control" placeholder="e.g. Brake Parts" value="<?php echo htmlspecialchars($deal['category']); ?>">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label fw-bold">Priority</label>
                                                                <input type="number" name="priority" class="form-control" min="0" value="<?php echo $deal['priority']; ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Status</label>
                                                                <select name="status" class="form-select">
                                                                    <option value="active" <?php echo $deal['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                                    <option value="inactive" <?php echo $deal['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label fw-bold">Start Date *</label>
                                                                <input type="date" name="start_date" class="form-control" required value="<?php echo $deal['start_date']; ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label fw-bold">End Date *</label>
                                                                <input type="date" name="end_date" class="form-control" required value="<?php echo $deal['end_date']; ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_deal" class="btn btn-danger">Update Deal</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="deleteModal<?php echo $deal['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-red"></i> Delete Deal</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($deal['title']); ?></strong>?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_deal" class="btn btn-danger">Delete</button>
                                                    </div>
                                                </form>
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
                <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>
</div>

<div class="modal fade" id="addDealModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2 text-red"></i> Add Hot Deal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Deal Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Summer Brake Sale">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Discount Text</label>
                            <input type="text" name="discount_text" class="form-control" placeholder="e.g. 20% OFF">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this deal"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Banner Image *</label>
                            <input type="file" name="banner_image" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG or WebP. Max 5MB.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shop</label>
                            <select name="shop_id" class="form-select">
                                <option value="">Admin (No Shop)</option>
                                <?php foreach ($shops as $s): ?>
                                    <option value="<?php echo $s['shop_id']; ?>"><?php echo htmlspecialchars($s['shop_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Coupon Code</label>
                            <input type="text" name="coupon_code" class="form-control" placeholder="e.g. SAVE20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Button Text</label>
                            <input type="text" name="button_text" class="form-control" value="Shop Now">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Button Link</label>
                            <input type="text" name="button_link" class="form-control" value="#" placeholder="URL or #">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Brake Parts">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Priority</label>
                            <input type="number" name="priority" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Start Date *</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">End Date *</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_deal" class="btn btn-danger">Add Deal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
