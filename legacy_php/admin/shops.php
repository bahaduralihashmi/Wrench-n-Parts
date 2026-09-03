<?php
$page_title = 'Manage Shops';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['update_status'])) {
    verifyCsrf();
    $sid = intval($_POST['shop_id']);
    $status = sanitize($_POST['status']);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE shops SET status = ? WHERE shop_id = ?");
        $stmt->bind_param("si", $status, $sid);
        $stmt->execute();
        $stmt->close();

        $getUser = $conn->prepare("SELECT user_id FROM shops WHERE shop_id = ?");
        $getUser->bind_param("i", $sid);
        $getUser->execute();
        $res = $getUser->get_result();
        $shopRow = $res->fetch_assoc();
        $getUser->close();

        if ($shopRow) {
            $updUser = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $updUser->bind_param("si", $status, $shopRow['user_id']);
            $updUser->execute();
            $updUser->close();
        }

        $conn->commit();
        setFlash('success', 'Shop and owner status updated to ' . ucfirst($status) . '.');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('danger', 'Error updating shop status.');
    }
    redirect('shops.php');
}

if (isset($_POST['delete_shop'])) {
    verifyCsrf();
    $sid = intval($_POST['shop_id']);
    $conn->begin_transaction();
    try {
        $del_products = $conn->prepare("DELETE FROM products WHERE shop_id = ?");
        $del_products->bind_param("i", $sid);
        $del_products->execute();
        $del_products->close();
        $del_shop = $conn->prepare("DELETE FROM shops WHERE shop_id = ?");
        $del_shop->bind_param("i", $sid);
        $del_shop->execute();
        $del_shop->close();
        $conn->commit();
        setFlash('success', 'Shop and associated products deleted successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('danger', 'Error deleting shop.');
    }
    redirect('shops.php');
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
    $where .= " AND (shop_name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}
if ($statusFilter !== '') {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$countQuery = "SELECT COUNT(*) as total FROM shops $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT s.*, u.name as owner_name FROM shops s LEFT JOIN users u ON s.user_id = u.user_id $where ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$shops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$shopIds = array_column($shops, 'shop_id');
$shopImages = [];
if (!empty($shopIds)) {
    $placeholders = implode(',', array_fill(0, count($shopIds), '?'));
    $imgTypes = str_repeat('i', count($shopIds));
    $imgStmt = $conn->prepare("SELECT * FROM shop_images WHERE shop_id IN ($placeholders)");
    $imgBindParams = $shopIds;
    $imgStmt->bind_param($imgTypes, ...$imgBindParams);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    while ($img = $imgResult->fetch_assoc()) {
        $shopImages[$img['shop_id']][] = $img;
    }
    $imgStmt->close();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-store"></i> Manage Shops</h2>
                <p class="admin-page-subtitle">Approve, review and manage all registered shops</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-store"></i> <?php echo $totalRows; ?> shops</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by name or description..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <select name="status" style="min-width:160px;">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="shops.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($shops)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-store"></i>
                            <h4>No shops found</h4>
                            <p>Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Shop</th>
                                    <th>Owner</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shops as $i => $shop): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($shop['shop_name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($shop['shop_name']); ?></div>
                                                    <div class="cell-info-sub">#<?php echo $shop['shop_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($shop['owner_name'] ?? 'N/A'); ?></td>
                                        <td><small><?php echo htmlspecialchars($shop['contact'] ?? 'N/A'); ?></small></td>
                                        <td>
                                            <?php
                                            $sClass = 'pending';
                                            $shopStatus = $shop['status'] ?? 'pending';
                                            if ($shopStatus === 'approved') $sClass = 'approved';
                                            elseif ($shopStatus === 'rejected') $sClass = 'rejected';
                                            elseif ($shopStatus === 'banned') $sClass = 'banned';
                                            ?>
                                            <span class="status-pill status-<?php echo $sClass; ?>"><?php echo ucfirst($shopStatus); ?></span>
                                            <?php if ($shopStatus === 'pending'): ?>
                                                <div style="margin-top:6px;display:flex;gap:4px;">
                                                    <form method="POST" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-success" style="padding:2px 8px;font-size:0.7rem;"><i class="fas fa-check"></i> Approve</button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-danger" style="padding:2px 8px;font-size:0.7rem;"><i class="fas fa-times"></i> Reject</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><small style="color:#999;"><?php echo isset($shop['created_at']) ? timeAgo($shop['created_at']) : 'N/A'; ?></small></td>
                                        <td>
                                            <div class="action-btns" style="justify-content:flex-end;">
                                                <button class="action-btn action-btn-view" data-bs-toggle="modal" data-bs-target="#shopModal<?php echo $shop['shop_id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $shop['shop_id']; ?>" title="Change Status"><i class="fas fa-pen"></i></button>
                                                <button class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $shop['shop_id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="shopModal<?php echo $shop['shop_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-store me-2 text-red"></i> <?php echo htmlspecialchars($shop['shop_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><div class="detail-label">Shop ID</div><div class="detail-value">#<?php echo $shop['shop_id']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Owner</div><div class="detail-value"><?php echo htmlspecialchars($shop['owner_name'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value"><?php echo htmlspecialchars($shop['contact'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value"><?php echo htmlspecialchars($shop['location'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="status-pill status-<?php echo $sClass; ?>"><?php echo ucfirst($shopStatus); ?></span></div></div>
                                                        <div class="detail-item"><div class="detail-label">Created</div><div class="detail-value"><?php echo isset($shop['created_at']) ? date('M d, Y', strtotime($shop['created_at'])) : 'N/A'; ?></div></div>
                                                    </div>
                                                    <?php if (!empty($shop['description'])): ?>
                                                        <div class="detail-item" style="margin-top:16px;"><div class="detail-label">Description</div><div class="detail-value"><?php echo htmlspecialchars($shop['description']); ?></div></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($shop['location'])): ?>
                                                        <div class="detail-item" style="margin-top:8px;"><div class="detail-label">Location</div><div class="detail-value"><?php echo htmlspecialchars($shop['location']); ?></div></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($shop['logo']) || !empty($shop['cnc_front']) || !empty($shop['cnc_back']) || !empty($shop['certificate'])): ?>
                                                        <div style="margin-top:20px;">
                                                            <div class="detail-label" style="font-weight:700;margin-bottom:12px;font-size:0.9rem;color:#1a1a2e;">Uploaded Documents</div>
                                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                                                <?php if (!empty($shop['logo'])): ?>
                                                                    <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                        <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">Shop Picture</div>
                                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop['logo']); ?>" alt="Shop Picture" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($shop['cnc_front'])): ?>
                                                                    <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                        <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">CNIC Front</div>
                                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop['cnc_front']); ?>" alt="CNIC Front" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($shop['cnc_back'])): ?>
                                                                    <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                        <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">CNIC Back</div>
                                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop['cnc_back']); ?>" alt="CNIC Back" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($shop['certificate'])): ?>
                                                                    <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                        <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">Certificate</div>
                                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($shop['certificate']); ?>" alt="Certificate" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($shopImages[$shop['shop_id']])): ?>
                                                        <div style="margin-top:20px;">
                                                            <div class="detail-label" style="font-weight:700;margin-bottom:12px;font-size:0.9rem;color:#1a1a2e;">Shop Gallery</div>
                                                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                                                                <?php foreach ($shopImages[$shop['shop_id']] as $img): ?>
                                                                    <div style="background:#f8f9fa;border-radius:10px;padding:8px;text-align:center;border:1px solid #e8eaed;">
                                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Shop Image" style="width:100%;height:100px;border-radius:8px;object-fit:cover;">
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="statusModal<?php echo $shop['shop_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-pen me-2 text-red"></i> Change Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
                                                        <p style="margin-bottom:12px;">Update status for <strong><?php echo htmlspecialchars($shop['shop_name']); ?></strong></p>
                                                        <select name="status" class="form-select">
                                                            <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                                                                <option value="<?php echo $s; ?>" <?php echo ($shop['status'] ?? 'pending') === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_status" class="btn btn-danger">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="deleteModal<?php echo $shop['shop_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-red"></i> Delete Shop</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
                                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($shop['shop_name']); ?></strong>? This will also remove associated products.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_shop" class="btn btn-danger">Delete Shop</button>
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

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
