<?php
$page_title = 'Browse Parts';
require_once __DIR__ . '/includes/config.php';

if (isset($_GET['wishlist']) && isLoggedIn() && $current_user['role'] === 'customer') {
    $pid = intval($_GET['wishlist']);
    $uid = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $uid, $pid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        setFlash('info', 'Product is already in your wishlist.');
    } else {
        $ins = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $ins->bind_param("ii", $uid, $pid);
        $ins->execute();
        $ins->close();
        setFlash('success', 'Product added to wishlist!');
    }
    $check->close();
    header("Location: " . SITE_URL . "/products.php");
    exit;
}

if (isset($_GET['add']) && isLoggedIn() && $current_user['role'] === 'customer') {
    $pid = intval($_GET['add']);
    $uid = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $uid, $pid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $uid AND product_id = $pid");
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
        $ins->bind_param("ii", $uid, $pid);
        $ins->execute();
    }
    setFlash('success', 'Product added to cart!');
    header("Location: " . SITE_URL . "/products.php");
    exit;
}

if (isset($_GET['id'])) {
    header("Location: " . SITE_URL . "/product-detail.php?id=" . intval($_GET['id']));
    exit;
}
if (isset($_GET['view'])) {
    header("Location: " . SITE_URL . "/product-detail.php?id=" . intval($_GET['view']));
    exit;
}

$where = "WHERE p.status = 'available'";
$params = [];
$types = "";

if (!empty($_GET['search'])) {
    $s = '%' . $_GET['search'] . '%';
    $where .= " AND (p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR p.compatible_vehicles LIKE ? OR p.car_brand LIKE ? OR p.car_model LIKE ?)";
    $params = array_merge($params, [$s, $s, $s, $s, $s, $s]);
    $types .= "ssssss";
}
if (!empty($_GET['category'])) {
    $where .= " AND p.category_id = ?";
    $params[] = intval($_GET['category']);
    $types .= "i";
}
if (!empty($_GET['brand'])) {
    $where .= " AND p.car_brand = ?";
    $params[] = $_GET['brand'];
    $types .= "s";
}
if (!empty($_GET['model'])) {
    $where .= " AND p.car_model = ?";
    $params[] = $_GET['model'];
    $types .= "s";
}
if (!empty($_GET['year'])) {
    $where .= " AND p.compatible_vehicles LIKE ?";
    $params[] = '%' . $_GET['year'] . '%';
    $types .= "s";
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$countQ = "SELECT COUNT(*) as total FROM products p $where";
if (!empty($params)) {
    $cStmt = $conn->prepare($countQ);
    $cStmt->bind_param($types, ...$params);
    $cStmt->execute();
    $total = $cStmt->get_result()->fetch_assoc()['total'];
    $cStmt->close();
} else {
    $total = $conn->query($countQ)->fetch_assoc()['total'];
}
$total_pages = ceil($total / $per_page);

$sql = "SELECT p.*, s.shop_name, c.category_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id LEFT JOIN categories c ON p.category_id = c.category_id $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $pStmt = $conn->prepare($sql);
    $pStmt->bind_param($types, ...$params);
    $pStmt->execute();
    $products = $pStmt->get_result();
} else {
    $products = $conn->query($sql);
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);
$car_brands = $conn->query("SELECT brand_name AS car_brand FROM car_brands ORDER BY brand_name")->fetch_all(MYSQLI_ASSOC);
$car_models = $conn->query("SELECT model_name AS car_model FROM car_models ORDER BY model_name")->fetch_all(MYSQLI_ASSOC);

$searchVal = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES);
$catVal = htmlspecialchars($_GET['category'] ?? '', ENT_QUOTES);
$brandVal = htmlspecialchars($_GET['brand'] ?? '', ENT_QUOTES);
$modelVal = htmlspecialchars($_GET['model'] ?? '', ENT_QUOTES);
$yearVal = htmlspecialchars($_GET['year'] ?? '', ENT_QUOTES);
$wishIds = getUserWishlistIds();

