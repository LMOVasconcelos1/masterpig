@extends('layouts.dashboard')

@section('title', 'Perfil do Usuário')
@section('page_title', 'Configurações da Conta')

@section('content')
@php($photoUrl = $user->foto_perfil_url ?? ('https://ui-avatars.com/api/?name='.urlencode($user->nome ?? 'User').'&background=3b82f6&color=fff'))
<div x-data="{ tab: 'account', photoUrl: '{{ $photoUrl }}', photoPreview: null }" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-6 bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img class="w-16 h-16 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 object-cover" :src="photoPreview || photoUrl" alt="Avatar">
                        <label for="foto_perfil" class="absolute -bottom-2 -right-2 w-9 h-9 inline-flex items-center justify-center rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" title="Alterar foto">
                            <i class="fa-solid fa-pencil"></i>
                        </label>
                    </div>
                    <div class="min-w-0">
                        <div class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate">{{ $user->nome }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</div>
                        <div class="mt-1 inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 border border-primary-100 dark:border-primary-800 px-3 py-1 text-xs font-semibold uppercase">
                            {{ $user->perfil ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4">
                <nav class="space-y-2">
                    <button type="button" @click="tab = 'account'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border transition-colors text-left" :class="tab === 'account' ? 'bg-primary-600 text-white border-primary-600 shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-200 dark:hover:border-gray-600'">
                        <i class="fa-solid fa-user w-5" :class="tab === 'account' ? 'text-white' : 'text-primary-600 dark:text-primary-400'"></i>
                        <span class="font-semibold">Dados da Conta</span>
                    </button>
                    <button type="button" @click="tab = 'password'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border transition-colors text-left" :class="tab === 'password' ? 'bg-primary-600 text-white border-primary-600 shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-200 dark:hover:border-gray-600'">
                        <i class="fa-solid fa-key w-5" :class="tab === 'password' ? 'text-white' : 'text-primary-600 dark:text-primary-400'"></i>
                        <span class="font-semibold">Alterar Senha</span>
                    </button>
                    <form accept-charset="UTF-8" method="POST" action="{{ route('logout', [], false) }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-100 dark:hover:border-red-800 transition-colors text-left">
                            <i class="fa-solid fa-right-from-bracket w-5"></i>
                            <span class="font-semibold">Sair</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>
    </div>

    <div class="lg:col-span-8 space-y-6">
        <div x-show="tab === 'account'" x-cloak class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Dados da Conta</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Atualize suas informações de perfil e e-mail.</div>
            </div>
            <div class="p-6">
                <form accept-charset="UTF-8" method="POST" action="{{ route('profile.update', [], false) }}" class="space-y-5" enctype="multipart/form-data">
                    @csrf
                    @method('patch')

                    <input id="foto_perfil" name="foto_perfil" type="file" accept="image/png,image/jpeg,image/webp" @change="photoPreview = ($event.target.files && $event.target.files[0]) ? URL.createObjectURL($event.target.files[0]) : null" class="sr-only">
                    <x-input-error :messages="$errors->get('foto_perfil')" class="mt-2" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                            <input id="nome" name="nome" type="text" value="{{ old('nome', $user->nome) }}" required autocomplete="name" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            <x-input-error :messages="$errors->get('nome')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                            <input type="text" value="{{ $user->cpf ?? '-' }}" disabled class="mt-1 w-full shadow-sm sm:text-sm border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</label>
                            <input type="text" value="{{ $user->usuario ?? '-' }}" disabled class="mt-1 w-full shadow-sm sm:text-sm border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Salvar Alterações
                        </button>
                        @if (session('status') === 'profile-updated')
                            <span class="text-sm text-emerald-700 font-semibold">Salvo com sucesso.</span>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div x-show="tab === 'password'" x-cloak class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Alterar Senha</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Defina uma senha forte para manter sua conta segura.</div>
            </div>
            <div class="p-6">
                <form accept-charset="UTF-8" method="POST" action="{{ route('password.update', [], false) }}" class="space-y-5">
                    @csrf
                    @method('put')

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha atual</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nova senha</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar nova senha</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Salvar Senha
                        </button>
                        @if (session('status') === 'password-updated')
                            <span class="text-sm text-emerald-700 font-semibold">Senha atualizada.</span>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-red-100 dark:border-red-900/30 overflow-hidden">
            <div class="px-6 py-4 border-b border-red-100 dark:border-red-900/30 bg-red-50/60 dark:bg-red-900/20">
                <div class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wider">Zona de Perigo</div>
                <div class="text-sm text-red-700/80 dark:text-red-400/80 mt-1">Ações irreversíveis.</div>
            </div>
            <div class="p-6">
                <form accept-charset="UTF-8" method="POST" action="{{ route('profile.destroy', [], false) }}" class="space-y-4">
                    @csrf
                    @method('delete')

                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Excluir sua conta remove permanentemente seus dados de acesso.
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="delete_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirme sua senha</label>
                            <input id="delete_password" name="password" type="password" autocomplete="current-password" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-red-500 focus:border-red-500">
                            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Excluir Conta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
