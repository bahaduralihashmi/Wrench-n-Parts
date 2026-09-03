/* Wrench n Parts — shared frontend client (vanilla JS, no build step)
   Provides API access, auth state, navbar/cart helpers, and chatbot.
*/
(function (global) {
  const WNP = {
    BASE: "",  // empty = same origin (Vercel) ; override per-page via data-api-base
    CART_CACHE_KEY: "wnp_cart",
  };

  // ----- URL helpers (handle /subdir/page.html -> root-relative API calls) -----
  function apiBase() {
    if (document.body && document.body.dataset.apiBase) return document.body.dataset.apiBase;
    return WNP.BASE || (location.origin || "");
  }

  // ----- Cookie helpers (auth cookie is HTTP-only so we cannot read it; /api/auth/me is the source of truth) -----
  function readCookie(name) {
    const m = document.cookie.match(new RegExp("(?:^|;\\s*)" + name + "=([^;]*)"));
    return m ? decodeURIComponent(m[1]) : null;
  }

  // ----- Core fetch -----
  async function request(path, opts) {
    opts = opts || {};
    const init = { method: opts.method || "GET", credentials: "include", headers: { "Accept": "application/json" } };
    if (opts.body && !(opts.body instanceof FormData)) {
      init.headers["Content-Type"] = "application/json";
      init.body = JSON.stringify(opts.body);
    } else if (opts.body instanceof FormData) {
      init.body = opts.body;
    }
    if (opts.headers) Object.assign(init.headers, opts.headers);
    const res = await fetch(apiBase() + path, init);
    let data;
    try { data = await res.json(); } catch (e) { data = { success: false, error: res.statusText }; }
    if (!res.ok) {
      const err = new Error((data && (data.error || data.message)) || res.statusText);
      err.status = res.status;
      err.data = data;
      throw err;
    }
    return data;
  }

  // ----- Convenience API methods -----
  const api = {
    get: (p) => request(p),
    post: (p, body) => request(p, { method: "POST", body }),
    put: (p, body) => request(p, { method: "PUT", body }),
    del: (p) => request(p, { method: "DELETE" }),

    auth: {
      me: () => request("/api/auth/me"),
      meOptional: () => request("/api/auth/me/optional"),
      login: (email, password) => request("/api/auth/login", { method: "POST", body: { email, password } }),
      logout: () => request("/api/auth/logout", { method: "POST" }),
      register: (payload) => request("/api/auth/register", { method: "POST", body: payload }),
      registerShopkeeper: (payload) => request("/api/auth/register-shopkeeper", { method: "POST", body: payload }),
      registerWorkshop: (payload) => request("/api/auth/register-workshop", { method: "POST", body: payload }),
    },
    products: {
      list: (params) => request("/api/products" + qs(params)),
      get: (id) => request("/api/products/" + id),
      hotDeals: () => request("/api/products/hot-deals"),
    },
    categories: {
      list: () => request("/api/categories"),
    },
    cart: {
      list: () => request("/api/cart"),
      add: (product_id, quantity) => request("/api/cart", { method: "POST", body: { product_id, quantity } }),
      update: (product_id, quantity) => request("/api/cart/" + product_id, { method: "PUT", body: { quantity } }),
      remove: (product_id) => request("/api/cart/" + product_id, { method: "DELETE" }),
      clear: () => request("/api/cart", { method: "DELETE" }),
    },
    wishlist: {
      list: () => request("/api/wishlist"),
      ids: () => request("/api/wishlist/ids"),
      add: (product_id) => request("/api/wishlist/" + product_id, { method: "POST" }),
      remove: (product_id) => request("/api/wishlist/" + product_id, { method: "DELETE" }),
    },
    orders: {
      list: (params) => request("/api/orders" + qs(params)),
      get: (id) => request("/api/orders/" + id),
    },
    checkout: (payload) => request("/api/checkout", { method: "POST", body: payload }),
    appointments: {
      list: (params) => request("/api/appointments" + qs(params)),
      create: (payload) => request("/api/appointments", { method: "POST", body: payload }),
      update: (id, payload) => request("/api/appointments/" + id, { method: "PUT", body: payload }),
    },
    workshops: {
      list: (params) => request("/api/workshops" + qs(params)),
      get: (id) => request("/api/workshops/" + id),
      mine: () => request("/api/workshops/mine"),
      updateMine: (payload) => request("/api/workshops/mine", { method: "PUT", body: payload }),
    },
    shops: {
      list: () => request("/api/shops"),
      get: (id) => request("/api/shops/" + id),
      mine: () => request("/api/shops/mine"),
      updateMine: (payload) => request("/api/shops/mine", { method: "PUT", body: payload }),
    },
    reviews: {
      list: (params) => request("/api/reviews" + qs(params)),
      create: (payload) => request("/api/reviews", { method: "POST", body: payload }),
    },
    notifications: {
      list: () => request("/api/notifications"),
      count: () => request("/api/notifications/count"),
      read: (id) => request("/api/notifications/" + id + "/read", { method: "PUT" }),
      readAll: () => request("/api/notifications/read-all", { method: "PUT" }),
    },
    search: (q) => request("/api/search?q=" + encodeURIComponent(q)),
    settings: () => request("/api/settings"),
    settingsAll: () => request("/api/settings/all"),
    settingsUpdate: (payload) => request("/api/settings", { method: "PUT", body: payload }),
    uploads: {
      image: (file) => {
        const fd = new FormData();
        fd.append("file", file);
        return request("/api/uploads/image", { method: "POST", body: fd });
      },
    },
    profile: {
      get: () => request("/api/profile"),
      update: (payload) => request("/api/profile", { method: "PUT", body: payload }),
      changePassword: (current_password, new_password) => request("/api/profile/change-password", { method: "POST", body: { current_password, new_password } }),
    },
    admin: {
      dashboard: () => request("/api/admin/dashboard"),
      users: (params) => request("/api/admin/users" + qs(params)),
      setUserStatus: (id, status) => request("/api/admin/users/" + id + "/status", { method: "PUT", body: { status } }),
      approveShop: (id) => request("/api/shops/" + id + "/approve", { method: "PUT" }),
      rejectShop: (id) => request("/api/shops/" + id + "/reject", { method: "PUT" }),
      approveWorkshop: (id) => request("/api/workshops/" + id + "/approve", { method: "PUT" }),
      rejectWorkshop: (id) => request("/api/workshops/" + id + "/reject", { method: "PUT" }),
      categoriesStats: () => request("/api/admin/categories-stats"),
      shopProfits: () => request("/api/admin/shop-profits"),
      hotDeals: () => request("/api/admin/hot-deals"),
    },
    management: {
      dashboard: () => request("/api/management/dashboard"),
      analytics: () => request("/api/management/analytics"),
      reports: () => request("/api/management/reports"),
      kbArticles: () => request("/api/management/kb/articles"),
      saveKbArticle: (payload, id) => id
        ? request("/api/management/kb/articles/" + id, { method: "PUT", body: payload })
        : request("/api/management/kb/articles", { method: "POST", body: payload }),
      deleteKbArticle: (id) => request("/api/management/kb/articles/" + id, { method: "DELETE" }),
      kbProblems: () => request("/api/management/kb/problems"),
      saveKbProblem: (payload) => request("/api/management/kb/problems", { method: "POST", body: payload }),
      feedback: (action) => request("/api/management/feedback" + (action ? ("?action=" + action) : "")),
      reviewFeedback: (id, admin_action) => request("/api/management/feedback/" + id, { method: "PUT", body: { admin_action } }),
      kbPending: () => request("/api/management/kb/pending"),
      reviewPending: (id, status) => request("/api/management/kb/pending/" + id, { method: "PUT", body: { status } }),
      chatbotConfig: () => request("/api/management/chatbot-config"),
      updateChatbotConfig: (payload) => request("/api/management/chatbot-config", { method: "PUT", body: payload }),
    },
    chatbot: {
      send: (message, session_id) => request("/api/chatbot/message", { method: "POST", body: { message, session_id } }),
      feedback: (payload) => request("/api/chatbot/feedback", { method: "POST", body: payload }),
      history: (session_id) => request("/api/chatbot/history?session_id=" + encodeURIComponent(session_id)),
    },
    returns: {
      create: (order_id, reason) => request("/api/returns", { method: "POST", body: { order_id, reason } }),
    },
    chat: {
      threads: () => request("/api/chat/threads"),
      conversation: (other_id) => request("/api/chat/with/" + other_id),
      send: (receiver_id, message) => request("/api/chat", { method: "POST", body: { receiver_id, message } }),
    },
  };

  function qs(params) {
    if (!params) return "";
    const u = new URLSearchParams();
    Object.keys(params).forEach((k) => {
      const v = params[k];
      if (v !== undefined && v !== null && v !== "") u.append(k, v);
    });
    const s = u.toString();
    return s ? "?" + s : "";
  }

  // ----- UI helpers -----
  function money(n) {
    if (n === null || n === undefined) return "Rs. 0";
    return "Rs. " + Number(n).toLocaleString();
  }

  function timeAgo(iso) {
    if (!iso) return "N/A";
    const t = new Date(iso.replace(" ", "T"));
    if (isNaN(t.getTime())) return iso;
    const diff = (Date.now() - t.getTime()) / 1000;
    if (diff < 60) return "Just now";
    if (diff < 3600) return Math.floor(diff / 60) + " minutes ago";
    if (diff < 86400) return Math.floor(diff / 3600) + " hours ago";
    return Math.floor(diff / 86400) + " days ago";
  }

  function flash(message, type) {
    const el = document.createElement("div");
    el.className = "wnp-flash wnp-flash-" + (type || "info");
    el.textContent = message;
    el.style.cssText = "position:fixed;top:20px;right:20px;padding:12px 20px;background:#fff;border-left:4px solid #dc3545;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,0.12);z-index:99999;font-family:Inter,sans-serif;";
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
  }

  async function refreshAuthUI() {
    try {
      const r = await api.auth.meOptional();
      const user = r.data;
      window.WNP_USER = user;
      document.dispatchEvent(new CustomEvent("wnp:auth", { detail: user }));
    } catch (e) {
      window.WNP_USER = null;
      document.dispatchEvent(new CustomEvent("wnp:auth", { detail: null }));
    }
  }

  async function refreshCartCount() {
    try {
      const r = await api.cart.list();
      const n = (r.data && r.data.count) || 0;
      const c = document.querySelectorAll("[data-cart-count]");
      c.forEach((el) => { el.textContent = n; el.style.display = n ? "" : "none"; });
      window.WNP_CART = r.data;
    } catch (e) {
      window.WNP_CART = null;
    }
  }

  global.WNP = Object.assign(WNP, { api, request, money, timeAgo, flash, refreshAuthUI, refreshCartCount });
  document.addEventListener("DOMContentLoaded", () => {
    refreshAuthUI();
    refreshCartCount();
  });
})(window);
