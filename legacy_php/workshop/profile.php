<?php
$page_title = 'Workshop Profile';
require_once __DIR__ . '/../includes/config.php';
requireRole('workshop');

$workshop = null;
$stmt = $conn->prepare("SELECT * FROM workshops WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$workshop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$workshop) {
    $wsName = $current_user['name'] . "'s Workshop";
    $stmt = $conn->prepare("INSERT INTO workshops (user_id, workshop_name, description, location, contact, services, status) VALUES (?, ?, '', '', '', '', 'pending')");
    $stmt->bind_param("is", $current_user['user_id'], $wsName);
    $stmt->execute();
    $workshop_id = $stmt->insert_id;
    $stmt->close();
    $stmt = $conn->prepare("SELECT * FROM workshops WHERE workshop_id = ?");
    $stmt->bind_param("i", $workshop_id);
    $stmt->execute();
    $workshop = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name)) {
            setFlash('danger', 'Name is required.');
            redirect(SITE_URL . '/workshop/profile.php');
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $name, $phone, $current_user['user_id']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_id'] = $current_user['user_id'];
        setFlash('success', 'Profile updated successfully.');
        redirect(SITE_URL . '/workshop/profile.php');
    }

    if ($action === 'update_workshop') {
        $workshop_name = sanitize($_POST['workshop_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $contact = sanitize($_POST['contact'] ?? '');
        $opening_time = sanitize($_POST['opening_time'] ?? '08:00:00');
        $closing_time = sanitize($_POST['closing_time'] ?? '18:00:00');

        if (empty($workshop_name)) {
            setFlash('danger', 'Workshop name is required.');
            redirect(SITE_URL . '/workshop/profile.php');
        }

        $stmt = $conn->prepare("UPDATE workshops SET workshop_name = ?, description = ?, location = ?, contact = ?, opening_time = ?, closing_time = ? WHERE workshop_id = ?");
        $stmt->bind_param("ssssssi", $workshop_name, $description, $location, $contact, $opening_time, $closing_time, $workshop['workshop_id']);
        $stmt->execute();
        $stmt->close();

        setFlash('success', 'Workshop details updated successfully.');
        redirect(SITE_URL . '/workshop/profile.php');
    }

    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlash('danger', 'All password fields are required.');
            redirect(SITE_URL . '/workshop/profile.php');
        }

        if ($new_password !== $confirm_password) {
            setFlash('danger', 'New passwords do not match.');
            redirect(SITE_URL . '/workshop/profile.php');
        }

        if (strlen($new_password) < 6) {
            setFlash('danger', 'Password must be at least 6 characters.');
            redirect(SITE_URL . '/workshop/profile.php');
        }

        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $current_user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current_password, $result['password'])) {
            setFlash('danger', 'Current password is incorrect.');
            redirect(SITE_URL . '/workshop/profile.php');
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $current_user['user_id']);
        $stmt->execute();
        $stmt->close();

        setFlash('success', 'Password changed successfully.');
        redirect(SITE_URL . '/workshop/profile.php');
    }

    if ($action === 'upload_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['logo']['type'];

            if (!in_array($file_type, $allowed_types)) {
                setFlash('danger', 'Only JPEG, PNG, GIF, and WebP images are allowed.');
                redirect(SITE_URL . '/workshop/profile.php');
            }

            $max_size = 5 * 1024 * 1024;
            if ($_FILES['logo']['size'] > $max_size) {
                setFlash('danger', 'Image size must be less than 5MB.');
                redirect(SITE_URL . '/workshop/profile.php');
            }

            $upload_dir = UPLOAD_DIR . 'workshops/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'workshop_' . $workshop['workshop_id'] . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
                if (!empty($workshop['logo']) && file_exists(UPLOAD_DIR . $workshop['logo'])) {
                    unlink(UPLOAD_DIR . $workshop['logo']);
                }

                $db_path = 'workshops/' . $filename;
                $stmt = $conn->prepare("UPDATE workshops SET logo = ? WHERE workshop_id = ?");
                $stmt->bind_param("si", $db_path, $workshop['workshop_id']);
                $stmt->execute();
                $stmt->close();

                setFlash('success', 'Logo uploaded successfully.');
            } else {
                setFlash('danger', 'Failed to upload the file.');
            }
        } else {
            setFlash('danger', 'Please select a file to upload.');
        }
        redirect(SITE_URL . '/workshop/profile.php');
    }
}

