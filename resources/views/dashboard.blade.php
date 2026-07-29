@extends('layouts.app')

@section('content')
    <!-- CSS KUSTOM MURNI UNTUK SWEETALERT -->
    <style>
        .swal-custom-popup {
            border-radius: 1rem !important;
            padding: 1.25em !important;
            font-family: inherit !important;
        }

        .swal-custom-title {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
            color: #1F2937 !important;
        }

        .swal-custom-html {
            font-size: 0.875rem !important;
            color: #4B5563 !important;
        }

        .swal-btn-confirm-delete {
            background-color: #F27D00 !important;
            /* uc-orange */
            color: #FFFFFF !important;
            padding: 8px 16px !important;
            border-radius: 10px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            cursor: pointer;
        }

        .swal-btn-confirm-recover {
            background-color: #0084FF !important;
            /* uc-blue */
            color: #FFFFFF !important;
            padding: 8px 16px !important;
            border-radius: 10px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            cursor: pointer;
        }

        .swal-btn-cancel {
            background-color: #6B7280 !important;
            /* abu-abu kalem */
            color: #FFFFFF !important;
            padding: 8px 16px !important;
            border-radius: 10px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
            margin-right: 12px !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            cursor: pointer;
        }
    </style>

    <div class="max-w-[1600px] w-full mx-auto p-6 lg:p-8 space-y-6">
        <!-- ALERT BERHASIL -->
        @if (session('success'))
            <div id="success-alert"
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl text-xs flex items-center justify-between shadow-sm transition-opacity duration-500">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                    <div><span class="font-bold">Berhasil!</span> {{ session('success') }}</div>
                </div>
                <button type="button" onclick="this.parentElement.remove();" class="text-emerald-600 hover:text-emerald-900">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        @endif

        <!-- Greeting -->
        <h1 class="text-2xl font-bold text-uc-dark">Halo, {{ $firstName }}!</h1>

        <!-- ================= BARIS ATAS: Statistik/Card & Preview Signage ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- KOLOM KIRI ATAS (3 Card Statistik & Tabel Playlists) - 8 Cols -->
            <div class="lg:col-span-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Total Content Card -->
                    <div class="bg-uc-blue text-white rounded-2xl p-5 shadow-sm flex flex-col justify-between h-32">
                        <span class="text-xs font-medium opacity-90">Total Content</span>
                        <div class="text-2xl font-bold text-center my-auto">
                            {{ $totalContent }} Videos
                        </div>
                    </div>
                    <!-- Active Playlist Card -->
                    <div class="bg-uc-green text-white rounded-2xl p-5 shadow-sm flex flex-col justify-between h-32">
                        <span class="text-xs font-medium opacity-90">Active Playlist</span>
                        <div class="text-2xl font-bold text-center my-auto">
                            @if (isset($currentSignage) && $currentSignage && $currentSignage->playlist_id)
                                Playlist {{ $currentSignage->playlist_id }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <!-- Average Playtime Card -->
                    <div class="bg-uc-purple text-white rounded-2xl p-5 shadow-sm flex flex-col justify-between h-32">
                        <span class="text-xs font-medium opacity-90">Average Playtime</span>
                        <div class="text-2xl font-bold text-center my-auto">
                            {{ $averagePlaytime }} second / Video
                        </div>
                    </div>
                </div>

                <!-- TABEL PLAYLISTS UTAMA DI ATAS -->
                <div class="space-y-3">
                    <!-- Tab Switcher Buttons -->
                    <div class="flex space-x-2">
                        <button onclick="switchTab('playlists')" id="tab-playlists-btn"
                            class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-uc-dark text-white shadow-sm">
                            Playlists
                        </button>
                        <button onclick="switchTab('deleted')" id="tab-deleted-btn"
                            class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-gray-200 text-gray-600 hover:bg-gray-300">
                            Deleted Playlists
                        </button>
                    </div>

                    <!-- TAB 1: PLAYLISTS AKTIF -->
                    <div id="tab-playlists-content">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-uc-yellow text-uc-dark font-semibold">
                                            <th class="p-3 border-b">ID</th>
                                            <th class="p-3 border-b">Tanggal</th>
                                            <th class="p-3 border-b text-center">Order</th>
                                            <th class="p-3 border-b">Judul Konten</th>
                                            <th class="p-3 border-b">Durasi</th>
                                            <th class="p-3 border-b text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-gray-700">
                                        @php
                                            $groupedPlaylists = $playlistsData->groupBy('playlist_id');
                                        @endphp
                                        @forelse($groupedPlaylists as $playlistId => $items)
                                            @foreach ($items as $index => $item)
                                                <tr class="playlist-row cursor-pointer hover:bg-amber-50/40 transition-colors"
                                                    data-playlist-id="{{ $playlistId }}">
                                                    @if ($index === 0)
                                                        <td class="p-3 font-semibold text-uc-dark align-middle border-r border-gray-50 bg-gray-50/50"
                                                            rowspan="{{ count($items) }}">
                                                            #P{{ $item->playlist_id }}
                                                        </td>
                                                        <td class="p-3 align-middle border-r border-gray-50 bg-gray-50/50"
                                                            rowspan="{{ count($items) }}">
                                                            {{ date('d/m/Y', strtotime($item->playlist_date)) }}
                                                        </td>
                                                    @endif
                                                    <td class="p-3 text-center font-bold text-uc-orange">
                                                        {{ $item->playlist_order }}</td>
                                                    <td class="p-3 font-medium text-uc-dark">{{ $item->content_title }}
                                                    </td>
                                                    <td class="p-3 text-uc-gray">{{ $item->content_duration }}s</td>
                                                    @if ($index === 0)
                                                        <td class="p-3 text-center align-middle bg-gray-50/50 space-y-2"
                                                            rowspan="{{ count($items) }}">
                                                            @php
                                                                $playlistVideos = $items
                                                                    ->map(function ($i) {
                                                                        $filePath = $i->content_file_path_url ?? '';
                                                                        $fullUrl =
                                                                            str_starts_with($filePath, 'http://') ||
                                                                            str_starts_with($filePath, 'https://')
                                                                                ? $filePath
                                                                                : asset(
                                                                                    'storage/' . ltrim($filePath, '/'),
                                                                                );
                                                                        $extension = strtolower(
                                                                            pathinfo($filePath, PATHINFO_EXTENSION),
                                                                        );
                                                                        $isImage = in_array($extension, [
                                                                            'jpg',
                                                                            'jpeg',
                                                                            'png',
                                                                            'gif',
                                                                            'webp',
                                                                        ]);
                                                                        return [
                                                                            'url' => $fullUrl,
                                                                            'title' => $i->content_title,
                                                                            'duration' =>
                                                                                $i->content_duration ??
                                                                                ($isImage ? 5 : 10),
                                                                        ];
                                                                    })
                                                                    ->values();
                                                            @endphp
                                                            <!-- Tombol Show Now -->
                                                            <button type="button"
                                                                onclick="startPlaylist({{ json_encode($playlistVideos) }}, '{{ $playlistId }}')"
                                                                class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-3 py-1.5 rounded-xl transition-all active:scale-95 flex items-center justify-center space-x-1.5 mx-auto text-[11px] shadow-sm w-full">
                                                                <i class="fa-solid fa-play text-[9px]"></i>
                                                                <span>Show Now</span>
                                                            </button>
                                                            <!-- Tombol Delete Terintegrasi SweetAlert2 -->
                                                            <form id="delete-form-{{ $playlistId }}"
                                                                action="{{ route('playlist.destroy', $playlistId) }}"
                                                                method="POST" class="inline-block w-full">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="button"
                                                                    onclick="confirmDelete('{{ $playlistId }}')"
                                                                    class="bg-[#EB5F5F] hover:bg-red-600 text-white font-semibold px-3 py-1.5 rounded-xl transition-all active:scale-95 flex items-center justify-center space-x-1.5 mx-auto text-[11px] shadow-sm w-full">
                                                                    <i class="fa-solid fa-trash-can text-[9px]"></i>
                                                                    <span>Delete</span>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="6" class="p-6 text-center text-gray-400">Belum ada playlist
                                                    aktif saat ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DELETED PLAYLISTS -->
                    <div id="tab-deleted-content" class="hidden">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-[#EB5F5F] text-white font-semibold">
                                            <th class="p-3 border-b">ID</th>
                                            <th class="p-3 border-b">Tanggal</th>
                                            <th class="p-3 border-b text-center">Total Items</th>
                                            <th class="p-3 border-b text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-gray-700">
                                        @forelse($trashedPlaylists ?? [] as $trash)
                                            <tr class="hover:bg-red-50/40 transition-colors">
                                                <td class="p-3 font-semibold text-uc-dark">#P{{ $trash->playlist_id }}</td>
                                                <td class="p-3">{{ date('d/m/Y', strtotime($trash->playlist_date)) }}
                                                </td>
                                                <td class="p-3 text-center font-bold">{{ $trash->details->count() }} Items
                                                </td>
                                                <td class="p-3 text-center">
                                                    <form id="recover-form-{{ $trash->playlist_id }}"
                                                        action="{{ route('playlist.restore', $trash->playlist_id) }}"
                                                        method="POST" class="inline-block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="button"
                                                            onclick="confirmRecover('{{ $trash->playlist_id }}')"
                                                            class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-3 py-1.5 rounded-xl transition-all active:scale-95 inline-flex items-center space-x-1 text-[11px] shadow-sm">
                                                            <i class="fa-solid fa-rotate-left"></i>
                                                            <span>Recover</span>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="p-6 text-center text-gray-400">Sampah kosong.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN ATAS (Samsung Signage Preview) - 4 Cols -->
            <div class="lg:col-span-4 flex flex-col items-center justify-center w-full">
                <!-- Status Badge -->
                <div class="flex items-center space-x-2 text-xs font-semibold text-uc-dark mb-3 justify-center w-full">
                    <span class="w-2.5 h-2.5 rounded-full bg-uc-green animate-pulse"></span>
                    <span id="playing-status-title">Now Playing: Standby</span>
                </div>
                <!-- SAMSUNG SIGNAGE 24" -->
                <div
                    class="w-full max-w-[280px] aspect-[9/16] bg-slate-900 p-2 border-4 border-slate-800 relative flex items-center justify-center shadow-xl">
                    <!-- Inner Screen -->
                    <div
                        class="w-full h-full bg-slate-950 border border-slate-800 overflow-hidden relative flex items-center justify-center">
                        <!-- Placeholder View -->
                        <div id="tv-placeholder" class="text-center p-5 flex flex-col items-center justify-center h-full">
                            <i class="fa-solid fa-tv text-3xl text-gray-600 mb-3"></i>
                            <p class="text-xs text-gray-300 font-medium mb-1">Samsung Signage 24"</p>
                            <p class="text-[10px] text-gray-500">Klik <span class="text-uc-green font-semibold">Show
                                    Now</span> untuk memutar playlist</p>
                        </div>
                        <!-- Video Player -->
                        <video id="tv-video-player" class="w-full h-full object-contain bg-black hidden" playsinline muted
                            controls disablePictureInPicture controlslist="nodownload noplaybackrate">
                            <source id="tv-video-source" src="" type="video/mp4">
                        </video>
                        <!-- Image Player -->
                        <img id="tv-image-player" src="" alt="Signage Image"
                            class="w-full h-full object-contain bg-black hidden">
                    </div>
                </div>
                <!-- Tombol Show ke Signage -->
                <div class="mt-4 text-center w-full max-w-[280px]">
                    <button id="btnShowSignage"
                        class="w-full py-3 px-6 text-white font-semibold rounded-xl shadow-lg transition duration-300 transform active:scale-95 text-xs"
                        style="background: linear-gradient(135deg, #00b4db, #0083b0);">
                        Show to Signage
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= BARIS BAWAH: Tabel Konten & Tabel Riwayat Signage (Tinggi Sama & Scrollable) ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- KOLOM KIRI BAWAH (Daftar Konten & Pengunggah) - 8 Cols -->
            <div class="lg:col-span-8 space-y-3 flex flex-col">
                <h3 class="text-xs font-bold text-uc-dark flex items-center space-x-2 px-1">
                    <span>Daftar Konten & Pengunggah</span>
                </h3>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[280px]">
                    <div class="overflow-x-auto overflow-y-auto flex-1">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-uc-blue text-white font-semibold">
                                    <th class="p-3">Judul Konten</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-right">Pengunggah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @php
                                    $sortedContents = isset($allContents)
                                        ? $allContents->sortByDesc(function ($item) {
                                            return $item->created_at ?? ($item->content_id ?? 0);
                                        })
                                        : collect();
                                @endphp
                                @forelse($sortedContents as $content)
                                    <tr class="hover:bg-blue-50/40 transition-colors">
                                        <td class="p-3 font-medium text-uc-dark">{{ $content->content_title }}</td>
                                        <td class="p-3 text-gray-500">{{ $content->content_category }}</td>
                                        <td class="p-3 text-right">
                                            <span
                                                class="inline-flex items-center space-x-1 bg-gray-100 text-gray-700 px-2.5 py-1 rounded-full font-medium">
                                                <i class="fa-solid fa-user text-[10px]"></i>
                                                <span>{{ $content->users_name }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-6 text-center text-gray-400 italic">Belum ada konten
                                            yang di-upload.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN BAWAH (Riwayat Signage History) - 4 Cols -->
            <div class="lg:col-span-4 space-y-3 flex flex-col w-full">
                <h3 class="text-xs font-bold text-uc-dark flex items-center space-x-2 px-1">
                    <span>Riwayat Signage (History)</span>
                </h3>

                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[280px] w-full">
                    <div class="overflow-x-auto overflow-y-auto flex-1">
                        <table class="w-full text-left text-xs">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-uc-purple text-white font-semibold">
                                    <th class="p-3">Playlist</th>
                                    <th class="p-3">User</th>
                                    <th class="p-3 text-right">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @php
                                    $signageHistories = isset($allSignageHistories) ? $allSignageHistories : collect();
                                @endphp
                                @forelse($signageHistories as $history)
                                    <tr class="hover:bg-purple-50/40 transition-colors">
                                        <td class="p-3 font-semibold text-uc-dark">#P{{ $history->playlist_id }}</td>
                                        <td class="p-3 text-gray-800 truncate max-w-[80px]"
                                            title="{{ $history->status_updated_by ?? '-' }}">
                                            {{ $history->status_updated_by ?? '-' }}
                                        </td>
                                        <td class="p-3 text-right text-gray-500 text-[10px]">
                                            {{ $history->status_updated_at ? date('d/m/Y H:i:s', strtotime($history->status_updated_at)) : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-6 text-center text-gray-400 italic">Belum ada riwayat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>

    <!-- SCRIPT TAB SWITCHER, SWEETALERT CONFIRM, & PLAYER -->
    <script>
        function switchTab(tabName) {
            const playlistsTabBtn = document.getElementById('tab-playlists-btn');
            const deletedTabBtn = document.getElementById('tab-deleted-btn');
            const playlistsContent = document.getElementById('tab-playlists-content');
            const deletedContent = document.getElementById('tab-deleted-content');

            if (tabName === 'playlists') {
                playlistsTabBtn.className =
                    "px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-uc-dark text-white shadow-sm";
                deletedTabBtn.className =
                    "px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-gray-200 text-gray-600 hover:bg-gray-300";

                playlistsContent.classList.remove('hidden');
                deletedContent.classList.add('hidden');
            } else {
                deletedTabBtn.className =
                    "px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-uc-dark text-white shadow-sm";
                playlistsTabBtn.className =
                    "px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-gray-200 text-gray-600 hover:bg-gray-300";

                deletedContent.classList.remove('hidden');
                playlistsContent.classList.add('hidden');
            }
        }

        // SweetAlert2 Konfirmasi Hapus
        function confirmDelete(playlistId) {
            Swal.fire({
                title: 'Pindahkan ke sampah?',
                text: "Playlist akan dipindahkan ke daftar Deleted Playlists.",
                icon: 'warning',
                width: '380px',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    popup: 'swal-custom-popup',
                    title: 'swal-custom-title',
                    htmlContainer: 'swal-custom-html',
                    confirmButton: 'swal-btn-confirm-delete',
                    cancelButton: 'swal-btn-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + playlistId).submit();
                }
            });
        }

        // SweetAlert2 Konfirmasi Recover
        function confirmRecover(playlistId) {
            Swal.fire({
                title: 'Pulihkan playlist?',
                text: "Playlist akan dikembalikan ke daftar aktif.",
                icon: 'question',
                width: '380px',
                showCancelButton: true,
                confirmButtonText: 'Ya, pulihkan!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    popup: 'swal-custom-popup',
                    title: 'swal-custom-title',
                    htmlContainer: 'swal-custom-html',
                    confirmButton: 'swal-btn-confirm-recover',
                    cancelButton: 'swal-btn-cancel'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('recover-form-' + playlistId).submit();
                }
            });
        }

        let currentPlaylist = [];
        let currentIndex = 0;
        let currentPlaylistId = '';
        let imageTimer = null;
        let selectedPlaylistId = null; // <-- Di sini tempat deklarasi variabel pilihan playlist

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.playlist-row').forEach(row => {
                row.addEventListener('click', function() {
                    // Ambil ID playlist dari atribut data
                    selectedPlaylistId = this.getAttribute('data-playlist-id');
                });
            });
        });

        function startPlaylist(videos, playlistId) {
            if (!videos || videos.length === 0) return;

            currentPlaylist = videos;
            currentIndex = 0;
            currentPlaylistId = playlistId;

            if (imageTimer) clearTimeout(imageTimer);

            playCurrentItem();
        }

        function playCurrentItem() {
            const placeholder = document.getElementById('tv-placeholder');
            const videoPlayer = document.getElementById('tv-video-player');
            const videoSource = document.getElementById('tv-video-source');
            const imagePlayer = document.getElementById('tv-image-player');
            const statusTitle = document.getElementById('playing-status-title');

            const item = currentPlaylist[currentIndex];

            placeholder.classList.add('hidden');
            statusTitle.innerText = `Now Playing: Playlist #P${currentPlaylistId}`;

            const extension = item.url.split('.').pop().split('?')[0].toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);

            if (isImage) {
                videoPlayer.classList.add('hidden');
                videoPlayer.pause();

                imagePlayer.src = item.url;
                imagePlayer.classList.remove('hidden');

                let duration = (item.duration || 5) * 1000;

                if (imageTimer) clearTimeout(imageTimer);
                imageTimer = setTimeout(() => {
                    currentIndex = (currentIndex + 1) % currentPlaylist.length;
                    playCurrentItem();
                }, duration);

            } else {
                imagePlayer.classList.add('hidden');
                videoPlayer.classList.remove('hidden');

                videoSource.src = item.url;
                videoPlayer.load();

                videoPlayer.play().catch(error => {
                    videoPlayer.muted = true;
                    videoPlayer.play();
                });

                videoPlayer.onended = function() {
                    currentIndex = (currentIndex + 1) % currentPlaylist.length;
                    playCurrentItem();
                };
            }
        }
    </script>

    <script>
        // 1. Variabel dan script klik tabel
        let selectedPlaylistId = null;

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.playlist-row').forEach(row => {
                row.addEventListener('click', function() {
                    let rawId = this.getAttribute('data-playlist-id');
                    selectedPlaylistId = rawId.replace('#', '');
                });
            });
        });
    </script>

    <!-- Script SweetAlert & AJAX untuk Tombol Show to Signage -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('btnShowSignage').addEventListener('click', function() {
            if (!selectedPlaylistId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Playlist Dulu',
                    text: 'Silakan klik salah satu playlist dari tabel untuk melihat preview.',
                    confirmButtonColor: '#0083b0'
                });
                return;
            }

            // <--- TARUH / TIMPA KODE YANG BARU DI DALAM SINI --->
            Swal.fire({
                title: 'Tampilkan ke Signage?',
                text: "Apakah Anda yakin ingin menampilkan playlist ini ke layar TV signage sekarang?",
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true, // Tombol batal di kiri
                confirmButtonColor: '#0083b0',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Tampilkan Sekarang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();

                    fetch(`/dashboard/show/${selectedPlaylistId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(async response => {
                            const isJson = response.headers.get('content-type')?.includes(
                                'application/json');
                            const data = isJson ? await response.json() : null;

                            if (!response.ok) {
                                const errorMsg = data && data.message ? data.message :
                                    'Server error status: ' + response.status;
                                throw new Error(errorMsg);
                            }
                            return data;
                        })
                        .then(data => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message ||
                                    'Playlist berhasil ditampilkan ke signage.',
                            }).then(() => {
                                location.reload();
                            });
                        })
                        .catch(error => {
                            console.error('Error detail:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: error.message || 'Terjadi kesalahan sistem.',
                            });
                        });
                }
            });
        });
    </script>
@endsection
