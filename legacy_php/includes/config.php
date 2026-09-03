<?php
date_default_timezone_set('Asia/Karachi');
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wrench_parts_db');
define('SITE_NAME', 'Wrench n Parts');
define('SITE_URL', 'http://localhost/Wrench_n_Parts');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+05:00'");

$maintenance_check = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
if ($maintenance_check && $maintenance_check->num_rows > 0) {
    $is_maintenance = $maintenance_check->fetch_assoc()['setting_value'];
    $is_admin_page = isset($_SERVER['PHP_SELF']) && (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/management/') !== false);
    $is_login = isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === 'login.php';
    if ($is_maintenance == '1' && !$is_admin_page && !$is_login) {
        if (isset($_SESSION['user_id'])) {
            $check_role = $conn->query("SELECT role FROM users WHERE user_id = " . intval($_SESSION['user_id']));
            if ($check_role && $check_role->num_rows > 0) {
                $role = $check_role->fetch_assoc()['role'];
                if (!in_array($role, ['admin', 'management'])) {
                    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Maintenance Mode - ' . SITE_NAME . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Inter,-apple-system,sans-serif;background:#f5f7fb;min-height:100vh;display:flex;align-items:center;justify-content:center}.maint-card{background:#fff;border-radius:20px;padding:50px 40px;max-width:480px;width:90%;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,0.08)}.maint-icon{width:90px;height:90px;background:linear-gradient(135deg,#ffc107,#ff9800);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.2rem;color:#fff}.maint-card h1{font-size:1.5rem;color:#1a1a2e;margin-bottom:12px}.maint-card p{color:#666;font-size:0.95rem;line-height:1.6;margin-bottom:24px}.maint-btn{display:inline-block;padding:12px 32px;background:#dc3545;color:#fff;border:none;border-radius:50px;font-size:0.9rem;font-weight:600;cursor:pointer;text-decoration:none}.maint-btn:hover{background:#c82333}</style></head><body><div class="maint-card"><div class="maint-icon"><i class="fas fa-wrench"></i></div><h1>Under Maintenance</h1><p>We are currently performing scheduled maintenance. Please check back later.</p><a href="' . SITE_URL . '/login.php" class="maint-btn"><i class="fas fa-arrow-left"></i> Back to Login</a></div></body></html>';
                    exit;
                }
            }
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Maintenance Mode - ' . SITE_NAME . '</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Inter,-apple-system,sans-serif;background:#f5f7fb;min-height:100vh;display:flex;align-items:center;justify-content:center}.maint-card{background:#fff;border-radius:20px;padding:50px 40px;max-width:480px;width:90%;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,0.08)}.maint-icon{width:90px;height:90px;background:linear-gradient(135deg,#ffc107,#ff9800);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.2rem;color:#fff}.maint-card h1{font-size:1.5rem;color:#1a1a2e;margin-bottom:12px}.maint-card p{color:#666;font-size:0.95rem;line-height:1.6;margin-bottom:24px}.maint-btn{display:inline-block;padding:12px 32px;background:#dc3545;color:#fff;border:none;border-radius:50px;font-size:0.9rem;font-weight:600;cursor:pointer;text-decoration:none}.maint-btn:hover{background:#c82333}</style></head><body><div class="maint-card"><div class="maint-icon"><i class="fas fa-wrench"></i></div><h1>Under Maintenance</h1><p>We are currently performing scheduled maintenance. Please check back later.</p><a href="' . SITE_URL . '/login.php" class="maint-btn"><i class="fas fa-arrow-left"></i> Back to Login</a></div></body></html>';
            exit;
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    $logged_in = false;
    $current_user = null;
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    $stmt->close();
    if (!$current_user) {
        session_destroy();
        $logged_in = false;
        header("Location: " . SITE_URL . "/login.php");
        exit;
    }
    $blockedStatuses = ['banned', 'pending', 'rejected'];
    if (in_array($current_user['status'], $blockedStatuses)) {
        $blockedMsg = $current_user['status'];
        session_destroy();
        $logged_in = false;
        $current_user = null;
        if ($blockedMsg === 'pending') {
            setFlash('warning', 'Your account is waiting for admin approval.');
        } elseif ($blockedMsg === 'rejected') {
            setFlash('danger', 'Your account has been rejected by the administrator.');
        } else {
            setFlash('danger', 'Your account has been banned. Contact admin.');
        }
        header("Location: " . SITE_URL . "/login.php");
        exit;
    }
    $logged_in = true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/login.php");
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    global $current_user;
    if ($current_user['role'] !== $role) {
        header("Location: " . SITE_URL . "/login.php");
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sanitizeSQL($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        setFlash('danger', 'Invalid security token. Please try again.');
        redirect($_SERVER['REQUEST_URI'] ?? SITE_URL);
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getCartCount() {
    if (!isLoggedIn()) return 0;
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

function getNotificationCount() {
    if (!isLoggedIn()) return 0;
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

function getUserWishlistIds() {
    if (!isLoggedIn()) return [];
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $r = $stmt->get_result();
    $ids = [];
    while ($row = $r->fetch_assoc()) $ids[] = (int)$row['product_id'];
    $stmt->close();
    return $ids;
}

function getWishlistCountForUser() {
    if (!isLoggedIn()) return 0;
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
    return $cnt;
}

function getSystemSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ? $result['setting_value'] : $default;
}

function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 0);
}

function timeAgo($datetime) {
    if (empty($datetime)) return 'N/A';
    $tz = new DateTimeZone('Asia/Karachi');
    $now = new DateTime('now', $tz);
    try {
        $ago = new DateTime($datetime, $tz);
    } catch (Exception $e) {
        return $datetime;
    }
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) {
        if ($diff->d === 1) return 'Yesterday';
        if ($diff->d < 7) return $diff->d . ' days ago';
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>
