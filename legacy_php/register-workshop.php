<?php
$page_title = 'Workshop Registration';
require_once __DIR__ . '/includes/config.php';
if ($logged_in) redirect(SITE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wname = sanitize($_POST['workshop_name']);
    $owner = sanitize($_POST['owner_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $city = sanitize($_POST['city']);
    $address = sanitize($_POST['address']);
    $services = isset($_POST['services']) ? implode(',', $_POST['services']) : '';
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
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'workshop', 'pending')");
            $stmt->bind_param("ssss", $owner, $email, $phone, $hashed);
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

            $ws_pics = [];
            if (!empty($_FILES['workshop_pictures']['name'][0])) {
                foreach ($_FILES['workshop_pictures']['name'] as $i => $name) {
                    $file = [
                        'name' => $_FILES['workshop_pictures']['name'][$i],
                        'type' => $_FILES['workshop_pictures']['type'][$i],
                        'tmp_name' => $_FILES['workshop_pictures']['tmp_name'][$i],
                        'error' => $_FILES['workshop_pictures']['error'][$i],
                        'size' => $_FILES['workshop_pictures']['size'][$i]
                    ];
                    $pic = handleUpload($file, 'ws', $user_id, $uploadDir, $allowedExts, $maxSize);
                    if ($pic) $ws_pics[] = $pic;
                }
            }
            $ws_pic = !empty($ws_pics) ? $ws_pics[0] : '';

            $cnc_front = handleUpload($_FILES['cnc_front'], 'ws_cnc_front', $user_id, $uploadDir, $allowedExts, $maxSize);
            $cnc_back = handleUpload($_FILES['cnc_back'], 'ws_cnc_back', $user_id, $uploadDir, $allowedExts, $maxSize);
            $certificate = handleUpload($_FILES['certificate'], 'ws_cert', $user_id, $uploadDir, $allowedExts, $maxSize);

            $ws_stmt = $conn->prepare("INSERT INTO workshops (user_id, workshop_name, description, location, contact, services, logo, cnc_front, cnc_back, certificate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $fullLocation = $city . ', ' . $address;
            $ws_stmt->bind_param("isssssssss", $user_id, $wname, $address, $fullLocation, $phone, $services, $ws_pic, $cnc_front, $cnc_back, $certificate);
            $ws_stmt->execute();
            $ws_stmt->close();

            if (count($ws_pics) > 1) {
                $newWorkshopId = $conn->insert_id;
                $extra_stmt = $conn->prepare("INSERT INTO workshop_images (workshop_id, image_path, is_primary) VALUES (?, ?, 0)");
                foreach (array_slice($ws_pics, 1) as $pic) {
                    $extra_stmt->bind_param("is", $newWorkshopId, $pic);
                    $extra_stmt->execute();
                }
                $extra_stmt->close();
            }

            setFlash('success', 'Workshop application submitted! We will review it within 24 hours.');
            redirect(SITE_URL . '/login.php');
        }
        $check->close();
    }
}

