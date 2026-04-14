@extends('layouts.dashboard')

@section('title', 'Gerenciar Rações')

@section('content')
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Rações</h2>
            <p class="text-sm text-white">Cadastro e gestão de rações</p>
        </div>
    </div>
</div>

<div class="mt-6">
<div x-data="{
    openCreate: {{ $errors->any() ? 'true' : 'false' }},
    createTab: 'geral',
    openDetail: false,
    loadingDetail: false,
    pdfUrl: '',
    openStock: false,
    stockLoading: false,
    stockId: null,
    stockCodigo: '',
    stockNome: '',
    stockValor: '',
    selected: null,
    fornecedores: [],
    tiposRacao: [],
    selectedFornecedor: '{{ old('fornecedor_id') }}',
    selectedTipoRacao: '{{ old('tipo_racao_id') }}',
    openFornecedorModal: false,
    openTipoModal: false,
    newFornecedorNome: '',
    newTipoNome: '',
    loadingFornecedor: false,
    loadingTipo: false,
    showToast: false,
    toastMessage: '',
    toastType: 'success',

    init() {
        this.$watch('openCreate', (value) => {
            if (value) this.createTab = 'geral';
        });

        fetch('/api/fornecedores')
            .then(response => response.json())
            .then(data => this.fornecedores = data);

        fetch('/api/tipos-racao')
            .then(response => response.json())
            .then(data => this.tiposRacao = data);

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

    viewRacao(id) {
        this.openDetail = true;
        this.loadingDetail = true;
        this.pdfUrl = `/admin/racoes/${id}/pdf`;
        this.selected = null;

        fetch(`/admin/racoes/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => { this.selected = data; })
            .catch(() => {
                this.notify('Não foi possível carregar os detalhes da ração.', 'error');
                this.openDetail = false;
            })
            .finally(() => { this.loadingDetail = false; });
    },

    openStockModal(id) {
        this.openStock = true;
        this.stockLoading = true;
        this.stockId = id;
        this.stockCodigo = '';
        this.stockNome = '';
        this.stockValor = '';

        fetch(`/admin/racoes/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                this.stockCodigo = data.codigo;
                this.stockNome = data.nome;
                this.stockValor = data.estoque ?? 0;
            })
            .catch(() => {
                this.notify('Não foi possível carregar o estoque.', 'error');
                this.openStock = false;
            })
            .finally(() => { this.stockLoading = false; });
    },

    saveStock() {
        if (!this.stockId) return;

        this.stockLoading = true;

        fetch(`/admin/racoes/${this.stockId}/estoque`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            },
            body: JSON.stringify({ estoque: this.stockValor })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    let msg = err.message;
                    if (err.errors && err.errors.estoque) msg = err.errors.estoque[0];
                    throw new Error(msg);
                });
            }
            return response.json();
        })
        .then(data => {
            this.notify(data.message || 'Estoque atualizado com sucesso!');
            this.openStock = false;
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify(error.message || 'Erro ao atualizar estoque.', 'error');
        })
        .finally(() => { this.stockLoading = false; });
    },

    saveFornecedor() {
        if (!this.newFornecedorNome.trim()) { 
            this.notify('Informe o nome do fornecedor', 'error'); 
            return; 
        }

        this.loadingFornecedor = true;

        fetch('{{ route('admin.fornecedores.store', [], false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            },
            body: JSON.stringify({ nome: this.newFornecedorNome })
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
            this.fornecedores.push(data);
            const novoId = data.id;
            this.newFornecedorNome = '';
            this.openFornecedorModal = false;
            this.$nextTick(() => {
                this.selectedFornecedor = String(novoId);
                this.notify('Fornecedor cadastrado com sucesso!');
            });
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify(error.message || 'Erro ao adicionar fornecedor.', 'error');
        })
        .finally(() => {
            this.loadingFornecedor = false;
        });
    },

    saveTipoRacao() {
        if (!this.newTipoNome.trim()) { 
            this.notify('Informe o nome do tipo de ração', 'error'); 
            return; 
        }

        this.loadingTipo = true;

        fetch('{{ route('admin.tipos-racao.store', [], false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            },
            body: JSON.stringify({ nome: this.newTipoNome })
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
            this.tiposRacao.push(data);
            const novoId = data.id;
            this.newTipoNome = '';
            this.openTipoModal = false;
            this.$nextTick(() => {
                this.selectedTipoRacao = String(novoId);
                this.notify('Tipo de ração cadastrado com sucesso!');
            });
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify(error.message || 'Erro ao adicionar tipo de ração.', 'error');
        })
        .finally(() => {
            this.loadingTipo = false;
        });
    }
}">
    <div 
        x-show="showToast" 
        x-transition:enter="transform ease-out duration-500 transition"
        x-transition:enter-start="translate-y-[-100%] opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white dark:bg-gray-800 shadow-2xl rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 dark:ring-white dark:ring-opacity-10 overflow-hidden border-l-4"
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
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="toastMessage"></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="showToast = false" class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">Fechar</span>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center gap-4 mb-6">
        <form action="{{ route('admin.racoes.index', [], false) }}" method="GET" class="flex flex-1 flex-wrap lg:flex-nowrap items-center gap-3 bg-white dark:bg-gray-900 p-3 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800">
            <div class="w-full lg:min-w-[220px] flex-1">
                <input type="text" name="codigo" value="{{ request('codigo') }}" placeholder="Código..." class="w-full pl-3 pr-3 py-2 text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-primary-500 focus:border-primary-500 rounded-lg">
            </div>
            <div class="w-full lg:min-w-[220px] flex-1">
                <input type="text" name="nome" value="{{ request('nome') }}" placeholder="Nome..." class="w-full pl-3 pr-3 py-2 text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-primary-500 focus:border-primary-500 rounded-lg">
            </div>
            <button type="submit" class="p-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" title="Filtrar">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            @if(request()->anyFilled(['codigo', 'nome']))
                <a href="{{ route('admin.racoes.index', [], false) }}" class="text-xs text-red-600 hover:underline font-medium whitespace-nowrap">Limpar</a>
            @endif
        </form>

        <div class="flex space-x-3 lg:justify-end">
            <button 
                @click="openCreate = true" 
                type="button"
                class="group relative inline-flex items-center justify-center w-12 h-12 bg-primary-600 text-white rounded-xl shadow-lg hover:bg-primary-700 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                title="Adicionar Nova Ração"
            >
                <i class="fa-solid fa-plus text-xl"></i>
                <span class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Nova Ração</span>
            </button>
            <a 
                href="{{ route('admin.racoes.export-pdf', request()->all(), false) }}"
                target="_blank"
                class="group relative inline-flex items-center justify-center w-12 h-12 bg-white text-primary-600 border-2 border-primary-100 rounded-xl shadow-md hover:bg-primary-50 hover:border-primary-200 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                title="Visualizar Relatório PDF"
            >
                <i class="fa-solid fa-file-pdf text-xl"></i>
                <span class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">PDF</span>
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lista de Rações</h6>
                <div class="text-sm text-gray-500 mt-1">Gerencie todas as rações cadastradas</div>
            </div>
        </div>
        <div class="p-4 sm:p-6">
            <div class="space-y-3 md:hidden">
                @forelse($racoes as $racao)
                    <button type="button" class="w-full text-left rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800/50 p-4 shadow-sm" @click="viewRacao({{ $racao->id }})">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">{{ $racao->codigo }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $racao->nome }}</div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button" @click.stop="openStockModal({{ $racao->id }})" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" title="Atualizar estoque">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </button>
                                <div class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-400">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 text-sm text-gray-500 dark:text-gray-400 text-center italic">
                        Nenhuma ração cadastrada.
                    </div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/80">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ver</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($racoes as $racao)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer" @click="viewRacao({{ $racao->id }})">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $racao->codigo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $racao->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <button type="button" @click.stop="openStockModal({{ $racao->id }})" class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Atualizar estoque">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </button>
                                <i class="fa-solid fa-chevron-right text-gray-300 dark:text-gray-600"></i>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center italic">Nenhuma ração cadastrada.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div x-show="openCreate" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openCreate" @click="openCreate = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openCreate" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-100 dark:border-gray-800">
                <form action="{{ route('admin.racoes.store', [], false) }}" method="POST">
                    @csrf
                    <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-5">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="w-11 h-11 bg-white/15 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-wheat-awn text-white text-lg"></i>
                                </div>
                                <div class="ml-4 text-left">
                                    <h3 class="text-lg leading-6 font-semibold text-white">Nova Ração</h3>
                                    <p class="text-xs text-primary-100 mt-1">Cadastre os dados gerais e, se desejar, complemente com nutrição e comercial.</p>
                                </div>
                            </div>
                            <button type="button" @click="openCreate = false" class="text-white/80 hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 px-6 pt-5">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
                            <div class="flex space-x-1">
                                <button type="button" @click="createTab = 'geral'" class="px-4 py-3 text-sm font-semibold rounded-t-lg transition-colors border-b-2" :class="createTab === 'geral' ? 'text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 border-primary-600' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-transparent'">
                                    <i class="fa-solid fa-list-check mr-2"></i>Geral
                                </button>
                                <button type="button" @click="createTab = 'nutricao'" class="px-4 py-3 text-sm font-semibold rounded-t-lg transition-colors border-b-2" :class="createTab === 'nutricao' ? 'text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 border-primary-600' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-transparent'">
                                    <i class="fa-solid fa-flask mr-2"></i>Nutrição
                                </button>
                                <button type="button" @click="createTab = 'comercial'" class="px-4 py-3 text-sm font-semibold rounded-t-lg transition-colors border-b-2" :class="createTab === 'comercial' ? 'text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 border-primary-600' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-transparent'">
                                    <i class="fa-solid fa-tags mr-2"></i>Comercial
                                </button>
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 pb-3">Campos obrigatórios marcados com *</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 px-6 pb-6">
                        <div x-show="createTab === 'geral'" x-cloak class="pt-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="text" name="codigo" value="{{ old('codigo') }}" placeholder="Ex: R001" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500" required>
                                    @error('codigo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
                                    <input type="text" name="nome" value="{{ old('nome') }}" placeholder="Ex: Ração Crescimento Premium" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500" required>
                                    @error('nome')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Classificação *</label>
                                    <input type="text" name="classificacao" value="{{ old('classificacao') }}" placeholder="Ex: Crescimento" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500" required>
                                    @error('classificacao')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estoque *</label>
                                    <div class="mt-1 relative">
                                        <input type="number" step="0.01" name="estoque" value="{{ old('estoque', 0) }}" placeholder="0,00" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500" required>
                                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 dark:text-gray-500">kg</span>
                                    </div>
                                    @error('estoque')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de ração *</label>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <select x-model="selectedTipoRacao" name="tipo_racao_id" class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500" required>
                                            <option value="">Selecione...</option>
                                            <template x-for="t in tiposRacao" :key="t.id">
                                                <option :value="String(t.id)" x-text="t.nome"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="openTipoModal = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar Tipo de Ração">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    @error('tipo_racao_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fase do animal *</label>
                                    <input type="text" name="fase_animal" value="{{ old('fase_animal') }}" placeholder="Ex: Terminação" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500" required>
                                    @error('fase_animal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div x-show="createTab === 'nutricao'" x-cloak class="pt-6">
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Informações Nutricionais</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Opcional</div>
                                </div>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <div class="mt-1 relative">
                                            <input type="number" step="0.01" name="proteina_bruta" value="{{ old('proteina_bruta') }}" placeholder="0,00" class="w-full pr-10 shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">%</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Energia metabolizável</label>
                                        <input type="number" step="0.01" name="energia_metabolizavel" value="{{ old('energia_metabolizavel') }}" placeholder="0,00" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fibra</label>
                                        <input type="number" step="0.01" name="fibra" value="{{ old('fibra') }}" placeholder="0,00" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Lisina</label>
                                        <input type="number" step="0.01" name="lisina" value="{{ old('lisina') }}" placeholder="0,00" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cálcio</label>
                                        <input type="number" step="0.01" name="calcio" value="{{ old('calcio') }}" placeholder="0,00" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fósforo</label>
                                        <input type="number" step="0.01" name="fosforo" value="{{ old('fosforo') }}" placeholder="0,00" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="createTab === 'comercial'" x-cloak class="pt-6">
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Informações Comerciais</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Opcional</div>
                                </div>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <div class="mt-1 flex items-center space-x-2">
                                            <select x-model="selectedFornecedor" name="fornecedor_id" class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                                <option value="">Selecione...</option>
                                                <template x-for="f in fornecedores" :key="f.id">
                                                    <option :value="String(f.id)" x-text="f.nome"></option>
                                                </template>
                                            </select>
                                            <button type="button" @click="openFornecedorModal = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar Fornecedor">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                        @error('fornecedor_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Marca</label>
                                        <input type="text" name="marca" value="{{ old('marca') }}" placeholder="Ex: Marca X" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Custo por kg</label>
                                        <div class="mt-1 relative">
                                            <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">R$</span>
                                            <input type="number" step="0.01" name="custo_por_kg" value="{{ old('custo_por_kg') }}" placeholder="0,00" class="w-full pl-9 shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Unidade de compra</label>
                                        <input type="text" name="unidade_compra" value="{{ old('unidade_compra') }}" placeholder="Ex: Saco" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Peso da embalagem</label>
                                        <div class="mt-1 relative">
                                            <input type="number" step="0.01" name="peso_embalagem" value="{{ old('peso_embalagem') }}" placeholder="0,00" class="w-full pr-10 shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 dark:text-gray-500">kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto">
                            <i class="fa-solid fa-check mr-2"></i>
                            Salvar
                        </button>
                        <button type="button" @click="openCreate = false" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                        <div class="mr-auto hidden sm:block text-xs text-gray-400 dark:text-gray-500">
                            <span x-show="createTab === 'geral'">Preencha os dados obrigatórios para salvar.</span>
                            <span x-show="createTab === 'nutricao'">Você pode deixar em branco se não tiver os dados nutricionais.</span>
                            <span x-show="createTab === 'comercial'">Fornecedor e custos são opcionais.</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="openDetail" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openDetail" @click="openDetail = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openDetail" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Detalhes da Ração</h3>
                        <div class="flex items-center space-x-2">
                            <a :href="pdfUrl" target="_blank" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Abrir PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            <button type="button" @click="openDetail = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div x-show="loadingDetail" class="text-sm text-gray-500">Carregando...</div>

                        <div x-show="!loadingDetail && selected" x-cloak class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                                    <div class="text-sm text-gray-900 dark:text-gray-100" x-text="selected.codigo"></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Nome</div>
                                    <div class="text-sm text-gray-900" x-text="selected.nome"></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Estoque</div>
                                    <div class="text-sm text-gray-900" x-text="(selected.estoque ?? '-') + ' kg'"></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Classificação</div>
                                    <div class="text-sm text-gray-900" x-text="selected.classificacao"></div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Tipo de ração</div>
                                    <div class="text-sm text-gray-900" x-text="selected.tipo_racao ? selected.tipo_racao.nome : '-'"></div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Fase do animal</div>
                                    <div class="text-sm text-gray-900" x-text="selected.fase_animal"></div>
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Informações Nutricionais</div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                        <div class="text-sm text-gray-900 dark:text-gray-100" x-text="selected.proteina_bruta ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Energia metabolizável</div>
                                        <div class="text-sm text-gray-900" x-text="selected.energia_metabolizavel ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Fibra</div>
                                        <div class="text-sm text-gray-900" x-text="selected.fibra ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Lisina</div>
                                        <div class="text-sm text-gray-900" x-text="selected.lisina ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Cálcio</div>
                                        <div class="text-sm text-gray-900" x-text="selected.calcio ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Fósforo</div>
                                        <div class="text-sm text-gray-900" x-text="selected.fosforo ?? '-'"></div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Informações Comerciais</div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                        <div class="text-sm text-gray-900 dark:text-gray-100" x-text="selected.fornecedor ? selected.fornecedor.nome : '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Marca</div>
                                        <div class="text-sm text-gray-900" x-text="selected.marca ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Custo por kg</div>
                                        <div class="text-sm text-gray-900" x-text="selected.custo_por_kg ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Unidade de compra</div>
                                        <div class="text-sm text-gray-900" x-text="selected.unidade_compra ?? '-'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase">Peso da embalagem</div>
                                        <div class="text-sm text-gray-900" x-text="selected.peso_embalagem ?? '-'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="openDetail = false" class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openStock" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openStock" @click="openStock = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openStock" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <div class="text-left">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100">Atualizar estoque</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-semibold dark:text-gray-200" x-text="stockCodigo"></span>
                                <span class="mx-1">-</span>
                                <span x-text="stockNome"></span>
                            </p>
                        </div>
                        <button type="button" @click="openStock = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estoque (kg)</label>
                        <div class="mt-1 relative">
                            <input type="number" step="0.01" x-model="stockValor" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-primary-500 focus:border-primary-500" :disabled="stockLoading">
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 dark:text-gray-500">kg</span>
                        </div>
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="stockLoading">Carregando/salvando...</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                    <button type="button" @click="saveStock()" :disabled="stockLoading" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!stockLoading"><span>Salvar</span></template>
                        <template x-if="stockLoading"><span>Gravando...</span></template>
                    </button>
                    <button type="button" @click="openStock = false" :disabled="stockLoading" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openFornecedorModal" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openFornecedorModal" @click="openFornecedorModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openFornecedorModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-lg text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Adicionar Fornecedor</h3>
                        <button type="button" @click="openFornecedorModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-400">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do fornecedor</label>
                        <input type="text" x-model="newFornecedorNome" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md" placeholder="Ex: Empresa XYZ">
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="saveFornecedor()" :disabled="loadingFornecedor" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loadingFornecedor"><span>Salvar</span></template>
                        <template x-if="loadingFornecedor"><span>Gravando...</span></template>
                    </button>
                    <button type="button" @click="openFornecedorModal = false" :disabled="loadingFornecedor" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openTipoModal" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openTipoModal" @click="openTipoModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openTipoModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-lg text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Adicionar Tipo de Ração</h3>
                        <button type="button" @click="openTipoModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-400">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do tipo</label>
                        <input type="text" x-model="newTipoNome" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md" placeholder="Ex: Gestação">
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/80 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="saveTipoRacao()" :disabled="loadingTipo" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loadingTipo"><span>Salvar</span></template>
                        <template x-if="loadingTipo"><span>Gravando...</span></template>
                    </button>
                    <button type="button" @click="openTipoModal = false" :disabled="loadingTipo" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
