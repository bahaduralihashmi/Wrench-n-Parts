/* Shared header/footer/sidebar injection for static HTML pages. */
(function () {
  function depth() {
    // Count ../ needed to reach /frontend root from current page
    const path = location.pathname.replace(/\\/g, "/");
    const parts = path.split("/").filter(Boolean);
    // parts might include 'frontend' explicitly; we just count folders after the last '.html'
    const idx = parts.findIndex((p) => /\.html?$/.test(p));
    if (idx < 0) return "";
    let up = "";
    for (let i = idx + 1; i < parts.length; i++) up += "../";
    return up;
  }

  function rel(p) { return depth() + p; }

  function renderNavbar() {
    return `
<nav class="navbar-v2">
  <div class="container nav-container-v2">
    <a class="nav-logo-v2" href="${rel("index.html")}">
      <img src="${rel("uploads/logo.png")}" alt="Wrench n Parts" class="logo-img">
      <span class="logo-text">Wrench <span style="color:#dc3545;font-weight:800">n</span> Parts</span>
    </a>
    <div class="nav-search-v2">
      <form onsubmit="return WNP.doSearch(event)">
        <i class="fas fa-search"></i>
        <input type="search" id="navSearch" placeholder="Search parts, brands, vehicles...">
      </form>
    </div>
    <div class="nav-actions-v2">
      <a href="${rel("workshop-finder.html")}" class="nav-link-v2"><i class="fas fa-tools"></i> Workshops</a>
      <a href="${rel("wishlist.html")}" class="nav-link-v2"><i class="fas fa-heart"></i></a>
      <a href="${rel("cart.html")}" class="nav-link-v2 nav-cart">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge" data-cart-count style="display:none">0</span>
      </a>
      <div class="nav-user-v2" id="navUserSlot"></div>
    </div>
  </div>
</nav>
<div class="nav-mobile-menu" id="navMobileMenu"></div>
<div id="navCategoryBar"></div>`;
  }

  function renderFooter() {
    return `
<footer class="site-footer-v2">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <a class="nav-logo-v2 mb-3" href="${rel("index.html")}">
          <img src="${rel("uploads/logo.png")}" alt="Wrench n Parts">
          <span class="logo-text">Wrench <span style="color:#dc3545;font-weight:800">n</span> Parts</span>
        </a>
        <p class="text-muted">Your trusted marketplace for genuine auto spare parts and verified workshops.</p>
      </div>
      <div class="col-lg-2 col-md-6">
        <h6 class="footer-heading">Shop</h6>
        <a href="${rel("products.html")}" class="footer-link">All Parts</a>
        <a href="${rel("products.html?hot=1")}" class="footer-link">Hot Deals</a>
        <a href="${rel("workshop-finder.html")}" class="footer-link">Workshops</a>
      </div>
      <div class="col-lg-2 col-md-6">
        <h6 class="footer-heading">Account</h6>
        <a href="${rel("login.html")}" class="footer-link">Login</a>
        <a href="${rel("register.html")}" class="footer-link">Register</a>
        <a href="${rel("register-shopkeeper.html")}" class="footer-link">Sell Parts</a>
        <a href="${rel("register-workshop.html")}" class="footer-link">Register Workshop</a>
      </div>
      <div class="col-lg-4 col-md-6">
        <h6 class="footer-heading">Contact</h6>
        <p class="text-muted mb-1"><i class="fas fa-envelope me-2"></i>info@wrenchnparts.com</p>
        <p class="text-muted mb-1"><i class="fas fa-phone me-2"></i>+1-800-WRENCH</p>
        <p class="text-muted mb-3"><i class="fas fa-map-marker-alt me-2"></i>123 Auto Street, Mechanical District</p>
        <div class="footer-social">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
    </div>
    <hr>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-muted small">
      <div>&copy; 2026 Wrench n Parts. All rights reserved.</div>
      <div>
        <a href="${rel("privacy-policy.html")}" class="footer-link d-inline me-3">Privacy</a>
        <a href="${rel("about.html")}" class="footer-link d-inline me-3">About</a>
        <a href="${rel("support.html")}" class="footer-link d-inline">Support</a>
      </div>
    </div>
  </div>
</footer>`;
  }

  function renderNavUser(user) {
    if (!user) {
      return `<a href="${rel("login.html")}" class="btn btn-sm btn-outline-danger">Login</a>
              <a href="${rel("register.html")}" class="btn btn-sm btn-danger">Sign Up</a>`;
    }
    const dash =
      user.role === "admin" ? "admin/dashboard.html" :
      user.role === "management" ? "management/dashboard.html" :
      user.role === "shopkeeper" ? "shopkeeper/dashboard.html" :
      user.role === "workshop" ? "workshop/dashboard.html" :
      "customer/dashboard.html";
    return `
      <a href="${rel(dash)}" class="nav-link-v2" title="${user.name}"><i class="fas fa-user-circle"></i> ${user.name.split(" ")[0]}</a>
      <button class="btn btn-sm btn-outline-secondary" onclick="WNP.logout()">Logout</button>`;
  }

  async function init() {
    const headerSlot = document.getElementById("wnp-header");
    if (headerSlot) headerSlot.innerHTML = renderNavbar();
    const footerSlot = document.getElementById("wnp-footer");
    if (footerSlot) footerSlot.innerHTML = renderFooter();

    document.addEventListener("wnp:auth", (e) => {
      const slot = document.getElementById("navUserSlot");
      if (slot) slot.innerHTML = renderNavUser(e.detail);
    });

    // Theme persistence
    const theme = localStorage.getItem("theme") || "light";
    if (theme === "dark") document.documentElement.setAttribute("data-theme", "dark");
  }

  WNP.doSearch = function (e) {
    e.preventDefault();
    const q = (document.getElementById("navSearch") || {}).value || "";
    if (!q.trim()) return false;
    location.href = rel("products.html") + "?q=" + encodeURIComponent(q);
    return false;
  };

  WNP.logout = async function () {
    try { await WNP.api.auth.logout(); } catch (e) {}
    window.WNP_USER = null;
    location.href = rel("index.html");
  };

  document.addEventListener("DOMContentLoaded", init);
})();
