<?php
$page_title = 'Login';
require_once __DIR__ . '/includes/config.php';
if ($logged_in) redirect(SITE_URL);

$role_param = isset($_GET['role']) ? $_GET['role'] : '';
$role_labels = [
    'customer' => 'Customer',
    'shopkeeper' => 'Shopkeeper',
    'workshop' => 'Workshop',
];
$role_label = isset($role_labels[$role_param]) ? $role_labels[$role_param] : '';

$reg_links = [
    'customer' => SITE_URL . '/register.php',
    'shopkeeper' => SITE_URL . '/register-shopkeeper.php',
    'workshop' => SITE_URL . '/register-workshop.php',
];
$reg_link = isset($reg_links[$role_param]) ? $reg_links[$role_param] : SITE_URL . '/register.php';
$reg_text = $role_param === 'shopkeeper' ? 'Apply to sell' : ($role_param === 'workshop' ? 'Register your workshop' : 'Create an account');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'banned') {
            setFlash('danger', 'Your account has been banned. Contact admin.');
        } elseif ($user['status'] === 'pending' || empty($user['status'])) {
            setFlash('warning', 'Your account is waiting for admin approval. Please try again later.');
        } elseif ($user['status'] === 'rejected') {
            setFlash('danger', 'Your account has been rejected by the administrator.');
        } elseif ($user['status'] === 'active' || $user['status'] === 'approved') {
            $_SESSION['user_id'] = $user['user_id'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            $dashMap = [
                'customer' => 'customer/dashboard.php',
                'shopkeeper' => 'shopkeeper/dashboard.php',
                'workshop' => 'workshop/dashboard.php',
                'admin' => 'admin/dashboard.php',
                'management' => 'management/dashboard.php'
            ];
            redirect(SITE_URL . '/' . ($dashMap[$user['role']] ?? 'index.php'));
        } else {
            setFlash('danger', 'Your account status is unknown. Contact admin.');
        }
    } else {
        setFlash('danger', 'Invalid email or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
    </script>
    <meta name="theme-color" content="#dc3545">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/uploads/logo.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo $role_label ? $role_label . ' Login' : 'Login'; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/responsive.css" rel="stylesheet">
    <style>
        body { background: #f8f8f8; font-family: 'Segoe UI', -apple-system, sans-serif; margin: 0; }

        .login-header {
            background: #fff;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }
        .login-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .login-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .login-brand-icon {
            width: 32px;
            height: 32px;
            background: #dc3545;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 900;
        }
        .login-logo-img {
            height: auto;
            width: auto;
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
            border-radius: 8px;
        }
        .login-brand-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #111;
        }

        .login-container {
            max-width: 440px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }
        .login-badge {
            display: inline-block;
            background: #fff0f0;
            color: #dc3545;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .login-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        .login-desc {
            color: #666;
            font-size: 0.92rem;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .login-form-group { margin-bottom: 20px; }
        .login-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        .login-form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 0.9rem;
            background: #fff;
            transition: all 0.2s;
            outline: none;
            box-sizing: border-box;
        }
        .login-form-group input:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
        }
        .login-form-group input::placeholder { color: #bbb; }

        .login-submit-btn {
            width: 100%;
            padding: 14px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .login-submit-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .login-register-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.85rem;
            color: #666;
        }
        .login-register-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
        }
        .login-register-link a:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .login-title { font-size: 1.5rem; }
            .login-container { margin: 24px auto; }
            .login-header .container { gap: 8px; }
            .login-brand-name { display: none; }
            .login-logo-img { max-height: 36px; max-width: 100px; }
            .login-header-right { gap: 6px !important; }
            .login-header-right a { font-size: 0 !important; gap: 0 !important; }
            .login-header-right a i { font-size: 0.85rem !important; margin: 0 !important; }
            .login-header-right .role-btn-text { display: none; }
            #roleBtn { padding: 7px 12px !important; font-size: 0.8rem !important; }
            #roleBtn span { display: none; }
        }
        @media (max-width: 400px) {
            .login-header-right > a:first-child { display: none; }
        }
    </style>
</head>
<body>

<header class="login-header">
    <div class="container">
        <a href="<?php echo SITE_URL; ?>/index.php" class="login-brand">
            <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Logo" class="login-logo-img">
            <span class="login-brand-name">Wrench <span style="color:#dc3545;font-weight:800;">n</span> Parts</span>
        </a>
        <div class="login-header-right" style="display:flex;align-items:center;gap:16px;">
            <button onclick="toggleTheme()" style="background:#f0f0f0;border:none;width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;transition:background .3s;" title="Toggle theme">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <a href="<?php echo SITE_URL; ?>/index.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:flex;align-items:center;gap:4px;">
                <i class="fas fa-arrow-left"></i> Back to home
            </a>
            <div style="position:relative;" id="roleDropdownWrap">
                <button onclick="var dd=document.getElementById('roleDropdown'); dd.style.display=dd.style.display==='block'?'none':'block';" id="roleBtn" style="padding:8px 20px;border:1.5px solid #dc3545;color:#dc3545;background:#fff;border-radius:8px;font-size:0.88rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-user"></i> <span>Choose Role</span>
                    <i class="fas fa-chevron-down" style="font-size:0.65rem;margin-left:2px;"></i>
                </button>
                <div id="roleDropdown" style="position:absolute;top:calc(100% + 6px);right:0;background:#fff;border:1px solid #e0e0e0;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,0.12);z-index:9999;min-width:200px;display:none;overflow:hidden;">
                    <a href="<?php echo SITE_URL; ?>/login.php?role=customer" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#333;font-size:0.88rem;">
                        <i class="fas fa-user" style="width:18px;text-align:center;color:#3498db;"></i> Customer Login
                    </a>
                    <a href="<?php echo SITE_URL; ?>/login.php?role=shopkeeper" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#333;font-size:0.88rem;">
                        <i class="fas fa-store" style="width:18px;text-align:center;color:#e67e22;"></i> Apply to Sell
                    </a>
                    <a href="<?php echo SITE_URL; ?>/login.php?role=workshop" style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;color:#333;font-size:0.88rem;">
                        <i class="fas fa-tools" style="width:18px;text-align:center;color:#27ae60;"></i> Register Workshop
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleTheme() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeIcon(next);
}
function updateThemeIcon(theme) {
    var icon = document.getElementById('themeIcon');
    if (icon) icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}
