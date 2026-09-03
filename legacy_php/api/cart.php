<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn() || $current_user['role'] !== 'customer') {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$uid = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function getCartTotals($conn, $uid) {
    $stmt = $conn->prepare("SELECT c.cart_id, c.quantity, c.product_id, p.price, p.discount_price, p.stock, p.product_name, p.product_image, s.shop_name FROM cart c JOIN products p ON c.product_id = p.product_id LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE c.user_id = ? ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $items = [];
    $subtotal = 0;
    while ($row = $result->fetch_assoc()) {
        $usePrice = ($row['discount_price'] && $row['discount_price'] > 0 && $row['discount_price'] < $row['price']) ? $row['discount_price'] : $row['price'];
        $itemTotal = $usePrice * $row['quantity'];
        $subtotal += $itemTotal;
        $items[] = [
            'cart_id' => (int)$row['cart_id'],
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['product_name'],
            'product_image' => $row['product_image'],
            'shop_name' => $row['shop_name'],
            'price' => (float)$row['price'],
            'discount_price' => (float)$row['discount_price'],
            'use_price' => (float)$usePrice,
            'quantity' => (int)$row['quantity'],
            'stock' => (int)$row['stock'],
            'item_total' => (float)$itemTotal,
        ];
    }

    $taxRate = floatval(getSystemSetting('tax_rate', '8.5')) / 100;
    $shippingFee = floatval(getSystemSetting('shipping_fee', '5.99'));
    $tax = $subtotal * $taxRate;
    $shipping = $subtotal > 0 ? $shippingFee : 0;
    $total = $subtotal + $tax + $shipping;
    $cartCount = count($items);

    return [
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'tax_rate' => $taxRate * 100,
        'tax' => round($tax, 2),
        'shipping' => round($shipping, 2),
        'total' => round($total, 2),
        'cart_count' => $cartCount,
    ];
}

if ($action === 'add') {
    $productId = intval($_POST['product_id'] ?? 0);
    if (!$productId) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid product']);
        exit;
    }

    $check = $conn->prepare("SELECT product_id, stock FROM products WHERE product_id = ? AND status = 'available'");
    $check->bind_param("i", $productId);
    $check->execute();
    $prod = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$prod) {
        echo json_encode(['ok' => false, 'msg' => 'Product not found']);
        exit;
    }
    if ($prod['stock'] < 1) {
        echo json_encode(['ok' => false, 'msg' => 'Out of stock']);
        exit;
    }

    $cartRow = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $cartRow->bind_param("ii", $uid, $productId);
    $cartRow->execute();
    $existing = $cartRow->get_result()->fetch_assoc();
    $cartRow->close();

    if ($existing) {
        $newQty = $existing['quantity'] + 1;
        if ($newQty > $prod['stock']) $newQty = $prod['stock'];
        $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
        $upd->bind_param("iii", $newQty, $existing['cart_id'], $uid);
        $upd->execute();
        $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $ins->bind_param("ii", $uid, $productId);
        $ins->execute();
        $ins->close();
    }

    $totals = getCartTotals($conn, $uid);
    echo json_encode(['ok' => true, 'msg' => 'Added to cart', 'cart_count' => $totals['cart_count']]);
    exit;
}

if ($action === 'update_qty') {
    $cartId = intval($_POST['cart_id'] ?? 0);
    $qty = intval($_POST['quantity'] ?? 1);

    if ($qty < 1) $qty = 1;

    $check = $conn->prepare("SELECT cart_id, product_id FROM cart WHERE cart_id = ? AND user_id = ?");
    $check->bind_param("ii", $cartId, $uid);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$row) {
        echo json_encode(['ok' => false, 'msg' => 'Cart item not found']);
        exit;
    }

    $pid = $row['product_id'];
    $stockCheck = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
    $stockCheck->bind_param("i", $pid);
    $stockCheck->execute();
    $stockRow = $stockCheck->get_result()->fetch_assoc();
    $stockCheck->close();

    if ($qty > $stockRow['stock']) {
        $qty = $stockRow['stock'];
    }

    $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
    $upd->bind_param("iii", $qty, $cartId, $uid);
    $upd->execute();
    $upd->close();

    $totals = getCartTotals($conn, $uid);
    $updatedItem = null;
    foreach ($totals['items'] as $item) {
        if ($item['cart_id'] === $cartId) {
            $updatedItem = $item;
            break;
        }
    }

    echo json_encode([
        'ok' => true,
        'updated_item' => $updatedItem,
        'totals' => $totals,
    ]);
    exit;
}

if ($action === 'remove') {
    $cartId = intval($_POST['cart_id'] ?? 0);

    $del = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $del->bind_param("ii", $cartId, $uid);
    $del->execute();
    $del->close();

    $totals = getCartTotals($conn, $uid);

    echo json_encode([
        'ok' => true,
        'removed_cart_id' => $cartId,
        'totals' => $totals,
    ]);
    exit;
}

if ($action === 'get_totals') {
    $totals = getCartTotals($conn, $uid);
    echo json_encode(['ok' => true, 'totals' => $totals]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Invalid action']);
