<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sui Control - @yield('title', 'Dashboard')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
<body class="bg-amber-900 font-sans text-gray-900 antialiased transition-colors duration-200">
    <div class="flex flex-col h-screen overflow-hidden" 
         x-data="{ 
            mobileMenuOpen: false
         }"
         x-effect="document.body.classList.toggle('overflow-hidden', mobileMenuOpen)">
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
                class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white shadow-2xl rounded-xl pointer-events-auto ring-1 ring-amber-800 ring-opacity-5 overflow-hidden border-l-4"
                :class="type === 'success' ? 'border-green-600' : 'border-red-600'"
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
                            <button @click="open = false" class="bg-amber-50 rounded-md inline-flex text-amber-800 hover:text-amber-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                                <span class="sr-only">Fechar</span>
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Top Navigation -->
        <header class="bg-amber-800 border-b border-amber-700 shadow-lg z-20">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo and Brand -->
                    <div class="flex items-center space-x-4">
                        <i class="fa-solid fa-piggy-bank text-2xl text-white"></i>
                        <div class="hidden sm:block">
                            <span class="text-xl font-bold tracking-wider uppercase text-white">{{ \App\Models\Configuracao::getGranjaAtual() }}</span>
                            <span class="text-xs text-amber-200 block">Sui Control</span>
                        </div>
                    </div>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center space-x-1">
                        <!-- Manejos Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center px-4 py-2 text-sm font-medium text-amber-100 hover:bg-amber-700 hover:text-white rounded-lg transition-colors">
                                <i class="fa-solid fa-leaf mr-2"></i>
                                Manejos
                                <i class="fa-solid fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-amber-200">
                                <a href="{{ route('dashboard', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('dashboard') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Plantel Reprodutivo
                                </a>
                                <a href="{{ url('/gestacao') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('gestacao') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Gestação
                                </a>
                                <a href="{{ route('maternidade', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('maternidade') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Maternidade
                                </a>
                                <a href="{{ url('/creche') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('creche') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Creche
                                </a>
                                <a href="{{ url('/terminacao') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('terminacao') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Terminação
                                </a>
                            </div>
                        </div>

                        @if(!config('masterpig.enforce_perfil_permissions', false) || Auth::user()->perfil === 'administrador')
                        <!-- Cadastros Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center px-4 py-2 text-sm font-medium text-amber-100 hover:bg-amber-700 hover:text-white rounded-lg transition-colors">
                                <i class="fa-solid fa-folder-open mr-2"></i>
                                Cadastros
                                <i class="fa-solid fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-amber-200">
                                <a href="{{ route('admin.causas.index', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('admin.causas.index') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Causas
                                </a>
                                <a href="{{ route('admin.racoes.index', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('admin.racoes.index') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Rações
                                </a>
                                <a href="{{ url('/admin/fornecedores') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->is('admin/fornecedores*') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Fornecedor
                                </a>
                                <a href="{{ url('/admin/clientes') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->is('admin/clientes*') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Cliente
                                </a>
                            </div>
                        </div>

                        <!-- Utilitários Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center px-4 py-2 text-sm font-medium text-amber-100 hover:bg-amber-700 hover:text-white rounded-lg transition-colors">
                                <i class="fa-solid fa-screwdriver-wrench mr-2"></i>
                                Utilitários
                                <i class="fa-solid fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-amber-200">
                                <a href="{{ route('admin.usuarios.index', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('admin.usuarios.index') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Usuários
                                </a>
                                <a href="{{ route('admin.metas.index', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ (request()->routeIs('admin.metas.index') || request()->is('admin/criterios*')) ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Metas e Critérios
                                </a>
                                <a href="{{ url('/admin/criterios/logs') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->is('admin/criterios/logs*') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Logs de critérios
                                </a>
                                <a href="{{ route('admin.zerar.index', [], false) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->routeIs('admin.zerar.index') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Começar do zero
                                </a>
                                <a href="{{ url('/admin/alteracoes') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 {{ request()->is('admin/alteracoes*') ? 'bg-amber-50 text-amber-900 font-semibold' : '' }}">
                                    <i class="fa-solid fa-circle-dot text-[8px] mr-2"></i>
                                    Atualizações do sistema
                                </a>
                            </div>
                        </div>
                        @endif
                    </nav>

                    <!-- Right side items -->
                    <div class="flex items-center space-x-4">
                        <!-- Mobile menu button -->
                        <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-amber-100 hover:bg-amber-700 hover:text-white focus:outline-none">
                            <i class="fa-solid fa-bars text-xl"></i>
                        </button>

                        
                        <!-- Notifications -->
                        @if(($notificacoesCount ?? 0) > 0)
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="relative p-2 text-amber-100 hover:text-white transition-colors focus:outline-none">
                                    <i class="fa-solid fa-bell text-xl"></i>
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-amber-800">
                                        {{ $notificacoesCount }}
                                    </span>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-lg shadow-lg z-50 border border-amber-200 overflow-hidden">
                                    <div class="px-4 py-3 bg-amber-50 border-b border-amber-200">
                                        <div class="text-xs font-bold text-amber-900 uppercase tracking-wider">Notificações</div>
                                    </div>
                                    <div class="max-h-80 overflow-y-auto">
                                        @foreach(($notificacoes ?? []) as $n)
                                            <div class="px-4 py-3 border-b border-amber-100 last:border-b-0">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-0.5 w-9 h-9 rounded-lg flex items-center justify-center bg-red-50 text-red-700">
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
                        @endif
                    <div class="flex items-center space-x-4">
                        
                        <!-- Calendário de 1000 Dias -->
                        <div class="hidden sm:block">
                            @include('components.calendar-menu')
                        </div>
                    </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ userMenuOpen: false }">
                            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-3 cursor-pointer group focus:outline-none">
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-semibold text-white group-hover:text-amber-100 transition-colors">{{ Auth::user()->nome }}</span>
                                </div>
                                <img class="w-10 h-10 rounded-full border-2 border-amber-600 group-hover:border-amber-400 transition-all shadow-sm object-cover" src="{{ Auth::user()->foto_perfil_url ?? ('https://ui-avatars.com/api/?name='.urlencode(Auth::user()->nome).'&background=f97316&color=fff') }}" alt="User Avatar">
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
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-amber-200"
                                x-cloak
                            >
                                <a href="{{ route('profile.edit', [], false) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-900 transition-colors">
                                    <i class="fa-solid fa-user mr-3 text-amber-600"></i>
                                    Meu Perfil
                                </a>
                                
                                <hr class="my-2 border-gray-100">
                                
                                <form accept-charset="UTF-8" method="POST" action="{{ route('logout', [], false) }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors text-left">
                                        <i class="fa-solid fa-right-from-bracket mr-3"></i>
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Menu Overlay -->
        <div x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 z-30 bg-black/50" @click="mobileMenuOpen = false" x-cloak></div>

        <!-- Mobile Navigation -->
        <nav x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] overflow-y-auto bg-amber-800 text-white lg:hidden" x-cloak>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between h-16 px-4 bg-amber-900">
                    <div class="flex items-center">
                        <i class="fa-solid fa-piggy-bank text-2xl text-white mr-3"></i>
                        <div>
                            <span class="text-xl font-bold tracking-wider uppercase text-white">{{ \App\Models\Configuracao::getGranjaAtual() }}</span>
                            <span class="text-xs text-amber-200 block">Sui Control</span>
                        </div>
                    </div>
                    <button type="button" class="p-2 rounded-lg text-amber-200 hover:bg-amber-700 hover:text-white" @click="mobileMenuOpen = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
                    <div class="space-y-1">
                        <div class="px-3 py-2 text-xs font-semibold text-amber-200 uppercase tracking-wider">Manejos</div>
                        <a href="{{ route('dashboard', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('dashboard') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Plantel Reprodutivo
                        </a>
                        <a href="{{ url('/gestacao') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('gestacao') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Gestação
                        </a>
                        <a href="{{ route('maternidade', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('maternidade') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Maternidade
                        </a>
                        <a href="{{ url('/creche') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('creche') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Creche
                        </a>
                        <a href="{{ url('/terminacao') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('terminacao') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Terminação
                        </a>
                    </div>

                    @if(!config('masterpig.enforce_perfil_permissions', false) || Auth::user()->perfil === 'administrador')
                    <div class="space-y-1">
                        <div class="px-3 py-2 text-xs font-semibold text-amber-200 uppercase tracking-wider">Cadastros</div>
                        <a href="{{ route('admin.plantel.femeas.index', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ (request()->routeIs('admin.plantel.femeas.index') || request()->routeIs('admin.plantel.femeas.show')) ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Fêmeas (cadastro)
                        </a>
                        <a href="{{ route('admin.causas.index', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('admin.causas.index') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Causas
                        </a>
                        <a href="{{ route('admin.racoes.index', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('admin.racoes.index') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Rações
                        </a>
                        <a href="{{ url('/admin/fornecedores') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->is('admin/fornecedores*') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Fornecedor
                        </a>
                        <a href="{{ url('/admin/clientes') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->is('admin/clientes*') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Cliente
                        </a>
                    </div>

                    <div class="space-y-1">
                        <div class="px-3 py-2 text-xs font-semibold text-amber-200 uppercase tracking-wider">Utilitários</div>
                        <a href="{{ route('admin.usuarios.index', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('admin.usuarios.index') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Usuários
                        </a>
                        <a href="{{ route('admin.metas.index', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ (request()->routeIs('admin.metas.index') || request()->is('admin/criterios*')) ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Metas e Critérios
                        </a>
                        <a href="{{ url('/admin/criterios/logs') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->is('admin/criterios/logs*') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Logs de critérios
                        </a>
                        <a href="{{ route('admin.zerar.index', [], false) }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->routeIs('admin.zerar.index') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Começar do zero
                        </a>
                        <a href="{{ url('/admin/alteracoes') }}" class="flex items-center px-4 py-3 text-white hover:bg-amber-700 {{ request()->is('admin/alteracoes*') ? 'bg-amber-700 font-semibold' : '' }}" @click="mobileMenuOpen = false">
                            <i class="fa-solid fa-circle-dot text-[8px] mr-3"></i>
                            Atualizações do sistema
                        </a>
                    </div>
                    @endif

                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 w-full overflow-hidden">
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto" style="background-color: var(--color-background);">
                <div class="py-6 px-4 sm:px-6 lg:px-8">
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
                <form accept-charset="UTF-8" @submit.prevent="send()" class="flex items-end gap-2">
                    <textarea
                        x-model="input"
                        rows="2"
                        class="w-full rounded-xl border border-gray-200 shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Digite sua pergunta?"
                        @keydown.enter.prevent="if(!$event.shiftKey) send(); else input += '\\n';"
                    ></textarea>
                    <button type="submit" :disabled="loading" class="shrink-0 inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
                <div class="mt-1 text-[11px] text-gray-500">Digite uma pergunta para enviar.</div>
                <div class="mt-1 text-[11px] text-gray-500">Enter envia, Shift+Enter quebra linha.</div>
                <div class="mt-2 text-[11px] text-orange-600 font-medium bg-orange-50 px-2 py-1 rounded-lg border border-orange-200">
                    <i class="fa-solid fa-code mr-1"></i>
                    Ambiente de Desenvolvimento
                </div>
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

    <!-- Banner: Em Desenvolvimento -->
    <div class="fixed bottom-0 left-0 right-0 z-50 bg-amber-50 dark:bg-amber-950/40 border-t border-amber-200 dark:border-amber-800/60 px-4 py-2">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2 text-amber-700 dark:text-amber-400 text-xs sm:text-sm font-medium">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 dark:text-amber-400 animate-pulse"></i>
            <span>
                Este sistema está em desenvolvimento ativo.
                <span class="font-semibold tracking-wide ml-1 px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700 text-[11px] uppercase">
                    Versão 0.05
                </span>
            </span>
        </div>
    </div>

    <!-- Alpine.js (local) -->
    <script defer src="{{ asset('js/vendor/alpine-collapse.min.js') }}"></script>
    <script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>
    
    <!-- Script de verificação e fallback para Alpine.js -->
    <script src="{{ asset('js/check-alpine.js') }}"></script>
    
    <!-- Fallback para Alpine.js via CDN (apenas em produção) -->
    @if(app()->environment('production'))
    <script>
        // Verifica se Alpine.js foi carregado após 2 segundos
        setTimeout(function() {
            if (typeof Alpine === 'undefined') {
                console.warn('Alpine.js não foi carregado dos arquivos locais, carregando do CDN...');
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
                script.defer = true;
                document.head.appendChild(script);
            }
        }, 2000);
    </script>
    @endif
    
    <script>
        window.toast = function (message, type = 'success') {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message: message, type: type }
            }));
        };
    </script>

    @stack('scripts')
</body>
</html>
