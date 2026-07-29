@extends('layouts.app')

@section('content')
    <main class="flex-1 p-6 lg:p-10 max-w-[1400px] w-full mx-auto">

        <!-- ALERT BERHASIL GANTI PASSWORD (AUTO-DISMISS 5 DETIK & ADA TOMBOL CLOSE) -->
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

        <form id="profile-form" action="{{ route('profile.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- GRID 2 KOLOM SEJAJAR -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

                <!-- KOLOM KIRI: MY PROFILE -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
                    <h2 class="text-xl font-bold text-uc-dark">My Profile</h2>

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">Name</label>
                        <input type="text"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-3.5 text-xs text-uc-dark focus:outline-none"
                            value="{{ Auth::user()->users_name ?? 'hello' }}" readonly>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">Email address</label>
                        <input type="email"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-3.5 text-xs text-uc-dark focus:outline-none"
                            value="{{ Auth::user()->users_email ?? 'hello@gmail.com' }}" readonly>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">Role</label>
                        <input type="text"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-3.5 text-xs text-uc-dark focus:outline-none"
                            value="{{ Auth::user()->users_role == 1 ? 'Admin' : (Auth::user()->users_role == 2 ? 'TV Device' : 'User') }}"
                            readonly>
                    </div>

                    <!-- Account Created -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">Account Created</label>
                        <input type="text"
                            class="w-full bg-gray-100 border border-gray-200 rounded-xl px-5 py-3.5 text-xs text-gray-400 cursor-not-allowed"
                            value="{{ Auth::user()->users_acc_created ? \Carbon\Carbon::parse(Auth::user()->users_acc_created)->format('d M Y') : '23 Jul 2026' }}"
                            readonly>
                    </div>
                </div>

                <!-- KOLOM KANAN: CHANGE PASSWORD -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-6">
                    <h2 class="text-xl font-bold text-uc-dark">Change Password</h2>

                    <!-- Current Password -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">Current Password</label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" placeholder="••••••••"
                                autocomplete="new-password"
                                class="w-full bg-gray-50 border {{ $errors->has('current_password') ? 'border-red-500' : 'border-gray-200' }} rounded-xl px-5 py-3.5 pr-12 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors [&::-ms-reveal]:hidden [&::-ms-clear]:hidden"
                                required>
                            <button type="button" id="toggleCurrentPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-uc-dark focus:outline-none">
                                <i id="eyeIconCurrent" class="fa-solid fa-eye-slash text-xs"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="text-red-500 text-[11px] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">New Password</label>
                        <div class="relative">
                            <input type="password" id="new_password" name="new_password" placeholder="••••••••"
                                autocomplete="new-password"
                                class="w-full bg-gray-50 border {{ $errors->has('new_password') ? 'border-red-500' : 'border-gray-200' }} rounded-xl px-5 py-3.5 pr-12 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors [&::-ms-reveal]:hidden [&::-ms-clear]:hidden"
                                required>
                            <button type="button" id="toggleNewPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-uc-dark focus:outline-none">
                                <i id="eyeIconNew" class="fa-solid fa-eye-slash text-xs"></i>
                            </button>
                        </div>
                        @error('new_password')
                            <p class="text-red-500 text-[11px] mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Requirement Box (Abu-abu ke Hijau) -->
                    <div class="bg-gray-50/50 border border-gray-200 rounded-xl p-4 space-y-2 text-xs">
                        <div id="req-length" class="flex items-center space-x-2 text-gray-400 transition-colors">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>Minimal 8 karakter</span>
                        </div>
                        <div id="req-case" class="flex items-center space-x-2 text-gray-400 transition-colors">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>Kombinasi huruf besar & kecil (A-Z)</span>
                        </div>
                        <div id="req-number" class="flex items-center space-x-2 text-gray-400 transition-colors">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>Mengandung minimal 1 angka (0-9)</span>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label class="block text-xs font-semibold text-uc-dark mb-2">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                                placeholder="••••••••" autocomplete="new-password"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-3.5 pr-12 text-xs text-uc-dark focus:outline-none focus:border-uc-orange transition-colors [&::-ms-reveal]:hidden [&::-ms-clear]:hidden"
                                required>
                            <button type="button" id="toggleConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-uc-dark focus:outline-none">
                                <i id="eyeIconConfirm" class="fa-solid fa-eye-slash text-xs"></i>
                            </button>
                        </div>
                        <p id="error-match" class="text-red-500 text-[11px] mt-1.5 hidden font-medium">Password baru dan
                            konfirmasi tidak sama!</p>
                    </div>

                </div>

            </div>

            <!-- TOMBOL SAVE DI POJOK KANAN BAWAH -->
            <div class="flex justify-end mt-8">
                <button type="submit" id="save-btn"
                    class="bg-uc-green hover:bg-emerald-600 text-white font-semibold px-10 py-3.5 rounded-xl text-xs transition-all shadow-sm">
                    Save
                </button>
            </div>

        </form>

    </main>

    <!-- Style tambahan untuk efek shake/cringe error -->
    <style>
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-6px);
            }

            40%,
            80% {
                transform: translateX(6px);
            }
        }

        .shake-error {
            animation: shake 0.4s ease-in-out;
            border-color: #ef4444 !important;
        }
    </style>

    <script>
        // Fungsi untuk menutup alert sukses secara manual atau otomatis
        function closeAlert() {
            const alertBox = document.getElementById('success-alert');
            if (alertBox) {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500); // Hilang setelah transisi fade out selesai
            }
        }

        // Otomatis hilangkan alert setelah 5 detik (5000 ms)
        document.addEventListener('DOMContentLoaded', (event) => {
            const alertBox = document.getElementById('success-alert');
            if (alertBox) {
                setTimeout(() => {
                    closeAlert();
                }, 5000);
            }
        });

        // Script Toggle Show/Hide Password
        function setupPasswordToggle(buttonId, inputId, iconId) {
            const toggleBtn = document.querySelector(buttonId);
            const passwordField = document.querySelector(inputId);
            const eyeIconElement = document.querySelector(iconId);

            if (toggleBtn && passwordField && eyeIconElement) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);

                    eyeIconElement.classList.toggle('fa-eye');
                    eyeIconElement.classList.toggle('fa-eye-slash');
                });
            }
        }

        setupPasswordToggle('#toggleCurrentPassword', '#current_password', '#eyeIconCurrent');
        setupPasswordToggle('#toggleNewPassword', '#new_password', '#eyeIconNew');
        setupPasswordToggle('#toggleConfirmPassword', '#new_password_confirmation', '#eyeIconConfirm');

        // Live Validation Password Requirements
        const passwordInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('new_password_confirmation');
        const reqLength = document.getElementById('req-length');
        const reqCase = document.getElementById('req-case');
        const reqNumber = document.getElementById('req-number');
        const errorMatch = document.getElementById('error-match');
        const profileForm = document.getElementById('profile-form');

        function checkRequirements(val) {
            const isLengthValid = val.length >= 8;
            if (isLengthValid) {
                reqLength.classList.remove('text-gray-400');
                reqLength.classList.add('text-uc-green', 'font-semibold');
            } else {
                reqLength.classList.remove('text-uc-green', 'font-semibold');
                reqLength.classList.add('text-gray-400');
            }

            const isCaseValid = /[a-z]/.test(val) && /[A-Z]/.test(val);
            if (isCaseValid) {
                reqCase.classList.remove('text-gray-400');
                reqCase.classList.add('text-uc-green', 'font-semibold');
            } else {
                reqCase.classList.remove('text-uc-green', 'font-semibold');
                reqCase.classList.add('text-gray-400');
            }

            const isNumberValid = /\d/.test(val);
            if (isNumberValid) {
                reqNumber.classList.remove('text-gray-400');
                reqNumber.classList.add('text-uc-green', 'font-semibold');
            } else {
                reqNumber.classList.remove('text-uc-green', 'font-semibold');
                reqNumber.classList.add('text-gray-400');
            }

            return isLengthValid && isCaseValid && isNumberValid;
        }

        passwordInput.addEventListener('input', function() {
            checkRequirements(passwordInput.value);
        });

        // Validasi saat tombol Save ditekan
        profileForm.addEventListener('submit', function(e) {
            const passVal = passwordInput.value;
            const confirmVal = confirmInput.value;
            const areRequirementsMet = checkRequirements(passVal);
            const isMatch = passVal === confirmVal;

            if (!areRequirementsMet || !isMatch) {
                e.preventDefault();

                if (!areRequirementsMet) {
                    passwordInput.classList.add('shake-error');
                    setTimeout(() => passwordInput.classList.remove('shake-error'), 400);
                }

                if (!isMatch) {
                    confirmInput.classList.add('shake-error');
                    errorMatch.classList.remove('hidden');
                    setTimeout(() => confirmInput.classList.remove('shake-error'), 400);
                } else {
                    errorMatch.classList.add('hidden');
                }
            }
        });

        confirmInput.addEventListener('input', function() {
            if (confirmInput.value === passwordInput.value) {
                errorMatch.classList.add('hidden');
            }
        });
    </script>
@endsection
