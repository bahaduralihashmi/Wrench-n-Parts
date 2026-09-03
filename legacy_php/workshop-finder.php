<?php
$page_title = 'Workshop Finder';
require_once __DIR__ . '/includes/config.php';

$where = "WHERE w.status IN ('active', 'approved')";
$params = [];
$types = "";
$city = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if ($search) {
    $s = '%' . $search . '%';
    $where .= " AND (w.workshop_name LIKE ? OR w.services LIKE ? OR w.description LIKE ?)";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= "sss";
}

if ($city && $city !== 'all') {
    $where .= " AND w.location LIKE ?";
    $params[] = '%' . $city . '%';
    $types .= "s";
}

if (!empty($params)) {
    $stmt = $conn->prepare("SELECT w.*, u.name as owner_name FROM workshops w JOIN users u ON w.user_id = u.user_id $where ORDER BY w.rating DESC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $workshops = $stmt->get_result();
} else {
    $workshops = $conn->query("SELECT w.*, u.name as owner_name FROM workshops w JOIN users u ON w.user_id = u.user_id $where ORDER BY w.rating DESC");
}

$cities = ['Lahore', 'Islamabad', 'Karachi', 'Multan'];

if (isset($_GET['book'])) {
    if (!$logged_in || $current_user['role'] !== 'customer') {
        setFlash('warning', 'Please login as a customer to book.');
        header("Location: " . SITE_URL . "/login.php");
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wid = intval($_GET['book']);
        $uid = $_SESSION['user_id'];
        $vmake = sanitize($_POST['vehicle_make']);
        $vmodel = sanitize($_POST['vehicle_model']);
        $vyear = intval($_POST['vehicle_year']);
        $stypeArr = isset($_POST['service_type']) ? array_map('sanitize', $_POST['service_type']) : [];
        $stype = implode(', ', $stypeArr);
        $desc = sanitize($_POST['description']);
        $adate = $_POST['appointment_date'];
        $atime = $_POST['appointment_time'];

        $stmt = $conn->prepare("INSERT INTO appointments (customer_id, workshop_id, vehicle_make, vehicle_model, vehicle_year, service_type, description, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iississss", $uid, $wid, $vmake, $vmodel, $vyear, $stype, $desc, $adate, $atime);
        if ($stmt->execute()) {
            setFlash('success', 'Appointment booked successfully! Awaiting workshop approval.');
        } else {
            setFlash('danger', 'Booking failed. Try again.');
        }
        $stmt->close();
        header("Location: " . SITE_URL . "/workshop-finder.php");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

if (isset($_GET['book']) && $_SERVER['REQUEST_METHOD'] !== 'POST'):
    $wid = intval($_GET['book']);
    $ws = $conn->query("SELECT * FROM workshops WHERE workshop_id = $wid")->fetch_assoc();
?>
<div class="container section-v2 page-enter" style="padding-left:40px;padding-right:40px;">
    <h2 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Book Appointment - <?php echo $ws['workshop_name']; ?></h2>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-modern">
                <div class="card-body">
                    <form method="POST" class="form-modern">
                        <div class="row g-3">
                            <div class="col-md-6 form-group">
                                <label class="form-label">Vehicle Make *</label>
                                <input type="text" name="vehicle_make" class="form-control" required placeholder="e.g., Toyota">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Vehicle Model *</label>
                                <input type="text" name="vehicle_model" class="form-control" required placeholder="e.g., Camry">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Year *</label>
                                <input type="number" name="vehicle_year" class="form-control" required min="1990" max="<?php echo date('Y') + 1; ?>" value="<?php echo date('Y'); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Service Type *</label>
                                <div style="border:1px solid #dee2e6;border-radius:10px;padding:12px;max-height:160px;overflow-y:auto;background:#fafbfc;">
                                    <?php
                                    $services = array_filter(array_map('trim', explode(',', $ws['services'])));
                                    foreach ($services as $s):
                                    ?>
                                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;">
                                        <input type="checkbox" name="service_type[]" value="<?php echo htmlspecialchars($s); ?>" id="svc_<?php echo md5($s); ?>" style="accent-color:#dc3545;width:16px;height:16px;">
                                        <label for="svc_<?php echo md5($s); ?>" style="margin:0;font-size:0.88rem;cursor:pointer;"><?php echo htmlspecialchars($s); ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;">
                                        <input type="checkbox" name="service_type[]" value="General Servicing" id="svc_gen" style="accent-color:#dc3545;width:16px;height:16px;">
                                        <label for="svc_gen" style="margin:0;font-size:0.88rem;cursor:pointer;">General Servicing</label>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;">
                                        <input type="checkbox" name="service_type[]" value="Other" id="svc_other" style="accent-color:#dc3545;width:16px;height:16px;">
                                        <label for="svc_other" style="margin:0;font-size:0.88rem;cursor:pointer;">Other</label>
                                    </div>
                                </div>
                                <small class="text-muted">Select one or more services</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Appointment Date *</label>
                                <input type="date" name="appointment_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="form-label">Appointment Time *</label>
                                <input type="time" name="appointment_time" class="form-control" required>
                            </div>
                            <div class="col-12 form-group">
                                <label class="form-label">Issue Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Describe your vehicle issue..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-modern btn-primary-modern btn-lg-modern w-100"><i class="fas fa-check me-2"></i>Confirm Booking</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <a href="workshop-finder.php" class="btn-modern btn-outline-modern mt-3"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="container section-v2 page-enter" style="padding-left:40px;padding-right:40px;">
    <div class="mb-4" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-weight:800;font-size:1.6rem;"><i class="fas fa-tools me-2"></i>Find Nearby Workshops</h2>
            <p class="text-muted">Browse admin-approved, trusted repair workshops near you for servicing, repairs & fitting.</p>
        </div>
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="btn btn-outline-secondary" style="border-radius:10px;font-weight:600;font-size:0.88rem;padding:10px 20px;display:inline-flex;align-items:center;gap:6px;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div style="background:#f8f9fa;border-radius:14px;padding:20px 24px;margin-bottom:28px;border:1px solid #e9ecef;">
        <form method="GET" id="workshopSearchForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Search by name or service</label>
                    <input type="text" name="search" class="form-control" placeholder="e.g. AC Service, Denting, AutoCare..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">City</label>
                    <select name="city" class="form-select">
                        <option value="all">All cities</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $city === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" onclick="findNearMe()" class="btn btn-dark w-100" style="border-radius:10px;font-weight:600;font-size:0.88rem;padding:10px 16px;">
                        <i class="fas fa-location-arrow me-1"></i> Near me shop
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="workshop-finder.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;font-weight:600;font-size:0.88rem;padding:10px 16px;">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </div>
            <input type="hidden" name="lat" id="latInput">
            <input type="hidden" name="lng" id="lngInput">
        </form>
    </div>
    <div id="locationMsg" style="display:none;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:10px 16px;margin-bottom:20px;color:#856404;font-size:0.88rem;"></div>
    <div class="row g-4">
        <?php if ($workshops->num_rows === 0): ?>
            <div class="col-12 text-center py-5">
                <div style="width:90px;height:90px;border-radius:50%;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px;">
                    <i class="fas fa-tools" style="font-size:2.2rem;color:#bbb;"></i>
                </div>
                <h5 style="font-weight:700;color:#444;">No workshops found</h5>
                <p class="text-muted">Try adjusting your search or city filter.</p>
            </div>
        <?php endif; ?>
        <?php while ($ws = $workshops->fetch_assoc()):
            $phone = preg_replace('/[^0-9]/', '', $ws['contact']);
        ?>
        <div class="col-md-6">
            <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.06);border:1px solid #eee;transition:all 0.3s;height:100%;display:flex;flex-direction:column;" onmouseover="this.style.boxShadow='0 8px 30px rgba(0,0,0,0.12)';this.style.transform='translateY(-3px)'" onmouseout="this.style.boxShadow='0 2px 16px rgba(0,0,0,0.06)';this.style.transform='translateY(0)'">
                <div style="padding:24px 24px 0;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                        <div>
                            <h5 style="font-weight:800;font-size:1.15rem;margin:0 0 4px;color:#1a1a2e;"><?php echo htmlspecialchars($ws['workshop_name']); ?></h5>
                            <small style="color:#888;"><i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($ws['owner_name']); ?></small>
                        </div>
                        <span style="background:#d4edda;color:#155724;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;">Active</span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:14px;margin:14px 0;font-size:0.88rem;color:#555;">
                        <span><i class="fas fa-map-marker-alt me-1" style="color:#dc3545;"></i><?php echo htmlspecialchars($ws['location']); ?></span>
                        <span><i class="fas fa-phone me-1" style="color:#0d6efd;"></i><?php echo htmlspecialchars($ws['contact']); ?></span>
                        <span><i class="fas fa-clock me-1" style="color:#0dcaf0;"></i><?php echo date('g:i A', strtotime($ws['opening_time'])); ?> &ndash; <?php echo date('g:i A', strtotime($ws['closing_time'])); ?></span>
                    </div>
                    <div style="margin-bottom:14px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star" style="color:<?php echo $i <= round($ws['rating']) ? '#f4c430' : '#ddd'; ?>;font-size:0.9rem;"></i>
                        <?php endfor; ?>
                        <small style="color:#888;margin-left:4px;">(<?php echo $ws['total_reviews']; ?> reviews)</small>
                    </div>
                </div>
                <div style="padding:0 24px 14px;">
                    <div style="font-size:0.82rem;color:#666;margin-bottom:12px;line-height:1.5;">
                        <strong style="color:#444;">Services:</strong><br>
                        <?php
                        $svcs = array_slice(explode(',', $ws['services']), 0, 6);
                        foreach ($svcs as $sv):
                        ?>
                            <span style="display:inline-block;background:#f0f4f8;color:#495057;padding:4px 10px;border-radius:6px;margin:3px 4px 3px 0;font-size:0.78rem;font-weight:500;"><?php echo htmlspecialchars(trim($sv)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($ws['description']): ?>
                        <p style="color:#888;font-size:0.82rem;margin:0 0 14px;line-height:1.5;"><?php echo htmlspecialchars(substr($ws['description'], 0, 120)); ?><?php echo strlen($ws['description']) > 120 ? '...' : ''; ?></p>
                    <?php endif; ?>
                </div>
                <div style="padding:14px 24px;background:#f9fafb;border-top:1px solid #f0f0f0;margin-top:auto;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="workshop-finder.php?book=<?php echo $ws['workshop_id']; ?>" style="flex:1;min-width:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#dc3545;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.85rem;transition:background 0.3s;" onmouseover="this.style.background='#bb2d3b'" onmouseout="this.style.background='#dc3545'">
                            <i class="fas fa-calendar-plus"></i> Book
                        </a>
                        <a href="tel:<?php echo $phone; ?>" style="flex:1;min-width:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#198754;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.85rem;transition:background 0.3s;" onmouseover="this.style.background='#157347'" onmouseout="this.style.background='#198754'">
                            <i class="fas fa-phone"></i> Call
                        </a>
                        <a href="https://wa.me/<?php echo $phone; ?>" target="_blank" style="flex:1;min-width:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#25d366;color:#fff;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.85rem;transition:background 0.3s;" onmouseover="this.style.background='#1da851'" onmouseout="this.style.background='#25d366'">
                            <i class="fab fa-whatsapp"></i> Message
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<script>
function findNearMe() {
    var msg = document.getElementById('locationMsg');
    if (!navigator.geolocation) {
        msg.style.display = 'block';
        msg.textContent = 'Geolocation is not supported by your browser. Pick a city from the dropdown instead.';
        return;
    }
    msg.style.display = 'block';
    msg.textContent = 'Requesting your location...';
    msg.style.background = '#d1ecf1';
    msg.style.borderColor = '#bee5eb';
    msg.style.color = '#0c5460';

    navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById('latInput').value = pos.coords.latitude;
        document.getElementById('lngInput').value = pos.coords.longitude;
        // Reverse geocode to find city using nominatim
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + pos.coords.latitude + '&lon=' + pos.coords.longitude)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var city = data.address.city || data.address.town || data.address.village || data.address.state || '';
                if (city) {
                    var sel = document.querySelector('select[name="city"]');
                    for (var i = 0; i < sel.options.length; i++) {
                        if (sel.options[i].text.toLowerCase() === city.toLowerCase()) {
                            sel.selectedIndex = i;
                            break;
                        }
                    }
                    msg.textContent = 'Location found: ' + city + '. Showing workshops nearby.';
                    msg.style.background = '#d4edda';
                    msg.style.borderColor = '#c3e6cb';
                    msg.style.color = '#155724';
                    document.getElementById('workshopSearchForm').submit();
                } else {
                    msg.textContent = 'Could not detect your city. Please pick a city from the dropdown.';
                    msg.style.background = '#fff3cd';
                    msg.style.borderColor = '#ffc107';
                    msg.style.color = '#856404';
                }
            })
            .catch(function() {
                msg.textContent = 'Could not determine your city. Please pick one from the dropdown.';
                msg.style.background = '#fff3cd';
                msg.style.borderColor = '#ffc107';
                msg.style.color = '#856404';
            });
    }, function(err) {
        msg.textContent = 'Location access was denied. Please allow location access, or pick a city from the dropdown instead.';
        msg.style.background = '#f8d7da';
        msg.style.borderColor = '#f5c6cb';
        msg.style.color = '#721c24';
    });
}
</script>
<style>
@media(max-width:768px){
    .page-enter[style*="padding-left:40px"]{padding-left:16px!important;padding-right:16px!important}
}
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
