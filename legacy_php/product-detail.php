<?php
$page_title = 'Product Detail';
require_once __DIR__ . '/includes/config.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$product_id) {
    redirect(SITE_URL . '/products.php');
}

$product = $conn->prepare("SELECT p.*, s.shop_name, s.shop_id as seller_shop_id, c.category_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ? AND p.status = 'available'");
$product->bind_param("i", $product_id);
$product->execute();
$product_data = $product->get_result()->fetch_assoc();
$product->close();

if (!$product_data) {
    setFlash('danger', 'Product not found.');
    redirect(SITE_URL . '/products.php');
}

if (isset($_GET['add_wishlist']) && isLoggedIn() && $current_user['role'] === 'customer') {
    $uid = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $uid, $product_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $ins = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $ins->bind_param("ii", $uid, $product_id);
        $ins->execute();
        $ins->close();
        setFlash('success', 'Added to wishlist!');
    } else {
        setFlash('info', 'Already in wishlist.');
    }
    $check->close();
    redirect(SITE_URL . "/product-detail.php?id=$product_id");
}

if (isset($_GET['remove_wishlist']) && isLoggedIn() && $current_user['role'] === 'customer') {
    $uid = $_SESSION['user_id'];
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->bind_param("ii", $uid, $product_id);
    $del->execute();
    $del->close();
    setFlash('success', 'Removed from wishlist.');
    redirect(SITE_URL . "/product-detail.php?id=$product_id");
}

if (isset($_GET['add_cart']) && isLoggedIn() && $current_user['role'] === 'customer') {
    $uid = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $uid, $product_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $uid AND product_id = $product_id");
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
        $ins->bind_param("ii", $uid, $product_id);
        $ins->execute();
        $ins->close();
    }
    $check->close();
    setFlash('success', 'Added to cart!');
    redirect(SITE_URL . "/product-detail.php?id=$product_id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review']) && isLoggedIn() && $current_user['role'] === 'customer') {
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    $uid = $_SESSION['user_id'];
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $existing = $conn->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ?");
        $existing->bind_param("ii", $uid, $product_id);
        $existing->execute();
        if ($existing->get_result()->num_rows === 0) {
            $review_image = null;
            if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['review_image']['tmp_name']);
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($ext, $allowed) && in_array($mime, $allowed_mimes)) {
                    $filename = 'review_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $upload_path = UPLOAD_DIR . $filename;
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    if (move_uploaded_file($_FILES['review_image']['tmp_name'], $upload_path)) {
                        $review_image = $filename;
                    }
                }
            }
            if ($review_image) {
                $ins = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, review_image) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("iiiss", $uid, $product_id, $rating, $comment, $review_image);
            } else {
                $ins = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
                $ins->bind_param("iiis", $uid, $product_id, $rating, $comment);
            }
            $ins->execute();
            $ins->close();
            setFlash('success', 'Review submitted!');
        } else {
            setFlash('info', 'You have already reviewed this product.');
        }
        $existing->close();
    } else {
        setFlash('danger', 'Please provide a rating and comment.');
    }
    redirect(SITE_URL . "/product-detail.php?id=$product_id");
}

