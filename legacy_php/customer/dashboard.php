<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

$total_orders = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
$total_orders->bind_param("i", $user_id);
$total_orders->execute();
$total_orders_count = $total_orders->get_result()->fetch_assoc()['total'];
$total_orders->close();

$pending_orders = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND order_status = 'pending'");
$pending_orders->bind_param("i", $user_id);
$pending_orders->execute();
$pending_orders_count = $pending_orders->get_result()->fetch_assoc()['total'];
$pending_orders->close();

$wishlist_count = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
$wishlist_count->bind_param("i", $user_id);
$wishlist_count->execute();
$wishlist_count_val = $wishlist_count->get_result()->fetch_assoc()['total'];
$wishlist_count->close();
$wishIds = getUserWishlistIds();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$searchVal = htmlspecialchars($_GET['search'] ?? '');
$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$filter_brand = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$filter_model = isset($_GET['model']) ? sanitize($_GET['model']) : '';
$filter_year = isset($_GET['year']) ? sanitize($_GET['year']) : '';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 8;
$pageOffset = ($page - 1) * $perPage;

$pagination_params = [];
if ($search) $pagination_params['search'] = $search;
if ($filter_category) $pagination_params['category'] = $filter_category;
if ($filter_brand) $pagination_params['brand'] = $filter_brand;
if ($filter_model) $pagination_params['model'] = $filter_model;
if ($filter_year) $pagination_params['year'] = $filter_year;

$where = "WHERE p.status = 'available'";
$params = [];
$types = "";

if ($search) {
    $s = "%{$search}%";
    $where .= " AND (p.product_name LIKE ? OR p.description LIKE ? OR p.brand LIKE ? OR p.car_brand LIKE ? OR p.car_model LIKE ?)";
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
    $types .= "sssss";
}
if ($filter_category) {
    $where .= " AND p.category_id = ?";
    $params[] = $filter_category;
    $types .= "i";
}
if ($filter_brand) {
    $where .= " AND p.car_brand = ?";
    $params[] = $filter_brand;
    $types .= "s";
}
if ($filter_model) {
    $where .= " AND p.car_model = ?";
    $params[] = $filter_model;
    $types .= "s";
}
if ($filter_year) {
    $where .= " AND p.compatible_vehicles LIKE ?";
    $params[] = "%{$filter_year}%";
    $types .= "s";
}

$hasFilters = $search || $filter_category || $filter_brand || $filter_model || $filter_year;

if (!empty($params)) {
    $products_stmt = $conn->prepare("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id $where ORDER BY p.created_at DESC LIMIT 8");
    $products_stmt->bind_param($types, ...$params);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
} else {
    $products_stmt = null;
    $products_result = $conn->query("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'available' ORDER BY p.created_at DESC LIMIT 8");
}

if (!empty($params)) {
    $hot_deals_stmt = $conn->prepare("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id $where AND p.discount_price IS NOT NULL AND p.discount_price > 0 AND p.discount_price < p.price ORDER BY p.created_at DESC LIMIT 4");
    $hot_deals_stmt->bind_param($types, ...$params);
    $hot_deals_stmt->execute();
    $hot_deals_result = $hot_deals_stmt->get_result();
} else {
    $hot_deals_stmt = null;
    $hot_deals_result = $conn->query("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'available' AND p.discount_price IS NOT NULL AND p.discount_price > 0 AND p.discount_price < p.price ORDER BY p.created_at DESC LIMIT 4");
}

$bannerDeals = $conn->query("SELECT hd.*, s.shop_name FROM hot_deals hd LEFT JOIN shops s ON hd.shop_id = s.shop_id WHERE hd.status = 'active' AND CURDATE() >= hd.start_date AND CURDATE() <= hd.end_date ORDER BY hd.priority ASC, hd.created_at DESC LIMIT 5");

if (!empty($params)) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM products p $where");
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_products = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'available'")->fetch_assoc()['total'];
}
$total_pages = ceil($total_products / $perPage);

if (!empty($params)) {
    $all_stmt = $conn->prepare("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $pageOffset");
    $all_stmt->bind_param($types, ...$params);
    $all_stmt->execute();
    $all_products_result = $all_stmt->get_result();
} else {
    $all_products_result = $conn->query("SELECT p.*, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'available' ORDER BY p.created_at DESC LIMIT $perPage OFFSET $pageOffset");
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);
$car_brands = $conn->query("SELECT brand_name AS car_brand FROM car_brands ORDER BY brand_name")->fetch_all(MYSQLI_ASSOC);
$car_models = $conn->query("SELECT model_name AS car_model FROM car_models ORDER BY model_name")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/header.php';
?>

