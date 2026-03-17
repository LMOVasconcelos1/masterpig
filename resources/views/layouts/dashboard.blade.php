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
        input[type="date"],
        input[type="time"],
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
        <div
            x-data="{ open: false, message: '', type: 'success' }"
            x-init="
                window.addEventListener('toast', (e) => {
                    message = e.detail.message;
                    type = e.detail.type || 'success';
                    open = true;
                    setTimeout(() => open = false, 4000);
                });
            "
        >
            <div
                x-show="open"
                x-transition:enter="transform ease-out duration-500 transition"
                x-transition:enter-start="translate-y-[-100%] opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white shadow-2xl rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden border-l-4"
                :class="type === 'success' ? 'border-green-500' : 'border-red-500'"
                x-cloak
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <template x-if="type === 'success'">
                                <i class="fa-solid fa-circle-check text-green-400 text-xl"></i>
                            </template>
                            <template x-if="type === 'error'">
                                <i class="fa-solid fa-circle-xmark text-red-400 text-xl"></i>
                            </template>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900" x-text="message"></p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button @click="open = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span class="sr-only">Fechar</span>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                    <div x-data="{ open: {{ (request()->routeIs('dashboard') || request()->routeIs('gestacao') || request()->routeIs('maternidade')) ? 'true' : 'false' }} }">
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
                            <a href="{{ url('/gestacao') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('gestacao') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Gestação
                            </a>
                            <a href="{{ route('maternidade', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('maternidade') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Maternidade
                            </a>
                        </div>
                    </div>

                    @if(!config('masterpig.enforce_perfil_permissions', false) || Auth::user()->perfil === 'administrador')
                    <div x-data="{ open: {{ (request()->is('admin/causas*') || request()->is('admin/racoes*') || request()->is('admin/fornecedores*') || request()->is('admin/clientes*')) ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-folder-open w-6 text-center"></i>
                                <span class="ml-3 font-medium">Cadastros</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <a href="{{ route('admin.causas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.causas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Causas
                            </a>
                            <a href="{{ route('admin.racoes.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.racoes.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Rações
                            </a>
                            <a href="{{ url('/admin/fornecedores') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/fornecedores*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Fornecedor
                            </a>
                            <a href="{{ url('/admin/clientes') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/clientes*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Cliente
                            </a>
                        </div>
                    </div>
                    <div x-data="{ open: {{ (request()->is('admin/usuarios*') || request()->is('admin/metas*') || request()->is('admin/criterios*') || request()->is('admin/criterios/logs*') || request()->is('admin/alteracoes*')) ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-screwdriver-wrench w-6 text-center"></i>
                                <span class="ml-3 font-medium">Utilitários</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <a href="{{ route('admin.usuarios.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.usuarios.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Usuários
                            </a>
                            <a href="{{ route('admin.metas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.metas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Metas
                            </a>
                            <a href="{{ url('/admin/criterios') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/criterios*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Critérios
                            </a>
                            <a href="{{ url('/admin/criterios/logs') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/criterios/logs*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Logs de critérios
                            </a>
                            <a href="{{ url('/admin/alteracoes') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/alteracoes*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white" @click="mobileSidebarOpen = false">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Atualizações do sistema
                            </a>
                        </div>
                    </div>
                    @endif
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
                    <div x-data="{ open: {{ (request()->routeIs('dashboard') || request()->routeIs('gestacao') || request()->routeIs('maternidade')) ? 'true' : 'false' }} }">
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
                            <a href="{{ url('/gestacao') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('gestacao') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Gestação
                            </a>
                            <a href="{{ route('maternidade', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('maternidade') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Maternidade
                            </a>
                        </div>
                    </div>

                    @if(!config('masterpig.enforce_perfil_permissions', false) || Auth::user()->perfil === 'administrador')
                    <div x-data="{ open: {{ (request()->is('admin/causas*') || request()->is('admin/racoes*') || request()->is('admin/fornecedores*') || request()->is('admin/clientes*')) ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-folder-open w-6 text-center"></i>
                                <span x-show="sidebarOpen" class="ml-3 font-medium">Cadastros</span>
                            </div>
                            <i x-show="sidebarOpen" class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="open && sidebarOpen" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <a href="{{ route('admin.causas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.causas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Causas
                            </a>
                            <a href="{{ route('admin.racoes.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.racoes.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Rações
                            </a>
                            <a href="{{ url('/admin/fornecedores') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/fornecedores*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Fornecedor
                            </a>
                            <a href="{{ url('/admin/clientes') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/clientes*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Cliente
                            </a>
                        </div>
                    </div>
                    <div x-data="{ open: {{ (request()->is('admin/usuarios*') || request()->is('admin/metas*') || request()->is('admin/criterios*') || request()->is('admin/criterios/logs*') || request()->is('admin/alteracoes*') || request()->is('admin/zerar*')) ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group">
                            <div class="flex items-center">
                                <i class="fa-solid fa-screwdriver-wrench w-6 text-center"></i>
                                <span x-show="sidebarOpen" class="ml-3 font-medium">Utilitários</span>
                            </div>
                            <i x-show="sidebarOpen" class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && sidebarOpen" x-cloak class="mt-1 ml-4 pl-4 border-l border-primary-600 space-y-1">
                            <a href="{{ route('admin.usuarios.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.usuarios.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Usuários
                            </a>
                            <a href="{{ route('admin.metas.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.metas.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Metas
                            </a>
                            <a href="{{ url('/admin/criterios') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/criterios*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Critérios
                            </a>
                            <a href="{{ url('/admin/criterios/logs') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/criterios/logs*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Logs de critérios
                            </a>
                            <a href="{{ route('admin.zerar.index', [], false) }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.zerar.index') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Começar do zero
                            </a>
                            <a href="{{ url('/admin/alteracoes') }}" class="flex items-center px-4 py-2 text-sm {{ request()->is('admin/alteracoes*') ? 'text-white font-bold bg-primary-700/50' : 'text-primary-300' }} transition-colors rounded-lg hover:bg-primary-700 hover:text-white">
                                <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                Atualizações do sistema
                            </a>
                        </div>
                    </div>
                    @endif
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

                    <!-- Right Nav -->
                    <div class="ml-auto flex items-center space-x-4">
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

    @if((bool) config('masterpig.chatbot_enabled', false))
    <div
        x-data="{
            open: false,
            expanded: false,
            loading: false,
            input: '',
            messages: [
                { role: 'bot', text: 'Me pergunte algo do banco.\n\nExemplos:\n- Quantas fêmeas ativas?\n- Quantos machos ativos?\n- Quantas leitoas ativas?\n- Quantas matrizes ativas?\n- Quantas mortes este mês?\n- Quantas vendas nos últimos 30 dias?' },
            ],
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.scrollBottom());
                }
            },
            toggleExpanded() {
                this.expanded = !this.expanded;
                this.$nextTick(() => this.scrollBottom());
            },
            scrollBottom() {
                const el = this.$refs.list;
                if (el) el.scrollTop = el.scrollHeight;
            },
            async send() {
                const text = String(this.input || '').trim();
                if (!text || this.loading) return;
                this.messages.push({ role: 'user', text });
                this.input = '';
                this.loading = true;
                this.$nextTick(() => this.scrollBottom());
                try {
                    const r = await fetch('/api/chatbot', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\\'csrf-token\\']').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ message: text }),
                    });

                    const ct = String(r.headers.get('content-type') || '');
                    let data = null;
                    if (ct.includes('application/json')) {
                        data = await r.json().catch(() => ({}));
                    } else {
                        const raw = await r.text().catch(() => '');
                        data = { message: raw };
                    }

                    if (!r.ok) {
                        if (r.status === 419) throw new Error('Sessão expirada. Atualize a página e tente novamente.');
                        if (r.status === 401) throw new Error('Você não está autenticado. Faça login e tente novamente.');
                        const msg = data?.message || 'Erro ao consultar.';
                        throw new Error(String(msg));
                    }

                    const answer = data?.answer ?? data?.message ?? '';
                    this.messages.push({ role: 'bot', text: String(answer) || 'Sem resposta.' });
                } catch (e) {
                    this.messages.push({ role: 'bot', text: String(e?.message || 'Erro ao consultar.') });
                } finally {
                    this.loading = false;
                    this.$nextTick(() => this.scrollBottom());
                }
            },
        }"
        class="fixed z-[120] right-4 bottom-4 sm:right-6 sm:bottom-6"
    >
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
            class="fixed inset-0 sm:absolute sm:inset-auto sm:right-0 sm:bottom-16 bg-white border border-gray-200 shadow-2xl overflow-hidden sm:rounded-2xl"
            :class="expanded ? 'sm:w-[520px] sm:h-[72vh]' : 'sm:w-[400px] sm:h-[56vh]'"
        >
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between sticky top-0 z-10">
                <div class="min-w-0">
                    <div class="font-bold text-gray-900 truncate">Chatbot</div>
                    <div class="text-xs text-gray-500 truncate">Perguntas do banco</div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="hidden sm:inline-flex w-9 h-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" @click="toggleExpanded()" title="Aumentar/Diminuir">
                        <i class="fa-solid" :class="expanded ? 'fa-down-left-and-up-right-to-center' : 'fa-up-right-and-down-left-from-center'"></i>
                    </button>
                    <button type="button" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" @click="toggle()" title="Fechar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div x-ref="list" class="px-4 py-3 space-y-3 overflow-y-auto h-[calc(100vh-130px)] sm:h-auto" :class="expanded ? 'sm:h-[calc(72vh-118px)]' : 'sm:h-[calc(56vh-118px)]'">
                <template x-for="(m, idx) in messages" :key="idx">
                    <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm whitespace-pre-wrap"
                             :class="m.role === 'user' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-800'">
                            <span x-text="m.text"></span>
                        </div>
                    </div>
                </template>
                <div x-show="loading" class="text-sm text-gray-500">Consultando...</div>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/50 sticky bottom-0">
                <form @submit.prevent="send()" class="flex items-end gap-2">
                    <textarea
                        x-model="input"
                        rows="2"
                        class="w-full rounded-xl border border-gray-200 shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Digite sua pergunta…"
                        @keydown.enter.prevent="if(!$event.shiftKey) send(); else input += '\\n';"
                    ></textarea>
                    <button type="submit" :disabled="loading" class="shrink-0 inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
                <div class="mt-1 text-[11px] text-gray-500" x-show="!input.trim() && !loading">Digite uma pergunta para enviar.</div>
                <div class="mt-1 text-[11px] text-gray-500">Enter envia, Shift+Enter quebra linha.</div>
            </div>
        </div>

        <button
            type="button"
            class="w-14 h-14 rounded-full shadow-xl bg-primary-600 text-white inline-flex items-center justify-center border border-primary-700 hover:bg-primary-700"
            @click="toggle()"
            x-show="!open"
            x-cloak
            title="Abrir chatbot"
        >
            <i class="fa-solid fa-comment-dots text-lg"></i>
        </button>
    </div>
    @endif

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