$reviews = $conn->prepare("SELECT r.*, u.name FROM reviews r LEFT JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviews->bind_param("i", $product_id);
$reviews->execute();
$reviews_result = $reviews->get_result();

$avg_rating = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg_r, COUNT(*) as total_r FROM reviews WHERE product_id = ?");
$avg_rating->bind_param("i", $product_id);
$avg_rating->execute();
$rating_data = $avg_rating->get_result()->fetch_assoc();
$avg_rating->close();

$related = $conn->prepare("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE p.category_id = ? AND p.product_id != ? AND p.status = 'available' ORDER BY RAND() LIMIT 4");
$related->bind_param("ii", $product_data['category_id'], $product_id);
$related->execute();
$related_result = $related->get_result();
$related->close();

$in_wishlist = false;
if (isLoggedIn() && $current_user['role'] === 'customer') {
    $uid = $_SESSION['user_id'];
    $wcheck = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wcheck->bind_param("ii", $uid, $product_id);
    $wcheck->execute();
    $in_wishlist = $wcheck->get_result()->num_rows > 0;
    $wcheck->close();
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .pd-wrapper { max-width: 1200px; margin: 30px auto; padding: 0 20px 60px; }
    .pd-breadcrumb {
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        gap: 0;
        background: linear-gradient(135deg, var(--primary, #1a1a2e) 0%, var(--primary-light, #2a2a4e) 100%);
        padding: 14px 22px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        font-family: var(--font, 'Inter', sans-serif);
        overflow: hidden;
        position: relative;
    }
    .pd-breadcrumb::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 4px; height: 100%;
        background: var(--accent, #dc3545);
        border-radius: 4px 0 0 4px;
    }
    .pd-breadcrumb-item {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: rgba(255,255,255,0.65);
        font-size: 0.88rem;
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .pd-breadcrumb-item:hover {
        color: #fff;
        background: rgba(255,255,255,0.1);
    }
    .pd-breadcrumb-item.active {
        color: #fff;
        font-weight: 600;
        background: rgba(220,53,69,0.2);
    }
    .pd-breadcrumb-item i { font-size: 0.82rem; opacity: 0.7; }
    .pd-breadcrumb-sep {
        color: rgba(255,255,255,0.25);
        font-size: 0.75rem;
        margin: 0 4px;
        user-select: none;
    }
    .pd-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 50px; }
    .pd-image-wrap { background: #f8f9fa; border-radius: 16px; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 30px; border: 1px solid #e9ecef; }
    .pd-image-wrap img { max-width: 100%; max-height: 450px; object-fit: contain; }
    .pd-name { font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #1a1a2e; }
    .pd-brand { font-size: 0.95rem; color: #666; margin-bottom: 12px; }
    .pd-brand strong { color: #333; }
    .pd-price-row { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
    .pd-price { font-size: 1.8rem; font-weight: 800; color: #1a73e8; }
    .pd-old-price { font-size: 1.2rem; color: #999; text-decoration: line-through; }
    .pd-discount-badge { background: #28a745; color: #fff; font-size: 0.8rem; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
    .pd-stock { font-size: 0.95rem; margin-bottom: 16px; }
    .pd-stock.in-stock { color: #28a745; }
    .pd-stock.out-of-stock { color: #dc3545; }
    .pd-desc { color: #555; line-height: 1.7; margin-bottom: 20px; }
    .pd-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 24px; }
    .pd-meta-item { background: #f8f9fa; padding: 10px 14px; border-radius: 10px; font-size: 0.9rem; }
    .pd-meta-item strong { display: block; font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .pd-actions { display: flex; gap: 12px; flex-wrap: wrap; }
    .pd-btn { padding: 12px 28px; border-radius: 12px; font-weight: 600; font-size: 0.95rem; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .3s; }
    .pd-btn-primary { background: #1a73e8; color: #fff; }
    .pd-btn-primary:hover { background: #1557b0; color: #fff; }
    .pd-btn-outline { background: transparent; color: #1a73e8; border: 2px solid #1a73e8; }
    .pd-btn-outline:hover { background: #1a73e8; color: #fff; }
    .pd-btn-danger { background: #dc3545; color: #fff; }
    .pd-btn-danger:hover { background: #b02a37; color: #fff; }
    .pd-btn-success { background: #28a745; color: #fff; }
    .pd-btn-success:hover { background: #1e7e34; color: #fff; }

    /* Reviews */
    .pd-section-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 20px; color: #1a1a2e; }
    .pd-review-card { background: #f8f9fa; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; }
    .pd-review-user { font-weight: 600; font-size: 0.95rem; }
    .pd-review-date { font-size: 0.8rem; color: #999; margin-left: 10px; }
    .pd-review-comment { color: #555; margin-top: 6px; font-size: 0.92rem; line-height: 1.5; }
    .pd-review-img { max-width: 200px; max-height: 180px; border-radius: 10px; margin-top: 10px; object-fit: cover; border: 1px solid #e9ecef; }
    .pd-rating-stars { color: #ffc107; font-size: 1rem; letter-spacing: 2px; }
    .pd-rating-stars .empty { color: #ddd; }
    .pd-review-form { background: #f8f9fa; border-radius: 12px; padding: 24px; margin-top: 20px; }
    .pd-review-form label { font-weight: 600; font-size: 0.9rem; margin-bottom: 6px; }
    .pd-review-form textarea { border-radius: 10px; border: 1.5px solid #e0e0e0; padding: 10px 14px; width: 100%; resize: vertical; min-height: 80px; }
    .pd-star-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; margin-bottom: 12px; }
    .pd-star-input input { display: none; }
    .pd-star-input label { font-size: 1.6rem; color: #ddd; cursor: pointer; transition: color .2s; }
    .pd-star-input input:checked ~ label,
    .pd-star-input label:hover,
    .pd-star-input label:hover ~ label { color: #ffc107; }

    /* Related */
    .pd-related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    .pd-related-card { background: #fff; border: 1px solid #e9ecef; border-radius: 14px; overflow: hidden; text-decoration: none; color: inherit; transition: all .3s; }
    .pd-related-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-3px); }
    .pd-related-card img { width: 100%; height: 160px; object-fit: contain; background: #f8f9fa; padding: 16px; }
    .pd-related-body { padding: 12px 16px 16px; }
    .pd-related-name { font-size: 0.9rem; font-weight: 600; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pd-related-price { font-size: 1rem; font-weight: 700; color: #1a73e8; }
    .pd-related-old { font-size: 0.82rem; color: #999; text-decoration: line-through; margin-left: 6px; }

    @media (max-width: 768px) {
        .pd-layout { grid-template-columns: 1fr; gap: 24px; }
        .pd-meta { grid-template-columns: 1fr; }
        .pd-price { font-size: 1.4rem; }
        .pd-name { font-size: 1.3rem; }
    }
</style>

<div class="pd-wrapper">
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <?php endif; ?>
    <div class="pd-breadcrumb">
        <a class="pd-breadcrumb-item" href="<?php echo SITE_URL; ?>/index.php">
            <i class="fas fa-home"></i> Home
        </a>
        <span class="pd-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
        <a class="pd-breadcrumb-item" href="<?php echo SITE_URL; ?>/products.php">
            <i class="fas fa-box"></i> Products
        </a>
        <span class="pd-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="pd-breadcrumb-item active"><?php echo htmlspecialchars($product_data['product_name']); ?></span>
    </div>

    <div class="pd-layout">
        <div class="pd-image-wrap">
            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($product_data['product_image']) ? $product_data['product_image'] : 'no-image.png'; ?>" alt="<?php echo htmlspecialchars($product_data['product_name']); ?>">
        </div>
        <div>
            <h1 class="pd-name"><?php echo htmlspecialchars($product_data['product_name']); ?></h1>
            <div class="pd-brand">by <a href="<?php echo SITE_URL; ?>/customer/shop-profile.php?id=<?php echo $product_data['seller_shop_id']; ?>" style="color:inherit;text-decoration:none;border-bottom:1px dashed currentColor;"><?php echo htmlspecialchars($product_data['shop_name'] ?? 'Verified Seller'); ?></a>
                <?php if ($product_data['brand']): ?> | Brand: <strong><?php echo htmlspecialchars($product_data['brand']); ?></strong><?php endif; ?>
            </div>

            <div class="pd-price-row">
                <?php if ($product_data['discount_price'] && $product_data['discount_price'] < $product_data['price']): ?>
                    <span class="pd-price"><?php echo formatCurrency($product_data['discount_price']); ?></span>
                    <span class="pd-old-price"><?php echo formatCurrency($product_data['price']); ?></span>
                    <span class="pd-discount-badge">-<?php echo round((1 - $product_data['discount_price'] / $product_data['price']) * 100); ?>%</span>
                <?php else: ?>
                    <span class="pd-price"><?php echo formatCurrency($product_data['price']); ?></span>
                <?php endif; ?>
            </div>

            <div class="pd-stock <?php echo $product_data['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                <i class="fas <?php echo $product_data['stock'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <?php echo $product_data['stock'] > 0 ? 'In Stock (' . $product_data['stock'] . ' units)' : 'Out of Stock'; ?>
            </div>

            <div class="pd-meta">
                <?php if ($product_data['category_name']): ?>
                    <div class="pd-meta-item"><strong>Category</strong><?php echo htmlspecialchars($product_data['category_name']); ?></div>
                <?php endif; ?>
                <?php if ($product_data['brand']): ?>
                    <div class="pd-meta-item"><strong>Brand</strong><?php echo htmlspecialchars($product_data['brand']); ?></div>
                <?php endif; ?>
                <?php if ($product_data['compatible_vehicles']): ?>
                    <div class="pd-meta-item"><strong>Compatible Vehicles</strong><?php echo htmlspecialchars($product_data['compatible_vehicles']); ?></div>
                <?php endif; ?>
                <div class="pd-meta-item">
                    <strong>Rating</strong>
                    <span class="pd-rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= round($rating_data['avg_r']) ? '' : ' empty'; ?>"></i>
                        <?php endfor; ?>
                    </span>
                    (<?php echo $rating_data['total_r']; ?> reviews)
                </div>
            </div>

            <div class="pd-desc"><?php echo nl2br(htmlspecialchars($product_data['description'])); ?></div>

            <div class="pd-actions">
                <?php if (isLoggedIn() && $current_user['role'] === 'customer'): ?>
                    <?php if ($product_data['stock'] > 0): ?>
                        <a href="?id=<?php echo $product_id; ?>&add_cart=1" class="pd-btn pd-btn-primary"><i class="fas fa-shopping-cart"></i> Add to Cart</a>
                    <?php endif; ?>
                    <button type="button" id="wishlistBtn" class="pd-btn <?php echo $in_wishlist ? 'pd-btn-danger' : 'pd-btn-outline'; ?>" onclick="toggleWishlistDetail(<?php echo $product_id; ?>)"><i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i> <span id="wishlistBtnText"><?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?></span></button>
                    <a href="<?php echo SITE_URL; ?>/customer/shop-chat.php?shop_id=<?php echo $product_data['seller_shop_id']; ?>" class="pd-btn pd-btn-outline"><i class="fas fa-comments"></i> Chat with Shop</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php?role=customer" class="pd-btn pd-btn-primary"><i class="fas fa-sign-in-alt"></i> Login to Buy</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="pd-section-title">Customer Reviews</div>
    <?php if ($reviews_result->num_rows > 0): ?>
        <?php while ($rv = $reviews_result->fetch_assoc()): ?>
            <div class="pd-review-card">
                <div>
                    <span class="pd-review-user"><?php echo htmlspecialchars($rv['name'] ?? 'Anonymous'); ?></span>
                    <span class="pd-rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $rv['rating'] ? '' : ' empty'; ?>"></i>
                        <?php endfor; ?>
                    </span>
                    <span class="pd-review-date"><?php echo timeAgo($rv['created_at']); ?></span>
                </div>
                <?php if ($rv['comment']): ?>
                    <div class="pd-review-comment"><?php echo htmlspecialchars($rv['comment']); ?></div>
                <?php endif; ?>
                <?php if (!empty($rv['review_image'])): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $rv['review_image']; ?>" class="pd-review-img" alt="Review image">
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color:#888;">No reviews yet. Be the first to review this product!</p>
    <?php endif; ?>

    <?php if (isLoggedIn() && $current_user['role'] === 'customer'): ?>
        <form method="post" class="pd-review-form" enctype="multipart/form-data">
            <h4 style="margin-bottom:16px;font-weight:600;">Write a Review</h4>
            <label>Your Rating</label>
            <div class="pd-star-input">
                <input type="radio" name="rating" value="5" id="s5" required><label for="s5"><i class="fas fa-star"></i></label>
                <input type="radio" name="rating" value="4" id="s4"><label for="s4"><i class="fas fa-star"></i></label>
                <input type="radio" name="rating" value="3" id="s3"><label for="s3"><i class="fas fa-star"></i></label>
                <input type="radio" name="rating" value="2" id="s2"><label for="s2"><i class="fas fa-star"></i></label>
                <input type="radio" name="rating" value="1" id="s1"><label for="s1"><i class="fas fa-star"></i></label>
            </div>
            <label>Your Review</label>
            <textarea name="comment" placeholder="Share your experience with this product..." required></textarea>
            <div style="margin-top:10px;">
                <label style="font-weight:600;font-size:0.9rem;margin-bottom:6px;display:block;">Attach Image (optional)</label>
                <input type="file" name="review_image" accept="image/*" style="font-size:0.88rem;">
            </div>
            <button type="submit" name="add_review" class="pd-btn pd-btn-primary" style="margin-top:12px;"><i class="fas fa-paper-plane"></i> Submit Review</button>
        </form>
    <?php endif; ?>

    <?php if ($related_result->num_rows > 0): ?>
        <div style="margin-top:50px;">
            <h2 class="pd-section-title">Related Products</h2>
            <div class="pd-related-grid">
                <?php while ($rel = $related_result->fetch_assoc()): ?>
                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $rel['product_id']; ?>" class="pd-related-card">
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($rel['product_image']) ? $rel['product_image'] : 'no-image.png'; ?>" alt="<?php echo htmlspecialchars($rel['product_name']); ?>">
                        <div class="pd-related-body">
                            <div class="pd-related-name"><?php echo htmlspecialchars($rel['product_name']); ?></div>
                            <div>
                                <?php if ($rel['discount_price'] && $rel['discount_price'] < $rel['price']): ?>
                                    <span class="pd-related-price"><?php echo formatCurrency($rel['discount_price']); ?></span>
                                    <span class="pd-related-old"><?php echo formatCurrency($rel['price']); ?></span>
                                <?php else: ?>
                                    <span class="pd-related-price"><?php echo formatCurrency($rel['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="js/wishlist.js"></script>
<script>
function toggleWishlistDetail(productId) {
    fetch('<?php echo SITE_URL; ?>/api/wishlist.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            var btn = document.getElementById('wishlistBtn');
            var txt = document.getElementById('wishlistBtnText');
            if (data.action === 'added') {
                btn.className = 'pd-btn pd-btn-danger';
                btn.querySelector('i').className = 'fas fa-heart';
                txt.textContent = 'Remove from Wishlist';
            } else {
                btn.className = 'pd-btn pd-btn-outline';
                btn.querySelector('i').className = 'far fa-heart';
                txt.textContent = 'Add to Wishlist';
            }
        } else if (data.msg === 'login_required') {
            window.location.href = '<?php echo SITE_URL; ?>/login.php';
        }
    });
}
</script>

<?php
$reviews->close();
require_once __DIR__ . '/includes/footer.php';
?>
