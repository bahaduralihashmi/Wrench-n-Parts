<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="dash-sidebar">
    <div class="dash-sidebar-brand">
        <div class="dash-brand-icon">WS</div>
        <div>
            <div class="dash-brand-text">Workshop Panel</div>
            <small style="color:#888;font-size:0.75rem;"><?php echo htmlspecialchars($workshop['workshop_name'] ?? 'My Workshop'); ?></small>
        </div>
    </div>
    <div class="dash-sidebar-label">Menu</div>
    <nav class="dash-nav">
        <a class="dash-nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/workshop/dashboard.php">
            <i class="fas fa-tachometer-alt"></i>Dashboard
        </a>
        <a class="dash-nav-link <?php echo $currentPage === 'appointments' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/workshop/appointments.php">
            <i class="fas fa-calendar-check"></i>Appointments
        </a>
        <a class="dash-nav-link <?php echo $currentPage === 'services' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/workshop/services.php">
            <i class="fas fa-cogs"></i>Services
        </a>
        <a class="dash-nav-link <?php echo $currentPage === 'reviews' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/workshop/reviews.php">
            <i class="fas fa-star"></i>Reviews
        </a>
        <a class="dash-nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/workshop/profile.php">
            <i class="fas fa-user-edit"></i>Profile
        </a>
    </nav>
    <div class="dash-sidebar-footer">
        <a class="dash-nav-link logout" href="<?php echo SITE_URL; ?>/index.php">
            <i class="fas fa-arrow-left"></i>Back to Site
        </a>
    </div>
</div>
