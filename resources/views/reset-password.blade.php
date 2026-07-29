<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Universitas Ciputra Signage</title>
    <!-- Tailwind CSS CDN & Font Awesome icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Config Warna Resmi UC Palette -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        uc: {
                            orange: '#F27D00',
                            dark: '#292F38',
                            gray: '#5F6870',
                            yellow: '#F9BF3B',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Poppins', sans-serif; }
        input::-ms-reveal, input::-ms-clear { display: none; }
    </style>
</head>
<body class="h-screen w-screen flex flex-col justify-between overflow-hidden relative bg-gray-100">

    <!-- Header Bar -->
    <header class="absolute top-0 left-0 w-full px-8 py-3 z-30 bg-white/70 backdrop-blur-md shadow-sm flex items-center">
        <img src="{{ asset('images/UC_LOGO_WARNA.png') }}" alt="Universitas Ciputra Logo" class="h-9 md:h-10 object-contain">
    </header>

    <!-- Main Content Background Area -->
    <main class="relative flex-1 flex items-center justify-center w-full h-full my-auto">
        <!-- Background Image with Gradient Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/UC_Background.png') }}" alt="UC Campus" class="w-full h-full object-cover">
            <!-- UC Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#F9BF3B]/85 via-[#F27D00]/50 to-transparent"></div>
        </div>

        <!-- Reset Password Card Container (Center Presisi) -->
        <div class="relative z-10 w-full max-w-md mx-4 my-auto">
            <div class="bg-white/90 backdrop-blur-md rounded-3xl p-6 md:p-8 shadow-2xl border border-white/50 text-center relative">
                
                <h2 class="text-2xl md:text-3xl font-bold text-uc-dark mb-4 tracking-tight">Reset Password</h2>

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded-xl text-xs mb-3 text-left">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('reset.password.update') }}" method="POST" class="space-y-3 text-left">
                    @csrf
                    <!-- Token & Email Hidden Input -->
                    <input type="hidden" name="token" value="{{ $token ?? '' }}">
                    <input type="hidden" name="email" value="{{ request('email') }}">

                    <!-- New Password Field -->
                    <div class="relative border-b-2 border-gray-300 focus-within:border-uc-orange transition-colors py-0.5">
                        <input type="password" id="password" name="password" placeholder="New Password" required
                            class="w-full bg-transparent pr-10 py-1.5 text-sm text-uc-dark focus:outline-none placeholder:text-uc-gray font-medium">
                        <button type="button" id="togglePassword" class="absolute right-2 top-2 text-uc-dark hover:text-uc-orange transition-colors">
                            <i class="fa-solid fa-lock" id="lockIcon1"></i>
                        </button>
                    </div>

                    <!-- Confirm New Password Field -->
                    <div class="relative border-b-2 border-gray-300 focus-within:border-uc-orange transition-colors py-0.5">
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm New Password" required
                            class="w-full bg-transparent pr-10 py-1.5 text-sm text-uc-dark focus:outline-none placeholder:text-uc-gray font-medium">
                        <button type="button" id="toggleConfirmPassword" class="absolute right-2 top-2 text-uc-dark hover:text-uc-orange transition-colors">
                            <i class="fa-solid fa-lock" id="lockIcon2"></i>
                        </button>
                    </div>

                    <!-- CHECKLIST CARD -->
                    <div class="bg-white/80 border border-gray-200 rounded-2xl p-3 text-[11px] text-left space-y-1.5 mt-2 shadow-sm">
                        <div id="check-length" class="flex items-center space-x-2 text-gray-400 transition-colors duration-200">
                            <i id="icon-length" class="fa-solid fa-check text-[10px]"></i>
                            <span>Minimal 8 karakter</span>
                        </div>
                        <div id="check-case" class="flex items-center space-x-2 text-gray-400 transition-colors duration-200">
                            <i id="icon-case" class="fa-solid fa-check text-[10px]"></i>
                            <span>Kombinasi huruf besar & kecil (A-Z)</span>
                        </div>
                        <div id="check-number" class="flex items-center space-x-2 text-gray-400 transition-colors duration-200">
                            <i id="icon-number" class="fa-solid fa-check text-[10px]"></i>
                            <span>Mengandung minimal 1 angka (0-9)</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full bg-uc-orange hover:bg-orange-600 text-white font-semibold py-3 rounded-2xl shadow-lg hover:shadow-uc-orange/40 transition-all duration-300 active:scale-95 text-center mt-3">
                        Reset Password
                    </button>
                </form>

                <!-- Footer Link -->
                <p class="text-xs text-uc-gray mt-5">
                    Remember your password? 
                    <a href="{{ url('/login') }}" class="text-uc-orange font-semibold hover:underline">Login here</a>
                </p>

            </div>
        </div>

        <!-- Mascot Yucca (Kembali ke Ukuran Awal) -->
        <div class="absolute -bottom-36 md:-bottom-40 lg:-bottom-14 right-2 md:right-8 lg:right-10 z-30 pointer-events-none">
            <img src="{{ asset('images/UC_Yucca.png') }}" alt="Mascot Yucca" class="w-64 md:w-80 lg:w-[400px] object-contain drop-shadow-2xl">
        </div>
    </main>

    <!-- Footer Orange Bar -->
    <footer class="bg-uc-orange text-white py-3.5 px-8 text-sm font-semibold relative z-20">
        © 2026 Universitas Ciputra Surabaya
    </footer>

    <!-- SCRIPT FOR TOGGLE PASSWORD & REAL-TIME CHECKLIST -->
    <script>
        const setupToggle = (buttonId, inputId, iconId) => {
            const btn = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            btn.addEventListener('click', () => {
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                icon.classList.toggle('fa-lock', !isPass);
                icon.classList.toggle('fa-lock-open', isPass);
            });
        };
        setupToggle('togglePassword', 'password', 'lockIcon1');
        setupToggle('toggleConfirmPassword', 'password_confirmation', 'lockIcon2');

        // Real-time Password Checklist
        const passwordInput = document.getElementById('password');
        const checkLength = document.getElementById('check-length');
        const checkCase = document.getElementById('check-case');
        const checkNumber = document.getElementById('check-number');

        passwordInput.addEventListener('input', function() {
            const val = this.value;
            updateCheckItem(checkLength, val.length >= 8);
            updateCheckItem(checkCase, /[A-Z]/.test(val) && /[a-z]/.test(val));
            updateCheckItem(checkNumber, /[0-9]/.test(val));
        });

        function updateCheckItem(element, isValid) {
            if (isValid) {
                element.classList.remove('text-gray-400');
                element.classList.add('text-emerald-600', 'font-medium');
            } else {
                element.classList.remove('text-emerald-600', 'font-medium');
                element.classList.add('text-gray-400');
            }
        }
    </script>
</body>
</html>