<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<button class="admin-sidebar-toggle" id="sidebarToggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('show'); document.getElementById('sidebarOverlay').classList.toggle('active');">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="sidebarOverlay" onclick="document.querySelector('.admin-sidebar').classList.remove('show'); this.classList.remove('active');"></div>
<div class="admin-sidebar" style="background:linear-gradient(180deg,#0f0c29 0%,#302b63 50%,#24243e 100%);border-right:none;">
    <div class="admin-sidebar-brand" style="padding:24px 20px;margin-bottom:8px;">
        <div class="brand-icon" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;color:#fff;box-shadow:0 4px 15px rgba(102,126,234,.4);">WN</div>
        <span class="brand-text" style="color:#fff;font-weight:700;font-size:1rem;">Management</span>
    </div>

    <div style="padding:0 16px;margin-bottom:6px;">
        <span style="font-size:.65rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.3);padding-left:8px;">Main Menu</span>
    </div>

    <nav class="admin-sidebar-nav" style="padding:0 12px;">
        <a class="admin-nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php" style="color:<?php echo $currentPage === 'dashboard.php' ? '#fff' : 'rgba(255,255,255,.55)'; ?>;padding:12px 16px;border-radius:12px;margin-bottom:4px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.9rem;text-decoration:none;transition:all .25s ease;background:<?php echo $currentPage === 'dashboard.php' ? 'linear-gradient(135deg,rgba(102,126,234,.3),rgba(118,75,162,.2))' : 'transparent'; ?>;<?php echo $currentPage === 'dashboard.php' ? 'box-shadow:0 2px 12px rgba(102,126,234,.15);' : ''; ?>" onmouseover="if(this.className.indexOf('active')===-1){this.style.background='rgba(255,255,255,.06)';this.style.color='#fff'}" onmouseout="if(this.className.indexOf('active')===-1){this.style.background='transparent';this.style.color='rgba(255,255,255,.55)'}">
            <i class="fas fa-tachometer-alt" style="width:20px;text-align:center;font-size:.95rem;"></i>Dashboard
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>" href="reports.php" style="color:<?php echo $currentPage === 'reports.php' ? '#fff' : 'rgba(255,255,255,.55)'; ?>;padding:12px 16px;border-radius:12px;margin-bottom:4px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.9rem;text-decoration:none;transition:all .25s ease;background:<?php echo $currentPage === 'reports.php' ? 'linear-gradient(135deg,rgba(102,126,234,.3),rgba(118,75,162,.2))' : 'transparent'; ?>;" onmouseover="if(this.className.indexOf('active')===-1){this.style.background='rgba(255,255,255,.06)';this.style.color='#fff'}" onmouseout="if(this.className.indexOf('active')===-1){this.style.background='transparent';this.style.color='rgba(255,255,255,.55)'}">
            <i class="fas fa-chart-line" style="width:20px;text-align:center;font-size:.95rem;"></i>Reports
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>" href="analytics.php" style="color:<?php echo $currentPage === 'analytics.php' ? '#fff' : 'rgba(255,255,255,.55)'; ?>;padding:12px 16px;border-radius:12px;margin-bottom:4px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.9rem;text-decoration:none;transition:all .25s ease;background:<?php echo $currentPage === 'analytics.php' ? 'linear-gradient(135deg,rgba(102,126,234,.3),rgba(118,75,162,.2))' : 'transparent'; ?>;" onmouseover="if(this.className.indexOf('active')===-1){this.style.background='rgba(255,255,255,.06)';this.style.color='#fff'}" onmouseout="if(this.className.indexOf('active')===-1){this.style.background='transparent';this.style.color='rgba(255,255,255,.55)'}">
            <i class="fas fa-chart-pie" style="width:20px;text-align:center;font-size:.95rem;"></i>Analytics
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'chatbot-config.php' ? 'active' : ''; ?>" href="chatbot-config.php" style="color:<?php echo $currentPage === 'chatbot-config.php' ? '#fff' : 'rgba(255,255,255,.55)'; ?>;padding:12px 16px;border-radius:12px;margin-bottom:4px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.9rem;text-decoration:none;transition:all .25s ease;background:<?php echo $currentPage === 'chatbot-config.php' ? 'linear-gradient(135deg,rgba(102,126,234,.3),rgba(118,75,162,.2))' : 'transparent'; ?>;" onmouseover="if(this.className.indexOf('active')===-1){this.style.background='rgba(255,255,255,.06)';this.style.color='#fff'}" onmouseout="if(this.className.indexOf('active')===-1){this.style.background='transparent';this.style.color='rgba(255,255,255,.55)'}">
            <i class="fas fa-robot" style="width:20px;text-align:center;font-size:.95rem;"></i>Chatbot Config
        </a>
        <a class="admin-nav-link <?php echo $currentPage === 'knowledge-base.php' ? 'active' : ''; ?>" href="knowledge-base.php" style="color:<?php echo $currentPage === 'knowledge-base.php' ? '#fff' : 'rgba(255,255,255,.55)'; ?>;padding:12px 16px;border-radius:12px;margin-bottom:4px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.9rem;text-decoration:none;transition:all .25s ease;background:<?php echo $currentPage === 'knowledge-base.php' ? 'linear-gradient(135deg,rgba(102,126,234,.3),rgba(118,75,162,.2))' : 'transparent'; ?>;" onmouseover="if(this.className.indexOf('active')===-1){this.style.background='rgba(255,255,255,.06)';this.style.color='#fff'}" onmouseout="if(this.className.indexOf('active')===-1){this.style.background='transparent';this.style.color='rgba(255,255,255,.55)'}">
            <i class="fas fa-book" style="width:20px;text-align:center;font-size:.95rem;"></i>Knowledge Base
        </a>
    </nav>

    <div style="padding:0 16px;margin:16px 0 6px;">
        <span style="font-size:.65rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.3);padding-left:8px;">Account</span>
    </div>

    <nav class="admin-sidebar-nav" style="padding:0 12px;">
        <a class="admin-nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>" href="profile.php" style="color:<?php echo $currentPage === 'profile.php' ? '#fff' : 'rgba(255,255,255,.55)'; ?>;padding:12px 16px;border-radius:12px;margin-bottom:4px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.9rem;text-decoration:none;transition:all .25s ease;background:<?php echo $currentPage === 'profile.php' ? 'linear-gradient(135deg,rgba(102,126,234,.3),rgba(118,75,162,.2))' : 'transparent'; ?>;" onmouseover="if(this.className.indexOf('active')===-1){this.style.background='rgba(255,255,255,.06)';this.style.color='#fff'}" onmouseout="if(this.className.indexOf('active')===-1){this.style.background='transparent';this.style.color='rgba(255,255,255,.55)'}">
            <i class="fas fa-user-circle" style="width:20px;text-align:center;font-size:.95rem;"></i>Profile
        </a>
    </nav>

    <div class="admin-sidebar-footer" style="padding:16px 12px;margin-top:auto;">
        <a class="admin-nav-link" href="<?php echo SITE_URL; ?>/index.php" style="color:rgba(255,255,255,.4);padding:12px 16px;border-radius:12px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:.85rem;text-decoration:none;transition:all .25s ease;border:1px solid rgba(255,255,255,.08);" onmouseover="this.style.background='rgba(255,255,255,.06)';this.style.color='#fff';this.style.borderColor='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,.4)';this.style.borderColor='rgba(255,255,255,.08)'">
            <i class="fas fa-arrow-left" style="width:20px;text-align:center;"></i>Back to Site
        </a>
    </div>
</div>
