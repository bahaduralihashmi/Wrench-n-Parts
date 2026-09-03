<?php
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);

        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            setFlash('danger', 'Email address is already in use by another account.');
            $check_email->close();
            redirect(SITE_URL . '/customer/profile.php');
        }
        $check_email->close();

        $profile_image = $current_user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['profile_image']['tmp_name']);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($ext, $allowed_exts) && in_array($mime, $allowed_mimes)) {
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $filename)) {
                    if ($profile_image && file_exists($upload_dir . $profile_image) && strpos($profile_image, 'profile_') === 0) {
                        unlink($upload_dir . $profile_image);
                    }
                    $profile_image = $filename;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_image = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $profile_image, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_id'] = $user_id;
        setFlash('success', 'Profile updated successfully!');
        redirect(SITE_URL . '/customer/profile.php');
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlash('danger', 'All password fields are required.');
            redirect(SITE_URL . '/customer/profile.php');
        }

        if ($new_password !== $confirm_password) {
            setFlash('danger', 'New password and confirmation do not match.');
            redirect(SITE_URL . '/customer/profile.php');
        }

        if (strlen($new_password) < 6) {
            setFlash('danger', 'New password must be at least 6 characters.');
            redirect(SITE_URL . '/customer/profile.php');
        }

        $user_data = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $user_data->bind_param("i", $user_id);
        $user_data->execute();
        $row = $user_data->get_result()->fetch_assoc();
        $user_data->close();

        if (!password_verify($current_password, $row['password'])) {
            setFlash('danger', 'Current password is incorrect.');
            redirect(SITE_URL . '/customer/profile.php');
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();
        $stmt->close();

        setFlash('success', 'Password changed successfully!');
        redirect(SITE_URL . '/customer/profile.php');
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="container-fluid px-4 py-4">
    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="cust-welcome-banner">
        <div class="cust-welcome-left">
            <h1 class="cust-welcome-title">My Profile</h1>
            <p class="cust-welcome-desc">Manage your account information</p>
        </div>
    </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="cust-section" style="margin-bottom:0;">
                        <div class="cust-empty-state" style="text-align:center;">
                    <div style="margin-bottom:16px;">
                        <?php if (!empty($current_user['profile_image'])): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $current_user['profile_image']; ?>" alt="Profile" class="rounded-circle" style="width:120px;height:120px;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:120px;height:120px;background:#e8f0fe;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;">
                                <i class="fas fa-user" style="font-size:2.5rem;color:#3498db;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h5 style="font-weight:700;margin-bottom:4px;"><?php echo htmlspecialchars($current_user['name']); ?></h5>
                    <p style="color:#888;margin-bottom:8px;font-size:0.88rem;"><?php echo htmlspecialchars($current_user['email']); ?></p>
                    <?php if (!empty($current_user['phone'])): ?>
                        <p style="color:#888;margin-bottom:8px;font-size:0.85rem;"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($current_user['phone']); ?></p>
                    <?php endif; ?>
                    <span class="dash-badge dash-badge-green">Customer</span>
                    <hr style="margin:16px 0 8px;border-color:#f0f0f0;">
                    <small style="color:#888;">Member since <?php echo date('F Y', strtotime($current_user['created_at'])); ?></small>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="cust-section" style="margin-bottom:20px;">
                <div class="cust-section-header">
                    <h2 class="cust-section-title">Personal Information</h2>
                </div>
                <div class="cust-empty-state" style="text-align:left;">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($current_user['name']); ?>" required style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email']); ?>" required style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" placeholder="Enter phone number" style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Profile Image</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*" style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                                <small style="color:#888;">JPG, PNG, GIF, or WebP. Max 5MB.</small>
                            </div>
                            <div class="col-12">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Address</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Enter your full address" style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;"><?php echo htmlspecialchars($current_user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="cust-btn-workshop mt-3"><i class="fas fa-save me-2"></i>Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="cust-section" style="margin-bottom:0;">
                <div class="cust-section-header">
                    <h2 class="cust-section-title">Change Password</h2>
                </div>
                <div class="cust-empty-state" style="text-align:left;">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="change_password" value="1">
                        <div class="row g-3">
                            <div class="col-12">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                                <small style="color:#888;">Minimum 6 characters</small>
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.8rem;font-weight:600;display:block;margin-bottom:6px;">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required style="border-radius:8px;padding:10px 14px;border:1.5px solid #ddd;">
                            </div>
                        </div>
                        <button type="submit" class="cust-btn-chatbot mt-3"><i class="fas fa-key me-2"></i>Change Password</button>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>