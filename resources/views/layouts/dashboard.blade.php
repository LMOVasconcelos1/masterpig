<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MasterPig - @yield('title', 'Dashboard')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS (via CDN for quick prototype, ideally via Vite) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a', 950: '#172554',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui'],
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: width 0.3s ease-in-out; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: true }">
        
        <!-- Sidebar -->
        <aside 
            :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="sidebar-transition relative z-20 flex-shrink-0 hidden h-full overflow-y-auto bg-primary-800 text-white lg:block shadow-xl"
        >
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-16 px-4 bg-primary-900">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-piggy-bank text-2xl text-primary-300"></i>
                        <span x-show="sidebarOpen" class="text-xl font-bold tracking-wider uppercase transition-opacity duration-300">MasterPig</span>
                    </div>
                </div>

                <!-- Nav Items -->
                <nav class="flex-1 px-3 py-4 space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-white transition-colors rounded-lg bg-primary-700/50 hover:bg-primary-700">
                        <i class="fa-solid fa-gauge-high w-6 text-center"></i>
                        <span x-show="sidebarOpen" class="ml-3 font-medium">Dashboard</span>
                    </a>

                    <div class="pt-4 pb-2">
                        <span x-show="sidebarOpen" class="px-4 text-xs font-semibold tracking-wider text-primary-300 uppercase">Gerenciamento</span>
                        <hr x-show="!sidebarOpen" class="mx-4 border-primary-700">
                    </div>

                    <a href="#" class="flex items-center px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                        <i class="fa-solid fa-users w-6 text-center"></i>
                        <span x-show="sidebarOpen" class="ml-3 font-medium">Usuários</span>
                    </a>

                    <a href="#" class="flex items-center px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                        <i class="fa-solid fa-box w-6 text-center"></i>
                        <span x-show="sidebarOpen" class="ml-3 font-medium">Produtos</span>
                    </a>

                    <a href="#" class="flex items-center px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                        <i class="fa-solid fa-chart-line w-6 text-center"></i>
                        <span x-show="sidebarOpen" class="ml-3 font-medium">Relatórios</span>
                    </a>

                    <div class="pt-4 pb-2">
                        <span x-show="sidebarOpen" class="px-4 text-xs font-semibold tracking-wider text-primary-300 uppercase">Configurações</span>
                        <hr x-show="!sidebarOpen" class="mx-4 border-primary-700">
                    </div>

                    <a href="#" class="flex items-center px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                        <i class="fa-solid fa-gear w-6 text-center"></i>
                        <span x-show="sidebarOpen" class="ml-3 font-medium">Ajustes</span>
                    </a>
                </nav>

                <!-- Sidebar Footer -->
                <div class="p-4 bg-primary-900">
                    <button @click="sidebarOpen = !sidebarOpen" class="flex items-center justify-center w-full px-4 py-2 text-primary-100 transition-colors rounded-lg hover:bg-primary-800">
                        <i class="fa-solid" :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 w-full overflow-hidden">
            <!-- Navbar -->
            <header class="z-10 flex-shrink-0 h-16 bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center justify-between h-full px-6">
                    <!-- Mobile Toggle -->
                    <button class="p-1 -ml-1 mr-5 rounded-md lg:hidden focus:outline-none focus:shadow-outline-primary">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>

                    <!-- Search -->
                    <div class="hidden md:flex relative text-gray-400 focus-within:text-primary-500">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="block w-64 pl-10 pr-3 py-2 text-sm text-gray-900 bg-gray-100 border-transparent rounded-lg focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" placeholder="Buscar informações...">
                    </div>

                    <!-- Right Nav -->
                    <div class="flex items-center space-x-4">
                        <button class="relative p-2 text-gray-400 hover:text-primary-500 transition-colors">
                            <i class="fa-solid fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 border-2 border-white rounded-full"></span>
                        </button>

                        <div class="h-8 border-l border-gray-200"></div>

                        <div class="flex items-center space-x-3 cursor-pointer group">
                            <div class="flex flex-col items-end">
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-primary-600 transition-colors">Admin User</span>
                                <span class="text-xs text-gray-500">Super Admin</span>
                            </div>
                            <img class="w-10 h-10 rounded-full border-2 border-primary-100 group-hover:border-primary-300 transition-all shadow-sm" src="https://ui-avatars.com/api/?name=Admin+User&background=3b82f6&color=fff" alt="User Avatar">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Page Heading -->
                    <div class="flex items-center justify-between mb-8">
                        <h1 class="text-2xl font-bold text-gray-800">@yield('page_title', 'Dashboard')</h1>
                        <button class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md">
                            <i class="fa-solid fa-download mr-2"></i>
                            Gerar Relatório
                        </button>
                    </div>

                    <!-- Content -->
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
