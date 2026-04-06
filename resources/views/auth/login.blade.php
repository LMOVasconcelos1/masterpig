<x-guest-layout>
    <div class="sm:max-w-lg w-full mx-auto">
        <div class="text-center mb-6">
            @if (file_exists(public_path('logo.png')))
                <img src="/logoSemFundo.png" alt="Sui Control" class="mx-auto w-40 h-40 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @else
                <img src="/favicon.ico" alt="Sui Control" class="mx-auto w-24 h-24 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @endif
            <div class="mt-3">
                <h1 class="text-2xl font-bold text-white drop-shadow-lg">Sui Control</h1>
                <p class="text-sm text-white/90 dark:text-gray-200 drop-shadow">Acesso ao sistema de gestão suína</p>
            </div>
        </div>

        <div class="bg-white/10 dark:bg-gray-900/10 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/30 p-8 transition-all duration-300 hover:shadow-3xl">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form id="login-form" method="POST" action="{{ route('login', [], false) }}" class="space-y-5">
                @csrf

                <div>
                    <label for="cnpj" class="block text-sm font-medium text-white/90 dark:text-gray-200">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" inputmode="numeric" autocomplete="organization" value="{{ old('cnpj') }}" class="mt-1 block w-full rounded-2xl border-white/20 dark:border-gray-600/50 bg-white/20 dark:bg-gray-800/50 text-white dark:text-gray-100 backdrop-blur-sm shadow-sm focus:border-white/40 dark:focus:border-gray-500 focus:ring-2 focus:ring-white/20 dark:focus:ring-gray-500/20 transition-all duration-200 hover:border-white/30 dark:hover:border-gray-500/40 placeholder-white/50 dark:placeholder-gray-400" placeholder="00.000.000/0000-00">
                    <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                </div>

                <div>
                    <label for="usuario" class="block text-sm font-medium text-white/90 dark:text-gray-200">Usuário</label>
                    <input id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" required autofocus class="mt-1 block w-full rounded-2xl border-white/20 dark:border-gray-600/50 bg-white/20 dark:bg-gray-800/50 text-white dark:text-gray-100 backdrop-blur-sm shadow-sm focus:border-white/40 dark:focus:border-gray-500 focus:ring-2 focus:ring-white/20 dark:focus:ring-gray-500/20 transition-all duration-200 hover:border-white/30 dark:hover:border-gray-500/40 placeholder-white/50 dark:placeholder-gray-400">
                    <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
                </div>

                <div>
                    <label for="senha" class="block text-sm font-medium text-white/90 dark:text-gray-200">Senha</label>
                    <input id="senha" name="senha" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-2xl border-white/20 dark:border-gray-600/50 bg-white/20 dark:bg-gray-800/50 text-white dark:text-gray-100 backdrop-blur-sm shadow-sm focus:border-white/40 dark:focus:border-gray-500 focus:ring-2 focus:ring-white/20 dark:focus:ring-gray-500/20 transition-all duration-200 hover:border-white/30 dark:hover:border-gray-500/40 placeholder-white/50 dark:placeholder-gray-400">
                    <x-input-error :messages="$errors->get('senha')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label for="remember_me" class="inline-flex items-center select-none">
                        <input id="remember_me" type="checkbox" class="rounded border-white/30 dark:border-gray-600 text-primary-600 shadow-sm focus:ring-primary-500 bg-white/20 dark:bg-gray-800/50" name="remember">
                        <span class="ms-2 text-sm text-white/80 dark:text-gray-300">Lembrar-me</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex items-center justify-center w-full rounded-2xl border border-white/20 dark:border-gray-600/50 shadow-lg px-4 py-3 bg-white/20 dark:bg-gray-800/50 backdrop-blur-sm text-sm font-semibold text-white dark:text-gray-100 hover:bg-white/30 dark:hover:bg-gray-700/60 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-white/30 dark:focus:ring-gray-500/30 transition-all duration-200 hover:scale-[1.02] hover:shadow-xl">
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
