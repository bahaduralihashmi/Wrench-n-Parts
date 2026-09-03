<?php
$page_title = 'My Hot Deals';
require_once __DIR__ . '/../includes/config.php';
requireRole('shopkeeper');

$shopkeeper_id = intval($_SESSION['user_id']);
$shop = $conn->query("SELECT * FROM shops WHERE user_id = $shopkeeper_id")->fetch_assoc();
if (!$shop) {
    setFlash('danger', 'No shop found for your account.');
    redirect(SITE_URL . '/shopkeeper/dashboard.php');
}
$shop_id = $shop['shop_id'];

if (!in_array($shop['status'], ['active', 'approved'])) {
    $page_title = 'Pending Approval';
    include __DIR__ . '/../includes/header.php';
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
                    <small style="color:#888;font-size:0.75rem;"><?php echo htmlspecialchars($shop['shop_name']); ?></small>
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
                <a class="dash-nav-link" href="profile.php"><i class="fas fa-user-cog"></i>Profile</a>
            </nav>
            <div class="dash-sidebar-footer">
                <a class="dash-nav-link logout" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
            </div>
        </div>
        <div class="dash-main" style="display:flex;align-items:center;justify-content:center;min-height:60vh;text-align:center;">
            <div>
                <i class="fas fa-clock" style="font-size:3rem;color:#f39c12;margin-bottom:16px;"></i>
                <h3>Shop Pending Approval</h3>
                <p style="color:#888;">Your shop is awaiting admin approval. You can create hot deals once approved.</p>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if (isset($_POST['add_deal'])) {
    verifyCsrf();
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $discount_text = sanitize($_POST['discount_text']);
    $coupon_code = sanitize($_POST['coupon_code']);
    $button_text = sanitize($_POST['button_text']);
    $button_link = sanitize($_POST['button_link']);
    $category = sanitize($_POST['category']);
    $start_date = sanitize($_POST['start_date']) ?: date('Y-m-d');
    $end_date = sanitize($_POST['end_date']) ?: date('Y-m-d', strtotime('+30 days'));
    $created_by = $shopkeeper_id;

    if ($end_date < $start_date) {
        setFlash('danger', 'End date cannot be before start date.');
        redirect('hot-deals.php');
    }

    $banner = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array(strtolower($ext), $allowed) && $_FILES['banner_image']['size'] <= 5242880) {
            $filename = 'deal_' . $shop_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], __DIR__ . '/../uploads/' . $filename)) {
                $banner = $filename;
            }
        }
    }

    if (empty($shop_id)) {
        setFlash('danger', 'No shop found for your account.');
        redirect('hot-deals.php');
    }

    $stmt = $conn->prepare("INSERT INTO hot_deals (shop_id, title, description, banner_image, discount_text, coupon_code, button_text, button_link, category, priority, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'active', ?)");
    $stmt->bind_param("issssssssssi", $shop_id, $title, $description, $banner, $discount_text, $coupon_code, $button_text, $button_link, $category, $start_date, $end_date, $created_by);
    if ($stmt->execute()) {
        setFlash('success', 'Hot deal created successfully! It is now live on the homepage.');
    } else {
        setFlash('danger', 'Error creating deal.');
    }
    $stmt->close();
    redirect(SITE_URL . '/index.php');
}

