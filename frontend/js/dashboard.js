/* Shared sidebar/navbar for dashboards (admin, shopkeeper, workshop, customer, management) */
(function () {
  function rel(p) {
    const parts = location.pathname.replace(/\\/g, "/").split("/").filter(Boolean);
    const idx = parts.findIndex((x) => /\.html?$/.test(x));
    let up = "";
    for (let i = idx + 1; i < parts.length; i++) up += "../";
    return up + p;
  }
  function item(label, icon, file, active) {
    return `<a href="${file}" class="dash-sidebar-item${active ? " active" : ""}"><i class="fas ${icon}"></i>${label}</a>`;
  }
  WNP.renderDashboard = function (kind, active) {
    const sets = {
      customer: [
        item("Overview", "fa-th-large", "dashboard.html", active === "dashboard"),
        item("Orders", "fa-box", "orders.html", active === "orders"),
        item("Wishlist", "fa-heart", "wishlist.html", active === "wishlist"),
        item("Bookings", "fa-calendar", "bookings.html", active === "bookings"),
        item("MechBot", "fa-robot", "chatbot.html", active === "chatbot"),
        item("Profile", "fa-user", "profile.html", active === "profile"),
        item("Returns", "fa-undo", "returns.html", active === "returns"),
      ],
      shopkeeper: [
        item("Overview", "fa-th-large", "dashboard.html", active === "dashboard"),
        item("Products", "fa-box", "products.html", active === "products"),
        item("Inventory", "fa-warehouse", "inventory.html", active === "inventory"),
        item("Orders", "fa-shopping-bag", "orders.html", active === "orders"),
        item("Hot Deals", "fa-fire", "hot-deals.html", active === "hot-deals"),
        item("Returns", "fa-undo", "returns.html", active === "returns"),
        item("Chat", "fa-comments", "chat.html", active === "chat"),
        item("Profile", "fa-store", "profile.html", active === "profile"),
      ],
      workshop: [
        item("Overview", "fa-th-large", "dashboard.html", active === "dashboard"),
        item("Appointments", "fa-calendar", "appointments.html", active === "appointments"),
        item("Services", "fa-tools", "services.html", active === "services"),
        item("Reviews", "fa-star", "reviews.html", active === "reviews"),
        item("Profile", "fa-user", "profile.html", active === "profile"),
      ],
      admin: [
        item("Overview", "fa-th-large", "dashboard.html", active === "dashboard"),
        item("Users", "fa-users", "users.html", active === "users"),
        item("Products", "fa-box", "products.html", active === "products"),
        item("Categories", "fa-tags", "categories.html", active === "categories"),
        item("Orders", "fa-shopping-bag", "orders.html", active === "orders"),
        item("Shops", "fa-store", "shops.html", active === "shops"),
        item("Workshops", "fa-tools", "workshops.html", active === "workshops"),
        item("Hot Deals", "fa-fire", "hot-deals.html", active === "hot-deals"),
        item("Shop Profits", "fa-chart-line", "shop-profits.html", active === "shop-profits"),
        item("Vehicle Catalog", "fa-car", "vehicle-catalog.html", active === "vehicle-catalog"),
        item("Management Team", "fa-user-tie", "management-team.html", active === "management-team"),
        item("Feedback", "fa-comments", "feedback-review.html", active === "feedback-review"),
        item("Settings", "fa-cog", "settings.html", active === "settings"),
      ],
      management: [
        item("Overview", "fa-th-large", "dashboard.html", active === "dashboard"),
        item("Analytics", "fa-chart-bar", "analytics.html", active === "analytics"),
        item("Reports", "fa-file-alt", "reports.html", active === "reports"),
        item("Knowledge Base", "fa-book", "knowledge-base.html", active === "knowledge-base"),
        item("Chatbot Config", "fa-robot", "chatbot-config.html", active === "chatbot-config"),
        item("Feedback Review", "fa-comments", "feedback-review.html", active === "feedback-review"),
        item("Profile", "fa-user", "profile.html", active === "profile"),
      ],
    };
    return `
<div class="dash-shell">
  <aside class="dash-sidebar">
    <div class="dash-sidebar-brand">
      <a href="${rel("../index.html")}"><img src="${rel("../uploads/logo.png")}" alt="Logo" style="height:36px"></a>
      <span class="dash-role">${kind.toUpperCase()}</span>
    </div>
    <nav class="dash-sidebar-nav">${(sets[kind] || []).join("")}</nav>
    <div class="dash-sidebar-footer">
      <button class="btn btn-sm btn-outline-secondary w-100" onclick="WNP.logout()">Logout</button>
    </div>
  </aside>
  <main class="dash-main">
    <div class="dash-topbar">
      <span class="dash-topbar-title">${active || "Dashboard"}</span>
      <span class="ms-auto small text-muted" id="dashUser"></span>
    </div>
    <div class="dash-content" id="dashContent"><div class="text-center py-5">Loading...</div></div>
  </main>
</div>`;
  };
  WNP.requireRole = async function (roles) {
    try {
      const r = await WNP.api.auth.me();
      if (!r.data) { location.href = rel("../login.html"); return null; }
      if (roles && roles.indexOf(r.data.role) === -1) {
        location.href = rel("../index.html");
        return null;
      }
      const el = document.getElementById("dashUser");
      if (el) el.textContent = r.data.name + " · " + r.data.role;
      return r.data;
    } catch (e) { location.href = rel("../login.html"); return null; }
  };
})();