<style>
    .bp-search-bar {
        display: flex; align-items: center; gap: 0;
        background: #fff; border: 1.5px solid #e0e0e0; border-radius: 50px;
        padding: 5px 5px 5px 20px; margin-bottom: 0;
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
    .bp-filter-panel {
        background: #fff; border-radius: 16px; border: 1px solid #e8e8e8;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-top: 14px;
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
    .bp-cat-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .bp-cat-pill {
        padding: 8px 16px; border: 1.5px solid #e8e8e8; border-radius: 50px;
        font-size: 0.82rem; font-weight: 500; color: #555; cursor: pointer;
        text-decoration: none; transition: all .2s; white-space: nowrap;
    }
    .bp-cat-pill:hover { border-color: #dc3545; color: #dc3545; }
    .bp-cat-pill.active { background: #dc3545; color: #fff; border-color: #dc3545; }
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
    @media (max-width: 768px) {
        .bp-search-bar { flex-wrap: wrap; border-radius: 14px; padding: 10px 12px; }
        .bp-search-bar input { min-width: 0; }
        .bp-filter-body { padding-left: 20px; }
        .bp-select-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="container-fluid px-4">
    <?php if ($hasFilters): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding:16px 20px;background:#f8f9fa;border-radius:12px;">
            <div>
                <h4 style="margin:0;font-weight:700;">Search Results <small style="color:#999;font-weight:400;">(<?php echo $total_products; ?> found)</small></h4>
                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                    <?php if ($search): ?><span style="background:#e8f0fe;color:#1a73e8;padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:500;">"<?php echo htmlspecialchars($search); ?>"</span><?php endif; ?>
                    <?php if ($filter_category): ?><?php foreach ($categories as $c) { if ($c['category_id'] == $filter_category) { ?><span style="background:#e8f4fd;color:#3498db;padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:500;"><?php echo $c['category_name']; ?></span><?php break; } } ?><?php endif; ?>
                    <?php if ($filter_brand): ?><span style="background:#e8f8f0;color:#27ae60;padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:500;"><?php echo htmlspecialchars($filter_brand); ?></span><?php endif; ?>
                    <?php if ($filter_model): ?><span style="background:#fef3e6;color:#e67e22;padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:500;"><?php echo htmlspecialchars($filter_model); ?></span><?php endif; ?>
                    <?php if ($filter_year): ?><span style="background:#f0e8fd;color:#9b59b6;padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:500;"><?php echo htmlspecialchars($filter_year); ?></span><?php endif; ?>
                </div>
            </div>
            <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#dc3545;text-decoration:none;font-size:0.85rem;font-weight:600;white-space:nowrap;"><i class="fas fa-times me-1"></i>Clear All</a>
        </div>
    <?php else: ?>
    <div class="cust-welcome-banner">
        <div class="cust-welcome-left">
            <div class="cust-welcome-label">Welcome Back</div>
            <h1 class="cust-welcome-title">Find the right part for your car</h1>
            <p class="cust-welcome-desc">Search 10,000+ genuine parts, or use the Filters in the search bar above to narrow it down by brand, model & year.</p>
        </div>
        <div class="cust-welcome-actions">
            <a href="<?php echo SITE_URL; ?>/workshop-finder.php" class="cust-btn-workshop">
                <i class="fas fa-tools"></i> Find a Trusted Workshop
            </a>
            <a href="<?php echo SITE_URL; ?>/customer/chatbot.php" class="cust-btn-chatbot">
                <i class="fas fa-robot"></i> Chat with AutoBot
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="cust-search-section">
        <div class="bp-search-bar" id="liveSearchWrapper">
            <i class="fas fa-search" style="color:#aaa;margin-right:10px;"></i>
            <input type="text" id="liveSearchInput" placeholder="Search for parts, brands or vehicles..." autocomplete="off" value="<?php echo $searchVal ?? ''; ?>">
            <button class="bp-filter-btn" onclick="document.getElementById('custFilterPanel').classList.toggle('show')">
                <i class="fas fa-sliders-h"></i> Filters
            </button>
            <button class="bp-search-btn" onclick="custSearch()"><i class="fas fa-search"></i></button>
        </div>
        <div class="cust-search-dropdown" id="liveSearchDropdown"></div>

        <div class="bp-filter-panel" id="custFilterPanel">
            <form method="GET" action="<?php echo SITE_URL; ?>/customer/dashboard.php">
                <input type="hidden" name="search" id="custFilterSearch" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <input type="hidden" name="category" id="custCatInput" value="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>">
                <input type="hidden" name="brand" id="custBrandInput" value="<?php echo htmlspecialchars($_GET['brand'] ?? ''); ?>">
                <input type="hidden" name="model" id="custModelInput" value="<?php echo htmlspecialchars($_GET['model'] ?? ''); ?>">
                <input type="hidden" name="year" id="custYearInput" value="<?php echo htmlspecialchars($_GET['year'] ?? ''); ?>">

                <div class="bp-filter-section open">
                    <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                        <div class="bp-filter-header-icon cat"><i class="fas fa-th-large"></i></div>
                        <div class="bp-filter-header-label">Category</div>
                        <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                    </div>
                    <div class="bp-filter-body">
                        <div class="bp-cat-pills" id="custCatPills"></div>
                    </div>
                </div>

                <div class="bp-filter-section">
                    <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                        <div class="bp-filter-header-icon brand"><i class="fas fa-car"></i></div>
                        <div class="bp-filter-header-label">Brand</div>
                        <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                    </div>
                    <div class="bp-filter-body">
                        <div class="bp-select-grid" id="custBrandPills"></div>
                    </div>
                </div>

                <div class="bp-filter-section">
                    <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                        <div class="bp-filter-header-icon model"><i class="fas fa-truck-monster"></i></div>
                        <div class="bp-filter-header-label">Model</div>
                        <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                    </div>
                    <div class="bp-filter-body">
                        <div class="bp-select-grid" id="custModelPills"></div>
                    </div>
                </div>

                <div class="bp-filter-section">
                    <div class="bp-filter-header" onclick="this.parentElement.classList.toggle('open')">
                        <div class="bp-filter-header-icon year"><i class="fas fa-calendar-alt"></i></div>
                        <div class="bp-filter-header-label">Year</div>
                        <i class="fas fa-chevron-right bp-filter-header-chevron"></i>
                    </div>
                    <div class="bp-filter-body">
                        <div class="bp-select-grid" id="custYearPills"></div>
                    </div>
                </div>

                <div class="bp-filter-actions">
                    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="bp-reset-link"><i class="fas fa-undo"></i> Reset</a>
                    <button type="submit" class="bp-find-btn">Find Parts</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function(){
        var cats = <?php echo json_encode($categories ?? []); ?>;
        var brands = <?php echo json_encode($car_brands ?? []); ?>;
        var models = <?php echo json_encode($car_models ?? []); ?>;
        var curCat = '<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>';
        var curBrand = '<?php echo htmlspecialchars($_GET['brand'] ?? ''); ?>';
        var curModel = '<?php echo htmlspecialchars($_GET['model'] ?? ''); ?>';
        var curYear = '<?php echo htmlspecialchars($_GET['year'] ?? ''); ?>';

        var catHtml = '<a href="#" class="bp-cat-pill'+(!curCat?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custCatInput\').value=\'\';document.querySelector(\'#custFilterPanel form\').submit();">All</a>';
        cats.forEach(function(c){
            catHtml += '<a href="#" class="bp-cat-pill'+(curCat==c.category_id?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custCatInput\').value=\''+c.category_id+'\';document.querySelector(\'#custFilterPanel form\').submit();">'+c.category_name+'</a>';
        });
        document.getElementById('custCatPills').innerHTML = catHtml;

        var brandHtml = '<a href="#" class="bp-select-pill'+(!curBrand?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custBrandInput\').value=\'\';document.querySelector(\'#custFilterPanel form\').submit();">All</a>';
        brands.forEach(function(b){
            brandHtml += '<a href="#" class="bp-select-pill'+(curBrand===b.car_brand?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custBrandInput\').value=\''+b.car_brand+'\';document.querySelector(\'#custFilterPanel form\').submit();">'+b.car_brand+'</a>';
        });
        document.getElementById('custBrandPills').innerHTML = brandHtml;

        var modelHtml = '<a href="#" class="bp-select-pill'+(!curModel?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custModelInput\').value=\'\';document.querySelector(\'#custFilterPanel form\').submit();">All</a>';
        models.forEach(function(m){
            modelHtml += '<a href="#" class="bp-select-pill'+(curModel===m.car_model?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custModelInput\').value=\''+m.car_model+'\';document.querySelector(\'#custFilterPanel form\').submit();">'+m.car_model+'</a>';
        });
        document.getElementById('custModelPills').innerHTML = modelHtml;

        var yearHtml = '<a href="#" class="bp-select-pill'+(!curYear?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custYearInput\').value=\'\';document.querySelector(\'#custFilterPanel form\').submit();">All</a>';
        var now = new Date().getFullYear();
        for(var y=now;y>=2010;y--){
            yearHtml += '<a href="#" class="bp-select-pill'+(curYear==y?' active':'')+'" onclick="event.preventDefault();document.getElementById(\'custYearInput\').value=\''+y+'\';document.querySelector(\'#custFilterPanel form\').submit();">'+y+'</a>';
        }
        document.getElementById('custYearPills').innerHTML = yearHtml;

        document.getElementById('liveSearchInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); custSearch(); }
        });
    })();

    function custSearch() {
        var q = document.getElementById('liveSearchInput').value.trim();
        window.location.href = '<?php echo SITE_URL; ?>/customer/dashboard.php?search=' + encodeURIComponent(q);
    }

    document.addEventListener('click', function(e){
        var p = document.getElementById('custFilterPanel');
        var w = document.getElementById('liveSearchWrapper');
        if(p && w && !p.contains(e.target) && !w.contains(e.target)) p.classList.remove('show');
    });

    var liveSearchInput = document.getElementById('liveSearchInput');
    var liveSearchDropdown = document.getElementById('liveSearchDropdown');
    var searchTimer = null;
    var siteUrl = '<?php echo SITE_URL; ?>';
    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length < 2) { liveSearchDropdown.classList.remove('show'); return; }
            searchTimer = setTimeout(function() {
                fetch(siteUrl + '/search-api.php?q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        liveSearchDropdown.innerHTML = '';
                        if (data.length === 0) {
                            liveSearchDropdown.innerHTML = '<div class="cust-search-dropdown-empty">No products found</div>';
                        } else {
                            data.forEach(function(p) {
                                var img = p.product_image ? siteUrl + '/uploads/' + p.product_image : siteUrl + '/uploads/no-image.png';
                                var price = p.discount_formatted ? p.discount_formatted : p.price_formatted;
                                var oldPrice = p.discount_formatted ? '<span class="old-price">' + p.price_formatted + '</span>' : '';
                                var stockBadge = p.stock > 0 ? '<span style="color:#28a745;font-size:0.72rem;"><i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:3px;"></i>In Stock</span>' : '<span style="color:#dc3545;font-size:0.72rem;">Out of Stock</span>';
                                liveSearchDropdown.innerHTML += '<a href="' + siteUrl + '/product-detail.php?id=' + p.product_id + '" class="cust-search-dropdown-item">' +
                                    '<img src="' + img + '" alt="' + p.product_name + '">' +
                                    '<div class="info"><div class="name">' + p.product_name + '</div>' +
                                    '<div class="meta">' + (p.brand ? p.brand + ' &middot; ' : '') + (p.shop_name || '') + ' &middot; ' + stockBadge + '</div>' +
                                    '<div><span class="price">' + price + '</span>' + oldPrice + '</div></div></a>';
                            });
                        }
                        liveSearchDropdown.classList.add('show');
                    });
            }, 300);
        });
        document.addEventListener('click', function(e) {
            if (!document.getElementById('liveSearchWrapper').contains(e.target)) {
                liveSearchDropdown.classList.remove('show');
            }
        });
    }
    </script>

    <?php if (!$hasFilters && $bannerDeals && $bannerDeals->num_rows > 0): ?>
    <div id="custHotDealsBanner" class="cust-section">
        <div class="cust-section-header">
            <h2 class="cust-section-title"><i class="fas fa-fire" style="color:#dc3545;margin-right:6px;"></i> Hot Deals</h2>
        </div>
        <div style="display:flex;gap:16px;overflow-x:auto;padding-bottom:8px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;">
            <?php while ($bd = $bannerDeals->fetch_assoc()): ?>
            <div style="min-width:320px;max-width:400px;flex-shrink:0;scroll-snap-align:start;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #f0f0f0;box-shadow:0 4px 16px rgba(0,0,0,0.05);transition:transform .3s,box-shadow .3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.05)'">
                <?php if (!empty($bd['banner_image'])): ?>
                    <div style="height:160px;overflow:hidden;position:relative;">
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo htmlspecialchars($bd['banner_image']); ?>" alt="<?php echo htmlspecialchars($bd['title']); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php if (!empty($bd['discount_text'])): ?>
                            <span style="position:absolute;top:12px;left:12px;background:linear-gradient(135deg,#dc3545,#b71c1c);color:#fff;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;box-shadow:0 4px 12px rgba(220,53,69,0.4);"><i class="fas fa-percent" style="margin-right:4px;"></i><?php echo htmlspecialchars($bd['discount_text']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="height:160px;background:linear-gradient(135deg,#dc3545,#b71c1c);display:flex;align-items:center;justify-content:center;position:relative;">
                        <i class="fas fa-fire" style="font-size:3rem;color:rgba(255,255,255,0.25);"></i>
                        <?php if (!empty($bd['discount_text'])): ?>
                            <span style="position:absolute;top:12px;left:12px;background:rgba(255,255,255,0.95);color:#dc3545;padding:5px 14px;border-radius:20px;font-size:0.78rem;font-weight:700;"><i class="fas fa-percent" style="margin-right:4px;"></i><?php echo htmlspecialchars($bd['discount_text']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div style="padding:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px;">
                        <h4 style="font-weight:700;font-size:1rem;color:#1a1a2e;margin:0;"><?php echo htmlspecialchars($bd['title']); ?></h4>
                        <?php if (!empty($bd['shop_name'])): ?>
                            <span style="font-size:0.72rem;color:#888;background:#f5f5f5;padding:2px 8px;border-radius:6px;white-space:nowrap;margin-left:8px;"><?php echo htmlspecialchars($bd['shop_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($bd['description'])): ?>
                        <p style="font-size:0.82rem;color:#888;margin:0 0 10px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?php echo htmlspecialchars($bd['description']); ?></p>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <?php if (!empty($bd['coupon_code'])): ?>
                            <span style="font-size:0.75rem;color:#dc3545;background:#fff0f0;padding:4px 10px;border-radius:8px;font-weight:600;border:1px dashed rgba(220,53,69,0.3);"><i class="fas fa-tag" style="margin-right:4px;"></i><?php echo htmlspecialchars($bd['coupon_code']); ?></span>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <span style="font-size:0.75rem;color:#999;"><i class="fas fa-calendar-alt" style="margin-right:4px;"></i><?php echo date('M d', strtotime($bd['start_date'])); ?> - <?php echo date('M d, Y', strtotime($bd['end_date'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <script>
    (function(){
        var currentCount = <?php echo $bannerDeals ? $bannerDeals->num_rows : 0; ?>;
        setInterval(function(){
            fetch('<?php echo SITE_URL; ?>/api/hot-deals-count.php')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d.count !== currentCount){
                    location.reload();
                }
            });
        }, 3000);
    })();
    </script>

    <div class="cust-section">
        <div class="cust-section-header">
            <h2 class="cust-section-title"><?php echo $hasFilters ? 'Filtered Results' : 'Best Selling Parts'; ?></h2>
            <?php if ($hasFilters): ?>
                <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="cust-section-link">Clear Filters <i class="fas fa-times ms-1"></i></a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/products.php" class="cust-section-link">View All <i class="fas fa-arrow-right ms-1"></i></a>
            <?php endif; ?>
        </div>
        <?php if ($products_result && $products_result->num_rows > 0): ?>
            <div class="cust-products-grid">
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $product['product_id']; ?>" class="cust-product-card" style="position:relative;">
                        <button type="button" class="wishlist-btn <?php echo in_array($product['product_id'], $wishIds) ? 'active' : ''; ?>" onclick="event.preventDefault();event.stopPropagation();toggleWishlist(<?php echo $product['product_id']; ?>, this)"><i class="<?php echo in_array($product['product_id'], $wishIds) ? 'fas' : 'far'; ?> fa-heart"></i></button>
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($product['product_image']) ? $product['product_image'] : 'no-image.png'; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="cust-product-img">
                        <div class="cust-product-body">
                            <div class="cust-product-shop"><?php echo htmlspecialchars($product['shop_name'] ?? 'Verified Seller'); ?></div>
                            <div class="cust-product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div>
                                <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                                    <span class="cust-product-price"><?php echo formatCurrency($product['discount_price']); ?></span>
                                    <span class="cust-product-old-price"><?php echo formatCurrency($product['price']); ?></span>
                                <?php else: ?>
                                    <span class="cust-product-price"><?php echo formatCurrency($product['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['stock'] > 0): ?>
                                <button type="button" class="cust-add-cart-btn" onclick="event.preventDefault();event.stopPropagation();addToCart(<?php echo $product['product_id']; ?>, this)"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            <?php else: ?>
                                <button type="button" class="cust-add-cart-btn" disabled><i class="fas fa-times"></i> Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="cust-empty-state">
                <div class="cust-empty-icon"><i class="fas fa-search"></i></div>
                <h3 class="cust-empty-title">No parts matched your search</h3>
                <p class="cust-empty-desc">Try a different keyword or clear your filters.</p>
                <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" class="cust-empty-btn">
                    <i class="fas fa-times"></i> Clear search & filters
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$hasFilters): ?>
    <div class="cust-section">
        <div class="cust-section-header">
            <h2 class="cust-section-title">All Products</h2>
        </div>
        <?php if ($all_products_result && $all_products_result->num_rows > 0): ?>
            <div class="cust-products-grid">
                <?php while ($ap = $all_products_result->fetch_assoc()): ?>
                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?php echo $ap['product_id']; ?>" class="cust-product-card" style="position:relative;">
                        <button type="button" class="wishlist-btn <?php echo in_array($ap['product_id'], $wishIds) ? 'active' : ''; ?>" onclick="event.preventDefault();event.stopPropagation();toggleWishlist(<?php echo $ap['product_id']; ?>, this)"><i class="<?php echo in_array($ap['product_id'], $wishIds) ? 'fas' : 'far'; ?> fa-heart"></i></button>
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($ap['product_image']) ? $ap['product_image'] : 'no-image.png'; ?>" alt="<?php echo htmlspecialchars($ap['product_name']); ?>" class="cust-product-img">
                        <div class="cust-product-body">
                            <div class="cust-product-shop"><?php echo htmlspecialchars($ap['shop_name'] ?? 'Verified Seller'); ?></div>
                            <div class="cust-product-name"><?php echo htmlspecialchars($ap['product_name']); ?></div>
                            <div>
                                <?php if ($ap['discount_price'] && $ap['discount_price'] < $ap['price']): ?>
                                    <span class="cust-product-price"><?php echo formatCurrency($ap['discount_price']); ?></span>
                                    <span class="cust-product-old-price"><?php echo formatCurrency($ap['price']); ?></span>
                                <?php else: ?>
                                    <span class="cust-product-price"><?php echo formatCurrency($ap['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($ap['stock'] > 0): ?>
                                <button type="button" class="cust-add-cart-btn" onclick="event.preventDefault();event.stopPropagation();addToCart(<?php echo $ap['product_id']; ?>, this)"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            <?php else: ?>
                                <button type="button" class="cust-add-cart-btn" disabled><i class="fas fa-times"></i> Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="All Products pagination">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $page - 1])); ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($pagination_params, ['page' => $page + 1])); ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="cust-empty-state">
                <div class="cust-empty-icon"><i class="fas fa-box-open"></i></div>
                <h3 class="cust-empty-title">No products available</h3>
                <p class="cust-empty-desc">Check back soon for new arrivals!</p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script src="<?php echo SITE_URL; ?>/js/wishlist.js"></script>
<script>
var siteUrl = '<?php echo SITE_URL; ?>';
function addToCart(productId, btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    var original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    var formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    fetch(siteUrl + '/api/cart.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                btn.classList.add('added');
                var badge = document.querySelector('.cust-cart-badge');
                if (badge) badge.textContent = data.cart_count;
                setTimeout(function() {
                    btn.innerHTML = original;
                    btn.classList.remove('added');
                    btn.disabled = false;
                }, 1500);
            } else {
                btn.innerHTML = original;
                btn.disabled = false;
                alert(data.msg || 'Could not add to cart');
            }
        })
        .catch(function() {
            btn.innerHTML = original;
            btn.disabled = false;
            alert('Network error. Please try again.');
        });
}
</script>
<?php
if (isset($products_stmt) && $products_stmt) $products_stmt->close();
if (isset($hot_deals_stmt) && $hot_deals_stmt) $hot_deals_stmt->close();
require_once __DIR__ . '/footer.php';
?>
