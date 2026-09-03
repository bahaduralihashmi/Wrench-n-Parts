<?php
require_once __DIR__ . '/../includes/config.php';
$deals = $conn->query("SELECT COUNT(*) as total FROM hot_deals WHERE status = 'active' AND CURDATE() >= start_date AND CURDATE() <= end_date");
$count = $deals ? $deals->fetch_assoc()['total'] : 0;
header('Content-Type: application/json');
echo json_encode(['count' => intval($count)]);
