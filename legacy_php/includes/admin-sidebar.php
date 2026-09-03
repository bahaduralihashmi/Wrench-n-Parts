<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<button class="admin-sidebar-toggle" id="adminSidebarToggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('show');document.getElementById('adminOverlay').classList.toggle('active')">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="adminOverlay" onclick="document.querySelector('.admin-sidebar').classList.remove('show');this.classList.remove('active')"></div>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <div class="brand-icon">W</div>
        <span class="brand-text">Wrench <span class="text-red">n</span> Parts</span>
    </div>

    <div class="admin-sidebar-label">Administration</div>

    <nav class="admin-sidebar-nav">
        <a class="admin-nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-th-large"></i> Overview
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'shops.php' ? 'active' : ''; ?>" href="shops.php">
            <i class="fas fa-store"></i> Shop Approvals
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'workshops.php' ? 'active' : ''; ?>" href="workshops.php">
            <i class="fas fa-wrench"></i> Workshop Approvals
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'management-team.php' ? 'active' : ''; ?>" href="management-team.php">
            <i class="fas fa-users-cog"></i> Management Team
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="users.php">
            <i class="fas fa-users"></i> All Users
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>" href="products.php">
            <i class="fas fa-box"></i> Products
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'hot-deals.php' ? 'active' : ''; ?>" href="hot-deals.php">
            <i class="fas fa-fire"></i> Hot Deals
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'shop-profits.php' ? 'active' : ''; ?>" href="shop-profits.php">
            <i class="fas fa-chart-line"></i> Shop Profits
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'vehicle-catalog.php' ? 'active' : ''; ?>" href="vehicle-catalog.php">
            <i class="fas fa-car"></i> Vehicle Catalog
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="admin-nav-link logout-link" href="<?php echo SITE_URL; ?>/logout.php" style="color:rgba(255,107,129,0.7)!important;border-top:1px solid rgba(255,255,255,0.06);margin-top:6px;padding-top:16px;">
            <i class="fas fa-sign-out-alt"></i> Log out
        </a>
    </nav>
</parameter>

</aside>
