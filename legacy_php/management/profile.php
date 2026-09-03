<?php
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/config.php';
requireRole('management');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = sanitize($_POST['name']);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);

                if (empty($name)) {
                    setFlash('danger', 'Name is required.');
                    redirect(SITE_URL . '/management/profile.php');
                }

                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $name, $phone, $address, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $current_user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                setFlash('success', 'Profile updated successfully.');
                redirect(SITE_URL . '/management/profile.php');
                break;

            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];

                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    setFlash('danger', 'All password fields are required.');
                    redirect(SITE_URL . '/management/profile.php');
                }

                if ($newPassword !== $confirmPassword) {
                    setFlash('danger', 'New password and confirmation do not match.');
                    redirect(SITE_URL . '/management/profile.php');
                }

                if (strlen($newPassword) < 6) {
                    setFlash('danger', 'New password must be at least 6 characters long.');
                    redirect(SITE_URL . '/management/profile.php');
                }

                if (!password_verify($currentPassword, $current_user['password'])) {
                    setFlash('danger', 'Current password is incorrect.');
                    redirect(SITE_URL . '/management/profile.php');
                }

                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashedPassword, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();

                setFlash('success', 'Password changed successfully.');
                redirect(SITE_URL . '/management/profile.php');
                break;
        }
    }
}

