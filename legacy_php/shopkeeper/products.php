<?php
$page_title = 'Manage Products';
require_once __DIR__ . '/../includes/config.php';
requireRole('shopkeeper');

$shop = null;
$stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shop) {
    setFlash('warning', 'Please set up your shop first.');
    redirect(SITE_URL . '/shopkeeper/profile.php');
}

$shop_id = $shop['shop_id'];

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();

    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $product_name = sanitize($_POST['product_name']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $brand = sanitize($_POST['brand']);
        $car_brand = sanitize($_POST['car_brand']);
        $car_model = sanitize($_POST['car_model']);
        $compatible_vehicles = sanitize($_POST['compatible_vehicles']);
        $action_type = $_POST['action'];

        $product_image = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file = $_FILES['product_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($ext, $allowed) && in_array($mime, $allowed_mimes)) {
                $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_path = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $product_image = $filename;
                }
            }
        }

        if ($action_type === 'add') {
            if ($category_id && $discount_price && $product_image) {
                $stmt = $conn->prepare("INSERT INTO products (shop_id, category_id, product_name, description, price, discount_price, stock, brand, car_brand, car_model, compatible_vehicles, product_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissddisssss", $shop_id, $category_id, $product_name, $description, $price, $discount_price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_image);
            } elseif ($category_id && $product_image) {
                $stmt = $conn->prepare("INSERT INTO products (shop_id, category_id, product_name, description, price, stock, brand, car_brand, car_model, compatible_vehicles, product_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdissssss", $shop_id, $category_id, $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_image);
            } elseif ($category_id) {
                $stmt = $conn->prepare("INSERT INTO products (shop_id, category_id, product_name, description, price, stock, brand, car_brand, car_model, compatible_vehicles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdissss", $shop_id, $category_id, $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles);
            } else {
                $stmt = $conn->prepare("INSERT INTO products (shop_id, product_name, description, price, stock, brand, car_brand, car_model, compatible_vehicles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdissss", $shop_id, $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles);
            }
            if ($stmt->execute()) {
                setFlash('success', 'Product added successfully!');
            } else {
                setFlash('danger', 'Database error: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($action_type === 'edit') {
            $product_id = (int)$_POST['product_id'];
            if ($product_image) {
                if ($category_id && $discount_price) {
                    $stmt = $conn->prepare("UPDATE products SET category_id=?, product_name=?, description=?, price=?, discount_price=?, stock=?, brand=?, car_brand=?, car_model=?, compatible_vehicles=?, product_image=? WHERE product_id=? AND shop_id=?");
                    $stmt->bind_param("iissddissssii", $category_id, $product_name, $description, $price, $discount_price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_image, $product_id, $shop_id);
                } elseif ($category_id) {
                    $stmt = $conn->prepare("UPDATE products SET category_id=?, product_name=?, description=?, price=?, stock=?, brand=?, car_brand=?, car_model=?, compatible_vehicles=?, product_image=? WHERE product_id=? AND shop_id=?");
                    $stmt->bind_param("iisdissssii", $category_id, $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_image, $product_id, $shop_id);
                } else {
                    $stmt = $conn->prepare("UPDATE products SET product_name=?, description=?, price=?, stock=?, brand=?, car_brand=?, car_model=?, compatible_vehicles=?, product_image=? WHERE product_id=? AND shop_id=?");
                    $stmt->bind_param("sissssssii", $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_image, $product_id, $shop_id);
                }
            } else {
                if ($category_id && $discount_price) {
                    $stmt = $conn->prepare("UPDATE products SET category_id=?, product_name=?, description=?, price=?, discount_price=?, stock=?, brand=?, car_brand=?, car_model=?, compatible_vehicles=? WHERE product_id=? AND shop_id=?");
                    $stmt->bind_param("iissddisssii", $category_id, $product_name, $description, $price, $discount_price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_id, $shop_id);
                } elseif ($category_id) {
                    $stmt = $conn->prepare("UPDATE products SET category_id=?, product_name=?, description=?, price=?, stock=?, brand=?, car_brand=?, car_model=?, compatible_vehicles=? WHERE product_id=? AND shop_id=?");
                    $stmt->bind_param("iisdisssii", $category_id, $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_id, $shop_id);
                } else {
                    $stmt = $conn->prepare("UPDATE products SET product_name=?, description=?, price=?, stock=?, brand=?, car_brand=?, car_model=?, compatible_vehicles=? WHERE product_id=? AND shop_id=?");
                    $stmt->bind_param("sisssssii", $product_name, $description, $price, $stock, $brand, $car_brand, $car_model, $compatible_vehicles, $product_id, $shop_id);
                }
            }
            if ($stmt->execute()) {
                setFlash('success', 'Product updated successfully!');
            } else {
                setFlash('danger', 'Database error: ' . $stmt->error);
            }
            $stmt->close();
        }

        redirect(SITE_URL . '/shopkeeper/products.php');
    }

    if ($_POST['action'] === 'delete') {
        $product_id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ? AND shop_id = ?");
        $stmt->bind_param("ii", $product_id, $shop_id);
        if ($stmt->execute()) {
            setFlash('success', 'Product deleted successfully!');
        } else {
            setFlash('danger', 'Database error: ' . $stmt->error);
        }
        $stmt->close();
        redirect(SITE_URL . '/shopkeeper/products.php');
    }
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.shop_id = ?";
$params = [$shop_id];
$types = "i";

if ($search) {
    $sql .= " AND (p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($filter_category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $filter_category;
    $types .= "i";
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
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
            <a class="dash-nav-link active" href="products.php"><i class="fas fa-boxes-stacked"></i>Products</a>
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
    <div class="dash-main">
        <a href="<?php echo SITE_URL; ?>/shopkeeper/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="dash-header">
            <h2 class="fw-bold mb-0"><i class="fas fa-boxes-stacked me-2"></i>Products</h2>
            <div class="dash-header-actions">
                <button class="dash-btn-action dash-btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-1"></i>Add Product
                </button>
            </div>
        </div>

        <div class="dash-card mb-4">
            <div class="dash-card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Search Products</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, brand, description..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Category</label>
                        <select name="category" class="form-select">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $filter_category == $cat['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="dash-btn-action dash-btn-primary w-100"><i class="fas fa-search me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="dash-card">
                <div class="dash-card-body text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No products found</h5>
                    <p class="text-muted">Add your first product to get started.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="dash-card">
                <div class="dash-card-body p-0">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $prod): ?>
                                    <tr>
                                        <td>
                                            <?php if ($prod['product_image']): ?>
                                                <img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($prod['product_image']); ?>" alt="" width="50" height="50" style="object-fit:cover;border-radius:8px;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" style="width:50px;height:50px;border-radius:8px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($prod['product_name']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($prod['category_name'] ?? 'N/A'); ?></small></td>
                                        <td><?php echo htmlspecialchars($prod['brand']); ?></td>
                                        <td class="product-price"><?php echo formatCurrency($prod['price']); ?></td>
                                        <td>
                                            <?php if ($prod['stock'] < 5): ?>
                                                <span class="dash-badge dash-badge-red"><?php echo $prod['stock']; ?></span>
                                            <?php elseif ($prod['stock'] < 20): ?>
                                                <span class="dash-badge dash-badge-orange"><?php echo $prod['stock']; ?></span>
                                            <?php else: ?>
                                                <span class="dash-badge dash-badge-green"><?php echo $prod['stock']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($prod['status'] === 'available'): ?>
                                                <span class="dash-badge dash-badge-green">Available</span>
                                            <?php else: ?>
                                                <span class="dash-badge dash-badge-gray"><?php echo ucfirst($prod['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="dash-btn-action dash-btn-outline btn-sm-modern me-1 edit-btn"
                                                data-id="<?php echo $prod['product_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                                data-category="<?php echo $prod['category_id']; ?>"
                                                data-description="<?php echo htmlspecialchars($prod['description']); ?>"
                                                data-price="<?php echo $prod['price']; ?>"
                                                data-discountprice="<?php echo $prod['discount_price'] ?? ''; ?>"
                                                data-stock="<?php echo $prod['stock']; ?>"
                                                data-brand="<?php echo htmlspecialchars($prod['brand']); ?>"
                                                data-carbrand="<?php echo htmlspecialchars($prod['car_brand'] ?? ''); ?>"
                                                data-carmodel="<?php echo htmlspecialchars($prod['car_model'] ?? ''); ?>"
                                                data-vehicles="<?php echo htmlspecialchars($prod['compatible_vehicles']); ?>"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="dash-btn-action dash-btn-outline btn-sm-modern delete-btn"
                                                data-id="<?php echo $prod['product_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                                title="Delete" style="border-color:#dc3545;color:#dc3545;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount Price</label>
                            <input type="number" name="discount_price" class="form-control" step="0.01" min="0" placeholder="Leave empty for no discount">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock *</label>
                            <input type="number" name="stock" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand (Parts)</label>
                            <input type="text" name="brand" class="form-control" placeholder="e.g. Bosch, Brembo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Car Brand</label>
                            <select name="car_brand" class="form-select">
                                <option value="">Select Car Brand</option>
                                <option value="Toyota">Toyota</option>
                                <option value="Honda">Honda</option>
                                <option value="Ford">Ford</option>
                                <option value="Suzuki">Suzuki</option>
                                <option value="Hyundai">Hyundai</option>
                                <option value="Kia">Kia</option>
                                <option value="Nissan">Nissan</option>
                                <option value="BMW">BMW</option>
                                <option value="Mercedes">Mercedes</option>
                                <option value="Audi">Audi</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Car Model</label>
                            <input type="text" name="car_model" class="form-control" placeholder="e.g. Corolla, Civic">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Compatible Vehicles</label>
                            <input type="text" name="compatible_vehicles" class="form-control" placeholder="e.g. Toyota Corolla 2020-2024">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="editProductForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="product_name" id="edit_product_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="edit_category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price *</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount Price</label>
                            <input type="number" name="discount_price" id="edit_discount_price" class="form-control" step="0.01" min="0" placeholder="Leave empty for no discount">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock *</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand (Parts)</label>
                            <input type="text" name="brand" id="edit_brand" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Car Brand</label>
                            <select name="car_brand" id="edit_car_brand" class="form-select">
                                <option value="">Select Car Brand</option>
                                <option value="Toyota">Toyota</option>
                                <option value="Honda">Honda</option>
                                <option value="Ford">Ford</option>
                                <option value="Suzuki">Suzuki</option>
                                <option value="Hyundai">Hyundai</option>
                                <option value="Kia">Kia</option>
                                <option value="Nissan">Nissan</option>
                                <option value="BMW">BMW</option>
                                <option value="Mercedes">Mercedes</option>
                                <option value="Audi">Audi</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Car Model</label>
                            <input type="text" name="car_model" id="edit_car_model" class="form-control" placeholder="e.g. Corolla, Civic">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Compatible Vehicles</label>
                            <input type="text" name="compatible_vehicles" id="edit_vehicles" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Image (leave empty to keep current)</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" id="delete_product_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Delete Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p class="text-muted">This will permanently delete <strong id="delete_product_name"></strong>. This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_product_id').value = this.dataset.id;
        document.getElementById('edit_product_name').value = this.dataset.name;
        document.getElementById('edit_category_id').value = this.dataset.category;
        document.getElementById('edit_description').value = this.dataset.description;
        document.getElementById('edit_price').value = this.dataset.price;
        document.getElementById('edit_discount_price').value = this.dataset.discountprice;
        document.getElementById('edit_stock').value = this.dataset.stock;
        document.getElementById('edit_brand').value = this.dataset.brand;
        document.getElementById('edit_car_brand').value = this.dataset.carbrand || '';
        document.getElementById('edit_car_model').value = this.dataset.carmodel || '';
        document.getElementById('edit_vehicles').value = this.dataset.vehicles;
        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    });
});

document.querySelectorAll('.delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('delete_product_id').value = this.dataset.id;
        document.getElementById('delete_product_name').textContent = this.dataset.name;
        new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
    });
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
