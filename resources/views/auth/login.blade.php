<x-guest-layout>
    <div class="sm:max-w-lg w-full mx-auto">
        <div class="text-center mb-6">
            @if (file_exists(public_path('logo.png')))
                <img src="/logoSemPalavra.png" alt="Sui Control" class="mx-auto w-40 h-40 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @else
                <img src="/favicon.ico" alt="Sui Control" class="mx-auto w-24 h-24 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @endif
            <div class="mt-3">
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Sui Control</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Acesso ao sistema de gestão suína</p>
            </div>
        </div>

        <div class="bg-white/90 dark:bg-gray-900/90 backdrop-blur rounded-3xl shadow-lg border border-gray-100 dark:border-gray-800 p-6 transition-all duration-300 hover:shadow-xl hover:border-primary-200 dark:hover:border-primary-800">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form id="login-form" method="POST" action="{{ route('login', [], false) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" inputmode="numeric" autocomplete="organization" value="{{ old('cnpj') }}" class="mt-1 block w-full rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400 dark:hover:border-primary-600" placeholder="00.000.000/0000-00">
                    <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                </div>

                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</label>
                    <input id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" required autofocus class="mt-1 block w-full rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400 dark:hover:border-primary-600">
                    <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
                </div>

                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha</label>
                    <input id="senha" name="senha" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400 dark:hover:border-primary-600">
                    <x-input-error :messages="$errors->get('senha')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label for="remember_me" class="inline-flex items-center select-none">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Lembrar-me</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex items-center justify-center w-full rounded-2xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-transform duration-200 hover:scale-[1.02]">
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
