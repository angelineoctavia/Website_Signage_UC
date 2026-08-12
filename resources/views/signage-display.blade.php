<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seamless Digital Signage Display</title>
    <style>
        body, html { 
            margin: 0; padding: 0; width: 100%; height: 100%; 
            background: #000; overflow: hidden; 
        }
        .stage { 
            position: relative; width: 100vw; height: 100vh; 
            background: #000; display: flex; justify-content: center; align-items: center; 
        }
        /* Dua player ditumpuk agar transisinya instan tanpa kedip */
        video, img { 
            position: absolute; width: 100%; height: 100%; 
            object-fit: contain; opacity: 0; transition: opacity 0.1s ease; 
        }
        video.active, img.active { 
            opacity: 1; z-index: 2; 
        }
    </style>
</head>
<body>

    <div class="stage" id="stage">
        <!-- Player 1 & 2 untuk bergantian secara mulus -->
        <video id="player1" muted playsinline></video>
        <video id="player2" muted playsinline></video>
        <img id="playerImg" alt="Image Slide">
    </div>

    <script>
        let playlist = [];
        let currentIndex = 0;
        let activePlayer = 1; // Menandai player video mana yang sedang aktif (1 atau 2)
        let imgTimer = null;

        // 1. Ambil data playlist terbaru secara berkala tanpa reload halaman
        async function fetchPlaylist() {
            try {
                let response = await fetch('/api/signage-active');
                let data = await response.json();
                
                if (JSON.stringify(data.media) !== JSON.stringify(playlist)) {
                    console.log("Playlist diperbarui secara real-time!");
                    playlist = data.media;
                    
                    // Jika sebelumnya kosong dan sekarang ada isinya, langsung jalankan
                    let v1 = document.getElementById('player1');
                    let v2 = document.getElementById('player2');
                    let img = document.getElementById('playerImg');
                    
                    if (v1.paused && v2.paused && !img.classList.contains('active') && playlist.length > 0) {
                        currentIndex = 0;
                        playMedia();
                    }
                }
            } catch (error) {
                console.error("Gagal sinkronisasi playlist:", error);
            }
        }

        function playMedia() {
            if (!playlist || playlist.length === 0) {
                document.getElementById('stage').innerHTML = '<h2 style="color:white; font-family:sans-serif; z-index:10;">Tidak ada playlist aktif</h2>';
                return;
            }

            if (currentIndex >= playlist.length) {
                currentIndex = 0; // Looping kembali ke awal playlist
            }

            let currentItem = playlist[currentIndex];
            let v1 = document.getElementById('player1');
            let v2 = document.getElementById('player2');
            let img = document.getElementById('playerImg');

            clearTimeout(imgTimer);

            if (currentItem.type === 'video') {
                // Sembunyikan gambar
                img.classList.remove('active');

                // Tentukan player mana yang sekarang aktif dan mana yang menyiapkan video berikutnya (Double Buffering)
                let currentVideoNode = (activePlayer === 1) ? v1 : v2;
                let nextVideoNode = (activePlayer === 1) ? v2 : v1;

                // Muat video baru ke player cadangan
                nextVideoNode.src = currentItem.url;
                nextVideoNode.load();
                
                nextVideoNode.oncanplaythrough = function() {
                    nextVideoNode.play().then(() => {
                        // Tampilkan player baru secara instan, sembunyikan yang lama
                        nextVideoNode.classList.add('active');
                        currentVideoNode.classList.remove('active');
                        currentVideoNode.pause();

                        // Tukar status active player
                        activePlayer = (activePlayer === 1) ? 2 : 1;
                    }).catch(e => console.log("Autoplay blocked/error:", e));
                };

                // Ketika video habis, lanjut ke index berikutnya
                nextVideoNode.onended = function() {
                    currentIndex++;
                    playMedia();
                };

                nextVideoNode.onerror = function() {
                    currentIndex++;
                    playMedia();
                };

            } else {
                // Jika isinya adalah Gambar (Image)
                v1.classList.remove('active');
                v2.classList.remove('active');
                v1.pause();
                v2.pause();

                img.src = currentItem.url;
                img.classList.add('active');

                let duration = (currentItem.duration || 5) * 1000;
                imgTimer = setTimeout(() => {
                    currentIndex++;
                    playMedia();
                }, duration);
            }
        }

        // Inisialisasi awal
        fetchPlaylist().then(() => {
            playMedia();
        });

        // Polling ke server tiap 30 detik di background (tanpa kedip/reload)
        setInterval(fetchPlaylist, 30000);
    </script>
</body>
</html>