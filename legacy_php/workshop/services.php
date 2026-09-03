<?php
$page_title = 'Manage Services';
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
    $action = $_POST['action'] ?? '';

    if ($action === 'add_service') {
        $new_service = trim($_POST['new_service'] ?? '');
        if (!empty($new_service)) {
            $current_services = $workshop['services'] ? explode(',', $workshop['services']) : [];
            $current_services = array_map('trim', $current_services);
            $current_services = array_filter($current_services);

            if (!in_array($new_service, $current_services)) {
                $current_services[] = $new_service;
                $services_str = implode(',', $current_services);
                $stmt = $conn->prepare("UPDATE workshops SET services = ? WHERE workshop_id = ?");
                $stmt->bind_param("si", $services_str, $workshop_id);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Service "' . htmlspecialchars($new_service) . '" added successfully.');
            } else {
                setFlash('warning', 'Service already exists.');
            }
        } else {
            setFlash('danger', 'Please enter a service name.');
        }
        redirect(SITE_URL . '/workshop/services.php');
    }

    if ($action === 'remove_service') {
        $remove_service = trim($_POST['service_name'] ?? '');
        if (!empty($remove_service)) {
            $current_services = $workshop['services'] ? explode(',', $workshop['services']) : [];
            $current_services = array_map('trim', $current_services);
            $current_services = array_filter($current_services);
            $current_services = array_values(array_diff($current_services, [$remove_service]));
            $services_str = implode(',', $current_services);
            $stmt = $conn->prepare("UPDATE workshops SET services = ? WHERE workshop_id = ?");
            $stmt->bind_param("si", $services_str, $workshop_id);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Service removed successfully.');
        }
        redirect(SITE_URL . '/workshop/services.php');
    }

    if ($action === 'update_details') {
        $description = sanitize($_POST['description'] ?? '');
        $opening_time = sanitize($_POST['opening_time'] ?? '08:00:00');
        $closing_time = sanitize($_POST['closing_time'] ?? '18:00:00');
        $location = sanitize($_POST['location'] ?? '');
        $contact = sanitize($_POST['contact'] ?? '');

        $stmt = $conn->prepare("UPDATE workshops SET description = ?, opening_time = ?, closing_time = ?, location = ?, contact = ? WHERE workshop_id = ?");
        $stmt->bind_param("sssssi", $description, $opening_time, $closing_time, $location, $contact, $workshop_id);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Workshop details updated successfully.');
        redirect(SITE_URL . '/workshop/services.php');
    }
}

$stmt = $conn->prepare("SELECT * FROM workshops WHERE workshop_id = ?");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$workshop = $stmt->get_result()->fetch_assoc();
$stmt->close();

$services = [];
if (!empty($workshop['services'])) {
    $services = array_map('trim', explode(',', $workshop['services']));
    $services = array_filter($services);
}

require_once __DIR__ . '/../includes/header.php';

$suggestion_services = [
    'Engine Repair', 'Oil Change', 'AC Service', 'Brake Service',
    'Denting & Painting', 'General Service', 'Wheel Alignment',
    'Battery Replacement', 'Electrical Work', 'Transmission Repair',
    'Suspension Work', 'Tire Replacement', 'Filter Replacement',
    'Coolant Flush', 'Fuel System', 'Exhaust Repair',
    'Steering Repair', 'Clutch Repair', 'Engine Tuning',
    'Spark Plug Replacement', 'Car Wash & Detailing', 'Diagnostics'
];
?>

