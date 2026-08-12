<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samsung Signage 24 Inch - Offline Ready Player</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, html {
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
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), rgba(0,0,0,0));
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
            background: rgba(0,0,0,0.5);
            padding: 4px 10px;
            border-radius: 8px;
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
            <p id="status-text" class="absolute inset-0 flex items-center justify-center text-white text-sm animate-pulse text-center px-4 z-10">
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
        const tvFrame    = document.getElementById('tv-frame');
        const header     = document.getElementById('floating-header');
        const syncBadge  = document.getElementById('sync-badge');
        const statusEl   = document.getElementById('status-text');
        const CACHE_NAME = 'signage-offline-cache-v1';
        const POLL_MS    = 10000; // cek update tiap 10 detik

        // State player
        let currentItems  = [];
        let currentIndex  = 0;
        let pendingItems  = null;
        let isPlaying     = false;  // guard buat cegah double-call playNext
        let imageTimerId  = null;
        let activeLayer   = 'a';    // layer mana yang lagi tampil

        // Hover gesture buat munculin header logout
        function showHeader() {
            header.classList.add('visible');
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => header.classList.remove('visible'), 3000);
        }
        tvFrame.addEventListener('mousemove', showHeader);
        tvFrame.addEventListener('touchstart', showHeader);

        // ===== SERVICE WORKER =====
        async function registerServiceWorker() {
            if (!('serviceWorker' in navigator)) return;
            try {
                await navigator.serviceWorker.register('/sw.js');
            } catch (err) {
                console.warn('SW gagal daftar:', err);
            }
        }

        // ===== CACHE HELPER =====
        async function downloadAndCachePlaylist(playlist) {
            localStorage.setItem('cached_playlist', JSON.stringify(playlist));

            if (!('caches' in window)) return;
            const cache = await caches.open(CACHE_NAME);

            for (const item of playlist.items) {
                try {
                    const res = await fetch(item.url, { cache: 'no-store' });
                    if (res.ok) await cache.put(item.url, res.clone());
                } catch (e) {
                    console.warn('Cache miss untuk:', item.url);
                }
            }
        }

        // ===== INIT: ambil playlist, cache, mulai main =====
        async function initSignagePlayer() {
            try {
                const res = await fetch('/api/signage/playlist', { cache: 'no-store' });
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

            // Mulai polling setelah init selesai
            setInterval(checkForUpdates, POLL_MS);
        }

        // ===== POLLING: cek versi terbaru di background =====
        async function checkForUpdates() {
            try {
                const res = await fetch('/api/signage/playlist', { cache: 'no-store' });
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
            } catch (_) {
                // Offline saat polling — diam saja, lanjut main dari cache
            }
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
            isPlaying    = false;
            playNext();
        }

        // Helper: ambil elemen img/video dari layer tertentu
        function getLayerEls(layer) {
            return {
                layerEl: document.getElementById('layer-' + layer),
                imgEl: document.getElementById('img-' + layer),
                videoEl: document.getElementById('video-' + layer),
            };
        }

        // Crossfade: layer baru jadi "active" (fade in), layer lama fade out & dibersihkan
        function crossfadeToLayer(newLayer) {
            const oldLayer = newLayer === 'a' ? 'b' : 'a';
            const { layerEl: newEl } = getLayerEls(newLayer);
            const { layerEl: oldEl, imgEl: oldImg, videoEl: oldVideo } = getLayerEls(oldLayer);

            statusEl.classList.add('hidden');
            newEl.classList.add('active');
            oldEl.classList.remove('active');

            // Bersihin layer lama SETELAH transisi opacity selesai (500ms), biar ga keliatan patah
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

        function playNext() {
            // Guard: jangan panggil dua kali bersamaan
            if (isPlaying) return;
            isPlaying = true;

            if (imageTimerId !== null) {
                clearTimeout(imageTimerId);
                imageTimerId = null;
            }

            // Swap ke playlist baru kalau sudah siap (di batas pergantian item)
            if (pendingItems) {
                currentItems = pendingItems;
                pendingItems = null;
                currentIndex = 0;
                syncBadge.textContent = 'Tersinkron (update diterapkan)';
                console.log('Playlist baru diterapkan.');
            }

            if (currentIndex >= currentItems.length) currentIndex = 0;

            const item = currentItems[currentIndex];
            const nextLayer = activeLayer === 'a' ? 'b' : 'a';
            const { imgEl, videoEl } = getLayerEls(nextLayer);

            if (item.type === 'image') {
                // Preload gambar di background dulu SEBELUM ditampilkan - ini yang menghilangkan kedip
                const preloadImg = new Image();
                preloadImg.onload = () => {
                    imgEl.src = item.url;
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
                    console.warn('Gagal load gambar, skip ke berikutnya');
                    isPlaying = false;
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };
                preloadImg.src = item.url;

            } else {
                // VIDEO - preload di layer tersembunyi, baru tampil pas beneran siap main
                videoEl.src = item.url;
                videoEl.muted = false;
                videoEl.classList.remove('hidden');
                imgEl.classList.add('hidden');
                videoEl.load();

                let started = false;
                const tryShow = () => {
                    if (started) return;
                    started = true;
                    crossfadeToLayer(nextLayer);
                    isPlaying = false;
                };

                videoEl.oncanplay = () => {
                    videoEl.play().then(tryShow).catch(() => {
                        // Autoplay dengan suara diblokir - fallback mute
                        videoEl.muted = true;
                        videoEl.play().then(tryShow).catch(() => {
                            isPlaying = false;
                            currentIndex = (currentIndex + 1) % currentItems.length;
                            playNext();
                        });
                    });
                };

                videoEl.onended = () => {
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };

                videoEl.onerror = () => {
                    console.warn('Gagal load video, skip ke berikutnya');
                    isPlaying = false;
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };
            }
        }

        // ===== BOOT =====
        window.onload = function () {
            registerServiceWorker();
            initSignagePlayer();
        };
    </script>
</body>

</html>