$suggestion_services = [
    'Engine Repair', 'Oil Change', 'AC Service', 'Brake Service',
    'Denting & Painting', 'General Service', 'Wheel Alignment',
    'Battery Replacement', 'Electrical Work', 'Transmission Repair',
    'Suspension Work', 'Tire Replacement'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#dc3545">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/uploads/logo.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Workshop Registration - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/css/responsive.css" rel="stylesheet">
    <style>
        body {
            background: #f8f8f8;
            font-family: 'Segoe UI', -apple-system, sans-serif;
        }

        .ws-reg-header {
            background: #fff;
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }

        .ws-reg-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ws-reg-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .ws-reg-brand-icon {
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
        .ws-reg-logo-img {
            height: auto;
            width: auto;
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
            border-radius: 8px;
        }

        .ws-reg-brand-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #111;
        }

        .ws-reg-header-link {
            color: #666;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .ws-reg-header-link:hover { color: #1a1a2e; }

        .ws-reg-container {
            max-width: 640px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        .ws-reg-badge {
            display: inline-block;
            background: #fff0f0;
            color: #dc3545;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .ws-reg-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 12px;
        }

        .ws-reg-desc {
            color: #666;
            font-size: 0.92rem;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .ws-form-group {
            margin-bottom: 20px;
        }

        .ws-form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .ws-form-group input[type="text"],
        .ws-form-group input[type="email"],
        .ws-form-group input[type="tel"],
        .ws-form-group input[type="password"],
        .ws-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 0.9rem;
            background: #fff;
            transition: all 0.2s;
            outline: none;
        }

        .ws-form-group input:focus,
        .ws-form-group textarea:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
        }

        .ws-form-group input::placeholder,
        .ws-form-group textarea::placeholder {
            color: #bbb;
        }

        .ws-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .ws-services-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .ws-services-hint {
            font-size: 0.78rem;
            color: #888;
            margin-bottom: 10px;
        }

        .ws-services-scroll {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 8px 12px;
            max-height: 160px;
            overflow-y: auto;
            background: #fff;
        }

        .ws-services-scroll .form-check {
            padding: 6px 0 6px 28px;
        }

        .ws-services-scroll .form-check-input:checked {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .ws-services-scroll .form-check-label {
            font-size: 0.88rem;
            color: #444;
        }

        .ws-submit-btn {
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

        .ws-submit-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .ws-submit-hint {
            text-align: center;
            font-size: 0.78rem;
            color: #888;
            margin-top: 12px;
        }

        .ws-signin-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #666;
        }

        .ws-signin-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
        }

        .ws-signin-link a:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .ws-form-row { grid-template-columns: 1fr; }
            .ws-reg-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<header class="ws-reg-header">
    <div class="container">
        <a href="<?php echo SITE_URL; ?>" class="ws-reg-brand">
            <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="Logo" class="ws-reg-logo-img">
            <span class="ws-reg-brand-name">Wrench n Parts</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/login.php?role=workshop" class="ws-reg-header-link">Already registered? <strong>Sign in</strong></a>
    </div>
</header>

<div class="ws-reg-container">
    <span class="ws-reg-badge"><i class="fas fa-tools me-1"></i> Workshop Application</span>
    <h1 class="ws-reg-title">Register your workshop</h1>
    <p class="ws-reg-desc">Submit your details to join our network. Once approved, customers will be able to find your workshop and book services. We review applications within 24 hours.</p>

    <?php
    $flash = getFlash();
    if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="ws-form-group">
            <label>Workshop / business name</label>
            <input type="text" name="workshop_name" placeholder="e.g. AutoCare Workshop" required>
        </div>

        <div class="ws-form-group">
            <label>Owner name</label>
            <input type="text" name="owner_name" placeholder="e.g. Aarav Mehta" required>
        </div>

        <div class="ws-form-group">
            <label>Business email</label>
            <input type="email" name="email" placeholder="workshop@example.com" required>
        </div>

        <div class="ws-form-row">
            <div class="ws-form-group">
                <label>Phone number</label>
                <input type="tel" name="phone" placeholder="+91 800 1234567" required>
            </div>
            <div class="ws-form-group">
                <label>City</label>
                <select name="city" required>
                    <option value="">Select city</option>
                    <option value="Lahore">Lahore</option>
                    <option value="Islamabad">Islamabad</option>
                    <option value="Karachi">Karachi</option>
                    <option value="Multan">Multan</option>
                </select>
            </div>
        </div>

        <div class="ws-form-group">
            <label>Street address</label>
            <input type="text" name="address" placeholder="Shop #, street, area" required>
        </div>

        <div class="ws-form-group">
            <label class="ws-services-label">Services offered</label>
            <p class="ws-services-hint">Select the services your workshop provides. You can update your service list later on your dashboard.</p>
            <div class="ws-services-scroll">
                <?php foreach ($suggestion_services as $svc): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="services[]" value="<?php echo $svc; ?>" id="svc_<?php echo md5($svc); ?>">
                    <label class="form-check-label" for="svc_<?php echo md5($svc); ?>"><?php echo $svc; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ws-form-group">
            <label>Workshop pictures</label>
            <p class="ws-services-hint">Upload photos of your shop front, sign board, or workspace — you can select multiple.</p>
            <div id="ws_multi_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="workshop_pictures[]" id="ws_pics_input" accept="image/jpeg,image/jpg,image/png,image/webp" multiple style="display:none">
                <div class="upload-gallery"></div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="ws-form-group">
            <label>CNIC picture (front)</label>
            <p class="ws-services-hint">Clear photo of the front side of your CNIC — 1 image only</p>
            <div id="ws_cnc_front_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="cnc_front" id="ws_cnc_front_input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none">
                <div class="upload-preview-wrap">
                    <img class="upload-preview-img" src="" alt="CNIC Front">
                    <button type="button" class="upload-remove-btn"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="ws-form-group">
            <label>CNIC picture (back)</label>
            <p class="ws-services-hint">Clear photo of the back side of your CNIC — 1 image only</p>
            <div id="ws_cnc_back_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="cnc_back" id="ws_cnc_back_input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none">
                <div class="upload-preview-wrap">
                    <img class="upload-preview-img" src="" alt="CNIC Back">
                    <button type="button" class="upload-remove-btn"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="ws-form-group">
            <label>Brand associated certificate</label>
            <p class="ws-services-hint">Upload your brand certificate to build trust with customers</p>
            <div id="ws_cert_wrapper">
                <div class="upload-area">
                    <div class="upload-add-btn">
                        <div class="upload-plus-icon"><i class="fas fa-plus"></i></div>
                        <div class="upload-label">Click to upload</div>
                        <div class="upload-sublabel">JPG, PNG or WEBP (max 5MB)</div>
                    </div>
                </div>
                <input type="file" name="certificate" id="ws_cert_input" accept="image/jpeg,image/jpg,image/png,image/webp" style="display:none">
                <div class="upload-preview-wrap">
                    <img class="upload-preview-img" src="" alt="Certificate">
                    <button type="button" class="upload-remove-btn"><i class="fas fa-times"></i></button>
                </div>
                <div class="upload-error"></div>
            </div>
        </div>

        <div class="ws-form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required minlength="6">
        </div>

        <div class="ws-form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm your password" required>
        </div>

        <button type="submit" class="ws-submit-btn">Submit Application</button>
        <p class="ws-submit-hint">You'll get a response within 24 hours.</p>
    </form>

    <p class="ws-signin-link">Already approved? <a href="<?php echo SITE_URL; ?>/login.php">Sign In</a></p>
</div>

<link href="<?php echo SITE_URL; ?>/css/upload.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>/js/upload.js"></script>
<script>
UploadSystem.init({
    singles: [
        { wrapperId: 'ws_cnc_front_wrapper', inputId: 'ws_cnc_front_input' },
        { wrapperId: 'ws_cnc_back_wrapper', inputId: 'ws_cnc_back_input' },
        { wrapperId: 'ws_cert_wrapper', inputId: 'ws_cert_input' }
    ],
    multi: { wrapperId: 'ws_multi_wrapper', inputId: 'ws_pics_input' }
});
</script>
<script src="<?php echo SITE_URL; ?>/js/register-sw.js"></script>
</body>
</html>
