<?php
$page_title = 'Manage Appointments';
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

$workshop_id = $workshop['workshop_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $new_status = sanitize($_POST['new_status'] ?? '');
        $allowed_statuses = ['pending', 'approved', 'in_progress', 'completed', 'cancelled'];

        if ($appointment_id > 0 && in_array($new_status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ? AND workshop_id = ?");
            $stmt->bind_param("sii", $new_status, $appointment_id, $workshop_id);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $conn->prepare("SELECT customer_id FROM appointments WHERE appointment_id = ?");
            $stmt2->bind_param("i", $appointment_id);
            $stmt2->execute();
            $cust = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if ($cust) {
                $status_label = ucfirst(str_replace('_', ' ', $new_status));
                $notif_title = 'Appointment ' . $status_label;
                $notif_msg = 'Your appointment at ' . $workshop['workshop_name'] . ' has been ' . $status_label . '.';
                $notif_link = SITE_URL . '/customer/bookings.php';
                $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                $stmt3->bind_param("isss", $cust['customer_id'], $notif_title, $notif_msg, $notif_link);
                $stmt3->execute();
                $stmt3->close();
            }

            setFlash('success', 'Appointment status updated successfully.');
        } else {
            setFlash('danger', 'Invalid request.');
        }
        redirect(SITE_URL . '/workshop/appointments.php');
    }

    if ($action === 'approve') {
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $workshop_notes = sanitize($_POST['workshop_notes'] ?? '');
        $estimated_cost = floatval($_POST['estimated_cost'] ?? 0);

        if ($appointment_id > 0) {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'approved', workshop_notes = ?, estimated_cost = ?, updated_at = NOW() WHERE appointment_id = ? AND workshop_id = ?");
            $stmt->bind_param("sdii", $workshop_notes, $estimated_cost, $appointment_id, $workshop_id);
            $stmt->execute();
            $stmt->close();

            $stmt2 = $conn->prepare("SELECT customer_id FROM appointments WHERE appointment_id = ?");
            $stmt2->bind_param("i", $appointment_id);
            $stmt2->execute();
            $cust = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if ($cust) {
                $notif_title = 'Appointment Approved';
                $notif_msg = 'Your appointment at ' . $workshop['workshop_name'] . ' has been approved. Estimated cost: ' . formatCurrency($estimated_cost);
                $notif_link = SITE_URL . '/customer/bookings.php';
                $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                $stmt3->bind_param("isss", $cust['customer_id'], $notif_title, $notif_msg, $notif_link);
                $stmt3->execute();
                $stmt3->close();
            }

            setFlash('success', 'Appointment approved successfully.');
        } else {
            setFlash('danger', 'Invalid request.');
        }
        redirect(SITE_URL . '/workshop/appointments.php');
    }
}

$filter_status = $_GET['status'] ?? '';
$valid_filters = ['', 'pending', 'approved', 'in_progress', 'completed', 'cancelled'];
if (!in_array($filter_status, $valid_filters)) {
    $filter_status = '';
}

$where_clause = "WHERE a.workshop_id = ?";
$params = [$workshop_id];
$types = "i";

