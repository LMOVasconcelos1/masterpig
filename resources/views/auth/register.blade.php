<x-guest-layout>
    <div class="sm:max-w-lg w-full mx-auto">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-gray-800">Criar Conta</h1>
            <p class="text-sm text-gray-500">Preencha seus dados para acessar o MasterPig</p>
        </div>

        <div class="bg-white/90 backdrop-blur rounded-3xl shadow-lg border border-gray-100 p-6 transition-all duration-300 hover:shadow-xl hover:border-primary-200">
            <form method="POST" action="{{ route('register', absolute: false) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" value="{{ old('cnpj') }}" autocomplete="organization" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400" placeholder="00.000.000/0000-00">
                    <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
                </div>

                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome</label>
                    <input id="nome" name="nome" type="text" value="{{ old('nome') }}" required autofocus autocomplete="nome" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('nome')" class="mt-2" />
                </div>

                <div>
                    <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                    <input id="cpf" name="cpf" type="text" value="{{ old('cpf') }}" required autocomplete="cpf" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
                </div>

                <div>
                    <label for="usuario" class="block text-sm font-medium text-gray-700">Usuário</label>
                    <input id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" required autocomplete="username" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <label for="perfil" class="block text-sm font-medium text-gray-700">Perfil</label>
                    <select id="perfil" name="perfil" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400" required>
                        <option value="">Selecione um perfil</option>
                        <option value="consultor" {{ old('perfil') == 'consultor' ? 'selected' : '' }}>Consultor</option>
                        <option value="operador" {{ old('perfil') == 'operador' ? 'selected' : '' }}>Operador</option>
                        <option value="administrador" {{ old('perfil') == 'administrador' ? 'selected' : '' }}>Administrador</option>
                    </select>
                    <x-input-error :messages="$errors->get('perfil')" class="mt-2" />
                </div>

                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700">Senha</label>
                    <input id="senha" name="senha" type="password" required autocomplete="new-password" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('senha')" class="mt-2" />
                </div>

                <div>
                    <label for="senha_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                    <input id="senha_confirmation" name="senha_confirmation" type="password" required autocomplete="new-password" class="mt-1 block w-full rounded-2xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 transition-all duration-200 hover:border-primary-400">
                    <x-input-error :messages="$errors->get('senha_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a class="text-sm text-primary-600 hover:text-primary-700 font-semibold" href="{{ route('login', absolute: false) }}">
                        Já tem conta? Entrar
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-transform duration-200 hover:scale-[1.02]">
                        Criar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
