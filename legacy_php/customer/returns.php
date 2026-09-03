<?php
$page_title = 'Product Returns';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit_return'])) {
    verifyCsrf();
    $order_id = intval($_POST['order_id']);
    $product_id = intval($_POST['product_id']);
    $shop_id = intval($_POST['shop_id']);
    $reason = sanitize($_POST['reason']);
    $condition = sanitize($_POST['condition']);

    $check = $conn->prepare("SELECT o.order_id, p.shop_id FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN products p ON oi.product_id = p.product_id WHERE o.order_id = ? AND o.customer_id = ? AND o.order_status IN ('delivered','completed') AND oi.product_id = ?");
    $check->bind_param("iii", $order_id, $user_id, $product_id);
    $check->execute();
    $check_row = $check->get_result()->fetch_assoc();
    $check->close();

    if ($check_row && (int)$check_row['shop_id'] === (int)$shop_id) {
        $dup = $conn->prepare("SELECT return_id FROM product_returns WHERE order_id = ? AND product_id = ? AND customer_id = ?");
        $dup->bind_param("iii", $order_id, $product_id, $user_id);
        $dup->execute();
        $duplicate = $dup->get_result()->num_rows > 0;
        $dup->close();

        if ($duplicate) {
            setFlash('danger', 'You have already submitted a return request for this product.');
        } else {
            $stmt = $conn->prepare("INSERT INTO product_returns (order_id, product_id, customer_id, shop_id, reason, condition_desc) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiss", $order_id, $product_id, $user_id, $shop_id, $reason, $condition);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Return request submitted successfully. We will review it shortly.');
        }
    } else {
        setFlash('danger', 'Invalid order or product not eligible for return.');
    }
    redirect(SITE_URL . '/customer/returns.php');
}