if ($filter_status !== '') {
    $where_clause .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql = "SELECT a.*, u.name as customer_name, u.phone as customer_phone, u.email as customer_email FROM appointments a JOIN users u ON a.customer_id = u.user_id $where_clause ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$status_counts = [];
$count_sql = "SELECT status, COUNT(*) as cnt FROM appointments WHERE workshop_id = ? GROUP BY status";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $workshop_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count_stmt->close();
foreach ($count_result as $row) {
    $status_counts[$row['status']] = $row['cnt'];
}

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
            <h2><i class="fas fa-calendar-check me-2 text-danger"></i>Appointments</h2>
        </div>

        <div class="row g-3 mb-4">
            <div class="col">
                <a href="?status=" class="btn btn-sm <?php echo $filter_status === '' ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill">
                    All <span class="badge bg-light text-dark ms-1"><?php echo array_sum($status_counts); ?></span>
                </a>
            </div>
            <div class="col">
                <a href="?status=pending" class="btn btn-sm <?php echo $filter_status === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?> rounded-pill">
                    Pending <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['pending'] ?? 0; ?></span>
                </a>
            </div>
            <div class="col">
                <a href="?status=approved" class="btn btn-sm <?php echo $filter_status === 'approved' ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill">
                    Approved <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['approved'] ?? 0; ?></span>
                </a>
            </div>
            <div class="col">
                <a href="?status=in_progress" class="btn btn-sm <?php echo $filter_status === 'in_progress' ? 'btn-info' : 'btn-outline-info'; ?> rounded-pill">
                    In Progress <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['in_progress'] ?? 0; ?></span>
                </a>
            </div>
            <div class="col">
                <a href="?status=completed" class="btn btn-sm <?php echo $filter_status === 'completed' ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill">
                    Completed <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['completed'] ?? 0; ?></span>
                </a>
            </div>
            <div class="col">
                <a href="?status=cancelled" class="btn btn-sm <?php echo $filter_status === 'cancelled' ? 'btn-secondary' : 'btn-outline-secondary'; ?> rounded-pill">
                    Cancelled <span class="badge bg-light text-dark ms-1"><?php echo $status_counts['cancelled'] ?? 0; ?></span>
                </a>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-body">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No appointments found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $apt): ?>
                                    <tr>
                                        <td>#<?php echo $apt['appointment_id']; ?></td>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($apt['customer_name']); ?></strong></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($apt['customer_phone'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($apt['vehicle_year'] . ' ' . $apt['vehicle_make'] . ' ' . $apt['vehicle_model']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($apt['service_type']); ?></td>
                                        <td>
                                            <div><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></div>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                        </td>
                                        <td><?php echo $apt['estimated_cost'] > 0 ? formatCurrency($apt['estimated_cost']) : '-'; ?></td>
                                        <td><span class="dash-badge dash-badge-<?php echo $apt['status'] === 'completed' ? 'green' : ($apt['status'] === 'cancelled' ? 'red' : ($apt['status'] === 'pending' ? 'orange' : 'blue')); ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?></span></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="dash-btn-action dash-btn-outline btn-sm-modern" style="padding:6px 10px;" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $apt['appointment_id']; ?>" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($apt['status'] === 'pending'): ?>
                                                    <button class="dash-btn-action dash-btn-outline btn-sm-modern" style="padding:6px 10px;border-color:#28a745;color:#28a745;" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $apt['appointment_id']; ?>" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this appointment?');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                                        <input type="hidden" name="new_status" value="cancelled">
                                                        <button type="submit" class="dash-btn-action dash-btn-outline btn-sm-modern" style="padding:6px 10px;border-color:#dc3545;color:#dc3545;" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($apt['status'] === 'approved'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                                        <input type="hidden" name="new_status" value="in_progress">
                                                        <button type="submit" class="dash-btn-action dash-btn-outline btn-sm-modern" style="padding:6px 10px;" title="Start Work">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($apt['status'] === 'in_progress'): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Mark as completed?');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                                        <input type="hidden" name="new_status" value="completed">
                                                        <button type="submit" class="dash-btn-action dash-btn-outline btn-sm-modern" style="padding:6px 10px;border-color:#28a745;color:#28a745;" title="Mark Completed">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Detail Modal -->
                                    <div class="modal fade" id="detailModal<?php echo $apt['appointment_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Appointment #<?php echo $apt['appointment_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-6">
                                                            <strong>Customer:</strong><br>
                                                            <?php echo htmlspecialchars($apt['customer_name']); ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Phone:</strong><br>
                                                            <?php echo htmlspecialchars($apt['customer_phone'] ?? 'N/A'); ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Email:</strong><br>
                                                            <?php echo htmlspecialchars($apt['customer_email'] ?? 'N/A'); ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Status:</strong><br>
                                                            <span class="dash-badge dash-badge-<?php echo $apt['status'] === 'completed' ? 'green' : ($apt['status'] === 'cancelled' ? 'red' : ($apt['status'] === 'pending' ? 'orange' : 'blue')); ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?></span>
                                                        </div>
                                                        <div class="col-12"><hr></div>
                                                        <div class="col-6">
                                                            <strong>Vehicle:</strong><br>
                                                            <?php echo htmlspecialchars($apt['vehicle_year'] . ' ' . $apt['vehicle_make'] . ' ' . $apt['vehicle_model']); ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Service:</strong><br>
                                                            <?php echo htmlspecialchars($apt['service_type']); ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Date & Time:</strong><br>
                                                            <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Estimated Cost:</strong><br>
                                                            <?php echo $apt['estimated_cost'] > 0 ? formatCurrency($apt['estimated_cost']) : 'Not set'; ?>
                                                        </div>
                                                        <div class="col-12">
                                                            <strong>Description:</strong><br>
                                                            <?php echo htmlspecialchars($apt['description'] ?: 'No description provided.'); ?>
                                                        </div>
                                                        <?php if (!empty($apt['workshop_notes'])): ?>
                                                            <div class="col-12">
                                                                <strong>Workshop Notes:</strong><br>
                                                                <?php echo htmlspecialchars($apt['workshop_notes']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($apt['status'] === 'pending'): ?>
                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?php echo $apt['appointment_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Approve Appointment #<?php echo $apt['appointment_id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Customer</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($apt['customer_name']); ?>" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Vehicle</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($apt['vehicle_year'] . ' ' . $apt['vehicle_make'] . ' ' . $apt['vehicle_model']); ?>" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Service</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($apt['service_type']); ?>" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="workshop_notes<?php echo $apt['appointment_id']; ?>">Workshop Notes</label>
                                                            <textarea class="form-control" id="workshop_notes<?php echo $apt['appointment_id']; ?>" name="workshop_notes" rows="3" placeholder="Add notes for the customer..."></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="estimated_cost<?php echo $apt['appointment_id']; ?>">Estimated Cost ($)</label>
                                                            <input type="number" class="form-control" id="estimated_cost<?php echo $apt['appointment_id']; ?>" name="estimated_cost" step="0.01" min="0" placeholder="0.00">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Approve</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
