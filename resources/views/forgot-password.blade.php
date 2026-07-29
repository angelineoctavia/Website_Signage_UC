<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Universitas Ciputra Signage</title>
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

        <!-- Forgot Password Card Container (Center Presisi) -->
        <div class="relative z-10 w-full max-w-md mx-4 my-auto">
            <div class="bg-white/90 backdrop-blur-md rounded-3xl p-6 md:p-8 shadow-2xl border border-white/50 text-center relative">
                
                <h2 class="text-2xl md:text-3xl font-bold text-uc-dark mb-2 tracking-tight">Forgot Password</h2>
                <p class="text-xs md:text-sm text-uc-gray mb-5 leading-relaxed">
                    Enter your registered email address and we’ll send you a link to reset it.
                </p>

                <!-- Alert Message Notification -->
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded-xl text-xs mb-3 text-left">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded-xl text-xs mb-3 text-left">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('forgot.process') }}" method="POST" class="space-y-3 text-left">
                    @csrf
                    
                    <!-- Email Field -->
                    <div class="relative border-b-2 border-gray-300 focus-within:border-uc-orange transition-colors py-0.5">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Email" required
                            class="w-full bg-transparent pr-10 py-1.5 text-sm text-uc-dark focus:outline-none placeholder:text-uc-gray font-medium">
                        <div class="absolute right-2 top-2 text-uc-dark pointer-events-none">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        class="w-full bg-uc-orange hover:bg-orange-600 text-white font-semibold py-3 rounded-2xl shadow-lg hover:shadow-uc-orange/40 transition-all duration-300 active:scale-95 text-center mt-3">
                        Send Reset Link
                    </button>
                </form>

                <!-- Footer Link inside Card -->
                <p class="text-xs text-uc-gray mt-5">
                    Remember your password? 
                    <a href="{{ url('/login') }}" class="text-uc-orange font-semibold hover:underline">Login here</a>
                </p>

            </div>
        </div>

        <!-- Mascot Yucca -->
        <div class="absolute -bottom-36 md:-bottom-40 lg:-bottom-14 right-2 md:right-8 lg:right-10 z-30 pointer-events-none">
            <img src="{{ asset('images/UC_Yucca.png') }}" alt="Mascot Yucca" class="w-64 md:w-80 lg:w-[380px] object-contain drop-shadow-2xl">
        </div>
    </main>

    <!-- Footer Orange Bar -->
    <footer class="bg-uc-orange text-white py-3.5 px-8 text-sm font-semibold relative z-20">
        © 2026 Universitas Ciputra Surabaya
    </footer>

</body>
</html>