if (isset($_POST['update_deal'])) {
    verifyCsrf();
    $id = intval($_POST['deal_id']);
    $check = $conn->query("SELECT id FROM hot_deals WHERE id = $id AND shop_id = $shop_id")->fetch_assoc();
    if (!$check) {
        setFlash('danger', 'Unauthorized access.');
        redirect('hot-deals.php');
    }
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $discount_text = sanitize($_POST['discount_text']);
    $coupon_code = sanitize($_POST['coupon_code']);
    $button_text = sanitize($_POST['button_text']);
    $button_link = sanitize($_POST['button_link']);
    $category = sanitize($_POST['category']);
    $start_date = sanitize($_POST['start_date']) ?: date('Y-m-d');
    $end_date = sanitize($_POST['end_date']) ?: date('Y-m-d', strtotime('+30 days'));
    $status = sanitize($_POST['status']) ?: 'active';

    if ($end_date < $start_date) {
        setFlash('danger', 'End date cannot be before start date.');
        redirect('hot-deals.php');
    }

    $banner = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array(strtolower($ext), $allowed) && $_FILES['banner_image']['size'] <= 5242880) {
            $filename = 'deal_' . $shop_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], __DIR__ . '/../uploads/' . $filename)) {
                $old = $conn->query("SELECT banner_image FROM hot_deals WHERE id = $id")->fetch_assoc();
                if ($old && $old['banner_image'] && file_exists(__DIR__ . '/../uploads/' . $old['banner_image'])) {
                    unlink(__DIR__ . '/../uploads/' . $old['banner_image']);
                }
                $banner = $filename;
            }
        }
    }

    if ($banner !== null) {
        $stmt = $conn->prepare("UPDATE hot_deals SET title=?, description=?, banner_image=?, discount_text=?, coupon_code=?, button_text=?, button_link=?, category=?, start_date=?, end_date=?, status=? WHERE id=? AND shop_id=?");
        $stmt->bind_param("sssssssssssii", $title, $description, $banner, $discount_text, $coupon_code, $button_text, $button_link, $category, $start_date, $end_date, $status, $id, $shop_id);
    } else {
        $stmt = $conn->prepare("UPDATE hot_deals SET title=?, description=?, discount_text=?, coupon_code=?, button_text=?, button_link=?, category=?, start_date=?, end_date=?, status=? WHERE id=? AND shop_id=?");
        $stmt->bind_param("ssssssssssii", $title, $description, $discount_text, $coupon_code, $button_text, $button_link, $category, $start_date, $end_date, $status, $id, $shop_id);
    }
    if ($stmt->execute()) {
        setFlash('success', 'Deal updated successfully.');
    } else {
        setFlash('danger', 'Error updating deal.');
    }
    $stmt->close();
    redirect('hot-deals.php');
}

