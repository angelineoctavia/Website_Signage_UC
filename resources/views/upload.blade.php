@extends('layouts.app')

@section('content')
    <main class="flex-1 p-6 lg:p-8 max-w-[1400px] w-full mx-auto">

        <!-- ALERT SUCCESS -->
        @if (session('success'))
            <div
                class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl text-xs flex items-center space-x-2 shadow-sm">
                <i class="fa-solid fa-circle-check text-sm"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <!-- ALERT ERROR (server-side, dari validasi/Drive) -->
        @if ($errors->any())
            <div
                class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs flex items-center space-x-2 shadow-sm">
                <i class="fa-solid fa-circle-exclamation text-sm"></i>
                <span class="font-medium">{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- ALERT ERROR (client-side JS) -->
        <div id="error-alert"
            class="hidden mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation text-sm"></i>
                <span id="error-message-text" class="font-medium"></span>
            </div>
            <button type="button" onclick="hideError()" class="text-red-400 hover:text-red-700">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" id="upload-form"
                onsubmit="return validateForm(event)">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- KIRI: DRAG & DROP UPLOAD -->
                    <div class="lg:col-span-6 flex flex-col space-y-2">
                        <label class="text-sm font-semibold text-uc-dark">File Upload (Auto-Upload ke Google Drive)</label>
                        <div id="drop-zone"
                            class="border-2 border-dashed border-gray-200 rounded-2xl p-8 flex flex-col items-center justify-center text-center bg-gray-50/50 hover:bg-gray-50 transition-colors relative h-[320px]">

                            <input type="file" name="file" id="file-input"
                                class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="handleFileSelect(this)">

                            <!-- Initial State -->
                            <div id="upload-placeholder" class="flex flex-col items-center pointer-events-none">
                                <div
                                    class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mb-4 text-uc-orange">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
                                </div>
                                <p class="text-xs text-gray-600 font-medium mb-1">
                                    Drag & drop file di sini, atau <span class="text-uc-orange font-semibold">browse</span>
                                </p>
                                <p class="text-[11px] text-gray-400">Supports MP4, JPG, PNG (Max 50MB)</p>
                            </div>

                            <!-- Preview File -->
                            <div id="file-preview-container" class="hidden flex flex-col items-center relative z-20">
                                <button type="button" onclick="resetUpload(event)"
                                    class="absolute -top-12 -right-4 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow transition-all z-30"
                                    title="Hapus File">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                                <i id="preview-icon" class="fa-solid fa-file-arrow-up text-4xl text-uc-orange mb-2"></i>
                                <p id="file-name-display" class="text-xs font-semibold text-uc-dark max-w-[220px] truncate">
                                </p>
                                <span class="text-[11px] text-green-600 mt-1 font-medium">
                                    <i class="fa-solid fa-check-circle"></i> File siap di-upload & di-arsip ke Drive
                                </span>
                            </div>
                        </div>
                        <span class="text-[11px] text-gray-400">File akan otomatis ter-upload ke Google Drive & tersimpan ke
                            database saat kamu klik "Upload".</span>
                    </div>

                    <!-- KANAN: INPUT FIELDS -->
                    <div class="lg:col-span-6 pt-[34px] space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-uc-dark mb-1.5">Content Title</label>
                            <input type="text" name="content_title" placeholder="Masukkan judul konten..."
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                                required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-uc-dark mb-1.5">Category</label>
                            <select name="category"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors cursor-pointer"
                                required>
                                <option value="" disabled selected>Pilih Kategori...</option>
                                <option value="Event">Event</option>
                                <option value="Daily">Daily</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-uc-dark mb-1.5">Media Type</label>
                                <input type="text" name="media_type" id="media-type-display" value="Auto-detect" readonly
                                    class="w-full bg-gray-100 border border-gray-200 rounded-xl px-4 py-3 text-xs text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-uc-dark mb-1.5">Duration (MM:SS)</label>
                                <input type="text" name="duration" id="duration-input" placeholder="00:00"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                                    required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ url('/dashboard') }}"
                        class="bg-red-400 hover:bg-red-500 text-white font-semibold px-8 py-3.5 rounded-xl text-xs transition-all shadow-sm">
                        Cancel
                    </a>
                    <button type="submit" id="submit-btn"
                        class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-8 py-3.5 rounded-xl text-xs transition-all shadow-sm flex items-center space-x-2">
                        <span id="submit-text">Upload</span>
                        <i id="submit-spinner" class="hidden fa-solid fa-spinner fa-spin"></i>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- SCRIPT -->
    <script>
        function showError(message) {
            const alertBox = document.getElementById('error-alert');
            document.getElementById('error-message-text').innerText = message;
            alertBox.classList.remove('hidden');
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function hideError() {
            document.getElementById('error-alert').classList.add('hidden');
        }

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                let file = input.files[0];
                let extension = file.name.split('.').pop().toUpperCase(); // ambil ekstensi asli
                document.getElementById('upload-placeholder').classList.add('hidden');
                document.getElementById('file-preview-container').classList.remove('hidden');
                document.getElementById('file-name-display').innerText = file.name;

                let iconEl = document.getElementById('preview-icon');
                let mediaTypeDisplay = document.getElementById('media-type-display');
                let durationInput = document.getElementById('duration-input');

                if (file.type.includes('image')) {
                    iconEl.className = "fa-solid fa-file-image text-4xl text-uc-orange mb-2";
                    mediaTypeDisplay.value = extension; // contoh: "PNG", "JPG"
                    durationInput.value = '00:05';
                } else if (file.type.includes('video')) {
                    iconEl.className = "fa-solid fa-file-video text-4xl text-uc-orange mb-2";
                    mediaTypeDisplay.value = extension; // contoh: "MP4", "MOV"

                    let videoElement = document.createElement('video');
                    videoElement.preload = 'metadata';
                    videoElement.onloadedmetadata = function() {
                        window.URL.revokeObjectURL(videoElement.src);
                        let durationSeconds = Math.round(videoElement.duration);
                        let minutes = Math.floor(durationSeconds / 60);
                        let seconds = durationSeconds % 60;
                        durationInput.value = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }
                    videoElement.src = URL.createObjectURL(file);
                }
            }
        }

        function resetUpload(event) {
            event.stopPropagation();
            const fileInput = document.getElementById('file-input');
            fileInput.value = '';
            document.getElementById('file-preview-container').classList.add('hidden');
            document.getElementById('upload-placeholder').classList.remove('hidden');
            document.getElementById('media-type-display').value = 'Auto-detect';
            document.getElementById('duration-input').value = '';
        }

        function validateForm(event) {
            hideError();
            const fileInput = document.getElementById('file-input');
            const durationInput = document.getElementById('duration-input').value.trim();

            if (!fileInput.files || fileInput.files.length === 0) {
                showError('Silakan pilih file terlebih dahulu!');
                event.preventDefault();
                return false;
            }

            const regexDuration = /^(\d{2}):(\d{2})$/;
            if (!durationInput.match(regexDuration)) {
                showError('Format Duration salah! Gunakan format MM:SS (contoh: 00:30 atau 01:15)');
                event.preventDefault();
                return false;
            }

            // Kasih feedback visual: upload sedang berjalan (Drive + DB dalam satu proses)
            const submitBtn = document.getElementById('submit-btn');
            document.getElementById('submit-text').innerText = 'Uploading...';
            document.getElementById('submit-spinner').classList.remove('hidden');
            submitBtn.disabled = true;

            return true;
        }
    </script>
@endsection
