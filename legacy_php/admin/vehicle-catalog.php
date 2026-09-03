<?php
$page_title = 'Vehicle Catalog';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (car_brand LIKE ? OR car_model LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$query = "SELECT DISTINCT car_brand, car_model FROM products WHERE car_brand IS NOT NULL AND car_brand != '' $where ORDER BY car_brand ASC, car_model ASC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalVehicles = count($vehicles);

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-car"></i> Vehicle Catalog</h2>
                <p class="admin-page-subtitle">Reference list of compatible vehicles from products</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-count-badge"><i class="fas fa-car"></i> <?php echo $totalVehicles; ?> vehicles</span>
            </div>
        </div>

        <div class="admin-filter-bar">
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Search by brand or model..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;min-width:200px;">
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                <a href="vehicle-catalog.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($vehicles)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-car"></i>
                            <h4>No vehicles found</h4>
                            <p>No vehicle data found in products</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vehicles as $i => $v): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <div class="cell-info-name"><?php echo htmlspecialchars($v['car_brand']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($v['car_model'] ?? 'All Models'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
