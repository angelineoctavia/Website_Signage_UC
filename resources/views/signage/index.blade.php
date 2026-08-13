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
        <div id="signage-container" class="relative w-full h-full flex items-center justify-center bg-black">
            <p id="status-text" class="text-white text-sm animate-pulse text-center px-4">
                Sinkronisasi & Mengunduh Konten...
            </p>
        </div>
    </div>
    <script>
        // ===== SETUP =====
        let hideTimer;
        const tvFrame = document.getElementById('tv-frame');
        const header = document.getElementById('floating-header');
        const syncBadge = document.getElementById('sync-badge');
        const CACHE_NAME = 'signage-offline-cache-v1';
        const POLL_MS = 10000; // cek update tiap 10 detik

        // State player
        let currentItems = [];
        let currentIndex = 0;
        let pendingItems = null;
        let isPlaying = false; // guard buat cegah double-call playNext
        let imageTimerId = null;

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
                    const res = await fetch(item.url, {
                        cache: 'no-store'
                    });
                    if (res.ok) await cache.put(item.url, res.clone());
                } catch (e) {
                    console.warn('Cache miss untuk:', item.url);
                }
            }
        }

        // ===== INIT: ambil playlist, cache, mulai main =====
        async function initSignagePlayer() {
            const statusEl = document.getElementById('status-text');
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

            // Mulai polling setelah init selesai
            setInterval(checkForUpdates, POLL_MS);
        }

        // ===== POLLING: cek versi terbaru di background =====
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
            } catch (_) {
                // Offline saat polling — diam saja, lanjut main dari cache
            }
        }

        // ===== LOOP UTAMA =====
        function startLoop(items) {
            if (!items || items.length === 0) {
                document.getElementById('signage-container').innerHTML =
                    '<p class="text-gray-400 text-xs">Playlist kosong.</p>';
                return;
            }
            currentItems = items;
            currentIndex = 0;
            isPlaying = false;
            playNext();
        }

        function playNext() {
            // Guard: jangan panggil dua kali bersamaan
            if (isPlaying) return;
            isPlaying = true;

            // Bersihkan timer gambar sebelumnya
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

            // Batas aman index
            if (currentIndex >= currentItems.length) currentIndex = 0;

            const item = currentItems[currentIndex];
            const container = document.getElementById('signage-container');

            // Kosongkan container sebelum render item baru
            container.innerHTML = '';

            if (item.type === 'image') {
                const img = document.createElement('img');
                img.src = item.url;
                img.className = 'w-full h-full object-contain';

                img.onload = () => {
                    isPlaying = false; // baru lepas guard setelah gambar loaded
                };

                img.onerror = () => {
                    console.warn('Gagal load gambar, skip ke berikutnya');
                    isPlaying = false;
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                    return;
                };

                container.appendChild(img);

                const durMs = (item.duration || 10) * 1000;
                imageTimerId = setTimeout(() => {
                    isPlaying = false;
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                }, durMs);
            } else {
                // VIDEO
                const video = document.createElement('video');
                video.src = item.url;
                video.className = 'w-full h-full object-contain';
                video.autoplay = true;
                video.playsInline = true;
                video.muted = false;

                let hasAdvanced = false;
                const safeNext = () => {
                    if (hasAdvanced) return;
                    hasAdvanced = true;
                    isPlaying = false;
                    currentIndex = (currentIndex + 1) % currentItems.length;
                    playNext();
                };

                video.onended = safeNext;
                video.onerror = () => {
                    console.warn('Gagal load video, skip ke berikutnya');
                    safeNext();
                };

                container.appendChild(video);

                // WATCHDOG TIMER: Jika video offline macet lebih dari 15 detik atau gagal start, paksa lanjut!
                const videoWatchdog = setTimeout(() => {
                    console.warn('Video offline timeout / macet, memaksa skip ke media berikutnya...');
                    safeNext();
                }, 15000);

                video.play().catch(() => {
                    video.muted = true;
                    video.play().catch(() => {
                        clearTimeout(videoWatchdog);
                        safeNext();
                    });
                });

                video.onplaying = () => {
                    isPlaying = false;
                };

                // Bersihkan watchdog kalau video selesai normal
                video.addEventListener('ended', () => {
                    clearTimeout(videoWatchdog);
                });
            }
        }

        // ===== BOOT =====
        window.onload = function() {
            registerServiceWorker();
            initSignagePlayer();
        };
    </script>
</body>

</html>