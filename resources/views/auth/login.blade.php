<x-guest-layout>
    <div class="sm:max-w-lg w-full mx-auto">
        <div class="text-center mb-6">
            @if (file_exists(public_path('logo.png')))
                <div class="relative mx-auto w-40 h-40">
                    {{-- Halo escuro atrás da logo (que é clara) para dar contraste sobre o wallpaper --}}
                    <div class="absolute inset-0 rounded-full blur-md" style="background: radial-gradient(circle, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.30) 55%, rgba(0,0,0,0) 75%);"></div>
                    <img src="/logoSemFundo.png" alt="Sui Control" class="relative w-full h-full object-contain transition-transform duration-300 hover:scale-105" style="filter: drop-shadow(0 4px 10px rgba(0,0,0,0.55));">
                </div>
            @else
                <img src="/favicon.ico" alt="Sui Control" class="mx-auto w-24 h-24 object-contain transition-transform duration-300 hover:scale-105 drop-shadow-lg">
            @endif
            <div class="mt-3">
                <h1 class="text-2xl font-bold text-white" style="text-shadow: 0 2px 6px rgba(0,0,0,0.7);">Sui Control</h1>
                <p class="text-sm text-white/95" style="text-shadow: 0 1px 4px rgba(0,0,0,0.7);">Acesso ao sistema de gestão suína</p>
            </div>
        </div>

        <div class="bg-white/12 dark:bg-gray-900/20 backdrop-blur-md rounded-3xl shadow-2xl border border-white/25 dark:border-gray-700/40 p-8 transition-all duration-300 hover:shadow-3xl shadow-[0_20px_60px_-12px_rgba(0,0,0,0.4)]">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form accept-charset="UTF-8" id="login-form" method="POST" action="{{ route('login', [], false) }}" class="space-y-5">
                @csrf

                <div>
                    <label for="cnpj" class="block text-sm font-medium text-white/90 dark:text-gray-200">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" inputmode="numeric" tabindex="1" autofocus autocomplete="off" value="{{ old('cnpj') }}" class="mt-1 block w-full rounded-2xl border-white/20 dark:border-gray-600/50 bg-white/20 dark:bg-gray-800/50 text-white dark:text-gray-100 backdrop-blur-sm shadow-sm focus:border-white/40 dark:focus:border-gray-500 focus:ring-2 focus:ring-white/20 dark:focus:ring-gray-500/20 transition-all duration-200 hover:border-white/30 dark:hover:border-gray-500/40 placeholder-white/50 dark:placeholder-gray-400" placeholder="00.000.000/0000-00">
                    <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                </div>

                <div>
                    <label for="usuario" class="block text-sm font-medium text-white/90 dark:text-gray-200">Usuário</label>
                    <input id="usuario" name="usuario" type="text" tabindex="2" autocomplete="username" value="{{ old('usuario') }}" required class="mt-1 block w-full rounded-2xl border-white/20 dark:border-gray-600/50 bg-white/20 dark:bg-gray-800/50 text-white dark:text-gray-100 backdrop-blur-sm shadow-sm focus:border-white/40 dark:focus:border-gray-500 focus:ring-2 focus:ring-white/20 dark:focus:ring-gray-500/20 transition-all duration-200 hover:border-white/30 dark:hover:border-gray-500/40 placeholder-white/50 dark:placeholder-gray-400">
                    <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
                </div>

                <div>
                    <label for="senha" class="block text-sm font-medium text-white/90 dark:text-gray-200">Senha</label>
                    <input id="senha" name="senha" type="password" tabindex="3" required autocomplete="current-password" class="mt-1 block w-full rounded-2xl border-white/20 dark:border-gray-600/50 bg-white/20 dark:bg-gray-800/50 text-white dark:text-gray-100 backdrop-blur-sm shadow-sm focus:border-white/40 dark:focus:border-gray-500 focus:ring-2 focus:ring-white/20 dark:focus:ring-gray-500/20 transition-all duration-200 hover:border-white/30 dark:hover:border-gray-500/40 placeholder-white/50 dark:placeholder-gray-400">
                    <x-input-error :messages="$errors->get('senha')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label for="remember_me" class="inline-flex items-center select-none">
                        <input id="remember_me" type="checkbox" tabindex="4" class="rounded border-white/30 dark:border-gray-600 text-primary-600 shadow-sm focus:ring-primary-500 bg-white/20 dark:bg-gray-800/50" name="remember">
                        <span class="ms-2 text-sm text-white/80 dark:text-gray-300">Lembrar-me</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" tabindex="5" class="inline-flex items-center justify-center w-full rounded-2xl border border-white/20 dark:border-gray-600/50 shadow-lg px-4 py-3 bg-white/20 dark:bg-gray-800/50 backdrop-blur-sm text-sm font-semibold text-white dark:text-gray-100 hover:bg-white/30 dark:hover:bg-gray-700/60 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-white/30 dark:focus:ring-gray-500/30 transition-all duration-200 hover:scale-[1.02] hover:shadow-xl">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>
                        Entrar
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        (() => {
            const key = 'mp_cnpj';
            const input = document.getElementById('cnpj');
            const form = document.getElementById('login-form');
            if (!input || !form) return;

            const normalize = (v) => (v || '').toString().replace(/\D+/g, '');

            const cached = normalize(localStorage.getItem(key));
            if (!input.value && cached) input.value = cached;

            const persist = () => {
                localStorage.setItem(key, normalize(input.value));
            };

            input.addEventListener('input', persist);
            form.addEventListener('submit', persist);
        })();
    </script>
</x-guest-layout>
