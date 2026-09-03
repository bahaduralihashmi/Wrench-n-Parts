<?php
$page_title = 'Manage Categories';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['add_category'])) {
    verifyCsrf();
    $name = sanitize($_POST['category_name']);
    $desc = sanitize($_POST['description'] ?? '');
    if (!empty($name)) {
        $check = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $cat_image = '';
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['category_image']['tmp_name']);
                if (in_array($ext, $allowed) && in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) {
                    $filename = 'cat_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    if (move_uploaded_file($_FILES['category_image']['tmp_name'], UPLOAD_DIR . $filename)) {
                        $cat_image = $filename;
                    }
                }
            }
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description, category_image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $desc, $cat_image);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Category added successfully.');
        } else {
            setFlash('danger', 'Category name already exists.');
        }
        $check->close();
    }
    redirect('categories.php');
}

if (isset($_POST['update_category'])) {
    verifyCsrf();
    $cid = intval($_POST['category_id']);
    $name = sanitize($_POST['category_name']);
    $desc = sanitize($_POST['description'] ?? '');
    if (!empty($name)) {
        $cat_image = $_POST['existing_image'] ?? '';
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['category_image']['tmp_name']);
            if (in_array($ext, $allowed) && in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) {
                $filename = 'cat_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                if (move_uploaded_file($_FILES['category_image']['tmp_name'], UPLOAD_DIR . $filename)) {
                    $old = $_POST['existing_image'] ?? '';
                    if ($old && file_exists(UPLOAD_DIR . $old)) unlink(UPLOAD_DIR . $old);
                    $cat_image = $filename;
                }
            }
        }
        $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ?, category_image = ? WHERE category_id = ?");
        $stmt->bind_param("sssi", $name, $desc, $cat_image, $cid);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Category updated successfully.');
    }
    redirect('categories.php');
}

if (isset($_POST['delete_category'])) {
    verifyCsrf();
    $cid = intval($_POST['category_id']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Category deleted successfully.');
    redirect('categories.php');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (category_name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$countQuery = "SELECT COUNT(*) as total FROM categories $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = ceil($totalRows / $perPage);

$query = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) as product_count FROM categories c $where ORDER BY c.category_name ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fullTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($fullTypes, ...$bindParams);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-tags"></i> Manage Categories</h2>
                <p class="admin-page-subtitle">Add, edit and organize product categories</p>
            </div>
            <div class="admin-header-actions">
                <button class="btn-filter" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fas fa-plus"></i> Add Category</button>
                <span class="admin-count-badge"><i class="fas fa-tags"></i> <?php echo $totalRows; ?> categories</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="categories.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($categories)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-tags"></i>
                            <h4>No categories found</h4>
                            <p>Click "Add Category" to create your first category</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $i => $cat): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <?php if (!empty($cat['category_image'])): ?>
                                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($cat['category_image']); ?>" alt="" style="width:40px;height:40px;border-radius:10px;object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><i class="fas fa-tag"></i></div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($cat['category_name']); ?></div>
                                                    <div class="cell-info-sub">#<?php echo $cat['category_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><small style="color:#888;"><?php echo htmlspecialchars(mb_strimwidth($cat['description'] ?? '', 0, 60, '...')); ?></small></td>
                                        <td><span class="admin-count-badge" style="font-size:0.75rem;"><?php echo $cat['product_count']; ?></span></td>
                                        <td>
                                            <div class="action-btns" style="justify-content:flex-end;">
                                                <button class="action-btn action-btn-view" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $cat['category_id']; ?>" title="View"><i class="fas fa-eye"></i></button>
                                                <button class="action-btn action-btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $cat['category_id']; ?>" title="Edit"><i class="fas fa-pen"></i></button>
                                                <button class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $cat['category_id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $cat['category_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-tag me-2 text-red"></i><?php echo htmlspecialchars($cat['category_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if (!empty($cat['category_image'])): ?>
                                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($cat['category_image']); ?>" alt="" style="width:100%;max-height:200px;object-fit:cover;border-radius:12px;margin-bottom:16px;">
                                                    <?php endif; ?>
                                                    <div class="detail-grid">
                                                        <div class="detail-item"><div class="detail-label">ID</div><div class="detail-value">#<?php echo $cat['category_id']; ?></div></div>
                                                        <div class="detail-item"><div class="detail-label">Products</div><div class="detail-value"><?php echo $cat['product_count']; ?></div></div>
                                                    </div>
                                                    <?php if (!empty($cat['description'])): ?>
                                                        <div class="detail-item" style="margin-top:12px;"><div class="detail-label">Description</div><div class="detail-value"><?php echo htmlspecialchars($cat['description']); ?></div></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $cat['category_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-pen me-2 text-red"></i> Edit Category</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($cat['category_image'] ?? ''); ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Category Name</label>
                                                            <input type="text" name="category_name" class="form-control" required value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Description</label>
                                                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Category Image</label>
                                                            <input type="file" name="category_image" class="form-control" accept="image/*">
                                                            <?php if (!empty($cat['category_image'])): ?>
                                                                <small class="text-muted">Current: <?php echo htmlspecialchars($cat['category_image']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_category" class="btn btn-danger">Update Category</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $cat['category_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-red"></i> Delete Category</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($cat['category_name']); ?></strong>?</p>
                                                        <?php if ($cat['product_count'] > 0): ?>
                                                            <p class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>This category has <?php echo $cat['product_count']; ?> product(s). They will become uncategorized.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_category" class="btn btn-danger">Delete Category</button>
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
                <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2 text-red"></i> Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" required placeholder="e.g. Engine Parts">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this category"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Image</label>
                        <input type="file" name="category_image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-danger">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
