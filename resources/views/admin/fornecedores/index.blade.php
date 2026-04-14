@extends('layouts.dashboard')

@section('title', 'Fornecedores')

@section('content')
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Fornecedores</h2>
            <p class="text-sm text-white">Cadastro e gestão de fornecedores</p>
        </div>
    </div>
</div>

<div class="space-y-6 mt-6" x-data="{
    openCreate: {{ $errors->any() ? 'true' : 'false' }},
}">
    @if(!empty($errorMessage))
        <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Cadastro de Fornecedores</h6>
                <div class="text-sm text-gray-500 mt-1">Cadastro de fornecedores.</div>
            </div>
            <button type="button" @click="openCreate = true" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fa-solid fa-plus mr-2"></i>
                Novo
            </button>
        </div>
        <div class="p-6">
            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-100 text-emerald-900 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-rose-50 border border-rose-100 text-rose-900 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="py-3 pr-4">Nome</th>
                            <th class="py-3 w-[160px]">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($fornecedores as $f)
                            <tr>
                                <td class="py-3 pr-4">
                                    <form method="POST" action="{{ url('/admin/fornecedores/'.$f->id) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="nome" value="{{ old('nome', $f->nome) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm px-3 py-2" />
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Salvar
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3">
                                    <form method="POST" action="{{ url('/admin/fornecedores/'.$f->id) }}" onsubmit="return confirm('Excluir este fornecedor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-900/20 px-3 py-2 text-sm font-semibold text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-6 text-sm text-gray-500">Nenhum fornecedor cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="openCreate" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openCreate" @click="openCreate = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openCreate" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-800">
                <form method="POST" action="{{ url('/admin/fornecedores') }}">
                    @csrf
                    <div class="bg-white dark:bg-gray-900 px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100">Novo fornecedor</h3>
                            <button type="button" @click="openCreate = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                            <input type="text" name="nome" value="{{ old('nome') }}" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" placeholder="Ex: Fornecedor X" required>
                            @error('nome')
                                <div class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto">
                            Salvar
                        </button>
                        <button type="button" @click="openCreate = false" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
