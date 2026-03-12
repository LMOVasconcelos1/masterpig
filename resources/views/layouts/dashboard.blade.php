<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"],
        select,
        textarea {
            padding: 0.75rem 1rem;
            font-size: 1.0625rem;
            border-radius: 0.75rem;
            min-height: 2.75rem;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }" x-effect="document.body.classList.toggle('overflow-hidden', mobileSidebarOpen)">
        <div x-show="mobileSidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="mobileSidebarOpen = false" x-cloak></div>

        <aside
            x-show="mobileSidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] overflow-y-auto bg-primary-800 text-white shadow-2xl lg:hidden"
            x-cloak
        >
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between h-16 px-4 bg-primary-900">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-piggy-bank text-2xl text-primary-300"></i>
                        <span class="text-xl font-bold tracking-wider uppercase">MasterPig</span>
                    </div>
                    <button type="button" class="p-2 rounded-lg text-primary-100 hover:bg-primary-800" @click="mobileSidebarOpen = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <div x-data="{ open: {{ request()->routeIs('dashboard') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-leaf w-6 text-center"></i>
                                <span class="ml-3 font-medium">Manejos</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <a href="{{ route('dashboard', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('dashboard') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Plantel Reprodutivo
                            </a>
                        </div>
                    </div>

                    <div x-data="{ open: {{ request()->is('admin/*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-screwdriver-wrench w-6 text-center"></i>
                                <span class="ml-3 font-medium">Utilitários</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <div x-data="{ subOpen: {{ request()->is('admin/causas*') || request()->is('admin/racoes*') || request()->is('admin/metas*') || request()->is('admin/usuarios*') ? 'true' : 'false' }} }">
                                <button @click="subOpen = !subOpen" class="flex items-center justify-between w-full px-4 py-2 text-sm text-primary-200 transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                    <span class="font-medium">Cadastros</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="subOpen ? 'rotate-180' : ''"></i>
                                </button>

                                <div x-show="subOpen" x-cloak class="mt-1 ml-2 space-y-1">
                                    @if(!config('masterpig.enforce_perfil_permissions', false) || Auth::user()->perfil === 'administrador')
                                        <a href="{{ route('admin.causas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.causas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                            <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                            Causas
                                        </a>
                                        <a href="{{ route('admin.racoes.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.racoes.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                            <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                            Rações
                                        </a>
                                        <a href="{{ route('admin.usuarios.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.usuarios.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                            <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                            Usuários
                                        </a>
                                        <a href="{{ route('admin.metas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.metas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                            <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                            Metas
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </aside>
        
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
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <div x-data="{ open: {{ request()->routeIs('dashboard') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-leaf w-6 text-center"></i>
                                <span x-show="sidebarOpen" class="ml-3 font-medium">Manejos</span>
                            </div>
                            <i x-show="sidebarOpen" class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open && sidebarOpen" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <a href="{{ route('dashboard', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('dashboard') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Plantel Reprodutivo
                            </a>
                        </div>
                    </div>

                    <!-- Utilitários Dropdown -->
                    <div x-data="{ open: {{ request()->is('admin/*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-screwdriver-wrench w-6 text-center"></i>
                                <span x-show="sidebarOpen" class="ml-3 font-medium">Utilitários</span>
                            </div>
                            <i x-show="sidebarOpen" class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="open && sidebarOpen" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <!-- Cadastros Sub-dropdown -->
                            <div x-data="{ subOpen: {{ request()->is('admin/causas*') || request()->is('admin/racoes*') || request()->is('admin/metas*') || request()->is('admin/usuarios*') ? 'true' : 'false' }} }">
                                <button @click="subOpen = !subOpen" class="flex items-center justify-between w-full px-4 py-2 text-sm text-primary-200 transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                    <span class="font-medium">Cadastros</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="subOpen ? 'rotate-180' : ''"></i>
                                </button>
                                
                                <div x-show="subOpen" x-cloak class="mt-1 ml-2 space-y-1">
                                    @if(!config('masterpig.enforce_perfil_permissions', false) || Auth::user()->perfil === 'administrador')
                                    <a href="{{ route('admin.causas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.causas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                        <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                        Causas
                                    </a>
                                    <a href="{{ route('admin.racoes.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.racoes.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                        <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                        Rações
                                    </a>
                                    <a href="{{ route('admin.usuarios.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.usuarios.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                        <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                        Usuários
                                    </a>
                                    <a href="{{ route('admin.metas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.metas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                        <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                        Metas
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
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
                <div class="flex items-center justify-between h-full px-4 sm:px-6">
                    <!-- Mobile Toggle -->
                    <button type="button" @click="mobileSidebarOpen = true" class="p-2 -ml-2 mr-2 rounded-lg lg:hidden focus:outline-none hover:bg-gray-100">
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
                        @if(($notificacoesCount ?? 0) > 0)
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="relative p-2 text-gray-500 hover:text-primary-600 transition-colors focus:outline-none">
                                    <i class="fa-solid fa-bell text-xl"></i>
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
                                        {{ $notificacoesCount }}
                                    </span>
                                </button>

                                <div
                                    x-show="open"
                                    @click.away="open = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-xl shadow-lg z-50 border border-gray-100 overflow-hidden"
                                    x-cloak
                                >
                                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                                        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">Notificações</div>
                                    </div>
                                    <div class="max-h-80 overflow-y-auto">
                                        @foreach(($notificacoes ?? []) as $n)
                                            <div class="px-4 py-3 border-b border-gray-100 last:border-b-0">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-0.5 w-9 h-9 rounded-xl flex items-center justify-center bg-red-50 text-red-700">
                                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-gray-900">{{ $n['titulo'] ?? 'Notificação' }}</div>
                                                        <div class="text-xs text-gray-600 mt-0.5">{{ $n['descricao'] ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="h-8 border-l border-gray-200"></div>
                        @endif

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ userMenuOpen: false }">
                            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-3 cursor-pointer group focus:outline-none">
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-semibold text-gray-700 group-hover:text-primary-600 transition-colors">{{ Auth::user()->nome }}</span>
                                </div>
                                <img class="w-10 h-10 rounded-full border-2 border-primary-100 group-hover:border-primary-300 transition-all shadow-sm object-cover" src="{{ Auth::user()->foto_perfil_url ?? ('https://ui-avatars.com/api/?name='.urlencode(Auth::user()->nome).'&background=3b82f6&color=fff') }}" alt="User Avatar">
                            </button>

                            <!-- Dropdown Menu -->
                            <div 
                                x-show="userMenuOpen" 
                                @click.away="userMenuOpen = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100"
                                x-cloak
                            >
                                <a href="{{ route('profile.edit', [], false) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-colors">
                                    <i class="fa-solid fa-user w-5"></i>
                                    <span>Perfil</span>
                                </a>
                                <div class="my-1 border-t border-gray-100"></div>
                                <form method="POST" action="{{ route('logout', [], false) }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors text-left">
                                        <i class="fa-solid fa-right-from-bracket w-5"></i>
                                        <span>Sair</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-3 sm:p-6">
                <div class="max-w-7xl mx-auto">
                    @php($pageTitle = trim($__env->yieldContent('page_title', '')))
                    @if($pageTitle !== '')
                        <div class="flex items-center justify-between mb-8">
                            <h1 class="text-2xl font-bold text-gray-800">{{ $pageTitle }}</h1>
                        </div>
                    @endif

                    <!-- Content -->
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
