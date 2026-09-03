<?php
$page_title = 'Shop Profile';
require_once __DIR__ . '/../includes/config.php';
requireRole('shopkeeper');

$shop = null;
$stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();

    if ($_POST['action'] === 'update_profile') {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);

        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file = $_FILES['profile_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'profile_' . $current_user['user_id'] . '_' . time() . '.' . $ext;
                $upload_path = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $profile_image = $filename;
                }
            }
        }

        if ($profile_image) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $name, $phone, $address, $profile_image, $current_user['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $name, $phone, $address, $current_user['user_id']);
        }
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_id'] = $current_user['user_id'];
        setFlash('success', 'Profile updated successfully!');
        redirect(SITE_URL . '/shopkeeper/profile.php');
    }

    if ($_POST['action'] === 'update_shop') {
        $shop_name = sanitize($_POST['shop_name']);
        $description = sanitize($_POST['description']);
        $location = sanitize($_POST['location']);
        $contact = sanitize($_POST['contact']);

        $logo = null;
        if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file = $_FILES['shop_logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'shop_' . $shop['shop_id'] . '_' . time() . '.' . $ext;
                $upload_path = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $logo = $filename;
                }
            }
        }

        if ($shop) {
            if ($logo) {
                $stmt = $conn->prepare("UPDATE shops SET shop_name = ?, description = ?, location = ?, contact = ?, logo = ? WHERE shop_id = ?");
                $stmt->bind_param("sssssi", $shop_name, $description, $location, $contact, $logo, $shop['shop_id']);
            } else {
                $stmt = $conn->prepare("UPDATE shops SET shop_name = ?, description = ?, location = ?, contact = ? WHERE shop_id = ?");
                $stmt->bind_param("ssssi", $shop_name, $description, $location, $contact, $shop['shop_id']);
            }
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Shop details updated successfully!');
        } else {
            $stmt = $conn->prepare("INSERT INTO shops (user_id, shop_name, description, location, contact, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("issss", $current_user['user_id'], $shop_name, $description, $location, $contact);
            $stmt->execute();
            $shop_id_new = $conn->insert_id;
            $stmt->close();

            if ($logo) {
                $stmt = $conn->prepare("UPDATE shops SET logo = ? WHERE shop_id = ?");
                $stmt->bind_param("si", $logo, $shop_id_new);
                $stmt->execute();
                $stmt->close();
            }
            setFlash('success', 'Shop created successfully! Awaiting admin approval.');
        }
        redirect(SITE_URL . '/shopkeeper/profile.php');
    }

    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $current_user['password'])) {
            setFlash('danger', 'Current password is incorrect.');
            redirect(SITE_URL . '/shopkeeper/profile.php');
        }

        if ($new_password !== $confirm_password) {
            setFlash('danger', 'New passwords do not match.');
            redirect(SITE_URL . '/shopkeeper/profile.php');
        }

        if (strlen($new_password) < 6) {
            setFlash('danger', 'New password must be at least 6 characters.');
            redirect(SITE_URL . '/shopkeeper/profile.php');
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $current_user['user_id']);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Password changed successfully!');
        redirect(SITE_URL . '/shopkeeper/profile.php');
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
                <small style="color:#888;font-size:0.75rem;"><?php echo $shop ? htmlspecialchars($shop['shop_name']) : 'No shop yet'; ?></small>
            </div>
        </div>
        <div class="dash-sidebar-label">Menu</div>
        <nav class="dash-nav">
            <a class="dash-nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a class="dash-nav-link" href="products.php"><i class="fas fa-boxes-stacked"></i>Products</a>
            <a class="dash-nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a>
            <a class="dash-nav-link" href="inventory.php"><i class="fas fa-warehouse"></i>Inventory</a>
            <a class="dash-nav-link" href="hot-deals.php"><i class="fas fa-fire"></i>Hot Deals</a>
            <a class="dash-nav-link" href="returns.php"><i class="fas fa-undo-alt"></i>Returns</a>
            <a class="dash-nav-link" href="chat.php"><i class="fas fa-comments"></i>Chat</a>
            <a class="dash-nav-link active" href="profile.php"><i class="fas fa-user-cog"></i>Profile</a>
        </nav>
        <div class="dash-sidebar-footer">
            <a class="dash-nav-link logout" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>
    <div class="dash-main">
        <a href="<?php echo SITE_URL; ?>/shopkeeper/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="dash-header">
            <h2 class="fw-bold"><i class="fas fa-user-cog me-2"></i>Profile & Settings</h2>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="dash-card mb-4">
                    <div class="dash-card-body">
                        <h5 class="fw-bold mb-3"><i class="fas fa-user me-2 text-primary"></i>Personal Information</h5>
                        <form method="POST" enctype="multipart/form-data" class="form-modern">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_profile">
                            <div class="text-center mb-3">
                                <?php if ($profile['profile_image']): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($profile['profile_image']); ?>" alt="Profile" class="rounded-circle mb-2" width="100" height="100" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:100px;height:100px;">
                                        <i class="fas fa-user fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <label class="form-label fw-bold small">Profile Photo</label>
                                    <input type="file" name="profile_image" class="form-control form-control-sm" accept="image/*">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="dash-btn-action dash-btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dash-card mb-4">
                    <div class="dash-card-body">
                        <h5 class="fw-bold mb-3"><i class="fas fa-store me-2 text-success"></i>Shop Details</h5>
                        <form method="POST" enctype="multipart/form-data" class="form-modern">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_shop">
                            <?php if ($shop && $shop['logo']): ?>
                                <div class="text-center mb-3">
                                    <img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($shop['logo']); ?>" alt="Shop Logo" class="rounded mb-2" width="120" height="120" style="object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Shop Name *</label>
                                <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($shop ? $shop['shop_name'] : ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($shop ? $shop['description'] : ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($shop ? $shop['location'] : ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact</label>
                                <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($shop ? $shop['contact'] : ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Shop Logo</label>
                                <input type="file" name="shop_logo" class="form-control" accept="image/*">
                                <?php if ($shop): ?>
                                    <small class="text-muted">Leave empty to keep current logo.</small>
                                <?php endif; ?>
                            </div>
                            <?php if ($shop): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Shop Status</label>
                                    <div>
                                        <?php if ($shop['status'] === 'active'): ?>
                                            <span class="dash-badge dash-badge-green"><i class="fas fa-check-circle me-1"></i>Active</span>
                                        <?php elseif ($shop['status'] === 'pending'): ?>
                                            <span class="dash-badge dash-badge-orange"><i class="fas fa-clock me-1"></i>Pending Approval</span>
                                        <?php else: ?>
                                            <span class="dash-badge dash-badge-red"><i class="fas fa-times-circle me-1"></i>Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="dash-btn-action dash-btn-primary" style="background:#28a745;"><i class="fas fa-save me-1"></i><?php echo $shop ? 'Update Shop' : 'Create Shop'; ?></button>
                        </form>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-body">
                        <h5 class="fw-bold mb-3"><i class="fas fa-lock me-2 text-warning"></i>Change Password</h5>
                        <form method="POST" class="form-modern">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                            <button type="submit" class="dash-btn-action dash-btn-outline"><i class="fas fa-key me-1"></i>Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
