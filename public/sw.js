// ResteOS Service Worker — offline temeli
// HTML: network-first (online'da daima guncel, offline'da cache)
// Statik (css/js/img): cache-first (hizli)
const CACHE = 'restoos-v2';

self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET' || req.url.includes('/webhook/')) return;

    const accept = req.headers.get('accept') || '';
    const isHTML = req.mode === 'navigate' || accept.includes('text/html');

    if (isHTML) {
        e.respondWith(
            fetch(req)
                .then((res) => { const c = res.clone(); caches.open(CACHE).then((cc) => cc.put(req, c)); return res; })
                .catch(() => caches.match(req))
        );
    } else {
        e.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((res) => {
                if (res && res.status === 200 && req.url.startsWith(self.location.origin)) {
                    const c = res.clone(); caches.open(CACHE).then((cc) => cc.put(req, c));
                }
                return res;
            }).catch(() => cached))
        );
    }
});
