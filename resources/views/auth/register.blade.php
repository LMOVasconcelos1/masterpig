<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Nome -->
        <div>
            <x-input-label for="nome" :value="__('Nome')" />
            <x-text-input id="nome" class="block mt-1 w-full" type="text" name="nome" :value="old('nome')" required autofocus autocomplete="nome" />
            <x-input-error :messages="$errors->get('nome')" class="mt-2" />
        </div>

        <!-- CPF -->
        <div class="mt-4">
            <x-input-label for="cpf" :value="__('CPF')" />
            <x-text-input id="cpf" class="block mt-1 w-full" type="text" name="cpf" :value="old('cpf')" required autocomplete="cpf" />
            <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
        </div>

        <!-- Usuário -->
        <div class="mt-4">
            <x-input-label for="usuario" :value="__('Usuário')" />
            <x-text-input id="usuario" class="block mt-1 w-full" type="text" name="usuario" :value="old('usuario')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('usuario')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Perfil -->
        <div class="mt-4">
            <x-input-label for="perfil" :value="__('Perfil')" />
            <select id="perfil" name="perfil" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                <option value="">Selecione um perfil</option>
                <option value="consultor" {{ old('perfil') == 'consultor' ? 'selected' : '' }}>Consultor</option>
                <option value="operador" {{ old('perfil') == 'operador' ? 'selected' : '' }}>Operador</option>
                <option value="administrador" {{ old('perfil') == 'administrador' ? 'selected' : '' }}>Administrador</option>
            </select>
            <x-input-error :messages="$errors->get('perfil')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div class="mt-4">
            <x-input-label for="senha" :value="__('Senha')" />

            <x-text-input id="senha" class="block mt-1 w-full"
                            type="password"
                            name="senha"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('senha')" class="mt-2" />
        </div>

        <!-- Confirmar Senha -->
        <div class="mt-4">
            <x-input-label for="senha_confirmation" :value="__('Confirmar Senha')" />

            <x-text-input id="senha_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="senha_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('senha_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
