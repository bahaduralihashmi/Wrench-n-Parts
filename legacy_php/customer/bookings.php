<?php
$page_title = 'My Bookings';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

if (isset($_POST['cancel_appointment'])) {
    verifyCsrf();
    $appt_id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND customer_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $appt_id, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        setFlash('success', 'Appointment #' . $appt_id . ' has been cancelled.');
    } else {
        setFlash('danger', 'Could not cancel this appointment.');
    }
    $stmt->close();
    redirect(SITE_URL . '/customer/bookings.php');
}

$appointments = $conn->prepare("SELECT a.*, w.workshop_name, w.location as workshop_location, w.contact as workshop_phone FROM appointments a LEFT JOIN workshops w ON a.workshop_id = w.workshop_id WHERE a.customer_id = ? ORDER BY a.created_at DESC");
$appointments->bind_param("i", $user_id);
$appointments->execute();
$appointments_result = $appointments->get_result();

require_once __DIR__ . '/header.php';
?>

<div class="container-fluid px-4 py-4">
    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="cust-welcome-banner">
        <div class="cust-welcome-left">
            <h1 class="cust-welcome-title">My Bookings</h1>
            <p class="cust-welcome-desc">View and manage your workshop appointments</p>
        </div>
        <div class="cust-welcome-actions">
            <a href="<?php echo SITE_URL; ?>/workshop-finder.php" class="cust-btn-workshop"><i class="fas fa-plus me-1"></i>Book New</a>
        </div>
    </div>

    <?php if ($appointments_result->num_rows > 0): ?>
        <?php while ($appt = $appointments_result->fetch_assoc()): ?>
        <div class="cust-section">
            <div class="cust-empty-state" style="text-align:left;padding:24px;">
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <div style="width:50px;height:50px;background:#e8f4fd;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-tools" style="color:#3498db;font-size:1.1rem;"></i>
                    </div>
                    <div style="flex:1;min-width:200px;">
                        <h5 style="margin:0 0 4px;font-weight:700;"><?php echo htmlspecialchars($appt['workshop_name'] ?? 'Workshop'); ?></h5>
                        <?php if ($appt['workshop_location']): ?>
                            <p style="margin:0;color:#888;font-size:0.85rem;"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($appt['workshop_location']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:120px;">
                        <small style="color:#888;display:block;">Service Type</small>
                        <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($appt['service_type'] ?? 'General Service'); ?></strong>
                    </div>
                    <div style="min-width:140px;">
                        <small style="color:#888;display:block;">Vehicle Info</small>
                        <strong style="font-size:0.9rem;"><?php echo htmlspecialchars(($appt['vehicle_make'] ?? '') . ' ' . ($appt['vehicle_model'] ?? '') . ' ' . ($appt['vehicle_year'] ?? '')); ?></strong>
                    </div>
                    <div style="min-width:130px;">
                        <small style="color:#888;display:block;">Date & Time</small>
                        <strong style="font-size:0.9rem;"><i class="fas fa-calendar-day me-1"></i><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></strong>
                        <?php if (!empty($appt['appointment_time'])): ?>
                            <br><small style="color:#888;"><i class="fas fa-clock me-1"></i><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <span class="dash-badge dash-badge-<?php echo $appt['status'] === 'completed' ? 'green' : ($appt['status'] === 'cancelled' ? 'red' : 'blue'); ?>"><?php echo ucfirst($appt['status']); ?></span>
                        <?php if ($appt['status'] === 'pending'): ?>
                            <br>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                <button type="submit" name="cancel_appointment" style="color:#dc3545;font-size:0.78rem;font-weight:600;text-decoration:none;margin-top:6px;display:inline-block;background:none;border:none;cursor:pointer;padding:0;">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($appt['description'])): ?>
                    <hr style="margin:12px 0 0;border-color:#f0f0f0;">
                    <p style="margin:12px 0 0;color:#888;font-size:0.85rem;"><i class="fas fa-sticky-note me-1"></i><?php echo htmlspecialchars($appt['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="cust-section">
            <div class="cust-empty-state">
                <div class="cust-empty-icon"><i class="fas fa-calendar-times"></i></div>
                <h3 class="cust-empty-title">No bookings yet</h3>
                <p class="cust-empty-desc">Find a workshop and book your first appointment</p>
                <a href="<?php echo SITE_URL; ?>/workshop-finder.php" class="cust-btn-workshop"><i class="fas fa-search me-2"></i>Find a Workshop</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$appointments->close();
require_once __DIR__ . '/footer.php';
?>