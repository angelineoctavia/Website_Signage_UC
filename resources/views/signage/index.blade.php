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

        // Fungsi Utama Inisialisasi Signage dengan True Offline Caching & Fallback Aman
        async function initSignagePlayer() {
            const statusText = document.getElementById('status-text');

            try {
                // 1. Coba ambil data playlist terbaru dari server admin
                const response = await fetch('/api/signage/playlist');
                if (!response.ok) throw new Error('Gagal terhubung ke server');

                const playlist = await response.json();
                console.log("Playlist dari server:", playlist);

                // Jika playlist items kosong
                if (!playlist.items || playlist.items.length === 0) {
                    statusText.textContent = "Playlist kosong.";
                    return;
                }

                // 2. Simpan metadata playlist ke localStorage untuk cadangan offline
                localStorage.setItem('cached_playlist', JSON.stringify(playlist));

                // 3. Download dan simpan fisik file media ke Cache Storage browser TV secara aman
                if ('caches' in window) {
                    const cache = await caches.open(CACHE_NAME);
                    statusText.textContent = "Mengunduh aset media ke memori TV...";

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
                    console.log("Proses caching aset selesai.");
                }

                // Jalankan pemutaran playlist
                startLoop(playlist.items);

            } catch (error) {
                console.warn("Mode Offline Aktif / Gagal Fetch:", error);
                statusText.textContent = "Koneksi terputus. Memuat dari Cache Offline...";

                // FALLBACK: Ambil data dari localStorage saat offline atau gagal fetch (termasuk saat di-refresh)
                const cachedData = localStorage.getItem('cached_playlist');
                if (cachedData) {
                    const playlist = JSON.parse(cachedData);
                    if (playlist.items && playlist.items.length > 0) {
                        startLoop(playlist.items);
                        return;
                    }
                }

                statusText.textContent = "Tidak ada koneksi internet & cache lokal kosong.";
            }
        }

        // Fungsi Looping Pemutaran Media
        function startLoop(items) {
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
                    const img = document.createElement('img');
                    img.src = item.url; // Browser otomatis mengambil dari cache jika offline
                    img.className = 'w-full h-full object-cover';
                    container.appendChild(img);

                    setTimeout(() => {
                        currentIndex = (currentIndex + 1) % items.length;
                        playNext();
                    }, (item.duration || 10) * 1000);

                } else if (item.type === 'video') {
                    const video = document.createElement('video');
                    video.src = item.url; // Browser otomatis mengambil dari cache jika offline
                    video.className = 'w-full h-full object-cover';
                    video.autoplay = true;
                    video.muted = false; // Tizen SSSP (signage) umumnya tidak strict soal autoplay+audio seperti Chrome desktop
                    video.playsInline = true;
                    container.appendChild(video);

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
            initSignagePlayer();
        };
    </script>
</body>

</html>