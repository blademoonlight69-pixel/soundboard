/**
 * MiniSoundboard Service Worker — PWA Support
 * Mengutamakan keandalan otentikasi, database real-time, dan pemutaran streaming audio tanpa konflik cache.
 */

const CACHE_NAME = 'minisoundboard-cache-v1';
const STATIC_ASSETS = [
    '/css/app.css',
    '/js/soundboard-player.js',
    '/manifest.json'
];

// Install: Cache aset statis dasar
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[SW] Cache prefetch warning:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activate: Bersihkan cache versi lama
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Jangan ganggu request audio streaming, otentikasi, atau mutating requests (POST/PATCH/DELETE)
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. Lewatkan semua request non-GET langsung ke network
    if (request.method !== 'GET') {
        return;
    }

    // 2. JANGAN PERNAH meng-cache streaming audio (/sounds/*/play atau file audio)
    // Audio streaming membutuhkan Range Header dan otentikasi server aktif
    if (url.pathname.includes('/sounds/') && url.pathname.includes('/play')) {
        return;
    }

    // 3. JANGAN meng-cache route API, login, register, atau logout
    if (url.pathname.startsWith('/api/') ||
        url.pathname === '/login' ||
        url.pathname === '/register' ||
        url.pathname === '/logout') {
        return;
    }

    // 4. Untuk aset statis (CSS, JS, manifest), gunakan Network-First dengan Cache Fallback
    if (url.pathname.endsWith('.css') || url.pathname.endsWith('.js') || url.pathname.endsWith('.json')) {
        event.respondWith(
            fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                return caches.match(request);
            })
        );
        return;
    }

    // 5. Untuk halaman HTML (dashboard, catalog, dll), prioritaskan Network agar data database selalu fresh
    event.respondWith(
        fetch(request).catch(() => {
            return caches.match(request);
        })
    );
});