$catName = 'All Categories';
if ($catVal) {
    foreach ($categories as $c) {
        if ($c['category_id'] == $catVal) { $catName = $c['category_name']; break; }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .bp-wrapper { max-width: 1200px; margin: 0 auto; padding: 30px 20px 60px; }

    /* Search Bar */
    .bp-search-bar {
        display: flex; align-items: center; gap: 0;
        background: #fff; border: 1.5px solid #e0e0e0; border-radius: 50px;
        padding: 5px 5px 5px 20px; margin-bottom: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        transition: border-color .3s;
    }
    .bp-search-bar:focus-within { border-color: #dc3545; }
    .bp-search-bar input {
        flex: 1; border: none; outline: none; font-size: 0.95rem;
        color: #333; background: transparent; padding: 10px 0;
    }
    .bp-search-bar input::placeholder { color: #aaa; }
    .bp-filter-btn {
        display: flex; align-items: center; gap: 6px;
        padding: 10px 18px; background: #f8f8f8; border: 1.5px solid #e8e8e8;
        border-radius: 50px; cursor: pointer; font-size: 0.85rem;
        font-weight: 600; color: #555; transition: all .3s; white-space: nowrap;
    }
    .bp-filter-btn:hover, .bp-filter-btn.active { background: #dc3545; color: #fff; border-color: #dc3545; }
    .bp-search-btn {
        width: 48px; height: 48px; border-radius: 50%;
        background: #dc3545; color: #fff; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; margin-left: 4px; transition: background .3s;
    }
    .bp-search-btn:hover { background: #c82333; }

    /* Filter Panel */
    .bp-filter-panel {
        background: #fff; border-radius: 16px; border: 1px solid #e8e8e8;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 28px;
        overflow: hidden; display: none;
    }
    .bp-filter-panel.show { display: block; }

    .bp-filter-section { border-bottom: 1px solid #f0f0f0; }
    .bp-filter-section:last-child { border-bottom: none; }
    .bp-filter-header {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 20px; cursor: pointer; transition: background .15s;
    }
    .bp-filter-header:hover { background: #fafafa; }
    .bp-filter-header-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;
    }
    .bp-filter-header-icon.cat { background: #e8f4fd; color: #3498db; }
    .bp-filter-header-icon.brand { background: #e8f8f0; color: #27ae60; }
    .bp-filter-header-icon.model { background: #fef3e6; color: #e67e22; }
    .bp-filter-header-icon.year { background: #f0e8fd; color: #9b59b6; }
    .bp-filter-header-label { font-weight: 700; font-size: 0.95rem; color: #1a1a2e; }
    .bp-filter-header-chevron { margin-left: auto; color: #ccc; font-size: 0.75rem; transition: transform .3s; }
    .bp-filter-section.open .bp-filter-header-chevron { transform: rotate(90deg); }
    .bp-filter-body { display: none; padding: 0 20px 16px 62px; }
    .bp-filter-section.open .bp-filter-body { display: block; }

    /* Category Pills */
    .bp-cat-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .bp-cat-pill {
        padding: 8px 16px; border: 1.5px solid #e8e8e8; border-radius: 50px;
        font-size: 0.82rem; font-weight: 500; color: #555; cursor: pointer;
        text-decoration: none; transition: all .2s; white-space: nowrap;
    }
    .bp-cat-pill:hover { border-color: #dc3545; color: #dc3545; }
    .bp-cat-pill.active { background: #dc3545; color: #fff; border-color: #dc3545; }

    /* Brand/Model/Year Select */
    .bp-select-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
    .bp-select-pill {
        padding: 10px 14px; border: 1.5px solid #e8e8e8; border-radius: 10px;
        font-size: 0.85rem; font-weight: 500; color: #555; cursor: pointer;
        text-decoration: none; text-align: center; transition: all .2s;
    }
    .bp-select-pill:hover { border-color: #dc3545; color: #dc3545; }
    .bp-select-pill.active { background: #dc3545; color: #fff; border-color: #dc3545; }

    .bp-filter-actions {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; border-top: 1px solid #f0f0f0;
    }
    .bp-reset-link { color: #888; font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 4px; }
    .bp-reset-link:hover { color: #dc3545; }
    .bp-find-btn {
        padding: 10px 28px; background: #dc3545; color: #fff;
        border: none; border-radius: 50px; font-size: 0.88rem;
        font-weight: 600; cursor: pointer; transition: background .3s;
    }
    .bp-find-btn:hover { background: #c82333; }

    /* Results */
    .bp-results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .bp-results-header h4 { font-size: 1.1rem; font-weight: 700; margin: 0; }
    .bp-results-header small { color: #999; }

    /* Product Grid */
    .bp-products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
    .bp-product-card {
        background: #fff; border: 1px solid #e8e8e8; border-radius: 14px;
        overflow: hidden; text-decoration: none; color: inherit;
        transition: all .3s;
    }
    .bp-product-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); transform: translateY(-3px); }
    .bp-product-img-wrap {
        position: relative; background: #f8f9fa; height: 180px;
        display: flex; align-items: center; justify-content: center; padding: 16px;
    }
    .bp-product-img-wrap img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .bp-wishlist-btn {
        position: absolute; top: 10px; right: 10px;
        width: 32px; height: 32px; border-radius: 50%;
        background: rgba(255,255,255,0.9); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: #ddd; font-size: 0.85rem; transition: all .3s;
    }
    .bp-wishlist-btn:hover { color: #dc3545; background: #fff; }
    .bp-product-body { padding: 14px 16px 16px; }
    .bp-product-name { font-size: 0.88rem; font-weight: 600; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
    .bp-product-meta { font-size: 0.78rem; color: #888; margin-bottom: 8px; }
    .bp-product-bottom { display: flex; align-items: center; justify-content: space-between; }
    .bp-product-price { font-size: 1rem; font-weight: 800; color: #1a73e8; }
    .bp-product-old { font-size: 0.78rem; color: #999; text-decoration: line-through; margin-left: 4px; }
    .bp-cart-btn {
        width: 34px; height: 34px; border-radius: 50%;
        background: #1a73e8; color: #fff; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.8rem; transition: background .3s;
    }
    .bp-cart-btn:hover { background: #1557b0; }

    @media (max-width: 768px) {
        .bp-search-bar { flex-wrap: wrap; border-radius: 14px; padding: 10px 12px; }
        .bp-search-bar input { min-width: 0; }
        .bp-filter-body { padding-left: 20px; }
        .bp-products-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .bp-product-img-wrap { height: 140px; }
        .bp-select-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* Breadcrumb */
    .bp-breadcrumb {
        margin-bottom: 24px;
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
    .bp-breadcrumb::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 4px; height: 100%;
        background: var(--accent, #dc3545);
        border-radius: 4px 0 0 4px;
    }
    .bp-breadcrumb-item {
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
    .bp-breadcrumb-item:hover {
        color: #fff;
        background: rgba(255,255,255,0.1);
    }
    .bp-breadcrumb-item.active {
        color: #fff;
        font-weight: 600;
        background: rgba(220,53,69,0.2);
    }
    .bp-breadcrumb-item i { font-size: 0.82rem; opacity: 0.7; }
    .bp-breadcrumb-sep {
        color: rgba(255,255,255,0.25);
        font-size: 0.75rem;
        margin: 0 4px;
        user-select: none;
    }
</style>

<div class="bp-wrapper">
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <?php endif; ?>
    <div class="bp-breadcrumb">
        <a class="bp-breadcrumb-item" href="<?php echo SITE_URL; ?>/index.php">
            <i class="fas fa-home"></i> Home
        </a>
        <span class="bp-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bp-breadcrumb-item active">
            <i class="fas fa-box"></i> Products
            <?php if ($catVal && $catName !== 'All Categories'): ?>
                <span class="bp-breadcrumb-sep" style="margin:0 2px;"><i class="fas fa-chevron-right"></i></span>
                <?php echo htmlspecialchars($catName); ?>
            <?php endif; ?>
        </span>
    </div>

    <div class="bp-search-bar">
        <i class="fas fa-search" style="color:#aaa;margin-right:10px;"></i>
        <input type="text" id="bpSearchInput" placeholder="Search for parts, brands or vehicles..." value="<?php echo $searchVal; ?>">
        <button class="bp-filter-btn" id="bpFilterBtn" onclick="document.getElementById('bpFilterPanel').classList.toggle('show')">
            <i class="fas fa-sliders-h"></i> Filters
        </button>
        <button class="bp-search-btn" onclick="bpSearch()"><i class="fas fa-search"></i></button>
    </div>

    <div class="bp-filter-panel" id="bpFilterPanel">
        <form method="GET" id="bpFilterForm">
            <input type="hidden" name="search" id="bpFilterSearch" value="<?php echo $searchVal; ?>">
            <input type="hidden" name="category" id="bpCatInput" value="<?php echo $catVal; ?>">
            <input type="hidden" name="brand" id="bpBrandInput" value="<?php echo $brandVal; ?>">
            <input type="hidden" name="model" id="bpModelInput" value="<?php echo $modelVal; ?>">
            <input type="hidden" name="year" id="bpYearInput" value="<?php echo $yearVal; ?>">

            <!-- Category -->
            <div class="bp-filter-section open">
                <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                    <div class="bp-filter-header-icon cat"><i class="fas fa-th-large"></i></div>
                    <div class="bp-filter-header-label">Category</div>
                    <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                </div>
                <div class="bp-filter-body">
                    <div class="bp-cat-pills">
                        <a href="#" class="bp-cat-pill <?php echo !$catVal ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpCatInput').value='';document.getElementById('bpFilterForm').submit();">All</a>
                        <?php foreach ($categories as $c): ?>
                            <a href="#" class="bp-cat-pill <?php echo $catVal == $c['category_id'] ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpCatInput').value='<?php echo $c['category_id']; ?>';document.getElementById('bpFilterForm').submit();"><?php echo htmlspecialchars($c['category_name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Brand (Car) -->
            <div class="bp-filter-section">
                <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                    <div class="bp-filter-header-icon brand"><i class="fas fa-car"></i></div>
                    <div class="bp-filter-header-label">Brand</div>
                    <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                </div>
                <div class="bp-filter-body">
                    <div class="bp-select-grid">
                        <a href="#" class="bp-select-pill <?php echo !$brandVal ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpBrandInput').value='';document.getElementById('bpFilterForm').submit();">All Brands</a>
                        <?php foreach ($car_brands as $b): ?>
                            <a href="#" class="bp-select-pill <?php echo $brandVal === $b['car_brand'] ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpBrandInput').value='<?php echo htmlspecialchars($b['car_brand']); ?>';document.getElementById('bpFilterForm').submit();"><?php echo htmlspecialchars($b['car_brand']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Model (Car) -->
            <div class="bp-filter-section">
                <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                    <div class="bp-filter-header-icon model"><i class="fas fa-truck-monster"></i></div>
                    <div class="bp-filter-header-label">Model</div>
                    <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                </div>
                <div class="bp-filter-body">
                    <div class="bp-select-grid">
                        <a href="#" class="bp-select-pill <?php echo !$modelVal ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpModelInput').value='';document.getElementById('bpFilterForm').submit();">All Models</a>
                        <?php foreach ($car_models as $m): ?>
                            <a href="#" class="bp-select-pill <?php echo $modelVal === $m['car_model'] ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpModelInput').value='<?php echo htmlspecialchars($m['car_model']); ?>';document.getElementById('bpFilterForm').submit();"><?php echo htmlspecialchars($m['car_model']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Year -->
            <div class="bp-filter-section">
                <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                    <div class="bp-filter-header-icon year"><i class="fas fa-calendar-alt"></i></div>
                    <div class="bp-filter-header-label">Year</div>
                    <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                </div>
                <div class="bp-filter-body">
                    <div class="bp-select-grid">
                        <a href="#" class="bp-select-pill <?php echo !$yearVal ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpYearInput').value='';document.getElementById('bpFilterForm').submit();">All Years</a>
                        <?php for ($y = date('Y'); $y >= 2010; $y--): ?>
                            <a href="#" class="bp-select-pill <?php echo $yearVal == $y ? 'active' : ''; ?>" onclick="event.preventDefault();document.getElementById('bpYearInput').value='<?php echo $y; ?>';document.getElementById('bpFilterForm').submit();"><?php echo $y; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="bp-filter-actions">
                <a href="products.php" class="bp-reset-link"><i class="fas fa-undo"></i> Reset</a>
                <button type="submit" class="bp-find-btn">Find Parts</button>
            </div>
        </form>
    </div>

    <div class="bp-results-header">
        <h4>Products <small>(<?php echo $total; ?> found)</small></h4>
    </div>

    <?php if ($products->num_rows === 0): ?>
        <div style="text-align:center;padding:60px 20px;">
            <i class="fas fa-box-open" style="font-size:3rem;color:#ddd;margin-bottom:16px;"></i>
            <h5 style="color:#999;margin-bottom:8px;">No products found</h5>
            <p style="color:#bbb;font-size:0.9rem;">Try adjusting your search or filters</p>
            <a href="products.php" style="color:#dc3545;text-decoration:none;font-weight:600;margin-top:8px;display:inline-block;">Clear all filters</a>
        </div>
    <?php else: ?>
        <div class="bp-products-grid">
            <?php while ($p = $products->fetch_assoc()): ?>
                <a href="product-detail.php?id=<?php echo $p['product_id']; ?>" class="bp-product-card">
                    <div class="bp-product-img-wrap">
                        <?php if ($p['product_image']): ?>
                            <img src="uploads/<?php echo $p['product_image']; ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-cog" style="font-size:2.5rem;color:#ddd;"></i>
                        <?php endif; ?>
                        <?php if ($logged_in && $current_user['role'] === 'customer'): ?>
                            <button class="bp-wishlist-btn <?php echo in_array($p['product_id'], $wishIds) ? 'active' : ''; ?>" onclick="event.preventDefault();event.stopPropagation();toggleWishlist(<?php echo $p['product_id']; ?>, this)" title="Add to Wishlist"><i class="<?php echo in_array($p['product_id'], $wishIds) ? 'fas' : 'far'; ?> fa-heart"></i></button>
                        <?php endif; ?>
                    </div>
                    <div class="bp-product-body">
                        <div class="bp-product-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
                        <div class="bp-product-meta"><?php echo htmlspecialchars($p['brand'] ?? ''); ?><?php if ($p['car_brand']): ?> &middot; <?php echo htmlspecialchars($p['car_brand']); ?><?php endif; ?><?php if ($p['car_model']): ?> &middot; <?php echo htmlspecialchars($p['car_model']); ?><?php endif; ?></div>
                        <div class="bp-product-bottom">
                            <div>
                                <?php if ($p['discount_price'] && $p['discount_price'] < $p['price']): ?>
                                    <span class="bp-product-price"><?php echo formatCurrency($p['discount_price']); ?></span>
                                    <span class="bp-product-old"><?php echo formatCurrency($p['price']); ?></span>
                                <?php else: ?>
                                    <span class="bp-product-price"><?php echo formatCurrency($p['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($logged_in && $current_user['role'] === 'customer' && $p['stock'] > 0): ?>
                                <button class="bp-cart-btn" onclick="event.preventDefault();event.stopPropagation();window.location.href='?add=<?php echo $p['product_id']; ?>'" title="Add to Cart"><i class="fas fa-cart-plus"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav style="margin-top:32px;">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i class="fas fa-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function bpSearch() {
    var q = document.getElementById('bpSearchInput').value.trim();
    document.getElementById('bpFilterSearch').value = q;
    document.getElementById('bpFilterForm').submit();
}
document.getElementById('bpSearchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') bpSearch();
});
</script>
<script src="js/wishlist.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
