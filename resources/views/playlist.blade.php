@extends('layouts.app')

@section('content')
    <main class="flex-1 p-6 lg:p-10 max-w-[1400px] w-full mx-auto">

        <!-- ALERT BERHASIL -->
        @if (session('success'))
            <div id="success-alert"
                class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl text-xs flex items-center justify-between shadow-sm transition-opacity duration-500">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                    <div>
                        <span class="font-bold">Berhasil!</span> {{ session('success') }}
                    </div>
                </div>
                <button type="button" onclick="closeAlert()"
                    class="text-emerald-600 hover:text-emerald-900 focus:outline-none p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        @endif

        <!-- ALERT ERROR DUPLIKAT -->
        <div id="duplicate-alert"
            class="hidden mb-6 bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl text-xs flex items-center justify-between shadow-sm transition-opacity duration-500">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-base"></i>
                <div>
                    <span class="font-bold">Perhatian:</span> Konten ini sudah ada di dalam playlist! Tidak boleh memilih
                    file yang sama dua kali.
                </div>
            </div>
            <button type="button" onclick="closeDuplicateAlert()"
                class="text-red-600 hover:text-red-900 focus:outline-none p-1">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="{{ route('playlist.store') }}" method="POST">
            @csrf

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 lg:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- KOLOM KIRI -->
                    <div class="lg:col-span-7 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-uc-dark mb-2">Playlist Date</label>
                            <input type="date" name="playlist_date" min="{{ date('Y-m-d') }}"
                                class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-3.5 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                                required>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-uc-dark">Total Content: <span
                                    id="content-count">0</span></label>

                            <div id="content-list" class="space-y-3 min-h-[50px]">
                                <!-- Item dinamis -->
                            </div>

                            <button type="button" onclick="openContentModal()"
                                class="bg-uc-orange hover:bg-orange-600 text-white font-semibold px-6 py-3 rounded-xl text-xs transition-all shadow-sm">
                                + Add Content
                            </button>
                        </div>

                        <!-- Total Duration Otomatis -->
                        <div>
                            <label class="block text-xs font-bold text-uc-dark mb-2">Total Duration</label>
                            <input type="text" id="total-duration-input" name="playlist_duration_formatted"
                                value="00:00" readonly
                                class="w-full bg-gray-100 border border-gray-200 rounded-2xl px-5 py-3.5 text-xs text-gray-500 font-semibold cursor-not-allowed">
                        </div>
                    </div>

                    <!-- KOLOM KANAN (Sleek Samsung Signage 24" Frame - Disamakan dengan Dashboard) -->
                    <div class="lg:col-span-5 flex flex-col items-center">

                        <!-- SAMSUNG SIGNAGE 24" (Siku-siku & Warna Gelap Konsisten) -->
                        <div
                            class="w-full max-w-[420px] aspect-[9/16] bg-slate-900 p-2 border-4 border-slate-800 rounded-none relative flex items-center justify-center shadow-2xl">

                            <!-- Inner Screen -->
                            <div
                                class="w-full h-full bg-slate-950 border border-slate-800 overflow-hidden relative flex items-center justify-center">

                                <!-- Placeholder View (Teks disesuaikan: "Pilih konten untuk preview", tanpa Live Preview) -->
                                <div id="preview-placeholder"
                                    class="text-center p-6 flex flex-col items-center justify-center h-full space-y-3">
                                    <i class="fa-solid fa-tv text-4xl text-gray-600 mb-1"></i>
                                    <div class="space-y-1">
                                        <h4 class="text-sm font-bold text-white tracking-wide">Samsung Signage 24"</h4>
                                        <p id="preview-title" class="text-xs text-gray-400 font-normal">Pilih konten untuk
                                            preview</p>
                                    </div>
                                </div>

                                <!-- Video Player (Mendukung Video & Gambar dengan object-contain) -->
                                <video id="preview-video" class="w-full h-full object-contain bg-black hidden" controls
                                    autoplay muted disablePictureInPicture controlslist="nodownload">
                                    Your browser does not support the video tag.
                                </video>

                                <img id="preview-image" src="" alt="Preview Banner"
                                    class="w-full h-full object-contain hidden">

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="flex justify-end items-center space-x-4 mt-8">
                <a href="{{ route('dashboard') }}"
                    class="bg-red-400 hover:bg-red-500 text-white font-semibold px-8 py-3.5 rounded-xl text-xs transition-all shadow-sm text-center">
                    Cancel
                </a>
                <button type="submit"
                    class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-10 py-3.5 rounded-xl text-xs transition-all shadow-sm">
                    Save
                </button>
            </div>
        </form>
    </main>

    <!-- MODAL PILIH KONTEN -->
    <div id="content-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[80vh] flex flex-col">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-sm font-bold text-uc-dark">Pilih Konten dari Database</h3>
                <button type="button" onclick="closeContentModal()" class="text-gray-400 hover:text-red-500 p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="overflow-y-auto space-y-4 flex-1 pr-1">
                <!-- EVENT -->
                <div>
                    <h4
                        class="text-[11px] font-bold text-uc-orange uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-star"></i> Konten Event
                    </h4>
                    <div class="space-y-2">
                        @php $hasEvent = false; @endphp
                        @foreach ($contents as $content)
                            @if (strtolower($content->content_category ?? '') == 'event')
                                @php $hasEvent = true; @endphp
                                <div class="modal-item flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:bg-orange-50 transition-all cursor-pointer"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }}
                                            | Durasi: {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span class="bg-uc-orange text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasEvent)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada konten event tersedia.</p>
                        @endif
                    </div>
                </div>

                <!-- DAILY -->
                <div class="pt-2">
                    <h4 class="text-[11px] font-bold text-uc-green uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        Konten Daily
                    </h4>
                    <div class="space-y-2">
                        @php $hasDaily = false; @endphp
                        @foreach ($contents as $content)
                            @if (strtolower($content->content_category ?? '') == 'daily' || empty($content->content_category))
                                @php $hasDaily = true; @endphp
                                <div class="modal-item flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:bg-emerald-50 transition-all cursor-pointer"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }}
                                            | Durasi: {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span class="bg-uc-green text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasDaily)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada konten daily tersedia.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sortable-drag {
            opacity: 0 !important;
        }

        .sortable-ghost {
            opacity: 0.3;
            background-color: #fff7ed !important;
            border: 2px dashed #f97316 !important;
        }

        .content-item {
            cursor: default;
        }

        .drag-handle {
            cursor: grab !important;
        }

        .drag-handle:active {
            cursor: grabbing !important;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <script>
        let playlistQueue = [];
        let currentPlaylistIndex = 0;
        let imageTimer = null;

        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('content-list');
            Sortable.create(el, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.drag-handle',
                onEnd: function() {
                    rebuildPlaylistQueue();
                }
            });
            updateContentCount();
        });

        function openContentModal() {
            document.getElementById('content-modal').classList.remove('hidden');
        }

        function closeContentModal() {
            document.getElementById('content-modal').classList.add('hidden');
        }

        function addContentToPlaylist(id, title, url, isImage, duration) {
            const existingInputs = document.querySelectorAll('input[name="contents[]"]');
            for (let input of existingInputs) {
                if (input.value === id) {
                    closeContentModal();
                    showDuplicateAlert();
                    return;
                }
            }

            const listContainer = document.getElementById('content-list');
            const newItem = document.createElement('div');
            newItem.className =
                "content-item flex items-center justify-between bg-white border border-gray-200 rounded-2xl px-5 py-3.5 shadow-xs hover:border-gray-300 transition-all";

            newItem.setAttribute('data-url', url);
            newItem.setAttribute('data-title', title);
            newItem.setAttribute('data-is-image', isImage ? '1' : '0');
            newItem.setAttribute('data-duration', duration);

            newItem.innerHTML = `
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-grip-vertical text-gray-400 text-xs drag-handle"></i>
                <span class="text-xs font-medium text-uc-dark content-name">${title} (${duration}s)</span>
            </div>
            <div class="flex items-center space-x-3">
                <input type="hidden" name="contents[]" value="${id}">
                <button type="button" onclick="removeContentItem(this, event)" class="text-gray-400 hover:text-red-500 transition-colors p-1 focus:outline-none">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        `;

            listContainer.appendChild(newItem);
            updateContentCount();
            closeContentModal();

            rebuildPlaylistQueue();
            if (playlistQueue.length === 1) {
                currentPlaylistIndex = 0;
                playCurrentQueueItem();
            }
        }

        function removeContentItem(button, event) {
            event.stopPropagation();
            const item = button.closest('.content-item');
            if (item) {
                item.remove();
                updateContentCount();
                rebuildPlaylistQueue();

                if (playlistQueue.length > 0) {
                    currentPlaylistIndex = 0;
                    playCurrentQueueItem();
                } else {
                    resetPreviewPlayer();
                }
            }
        }

        function rebuildPlaylistQueue() {
            playlistQueue = [];
            let totalSeconds = 0;

            const items = document.querySelectorAll('#content-list .content-item');
            items.forEach(item => {
                const dur = parseInt(item.getAttribute('data-duration')) || 5;
                totalSeconds += dur;

                playlistQueue.push({
                    title: item.getAttribute('data-title'),
                    url: item.getAttribute('data-url'),
                    isImage: item.getAttribute('data-is-image') === '1',
                    duration: dur
                });
            });

            updateTotalDurationDisplay(totalSeconds);
        }

        function updateTotalDurationDisplay(totalSeconds) {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            const formatted = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            document.getElementById('total-duration-input').value = formatted;
        }

        function playCurrentQueueItem() {
            const titleEl = document.getElementById('preview-title');
            const videoEl = document.getElementById('preview-video');
            const imageEl = document.getElementById('preview-image');
            const placeholderEl = document.getElementById('preview-placeholder');

            if (imageTimer) {
                clearTimeout(imageTimer);
                imageTimer = null;
            }
            videoEl.pause();

            if (playlistQueue.length === 0) {
                resetPreviewPlayer();
                return;
            }

            if (currentPlaylistIndex >= playlistQueue.length) {
                currentPlaylistIndex = 0;
            }

            const currentItem = playlistQueue[currentPlaylistIndex];
            titleEl.textContent = `Preview: ${currentItem.title} (${currentPlaylistIndex + 1}/${playlistQueue.length})`;

            placeholderEl.classList.add('hidden');

            if (currentItem.isImage) {
                videoEl.classList.add('hidden');
                imageEl.src = currentItem.url;
                imageEl.classList.remove('hidden');

                imageTimer = setTimeout(() => {
                    currentPlaylistIndex++;
                    playCurrentQueueItem();
                }, currentItem.duration * 1000);

            } else {
                imageEl.classList.add('hidden');
                videoEl.src = currentItem.url;
                videoEl.classList.remove('hidden');

                videoEl.play().catch(e => console.log("Autoplay dicegah browser:", e));

                videoEl.onended = function() {
                    currentPlaylistIndex++;
                    playCurrentQueueItem();
                };
            }
        }

        function resetPreviewPlayer() {
            if (imageTimer) clearTimeout(imageTimer);
            const videoEl = document.getElementById('preview-video');
            const imageEl = document.getElementById('preview-image');
            const placeholderEl = document.getElementById('preview-placeholder');
            const titleEl = document.getElementById('preview-title');

            videoEl.pause();
            videoEl.src = '';
            videoEl.classList.add('hidden');
            imageEl.src = '';
            imageEl.classList.add('hidden');
            placeholderEl.classList.remove('hidden');
            titleEl.textContent = "Pilih konten untuk preview"; // Teks disesuaikan
            updateTotalDurationDisplay(0);
        }

        function updateContentCount() {
            const listContainer = document.getElementById('content-list');
            const countLabel = document.getElementById('content-count');
            countLabel.textContent = listContainer.querySelectorAll('.content-item').length;
        }

        function closeAlert() {
            const alertBox = document.getElementById('success-alert');
            if (alertBox) {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }
        }

        function showDuplicateAlert() {
            const alertBox = document.getElementById('duplicate-alert');
            alertBox.classList.remove('hidden');
            alertBox.style.opacity = '1';
            setTimeout(() => closeDuplicateAlert(), 5000);
        }

        function closeDuplicateAlert() {
            const alertBox = document.getElementById('duplicate-alert');
            if (alertBox) {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.classList.add('hidden'), 500);
            }
        }
    </script>
@endsection
