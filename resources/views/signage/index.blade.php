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
            justify-content: flex-end;
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
    </style>
</head>

<body class="flex items-center justify-center h-screen bg-neutral-950">

    <div id="tv-frame">

        <div id="floating-header">
            <form action="{{ route('logout') }}" method="GET">
                @csrf
                <button type="submit"
                    class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs px-3.5 py-2 rounded-lg font-semibold transition-all shadow-lg backdrop-blur-md">
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
            <p id="status-text" class="text-white text-sm animate-pulse text-center px-4">Sinkronisasi & Mengunduh
                Konten...</p>
        </div>
    </div>

    <script>
        let hideTimer;
        const tvFrame = document.getElementById('tv-frame');
        const header = document.getElementById('floating-header');

        // PENTING: nama ini HARUS SAMA PERSIS dengan CACHE_NAME di sw.js
        const CACHE_NAME = 'signage-offline-cache-v2';

        // Interval polling untuk cek playlist baru dari dashboard (dalam ms)
        const POLL_INTERVAL = 20000; // 20 detik

        // State pemutaran, disimpan di scope luar biar bisa di-restart pas ada playlist baru
        let currentVersion = null;
        let activeTimer = null;
        let currentVideoEl = null;

        function handleCursorMovement() {
            header.classList.add('visible');
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => {
                header.classList.remove('visible');
            }, 3000);
        }

        tvFrame.addEventListener('mousemove', handleCursorMovement);
        tvFrame.addEventListener('touchstart', handleCursorMovement);

        // Daftarkan Service Worker (WAJIB agar refresh/offline bisa jalan)
        async function registerServiceWorker() {
            if ('serviceWorker' in navigator) {
                try {
                    const reg = await navigator.serviceWorker.register('/sw.js');
                    console.log('Service Worker terdaftar dengan scope:', reg.scope);
                } catch (err) {
                    console.warn('Gagal mendaftarkan Service Worker:', err);
                }
            } else {
                console.warn('Browser ini tidak mendukung Service Worker.');
            }
        }

        // Cek playlist terbaru dari server. Kalau ada versi baru (beda dari yang lagi diputar),
        // download asetnya lalu langsung ganti pemutaran - TANPA reload halaman.
        // isInitial = true hanya dipakai saat load pertama kali (untuk fallback ke cache lokal saat offline).
        async function checkAndSyncPlaylist(isInitial = false) {
            const statusText = document.getElementById('status-text');

            try {
                // cache: 'no-store' -> pastikan selalu tanya versi TERBARU ke server, bukan versi lama
                const response = await fetch('/api/signage/playlist', { cache: 'no-store' });
                if (!response.ok) throw new Error('Gagal terhubung ke server');

                const playlist = await response.json();

                if (!playlist.items || playlist.items.length === 0) {
                    if (isInitial) statusText.textContent = "Playlist kosong.";
                    return;
                }

                // Kalau versinya sama kayak yang lagi diputar sekarang, tidak usah diapa-apain
                // (biar video yang lagi jalan tidak keinterupsi terus-terusan tiap polling)
                if (playlist.version === currentVersion) return;

                console.log(`Playlist baru terdeteksi (versi ${currentVersion} -> ${playlist.version}), sinkronisasi...`);

                // Simpan metadata playlist ke localStorage untuk cadangan offline
                localStorage.setItem('cached_playlist', JSON.stringify(playlist));

                // Download & simpan fisik file media ke Cache Storage browser TV
                if ('caches' in window) {
                    const cache = await caches.open(CACHE_NAME);
                    if (isInitial) statusText.textContent = "Mengunduh aset media ke memori TV...";

                    for (const item of playlist.items) {
                        try {
                            const mediaResponse = await fetch(item.url);
                            if (mediaResponse.ok) {
                                await cache.put(item.url, mediaResponse);
                            } else {
                                console.warn(`Gagal mendownload asset: ${item.url}`);
                            }
                        } catch (err) {
                            console.warn(`Skipping asset due to network/CORS error: ${item.url}`);
                        }
                    }
                }

                currentVersion = playlist.version;
                startLoop(playlist.items);

            } catch (error) {
                console.warn("Gagal ambil playlist terbaru / offline:", error);

                // Fallback ke cache lokal HANYA saat load pertama kali (belum ada apa-apa yang diputar).
                // Kalau ini cuma polling biasa dan lagi offline sesaat, biarkan playlist yang sedang
                // berjalan tetap lanjut, jangan diinterupsi.
                if (isInitial) {
                    statusText.textContent = "Koneksi terputus. Memuat dari Cache Offline...";
                    const cachedData = localStorage.getItem('cached_playlist');
                    if (cachedData) {
                        const playlist = JSON.parse(cachedData);
                        if (playlist.items && playlist.items.length > 0) {
                            currentVersion = playlist.version;
                            startLoop(playlist.items);
                            return;
                        }
                    }
                    statusText.textContent = "Tidak ada koneksi internet & cache lokal kosong.";
                }
            }
        }

        // Fungsi Looping Pemutaran Media
        function startLoop(items) {
            // Hentikan pemutaran/timer sebelumnya (kalau ini pergantian playlist saat sedang jalan)
            if (activeTimer) {
                clearTimeout(activeTimer);
                activeTimer = null;
            }
            if (currentVideoEl) {
                currentVideoEl.onended = null;
                currentVideoEl.onerror = null;
                currentVideoEl.pause();
                currentVideoEl = null;
            }

            if (!items || items.length === 0) {
                document.getElementById('signage-container').innerHTML =
                    `<p class="text-gray-400 text-xs">Playlist kosong.</p>`;
                return;
            }

            let currentIndex = 0;
            const container = document.getElementById('signage-container');

            function playNext() {
                const item = items[currentIndex];
                container.innerHTML = '';

                if (item.type === 'image') {
                    currentVideoEl = null;
                    const img = document.createElement('img');
                    img.src = item.url; // Browser otomatis mengambil dari cache jika offline
                    img.className = 'w-full h-full object-contain';
                    container.appendChild(img);

                    activeTimer = setTimeout(() => {
                        currentIndex = (currentIndex + 1) % items.length;
                        playNext();
                    }, (item.duration || 10) * 1000);

                } else if (item.type === 'video') {
                    const video = document.createElement('video');
                    video.src = item.url; // Browser otomatis mengambil dari cache jika offline
                    video.className = 'w-full h-full object-contain';
                    video.autoplay = true;
                    video.muted = false; // Tizen SSSP (signage) umumnya tidak strict soal autoplay+audio seperti Chrome desktop
                    video.playsInline = true;
                    container.appendChild(video);
                    currentVideoEl = video;

                    video.onended = () => {
                        currentIndex = (currentIndex + 1) % items.length;
                        playNext();
                    };

                    video.onerror = () => {
                        currentIndex = (currentIndex + 1) % items.length;
                        playNext();
                    };

                    // Fallback: kalau di device tertentu ternyata tetap ditolak browser,
                    // coba lagi dengan mute daripada macet diam total
                    const playPromise = video.play();
                    if (playPromise !== undefined) {
                        playPromise.catch((err) => {
                            console.warn('Autoplay dengan suara gagal, coba ulang dengan mute:', err);
                            video.muted = true;
                            video.play().catch(() => {
                                currentIndex = (currentIndex + 1) % items.length;
                                playNext();
                            });
                        });
                    }
                }
            }

            playNext();
        }

        window.onload = async () => {
            await registerServiceWorker();

            // Load pertama kali - kalau offline, otomatis fallback ke cache lokal
            await checkAndSyncPlaylist(true);

            // Polling berkala - cek apakah ada playlist baru dari dashboard,
            // tanpa perlu refresh manual/reload halaman
            setInterval(() => checkAndSyncPlaylist(false), POLL_INTERVAL);
        };
    </script>
</body>

</html>