@extends('layouts.app')

@section('content')
    <style>
        .swal-custom-popup { border-radius: 1rem !important; padding: 1.25em !important; font-family: inherit !important; }
        .swal-custom-title { font-size: 1.125rem !important; font-weight: 600 !important; color: #1F2937 !important; }
        .swal-custom-html { font-size: 0.875rem !important; color: #4B5563 !important; }
        .swal-btn-confirm-delete { background-color: #F27D00 !important; color: #FFFFFF !important; padding: 8px 16px !important; border-radius: 10px !important; font-size: 12px !important; font-weight: 600 !important; border: none !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; cursor: pointer; }
        .swal-btn-confirm-recover { background-color: #0084FF !important; color: #FFFFFF !important; padding: 8px 16px !important; border-radius: 10px !important; font-size: 12px !important; font-weight: 600 !important; border: none !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; cursor: pointer; }
        .swal-btn-cancel { background-color: #6B7280 !important; color: #FFFFFF !important; padding: 8px 16px !important; border-radius: 10px !important; font-size: 12px !important; font-weight: 600 !important; border: none !important; margin-right: 12px !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; cursor: pointer; }
        .playlist-bar { transition: filter 0.15s ease; }
        .playlist-bar:hover { filter: brightness(0.95); }
    </style>

    <div class="max-w-[1600px] w-full mx-auto p-6 lg:p-8 space-y-6">
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

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl text-xs flex items-center space-x-3 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-base"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <h1 class="text-2xl font-bold text-uc-dark">Halo, {{ $firstName }}!</h1>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- KOLOM KIRI ATAS (Kalender) -->
            <div class="lg:col-span-8 space-y-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-uc-blue text-white rounded-2xl p-5 shadow-sm flex flex-col justify-between h-32">
                        <span class="text-xs font-medium opacity-90">Total Content</span>
                        <div class="text-2xl font-bold text-center my-auto">{{ $totalContent }} Videos</div>
                    </div>
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
                    <div class="bg-uc-purple text-white rounded-2xl p-5 shadow-sm flex flex-col justify-between h-32">
                        <span class="text-xs font-medium opacity-90">Average Playtime</span>
                        <div class="text-2xl font-bold text-center my-auto">{{ $averagePlaytime }} second / Video</div>
                    </div>
                    <div class="bg-[#F27D00] text-white rounded-2xl p-5 shadow-sm flex flex-col justify-between h-32">
                        <span class="text-xs font-medium opacity-90">Total Playlists</span>
                        <div class="text-2xl font-bold text-center my-auto">{{ $activePlaylists }} Playlist</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex space-x-2">
                        <button onclick="switchTab('playlists')" id="tab-playlists-btn"
                            class="px-4 py-2 rounded-lg text-xs font-medium transition-all bg-[#F27D00] text-white shadow-sm">
                            Playlists
                        </button>
                        <button onclick="switchTab('deleted')" id="tab-deleted-btn"
                            class="px-4 py-2 rounded-lg text-xs font-medium transition-all bg-[#5F6870] text-white shadow-sm">
                            Deleted Playlists
                        </button>
                    </div>

                    <!-- TAB 1: KALENDER -->
                    <div id="tab-playlists-content" class="mb-8">
                        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">

                            <div class="bg-[#F27D00] text-white px-6 py-4 font-bold text-xs flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-solid fa-calendar-days text-sm"></i>
                                    <span>Playlists Calendar Schedule</span>
                                </div>
                                <div class="text-[11px] font-normal text-orange-100">
                                    Klik pada tanggal/event untuk melihat detail, edit, atau tayangkan.
                                </div>
                            </div>

                            <div class="p-6 md:p-8">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-5">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('dashboard', ['month' => $currentMonthCarbon->copy()->subMonth()->month, 'year' => $currentMonthCarbon->copy()->subMonth()->year, 'category' => $categoryFilter]) }}"
                                            class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-200 text-uc-gray hover:bg-gray-50 transition-colors">
                                            <i class="fa-solid fa-chevron-left text-xs"></i>
                                        </a>

                                        <div class="relative">
                                            <button type="button" onclick="toggleMonthPicker()" id="monthPickerBtn"
                                                class="text-sm font-bold text-uc-dark w-44 text-center hover:text-uc-orange transition-colors flex items-center justify-center gap-1.5 px-2 py-1 rounded-lg hover:bg-orange-50">
                                                {{ $currentMonthCarbon->translatedFormat('F Y') }}
                                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                            </button>

                                            <div id="monthPickerDropdown"
                                                class="hidden absolute top-full left-1/2 -translate-x-1/2 mt-2 bg-white border border-gray-200 rounded-2xl shadow-xl p-3 z-50 flex gap-3 w-64">
                                                <div class="flex-1">
                                                    <p class="text-[10px] font-semibold text-uc-gray mb-1.5 text-center">Bulan</p>
                                                    <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
                                                        @foreach (range(1, 12) as $m)
                                                            <button type="button" onclick="selectMonthYear({{ $m }}, null)"
                                                                class="w-full text-[11px] text-left px-2 py-1.5 rounded-lg hover:bg-orange-50 transition-colors {{ $m == $month ? 'bg-uc-orange text-white font-semibold' : 'text-uc-dark' }}">
                                                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-[10px] font-semibold text-uc-gray mb-1.5 text-center">Tahun</p>
                                                    <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
                                                        @foreach (range($year - 6, $year + 6) as $y)
                                                            <button type="button" onclick="selectMonthYear(null, {{ $y }})"
                                                                class="w-full text-[11px] text-left px-2 py-1.5 rounded-lg hover:bg-orange-50 transition-colors {{ $y == $year ? 'bg-uc-orange text-white font-semibold' : 'text-uc-dark' }}">
                                                                {{ $y }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('dashboard', ['month' => $currentMonthCarbon->copy()->addMonth()->month, 'year' => $currentMonthCarbon->copy()->addMonth()->year, 'category' => $categoryFilter]) }}"
                                            class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-200 text-uc-gray hover:bg-gray-50 transition-colors">
                                            <i class="fa-solid fa-chevron-right text-xs"></i>
                                        </a>
                                        <a href="{{ route('dashboard', ['month' => now()->month, 'year' => now()->year, 'category' => $categoryFilter]) }}"
                                            class="text-[11px] font-semibold text-uc-orange hover:underline ml-1">
                                            Hari Ini
                                        </a>
                                    </div>

                                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                        <input type="hidden" name="month" value="{{ $month }}">
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <label class="text-[11px] font-semibold text-uc-gray">Filter:</label>
                                        <select name="category" onchange="this.form.submit()"
                                            class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-uc-orange">
                                            <option value="all" {{ $categoryFilter === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                                            @foreach ($availableCategories as $cat)
                                                <option value="{{ $cat }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>

                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <div class="grid grid-cols-7 divide-x divide-gray-200 text-center font-semibold text-xs text-uc-gray bg-gray-50 py-2">
                                        <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                                    </div>

                                    @foreach ($calendarWeeks as $week)
                                        <div class="border-t border-gray-200">
                                            <div class="grid grid-cols-7 divide-x divide-gray-200 py-1.5">
                                                @foreach ($week['days'] as $cellDate)
                                                    <div class="h-7 flex items-center justify-center">
                                                        @if ($cellDate)
                                                            <span class="text-xs font-bold {{ $cellDate == $todayStr ? 'bg-uc-orange text-white w-6 h-6 rounded-full flex items-center justify-center' : 'text-uc-dark' }}">
                                                                {{ (int) substr($cellDate, 8, 2) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if ($week['row_count'] > 0)
                                                <div class="grid grid-cols-7 gap-1 px-1 pb-1.5" style="grid-template-rows: repeat({{ $week['row_count'] }}, 26px);">
                                                    @foreach ($week['bars'] as $bar)
                                                        <div class="playlist-bar {{ $bar['bg'] }} {{ $bar['border'] }} {{ $bar['text'] }} border text-[10px] font-semibold px-2 flex items-center rounded-lg truncate shadow-sm cursor-pointer"
                                                            style="grid-column: {{ $bar['start_col'] }} / {{ $bar['end_col'] }}; grid-row: {{ $bar['row'] }};"
                                                            data-id="{{ $bar['id'] }}"
                                                            data-start="{{ $bar['start_date'] }}"
                                                            data-end="{{ $bar['end_date'] }}"
                                                            data-items='@json($bar['items'])'
                                                            data-videos='@json($bar['videos'])'>
                                                            Playlist {{ $bar['id'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL DETAIL PLAYLIST -->
                    <div id="playlistModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
                        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl relative">
                            <button onclick="closePlaylistModal()"
                                class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 text-base font-bold focus:outline-none">
                                <i class="fa-solid fa-xmark"></i>
                            </button>

                            <div class="flex items-center space-x-3 mb-5">
                                <div class="w-10 h-10 bg-orange-100 text-uc-orange rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-list-check text-lg"></i>
                                </div>
                                <div>
                                    <h4 id="modalPlaylistTitle" class="text-base font-bold text-uc-dark">Detail Playlist</h4>
                                    <p id="modalPlaylistDate" class="text-xs text-uc-gray">Periode Tayang</p>
                                </div>
                            </div>

                            <div class="mb-5">
                                <p class="text-[11px] font-semibold text-uc-gray mb-2">Daftar Konten dalam Playlist Ini</p>
                                <div class="max-h-56 overflow-y-auto divide-y divide-gray-100">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-gray-500 font-semibold border-b border-gray-200">
                                                <th class="py-2 text-center w-10">No</th>
                                                <th class="py-2 text-left">Judul Konten</th>
                                                <th class="py-2 text-right w-16">Durasi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modalContentList" class="divide-y divide-gray-100 text-uc-dark">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <button id="modalShowNowBtn" type="button"
                                    class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-6 py-3 rounded-xl text-xs transition-all shadow-sm flex items-center space-x-1.5">
                                    <i class="fa-solid fa-play text-[10px]"></i><span>Show Now (Preview)</span>
                                </button>

                                <div class="flex items-center space-x-3">
                                    <button id="modalDeleteBtn" type="button"
                                        class="bg-red-400 hover:bg-red-500 text-white font-semibold px-6 py-3 rounded-xl text-xs transition-all shadow-sm">
                                        Delete
                                    </button>
                                    <a id="modalEditBtn" href="#"
                                        class="px-6 py-3 text-white font-semibold rounded-xl shadow-lg transition duration-300 transform active:scale-95 text-xs flex items-center space-x-1.5"
                                        style="background: linear-gradient(135deg, #00b4db, #0083b0);">
                                        <i class="fa-solid fa-pen-to-square"></i><span>Edit</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DELETED PLAYLISTS -->
                    <div id="tab-deleted-content" class="hidden">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[400px]">
                            <div class="bg-[#F27D00] text-white px-5 py-3 font-bold text-xs flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Deleted Playlists</span>
                                </div>
                            </div>
                            <div class="p-3 bg-white flex-1 flex flex-col overflow-hidden">
                                <div class="border border-gray-200 rounded-none overflow-x-auto overflow-y-auto flex-1">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="sticky top-0 z-10">
                                            <tr class="bg-gray-100 text-gray-700 font-semibold border-b border-gray-200">
                                                <th class="p-3">Playlist</th>
                                                <th class="p-3">Tanggal</th>
                                                <th class="p-3 text-center">Total Items</th>
                                                <th class="p-3 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 text-gray-700">
                                            @forelse($trashedPlaylists ?? [] as $index => $trash)
                                                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-orange-50/50 transition-colors">
                                                    <td class="p-3 font-semibold text-uc-dark">Playlist {{ $trash->playlist_id }}</td>
                                                    <td class="p-3">
                                                        {{ date('d/m/Y', strtotime($trash->playlist_start_date)) }} - {{ date('d/m/Y', strtotime($trash->playlist_end_date)) }}
                                                    </td>
                                                    <td class="p-3 text-center font-bold">{{ $trash->details->count() }} Items</td>
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
                                                    <td colspan="4" class="p-6 text-center text-gray-400 italic">Sampah kosong.</td>
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

            <!-- KOLOM KANAN ATAS (TV) -->
            <div class="lg:col-span-4 lg:self-center flex flex-col items-center justify-center w-full">
                <div class="flex items-center space-x-2 text-sm font-semibold text-uc-dark mb-4 justify-center w-full">
                    <span class="w-3 h-3 rounded-full bg-uc-green animate-pulse"></span>
                    <span id="playing-status-title">Now Playing: Standby</span>
                </div>

                <div class="w-full max-w-[300px] aspect-[9/16] bg-slate-900 p-2.5 border-4 border-slate-800 relative flex items-center justify-center shadow-xl">
                    <div class="w-full h-full bg-slate-950 border border-slate-800 overflow-hidden relative flex items-center justify-center">
                        <div id="tv-placeholder" class="text-center p-5 flex flex-col items-center justify-center h-full">
                            <i class="fa-solid fa-tv text-4xl text-gray-600 mb-3"></i>
                            <p class="text-xs text-gray-300 font-medium mb-1.5">Samsung Signage 24"</p>
                            <p class="text-[10px] text-gray-500">Klik <span class="text-uc-green font-semibold">Show Now</span> untuk memutar playlist</p>
                        </div>
                        <video id="tv-video-player" class="w-full h-full object-contain bg-black hidden" playsinline muted
                            controls disablePictureInPicture controlslist="nodownload noplaybackrate">
                            <source id="tv-video-source" src="" type="video/mp4">
                        </video>
                        <img id="tv-image-player" src="" alt="Signage Image" class="w-full h-full object-contain bg-black hidden">
                    </div>
                </div>

                <div class="mt-5 text-center w-full max-w-[300px]">
                    <button id="btnShowSignage"
                        class="w-full py-3.5 px-6 text-white font-semibold rounded-xl shadow-lg transition duration-300 transform active:scale-95 text-xs"
                        style="background: linear-gradient(135deg, #00b4db, #0083b0);">
                        Show to Signage
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-8 space-y-3 flex flex-col">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[280px]">
                    <div class="bg-[#F27D00] text-white px-5 py-3 font-bold text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-table"></i>
                            <span>Daftar Konten & Pengunggah</span>
                        </div>
                    </div>
                    <div class="p-3 bg-white flex-1 flex flex-col overflow-hidden">
                        <div class="border border-gray-200 rounded-none overflow-x-auto overflow-y-auto flex-1">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gray-100 text-gray-700 font-semibold border-b border-gray-200">
                                        <th class="p-3">Judul Konten</th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-right">Pengunggah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 text-gray-700">
                                    @php
                                        $sortedContents = isset($allContents)
                                            ? $allContents->sortByDesc(fn($item) => $item->created_at ?? ($item->content_id ?? 0))
                                            : collect();
                                    @endphp
                                    @forelse($sortedContents as $index => $content)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-orange-50/50 transition-colors">
                                            <td class="p-3 font-medium text-uc-dark">{{ $content->content_title }}</td>
                                            <td class="p-3 text-gray-500">{{ $content->content_category }}</td>
                                            <td class="p-3 text-right">
                                                <span class="inline-flex items-center space-x-1 bg-gray-100 text-gray-700 px-2.5 py-1 rounded-none font-medium">
                                                    <i class="fa-solid fa-user text-[10px]"></i>
                                                    <span>{{ $content->users_name }}</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="p-6 text-center text-gray-400 italic">Belum ada konten yang di-upload.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-3 flex flex-col w-full">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[280px] w-full">
                    <div class="bg-[#F27D00] text-white px-5 py-3 font-bold text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-table"></i>
                            <span>Riwayat Signage (History)</span>
                        </div>
                    </div>
                    <div class="p-3 bg-white flex-1 flex flex-col overflow-hidden">
                        <div class="border border-gray-200 rounded-none overflow-x-auto overflow-y-auto flex-1">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-gray-100 text-gray-700 font-semibold border-b border-gray-200">
                                        <th class="p-3">Playlist</th>
                                        <th class="p-3">User</th>
                                        <th class="p-3 text-right">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 text-gray-700">
                                    @php $signageHistories = isset($allSignageHistories) ? $allSignageHistories : collect(); @endphp
                                    @forelse($signageHistories as $index => $history)
                                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-orange-50/50 transition-colors">
                                            <td class="p-3 font-semibold text-uc-dark">Playlist {{ $history->playlist_id }}</td>
                                            <td class="p-3 text-gray-800 truncate max-w-[80px]" title="{{ $history->status_updated_by ?? '-' }}">
                                                {{ $history->status_updated_by ?? '-' }}
                                            </td>
                                            <td class="p-3 text-right text-gray-500 text-[10px]">
                                                {{ $history->status_updated_at ? date('d/m/Y H:i:s', strtotime($history->status_updated_at)) : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="p-6 text-center text-gray-400 italic">Belum ada riwayat.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function switchTab(tab) {
                const playlistsContent = document.getElementById('tab-playlists-content');
                const deletedContent = document.getElementById('tab-deleted-content');
                const playlistsBtn = document.getElementById('tab-playlists-btn');
                const deletedBtn = document.getElementById('tab-deleted-btn');

                if (tab === 'playlists') {
                    playlistsContent.classList.remove('hidden');
                    deletedContent.classList.add('hidden');
                    playlistsBtn.className = "px-4 py-2 rounded-lg text-xs font-medium transition-all bg-[#F27D00] text-white shadow-sm";
                    deletedBtn.className = "px-4 py-2 rounded-lg text-xs font-medium transition-all bg-[#5F6870] text-white shadow-sm";
                } else {
                    playlistsContent.classList.add('hidden');
                    deletedContent.classList.remove('hidden');
                    playlistsBtn.className = "px-4 py-2 rounded-lg text-xs font-medium transition-all bg-[#5F6870] text-white shadow-sm";
                    deletedBtn.className = "px-4 py-2 rounded-lg text-xs font-medium transition-all bg-[#F27D00] text-white shadow-sm";
                }
            }

            function confirmDelete(playlistId) {
                Swal.fire({
                    title: 'Pindahkan ke sampah?', text: "Playlist akan dipindahkan ke daftar Deleted Playlists.",
                    icon: 'warning', width: '380px', showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal', reverseButtons: true, buttonsStyling: false,
                    customClass: { popup: 'swal-custom-popup', title: 'swal-custom-title', htmlContainer: 'swal-custom-html', confirmButton: 'swal-btn-confirm-delete', cancelButton: 'swal-btn-cancel' }
                }).then((result) => {
                    if (result.isConfirmed) document.getElementById('delete-form-' + playlistId).submit();
                });
            }

            function confirmRecover(playlistId) {
                Swal.fire({
                    title: 'Pulihkan playlist?', text: "Playlist akan dikembalikan ke daftar aktif.",
                    icon: 'question', width: '380px', showCancelButton: true,
                    confirmButtonText: 'Ya, pulihkan!', cancelButtonText: 'Batal', reverseButtons: true, buttonsStyling: false,
                    customClass: { popup: 'swal-custom-popup', title: 'swal-custom-title', htmlContainer: 'swal-custom-html', confirmButton: 'swal-btn-confirm-recover', cancelButton: 'swal-btn-cancel' }
                }).then((result) => {
                    if (result.isConfirmed) document.getElementById('recover-form-' + playlistId).submit();
                });
            }

            function toggleMonthPicker() {
                document.getElementById('monthPickerDropdown').classList.toggle('hidden');
            }

            function selectMonthYear(m, y) {
                const url = new URL(window.location.href);
                if (m !== null) url.searchParams.set('month', m);
                if (y !== null) url.searchParams.set('year', y);
                url.searchParams.set('category', '{{ $categoryFilter }}');
                window.location.href = url.toString();
            }

            document.addEventListener('click', function (e) {
                const btn = document.getElementById('monthPickerBtn');
                const dropdown = document.getElementById('monthPickerDropdown');
                if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            let currentPlaylist = [];
            let currentIndex = 0;
            let currentPlaylistId = '';
            let imageTimer = null;
            let selectedPlaylistId = null;

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
                statusTitle.innerText = `Now Playing: Playlist ${currentPlaylistId}`;

                const isImage = item.isImage === true;

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
                    videoPlayer.play().catch(() => { videoPlayer.muted = true; videoPlayer.play(); });
                    videoPlayer.onended = function () {
                        currentIndex = (currentIndex + 1) % currentPlaylist.length;
                        playCurrentItem();
                    };
                }
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.getElementById('btnShowSignage').addEventListener('click', function () {
                if (!selectedPlaylistId) {
                    Swal.fire({ icon: 'warning', title: 'Pilih Playlist Dulu', text: 'Silakan klik salah satu playlist dari kalender untuk melihat detail.', confirmButtonColor: '#0083b0' });
                    return;
                }

                Swal.fire({
                    title: 'Tampilkan ke Signage?', text: "Apakah Anda yakin ingin menampilkan playlist ini ke layar TV signage sekarang?",
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#0083b0', cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Tampilkan Sekarang', cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.showLoading();
                        fetch(`/dashboard/show/${selectedPlaylistId}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        })
                            .then(async response => {
                                const isJson = response.headers.get('content-type')?.includes('application/json');
                                const data = isJson ? await response.json() : null;
                                if (!response.ok) throw new Error(data?.message || 'Server error status: ' + response.status);
                                return data;
                            })
                            .then(data => {
                                Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message || 'Playlist berhasil ditampilkan ke signage.' })
                                    .then(() => location.reload());
                            })
                            .catch(error => {
                                Swal.fire({ icon: 'error', title: 'Gagal!', text: error.message || 'Terjadi kesalahan sistem.' });
                            });
                    }
                });
            });
        </script>

        <script>
            let currentModalVideos = [];

            function openPlaylistModal(id, startDate, endDate, items, videos) {
                document.getElementById('modalPlaylistTitle').innerText = 'Playlist ' + id;
                document.getElementById('modalPlaylistDate').innerText = 'Periode: ' + startDate + ' s/d ' + endDate;
                document.getElementById('modalEditBtn').href = '/playlist/' + id + '/edit';

                const listBody = document.getElementById('modalContentList');
                listBody.innerHTML = '';
                items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="py-2 text-center text-uc-orange font-bold">${item.order}</td>
                        <td class="py-2 font-medium">${item.title}</td>
                        <td class="py-2 text-right text-gray-500">${item.duration}s</td>
                    `;
                    listBody.appendChild(tr);
                });

                selectedPlaylistId = id;
                currentModalVideos = videos;

                document.getElementById('modalDeleteBtn').onclick = function () {
                    triggerDeletePlaylist(id);
                };

                document.getElementById('modalShowNowBtn').onclick = function () {
                    startPlaylist(currentModalVideos, id);
                    closePlaylistModal();
                };

                document.getElementById('playlistModal').classList.remove('hidden');
                document.getElementById('playlistModal').classList.add('flex');
            }

            function closePlaylistModal() {
                document.getElementById('playlistModal').classList.remove('flex');
                document.getElementById('playlistModal').classList.add('hidden');
            }

            function triggerDeletePlaylist(id) {
                if (confirm('Apakah kamu yakin ingin menghapus playlist ini?')) {
                    window.location.href = '/playlist/' + id + '/delete';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.playlist-bar').forEach(function (bar) {
                    bar.addEventListener('click', function () {
                        const items = JSON.parse(this.dataset.items || '[]');
                        const videos = JSON.parse(this.dataset.videos || '[]');
                        openPlaylistModal(this.dataset.id, this.dataset.start, this.dataset.end, items, videos);
                    });
                });
            });
        </script>
    </div>
@endsection