<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$search = '%' . $conn->real_escape_string($q) . '%';
$stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.price, p.discount_price, p.product_image, p.brand, p.stock, s.shop_name, c.category_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.status = 'available' AND (p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?) ORDER BY p.created_at DESC LIMIT 6");

$stmt->bind_param("ssss", $search, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $row['price_formatted'] = formatCurrency($row['price']);
    $row['discount_formatted'] = $row['discount_price'] ? formatCurrency($row['discount_price']) : null;
    $products[] = $row;
}

$stmt->close();
echo json_encode($products);
?>