$returns = $conn->prepare("SELECT r.*, p.product_name, p.product_image, o.order_id, s.shop_name 
    FROM product_returns r 
    LEFT JOIN products p ON r.product_id = p.product_id 
    LEFT JOIN orders o ON r.order_id = o.order_id 
    LEFT JOIN shops s ON r.shop_id = s.shop_id 
    WHERE r.customer_id = ? 
    ORDER BY r.created_at DESC");
$returns->bind_param("i", $user_id);
$returns->execute();
$returns_result = $returns->get_result();

$delivered_orders = $conn->prepare("SELECT DISTINCT o.*, s.shop_name, s.shop_id FROM orders o LEFT JOIN order_items oi ON o.order_id = oi.order_id LEFT JOIN products p ON oi.product_id = p.product_id LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE o.customer_id = ? AND o.order_status IN ('delivered','completed') ORDER BY o.created_at DESC");
$delivered_orders->bind_param("i", $user_id);
$delivered_orders->execute();
$delivered_result = $delivered_orders->get_result();

require_once __DIR__ . '/header.php';
?>

<style>
.return-hero{background:linear-gradient(135deg,#dc3545 0%,#b71c1c 100%);border-radius:20px;padding:40px;color:#fff;margin-bottom:30px;position:relative;overflow:hidden}
.return-hero::before{content:'';position:absolute;top:-50%;right:-20%;width:400px;height:400px;background:radial-gradient(circle,rgba(255,255,255,.1),transparent);border-radius:50%}
.return-hero h1{font-size:1.8rem;font-weight:800;margin-bottom:8px}
.return-hero p{font-size:.95rem;opacity:.9;margin:0}
.return-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:30px}
.return-stat{background:#fff;border-radius:14px;padding:20px;text-align:center;border:1px solid #f0f0f0;box-shadow:0 2px 12px rgba(0,0,0,.04);transition:all .3s}
.return-stat:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
[data-theme="dark"] .return-stat{background:#1a1a2e;border-color:#2a2a3e}
.return-stat i{font-size:1.4rem;margin-bottom:8px}
.return-stat h3{font-size:1.6rem;font-weight:800;margin:0;color:#1a1a2e}
[data-theme="dark"] .return-stat h3{color:#e8e8f0}
.return-stat p{font-size:.78rem;color:#888;margin:4px 0 0}
[data-theme="dark"] .return-stat p{color:#999}
.return-form-section{background:#fff;border-radius:16px;padding:28px;border:1px solid #f0f0f0;box-shadow:0 2px 16px rgba(0,0,0,.04);margin-bottom:24px;transition:all .3s}
[data-theme="dark"] .return-form-section{background:#1a1a2e;border-color:#2a2a3e}
.return-form-section h2{font-size:1.15rem;font-weight:700;color:#1a1a2e;margin-bottom:20px;display:flex;align-items:center;gap:10px}
[data-theme="dark"] .return-form-section h2{color:#e8e8f0}
.return-form-section h2 i{width:36px;height:36px;background:linear-gradient(135deg,#dc3545,#b71c1c);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem}
.return-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.return-form-group{display:flex;flex-direction:column;gap:6px}
.return-form-group.full{grid-column:1/-1}
.return-form-group label{font-size:.82rem;font-weight:600;color:#444}
[data-theme="dark"] .return-form-group label{color:#ccc}
.return-form-group select,.return-form-group textarea{padding:10px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:.88rem;color:#333;background:#fafafa;transition:border-color .3s}
[data-theme="dark"] .return-form-group select,[data-theme="dark"] .return-form-group textarea{background:#0f172a;border-color:#475569;color:#e2e8f0}
.return-form-group select:focus,.return-form-group textarea:focus{outline:none;border-color:#dc3545;background:#fff}
[data-theme="dark"] .return-form-group select:focus,[data-theme="dark"] .return-form-group textarea:focus{background:#0f172a}
.return-form-group textarea{resize:vertical;min-height:80px}
.return-submit-btn{padding:12px 32px;background:linear-gradient(135deg,#dc3545,#b71c1c);color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:all .3s}
.return-submit-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(220,53,69,.4)}
.return-list{display:flex;flex-direction:column;gap:14px}
.return-card{background:#fff;border-radius:14px;padding:20px;border:1px solid #f0f0f0;box-shadow:0 2px 12px rgba(0,0,0,.04);display:flex;gap:16px;align-items:start;transition:all .3s}
[data-theme="dark"] .return-card{background:#1a1a2e;border-color:#2a2a3e}
.return-card-img{width:70px;height:70px;border-radius:12px;object-fit:cover;background:#f8f8f8;flex-shrink:0}
[data-theme="dark"] .return-card-img{background:#0f172a}
.return-card-info{flex:1;min-width:0}
.return-card-info h4{font-size:.95rem;font-weight:700;color:#1a1a2e;margin:0 0 4px}
[data-theme="dark"] .return-card-info h4{color:#e8e8f0}
.return-card-info p{font-size:.78rem;color:#888;margin:0 0 6px}
[data-theme="dark"] .return-card-info p{color:#999}
.return-card-meta{display:flex;gap:12px;flex-wrap:wrap;font-size:.75rem;color:#999}
.return-card-meta span{display:flex;align-items:center;gap:4px}
.return-status{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:600}
.return-status.pending{background:#fef3c7;color:#d97706}
.return-status.approved{background:#d1fae5;color:#059669}
.return-status.rejected{background:#fee2e2;color:#dc2626}
.return-status.completed{background:#dbeafe;color:#2563eb}
.return-empty{text-align:center;padding:60px 20px;color:#aaa}
.return-empty i{font-size:3rem;margin-bottom:16px;display:block}
@media(max-width:768px){
    .return-stats{grid-template-columns:repeat(2,1fr)}
    .return-form-grid{grid-template-columns:1fr}
    .return-card{flex-direction:column}
    .return-card-img{width:100%;height:140px}
}
</style>

<div class="container-fluid px-4 py-4">
    <div class="return-hero">
        <h1><i class="fas fa-undo-alt" style="margin-right:10px;"></i>Product Returns</h1>
        <p>Request a return for delivered orders. We'll review your request within 24-48 hours.</p>
    </div>

    <?php
    $total_returns = $returns_result->num_rows;
    $pending_returns = 0; $approved_returns = 0; $completed_returns = 0;
    $returns_result->data_seek(0);
    while ($r = $returns_result->fetch_assoc()) {
        if ($r['return_status'] === 'pending') $pending_returns++;
        elseif ($r['return_status'] === 'approved') $approved_returns++;
        elseif ($r['return_status'] === 'completed') $completed_returns++;
    }
    ?>
    <div class="return-stats">
        <div class="return-stat"><i class="fas fa-undo" style="color:#dc3545;"></i><h3><?php echo $total_returns; ?></h3><p>Total Returns</p></div>
        <div class="return-stat"><i class="fas fa-clock" style="color:#d97706;"></i><h3><?php echo $pending_returns; ?></h3><p>Pending</p></div>
        <div class="return-stat"><i class="fas fa-check-circle" style="color:#059669;"></i><h3><?php echo $approved_returns; ?></h3><p>Approved</p></div>
        <div class="return-stat"><i class="fas fa-flag-checkered" style="color:#2563eb;"></i><h3><?php echo $completed_returns; ?></h3><p>Completed</p></div>
    </div>

    <div class="return-form-section">
        <h2><i class="fas fa-plus-circle"></i> Submit Return Request</h2>
        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="return-form-grid">
                <div class="return-form-group">
                    <label>Select Order</label>
                    <select name="order_id" id="returnOrderSelect" required onchange="loadOrderProducts(this.value)">
                        <option value="">-- Choose delivered order --</option>
                        <?php while ($od = $delivered_result->fetch_assoc()): ?>
                            <option value="<?php echo $od['order_id']; ?>" data-shop="<?php echo $od['shop_id']; ?>"><?php echo '#' . $od['order_id'] . ' - ' . htmlspecialchars($od['shop_name'] ?? 'Shop') . ' (' . date('M d, Y', strtotime($od['created_at'])) . ')'; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="return-form-group">
                    <label>Select Product</label>
                    <select name="product_id" id="returnProductSelect" required>
                        <option value="">-- Choose order first --</option>
                    </select>
                </div>
                <input type="hidden" name="shop_id" id="returnShopId" value="">
                <div class="return-form-group">
                    <label>Return Reason</label>
                    <select name="condition" required>
                        <option value="">-- Select reason --</option>
                        <option value="unused">Unused - Changed my mind</option>
                        <option value="damaged">Damaged during delivery</option>
                        <option value="wrong_item">Wrong item received</option>
                        <option value="defective">Defective / Not working</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="return-form-group full">
                    <label>Description</label>
                    <textarea name="reason" placeholder="Describe the issue in detail..." required></textarea>
                </div>
                <div class="return-form-group full">
                    <button type="submit" name="submit_return" class="return-submit-btn"><i class="fas fa-paper-plane" style="margin-right:6px;"></i>Submit Return Request</button>
                </div>
            </div>
        </form>
    </div>

    <div class="return-form-section">
        <h2><i class="fas fa-history"></i> Return History</h2>
        <?php if ($returns_result->num_rows > 0): ?>
            <div class="return-list">
                <?php $returns_result->data_seek(0); while ($ret = $returns_result->fetch_assoc()): ?>
                    <div class="return-card">
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($ret['product_image']) ? $ret['product_image'] : 'no-image.png'; ?>" class="return-card-img" alt="">
                        <div class="return-card-info">
                            <h4><?php echo htmlspecialchars($ret['product_name'] ?? 'Product'); ?></h4>
                            <p><?php echo htmlspecialchars($ret['reason']); ?></p>
                            <div class="return-card-meta">
                                <span><i class="fas fa-shopping-bag"></i> Order #<?php echo $ret['order_id']; ?></span>
                                <span><i class="fas fa-store"></i> <?php echo htmlspecialchars($ret['shop_name'] ?? 'Shop'); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($ret['created_at'])); ?></span>
                            </div>
                        </div>
                        <span class="return-status <?php echo $ret['return_status']; ?>"><i class="fas fa-<?php echo $ret['return_status']==='pending'?'clock':($ret['return_status']==='approved'?'check':($ret['return_status']==='rejected'?'times':'flag-checkered')); ?>"></i> <?php echo ucfirst($ret['return_status']); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="return-empty"><i class="fas fa-inbox"></i><p>No return requests yet</p></div>
        <?php endif; ?>
    </div>
</div>

<script>
function loadOrderProducts(orderId) {
    var sel = document.getElementById('returnProductSelect');
    var shopInput = document.getElementById('returnShopId');
    sel.innerHTML = '<option value="">Loading...</option>';
    if (!orderId) { sel.innerHTML = '<option value="">-- Choose order first --</option>'; shopInput.value = ''; return; }
    var opt = document.querySelector('#returnOrderSelect option[value="'+orderId+'"]');
    if (opt) shopInput.value = opt.getAttribute('data-shop') || '';
    fetch('<?php echo SITE_URL; ?>/api/order-items.php?order_id=' + orderId)
        .then(function(r){ return r.json(); })
        .then(function(data){
            sel.innerHTML = '<option value="">-- Select product --</option>';
            if (data.items) {
                data.items.forEach(function(item){
                    sel.innerHTML += '<option value="'+item.product_id+'">'+item.product_name+' (x'+item.quantity+')</option>';
                });
            }
        })
        .catch(function(){ sel.innerHTML = '<option value="">Error loading products</option>'; });
}
</script>

<?php
$returns->close();
$delivered_orders->close();
require_once __DIR__ . '/footer.php';
?>
