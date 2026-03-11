<x-guest-layout>
    <div class="sm:max-w-lg w-full mx-auto">
        <div class="text-center mb-6">
            @if (file_exists(public_path('logo.png')))
                <img src="/logoSemPalavra.png" alt="MasterPig" class="mx-auto w-40 h-40 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @else
                <img src="/favicon.ico" alt="MasterPig" class="mx-auto w-24 h-24 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @endif
            <div class="mt-3">
                <h1 class="text-xl font-bold text-gray-800">MasterPig</h1>
                <p class="text-sm text-gray-500">Acesso ao sistema de gestão suína</p>
            </div>
        </div>

        <div class="bg-white/90 backdrop-blur rounded-3xl shadow-lg border border-gray-100 p-6 transition-all duration-300 hover:shadow-xl hover:border-primary-200">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form id="login-form" method="POST" action="{{ route('login', absolute: false) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" inputmode="numeric" autocomplete="organization" value="{{ old('cnpj') }}" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400" placeholder="00.000.000/0000-00">
                    <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                </div>

                <div>
                    <label for="identificador" class="block text-sm font-medium text-gray-700">E-mail, CPF ou Usuário</label>
                    <input id="identificador" name="identificador" type="text" value="{{ old('identificador') }}" required autofocus class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('identificador')" class="mt-2" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
                    <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
                </div>

                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700">Senha</label>
                    <input id="senha" name="senha" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('senha')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label for="remember_me" class="inline-flex items-center select-none">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600">Lembrar-me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a class="text-sm text-primary-600 hover:text-primary-700 font-semibold" href="{{ route('password.request', absolute: false) }}">
                            Esqueci minha senha
                        </a>
                    @endif
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex items-center justify-center w-full rounded-2xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-transform duration-200 hover:scale-[1.02]">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>
                        Entrar
                    </button>
                </div>

                <div class="pt-3 text-center">
                    <a href="{{ route('register', absolute: false) }}" class="inline-flex items-center justify-center w-full rounded-2xl border border-primary-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-primary-700 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-transform duration-200 hover:scale-[1.02]">
                        <i class="fa-solid fa-user-plus mr-2"></i>
                        Se inscrever
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            try {
                var cnpjInput = document.getElementById('cnpj');
                var saved = localStorage.getItem('masterpig:cnpj');
                if (cnpjInput && saved) cnpjInput.value = saved;
                var form = document.getElementById('login-form');
                if (form) {
                    form.addEventListener('submit', function () {
                        if (cnpjInput && cnpjInput.value.trim() !== '') {
                            localStorage.setItem('masterpig:cnpj', cnpjInput.value.trim());
                        }
                    });
                }
            } catch (e) {}
        });
    </script>
</x-guest-layout>
