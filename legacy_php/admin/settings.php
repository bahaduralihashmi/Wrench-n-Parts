<?php
$page_title = 'System Settings';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

if (isset($_POST['save_settings'])) {
    verifyCsrf();
    $settings = [
        'site_name' => sanitize($_POST['site_name'] ?? SITE_NAME),
        'site_email' => sanitize($_POST['site_email'] ?? ''),
        'site_phone' => sanitize($_POST['site_phone'] ?? ''),
        'site_address' => sanitize($_POST['site_address'] ?? ''),
        'currency' => sanitize($_POST['currency'] ?? 'Rs.'),
        'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
        'shipping_fee' => floatval($_POST['shipping_fee'] ?? 0),
        'chatbot_enabled' => isset($_POST['chatbot_enabled']) ? '1' : '0',
        'chatbot_name' => sanitize($_POST['chatbot_name'] ?? 'MechBot'),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
    ];

    foreach ($settings as $key => $value) {
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM system_settings WHERE setting_key = ?");
        $check->bind_param("s", $key);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc()['cnt'];
        $check->close();

        if ($exists > 0) {
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
            $stmt->close();
        }
    }

    setFlash('success', 'Settings saved successfully.');
    redirect('settings.php');
}

$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$site_name = $settings['site_name'] ?? SITE_NAME;
$site_email = $settings['site_email'] ?? '';
$site_phone = $settings['site_phone'] ?? '';
$site_address = $settings['site_address'] ?? '';
$currency = $settings['currency'] ?? 'Rs.';
$tax_rate = $settings['tax_rate'] ?? '0';
$shipping_fee = $settings['shipping_fee'] ?? '0';
$chatbot_enabled = ($settings['chatbot_enabled'] ?? '1') === '1';
$chatbot_name = $settings['chatbot_name'] ?? 'MechBot';
$maintenance_mode = ($settings['maintenance_mode'] ?? '0') === '1';

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-cog"></i> System Settings</h2>
                <p class="admin-page-subtitle">Configure your platform settings</p>
            </div>
        </div>

        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="admin-grid-2col">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <i class="fas fa-globe"></i>
                        <h6>Site Information</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($site_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Email</label>
                            <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($site_email); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Phone</label>
                            <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($site_phone); ?>">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Site Address</label>
                            <textarea name="site_address" class="form-control" rows="3"><?php echo htmlspecialchars($site_address); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <i class="fas fa-calculator"></i>
                        <h6>Pricing & Finance</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="mb-3">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" name="currency" class="form-control" value="<?php echo htmlspecialchars($currency); ?>" maxlength="5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" name="tax_rate" class="form-control" value="<?php echo htmlspecialchars($tax_rate); ?>" step="0.01" min="0" max="100">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Shipping Fee</label>
                            <input type="number" name="shipping_fee" class="form-control" value="<?php echo htmlspecialchars($shipping_fee); ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <i class="fas fa-robot"></i>
                        <h6>Chatbot Settings</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="chatbot_enabled" id="chatbotEnabled" <?php echo $chatbot_enabled ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="chatbotEnabled">Enable Chatbot</label>
                            </div>
                            <small class="text-muted">Toggle the floating chatbot on/off for users.</small>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Chatbot Name</label>
                            <input type="text" name="chatbot_name" class="form-control" value="<?php echo htmlspecialchars($chatbot_name); ?>">
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <i class="fas fa-tools"></i>
                        <h6>Maintenance</h6>
                    </div>
                    <div class="admin-card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceMode" <?php echo $maintenance_mode ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenanceMode">Enable Maintenance Mode</label>
                            </div>
                            <small class="text-muted">When enabled, non-admin users will see a maintenance message.</small>
                        </div>
                        <?php if ($maintenance_mode): ?>
                            <div class="alert alert-warning mb-0" style="border-radius:10px;">
                                <i class="fas fa-exclamation-triangle me-2"></i>Maintenance mode is currently <strong>ACTIVE</strong>.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mb-0" style="border-radius:10px;">
                                <i class="fas fa-check-circle me-2"></i>Site is <strong>LIVE</strong>.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top:8px;margin-bottom:32px;">
                <button type="submit" name="save_settings" class="btn-save-settings">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
