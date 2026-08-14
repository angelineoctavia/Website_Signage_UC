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
        @if ($errors->any())
            <div
                class="mb-6 bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl text-xs flex items-center space-x-3 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-base"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif
        <form action="{{ $editMode ? route('playlist.update', $playlist->playlist_id) : route('playlist.store') }}"
            method="POST">
            @csrf
            @if ($editMode)
                @method('PUT')
            @endif
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 lg:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- KOLOM KIRI -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Ganti bagian Playlist Date dengan Start Date & End Date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-uc-dark mb-1.5">Start Date</label>
                                <div class="relative">
                                    <input type="text" name="playlist_start_date" id="playlist_start_date" required
                                        readonly autocomplete="off"
                                        value="{{ old('playlist_start_date', $editMode ? \Carbon\Carbon::parse($playlist->playlist_start_date)->format('Y-m-d') : '') }}"
                                        placeholder="Pilih tanggal"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-4 pr-10 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors cursor-pointer">
                                    <i
                                        class="fa-solid fa-calendar-days absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-uc-dark mb-1.5">End Date</label>
                                <div class="relative">
                                    <input type="text" name="playlist_end_date" id="playlist_end_date" required readonly
                                        autocomplete="off"
                                        value="{{ old('playlist_end_date', $editMode ? \Carbon\Carbon::parse($playlist->playlist_end_date)->format('Y-m-d') : '') }}"
                                        placeholder="Pilih tanggal"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-4 pr-10 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors cursor-pointer">
                                    <i
                                        class="fa-solid fa-calendar-days absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-4 text-[11px]">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded bg-red-400"></span>
                                    <span class="text-gray-500">Sudah ada playlist</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded bg-emerald-400"></span>
                                    <span class="text-gray-500">Tersedia</span>
                                </div>
                                <span class="text-gray-400">— klik ikon kalender di atas untuk lihat & pilih tanggal</span>
                            </div>
                            <p id="dateOverlapWarning" class="hidden text-[11px] text-red-500 font-medium">
                                <i class="fa-solid fa-triangle-exclamation"></i> Tanggal ini sudah dipakai playlist lain,
                                silakan pilih tanggal lain.
                            </p>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-uc-dark">Total Content: <span
                                    id="content-count">0</span></label>
                            <div id="content-list" class="space-y-3 min-h-[50px]">
                                @if ($editMode)
                                    @foreach ($existingItems as $item)
                                        <div class="content-item flex items-center justify-between bg-white border border-gray-200 rounded-2xl px-5 py-3.5 shadow-xs hover:border-gray-300 transition-all"
                                            data-url="{{ $item['url'] }}" data-title="{{ $item['title'] }}"
                                            data-is-image="{{ $item['isImage'] ? '1' : '0' }}"
                                            data-duration="{{ $item['duration'] }}">
                                            <div class="flex items-center space-x-3">
                                                <i class="fa-solid fa-grip-vertical text-gray-400 text-xs drag-handle"></i>
                                                <span
                                                    class="text-xs font-medium text-uc-dark content-name">{{ $item['title'] }}
                                                    ({{ $item['duration'] }}s)
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <input type="hidden" name="contents[]" value="{{ $item['id'] }}">
                                                <button type="button" onclick="removeContentItem(this, event)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors p-1 focus:outline-none">
                                                    <i class="fa-solid fa-xmark text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
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
                <button type="submit" id="submitPlaylistBtn"
                    class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-10 py-3.5 rounded-xl text-xs transition-all shadow-sm">
                    {{ isset($editMode) && $editMode ? 'Update Playlist' : 'Save' }}
                </button>
            </div>
        </form>
    </main>
    <!-- MODAL PILIH KONTEN -->
    <div id="content-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-xl space-y-4 h-[550px] flex flex-col">
            <div class="flex justify-between items-center border-b pb-3 flex-shrink-0">
                <h3 class="text-sm font-bold text-uc-dark">Pilih Konten dari Database</h3>
                <button type="button" onclick="closeContentModal()" class="text-gray-400 hover:text-red-500 p-1">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            <!-- SEARCH BAR -->
            <div class="py-1 flex-shrink-0">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" id="contentSearchInput" onkeyup="filterContentItems()"
                        placeholder="Cari judul konten..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors">
                </div>
            </div>
            <div class="overflow-y-auto space-y-4 flex-1 pr-1">
                <!-- 1. EVENT -->
                <div class="category-section">
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
                                    data-title="{{ strtolower($content->content_title) }}"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }} | Durasi:
                                            {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span
                                        class="bg-uc-orange text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasEvent)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada konten event tersedia.</p>
                        @endif
                    </div>
                </div>
                <!-- 2. REGULAR CONTENT -->
                <div class="category-section pt-2">
                    <h4
                        class="text-[11px] font-bold text-blue-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-file-lines"></i> Regular Content
                    </h4>
                    <div class="space-y-2">
                        @php $hasRegular = false; @endphp
                        @foreach ($contents as $content)
                            @if (strtolower($content->content_category ?? '') == 'regular content' || empty($content->content_category))
                                @php $hasRegular = true; @endphp
                                <div class="modal-item flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:bg-blue-50 transition-all cursor-pointer"
                                    data-title="{{ strtolower($content->content_title) }}"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }} | Durasi:
                                            {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span class="bg-blue-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasRegular)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada regular content tersedia.</p>
                        @endif
                    </div>
                </div>
                <!-- 3. PROMOTION -->
                <div class="category-section pt-2">
                    <h4
                        class="text-[11px] font-bold text-purple-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-bullhorn"></i> Promotion
                    </h4>
                    <div class="space-y-2">
                        @php $hasPromotion = false; @endphp
                        @foreach ($contents as $content)
                            @if (strtolower($content->content_category ?? '') == 'promotion')
                                @php $hasPromotion = true; @endphp
                                <div class="modal-item flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:bg-purple-50 transition-all cursor-pointer"
                                    data-title="{{ strtolower($content->content_title) }}"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }} | Durasi:
                                            {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span
                                        class="bg-purple-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasPromotion)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada konten promotion tersedia.</p>
                        @endif
                    </div>
                </div>
                <!-- 4. ACHIEVEMENT -->
                <div class="category-section pt-2">
                    <h4
                        class="text-[11px] font-bold text-amber-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-trophy"></i> Achievement
                    </h4>
                    <div class="space-y-2">
                        @php $hasAchievement = false; @endphp
                        @foreach ($contents as $content)
                            @if (strtolower($content->content_category ?? '') == 'achievement')
                                @php $hasAchievement = true; @endphp
                                <div class="modal-item flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:bg-amber-50 transition-all cursor-pointer"
                                    data-title="{{ strtolower($content->content_title) }}"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }} | Durasi:
                                            {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span
                                        class="bg-amber-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasAchievement)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada konten achievement tersedia.</p>
                        @endif
                    </div>
                </div>
                <!-- 5. BUSINESS & COMMUNITY -->
                <div class="category-section pt-2">
                    <h4
                        class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-handshake"></i> Business & Community
                    </h4>
                    <div class="space-y-2">
                        @php $hasBusiness = false; @endphp
                        @foreach ($contents as $content)
                            @if (strtolower($content->content_category ?? '') == 'business & community')
                                @php $hasBusiness = true; @endphp
                                <div class="modal-item flex items-center justify-between p-3 border border-gray-100 rounded-xl hover:bg-emerald-50 transition-all cursor-pointer"
                                    data-title="{{ strtolower($content->content_title) }}"
                                    onclick="addContentToPlaylist('{{ $content->contents_id ?? $content->content_title }}', '{{ $content->content_title }}', '{{ $content->full_url }}', {{ $content->is_image ? 'true' : 'false' }}, {{ $content->duration_seconds }})">
                                    <div>
                                        <p class="text-xs font-bold text-uc-dark">{{ $content->content_title }}</p>
                                        <p class="text-[10px] text-gray-400">Tipe:
                                            {{ strtoupper($content->content_type ?? '-') }} | Durasi:
                                            {{ $content->duration_seconds }}s</p>
                                    </div>
                                    <span
                                        class="bg-emerald-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-semibold">+
                                        Pilih</span>
                                </div>
                            @endif
                        @endforeach
                        @if (!$hasBusiness)
                            <p class="text-[11px] text-gray-400 italic pl-2">Tidak ada konten business & community
                                tersedia.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Flatpickr: custom date picker biar bisa nge-highlight tanggal booked/tersedia -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        /* Font & tampilan umum kalender */
        .flatpickr-calendar {
            font-family: inherit !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            border-radius: 16px !important;
            width: auto !important;
            padding: 12px !important;
        }

        .flatpickr-current-month {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #1F2937 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
        }

        .flatpickr-current-month input.cur-year,
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            font-weight: 700 !important;
        }

        .flatpickr-calendar .flatpickr-monthDropdown-months {
            max-height: 100px !important;
            overflow-y: auto !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            background: transparent !important;
            border: none !important;
            cursor: pointer;
            padding-right: 12px !important;
        }

        .flatpickr-current-month input.cur-year {
            display: none !important;
        }

        .flatpickr-current-month .numInputWrapper span.arrowUp,
        .flatpickr-current-month .numInputWrapper span.arrowDown {
            display: none !important;
        }

        .flatpickr-current-month .numInputWrapper {
            width: 60px !important;
            padding: 0 !important;
        }

        /* Container Dropdown Bulan & Tahun agar seragam pakai panah v */
        .flatpickr-month-wrapper,
        .flatpickr-year-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .flatpickr-month-wrapper::after,
        .flatpickr-year-wrapper::after {
            content: "v";
            font-size: 11px;
            font-weight: 700;
            color: #1F2937;
            position: absolute;
            right: 2px;
            pointer-events: none;
            transform: scaleY(0.7);
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            padding-right: 16px !important;
        }

        .flatpickr-year-select {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #1F2937 !important;
            border: none !important;
            background: transparent !important;
            cursor: pointer;
            padding: 0 16px 0 4px;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            max-height: 200px;
        }

        .flatpickr-year-select:focus {
            outline: none !important;
        }

        /* Header nama hari (Sun/Mon/Tue/dst) */
        .flatpickr-weekdays {
            background: transparent !important;
            text-align: center !important;
        }

        span.flatpickr-weekday {
            font-size: 11px !important;
            font-weight: 600 !important;
            color: #6B7280 !important;
            text-transform: none !important;
        }

        /* PERBAIKAN UTAMA: Ubah container hari menjadi CSS Grid agar kotaknya terpisah rapi (tidak nempel) */
        .flatpickr-days {
            width: 315px !important;
        }

        .dayContainer {
            display: grid !important;
            grid-template-columns: repeat(7, 1fr) !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            justify-content: center !important;
            gap: 6px !important;
            /* Jarak antar kotak tanggal */
            padding: 4px 0 !important;
        }

        /* Styling kotak tanggal individual */
        .flatpickr-day {
            width: 38px !important;
            height: 38px !important;
            line-height: 38px !important;
            margin: 0 !important;
            border-radius: 10px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            border: none !important;
            box-shadow: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            cursor: default !important;
        }

        /* Status Tanggal: Booked (Merah Muda) */
        .flatpickr-day.booked-date {
            background: #fee2e2 !important;
            color: #dc2626 !important;
            cursor: default !important;
        }

        /* Status Tanggal: Available (Hijau Muda) */
        .flatpickr-day.available-date {
            background: #d1fae5 !important;
            color: #059669 !important;
        }

        .flatpickr-day.available-date:hover {
            background: #a7f3d0 !important;
        }

        /* Status Tanggal: Past / Lewat */
        .flatpickr-day.past-date {
            background: #f3f4f6 !important;
            color: #9ca3af !important;
            cursor: default !important;
        }

        /* Tanggal yang dipilih (Selected) */
        .flatpickr-day.selected.available-date,
        .flatpickr-day.selected {
            background: #F27D00 !important;
            color: #fff !important;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #F27D00 !important;
        }

        /* Tanggal hari ini (Today) */
        .flatpickr-day.today:not(.selected) {
            border: 2px solid #F27D00 !important;
            background: transparent;
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
            rebuildPlaylistQueue();
            if (playlistQueue.length > 0) {
                currentPlaylistIndex = 0;
                playCurrentQueueItem();
            }
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

        function filterContentItems() {
            let input = document.getElementById('contentSearchInput').value.toLowerCase();
            let sections = document.querySelectorAll('.category-section');
            sections.forEach(section => {
                let items = section.querySelectorAll('.modal-item');
                let hasVisibleItem = false;
                items.forEach(item => {
                    let title = item.getAttribute('data-title');
                    if (title.includes(input)) {
                        item.style.display = 'flex';
                        hasVisibleItem = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                // Sembunyikan seluruh section kategori jika tidak ada item yang cocok di dalamnya
                if (hasVisibleItem) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }
    </script>
    <script>
        const bookedDates = @json($bookedDates ?? []);

        function isPastDate(date) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return date < today;
        }
        // Kasih class ke tanggal booked/tersedia/lampau langsung di dalam popup kalender
        function markDateAvailability(dObj, dStr, fp, dayElem) {
            const dateStr = flatpickr.formatDate(dayElem.dateObj, "Y-m-d");
            const minDate = fp.config.minDate; // khusus End Date, ini ke-set = Start Date yang dipilih + 1 hari
            if (isPastDate(dayElem.dateObj)) {
                dayElem.classList.add('past-date');
            } else if (minDate && dayElem.dateObj < minDate) {
                // Tanggal sebelum atau sama dengan Start Date - gabisa dipilih di End Date, jadi abu-abu
                dayElem.classList.add('past-date');
            } else if (bookedDates.includes(dateStr)) {
                dayElem.classList.add('booked-date');
            } else {
                dayElem.classList.add('available-date');
            }
        }
        // Aturan tanggal yang tidak boleh diklik sama sekali: sudah lewat, atau sudah dipakai playlist lain
        const dateDisableRules = [
            function(date) {
                return isPastDate(date) || bookedDates.includes(flatpickr.formatDate(date, "Y-m-d"));
            }
        ];

        function checkDateOverlap() {
            const start = document.getElementById('playlist_start_date').value;
            const end = document.getElementById('playlist_end_date').value;
            const warning = document.getElementById('dateOverlapWarning');
            const submitBtn = document.getElementById('submitPlaylistBtn');
            if (!start || !end) return;
            let overlap = false;
            let cur = new Date(start);
            const endD = new Date(end);
            while (cur <= endD) {
                if (bookedDates.includes(cur.toISOString().slice(0, 10))) {
                    overlap = true;
                    break;
                }
                cur.setDate(cur.getDate() + 1);
            }
            warning.classList.toggle('hidden', !overlap);
            if (submitBtn) submitBtn.disabled = overlap;
        }

        // Bungkus dropdown bulan bawaan Flatpickr agar ada pembungkus dan panah v
        function setupMonthDropdown(selectedDates, dateStr, instance) {
            const monthNav = instance.monthNav;
            const monthDropdown = monthNav.querySelector('.flatpickr-monthDropdown-months');
            if (monthDropdown && !monthDropdown.parentElement.classList.contains('flatpickr-month-wrapper')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'flatpickr-month-wrapper';
                monthDropdown.parentNode.insertBefore(wrapper, monthDropdown);
                wrapper.appendChild(monthDropdown);
            }
        }

        // Ganti input angka tahun bawaan Flatpickr jadi <select> scrollable yang dibungkus div panah v
        function setupYearDropdown(selectedDates, dateStr, instance) {
            setupMonthDropdown(selectedDates, dateStr, instance);
            const yearInput = instance.currentYearElement;
            if (!yearInput || yearInput.dataset.customized) return;
            const thisYear = new Date().getFullYear();
            const startYear = thisYear - 10;
            const endYear = thisYear + 50;

            const wrapper = document.createElement('div');
            wrapper.className = 'flatpickr-year-wrapper';

            const select = document.createElement('select');
            select.className = 'flatpickr-year-select';
            for (let y = startYear; y <= endYear; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === instance.currentYear) opt.selected = true;
                select.appendChild(opt);
            }
            select.addEventListener('change', function() {
                instance.changeYear(parseInt(this.value));
            });

            wrapper.appendChild(select);
            yearInput.insertAdjacentElement('afterend', wrapper);
            yearInput.dataset.customized = 'true';
        }

        function syncYearDropdown(selectedDates, dateStr, instance) {
            const sel = instance.calendarContainer.querySelector('.flatpickr-year-select');
            if (sel) sel.value = instance.currentYear;
        }
        // Popup kalender End Date - dibuat duluan biar bisa direferensikan dari Start Date
        const endDatePicker = flatpickr("#playlist_end_date", {
            dateFormat: "Y-m-d",
            disableMobile: true,
            disable: dateDisableRules,
            onDayCreate: markDateAvailability,
            onReady: setupYearDropdown,
            onYearChange: syncYearDropdown,
            onChange: function() {
                checkDateOverlap();
            }
        });
        // Popup kalender Start Date
        const startDatePicker = flatpickr("#playlist_start_date", {
            dateFormat: "Y-m-d",
            disableMobile: true,
            disable: dateDisableRules,
            onDayCreate: markDateAvailability,
            onReady: setupYearDropdown,
            onYearChange: syncYearDropdown,
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    // Set minDate untuk End Date minimal SAMA DENGAN Start Date (boleh playlist 1 hari)
                    endDatePicker.set('minDate', selectedDates[0]);

                    // Reset End Date HANYA kalau dia jadi LEBIH KECIL dari Start Date baru (bukan lagi kalau sama persis)
                    const currentEndDate = endDatePicker.selectedDates[0];
                    if (currentEndDate && currentEndDate < selectedDates[0]) {
                        endDatePicker.clear();
                    }
                }
                checkDateOverlap();
            }
        });
        // Kalau load pertama kali dalam mode edit, set minDate End Date secara otomatis jika Start Date sudah ada
        @if ($editMode && $playlist->playlist_start_date)
            const initialStart = new Date("{{ \Carbon\Carbon::parse($playlist->playlist_start_date)->format('Y-m-d') }}");
            endDatePicker.set('minDate', initialStart);
        @endif

        // Kalau load pertama kali dalam mode edit, cek juga overlap-nya
        checkDateOverlap();
    </script>
@endsection
