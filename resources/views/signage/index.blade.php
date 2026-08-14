<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samsung Signage 24 Inch - Offline Ready Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        #tv-frame {
            width: 100vw;
            height: 100vh;
            max-width: 450px;
            max-height: 800px;
            background: #000;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            border: 8px solid #222;
            border-radius: 12px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media screen and (orientation: portrait) {
            #tv-frame {
                width: 100%;
                height: 100%;
                max-width: 100%;
                max-height: 100%;
                border: none;
                border-radius: 0;
            }
        }

        #floating-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0));
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: opacity 0.4s ease-in-out, transform 0.4s ease-in-out;
            opacity: 0;
            transform: translateY(-100%);
            z-index: 50;
        }

        #tv-frame:hover #floating-header,
        #floating-header.visible {
            opacity: 1;
            transform: translateY(0);
        }

        #sync-badge {
            font-size: 10px;
            color: #9CA3AF;
            background: rgba(0, 0, 0, 0.5);
            padding: 4px 10px;
            border-radius: 8px;
        }

        /* ===== Debug overlay ===== */
        #debug-overlay {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 100;
            font-family: monospace;
            font-size: 10px;
            line-height: 1.5;
            color: #0f0;
            background: rgba(0, 0, 0, 0.65);
            padding: 6px 10px;
            border-radius: 6px;
            white-space: pre;
            pointer-events: none;
            max-width: 90%;
            word-break: break-all;
        }

        /* ===== Double-buffer layer buat crossfade, biar ga ada momen "kosong" pas ganti konten ===== */
        .signage-layer {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            z-index: 1;
        }

        .signage-layer.active {
            opacity: 1;
            z-index: 2;
        }

        .signage-layer img,
        .signage-layer video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }
    </style>
</head>

