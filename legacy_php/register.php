<?php
$page_title = 'Register';
require_once __DIR__ . '/includes/config.php';
if ($logged_in) redirect(SITE_URL);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
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
            $validRoles = ['customer', 'shopkeeper', 'workshop'];
            if (!in_array($role, $validRoles)) $role = 'customer';
            $status = ($role === 'shopkeeper' || $role === 'workshop') ? 'pending' : 'active';
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $phone, $hashed, $role, $status);
            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;
                $stmt->close();

                if ($role === 'customer') {
                    $_SESSION['user_id'] = $new_user_id;
                    setFlash('success', 'Registration successful! Welcome, ' . $name . '!');
                    redirect(SITE_URL . '/customer/dashboard.php');
                } else {
                    setFlash('success', 'Registration successful! Please login.');
                    redirect(SITE_URL . '/login.php');
                }
            } else {
                setFlash('danger', 'Registration failed. Try again.');
            }
        }
        $check->close();
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-brand">
                        <div class="brand-icon-lg">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <h3>Create Account</h3>
                        <p>Join <?php echo SITE_NAME; ?> today</p>
                    </div>
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required placeholder="Enter your email">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" required placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label>I am a</label>
                            <select name="role" class="form-control" required>
                                <option value="customer">Customer</option>
                                <option value="shopkeeper">Shopkeeper / Parts Seller</option>
                                <option value="workshop">Workshop Owner</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min 6 characters">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                        </div>
                        <button type="submit" class="btn-modern btn-primary-modern w-100 mt-3">Register</button>
                    </form>
                    <div class="auth-footer">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;';
    document.body.appendChild(canvas);
    var ctx = canvas.getContext('2d');
    var w, h, dots = [];
    function resize() { w = canvas.width = window.innerWidth; h = canvas.height = window.innerHeight; }
    window.addEventListener('resize', resize);
    resize();
    for (var i = 0; i < 60; i++) {
        dots.push({
            x: Math.random() * w,
            y: Math.random() * h,
            r: Math.random() * 2 + 1,
            dx: (Math.random() - 0.5) * 0.5,
            dy: (Math.random() - 0.5) * 0.5,
            o: Math.random() * 0.4 + 0.1
        });
    }
    function draw() {
        ctx.clearRect(0, 0, w, h);
        for (var i = 0; i < dots.length; i++) {
            var d = dots[i];
            ctx.beginPath();
            ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(220, 53, 69, ' + d.o + ')';
            ctx.fill();
            d.x += d.dx;
            d.y += d.dy;
            if (d.x < 0 || d.x > w) d.dx *= -1;
            if (d.y < 0 || d.y > h) d.dy *= -1;
        }
        requestAnimationFrame(draw);
    }
    draw();
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
