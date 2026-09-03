<?php
$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['update_status'])) {
    verifyCsrf();
    $uid = intval($_POST['user_id']);
    $status = sanitize($_POST['status']);
    $validStatuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $validStatuses)) $status = 'pending';
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $status, $uid);
    $stmt->execute();
    $stmt->close();
    $shopStmt = $conn->prepare("UPDATE shops SET status = ? WHERE user_id = ?");
    $shopStmt->bind_param("si", $status, $uid);
    $shopStmt->execute();
    $shopStmt->close();
    $wsStmt = $conn->prepare("UPDATE workshops SET status = ? WHERE user_id = ?");
    $wsStmt->bind_param("si", $status, $uid);
    $wsStmt->execute();
    $wsStmt->close();
    setFlash('success', 'User status updated to ' . ucfirst($status) . '.');
    redirect('users.php');
}

if (isset($_POST['approve_user'])) {
    verifyCsrf();
    $uid = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    $shopStmt = $conn->prepare("UPDATE shops SET status = 'approved' WHERE user_id = ?");
    $shopStmt->bind_param("i", $uid);
    $shopStmt->execute();
    $shopStmt->close();
    $wsStmt = $conn->prepare("UPDATE workshops SET status = 'approved' WHERE user_id = ?");
    $wsStmt->bind_param("i", $uid);
    $wsStmt->execute();
    $wsStmt->close();
    setFlash('success', 'User approved successfully.');
    redirect('users.php');
}

if (isset($_POST['reject_user'])) {
    verifyCsrf();
    $uid = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    $shopStmt = $conn->prepare("UPDATE shops SET status = 'rejected' WHERE user_id = ?");
    $shopStmt->bind_param("i", $uid);
    $shopStmt->execute();
    $shopStmt->close();
    $wsStmt = $conn->prepare("UPDATE workshops SET status = 'rejected' WHERE user_id = ?");
    $wsStmt->bind_param("i", $uid);
    $wsStmt->execute();
    $wsStmt->close();
    setFlash('success', 'User rejected.');
    redirect('users.php');
}

if (isset($_POST['delete_user'])) {
    verifyCsrf();
    $uid = intval($_POST['user_id']);
    $conn->begin_transaction();
    try {
        $shopStmt = $conn->prepare("SELECT shop_id FROM shops WHERE user_id = ?");
        $shopStmt->bind_param("i", $uid);
        $shopStmt->execute();
        $shops = $shopStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $shopStmt->close();

        $delProd = $conn->prepare("DELETE FROM products WHERE shop_id = ?");
        $delShop = $conn->prepare("DELETE FROM shops WHERE shop_id = ?");
        foreach ($shops as $s) {
            $delProd->bind_param("i", $s['shop_id']);
            $delProd->execute();
            $delShop->bind_param("i", $s['shop_id']);
            $delShop->execute();
        }
        $delProd->close();
        $delShop->close();

        $delWish = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $delWish->bind_param("i", $uid);
        $delWish->execute();
        $delWish->close();

        $delCart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $delCart->bind_param("i", $uid);
        $delCart->execute();
        $delCart->close();

        $delNotif = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $delNotif->bind_param("i", $uid);
        $delNotif->execute();
        $delNotif->close();

        $delChat = $conn->prepare("DELETE FROM chatbot_logs WHERE user_id = ?");
        $delChat->bind_param("i", $uid);
        $delChat->execute();
        $delChat->close();

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        setFlash('success', 'User and all related data deleted successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('danger', 'Error deleting user.');
    }
    redirect('users.php');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "WHERE role IN ('shopkeeper','workshop','admin','management')";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}
if ($roleFilter !== '') {
    $where .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

$countQuery = "SELECT COUNT(*) as total FROM users $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-users"></i> Manage Users</h2>
                <p class="admin-page-subtitle">View, edit and manage all platform users</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-users"></i> <?php echo $totalRows; ?> users</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <select name="role" style="min-width:160px;">
                    <option value="">All Roles</option>
                    <?php foreach (['shopkeeper', 'workshop', 'admin', 'management'] as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo $roleFilter === $r ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="users.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($users)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-users"></i>
                            <h4>No users found</h4>
                            <p>Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $i => $u): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($u['name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($u['name']); ?></div>
                                                    <div class="cell-info-sub"><?php echo htmlspecialchars($u['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="role-badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                        <td>
                                            <?php
                                            $sClass = 'pending';
                                            $statusVal = $u['status'] ?? 'pending';
                                            if ($statusVal === 'active' || $statusVal === 'approved') $sClass = 'approved';
                                            elseif ($statusVal === 'rejected') $sClass = 'rejected';
                                            elseif ($statusVal === 'banned') $sClass = 'banned';
                                            ?>
                                            <span class="status-pill status-<?php echo $sClass; ?>"><?php echo ucfirst($statusVal); ?></span>
                                        </td>
                                        <td><small style="color:#999;"><?php echo timeAgo($u['created_at']); ?></small></td>
                                        <td>
                                            <div class="action-btns" style="justify-content:flex-end;">
                                                <button class="action-btn action-btn-view" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $u['user_id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                                <?php if ($statusVal === 'pending'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                        <button type="submit" name="approve_user" class="action-btn" title="Approve" style="background:#28a745;color:#fff;border:none;"><i class="fas fa-check"></i></button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                        <button type="submit" name="reject_user" class="action-btn" title="Reject" style="background:#dc3545;color:#fff;border:none;"><i class="fas fa-times"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <button class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $u['user_id']; ?>" title="Change Status"><i class="fas fa-pen"></i></button>
                                                <button class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $u['user_id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="userModal<?php echo $u['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-user me-2 text-red"></i> User #<?php echo $u['user_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><div class="detail-label">Name</div><div class="detail-value"><?php echo htmlspecialchars($u['name']); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value"><?php echo htmlspecialchars($u['email']); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value"><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Role</div><div class="detail-value"><span class="role-badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></div></div>
                                                        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="status-pill status-<?php echo $sClass; ?>"><?php echo ucfirst($statusVal); ?></span></div></div>
                                                        <div class="detail-item"><div class="detail-label">Joined</div><div class="detail-value"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></div></div>
                                                    </div>
                                                    <?php if (!empty($u['address'])): ?>
                                                        <div class="detail-item" style="margin-top:16px;"><div class="detail-label">Address</div><div class="detail-value"><?php echo htmlspecialchars($u['address']); ?></div></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="statusModal<?php echo $u['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-pen me-2 text-red"></i> Change Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                        <p style="margin-bottom:12px;">Update status for <strong><?php echo htmlspecialchars($u['name']); ?></strong></p>
                                                        <select name="status" class="form-select">
                                                            <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                                                                <option value="<?php echo $s; ?>" <?php echo $statusVal === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
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

                                    <div class="modal fade" id="deleteModal<?php echo $u['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-red"></i> Delete User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($u['name']); ?></strong>? This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
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
                <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