<style>
.ws-suggest-wrap{position:relative}
.ws-suggest-input{width:100%;padding:12px 16px;border:2px solid #e8eaed;border-radius:12px;font-size:0.92rem;outline:none;transition:border-color .2s;background:#fff}
.ws-suggest-input:focus{border-color:#dc3545;box-shadow:0 0 0 3px rgba(220,53,69,0.08)}
.ws-suggest-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e8eaed;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,0.1);z-index:50;max-height:240px;overflow-y:auto;display:none}
.ws-suggest-dropdown.show{display:block}
.ws-suggest-item{padding:10px 16px;cursor:pointer;font-size:0.88rem;display:flex;align-items:center;gap:10px;transition:background .15s}
.ws-suggest-item:hover,.ws-suggest-item.active{background:#fff0f0;color:#dc3545}
.ws-suggest-item i{color:#dc3545;font-size:0.8rem;width:18px;text-align:center}
.ws-suggest-item.already-added{color:#999;background:#f8f8f8}
.ws-suggest-item.already-added i{color:#999}
.ws-suggest-label{font-size:0.72rem;color:#999;padding:6px 16px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px}
.ws-suggest-tag{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#fff0f0,#ffe8e8);color:#dc3545;padding:6px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;cursor:default;border:1px solid rgba(220,53,69,0.15);animation:wsTagIn .25s ease}
@keyframes wsTagIn{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}
.ws-suggest-tag .ws-tag-remove{cursor:pointer;opacity:0.6;transition:opacity .15s;font-size:0.75rem}
.ws-suggest-tag .ws-tag-remove:hover{opacity:1}
.ws-suggest-count{font-size:0.75rem;color:#999;margin-left:auto}
</style>

<button class="admin-sidebar-toggle" id="workshopSidebarToggle" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.toggle('show');document.getElementById('workshopOverlay').classList.toggle('active')">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="workshopOverlay" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.remove('show');this.classList.remove('active')"></div>
<div class="dash-layout">
    <?php require_once __DIR__ . '/../includes/workshop-sidebar.php'; ?>

    <div class="dash-main">
        <a href="<?php echo SITE_URL; ?>/workshop/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="dash-header">
            <h2><i class="fas fa-cogs me-2 text-danger"></i>Services & Workshop Details</h2>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="dash-card mb-4">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-plus-circle me-2 text-success"></i>Add New Service</h5>
                        <form method="POST" id="addServiceForm">
                            <input type="hidden" name="action" value="add_service">
                            <input type="hidden" name="new_service" id="selectedServiceInput">
                            <div class="ws-suggest-wrap">
                                <input type="text" class="ws-suggest-input" id="serviceInput" placeholder="Type or select a service..." autocomplete="off">
                                <div class="ws-suggest-dropdown" id="serviceDropdown"></div>
                            </div>
                            <button type="submit" class="dash-btn-action dash-btn-primary mt-3" id="addServiceBtn" disabled style="opacity:0.5;cursor:not-allowed;"><i class="fas fa-plus me-1"></i>Add Service</button>
                        </form>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-list me-2 text-primary"></i>Current Services</h5>
                        <?php if (empty($services)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-tools fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No services added yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($services as $service): ?>
                                    <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span><?php echo htmlspecialchars($service); ?></span>
                                        <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Remove this service?');">
                                            <input type="hidden" name="action" value="remove_service">
                                            <input type="hidden" name="service_name" value="<?php echo htmlspecialchars($service); ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remove">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dash-card">
                    <div class="dash-card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-edit me-2 text-warning"></i>Workshop Details</h5>
                        <form method="POST" class="form-modern">
                            <input type="hidden" name="action" value="update_details">
                            <div class="mb-3">
                                <label class="form-label">Workshop Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($workshop['workshop_name']); ?>" disabled>
                                <small class="text-muted">Contact admin to change workshop name.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($workshop['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($workshop['location'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($workshop['contact'] ?? ''); ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Opening Time</label>
                                    <input type="time" class="form-control" name="opening_time" value="<?php echo htmlspecialchars($workshop['opening_time'] ?? '08:00:00'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Closing Time</label>
                                    <input type="time" class="form-control" name="closing_time" value="<?php echo htmlspecialchars($workshop['closing_time'] ?? '18:00:00'); ?>">
                                </div>
                            </div>
                            <button type="submit" class="dash-btn-action dash-btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const suggestions = <?php echo json_encode($suggestion_services); ?>;
    const currentServices = <?php echo json_encode(array_values($services)); ?>;

    const input = document.getElementById('serviceInput');
    const dropdown = document.getElementById('serviceDropdown');
    const hiddenInput = document.getElementById('selectedServiceInput');
    const addBtn = document.getElementById('addServiceBtn');
    const form = document.getElementById('addServiceForm');
    let activeIndex = -1;

    function renderDropdown(query) {
        const q = query.toLowerCase().trim();
        let filtered = suggestions;
        if (q) {
            filtered = suggestions.filter(s => s.toLowerCase().includes(q));
        }

        if (filtered.length === 0 && q) {
            dropdown.innerHTML = '<div class="ws-suggest-label">Suggestions</div><div class="ws-suggest-item" data-value="' + q + '"><i class="fas fa-plus"></i> Add "' + q + '" as new service</div>';
            dropdown.classList.add('show');
            return;
        }

        const added = filtered.filter(s => currentServices.includes(s));
        const notAdded = filtered.filter(s => !currentServices.includes(s));

        let html = '';
        if (notAdded.length > 0) {
            html += '<div class="ws-suggest-label">Available Services <span class="ws-suggest-count">' + notAdded.length + '</span></div>';
            notAdded.forEach(s => {
                html += '<div class="ws-suggest-item" data-value="' + s + '"><i class="fas fa-plus-circle"></i> ' + s + '</div>';
            });
        }
        if (added.length > 0) {
            html += '<div class="ws-suggest-label">Already Added</div>';
            added.forEach(s => {
                html += '<div class="ws-suggest-item already-added" data-value="' + s + '"><i class="fas fa-check-circle"></i> ' + s + '</div>';
            });
        }
        dropdown.innerHTML = html;
        dropdown.classList.add('show');
        activeIndex = -1;
    }

    input.addEventListener('input', function() {
        renderDropdown(this.value);
        hiddenInput.value = '';
        addBtn.disabled = true;
        addBtn.style.opacity = '0.5';
        addBtn.style.cursor = 'not-allowed';
    });

    input.addEventListener('focus', function() {
        renderDropdown(this.value);
    });

    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.ws-suggest-item:not(.already-added)');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && items[activeIndex]) {
                selectItem(items[activeIndex]);
            } else if (input.value.trim()) {
                hiddenInput.value = input.value.trim();
                input.value = input.value.trim();
                addBtn.disabled = false;
                addBtn.style.opacity = '1';
                addBtn.style.cursor = 'pointer';
                dropdown.classList.remove('show');
            }
        } else if (e.key === 'Escape') {
            dropdown.classList.remove('show');
        }
    });

    dropdown.addEventListener('click', function(e) {
        const item = e.target.closest('.ws-suggest-item');
        if (item) selectItem(item);
    });

    function selectItem(el) {
        const val = el.dataset.value;
        if (el.classList.contains('already-added')) return;
        hiddenInput.value = val;
        input.value = val;
        addBtn.disabled = false;
        addBtn.style.opacity = '1';
        addBtn.style.cursor = 'pointer';
        dropdown.classList.remove('show');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ws-suggest-wrap')) {
            dropdown.classList.remove('show');
        }
    });
})();
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