$uid = intval($_SESSION['user_id']);
$totalOrders = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE customer_id = $uid")->fetch_assoc()['cnt'];
$totalAppointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE customer_id = $uid")->fetch_assoc()['cnt'];
$memberSince = date('M d, Y', strtotime($current_user['created_at']));

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mgmt-wrap{max-width:1000px;margin:0 auto;padding:28px 16px 50px}
.mg-profile-hero{background:linear-gradient(135deg,#0f0c29 0%,#302b63 50%,#24243e 100%);border-radius:20px;padding:40px;text-align:center;color:#fff;position:relative;overflow:hidden;margin-bottom:28px}
.mg-profile-hero::before{content:'';position:absolute;top:-30%;right:-10%;width:280px;height:280px;background:radial-gradient(circle,rgba(102,126,234,.3) 0%,transparent 70%);border-radius:50%}
.mg-profile-hero::after{content:'';position:absolute;bottom:-40%;left:15%;width:240px;height:240px;background:radial-gradient(circle,rgba(233,69,96,.2) 0%,transparent 70%);border-radius:50%}
.mg-profile-avatar{width:110px;height:110px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;margin:0 auto 18px;background:linear-gradient(135deg,#667eea,#764ba2);box-shadow:0 8px 30px rgba(102,126,234,.4);position:relative;z-index:1;border:4px solid rgba(255,255,255,.2)}
.mg-profile-avatar img{width:100%;height:100%;border-radius:50%;object-fit:cover}
.mg-profile-hero h2{font-size:1.6rem;font-weight:800;margin:0 0 6px;position:relative;z-index:1}
.mg-profile-hero .role-tag{display:inline-block;background:rgba(255,255,255,.15);backdrop-filter:blur(4px);padding:5px 16px;border-radius:30px;font-size:.78rem;font-weight:600;position:relative;z-index:1}
.mg-profile-hero .email-text{font-size:.88rem;opacity:.7;margin-top:8px;position:relative;z-index:1}
.mg-profile-stats{display:flex;justify-content:center;gap:40px;margin-top:22px;position:relative;z-index:1}
.mg-profile-stat{text-align:center}
.mg-profile-stat .val{font-size:1.4rem;font-weight:800}
.mg-profile-stat .lbl{font-size:.72rem;opacity:.6;text-transform:uppercase;letter-spacing:.5px;font-weight:600}

.mg-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);overflow:hidden;margin-bottom:22px}
.mg-card-h{display:flex;align-items:center;gap:12px;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc 0%,#fff 100%)}
.mg-card-h h5{margin:0;font-size:1rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.mg-card-h h5 i{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.mg-card-b{padding:24px 28px}

.mg-form-group{margin-bottom:18px}
.mg-form-group label{display:block;font-size:.82rem;font-weight:700;color:#555;margin-bottom:6px}
.mg-form-input{width:100%;padding:12px 16px;border:1.5px solid #e0e0e0;border-radius:12px;font-size:.9rem;font-family:'Inter',sans-serif;transition:all .25s;background:#fafafa}
.mg-form-input:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);outline:none;background:#fff}
.mg-form-input::placeholder{color:#bbb}
textarea.mg-form-input{resize:vertical;min-height:80px}

.mg-btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;padding:13px 30px;border-radius:12px;font-size:.9rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .3s;box-shadow:0 4px 15px rgba(102,126,234,.3);font-family:'Inter',sans-serif}
.mg-btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(102,126,234,.4)}
.mg-btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;padding:13px 30px;border-radius:12px;font-size:.9rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .3s;box-shadow:0 4px 15px rgba(245,158,11,.3);font-family:'Inter',sans-serif}
.mg-btn-warning:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(245,158,11,.4)}

.mg-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.mg-detail-item{padding:16px;background:linear-gradient(135deg,#f8f9fa,#f1f5f9);border-radius:12px;border:1px solid #f0f0f0}
.mg-detail-label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#aaa;margin-bottom:5px}
.mg-detail-val{font-size:.95rem;font-weight:700;color:#1a1a2e}
.mg-status-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:700}

.mg-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:22px}

@media(max-width:768px){.mg-grid-2{grid-template-columns:1fr}.mg-detail-grid{grid-template-columns:1fr}.mg-profile-stats{gap:20px}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="mgmt-wrap">

            <div class="mg-profile-hero">
                <?php if (!empty($current_user['profile_image'])): ?>
                    <div class="mg-profile-avatar"><img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($current_user['profile_image']); ?>" alt="Profile"></div>
                <?php else: ?>
                    <div class="mg-profile-avatar"><?php echo strtoupper(substr($current_user['name'], 0, 1)); ?></div>
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($current_user['name']); ?></h2>
                <span class="role-tag"><i class="fas fa-shield-alt me-1"></i><?php echo ucfirst(htmlspecialchars($current_user['role'])); ?></span>
                <div class="email-text"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($current_user['email']); ?></div>
                <div class="mg-profile-stats">
                    <div class="mg-profile-stat"><div class="val"><?php echo number_format($totalOrders); ?></div><div class="lbl">Orders</div></div>
                    <div class="mg-profile-stat"><div class="val"><?php echo number_format($totalAppointments); ?></div><div class="lbl">Appointments</div></div>
                    <div class="mg-profile-stat"><div class="val"><?php echo $memberSince; ?></div><div class="lbl">Joined</div></div>
                </div>
            </div>

            <div class="mg-grid-2">
                <div class="mg-card">
                    <div class="mg-card-h"><h5><i class="fas fa-edit me-2"></i>Edit Profile</h5></div>
                    <div class="mg-card-b">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mg-form-group">
                                <label>Full Name</label>
                                <input type="text" class="mg-form-input" name="name" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                            </div>
                            <div class="mg-form-group">
                                <label>Email Address</label>
                                <input type="email" class="mg-form-input" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled style="opacity:.6;">
                                <small style="color:#999;font-size:.78rem;">Email cannot be changed.</small>
                            </div>
                            <div class="mg-form-group">
                                <label>Phone Number</label>
                                <input type="text" class="mg-form-input" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                            </div>
                            <div class="mg-form-group">
                                <label>Role</label>
                                <input type="text" class="mg-form-input" value="<?php echo ucfirst(htmlspecialchars($current_user['role'])); ?>" disabled style="opacity:.6;">
                            </div>
                            <div class="mg-form-group">
                                <label>Address</label>
                                <textarea class="mg-form-input" name="address" rows="3"><?php echo htmlspecialchars($current_user['address'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="mg-btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        </form>
                    </div>
                </div>

                <div class="mg-card">
                    <div class="mg-card-h"><h5><i class="fas fa-lock me-2"></i>Change Password</h5></div>
                    <div class="mg-card-b">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mg-form-group">
                                <label>Current Password</label>
                                <input type="password" class="mg-form-input" name="current_password" required>
                            </div>
                            <div class="mg-form-group">
                                <label>New Password</label>
                                <input type="password" class="mg-form-input" name="new_password" minlength="6" required>
                                <small style="color:#999;font-size:.78rem;">Minimum 6 characters.</small>
                            </div>
                            <div class="mg-form-group">
                                <label>Confirm New Password</label>
                                <input type="password" class="mg-form-input" name="confirm_password" minlength="6" required>
                            </div>
                            <button type="submit" class="mg-btn-warning" onclick="return confirm('Are you sure you want to change your password?');"><i class="fas fa-key me-1"></i>Change Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mg-card">
                <div class="mg-card-h"><h5><i class="fas fa-id-card me-2"></i>Account Details</h5></div>
                <div class="mg-card-b">
                    <div class="mg-detail-grid">
                        <div class="mg-detail-item"><div class="mg-detail-label">User ID</div><div class="mg-detail-val">#<?php echo $current_user['user_id']; ?></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Name</div><div class="mg-detail-val"><?php echo htmlspecialchars($current_user['name']); ?></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Email</div><div class="mg-detail-val"><?php echo htmlspecialchars($current_user['email']); ?></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Phone</div><div class="mg-detail-val"><?php echo htmlspecialchars($current_user['phone'] ?? 'Not set'); ?></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Address</div><div class="mg-detail-val"><?php echo htmlspecialchars($current_user['address'] ?? 'Not set'); ?></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Role</div><div class="mg-detail-val"><span class="mg-status-badge" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#2563eb;"><?php echo ucfirst(htmlspecialchars($current_user['role'])); ?></span></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Status</div><div class="mg-detail-val"><span class="mg-status-badge" style="background:linear-gradient(135deg,<?php echo $current_user['status'] === 'active' ? '#d1fae5,#a7f3d0' : '#fee2e2,#fecaca'; ?>);color:<?php echo $current_user['status'] === 'active' ? '#059669' : '#dc2626'; ?>;"><?php echo ucfirst(htmlspecialchars($current_user['status'])); ?></span></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Member Since</div><div class="mg-detail-val"><?php echo date('F d, Y', strtotime($current_user['created_at'])); ?></div></div>
                        <div class="mg-detail-item"><div class="mg-detail-label">Last Updated</div><div class="mg-detail-val"><?php echo date('F d, Y', strtotime($current_user['updated_at'])); ?></div></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
