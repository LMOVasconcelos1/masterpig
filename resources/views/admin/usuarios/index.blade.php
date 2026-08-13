@extends('layouts.dashboard')

@section('title', 'Usuários')

@section('content')
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Usuários</h2>
            <p class="text-sm text-white">Cadastro e gestão de usuários</p>
        </div>
    </div>
</div>

<div class="space-y-6 mt-6" x-data="{
    openCreate: {{ ($errors->any() && old('_form') === 'create') ? 'true' : 'false' }},
    openEdit: {{ ($errors->any() && old('_form') === 'edit') ? 'true' : 'false' }},
    openDelete: false,
    createSubmitting: false,
    editSubmitting: false,
    deleteSubmitting: false,
    edit: {
        id: '{{ old('user_id') }}',
        usuario: @js(old('usuario')),
        senha: '',
    },
    deleting: { id: '', usuario: '' },
    openEditModal(user) {
        this.edit = { id: String(user.id), usuario: user.usuario, senha: '' };
        this.openEdit = true;
        this.$nextTick(() => { this.editSubmitting = false; });
    },
    openDeleteModal(user) {
        this.deleting = { id: String(user.id), usuario: user.usuario };
        this.openDelete = true;
        this.$nextTick(() => { this.deleteSubmitting = false; });
    },
}">
    @if(!empty($errorMessage))
        <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="flex items-center justify-end">
        <button type="button" @click="openCreate = true" class="group relative inline-flex items-center justify-center w-12 h-12 bg-primary-600 text-white rounded-xl shadow-lg hover:bg-primary-700 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" title="Adicionar Usuário">
            <i class="fa-solid fa-user-plus text-xl"></i>
        </button>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
            <h6 class="font-bold text-primary-700 dark:text-primary-400 uppercase text-xs tracking-wider">Lista de Usuários</h6>
        </div>
        <div class="p-4 sm:p-6">
            <div class="space-y-3 md:hidden">
                @forelse($usuarios as $u)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">{{ $u->nome }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">Usuário: {{ $u->usuario }}</div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button" @click="openEditModal({ id: {{ $u->id }}, usuario: @js($u->usuario) })" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors" title="Editar senha">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" @click="openDeleteModal({ id: {{ $u->id }}, usuario: @js($u->usuario) })" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Excluir">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-500 text-center italic">
                        Nenhum usuário cadastrado.
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuário</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($usuarios as $u)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $u->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $u->usuario }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <button type="button" @click="openEditModal({ id: {{ $u->id }}, usuario: @js($u->usuario) })" class="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Editar">
                                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                                    </button>
                                    <button type="button" @click="openDeleteModal({ id: {{ $u->id }}, usuario: @js($u->usuario) })" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                        <i class="fa-solid fa-trash-can text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center italic">Nenhum usuário cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div x-show="openCreate" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openCreate" @click="openCreate = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openCreate" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-800">
                <form accept-charset="UTF-8" action="{{ route('admin.usuarios.store', [], false) }}" method="POST" @submit="createSubmitting = true">
                    @csrf
                    <input type="hidden" name="_form" value="create">
                    <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Adicionar Usuário</h3>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</label>
                            <input type="text" name="usuario" value="{{ old('usuario') }}" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" required>
                            @if(old('_form') === 'create') @error('usuario') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror @endif
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha</label>
                            <input type="password" name="senha" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" required>
                            @if(old('_form') === 'create') @error('senha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" :disabled="createSubmitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Salvar
                        </button>
                        <button type="button" @click="openCreate = false" :disabled="createSubmitting" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="openEdit" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title-edit" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openEdit" @click="openEdit = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openEdit" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-800">
                <form accept-charset="UTF-8" :action="`/admin/usuarios/${edit.id}`" method="POST" @submit="editSubmitting = true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="user_id" :value="edit.id">
                    <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Editar Usuário</h3>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuário</label>
                            <input type="text" x-model="edit.usuario" disabled class="mt-1 block w-full shadow-sm sm:text-sm border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nova senha (opcional)</label>
                            <input type="password" name="senha" x-model="edit.senha" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md">
                            @if(old('_form') === 'edit') @error('senha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" :disabled="editSubmitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Salvar
                        </button>
                        <button type="button" @click="openEdit = false" :disabled="editSubmitting" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="openDelete" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title-delete" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openDelete" @click="openDelete = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openDelete" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-800">
                <form accept-charset="UTF-8" :action="`/admin/usuarios/${deleting.id}`" method="POST" @submit="deleteSubmitting = true">
                    @csrf
                    @method('DELETE')
                    <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Excluir Usuário</h3>
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            Tem certeza que deseja excluir o usuário <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="deleting.usuario"></span>?
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" :disabled="deleteSubmitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Excluir
                        </button>
                        <button type="button" @click="openDelete = false" :disabled="deleteSubmitting" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