<body class="flex items-center justify-center h-screen bg-neutral-950">
    <div id="tv-frame">

        <div id="debug-overlay">Menyiapkan diagnosa...</div>

        <div id="floating-header">
            <span id="sync-badge">Standby</span>
            <form action="{{ route('logout') }}" method="GET">
                @csrf
                <button type="submit"
                    class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs px-3.5 py-2 rounded-lg font-semibold transition-all shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                </button>
            </form>
        </div>

        <div id="signage-container" class="relative w-full h-full bg-black">
            <p id="status-text"
                class="absolute inset-0 flex items-center justify-center text-white text-sm animate-pulse text-center px-4 z-10">
                Sinkronisasi & Mengunduh Konten...
            </p>

            <!-- Layer A & B - selalu ada di DOM, gantian yang "active" (opacity 1) -->
            <div id="layer-a" class="signage-layer">
                <img id="img-a" class="hidden">
                <video id="video-a" class="hidden" playsinline></video>
            </div>
            <div id="layer-b" class="signage-layer">
                <img id="img-b" class="hidden">
                <video id="video-b" class="hidden" playsinline></video>
            </div>
        </div>
    </div>

    <script>
        // ===== SETUP =====
        let hideTimer;
        const tvFrame = document.getElementById('tv-frame');
        const header = document.getElementById('floating-header');
        const syncBadge = document.getElementById('sync-badge');
        const statusEl = document.getElementById('status-text');
        const debugEl = document.getElementById('debug-overlay');
        const POLL_MS = 10000;
        const DEBUG_POLL_MS = 3000;

        // ===== DEBUG STATE (baru) =====
        let debugPlayInfo = '-';
        let debugLastError = '-';

        // ===== INDEXEDDB =====
        const DB_NAME = 'signage-offline-db';
        const DB_VERSION = 1;
        const STORE_NAME = 'media';
        let dbPromise = null;

        // ===== CLEAR CACHE VIA URL PARAM =====
        // Akses: http://[IP]:8000/signage-view?clearcache=1
        async function clearAllCacheIfRequested() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('clearcache') === '1') {
                try {
                    localStorage.removeItem('cached_playlist');
                    const db = await openDB();
                    db.close();
                    await new Promise((resolve, reject) => {
                        const req = indexedDB.deleteDatabase(DB_NAME);
                        req.onsuccess = () => resolve();
                        req.onerror = () => reject(req.error);
                        req.onblocked = () => resolve(); // tetap lanjut walau blocked
                    });
                } catch (e) {
                    console.warn('Gagal clear cache:', e);
                }
                // Redirect ke URL bersih (tanpa ?clearcache=1) biar gak infinite clear tiap reload
                window.location.href = window.location.pathname;
                return true; // sinyal supaya boot process nunggu redirect
            }
            return false;
        }

        function openDB() {
            if (dbPromise) return dbPromise;
            dbPromise = new Promise((resolve, reject) => {
                const req = indexedDB.open(DB_NAME, DB_VERSION);
                req.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME);
                    }
                };
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
            return dbPromise;
        }

        async function idbSet(key, blob) {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).put(blob, key);
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }

        async function idbGet(key) {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readonly');
                const req = tx.objectStore(STORE_NAME).get(key);
                req.onsuccess = () => resolve(req.result || null);
                req.onerror = () => reject(req.error);
            });
        }

        async function idbKeys() {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readonly');
                const req = tx.objectStore(STORE_NAME).getAllKeys();
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }

        async function idbDelete(key) {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).delete(key);
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }

        async function idbClearUnused(usedUrls) {
            try {
                const keys = await idbKeys();
                for (const key of keys) {
                    if (!usedUrls.includes(key)) {
                        await idbDelete(key);
                        if (mediaBlobUrls[key]) {
                            URL.revokeObjectURL(mediaBlobUrls[key]);
                            delete mediaBlobUrls[key];
                        }
                    }
                }
            } catch (e) {
                console.warn('Gagal bersihin IndexedDB lama:', e);
            }
        }

        let mediaBlobUrls = {};

        // Ambil URL yang siap dipakai buat src img/video: prioritas dari IndexedDB (blob: URL),
        // fallback ke URL server asli kalau belum sempat ke-cache.
        async function getPlayableUrl(url) {
            if (mediaBlobUrls[url]) return mediaBlobUrls[url];
            try {
                const blob = await idbGet(url);
                if (blob) {
                    const blobUrl = URL.createObjectURL(blob);
                    mediaBlobUrls[url] = blobUrl;
                    return blobUrl;
                }
            } catch (e) {
                console.warn('Gagal ambil dari IndexedDB:', url, e);
                debugLastError = 'IDB read gagal: ' + e.message;
            }
            return url; // fallback ke URL server langsung
        }

        // State player
        let currentItems = [];
        let currentIndex = 0;
        let pendingItems = null;
        let isPlaying = false;
        let imageTimerId = null;
        let activeLayer = 'a';

        function showHeader() {
            header.classList.add('visible');
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => header.classList.remove('visible'), 3000);
        }
        tvFrame.addEventListener('mousemove', showHeader);
        tvFrame.addEventListener('touchstart', showHeader);

        // ===== DEBUG OVERLAY =====
        async function updateDebugOverlay() {
            const netStatus = navigator.onLine ? 'online' : 'OFFLINE';

            const swSupported = 'serviceWorker' in navigator;
            let swStatus = swSupported ? 'ok' : 'X';
            let swActive = '';
            if (swSupported) {
                swActive = navigator.serviceWorker.controller ? '(aktif)' : '(blm aktif)';
            }

            const cacheStatus = ('caches' in window) ? 'ok' : 'X';
            const idbSupported = 'indexedDB' in window;
            const idbStatus = idbSupported ? 'ok' : 'X';
            const blobUrlStatus = (typeof URL !== 'undefined' && 'createObjectURL' in URL) ? 'ok' : 'X';

            let itemsCount = '?';
            if (idbSupported) {
                try {
                    const keys = await idbKeys();
                    itemsCount = keys.length;
                } catch (e) {
                    itemsCount = 'err';
                }
            }

            debugEl.textContent =
                `Net:${netStatus}\n` +
                `SW:${swStatus} ${swActive}\n` +
                `Cache:${cacheStatus}\n` +
                `IDB:${idbStatus}\n` +
                `BlobURL:${blobUrlStatus}\n` +
                `Items:${itemsCount}\n` +
                `Item:${debugPlayInfo}\n` +
                `Err:${debugLastError}`;
        }

        // ===== SERVICE WORKER =====
        async function registerServiceWorker() {
            if (!('serviceWorker' in navigator)) return;
            try {
                await navigator.serviceWorker.register('/sw.js');
            } catch (err) {
                console.warn('SW gagal daftar:', err);
            }
        }

        // ===== DOWNLOAD & SIMPAN KE INDEXEDDB =====
        async function downloadAndCachePlaylist(playlist) {
            localStorage.setItem('cached_playlist', JSON.stringify(playlist));

            for (const item of playlist.items) {
                try {
                    const res = await fetch(item.url, {
                        cache: 'no-store'
                    });
                    if (res.ok) {
                        const blob = await res.blob();
                        await idbSet(item.url, blob);
                    }
                } catch (e) {
                    console.warn('Gagal download/simpan ke IndexedDB:', item.url, e);
                    debugLastError = 'Download gagal: ' + item.url;
                }
            }

            await idbClearUnused(playlist.items.map(i => i.url));
        }

        // ===== INIT =====
        async function initSignagePlayer() {
            try {
                const res = await fetch('/api/signage/playlist', {
                    cache: 'no-store'
                });
                if (!res.ok) throw new Error('Server tidak merespons');

                const playlist = await res.json();

                if (!playlist.items || playlist.items.length === 0) {
                    statusEl.textContent = 'Tidak ada playlist terjadwal untuk hari ini.';
                    localStorage.removeItem('cached_playlist');
                    return;
                }

                statusEl.textContent = 'Mengunduh konten...';
                await downloadAndCachePlaylist(playlist);

                syncBadge.textContent = 'Tersinkron · v' + playlist.version;
                startLoop(playlist.items);

            } catch (err) {
                console.warn('Offline / gagal koneksi:', err);
                statusEl.textContent = 'Memuat dari cache offline...';

                const raw = localStorage.getItem('cached_playlist');
                if (raw) {
                    const cached = JSON.parse(raw);
                    syncBadge.textContent = 'Offline · cache ' + (cached.version ?? '?');
                    startLoop(cached.items);
                } else {
                    statusEl.textContent = 'Tidak ada koneksi & cache kosong.';
                }
            }

            setInterval(checkForUpdates, POLL_MS);
        }

        // ===== POLLING =====
        async function checkForUpdates() {
            try {
                const res = await fetch('/api/signage/playlist', {
                    cache: 'no-store'
                });
                if (!res.ok) return;

                const playlist = await res.json();
                const raw = localStorage.getItem('cached_playlist');
                const cachedVersion = raw ? JSON.parse(raw).version : null;

                if (
                    playlist.version &&
                    playlist.version !== cachedVersion &&
                    playlist.items?.length > 0
                ) {
                    console.log('Update:', cachedVersion, '→', playlist.version);
                    syncBadge.textContent = 'Mengunduh update...';
                    await downloadAndCachePlaylist(playlist);
                    pendingItems = playlist.items;
                    syncBadge.textContent = 'Update siap · menunggu giliran...';
                }
            } catch (_) {}
        }

        // ===== LOOP UTAMA =====
        function startLoop(items) {
            if (!items || items.length === 0) {
                statusEl.textContent = 'Playlist kosong.';
                statusEl.classList.remove('hidden');
                return;
            }
            currentItems = items;
            currentIndex = 0;
            isPlaying = false;
            playNext();
        }

        function getLayerEls(layer) {
            return {
                layerEl: document.getElementById('layer-' + layer),
                imgEl: document.getElementById('img-' + layer),
                videoEl: document.getElementById('video-' + layer),
            };
        }

        function crossfadeToLayer(newLayer) {
            const oldLayer = newLayer === 'a' ? 'b' : 'a';
            const {
                layerEl: newEl
            } = getLayerEls(newLayer);
            const {
                layerEl: oldEl,
                imgEl: oldImg,
                videoEl: oldVideo
            } = getLayerEls(oldLayer);

            statusEl.classList.add('hidden');
            newEl.classList.add('active');
            oldEl.classList.remove('active');

            setTimeout(() => {
                oldVideo.pause();
                oldVideo.removeAttribute('src');
                oldVideo.load();
                oldVideo.classList.add('hidden');
                oldImg.src = '';
                oldImg.classList.add('hidden');
            }, 550);

            activeLayer = newLayer;
        }

        async function playNext() {
            if (isPlaying) return;
            isPlaying = true;
            if (imageTimerId !== null) {
                clearTimeout(imageTimerId);
                imageTimerId = null;
            }

            if (pendingItems) {
                currentItems = pendingItems;
                pendingItems = null;
                currentIndex = 0;
                syncBadge.textContent = 'Tersinkron (update diterapkan)';
            }

            if (currentIndex >= currentItems.length) currentIndex = 0;
            const item = currentItems[currentIndex];
            const nextLayer = activeLayer === 'a' ? 'b' : 'a';
            const {
                imgEl,
                videoEl
            } = getLayerEls(nextLayer);
            const playableUrl = await getPlayableUrl(item.url);

            // Update debug info: index/type/sumber (BLOB dari IndexedDB atau URL server langsung)
            debugPlayInfo = `${currentIndex}/${item.type}/${playableUrl.startsWith('blob:') ? 'BLOB' : 'URL'}`;

            if (item.type === 'image') {
                const preloadImg = new Image();
                preloadImg.onload = () => {
                    imgEl.src = playableUrl;
                    imgEl.classList.remove('hidden');
                    videoEl.classList.add('hidden');
                    crossfadeToLayer(nextLayer);
                    isPlaying = false;

                    const durMs = (item.duration || 10) * 1000;
                    imageTimerId = setTimeout(() => {
                        currentIndex = (currentIndex + 1) % currentItems.length;
                        playNext();
                    }, durMs);
                };
                preloadImg.onerror = () => {
                    debugLastError = `IMG gagal (${playableUrl.startsWith('blob:') ? 'blob' : 'url'}): ${item.url}`;
                    isPlaying = false;
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };
                preloadImg.src = playableUrl;
            } else {
                videoEl.pause();
                videoEl.src = playableUrl;
                videoEl.muted = false;
                videoEl.classList.remove('hidden');
                imgEl.classList.add('hidden');
                videoEl.load();

                crossfadeToLayer(nextLayer);

                const playPromise = videoEl.play();
                if (playPromise !== undefined) {
                    playPromise.catch((e) => {
                        if (e.name === 'AbortError') {
                            // Wajar terjadi kalau src berganti cepat — bukan kegagalan asli, abaikan.
                            return;
                        }
                        debugLastError = `VIDEO play() gagal: ${e.message}`;
                        videoEl.muted = true;
                        videoEl.play().catch((e2) => {
                            debugLastError = `VIDEO play() gagal (muted juga): ${e2.message}`;
                            currentIndex = (currentIndex + 1) % currentItems.length;
                            isPlaying = false;
                            playNext();
                        });
                    });
                }

                isPlaying = false;

                videoEl.onended = () => {
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };

                videoEl.onerror = () => {
                    debugLastError =
                        `VIDEO onerror (${playableUrl.startsWith('blob:') ? 'blob' : 'url'}): ${item.url}`;
                    console.warn('Video error, skipping...');
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };
            }
        }

        // ===== BOOT =====
        window.onload = async function() {
            const isClearing = await clearAllCacheIfRequested();
            if (isClearing) return; // lagi proses redirect, jangan lanjut init dulu

            registerServiceWorker();
            initSignagePlayer();
            updateDebugOverlay();
            setInterval(updateDebugOverlay, DEBUG_POLL_MS);
        };
    </script>
</body>

</html>
