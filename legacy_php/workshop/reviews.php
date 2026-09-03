<?php
$page_title = 'Reviews';
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

$stmt = $conn->prepare("SELECT r.*, u.name as customer_name, u.profile_image as customer_image FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.workshop_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $workshop_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $rating_sum = 0;
    foreach ($reviews as $rev) {
        $rating_sum += $rev['rating'];
    }
    $avg_rating = $rating_sum / $total_reviews;
}

$rating_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($reviews as $rev) {
    $rating_distribution[$rev['rating']]++;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* Rating Summary Card */
    .rv-summary-card {
        background: linear-gradient(135deg, var(--primary, #1a1a2e) 0%, var(--primary-light, #2a2a4e) 100%);
        border-radius: 16px;
        padding: 2px;
        box-shadow: 0 8px 30px rgba(26,26,46,0.25);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .rv-summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(26,26,46,0.35);
    }
    .rv-summary-inner {
        background: rgba(255,255,255,0.05);
        border-radius: 14px;
        padding: 40px 24px 32px;
        backdrop-filter: blur(10px);
    }
    .rv-avg-number {
        font-size: 3.5rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
        margin-bottom: 12px;
        text-shadow: 0 2px 10px rgba(220,53,69,0.3);
    }
    .rv-stars-row {
        display: flex;
        justify-content: center;
        gap: 6px;
        margin-bottom: 12px;
    }
    .rv-star {
        font-size: 1.3rem;
        transition: transform 0.2s ease;
    }
    .rv-star.filled {
        color: #f59e0b;
        filter: drop-shadow(0 1px 3px rgba(245,158,11,0.4));
    }
    .rv-star.empty {
        color: rgba(255,255,255,0.25);
    }
    .rv-summary-card:hover .rv-star.filled {
        transform: scale(1.15);
    }
    .rv-total-text {
        color: rgba(255,255,255,0.6);
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Rating Distribution Card */
    .rv-dist-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f0f0f0;
        overflow: hidden;
    }
    .rv-dist-header {
        padding: 18px 24px;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--primary, #1a1a2e);
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .rv-dist-header i {
        color: var(--accent, #dc3545);
        font-size: 0.9rem;
    }
    .rv-dist-body {
        padding: 20px 24px;
    }
    .rv-dist-row {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }
    .rv-dist-row:last-child {
        margin-bottom: 0;
    }
    .rv-dist-label {
        min-width: 42px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #555;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .rv-dist-label i {
        color: #f59e0b;
        font-size: 0.7rem;
    }
    .rv-dist-bar-track {
        flex: 1;
        height: 10px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .rv-dist-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent, #dc3545), #f59e0b);
        border-radius: 10px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .rv-dist-bar-fill::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: rv-shimmer 2s infinite;
    }
    @keyframes rv-shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .rv-dist-count {
        min-width: 28px;
        text-align: right;
        font-size: 0.85rem;
        font-weight: 600;
        color: #888;
    }
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
            <h2><i class="fas fa-star me-2 text-danger"></i>Customer Reviews</h2>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="rv-summary-card">
                    <div class="rv-summary-inner text-center">
                        <div class="rv-avg-number"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="rv-stars-row">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($avg_rating)): ?>
                                    <i class="fas fa-star rv-star filled"></i>
                                <?php elseif ($i - $avg_rating < 1 && $i - $avg_rating > 0): ?>
                                    <i class="fas fa-star-half-alt rv-star filled"></i>
                                <?php else: ?>
                                    <i class="far fa-star rv-star empty"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="rv-total-text"><?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="rv-dist-card">
                    <div class="rv-dist-header">
                        <i class="fas fa-chart-bar"></i> Rating Distribution
                    </div>
                    <div class="rv-dist-body">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php $pct = $total_reviews > 0 ? ($rating_distribution[$i] / $total_reviews * 100) : 0; ?>
                            <div class="rv-dist-row">
                                <span class="rv-dist-label"><?php echo $i; ?> <i class="fas fa-star"></i></span>
                                <div class="rv-dist-bar-track">
                                    <div class="rv-dist-bar-fill" style="width: <?php echo $pct; ?>%"></div>
                                </div>
                                <span class="rv-dist-count"><?php echo $rating_distribution[$i]; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-card-body">
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No reviews yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="d-flex mb-4 pb-4 border-bottom">
                            <div class="me-3">
                                <?php if (!empty($review['customer_image'])): ?>
                                    <img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($review['customer_image']); ?>" alt="Avatar" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($review['customer_name']); ?></h6>
                                    <small class="text-muted"><?php echo timeAgo($review['created_at']); ?></small>
                                </div>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                    <span class="ms-2 text-muted">(<?php echo $review['rating']; ?>/5)</span>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'] ?? 'No comment provided.')); ?></p>
                                <small class="text-muted"><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
