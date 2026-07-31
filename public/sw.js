// PENTING: nama ini HARUS SAMA dengan CACHE_NAME di index.blade.php
const CACHE_NAME = 'signage-offline-cache-v2';
const urlsToCache = [
    '/signage-view', // Ganti dengan URL rute halaman signage kamu jika berbeda
];

// Saat Service Worker dipasang, simpan halaman utama ke cache
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(urlsToCache);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((key) => {
                    // FIX: sebelumnya "cache_name" (typo, undefined variable)
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Cegat request untuk media DAN halaman utama
self.addEventListener('fetch', (event) => {
    const url = event.request.url;
    const isMedia = event.request.destination === 'image' ||
        event.request.destination === 'video' ||
        /\.(jpg|jpeg|png|gif|webp|mp4|mov|avi)(\?|$)/i.test(url);

    const isPage = event.request.mode === 'navigate';

    if (!isMedia && !isPage) return;

    event.respondWith(
        caches.open(CACHE_NAME).then(async (cache) => {
            const cached = await cache.match(event.request);

            // Strategi: kalau ada di cache DAN ini adalah media, langsung pakai cache
            // (media besar/video tidak perlu di-refetch tiap saat, boros bandwidth)
            if (cached && isMedia) {
                return cached;
            }

            try {
                const fresh = await fetch(event.request);
                if (fresh && fresh.status === 200) {
                    cache.put(event.request, fresh.clone());
                }
                return fresh;
            } catch (err) {
                // Offline: fallback ke cache apapun yang ada
                if (cached) {
                    return cached;
                }
                if (isPage) {
                    const fallbackPage = await cache.match('/signage-view');
                    if (fallbackPage) return fallbackPage;
                }
                return new Response('', { status: 404 });
            }
        })
    );
});