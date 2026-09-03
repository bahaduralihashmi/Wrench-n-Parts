<?php
$page_title = 'Workshop Dashboard';
require_once __DIR__ . '/../includes/config.php';
requireRole('workshop');

if ($current_user['status'] === 'pending') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pending Approval - Wrench n Parts</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Inter', -apple-system, sans-serif; background: #f5f7fb; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .pending-card { background: #fff; border-radius: 20px; padding: 50px 40px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
            .pending-icon { width: 90px; height: 90px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2.2rem; color: #fff; }
            .pending-card h1 { font-size: 1.5rem; color: #1a1a2e; margin-bottom: 12px; }
            .pending-card p { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px; }
            .pending-card .email { color: #dc3545; font-weight: 600; }
            .pending-steps { text-align: left; background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 28px; }
            .pending-steps h3 { font-size: 0.9rem; color: #333; margin-bottom: 12px; }
            .pending-steps li { color: #555; font-size: 0.85rem; margin-bottom: 8px; list-style: none; display: flex; align-items: center; gap: 8px; }
            .pending-steps li i { color: #27ae60; font-size: 0.8rem; }
            .pending-btn { display: inline-block; padding: 12px 32px; background: #dc3545; color: #fff; border: none; border-radius: 50px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .3s; }
            .pending-btn:hover { background: #c82333; }
        </style>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/responsive.css">
    </head>
    <body>
        <div class="pending-card">
            <div class="pending-icon"><i class="fas fa-clock"></i></div>
            <h1>Account Pending Approval</h1>
            <p>Thank you for registering as a Workshop! Your account (<span class="email"><?php echo htmlspecialchars($current_user['email']); ?></span>) is currently under review by our admin team.</p>
            <div class="pending-steps">
                <h3><i class="fas fa-info-circle"></i> What happens next?</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Our team will review your workshop application</li>
                    <li><i class="fas fa-check-circle"></i> We'll verify your certifications and services</li>
                    <li><i class="fas fa-check-circle"></i> You'll receive approval within 24 hours</li>
                    <li><i class="fas fa-check-circle"></i> Once approved, you can login and manage your workshop</li>
                </ul>
            </div>
            <a href="<?php echo SITE_URL; ?>/login.php" class="pending-btn"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

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

$workshop_id = $workshop['workshop_id'];

$total_appointments = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE workshop_id = ?");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pending_appointments = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE workshop_id = ? AND status = 'pending'");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$pending_appointments = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$completed_jobs = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE workshop_id = ? AND status = 'completed'");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$completed_jobs = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$avg_rating = 0;
$stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating FROM reviews WHERE workshop_id = ?");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$avg_rating = $stmt->get_result()->fetch_assoc()['avg_rating'];
$stmt->close();

$recent_appointments = [];
$stmt = $conn->prepare("SELECT a.*, u.name as customer_name FROM appointments a LEFT JOIN users u ON a.customer_id = u.user_id WHERE a.workshop_id = ? ORDER BY a.created_at DESC LIMIT 5");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$recent_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$services = [];
if (!empty($workshop['services'])) {
    $services = array_map('trim', explode(',', $workshop['services']));
    $services = array_filter($services);
}

$today = date('l, F j, Y');
$greeting = '';
$h = (int)date('H');
if ($h < 12) $greeting = 'Good Morning';
elseif ($h < 17) $greeting = 'Good Afternoon';
else $greeting = 'Good Evening';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .wd-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 24px 16px 40px;
    }

    /* ── Welcome Banner ── */
    .wd-banner {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 20px;
        padding: 40px 36px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 28px;
    }
    .wd-banner::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(233,69,96,.25) 0%, transparent 70%);
        border-radius: 50%;
    }
    .wd-banner::after {
        content: '';
        position: absolute;
        bottom: -50%;
        left: 20%;
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(83,120,249,.2) 0%, transparent 70%);
        border-radius: 50%;
    }
    .wd-banner h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 4px;
        position: relative;
        z-index: 1;
    }
    .wd-banner p {
        font-size: .95rem;
        opacity: .85;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    .wd-banner .wd-date {
        display: inline-block;
        margin-top: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(4px);
        padding: 6px 16px;
        border-radius: 30px;
        font-size: .85rem;
        position: relative;
        z-index: 1;
    }
    .wd-banner .wd-status-badge {
        display: inline-block;
        margin-top: 14px;
        margin-left: 8px;
        padding: 6px 16px;
        border-radius: 30px;
        font-size: .8rem;
        font-weight: 600;
        position: relative;
        z-index: 1;
    }
    .wd-status-active { background: rgba(40,167,69,.25); color: #5eff9a; }
    .wd-status-pending { background: rgba(255,193,7,.25); color: #ffd866; }
    .wd-status-inactive { background: rgba(220,53,69,.25); color: #ff8a8a; }

    /* ── Stats Grid ── */
    .wd-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }
    .wd-stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 26px 22px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        border: 1px solid rgba(0,0,0,.05);
        transition: transform .2s, box-shadow .2s;
    }
    .wd-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 24px rgba(0,0,0,.1);
    }
    .wd-stat-card .wd-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 16px;
    }
    .wd-stat-card .wd-stat-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 4px;
    }
    .wd-stat-card .wd-stat-label {
        font-size: .85rem;
        color: #6b7280;
        font-weight: 500;
    }
    .wd-stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 80px;
        height: 80px;
        border-radius: 0 16px 0 80px;
        opacity: .07;
    }

    .wd-icon-total { background: rgba(99,102,241,.12); color: #6366f1; }
    .wd-icon-total + .wd-stat-value { color: #6366f1; }
    .wd-stat-card:nth-child(1)::after { background: #6366f1; }

    .wd-icon-completed { background: rgba(34,197,94,.12); color: #22c55e; }
    .wd-icon-completed + .wd-stat-value { color: #22c55e; }
    .wd-stat-card:nth-child(2)::after { background: #22c55e; }

    .wd-icon-pending { background: rgba(245,158,11,.12); color: #f59e0b; }
    .wd-icon-pending + .wd-stat-value { color: #f59e0b; }
    .wd-stat-card:nth-child(3)::after { background: #f59e0b; }

    .wd-icon-rating { background: rgba(236,72,153,.12); color: #ec4899; }
    .wd-icon-rating + .wd-stat-value { color: #ec4899; }
    .wd-stat-card:nth-child(4)::after { background: #ec4899; }

    /* ── Content Grid ── */
    .wd-content {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
    }

    /* ── Cards ── */
    .wd-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        border: 1px solid rgba(0,0,0,.05);
        overflow: hidden;
    }
    .wd-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid #f1f5f9;
    }
    .wd-card-head h5 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
    }
    .wd-card-body { padding: 20px 24px; }

    /* ── Table ── */
    .wd-table {
        width: 100%;
        border-collapse: collapse;
    }
    .wd-table th {
        font-size: .75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #94a3b8;
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #f1f5f9;
    }
    .wd-table td {
        padding: 14px 12px;
        font-size: .9rem;
        color: #334155;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }
    .wd-table tr:last-child td { border-bottom: none; }
    .wd-table tr:hover td { background: #f8fafc; }

    .wd-status-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .wd-pill-completed { background: #dcfce7; color: #16a34a; }
    .wd-pill-pending { background: #fef3c7; color: #d97706; }
    .wd-pill-cancelled { background: #fee2e2; color: #dc2626; }
    .wd-pill-confirmed { background: #dbeafe; color: #2563eb; }
    .wd-pill-default { background: #f1f5f9; color: #64748b; }

    .wd-empty {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }
    .wd-empty i { font-size: 2.5rem; margin-bottom: 12px; display: block; }

    /* ── Services ── */
    .wd-service-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #f1f5f9;
        border-radius: 10px;
        font-size: .85rem;
        color: #475569;
        font-weight: 500;
        margin: 0 6px 8px 0;
    }
    .wd-service-tag i { color: #6366f1; font-size: .8rem; }

    /* ── Quick Actions ── */
    .wd-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .wd-action-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        border-radius: 12px;
        text-decoration: none;
        color: #334155;
        font-weight: 600;
        font-size: .92rem;
        border: 1px solid #f1f5f9;
        transition: all .2s;
    }
    .wd-action-link:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
        transform: translateX(4px);
        color: #1e293b;
    }
    .wd-action-link .wd-action-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .wd-action-link small {
        display: block;
        font-weight: 400;
        font-size: .78rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    .wd-ai-blue { background: rgba(99,102,241,.1); color: #6366f1; }
    .wd-ai-green { background: rgba(34,197,94,.1); color: #22c55e; }
    .wd-ai-pink { background: rgba(236,72,153,.1); color: #ec4899; }

    /* ── Link Button ── */
    .wd-view-all {
        font-size: .82rem;
        font-weight: 600;
        color: #6366f1;
        text-decoration: none;
        padding: 6px 14px;
        border-radius: 8px;
        transition: background .2s;
    }
    .wd-view-all:hover { background: rgba(99,102,241,.08); color: #4f46e5; }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
        .wd-content { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .wd-stats { grid-template-columns: repeat(2, 1fr); gap: 14px; }
        .wd-banner { padding: 28px 22px; }
        .wd-banner h1 { font-size: 1.35rem; }
        .wd-wrap { padding: 16px 12px 32px; }
    }
    @media (max-width: 480px) {
        .wd-stats { grid-template-columns: 1fr; }
        .wd-stat-card { padding: 20px 18px; }
    }
</style>

<div class="wd-wrap">

    <!-- Welcome Banner -->
    <div class="wd-banner">
        <h1><?php echo $greeting . ', ' . htmlspecialchars($workshop['workshop_name']); ?>!</h1>
        <p>Here's what's happening with your workshop today.</p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span class="wd-date"><i class="fas fa-calendar-alt me-1"></i><?php echo $today; ?></span>
            <?php if ($workshop['status'] === 'active'): ?>
                <span class="wd-status-badge wd-status-active"><i class="fas fa-check-circle me-1"></i>Active</span>
            <?php elseif ($workshop['status'] === 'pending'): ?>
                <span class="wd-status-badge wd-status-pending"><i class="fas fa-clock me-1"></i>Pending Approval</span>
            <?php else: ?>
                <span class="wd-status-badge wd-status-inactive"><i class="fas fa-ban me-1"></i>Inactive</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="wd-stats">
        <div class="wd-stat-card">
            <div class="wd-stat-icon wd-icon-total"><i class="fas fa-calendar-check"></i></div>
            <div class="wd-stat-value"><?php echo $total_appointments; ?></div>
            <div class="wd-stat-label">Total Appointments</div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-icon wd-icon-completed"><i class="fas fa-check-double"></i></div>
            <div class="wd-stat-value"><?php echo $completed_jobs; ?></div>
            <div class="wd-stat-label">Completed</div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-icon wd-icon-pending"><i class="fas fa-hourglass-half"></i></div>
            <div class="wd-stat-value"><?php echo $pending_appointments; ?></div>
            <div class="wd-stat-label">Pending</div>
        </div>
        <div class="wd-stat-card">
            <div class="wd-stat-icon wd-icon-rating"><i class="fas fa-star"></i></div>
            <div class="wd-stat-value"><?php echo number_format($avg_rating, 1); ?></div>
            <div class="wd-stat-label">Avg Rating</div>
        </div>
    </div>

    <!-- Content: Appointments + Sidebar -->
    <div class="wd-content">

        <!-- Recent Appointments -->
        <div class="wd-card">
            <div class="wd-card-head">
                <h5><i class="fas fa-calendar-alt me-2" style="color:#6366f1;"></i>Recent Appointments</h5>
                <a href="<?php echo SITE_URL; ?>/workshop/appointments.php" class="wd-view-all">View All <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="wd-card-body" style="padding:0;">
                <?php if (empty($recent_appointments)): ?>
                    <div class="wd-empty">
                        <i class="fas fa-calendar-times"></i>
                        <p>No appointments yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="wd-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_appointments as $apt): ?>
                                    <tr>
                                        <td><i class="fas fa-user-circle me-1" style="color:#94a3b8;"></i><?php echo htmlspecialchars($apt['customer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($apt['vehicle_make'] . ' ' . $apt['vehicle_model']); ?></td>
                                        <td><?php echo htmlspecialchars($apt['service_type']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                        <td>
                                            <?php
                                            $pillClass = 'wd-pill-default';
                                            if ($apt['status'] === 'completed') $pillClass = 'wd-pill-completed';
                                            elseif ($apt['status'] === 'pending') $pillClass = 'wd-pill-pending';
                                            elseif ($apt['status'] === 'cancelled') $pillClass = 'wd-pill-cancelled';
                                            elseif ($apt['status'] === 'confirmed') $pillClass = 'wd-pill-confirmed';
                                            ?>
                                            <span class="wd-status-pill <?php echo $pillClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- Services Overview -->
            <div class="wd-card">
                <div class="wd-card-head">
                    <h5><i class="fas fa-cogs me-2" style="color:#6366f1;"></i>Services</h5>
                    <a href="<?php echo SITE_URL; ?>/workshop/services.php" class="wd-view-all">Manage</a>
                </div>
                <div class="wd-card-body">
                    <?php if (empty($services)): ?>
                        <div class="wd-empty" style="padding:24px 12px;">
                            <i class="fas fa-tools" style="font-size:1.8rem;"></i>
                            <p style="margin:0;">No services added yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <span class="wd-service-tag"><i class="fas fa-wrench"></i><?php echo htmlspecialchars($service); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="wd-card">
                <div class="wd-card-head">
                    <h5><i class="fas fa-bolt me-2" style="color:#f59e0b;"></i>Quick Actions</h5>
                </div>
                <div class="wd-card-body">
                    <div class="wd-actions">
                        <a href="<?php echo SITE_URL; ?>/workshop/appointments.php" class="wd-action-link">
                            <div class="wd-action-icon wd-ai-blue"><i class="fas fa-calendar-check"></i></div>
                            <div>
                                View Appointments
                                <small>Manage and update bookings</small>
                            </div>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/workshop/services.php" class="wd-action-link">
                            <div class="wd-action-icon wd-ai-green"><i class="fas fa-cogs"></i></div>
                            <div>
                                Manage Services
                                <small>Add, edit or remove services</small>
                            </div>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/workshop/reviews.php" class="wd-action-link">
                            <div class="wd-action-icon wd-ai-pink"><i class="fas fa-star"></i></div>
                            <div>
                                View Reviews
                                <small>Check customer feedback</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
