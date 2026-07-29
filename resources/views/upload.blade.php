@extends('layouts.app')

@section('content')
<!-- MAIN UPLOAD CONTENT -->
<main class="flex-1 p-6 lg:p-8 max-w-[1400px] w-full mx-auto">

    <!-- ALERT ERROR STYLING -->
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

    <!-- Form Card Container -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <form action="{{ url('/upload') }}" method="POST" enctype="multipart/form-data" id="upload-form"
            onsubmit="return validateForm(event)">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- LEFT COLUMN: FILE UPLOAD ZONE (6 Cols) -->
                <div class="lg:col-span-6 flex flex-col space-y-2">
                    <label class="text-sm font-semibold text-uc-dark">File Upload</label>

                    <div id="drop-zone"
                        class="border-2 border-dashed border-gray-200 rounded-2xl p-8 flex flex-col items-center justify-center text-center bg-gray-50/50 hover:bg-gray-50 transition-colors relative h-[320px]">
                        <input type="file" name="file" id="file-input"
                            class="absolute inset-0 opacity-0 cursor-pointer z-10" accept=".mp4,.jpg,.jpeg,.png"
                            required onchange="handleFileSelect(this)">

                        <!-- Initial Placeholder -->
                        <div id="upload-placeholder" class="flex flex-col items-center">
                            <div
                                class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mb-4 text-uc-orange">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
                            </div>
                            <p class="text-xs text-gray-600 font-medium mb-1">
                                Drag and drop your files here! or <span
                                    class="text-uc-orange font-semibold underline">browse</span>
                            </p>
                        </div>

                        <!-- Success Preview State + Tombol Close (X) di Atas Kanan -->
                        <div id="file-preview-container" class="hidden flex flex-col items-center relative z-20">
                            <button type="button" onclick="resetUpload(event)"
                                class="absolute -top-12 -right-4 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow transition-all"
                                title="Hapus / Ganti File">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                            <i id="preview-icon" class="fa-solid fa-file-video text-4xl text-uc-orange mb-2"></i>
                            <p id="file-name-display"
                                class="text-xs font-semibold text-uc-dark max-w-[220px] truncate"></p>
                            <span class="text-[10px] text-green-600 mt-1 font-medium"><i
                                    class="fa-solid fa-check-circle"></i> File siap di-upload</span>
                        </div>
                    </div>

                    <span class="text-[11px] text-gray-400">Supports MP4, JPG, PNG (Max 50MB, Wajib Portrait, Max
                        Duration: 1:30 / 90s)</span>
                </div>

                <!-- RIGHT COLUMN: INPUT FIELDS (6 Cols) -->
                <div class="lg:col-span-6 space-y-4">

                    <!-- Content Title -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-1.5">Content Title</label>
                        <input type="text" name="content_title" placeholder="Masukkan judul konten..."
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                            required>
                    </div>

                    <!-- Start Date & Time -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-1.5">Start Date & Time</label>
                        <input type="datetime-local" name="start_datetime" id="start_datetime"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                            required>
                    </div>

                    <!-- End Date & Time -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-1.5">End Date & Time</label>
                        <input type="datetime-local" name="end_datetime" id="end_datetime"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                            required>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-1.5">Category</label>
                        <div class="relative">
                            <select name="category"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors cursor-pointer appearance-none"
                                required>
                                <option value="" disabled selected>Pilih Kategori...</option>
                                <option value="Event">Event</option>
                                <option value="Daily">Daily</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-uc-gray">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Media Type & Duration -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-uc-dark mb-1.5">Media Type</label>
                            <input type="text" id="media-type-display" value="Auto-detect" readonly
                                class="w-full bg-gray-100 border border-gray-200 rounded-xl px-4 py-3 text-xs text-gray-400 cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-uc-dark mb-1.5">Duration (MM:SS)</label>
                            <input type="text" name="duration" id="duration-input" value="00:00"
                                placeholder="00:00"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors"
                                required>
                        </div>
                    </div>

                </div>

            </div>

            <!-- ACTION BUTTONS (Padding px-8 disamakan persis dengan halaman playlist) -->
            <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-100">
                <a href="{{ url('/dashboard') }}"
                    class="bg-red-400 hover:bg-red-500 text-white font-semibold px-8 py-3.5 rounded-xl text-xs transition-all shadow-sm text-center">
                    Cancel
                </a>
                <button type="submit" id="submit-btn"
                    class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-8 py-3.5 rounded-xl text-xs transition-all shadow-sm flex items-center space-x-2">
                    <span id="submit-text">Upload</span>
                    <i id="submit-spinner" class="fa-solid fa-spinner fa-spin hidden"></i>
                </button>
            </div>

        </form>
    </div>

</main>