$stmt = $conn->prepare("SELECT * FROM workshops WHERE workshop_id = ?");
$stmt->bind_param("i", $workshop['workshop_id']);
$stmt->execute();
$workshop = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<button class="admin-sidebar-toggle" id="workshopSidebarToggle" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.toggle('show');document.getElementById('workshopOverlay').classList.toggle('active')">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="workshopOverlay" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.remove('show');this.classList.remove('active')"></div>
<div class="dash-layout">
    <?php require_once __DIR__ . '/../includes/workshop-sidebar.php'; ?>

    <div class="dash-main">
        <a href="<?php echo SITE_URL; ?>/workshop/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="dash-header">
            <h2><i class="fas fa-user-edit me-2 text-danger"></i>Workshop Profile</h2>
            <div class="dash-header-actions">
                <span class="dash-badge dash-badge-<?php echo $workshop['status'] === 'active' ? 'green' : ($workshop['status'] === 'pending' ? 'orange' : 'gray'); ?>">
                    <?php echo ucfirst($workshop['status']); ?>
                </span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="dash-card mb-4">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-user me-2 text-primary"></i>User Profile</h5>
                        <form method="POST" class="form-modern">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small class="text-muted">Contact admin to change email.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="dash-btn-action dash-btn-primary"><i class="fas fa-save me-1"></i>Save Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="dash-card mb-4">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-store me-2 text-warning"></i>Workshop Details</h5>
                        <form method="POST" class="form-modern">
                            <input type="hidden" name="action" value="update_workshop">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Workshop Name</label>
                                    <input type="text" class="form-control" name="workshop_name" value="<?php echo htmlspecialchars($workshop['workshop_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($workshop['contact'] ?? ''); ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($workshop['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($workshop['location'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Opening Time</label>
                                    <input type="time" class="form-control" name="opening_time" value="<?php echo htmlspecialchars($workshop['opening_time'] ?? '08:00:00'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Closing Time</label>
                                    <input type="time" class="form-control" name="closing_time" value="<?php echo htmlspecialchars($workshop['closing_time'] ?? '18:00:00'); ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="dash-btn-action dash-btn-primary"><i class="fas fa-save me-1"></i>Save Workshop Details</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-lock me-2 text-danger"></i>Change Password</h5>
                        <form method="POST" class="form-modern">
                            <input type="hidden" name="action" value="change_password">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" minlength="6" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="dash-btn-action dash-btn-outline"><i class="fas fa-key me-1"></i>Change Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dash-card mb-4">
                    <div class="dash-card-body text-center">
                        <h5 class="card-title mb-3"><i class="fas fa-image me-2 text-info"></i>Workshop Logo</h5>
                        <div class="mb-3">
                            <?php if (!empty($workshop['logo'])): ?>
                                <img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($workshop['logo']); ?>" alt="Workshop Logo" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                                    <i class="fas fa-tools fa-3x text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_logo">
                            <div class="mb-3">
                                <input type="file" class="form-control" name="logo" accept="image/*" required>
                            </div>
                            <button type="submit" class="dash-btn-action dash-btn-primary"><i class="fas fa-upload me-1"></i>Upload Logo</button>
                        </form>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-info-circle me-2 text-secondary"></i>Account Info</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                            <li class="mb-2"><strong>Role:</strong> <span class="dash-badge dash-badge-blue">Workshop</span></li>
                            <li class="mb-2"><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></li>
                            <li><strong>Status:</strong> <span class="dash-badge dash-badge-<?php echo $user['status'] === 'active' ? 'green' : 'gray'; ?>"><?php echo ucfirst($user['status']); ?></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
