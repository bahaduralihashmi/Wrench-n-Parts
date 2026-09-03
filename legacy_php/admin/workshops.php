<?php
$page_title = 'Manage Workshops';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['update_status'])) {
    verifyCsrf();
    $wid = intval($_POST['workshop_id']);
    $status = sanitize($_POST['status']);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE workshops SET status = ? WHERE workshop_id = ?");
        $stmt->bind_param("si", $status, $wid);
        $stmt->execute();
        $stmt->close();

        $getUser = $conn->prepare("SELECT user_id FROM workshops WHERE workshop_id = ?");
        $getUser->bind_param("i", $wid);
        $getUser->execute();
        $res = $getUser->get_result();
        $wsRow = $res->fetch_assoc();
        $getUser->close();

        if ($wsRow) {
            $updUser = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
            $updUser->bind_param("si", $status, $wsRow['user_id']);
            $updUser->execute();
            $updUser->close();
        }

        $conn->commit();
        setFlash('success', 'Workshop and owner status updated to ' . ucfirst($status) . '.');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('danger', 'Error updating workshop status.');
    }
    redirect('workshops.php');
}

if (isset($_POST['delete_workshop'])) {
    verifyCsrf();
    $wid = intval($_POST['workshop_id']);
    $conn->begin_transaction();
    try {
        $delAppt = $conn->prepare("DELETE FROM appointments WHERE workshop_id = ?");
        $delAppt->bind_param("i", $wid);
        $delAppt->execute();
        $delAppt->close();

        $delReview = $conn->prepare("DELETE FROM reviews WHERE workshop_id = ?");
        $delReview->bind_param("i", $wid);
        $delReview->execute();
        $delReview->close();

        $stmt = $conn->prepare("DELETE FROM workshops WHERE workshop_id = ?");
        $stmt->bind_param("i", $wid);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        setFlash('success', 'Workshop and associated data deleted successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('danger', 'Error deleting workshop.');
    }
    redirect('workshops.php');
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
    $where .= " AND (workshop_name LIKE ? OR description LIKE ? OR services LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}
if ($statusFilter !== '') {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$countQuery = "SELECT COUNT(*) as total FROM workshops $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT w.*, u.name as owner_name FROM workshops w LEFT JOIN users u ON w.user_id = u.user_id $where ORDER BY w.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$workshops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$wsIds = array_column($workshops, 'workshop_id');
$wsImages = [];
if (!empty($wsIds)) {
    $placeholders = implode(',', array_fill(0, count($wsIds), '?'));
    $imgTypes = str_repeat('i', count($wsIds));
    $imgStmt = $conn->prepare("SELECT * FROM workshop_images WHERE workshop_id IN ($placeholders)");
    $imgStmt->bind_param($imgTypes, ...$wsIds);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    while ($img = $imgResult->fetch_assoc()) {
        $wsImages[$img['workshop_id']][] = $img;
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
                <h2 class="admin-page-title"><i class="fas fa-wrench"></i> Manage Workshops</h2>
                <p class="admin-page-subtitle">Approve, review and manage all registered workshops</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-wrench"></i> <?php echo $totalRows; ?> workshops</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by name, description, services..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <select name="status" style="min-width:160px;">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="workshops.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($workshops)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-wrench"></i>
                            <h4>No workshops found</h4>
                            <p>Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Workshop</th>
                                    <th>Owner</th>
                                    <th>Services</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workshops as $i => $ws): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($ws['workshop_name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($ws['workshop_name']); ?></div>
                                                    <div class="cell-info-sub">#<?php echo $ws['workshop_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($ws['owner_name'] ?? 'N/A'); ?></td>
                                        <td><small><?php echo htmlspecialchars($ws['services'] ?: 'General'); ?></small></td>
                                        <td>
                                            <?php
                                            $sClass = 'pending';
                                            $wsStatus = $ws['status'] ?? 'pending';
                                            if ($wsStatus === 'approved') $sClass = 'approved';
                                            elseif ($wsStatus === 'rejected') $sClass = 'rejected';
                                            ?>
                                            <span class="status-pill status-<?php echo $sClass; ?>"><?php echo ucfirst($wsStatus); ?></span>
                                            <?php if ($wsStatus === 'pending'): ?>
                                                <div style="margin-top:6px;display:flex;gap:4px;">
                                                    <form method="POST" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="workshop_id" value="<?php echo $ws['workshop_id']; ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-success" style="padding:2px 8px;font-size:0.7rem;"><i class="fas fa-check"></i> Approve</button>
                                                    </form>
                                                    <form method="POST" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="workshop_id" value="<?php echo $ws['workshop_id']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-danger" style="padding:2px 8px;font-size:0.7rem;"><i class="fas fa-times"></i> Reject</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><small style="color:#999;"><?php echo isset($ws['created_at']) ? timeAgo($ws['created_at']) : 'N/A'; ?></small></td>
                                        <td>
                                            <div class="action-btns" style="justify-content:flex-end;">
                                                <button class="action-btn action-btn-view" data-bs-toggle="modal" data-bs-target="#wsModal<?php echo $ws['workshop_id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $ws['workshop_id']; ?>" title="Change Status"><i class="fas fa-pen"></i></button>
                                                <button class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $ws['workshop_id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="wsModal<?php echo $ws['workshop_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-wrench me-2 text-red"></i> <?php echo htmlspecialchars($ws['workshop_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><div class="detail-label">Workshop ID</div><div class="detail-value">#<?php echo $ws['workshop_id']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Owner</div><div class="detail-value"><?php echo htmlspecialchars($ws['owner_name'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Services</div><div class="detail-value"><?php echo htmlspecialchars($ws['services'] ?: 'General'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value"><?php echo htmlspecialchars($ws['contact'] ?? 'N/A'); ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Working Hours</div><div class="detail-value"><?php echo htmlspecialchars(($ws['opening_time'] ?? 'N/A') . ' - ' . ($ws['closing_time'] ?? 'N/A')); ?></div></div>
                                                        <div class="detail-item">
                                                            <div class="detail-label">Rating</div>
                                                            <div class="detail-value">
                                                                <?php $rating = floatval($ws['rating'] ?? 0); for ($i2 = 1; $i2 <= 5; $i2++): ?>
                                                                    <i class="fas fa-star" style="color:<?php echo $i2 <= $rating ? '#f59e0b' : '#ddd'; ?>;font-size:0.85rem;"></i>
                                                                <?php endfor; ?>
                                                                <span style="margin-left:4px;font-size:0.85rem;color:#888;">(<?php echo $rating; ?>)</span>
                                                            </div>
                                                        </div>
                                                        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="status-pill status-<?php echo $sClass; ?>"><?php echo ucfirst($wsStatus); ?></span></div></div>
                                                    </div>
                                                <?php if (!empty($ws['description'])): ?>
                                                    <div class="detail-item" style="margin-top:16px;"><div class="detail-label">Description</div><div class="detail-value"><?php echo htmlspecialchars($ws['description']); ?></div></div>
                                                <?php endif; ?>
                                                <?php if (!empty($ws['location'])): ?>
                                                    <div class="detail-item" style="margin-top:8px;"><div class="detail-label">Location</div><div class="detail-value"><?php echo htmlspecialchars($ws['location']); ?></div></div>
                                                <?php endif; ?>
                                                <?php if (!empty($ws['logo']) || !empty($ws['cnc_front']) || !empty($ws['cnc_back']) || !empty($ws['certificate'])): ?>
                                                    <div style="margin-top:20px;">
                                                        <div class="detail-label" style="font-weight:700;margin-bottom:12px;font-size:0.9rem;color:#1a1a2e;">Uploaded Documents</div>
                                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                                            <?php if (!empty($ws['logo'])): ?>
                                                                <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                    <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">Workshop Picture</div>
                                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($ws['logo']); ?>" alt="Workshop Picture" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ws['cnc_front'])): ?>
                                                                <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                    <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">CNIC Front</div>
                                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($ws['cnc_front']); ?>" alt="CNIC Front" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ws['cnc_back'])): ?>
                                                                <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                    <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">CNIC Back</div>
                                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($ws['cnc_back']); ?>" alt="CNIC Back" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($ws['certificate'])): ?>
                                                                <div style="background:#f8f9fa;border-radius:10px;padding:10px;text-align:center;">
                                                                    <div style="font-size:0.75rem;color:#888;margin-bottom:6px;font-weight:600;">Certificate</div>
                                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($ws['certificate']); ?>" alt="Certificate" style="max-width:100%;max-height:140px;border-radius:8px;object-fit:cover;border:1px solid #e8eaed;">
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($wsImages[$ws['workshop_id']])): ?>
                                                    <div style="margin-top:20px;">
                                                        <div class="detail-label" style="font-weight:700;margin-bottom:12px;font-size:0.9rem;color:#1a1a2e;">Workshop Gallery</div>
                                                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                                                            <?php foreach ($wsImages[$ws['workshop_id']] as $img): ?>
                                                                <div style="background:#f8f9fa;border-radius:10px;padding:8px;text-align:center;border:1px solid #e8eaed;">
                                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Workshop Image" style="width:100%;height:100px;border-radius:8px;object-fit:cover;">
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

                                    <div class="modal fade" id="statusModal<?php echo $ws['workshop_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-pen me-2 text-red"></i> Change Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="workshop_id" value="<?php echo $ws['workshop_id']; ?>">
                                                        <p style="margin-bottom:12px;">Update status for <strong><?php echo htmlspecialchars($ws['workshop_name']); ?></strong></p>
                                                        <select name="status" class="form-select">
                                                            <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                                                                <option value="<?php echo $s; ?>" <?php echo ($ws['status'] ?? 'pending') === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
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

                                    <div class="modal fade" id="deleteModal<?php echo $ws['workshop_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-red"></i> Delete Workshop</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="workshop_id" value="<?php echo $ws['workshop_id']; ?>">
                                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($ws['workshop_name']); ?></strong>? This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_workshop" class="btn btn-danger">Delete Workshop</button>
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
