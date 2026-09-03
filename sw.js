const CACHE_NAME = 'wnp-v3';
const STATIC_CACHE = 'wnp-static-v3';
const DYNAMIC_CACHE = 'wnp-dynamic-v3';

const BASE = self.location.pathname.replace(/\/sw\.js$/, '/');
const STATIC_ASSETS = [
    BASE,
    BASE + 'index.php',
    BASE + 'login.php',
    BASE + 'css/style.css',
    BASE + 'css/responsive.css',
    BASE + 'css/customer-dashboard.css',
    BASE + 'css/chatbot-response.css',
    BASE + 'css/admin.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => {
                console.log('Some static assets failed to cache:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip API calls, chatbot, admin, management, shopkeeper, customer dynamic pages
    if (url.pathname.includes('/api/') ||
        url.pathname.includes('/chatbot/') ||
        url.pathname.includes('/admin/') ||
        url.pathname.includes('/management/') ||
        url.pathname.includes('/shopkeeper/') ||
        url.pathname.includes('/customer/') ||
        url.pathname.includes('/workshop/')) {
        return;
    }

    // Network-first for dynamic pages
    if (request.headers.get('accept') &&
        request.headers.get('accept').includes('text/html')) {
        event.respondWith(
            fetch(request).then(response => {
                const clone = response.clone();
                caches.open(DYNAMIC_CACHE).then(cache => cache.put(request, clone));
                return response;
            }).catch(() => caches.match(request).then(cached => cached || caches.match('/')))
        );
        return;
    }

    // Cache-first for static assets (CSS, JS, images)
    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            return fetch(request).then(response => {
                if (response.ok && url.origin === location.origin) {
                    const clone = response.clone();
                    caches.open(DYNAMIC_CACHE).then(cache => cache.put(request, clone));
                }
                return response;
            });
        }).catch(() => {
            // Return offline fallback for images
            if (request.destination === 'image') {
                return new Response(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect fill="#333" width="200" height="200"/><text fill="#999" x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="14">Offline</text></svg>',
                    { headers: { 'Content-Type': 'image/svg+xml' } }
                );
            }
        })
    );
});