<!-- SCRIPT -->
<script>
    function showError(message) {
        const alertBox = document.getElementById('error-alert');
        const messageText = document.getElementById('error-message-text');
        messageText.innerText = message;
        alertBox.classList.remove('hidden');
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function hideError() {
        const alertBox = document.getElementById('error-alert');
        alertBox.classList.add('hidden');
    }

    // Validasi Tanggal & Durasi saat Submit
    function validateForm(event) {
        hideError();

        const startVal = document.getElementById('start_datetime').value;
        const endVal = document.getElementById('end_datetime').value;
        const durationInput = document.getElementById('duration-input').value.trim();

        if (startVal && endVal) {
            const startDate = new Date(startVal);
            const endDate = new Date(endVal);

            if (endDate <= startDate) {
                showError('End Date & Time tidak boleh lebih awal atau sama dengan Start Date & Time!');
                event.preventDefault();
                return false;
            }
        }

        const regexDuration = /^(\d{2}):(\d{2})$/;
        const durMatch = durationInput.match(regexDuration);

        if (!durMatch) {
            showError('Format Duration salah! Gunakan format MM:SS (contoh: 00:10 atau 01:30)');
            event.preventDefault();
            return false;
        }

        const totalSeconds = parseInt(durMatch[1]) * 60 + parseInt(durMatch[2]);
        if (totalSeconds > 90) {
            showError('Durasi maksimal adalah 1 menit 30 detik (01:30)!');
            event.preventDefault();
            return false;
        }

        // Ubah tombol jadi loading state saat validasi lolos dan data dikirim ke backend
        const submitBtn = document.getElementById('submit-btn');
        const submitText = document.getElementById('submit-text');
        const submitSpinner = document.getElementById('submit-spinner');

        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        submitText.innerText = 'Mengunggah...';
        submitSpinner.classList.remove('hidden');

        return true;
    }

    function formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function resetUpload(e) {
        if (e) e.stopPropagation();
        const fileInput = document.getElementById('file-input');
        const placeholder = document.getElementById('upload-placeholder');
        const previewContainer = document.getElementById('file-preview-container');
        const mediaTypeDisplay = document.getElementById('media-type-display');
        const durationInput = document.getElementById('duration-input');

        fileInput.value = '';
        previewContainer.classList.add('hidden');
        placeholder.classList.remove('hidden');
        mediaTypeDisplay.value = 'Auto-detect';
        durationInput.value = '00:00';
        durationInput.readOnly = false;
        durationInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        hideError();
    }

    function handleFileSelect(input) {
        hideError();
        if (input.files && input.files[0]) {
            const file = input.files[0];

            if (file.size > 50 * 1024 * 1024) {
                showError('Ukuran file terlalu besar! Maksimal 50MB.');
                input.value = '';
                return;
            }

            if (file.type.includes('video')) {
                const videoElement = document.createElement('video');
                videoElement.preload = 'metadata';
                videoElement.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(videoElement.src);
                    const durationSec = Math.round(videoElement.duration);
                    const width = videoElement.videoWidth;
                    const height = videoElement.videoHeight;

                    if (width > height) {
                        showError(
                            'Video harus berformat Portrait (tegak) untuk Samsung Signage 24 inch. Video Landscape tidak diperbolehkan.'
                            );
                        input.value = '';
                        return;
                    }

                    if (durationSec > 90) {
                        showError('Durasi video terlalu panjang! Maksimal 1 menit 30 detik (90 detik).');
                        input.value = '';
                        return;
                    }

                    runUploadProcess(file, formatDuration(durationSec), 'video');
                }
                videoElement.onerror = function() {
                    showError('Gagal membaca file video. Pastikan format valid.');
                    input.value = '';
                }
                videoElement.src = URL.createObjectURL(file);

            } else if (file.type.includes('image')) {
                const imgElement = new Image();
                imgElement.onload = function() {
                    const width = imgElement.width;
                    const height = imgElement.height;

                    if (width > height) {
                        showError(
                            'Gambar harus berformat Portrait (tegak) untuk Samsung Signage 24 inch. Gambar Landscape tidak diperbolehkan.'
                            );
                        input.value = '';
                        return;
                    }

                    runUploadProcess(file, '00:10', 'image');
                }
                imgElement.onerror = function() {
                    showError('Gagal membaca file gambar. Pastikan format valid.');
                    input.value = '';
                }
                imgElement.src = URL.createObjectURL(file);
            } else {
                showError('Format file tidak didukung! Hanya mendukung MP4, JPG, PNG.');
                input.value = '';
            }
        }
    }

    function runUploadProcess(file, formattedDuration, type) {
        const placeholder = document.getElementById('upload-placeholder');
        const previewContainer = document.getElementById('file-preview-container');
        const fileNameDisplay = document.getElementById('file-name-display');
        const previewIcon = document.getElementById('preview-icon');
        const mediaTypeDisplay = document.getElementById('media-type-display');
        const durationInput = document.getElementById('duration-input');

        placeholder.classList.add('hidden');
        previewContainer.classList.remove('hidden');
        fileNameDisplay.innerText = file.name;

        if (type === 'video') {
            mediaTypeDisplay.value = 'Video (' + file.type + ')';
            previewIcon.className = 'fa-solid fa-file-video text-4xl text-uc-orange mb-2';

            durationInput.value = formattedDuration;
            durationInput.readOnly = true;
            durationInput.classList.add('bg-gray-100', 'cursor-not-allowed');
        } else {
            mediaTypeDisplay.value = 'Image (' + file.type + ')';
            previewIcon.className = 'fa-solid fa-file-image text-4xl text-uc-orange mb-2';

            durationInput.value = formattedDuration;
            durationInput.readOnly = false;
            durationInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }
    }
</script>
@endsection