updateThemeIcon(localStorage.getItem('theme') || 'light');
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('roleDropdownWrap');
    var dd = document.getElementById('roleDropdown');
    if (wrap && dd && !wrap.contains(e.target)) dd.style.display = 'none';
});
</script>

<div class="login-container">
    <?php if ($role_param === 'shopkeeper'): ?>
        <span class="login-badge"><i class="fas fa-store me-1"></i> Shopkeeper Login</span>
        <h1 class="login-title">Welcome back, seller</h1>
        <p class="login-desc">Sign in to manage your shop, products, and orders.</p>
    <?php elseif ($role_param === 'workshop'): ?>
        <span class="login-badge"><i class="fas fa-tools me-1"></i> Workshop Login</span>
        <h1 class="login-title">Welcome back</h1>
        <p class="login-desc">Sign in to manage your workshop, appointments, and services.</p>
    <?php else: ?>
        <span class="login-badge"><i class="fas fa-user me-1"></i> Customer Login</span>
        <h1 class="login-title">Welcome back</h1>
        <p class="login-desc">Sign in to track orders, manage wishlist, and book services.</p>
    <?php endif; ?>

    <?php
    $flash = getFlash();
    if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="login-form-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="login-form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="login-submit-btn">Login</button>
    </form>

    <div class="login-register-link">
        Don't have an account? <a href="<?php echo $reg_link; ?>"><?php echo $reg_text; ?></a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/js/register-sw.js"></script>
</body>
</html>