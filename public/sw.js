// ResteOS Service Worker — offline temeli
// HTML + API/veri: network-first (online'da daima guncel, offline'da cache yedegi)
// Statik (css/js/img/font): stale-while-revalidate (cache'ten hizli, arka planda tazelenir)
// v3: API yanitlari eskiden cache-first'tu → acilan detay/zil/liste ilk yanitta takili kaliyordu
const CACHE = 'restoos-v3';

self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

const STATIK = /\.(css|js|png|jpe?g|svg|webp|gif|ico|woff2?|ttf|otf|mp3|wav)$/i;

function sakla(req, res) {
    if (res && res.status === 200 && req.url.startsWith(self.location.origin)) {
        const c = res.clone(); caches.open(CACHE).then((cc) => cc.put(req, c));
    }
    return res;
}

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET' || req.url.includes('/webhook/') || req.url.includes('/garson-yonetimi/veri')) return; // canlı veri önbelleğe alınmaz

    const url = new URL(req.url);
    const statik = url.origin === self.location.origin && STATIK.test(url.pathname);

    if (statik) {
        // cache'ten hemen ver, ağdan tazele (bir sonraki açılışta yeni sürüm)
        e.respondWith(
            caches.match(req).then((cached) => {
                const ag = fetch(req).then((res) => sakla(req, res)).catch(() => cached);
                return cached || ag;
            })
        );
        return;
    }
    // HTML, API ve diğer her şey: önce ağ (güncel veri), ağ yoksa son kopya
    e.respondWith(
        fetch(req).then((res) => sakla(req, res)).catch(() => caches.match(req))
    );
});