if (isset($_POST['delete_deal'])) {
    verifyCsrf();
    $id = intval($_POST['deal_id']);
    $old = $conn->query("SELECT banner_image FROM hot_deals WHERE id = $id AND shop_id = $shop_id")->fetch_assoc();
    if (!$old) {
        setFlash('danger', 'Unauthorized access.');
        redirect('hot-deals.php');
    }
    if ($old['banner_image'] && file_exists(__DIR__ . '/../uploads/' . $old['banner_image'])) {
        unlink(__DIR__ . '/../uploads/' . $old['banner_image']);
    }
    $stmt = $conn->prepare("DELETE FROM hot_deals WHERE id = ? AND shop_id = ?");
    $stmt->bind_param("ii", $id, $shop_id);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Deal deleted.');
    redirect('hot-deals.php');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where = "WHERE hd.shop_id = ?";
$params = [$shop_id];
$types = "i";

if ($search !== '') {
    $where .= " AND (hd.title LIKE ? OR hd.description LIKE ? OR hd.category LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
    $types .= "sss";
}
if ($statusFilter !== '') {
    $where .= " AND hd.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$stmt = $conn->prepare("SELECT hd.* FROM hot_deals hd $where ORDER BY hd.priority ASC, hd.created_at DESC");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$deals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalDeals = count($deals);
$activeDeals = count(array_filter($deals, fn($d) => $d['status'] === 'active'));
$inactiveDeals = count(array_filter($deals, fn($d) => $d['status'] === 'inactive'));
$expiringSoon = count(array_filter($deals, fn($d) => $d['end_date'] && strtotime($d['end_date']) - time() < 7 * 86400 && strtotime($d['end_date']) > time()));

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.hd-wrap{padding:0;display:flex;gap:0;min-height:100vh}
.hd-sidebar{width:260px;display:flex;flex-direction:column}
.hd-sidebar .dash-sidebar-brand{padding:20px;border-bottom:1px solid #f0f0f0}
.hd-sidebar .dash-nav{padding:8px 12px;flex:1}
.hd-sidebar .dash-nav .dash-nav-link{border-radius:10px;margin-bottom:2px}
.hd-sidebar .dash-nav .dash-nav-link.active{background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;box-shadow:0 4px 12px rgba(220,53,69,0.3)}
.hd-sidebar .dash-sidebar-footer{padding:12px;border-top:1px solid #f0f0f0}
.hd-main{flex:1;padding:28px 32px;margin-left:260px}
.hd-back{color:#777;text-decoration:none;font-size:0.85rem;font-weight:500;display:inline-flex;align-items:center;gap:5px;margin-bottom:16px;transition:color .2s}
.hd-back:hover{color:#dc3545}
.hd-page-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.hd-page-title{font-size:1.6rem;font-weight:800;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.hd-page-title i{color:#dc3545;font-size:1.3rem}
.hd-add-btn{padding:10px 22px;background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:none;border-radius:12px;font-weight:600;font-size:0.9rem;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(220,53,69,0.25);transition:all .3s}
.hd-add-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(220,53,69,0.35)}

.hd-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.hd-stat{background:#fff;border-radius:16px;padding:20px;display:flex;align-items:center;gap:14px;border:1px solid #f0f0f0;transition:transform .2s,box-shadow .2s}
.hd-stat:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.06)}
.hd-stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.hd-stat-icon.total{background:linear-gradient(135deg,#e3f2fd,#bbdefb);color:#1565c0}
.hd-stat-icon.active{background:linear-gradient(135deg,#e8f5e9,#c8e6c9);color:#2e7d32}
.hd-stat-icon.inactive{background:linear-gradient(135deg,#fff3e0,#ffe0b2);color:#e65100}
.hd-stat-icon.expiring{background:linear-gradient(135deg,#fce4ec,#f8bbd0);color:#c62828}
.hd-stat-num{font-size:1.5rem;font-weight:800;color:#1a1a2e;line-height:1}
.hd-stat-label{font-size:0.78rem;color:#888;margin-top:2px}

.hd-filter-bar{background:#fff;border-radius:16px;padding:16px 20px;margin-bottom:24px;border:1px solid #f0f0f0;display:flex;gap:12px;align-items:center}
.hd-filter-bar input,.hd-filter-bar select{border:1px solid #e8eaed;border-radius:10px;padding:10px 14px;font-size:0.88rem;outline:none;transition:border-color .2s}
.hd-filter-bar input:focus,.hd-filter-bar select:focus{border-color:#dc3545}
.hd-filter-bar input{flex:1;min-width:0}
.hd-filter-bar select{min-width:150px}
.hd-filter-btn{padding:10px 20px;background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s}
.hd-filter-btn:hover{box-shadow:0 4px 12px rgba(220,53,69,0.3)}
.hd-filter-reset{padding:10px 16px;background:#f5f5f5;color:#666;border:none;border-radius:10px;text-decoration:none;font-weight:500;white-space:nowrap;transition:all .2s}
.hd-filter-reset:hover{background:#eee;color:#333}

.hd-empty{text-align:center;padding:80px 20px;background:#fff;border-radius:20px;border:2px dashed #e8eaed}
.hd-empty-icon{width:80px;height:80px;background:linear-gradient(135deg,#fff0f0,#ffe0e0);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
.hd-empty-icon i{font-size:2rem;color:#dc3545}
.hd-empty h4{color:#333;margin-bottom:8px}
.hd-empty p{color:#999;margin-bottom:20px}

.hd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
.hd-card{background:#fff;border-radius:16px;overflow:hidden;border:1px solid #f0f0f0;transition:all .3s}
.hd-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,0.08)}
.hd-card-banner{position:relative;height:180px;overflow:hidden}
.hd-card-banner img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.hd-card:hover .hd-card-banner img{transform:scale(1.05)}
.hd-card-banner-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#dc3545 0%,#b71c1c 100%);display:flex;align-items:center;justify-content:center}
.hd-card-banner-placeholder i{font-size:2.5rem;color:rgba(255,255,255,0.3)}
.hd-card-discount{position:absolute;top:12px;left:12px;background:linear-gradient(135deg,#dc3545,#b71c1c);color:#fff;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;box-shadow:0 4px 12px rgba(220,53,69,0.4)}
.hd-card-status{position:absolute;top:12px;right:12px;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:600;backdrop-filter:blur(8px)}
.hd-card-status.active{background:rgba(46,125,50,0.15);color:#2e7d32;border:1px solid rgba(46,125,50,0.3)}
.hd-card-status.inactive{background:rgba(230,81,0,0.15);color:#e65100;border:1px solid rgba(230,81,0,0.3)}
.hd-card-coupon{position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,0.95);backdrop-filter:blur(8px);padding:4px 12px;border-radius:8px;font-size:0.72rem;font-weight:700;color:#dc3545;letter-spacing:0.5px;border:1px dashed #dc3545}
.hd-card-body{padding:18px 20px}
.hd-card-title{font-size:1.05rem;font-weight:700;color:#1a1a2e;margin-bottom:6px;line-height:1.3}
.hd-card-desc{font-size:0.82rem;color:#888;margin-bottom:12px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.hd-card-meta{display:flex;align-items:center;gap:12px;font-size:0.78rem;color:#999;margin-bottom:14px}
.hd-card-meta i{color:#dc3545;font-size:0.72rem}
.hd-card-meta span{display:inline-flex;align-items:center;gap:4px}
.hd-card-actions{display:flex;gap:8px}
.hd-card-actions button,.hd-card-actions a{flex:1;padding:9px;border:none;border-radius:10px;font-size:0.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;text-decoration:none}
.hd-btn-edit{background:linear-gradient(135deg,#dc3545,#c82333);color:#fff}
.hd-btn-edit:hover{box-shadow:0 4px 12px rgba(220,53,69,0.3);transform:translateY(-1px)}
.hd-btn-delete{background:#fff0f0;color:#dc3545;border:1px solid #fce4ec !important}
.hd-btn-delete:hover{background:#fce4ec}

.hd-modal-content{border:none;border-radius:20px;overflow:hidden}
.hd-modal-header{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;padding:20px 24px;border:none}
.hd-modal-header h5{font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px}
.hd-modal-header .btn-close{filter:brightness(0) invert(1);opacity:0.7}
.hd-modal-body{padding:24px}
.hd-modal-body label{font-weight:600;font-size:0.85rem;color:#333;margin-bottom:4px}
.hd-modal-body .form-control,.hd-modal-body .form-select{border:1px solid #e8eaed;border-radius:10px;padding:10px 14px;font-size:0.88rem}
.hd-modal-body .form-control:focus,.hd-modal-body .form-select:focus{border-color:#dc3545;box-shadow:0 0 0 3px rgba(220,53,69,0.1)}
.hd-modal-footer{padding:16px 24px;border-top:1px solid #f0f0f0}
.hd-modal-footer .btn-cancel{background:#f5f5f5;color:#666;border:none;border-radius:10px;padding:10px 20px;font-weight:600}
.hd-modal-footer .btn-save{background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;box-shadow:0 4px 12px rgba(220,53,69,0.25)}
.hd-modal-footer .btn-delete{background:#dc3545;color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600}

@media(max-width:992px){.hd-stats{grid-template-columns:repeat(2,1fr)}.hd-grid{grid-template-columns:1fr}}
@media(max-width:768px){.hd-sidebar{width:100%;min-height:auto;position:relative}.hd-main{padding:16px;margin-left:0}.hd-filter-bar{flex-direction:column}.hd-stats{grid-template-columns:1fr 1fr}}
</style>

<button class="admin-sidebar-toggle" id="skSidebarToggle" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.toggle('show');document.getElementById('skOverlay').classList.toggle('active')">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="skOverlay" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.remove('show');this.classList.remove('active')"></div>
<div class="dash-layout">
    <div class="dash-sidebar hd-sidebar">
        <div class="dash-sidebar-brand">
            <div class="dash-brand-icon">SK</div>
            <div>
                <div class="dash-brand-text">Shopkeeper</div>
                <small style="color:#888;font-size:0.75rem;"><?php echo htmlspecialchars($shop['shop_name']); ?></small>
            </div>
        </div>
        <div class="dash-sidebar-label">Menu</div>
        <nav class="dash-nav">
            <a class="dash-nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a class="dash-nav-link" href="products.php"><i class="fas fa-boxes-stacked"></i>Products</a>
            <a class="dash-nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a>
            <a class="dash-nav-link" href="inventory.php"><i class="fas fa-warehouse"></i>Inventory</a>
            <a class="dash-nav-link active" href="hot-deals.php"><i class="fas fa-fire"></i>Hot Deals</a>
            <a class="dash-nav-link" href="returns.php"><i class="fas fa-undo-alt"></i>Returns</a>
            <a class="dash-nav-link" href="chat.php"><i class="fas fa-comments"></i>Chat</a>
            <a class="dash-nav-link" href="profile.php"><i class="fas fa-user-cog"></i>Profile</a>
        </nav>
        <div class="dash-sidebar-footer">
            <a class="dash-nav-link logout" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>
    <div class="dash-main hd-main">
        <a href="dashboard.php" class="hd-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="hd-page-head">
            <div class="hd-page-title"><i class="fas fa-fire"></i> My Hot Deals</div>
            <button class="hd-add-btn" data-bs-toggle="modal" data-bs-target="#addDealModal"><i class="fas fa-plus"></i> Create Deal</button>
        </div>

        <div class="hd-stats">
            <div class="hd-stat">
                <div class="hd-stat-icon total"><i class="fas fa-layer-group"></i></div>
                <div><div class="hd-stat-num"><?php echo $totalDeals; ?></div><div class="hd-stat-label">Total Deals</div></div>
            </div>
            <div class="hd-stat">
                <div class="hd-stat-icon active"><i class="fas fa-check-circle"></i></div>
                <div><div class="hd-stat-num"><?php echo $activeDeals; ?></div><div class="hd-stat-label">Active</div></div>
            </div>
            <div class="hd-stat">
                <div class="hd-stat-icon inactive"><i class="fas fa-pause-circle"></i></div>
                <div><div class="hd-stat-num"><?php echo $inactiveDeals; ?></div><div class="hd-stat-label">Inactive</div></div>
            </div>
            <div class="hd-stat">
                <div class="hd-stat-icon expiring"><i class="fas fa-hourglass-half"></i></div>
                <div><div class="hd-stat-num"><?php echo $expiringSoon; ?></div><div class="hd-stat-label">Expiring Soon</div></div>
            </div>
        </div>

        <div class="hd-filter-bar">
            <form method="GET" style="display:flex;gap:12px;align-items:center;width:100%;">
                <input type="text" name="search" placeholder="Search deals by title, description, category..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="hd-filter-btn"><i class="fas fa-search"></i> Filter</button>
                <a href="hot-deals.php" class="hd-filter-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <?php if (empty($deals)): ?>
            <div class="hd-empty">
                <div class="hd-empty-icon"><i class="fas fa-fire"></i></div>
                <h4>No deals yet</h4>
                <p>Create your first hot deal to attract customers and boost sales</p>
                <button class="hd-add-btn" data-bs-toggle="modal" data-bs-target="#addDealModal"><i class="fas fa-plus"></i> Create Your First Deal</button>
            </div>
        <?php else: ?>
            <div class="hd-grid">
                <?php foreach ($deals as $deal): ?>
                    <div class="hd-card">
                        <div class="hd-card-banner">
                            <?php if ($deal['banner_image']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($deal['banner_image']); ?>" alt="<?php echo htmlspecialchars($deal['title']); ?>">
                            <?php else: ?>
                                <div class="hd-card-banner-placeholder"><i class="fas fa-fire"></i></div>
                            <?php endif; ?>
                            <?php if ($deal['discount_text']): ?>
                                <div class="hd-card-discount"><?php echo htmlspecialchars($deal['discount_text']); ?></div>
                            <?php endif; ?>
                            <div class="hd-card-status <?php echo $deal['status']; ?>"><?php echo ucfirst($deal['status']); ?></div>
                            <?php if ($deal['coupon_code']): ?>
                                <div class="hd-card-coupon"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($deal['coupon_code']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="hd-card-body">
                            <div class="hd-card-title"><?php echo htmlspecialchars($deal['title']); ?></div>
                            <?php if ($deal['description']): ?>
                                <div class="hd-card-desc"><?php echo htmlspecialchars($deal['description']); ?></div>
                            <?php endif; ?>
                            <div class="hd-card-meta">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date('M d', strtotime($deal['start_date'])); ?> - <?php echo date('M d, Y', strtotime($deal['end_date'])); ?></span>
                                <?php if ($deal['category']): ?>
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($deal['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="hd-card-actions">
                                <button class="hd-btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $deal['id']; ?>"><i class="fas fa-pen"></i> Edit</button>
                                <button class="hd-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $deal['id']; ?>"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="editModal<?php echo $deal['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content hd-modal-content">
                                <form method="POST" enctype="multipart/form-data">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                    <div class="modal-header hd-modal-header">
                                        <h5><i class="fas fa-pen"></i> Edit Deal</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body hd-modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label class="form-label">Deal Title *</label>
                                                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($deal['title']); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Discount Badge</label>
                                                <input type="text" name="discount_text" class="form-control" value="<?php echo htmlspecialchars($deal['discount_text']); ?>" placeholder="20% OFF">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($deal['description']); ?></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Banner Image</label>
                                                <input type="file" name="banner_image" class="form-control" accept="image/*">
                                                <small class="text-muted">Leave empty to keep current image</small>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Coupon Code</label>
                                                <input type="text" name="coupon_code" class="form-control" value="<?php echo htmlspecialchars($deal['coupon_code']); ?>" placeholder="SAVE20">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" <?php echo $deal['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $deal['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Button Text</label>
                                                <input type="text" name="button_text" class="form-control" value="<?php echo htmlspecialchars($deal['button_text']); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Button Link</label>
                                                <input type="text" name="button_link" class="form-control" value="<?php echo htmlspecialchars($deal['button_link']); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Category</label>
                                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($deal['category']); ?>" placeholder="Brake Parts">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Start Date *</label>
                                                <input type="date" name="start_date" class="form-control" required value="<?php echo $deal['start_date']; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">End Date *</label>
                                                <input type="date" name="end_date" class="form-control" required value="<?php echo $deal['end_date']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer hd-modal-footer">
                                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_deal" class="btn btn-save"><i class="fas fa-save"></i> Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteModal<?php echo $deal['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content hd-modal-content">
                                <form method="POST">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                    <div class="modal-header hd-modal-header" style="justify-content:center;">
                                        <h5><i class="fas fa-exclamation-triangle"></i> Delete Deal</h5>
                                    </div>
                                    <div class="modal-body hd-modal-body" style="text-align:center;">
                                        <p style="margin:0;color:#555;">Are you sure you want to delete<br><strong><?php echo htmlspecialchars($deal['title']); ?></strong>?</p>
                                        <small style="color:#999;">This action cannot be undone.</small>
                                    </div>
                                    <div class="modal-footer hd-modal-footer" style="justify-content:center;gap:8px;">
                                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="delete_deal" class="btn btn-delete"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addDealModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content hd-modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-header hd-modal-header">
                    <h5><i class="fas fa-fire"></i> Create Hot Deal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body hd-modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Deal Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Brake Pad Sale">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount Badge</label>
                            <input type="text" name="discount_text" class="form-control" placeholder="e.g. 20% OFF">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Describe this deal briefly"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banner Image</label>
                            <input type="file" name="banner_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Coupon Code</label>
                            <input type="text" name="coupon_code" class="form-control" placeholder="SAVE20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="Brake Parts">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button Text</label>
                            <input type="text" name="button_text" class="form-control" value="Shop Now">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button Link</label>
                            <input type="text" name="button_link" class="form-control" value="#" placeholder="URL or #">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date *</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer hd-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_deal" class="btn btn-save"><i class="fas fa-plus"></i> Create Deal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
