<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universitas Ciputra Signage</title>
    <!-- Tailwind CSS CDN & Font Awesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
                            blue: '#0084FF',
                            green: '#00C853',
                            purple: '#5B43D6'
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
<body class="min-h-screen bg-gray-100 flex flex-col justify-between overflow-x-hidden">

    <!-- TOP NAVBAR -->
    <header class="sticky top-0 z-50 bg-white shadow-sm px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center">
            <img src="{{ asset('images/UC_LOGO_WARNA.png') }}" alt="Universitas Ciputra Logo" class="h-9 object-contain">
        </div>

        <nav class="flex items-center space-x-10 text-sm font-semibold relative">
            <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard*') ? 'text-uc-orange' : 'text-uc-gray hover:text-uc-orange' }} transition-colors">Dashboard</a>
            <a href="{{ url('/upload') }}" class="{{ request()->is('upload*') ? 'text-uc-orange' : 'text-uc-gray hover:text-uc-orange' }} transition-colors">Upload</a>
            <a href="{{ url('/playlist') }}" class="{{ request()->is('playlist*') ? 'text-uc-orange' : 'text-uc-gray hover:text-uc-orange' }} transition-colors">Playlist</a>
            
            <!-- Account Menu with Dropdown Trigger -->
            <div class="relative">
                <button onclick="toggleAccountMenu()" id="account-menu-btn"
                    class="{{ request()->is('account*') || request()->is('profile*') ? 'text-uc-orange' : 'text-uc-gray' }} hover:text-uc-orange transition-colors flex items-center space-x-1 focus:outline-none">
                    <span>Account</span>
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </button>

                <!-- Dropdown Account -->
                <div id="account-dropdown"
                    class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50 text-xs font-medium">
                    <a href="{{ route('profile.index') }}"
                        class="flex items-center px-4 py-2.5 text-uc-dark hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-gear mr-2.5 text-uc-gray"></i> Account Settings
                    </a>
                    <a href="{{ route('logout') }}"
                        class="flex items-center px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fa-solid fa-right-from-bracket mr-2.5"></i> Log Out
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- KONTEN UTAMA -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="bg-uc-orange text-white py-3.5 px-8 text-sm font-semibold z-20">
        © 2026 Universitas Ciputra Surabaya
    </footer>

    <!-- SCRIPT DROPDOWN & GLOBAL CONFIG -->
    <script>
        function toggleAccountMenu() {
            const dropdown = document.getElementById('account-dropdown');
            dropdown.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            const btn = document.getElementById('account-menu-btn');
            const dropdown = document.getElementById('account-dropdown');
            if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>