<?php
$page_title = 'Shopkeeper Registration';
require_once __DIR__ . '/includes/config.php';
if ($logged_in) redirect(SITE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = sanitize($_POST['shop_name']);
    $owner_name = sanitize($_POST['owner_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $city = sanitize($_POST['city']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        setFlash('danger', 'Passwords do not match.');
    } elseif (strlen($password) < 6) {
        setFlash('danger', 'Password must be at least 6 characters.');
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            setFlash('danger', 'Email already registered.');
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'shopkeeper', 'pending')");
            $stmt->bind_param("ssss", $owner_name, $email, $phone, $hashed);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            $uploadDir = __DIR__ . '/uploads/';
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize = 5 * 1024 * 1024;

            function handleUpload($file, $prefix, $userId, $uploadDir, $allowedExts, $maxSize) {
                if ($file['error'] !== UPLOAD_ERR_OK) return '';
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts)) return '';
                if ($file['size'] > $maxSize) return '';
                $filename = $prefix . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    return $filename;
                }
                return '';
            }

            $shop_pics = [];
            if (!empty($_FILES['shop_pictures']['name'][0])) {
                foreach ($_FILES['shop_pictures']['name'] as $i => $name) {
                    $file = [
                        'name' => $_FILES['shop_pictures']['name'][$i],
                        'type' => $_FILES['shop_pictures']['type'][$i],
                        'tmp_name' => $_FILES['shop_pictures']['tmp_name'][$i],
                        'error' => $_FILES['shop_pictures']['error'][$i],
                        'size' => $_FILES['shop_pictures']['size'][$i]
                    ];
                    $pic = handleUpload($file, 'shop', $user_id, $uploadDir, $allowedExts, $maxSize);
                    if ($pic) $shop_pics[] = $pic;
                }
            }
            $shop_pic = !empty($shop_pics) ? $shop_pics[0] : '';

            $cnc_front = handleUpload($_FILES['cnc_front'], 'cnc_front', $user_id, $uploadDir, $allowedExts, $maxSize);
            $cnc_back = handleUpload($_FILES['cnc_back'], 'cnc_back', $user_id, $uploadDir, $allowedExts, $maxSize);
            $certificate = handleUpload($_FILES['certificate'], 'cert', $user_id, $uploadDir, $allowedExts, $maxSize);

            $shop_stmt = $conn->prepare("INSERT INTO shops (user_id, shop_name, location, contact, description, logo, cnc_front, cnc_back, certificate, status) VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, 'pending')");
            $shop_stmt->bind_param("isssssss", $user_id, $shop_name, $city, $phone, $shop_pic, $cnc_front, $cnc_back, $certificate);
            $shop_stmt->execute();
            $shop_stmt->close();

            if (count($shop_pics) > 1) {
                $newShopId = $conn->insert_id;
                $extra_stmt = $conn->prepare("INSERT INTO shop_images (shop_id, image_path, is_primary) VALUES (?, ?, 0)");
                foreach (array_slice($shop_pics, 1) as $pic) {
                    $extra_stmt->bind_param("is", $newShopId, $pic);
                    $extra_stmt->execute();
                }
                $extra_stmt->close();
            }

            setFlash('success', 'Shopkeeper application submitted! We will review it within 48 hours.');
            redirect(SITE_URL . '/login.php');
        }
        $check->close();
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
    <title>Shopkeeper Registration - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/responsive.css" rel="stylesheet">
    <style>
        body { background: #f8f8f8; font-family: 'Segoe UI', -apple-system, sans-serif; margin: 0; }

        .reg-header {
            background: #fff;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }
        .reg-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .reg-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .reg-brand-icon {
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
        .reg-logo-img {
            height: auto;
            width: auto;
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
            border-radius: 8px;
        }
        .reg-brand-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #111;
        }
        .reg-header-link {
            color: #666;
            font-size: 0.85rem;
            text-decoration: none;
            background: #f5f5f5;
            padding: 8px 16px;
            border-radius: 50px;
        }
        .reg-header-link:hover { color: #111; background: #eee; }

        .reg-container {
            max-width: 640px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }
        .reg-badge {
            display: inline-block;
            background: #fff0f0;
            color: #dc3545;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .reg-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        .reg-desc {
            color: #666;
            font-size: 0.92rem;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .reg-form-group { margin-bottom: 20px; }
        .reg-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        .reg-form-group input[type="text"],
        .reg-form-group input[type="email"],
        .reg-form-group input[type="tel"],
        .reg-form-group input[type="password"] {
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
        .reg-form-group input:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
        }
        .reg-form-group input::placeholder { color: #bbb; }

        .reg-upload-area {
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }
        .reg-upload-area:hover {
            border-color: #dc3545;
            background: #fff0f0;
        }
        .reg-upload-area input[type="file"] { display: none; }
        .reg-upload-icon {
            width: 48px;
            height: 48px;
            background: #fff0f0;
            color: #dc3545;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 8px;
        }
        .reg-upload-hint {
            font-size: 0.72rem;
            color: #aaa;
            margin-top: 6px;
        }
        .reg-upload-preview {
            max-width: 200px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
            display: none;
        }

        .reg-submit-btn {
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
        .reg-submit-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .reg-submit-hint {
            text-align: center;
            font-size: 0.78rem;
            color: #888;
            margin-top: 12px;
        }
        .reg-signin-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #666;
        }
        .reg-signin-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
        }
        .reg-signin-link a:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .reg-title { font-size: 1.5rem; }
            .reg-container { margin: 24px auto; }
        }
    </style>
</head>
<body>

<header class="reg-header">
    <div class="container">
        <a href="<?php echo SITE_URL; ?>" class="reg-brand">
            <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Logo" class="reg-logo-img">
            <span class="reg-brand-name">Wrench n Parts</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/login.php?role=shopkeeper" class="reg-header-link">Already registered? <strong>Sign in</strong></a>
    </div>
</header>

<div class="reg-container">
    <span class="reg-badge"><i class="fas fa-store me-1"></i> Shopkeeper Application</span>
    <h1 class="reg-title">Apply to sell</h1>
    <p class="reg-desc">Submit your shop details for review. The admin or management team will approve your account before it goes live.</p>

    <?php
    $flash = getFlash();
    if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="reg-form-group">
            <label>Shop / Business name</label>
            <input type="text" name="shop_name" placeholder="Speed Motors" required>
        </div>

        <div class="reg-form-group">
            <label>Owner name</label>
            <input type="text" name="owner_name" placeholder="Omid Ahmadi" required>
        </div>

        <div class="reg-form-group">
            <label>Business email</label>
            <input type="email" name="email" placeholder="shop@example.com" required>
        </div>

        <div class="reg-form-group">
            <label>Phone number</label>
            <input type="tel" name="phone" placeholder="+93 900 123457" required>
        </div>

        <div class="reg-form-group">
            <label>City</label>
            <input type="text" name="city" placeholder="Kabul" required>
        </div>

        <div class="reg-form-group">
            <label>Shop pictures</label>
            <p class="reg-upload-hint" style="margin-top:0;margin-bottom:8px;font-size:0.82rem;color:#666;">Upload storefront or workshop photos — you can select multiple.</p>
            <div id="shop_multi_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="shop_pictures[]" id="shop_pics_input" accept="image/jpeg,image/jpg,image/png,image/webp" multiple style="display:none">
                <div class="upload-gallery"></div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="reg-form-group">
            <label>CNIC picture (Front)</label>
            <p class="reg-upload-hint" style="margin-top:0;margin-bottom:8px;font-size:0.82rem;color:#666;">Clear photo of the front side of your CNIC — 1 image only.</p>
            <div id="cnc_front_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="cnc_front" id="cnc_front_input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none">
                <div class="upload-preview-wrap">
                    <img class="upload-preview-img" src="" alt="CNIC Front">
                    <button type="button" class="upload-remove-btn"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="reg-form-group">
            <label>CNIC picture (Back)</label>
            <p class="reg-upload-hint" style="margin-top:0;margin-bottom:8px;font-size:0.82rem;color:#666;">Clear photo of the back side of your CNIC — 1 image only.</p>
            <div id="cnc_back_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="cnc_back" id="cnc_back_input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none">
                <div class="upload-preview-wrap">
                    <img class="upload-preview-img" src="" alt="CNIC Back">
                    <button type="button" class="upload-remove-btn"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="reg-form-group">
            <label>Brand associated certificate</label>
            <p class="reg-upload-hint" style="margin-top:0;margin-bottom:8px;font-size:0.82rem;color:#666;">Upload any brand partnership or distributorship certificates you hold.</p>
            <div id="cert_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="certificate" id="cert_input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none">
                <div class="upload-preview-wrap">
                    <img class="upload-preview-img" src="" alt="Certificate">
                    <button type="button" class="upload-remove-btn"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="reg-form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required minlength="6">
        </div>

        <div class="reg-form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm your password" required>
        </div>

        <button type="submit" class="reg-submit-btn">Submit Application</button>
        <p class="reg-submit-hint">You'll get a response within 48 hours.</p>
    </form>

    <p class="reg-signin-link">Already approved? <a href="<?php echo SITE_URL; ?>/login.php?role=shopkeeper">Sign in</a></p>
</div>

<link href="<?php echo SITE_URL; ?>/css/upload.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/js/upload.js"></script>
<script>
UploadSystem.init({
    singles: [
        { wrapperId: 'cnc_front_wrapper', inputId: 'cnc_front_input' },
        { wrapperId: 'cnc_back_wrapper', inputId: 'cnc_back_input' },
        { wrapperId: 'cert_wrapper', inputId: 'cert_input' }
    ],
    multi: { wrapperId: 'shop_multi_wrapper', inputId: 'shop_pics_input' }
});
</script>
<script src="<?php echo SITE_URL; ?>/js/register-sw.js"></script>
</body>
</html>