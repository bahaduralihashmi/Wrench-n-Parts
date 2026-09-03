<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if (!$logged_in) {
    echo json_encode(['ok' => false, 'msg' => 'login_required']);
    exit;
}

$user_id = $current_user['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if (!$product_id) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid product']);
    exit;
}

$stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE wishlist_id = ?");
    $stmt->bind_param("i", $existing['wishlist_id']);
    $stmt->execute();
    $stmt->close();
    $count = getWishlistCount($user_id);
    echo json_encode(['ok' => true, 'action' => 'removed', 'count' => $count]);
} else {
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
    $count = getWishlistCount($user_id);
    echo json_encode(['ok' => true, 'action' => 'added', 'count' => $count]);
}

function getWishlistCount($uid) {
    global $conn;
    $uid = intval($uid);
    $r = $conn->query("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id = $uid");
    return $r->fetch_assoc()['cnt'];
}
