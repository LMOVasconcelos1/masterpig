@extends('layouts.dashboard')

@section('title', 'Gerenciar Causas')
@section('page_title', 'Causas')

@section('content')
<div x-data="{ 
    openModal: {{ ($errors->any() && old('_form') === 'create') ? 'true' : 'false' }}, 
    openEditModal: {{ ($errors->any() && old('_form') === 'edit') ? 'true' : 'false' }},
    openDeleteModal: false,
    openGrupoModal: false, 
    grupos: [], 
    newGrupoNome: '', 
    selectedGrupo: '{{ old('grupo_causa_id') }}',
    createSubmitting: false,
    editSubmitting: false,
    deleteSubmitting: false,
    edit: {
        id: '{{ old('causa_id') }}',
        codigo: @js(old('codigo')),
        nome: @js(old('nome')),
        grupo_causa_id: '{{ old('grupo_causa_id') }}',
        situacao: {{ old('situacao', true) ? 'true' : 'false' }},
    },
    deleting: { id: '', codigo: '', nome: '' },
    showToast: false,
    toastMessage: '',
    toastType: 'success',
    loadingGrupo: false,
    
    init() {
        fetch('/api/grupos-causa')
            .then(response => response.json())
            .then(data => this.grupos = data);

        @if(session('success'))
            this.notify('{{ session('success') }}');
        @endif

        @if(session('error'))
            this.notify('{{ session('error') }}', 'error');
        @endif

        @if($errors->any())
            this.notify('Por favor, corrija os erros no formulário.', 'error');
        @endif
    },

    notify(message, type = 'success') {
        this.toastMessage = message;
        this.toastType = type;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 4000);
    },

    saveGrupo() {
        if (!this.newGrupoNome.trim()) { 
            this.notify('Informe o nome do grupo', 'error'); 
            return; 
        }
        
        this.loadingGrupo = true;
        
        fetch('{{ route('admin.grupo-causa.store', [], false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            },
            body: JSON.stringify({ nome: this.newGrupoNome })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    let msg = err.message;
                    if (err.errors && err.errors.nome) msg = err.errors.nome[0];
                    throw new Error(msg);
                });
            }
            return response.json();
        })
        .then(data => {
            // Adiciona o novo grupo à lista
            this.grupos.push(data);
            
            // Limpa o nome e fecha o modal IMEDIATAMENTE
            const novoId = data.id;
            this.newGrupoNome = '';
            this.openGrupoModal = false;
            
            // Usa nextTick para garantir que o Alpine renderizou o novo <option> antes de selecionar
            this.$nextTick(() => {
                this.selectedGrupo = novoId;
                this.notify('Grupo cadastrado com sucesso!');
            });
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify(error.message || 'Erro ao adicionar grupo.', 'error');
        })
        .finally(() => {
            this.loadingGrupo = false;
        });
    },

    toggleSituacao(id, currentStatus) {
        const toggleUrl = `/admin/causas/${id}/toggle`;
        
        fetch(toggleUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.notify(data.message);
            } else {
                throw new Error('Erro ao atualizar');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify('Não foi possível atualizar a situação.', 'error');
            // Recarrega a página em caso de erro crítico para sincronizar o estado visual
            setTimeout(() => window.location.reload(), 1000);
        });
    }
    ,
    openEdit(causa) {
        this.edit = {
            id: String(causa.id),
            codigo: causa.codigo,
            nome: causa.nome,
            grupo_causa_id: String(causa.grupo_causa_id),
            situacao: Boolean(causa.situacao),
        };
        this.openEditModal = true;
        this.$nextTick(() => {
            this.editSubmitting = false;
        });
    },
    openDelete(causa) {
        this.deleting = {
            id: String(causa.id),
            codigo: causa.codigo,
            nome: causa.nome,
        };
        this.openDeleteModal = true;
        this.$nextTick(() => {
            this.deleteSubmitting = false;
        });
    },
}">
    <!-- Toast Notification -->
    <div 
        x-show="showToast" 
        x-transition:enter="transform ease-out duration-500 transition"
        x-transition:enter-start="translate-y-[-100%] opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white shadow-2xl rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden border-l-4"
        :class="toastType === 'success' ? 'border-green-500' : 'border-red-500'"
        x-cloak
    >
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <template x-if="toastType === 'success'">
                        <i class="fa-solid fa-circle-check text-green-400 text-xl"></i>
                    </template>
                    <template x-if="toastType === 'error'">
                        <i class="fa-solid fa-circle-xmark text-red-400 text-xl"></i>
                    </template>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900" x-text="toastMessage"></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="showToast = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">Fechar</span>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center gap-4 mb-6">
        <!-- Filtros -->
        <form action="{{ route('admin.causas.index', [], false) }}" method="GET" class="flex flex-1 flex-wrap lg:flex-nowrap items-center gap-3 bg-white p-3 rounded-xl shadow-sm border border-gray-100">
            <div class="w-full lg:min-w-[220px] flex-1">
                <select name="grupo_id" class="w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 rounded-lg">
                    <option value="">Todos os Grupos</option>
                    @foreach($gruposCausa as $grupo)
                        <option value="{{ $grupo->id }}" {{ request('grupo_id') == $grupo->id ? 'selected' : '' }}>{{ $grupo->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:min-w-[220px] flex-1">
                <input type="text" name="nome" value="{{ request('nome') }}" placeholder="Causa..." class="w-full pl-3 pr-3 py-2 text-sm border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 rounded-lg">
            </div>
            <div class="w-full sm:w-auto lg:min-w-[170px]">
                <select name="situacao" class="w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 rounded-lg">
                    <option value="">Todas Situações</option>
                    <option value="1" {{ request('situacao') === '1' ? 'selected' : '' }}>Ativos</option>
                    <option value="0" {{ request('situacao') === '0' ? 'selected' : '' }}>Inativos</option>
                </select>
            </div>
            <button type="submit" class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors" title="Filtrar">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            @if(request()->anyFilled(['grupo_id', 'nome', 'situacao']))
                <a href="{{ route('admin.causas.index', [], false) }}" class="text-xs text-red-600 hover:underline font-medium whitespace-nowrap">Limpar</a>
            @endif
        </form>

        <!-- Ações -->
        <div class="flex space-x-3 lg:justify-end">
            <!-- Botão Adicionar (Ícone) -->
            <button 
                @click="openModal = true" 
                class="group relative inline-flex items-center justify-center w-12 h-12 bg-primary-600 text-white rounded-xl shadow-lg hover:bg-primary-700 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                title="Adicionar Nova Causa"
            >
                <i class="fa-solid fa-plus text-xl"></i>
                <span class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Nova Causa</span>
            </button>

            <!-- Botão Relatório (Ícone) -->
            <a 
                href="{{ route('admin.causas.export-pdf', request()->all(), false) }}" 
                target="_blank"
                class="group relative inline-flex items-center justify-center w-12 h-12 bg-white text-primary-600 border-2 border-primary-100 rounded-xl shadow-md hover:bg-primary-50 hover:border-primary-200 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                title="Visualizar Relatório PDF"
            >
                <i class="fa-solid fa-file-pdf text-xl"></i>
                <span class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Visualizar PDF</span>
            </a>
        </div>
    </div>

    <!-- Tabela de Causas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lista de Causas</h6>
        </div>
        <div class="p-4 sm:p-6">
            <div class="space-y-3 md:hidden">
                @forelse($causas as $causa)
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate">{{ $causa->codigo }} - {{ $causa->nome }}</div>
                                <div class="text-xs text-gray-500 mt-1 truncate">Grupo: {{ $causa->grupoCausa->nome }}</div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button" @click="openEdit({ id: {{ $causa->id }}, codigo: @js($causa->codigo), nome: @js($causa->nome), grupo_causa_id: {{ $causa->grupo_causa_id }}, situacao: {{ $causa->situacao ? 'true' : 'false' }} })" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-primary-600 hover:bg-primary-50 transition-colors" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" @click="openDelete({ id: {{ $causa->id }}, codigo: @js($causa->codigo), nome: @js($causa->nome) })" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-red-600 hover:bg-red-50 transition-colors" title="Excluir">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div x-data="{ active: {{ $causa->situacao ? 'true' : 'false' }} }" class="flex items-center justify-between">
                                <div class="text-xs font-semibold" :class="active ? 'text-emerald-700' : 'text-gray-500'" x-text="active ? 'Ativo' : 'Inativo'"></div>
                                <button
                                    type="button"
                                    @click="active = !active; toggleSituacao({{ $causa->id }}, active)"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2"
                                    :class="active ? 'bg-primary-600' : 'bg-gray-200'"
                                    role="switch"
                                    aria-checked="false"
                                >
                                    <span class="sr-only">Alternar situação</span>
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="active ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-500 text-center italic">
                        Nenhuma causa cadastrada.
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grupo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($causas as $causa)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $causa->codigo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $causa->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $causa->grupoCausa->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <!-- Toggle Switch Animado -->
                            <div x-data="{ active: {{ $causa->situacao ? 'true' : 'false' }} }" class="flex items-center">
                                <button 
                                    type="button" 
                                    @click="active = !active; toggleSituacao({{ $causa->id }}, active)"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2" 
                                    :class="active ? 'bg-primary-600' : 'bg-gray-200'"
                                    role="switch" 
                                    aria-checked="false"
                                >
                                    <span class="sr-only">Alternar situação</span>
                                    <span 
                                        aria-hidden="true" 
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="active ? 'translate-x-5' : 'translate-x-0'"
                                    ></span>
                                </button>
                                <span class="ml-3 text-xs font-medium" :class="active ? 'text-green-600' : 'text-gray-400'" x-text="active ? 'Ativo' : 'Inativo'"></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                <button @click="openEdit({ id: {{ $causa->id }}, codigo: @js($causa->codigo), nome: @js($causa->nome), grupo_causa_id: {{ $causa->grupo_causa_id }}, situacao: {{ $causa->situacao ? 'true' : 'false' }} })" class="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Editar">
                                    <i class="fa-solid fa-pen-to-square text-lg"></i>
                                </button>
                                <button @click="openDelete({ id: {{ $causa->id }}, codigo: @js($causa->codigo), nome: @js($causa->nome) })" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                    <i class="fa-solid fa-trash-can text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center italic">Nenhuma causa cadastrada.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro de Causa -->
    <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openModal" @click="openModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="openModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.causas.store', [], false) }}" method="POST" @submit="createSubmitting = true">
                    @csrf
                    <input type="hidden" name="_form" value="create">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Adicionar Nova Causa</h3>
                        <div class="mt-4">
                            <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
                            <input type="text" name="codigo" id="codigo" value="{{ old('codigo') }}" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                            @error('codigo')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-4">
                            <label for="nome_causa" class="block text-sm font-medium text-gray-700">Causa</label>
                            <input type="text" name="nome" id="nome_causa" value="{{ old('nome') }}" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                            @error('nome')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-4">
                            <label for="grupo_causa_id" class="block text-sm font-medium text-gray-700">Grupo da Causa</label>
                            <div class="flex items-center space-x-2">
                                <select x-model="selectedGrupo" name="grupo_causa_id" id="grupo_causa_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                    <option value="">Selecione um grupo</option>
                                    <template x-for="grupo in grupos" :key="grupo.id">
                                        <option :value="grupo.id" x-text="grupo.nome"></option>
                                    </template>
                                </select>
                                <button type="button" @click="openGrupoModal = true" class="p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Adicionar Novo Grupo">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            @error('grupo_causa_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-4">
                            <label for="situacao" class="flex items-center">
                                <input type="hidden" name="situacao" value="0">
                                <input type="checkbox" name="situacao" id="situacao" value="1" {{ old('situacao', true) ? 'checked' : '' }} class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-900">Ativo</span>
                            </label>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="createSubmitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Salvar Causa
                        </button>
                        <button type="button" @click="openModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="openEditModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title-edit" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openEditModal" @click="openEditModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="openEditModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="`/admin/causas/${edit.id}`" method="POST" @submit="editSubmitting = true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="causa_id" :value="edit.id">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-edit">Editar Causa</h3>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Código</label>
                            <input type="text" name="codigo" x-model="edit.codigo" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                            @if(old('_form') === 'edit')
                                @error('codigo')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Causa</label>
                            <input type="text" name="nome" x-model="edit.nome" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                            @if(old('_form') === 'edit')
                                @error('nome')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Grupo da Causa</label>
                            <select name="grupo_causa_id" x-model="edit.grupo_causa_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                <option value="">Selecione um grupo</option>
                                <template x-for="grupo in grupos" :key="grupo.id">
                                    <option :value="String(grupo.id)" x-text="grupo.nome"></option>
                                </template>
                            </select>
                            @if(old('_form') === 'edit')
                                @error('grupo_causa_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                        <div class="mt-4">
                            <label class="flex items-center">
                                <input type="hidden" name="situacao" value="0">
                                <input type="checkbox" name="situacao" value="1" x-model="edit.situacao" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-900">Ativo</span>
                            </label>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="editSubmitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Salvar
                        </button>
                        <button type="button" @click="openEditModal = false" :disabled="editSubmitting" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="openDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title-delete" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openDeleteModal" @click="openDeleteModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="openDeleteModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form :action="`/admin/causas/${deleting.id}`" method="POST" @submit="deleteSubmitting = true">
                    @csrf
                    @method('DELETE')
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-delete">Excluir Causa</h3>
                        <div class="mt-3 text-sm text-gray-600">
                            Tem certeza que deseja excluir a causa <span class="font-semibold text-gray-900" x-text="deleting.codigo"></span> - <span class="font-semibold text-gray-900" x-text="deleting.nome"></span>?
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="deleteSubmitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Excluir
                        </button>
                        <button type="button" @click="openDeleteModal = false" :disabled="deleteSubmitting" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro de Grupo de Causa -->
    <div x-show="openGrupoModal" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title-grupo" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openGrupoModal" @click="openGrupoModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="openGrupoModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-grupo">Adicionar Novo Grupo de Causa</h3>
                    <div class="mt-4">
                        <label for="new_grupo_nome" class="block text-sm font-medium text-gray-700">Nome do Grupo</label>
                        <input type="text" x-model="newGrupoNome" id="new_grupo_nome" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Ex: Falhas Reprodutivas" required>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                        @click="saveGrupo()" 
                        :disabled="loadingGrupo"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <template x-if="!loadingGrupo">
                            <span>Salvar Grupo</span>
                        </template>
                        <template x-if="loadingGrupo">
                            <div class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Gravando...
                            </div>
                        </template>
                    </button>
                    <button type="button" @click="openGrupoModal = false" :disabled="loadingGrupo" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
