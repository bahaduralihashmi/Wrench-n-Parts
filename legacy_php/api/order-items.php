<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo json_encode(['items' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT oi.product_id, oi.quantity, p.product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? AND EXISTS (SELECT 1 FROM orders o WHERE o.order_id = oi.order_id AND o.customer_id = ?)");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['items' => $items]);
?>
