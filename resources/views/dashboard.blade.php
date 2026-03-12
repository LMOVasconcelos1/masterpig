@extends('layouts.dashboard')

@section('title', 'Plantel Reprodutivo')
@section('page_title', '')

@section('content')
<div x-data="{ tab: (function(){ const t = (new URLSearchParams(window.location.search).get('tab') || 'visao'); return t === 'analise' ? 'relatorios' : t; })(), toastOpen: false, toastMessage: '', toastType: 'success' }"
     x-init="
        window.addEventListener('toast', (e) => { toastMessage = e.detail.message; toastType = e.detail.type || 'success'; toastOpen = true; setTimeout(() => toastOpen = false, 4000); });
     "
     class="space-y-6">
<div 
    x-show="toastOpen" 
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
                <button @click="toastOpen = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <span class="sr-only">Fechar</span>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
        <div>
            <div class="text-xs font-bold text-primary-700 uppercase tracking-wider">Plantel Reprodutivo</div>
            <div class="text-sm text-gray-500 mt-1">Visão geral, lançamentos e relatórios</div>
        </div>
        <div class="flex items-center space-x-1">
            <button type="button" @click="tab = 'visao'" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors" :class="tab === 'visao' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Visão Geral
            </button>
            <button type="button" @click="tab = 'lancamentos'" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors" :class="tab === 'lancamentos' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Lançamentos
            </button>
            <button type="button" @click="tab = 'relatorios'" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors" :class="tab === 'relatorios' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Relatórios
            </button>
        </div>
    </div>
</div>

<div x-show="tab === 'visao'" x-cloak>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-5 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Estoque Total (Animais)</div>
                <div class="text-2xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $estoqueTotalAnimais ?? 0 }}
                </div>
            </div>
            <div class="p-3 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-warehouse text-2xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm text-gray-500">
            <i class="fa-solid fa-piggy-bank mr-2"></i>
            <span class="font-medium">Leitoas + Matrizes + Machos</span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-5 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Leitoas Ativas</div>
                <div class="text-2xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $leitoasAtivas ?? 0 }}
                </div>
            </div>
            <div class="p-3 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-piggy-bank text-2xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-skull-crossbones mr-2 text-red-500"></i>
                <span class="font-medium">Mortes</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasLeitoas['morte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-ban mr-2 text-amber-600"></i>
                <span class="font-medium">Descartes</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasLeitoas['descarte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>
                <span class="font-medium">Vendas</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasLeitoas['venda'] ?? 0 }}</span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-5 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Matrizes Ativas</div>
                <div class="text-2xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $matrizesAtivas ?? 0 }}
                </div>
            </div>
            <div class="p-3 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-piggy-bank text-2xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-skull-crossbones mr-2 text-red-500"></i>
                <span class="font-medium">Mortes</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasMatrizes['morte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-ban mr-2 text-amber-600"></i>
                <span class="font-medium">Descartes</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasMatrizes['descarte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>
                <span class="font-medium">Vendas</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasMatrizes['venda'] ?? 0 }}</span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-5 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Machos Ativos</div>
                <div class="text-2xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $machosAtivos ?? 0 }}
                </div>
            </div>
            <div class="p-3 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-piggy-bank text-2xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-skull-crossbones mr-2 text-red-500"></i>
                <span class="font-medium">Mortes</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasMachos['morte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-ban mr-2 text-amber-600"></i>
                <span class="font-medium">Descartes</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasMachos['descarte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>
                <span class="font-medium">Vendas</span>
            </div>
            <span class="font-semibold text-gray-800">{{ $saidasMachos['venda'] ?? 0 }}</span>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Inconsistências do Plantel</h6>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto border border-gray-100 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identificação</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localização</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última operação</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Problema</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($inconsistenciasPlantel ?? []) as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.plantel.femeas.show', $row['femea_id'], false) }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Abrir cadastro">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <a href="{{ route('admin.plantel.femeas.show', $row['femea_id'], false) }}" class="font-semibold text-primary-700 hover:underline">
                                        {{ $row['id_primaria'] }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ $row['id_secundaria'] ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['localizacao'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['ultima_operacao'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-red-700 font-semibold">{{ $row['problema'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-sm text-gray-500 text-center italic">
                                    Nenhuma inconsistência encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div x-show="tab === 'lancamentos'" x-cloak>
    <div x-data="{
        item: 'femeas',
        mov: 'compra',
        compraFemeasTipo: 'leitoa',
        openNovo: false,
        openNovoTab: 'principal',
        nascimentoAuto: true,
        lastAutoNascimento: '',
        syncingNascimento: false,
        novoSubtipo: null,
        lancamentosLoading: false,
        lancamentosError: '',
        comprasFemeas: [],
        comprasMachos: [],
        mortesFemeas: [],
        descartesFemeas: [],
        vendasFemeas: [],
        racas: [],
        fornecedores: [],
        racaId: '',
        fornecedorId: '',
        idPrimaria: '',
        idSecundaria: '',
        dataCompra: '',
        dataNascimento: '',
        ciclosAteCompra: '',
        dataCobertura: '',
        femeaMorteId: '',
        dataMorte: '',
        causaMorteId: '',
        causasMorte: [],
        causasDescarte: [],
        causasVenda: [],
        femeasAtivas: [],
        machosAtivos: [],
        femeasMode: '',
        machosMode: '',
        racasLoaded: false,
        fornecedoresLoaded: false,
        dataDescarte: '',
        causaDescarteId: '',
        dataVenda: '',
        causaVendaId: '',
        valorVenda: '',
        pesoVenda: '',
        comprador: '',
        valorCompra: '',
        pesoCompra: '',
        caracteristicas: '',
        localizacao: '',
        baia: '',
        openNovaRaca: false,
        novaRacaNome: '',
        saving: false,
        normalizeDateInput(value) {
            const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
            const d = digits.slice(0, 2);
            const m = digits.slice(2, 4);
            const y = digits.slice(4, 8);
            if (digits.length <= 2) return d;
            if (digits.length <= 4) return `${d}/${m}`;
            return `${d}/${m}/${y}`;
        },
        parseBrDate(value) {
            const v = String(value || '').trim();
            if (!v) return '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
            const match = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (!match) return '';
            const dd = match[1];
            const mm = match[2];
            const yyyy = match[3];
            return `${yyyy}-${mm}-${dd}`;
        },
        formatBrDate(iso) {
            const v = String(iso || '').trim();
            if (!v) return '';
            if (v.includes('/')) return v;
            if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
            const [y, m, d] = v.split('-');
            return `${d}/${m}/${y}`;
        },
        get diasGestacao() {
            if (!(this.item === 'femeas' && this.mov === 'compra' && this.compraFemeasTipo === 'matriz_gestante')) return null;
            const cobIso = this.parseBrDate(this.dataCobertura);
            if (!cobIso) return null;
            const start = new Date(cobIso + 'T00:00:00');
            const today = new Date();
            const diff = Math.floor((today.getTime() - start.getTime()) / 86400000);
            if (!Number.isFinite(diff) || diff < 0) return null;
            return diff;
        },
        get itemLabel() {
            const map = { femeas: 'Fêmeas', machos: 'Machos', semen: 'Sêmen' };
            return map[this.item] ?? 'Item';
        },
        get movLabel() {
            const map = { compra: 'Compra', morte: 'Morte', descarte: 'Descarte', venda: 'Venda' };
            return map[this.mov] ?? 'Movimentação';
        },
        get subtipoLabel() {
            if (this.item !== 'femeas' || this.mov !== 'compra') return '';
            const map = {
                leitoa: 'Leitoa',
                matriz_vazia: 'Matriz vazia',
                matriz_gestante: 'Matriz gestante',
            };
            return map[this.compraFemeasTipo] ?? '';
        },
        get tipoLabel() {
            if (this.mov === 'compra') return `Compra de ${this.itemLabel}`;
            if (this.mov === 'morte') return `Morte - ${this.itemLabel}`;
            if (this.mov === 'descarte') return `Descarte - ${this.itemLabel}`;
            if (this.mov === 'venda') return `Venda - ${this.itemLabel}`;
            return `${this.movLabel} - ${this.itemLabel}`;
        },
        get modalTitle() {
            if (this.item === 'femeas' && this.mov === 'compra') {
                return `Incluir compra de ${this.subtipoLabel || 'fêmeas'}`;
            }
            return `Novo - ${this.tipoLabel}`;
        },
        get ciclosObrigatorio() {
            return this.item === 'femeas' && this.mov === 'compra' && (this.compraFemeasTipo === 'matriz_vazia' || this.compraFemeasTipo === 'matriz_gestante');
        },
        get coberturaObrigatorio() {
            return this.item === 'femeas' && this.mov === 'compra' && this.compraFemeasTipo === 'matriz_gestante';
        },
        get sugestaoNascimento() {
            const compraIso = this.parseBrDate(this.dataCompra);
            if (!compraIso) return '';
            const ciclos = Number(this.ciclosAteCompra);
            if (!Number.isFinite(ciclos) || ciclos <= 0) return '';
            const d = new Date(compraIso + 'T00:00:00');
            d.setDate(d.getDate() - Math.round(ciclos * 21));
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${dd}/${mm}/${yyyy}`;
        },
        init() {
            this.$watch('dataCompra', () => this.syncNascimentoFromCiclos());
            this.$watch('ciclosAteCompra', () => this.syncNascimentoFromCiclos());
            this.$watch('compraFemeasTipo', () => this.syncNascimentoFromCiclos());
        },
        syncNascimentoFromCiclos() {
            if (!(this.item === 'femeas' && this.mov === 'compra' && this.compraFemeasTipo === 'matriz_vazia')) return;
            const sugestao = this.sugestaoNascimento;
            if (!sugestao) return;

            const current = String(this.dataNascimento || '').trim();
            const shouldAuto = this.nascimentoAuto || current === '' || current === this.lastAutoNascimento;

            if (!shouldAuto) return;

            this.syncingNascimento = true;
            this.dataNascimento = sugestao;
            this.lastAutoNascimento = sugestao;
            this.nascimentoAuto = true;
            this.syncingNascimento = false;
        },
        ensureRacas() {
            if (this.racasLoaded) return;
            this.racasLoaded = true;
            fetch('/api/racas')
                .then(r => r.json())
                .then(data => this.racas = data);
        },
        ensureFornecedores() {
            if (this.fornecedoresLoaded) return;
            this.fornecedoresLoaded = true;
            fetch('/api/fornecedores')
                .then(r => r.json())
                .then(data => this.fornecedores = data);
        },
        ensureFemeasAtivas() {
            if (this.femeasAtivas.length > 0 && this.femeasMode === 'ativas') return;
            fetch('/api/plantel/femeas')
                .then(r => r.json())
                .then(data => {
                    this.femeasAtivas = data;
                    this.femeasMode = 'ativas';
                });
        },
        ensureFemeasTodas() {
            if (this.femeasAtivas.length > 0 && this.femeasMode === 'todas') return;
            fetch('/api/plantel/femeas?all=1')
                .then(r => r.json())
                .then(data => {
                    this.femeasAtivas = data;
                    this.femeasMode = 'todas';
                });
        },
        ensureMachosAtivos() {
            if (this.machosAtivos.length > 0 && this.machosMode === 'ativos') return;
            fetch('/api/plantel/machos')
                .then(r => r.json())
                .then(data => {
                    this.machosAtivos = data;
                    this.machosMode = 'ativos';
                });
        },
        ensureMachosTodos() {
            if (this.machosAtivos.length > 0 && this.machosMode === 'todos') return;
            fetch('/api/plantel/machos?all=1')
                .then(r => r.json())
                .then(data => {
                    this.machosAtivos = data;
                    this.machosMode = 'todos';
                });
        },
        afterSaveReload() {
            if (this.item === 'femeas' && this.mov === 'compra') this.loadComprasFemeas();
            if (this.item === 'machos' && this.mov === 'compra') this.loadComprasMachos();
            if (this.item === 'femeas' && this.mov === 'morte') this.loadMortesFemeas();
            if (this.item === 'machos' && this.mov === 'morte') this.loadMortesMachos();
            if (this.item === 'femeas' && this.mov === 'descarte') this.loadDescartesFemeas();
            if (this.item === 'machos' && this.mov === 'descarte') this.loadDescartesMachos();
            if (this.item === 'femeas' && this.mov === 'venda') this.loadVendasFemeas();
            if (this.item === 'machos' && this.mov === 'venda') this.loadVendasMachos();
        },
        ensureCausasMorte() {
            if (this.causasMorte.length > 0) return;
            fetch('/api/plantel/causas-morte')
                .then(r => r.json())
                .then(data => this.causasMorte = data);
        },
        ensureCausasDescarte() {
            if (this.causasDescarte.length > 0) return;
            fetch('/api/plantel/causas-descarte')
                .then(r => r.json())
                .then(data => this.causasDescarte = data);
        },
        ensureCausasVenda() {
            if (this.causasVenda.length > 0) return;
            fetch('/api/plantel/causas-venda')
                .then(r => r.json())
                .then(data => this.causasVenda = data);
        },
        loadComprasFemeas() {
            if (!(this.item === 'femeas' && this.mov === 'compra')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/femeas/compras', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.comprasFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.comprasFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadComprasMachos() {
            if (!(this.item === 'machos' && this.mov === 'compra')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/machos/compras', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.comprasMachos = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.comprasMachos = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadMortesMachos() {
            if (!(this.item === 'machos' && this.mov === 'morte')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/machos/mortes', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.mortesFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.mortesFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadDescartesMachos() {
            if (!(this.item === 'machos' && this.mov === 'descarte')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/machos/descartes', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.descartesFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.descartesFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadVendasMachos() {
            if (!(this.item === 'machos' && this.mov === 'venda')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/machos/vendas', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.vendasFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.vendasFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadMortesFemeas() {
            if (!(this.item === 'femeas' && this.mov === 'morte')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/femeas/mortes', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.mortesFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.mortesFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadDescartesFemeas() {
            if (!(this.item === 'femeas' && this.mov === 'descarte')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/femeas/descartes', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.descartesFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.descartesFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        loadVendasFemeas() {
            if (!(this.item === 'femeas' && this.mov === 'venda')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/femeas/vendas', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.vendasFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.vendasFemeas = [];
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        formatData(iso) {
            if (!iso) return '-';
            if (typeof iso === 'string' && iso.includes('/')) return iso;
            const [y, m, d] = iso.split('-');
            if (!y || !m || !d) return iso;
            return `${d}/${m}/${y}`;
        },
        formatTipo(v) {
            const map = { leitoa: 'Leitoa', matriz_vazia: 'Matriz vazia', matriz_gestante: 'Matriz gestante' };
            return map[v] ?? (v ?? '-');
        },
        formatIdade(dias) {
            if (dias === null || dias === undefined) return '-';
            const n = Number(dias);
            if (!Number.isFinite(n)) return '-';
            if (n > 1000) return '1000+';
            return `${Math.floor(n)}`;
        },
        resetForm() {
            this.openNovoTab = 'principal';
            this.nascimentoAuto = true;
            this.lastAutoNascimento = '';
            this.racaId = '';
            this.fornecedorId = '';
            this.idPrimaria = '';
            this.idSecundaria = '';
            this.dataCompra = '';
            this.dataNascimento = '';
            this.ciclosAteCompra = '';
            this.dataCobertura = '';
            this.femeaMorteId = '';
            this.dataMorte = '';
            this.causaMorteId = '';
            this.dataDescarte = '';
            this.causaDescarteId = '';
            this.dataVenda = '';
            this.causaVendaId = '';
            this.valorVenda = '';
            this.pesoVenda = '';
            this.comprador = '';
            this.valorCompra = '';
            this.pesoCompra = '';
            this.caracteristicas = '';
            this.localizacao = '';
            this.baia = '';
        },
        openNovoForm(subtipo = null) {
            this.novoSubtipo = subtipo;
            if (this.item === 'femeas' && this.mov === 'compra' && subtipo) {
                this.compraFemeasTipo = subtipo;
            }
            this.resetForm();
            if (this.mov === 'compra') {
                this.ensureRacas();
                this.ensureFornecedores();
            }
            this.openNovo = true;
        },
        openNovoMorte() {
            this.resetForm();
            this.ensureFemeasAtivas();
            this.ensureCausasMorte();
            this.openNovo = true;
        },
        openNovoDescarte() {
            this.resetForm();
            this.ensureFemeasAtivas();
            this.ensureCausasDescarte();
            this.openNovo = true;
        },
        openNovoVenda() {
            this.resetForm();
            this.ensureFemeasTodas();
            this.ensureCausasVenda();
            this.openNovo = true;
        },
        openNovoMorteMacho() {
            this.resetForm();
            this.ensureMachosAtivos();
            this.ensureCausasMorte();
            this.openNovo = true;
        },
        openNovoDescarteMacho() {
            this.resetForm();
            this.ensureMachosAtivos();
            this.ensureCausasDescarte();
            this.openNovo = true;
        },
        openNovoVendaMacho() {
            this.resetForm();
            this.ensureMachosTodos();
            this.ensureCausasVenda();
            this.openNovo = true;
        },
        saveRaca() {
            if (!this.novaRacaNome.trim()) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe o nome da raça', type: 'error' } }));
                return;
            }

            this.saving = true;

            fetch('{{ route('admin.racas.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ nome: this.novaRacaNome })
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
                this.racas.push(data);
                this.racaId = String(data.id);
                this.novaRacaNome = '';
                this.openNovaRaca = false;
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao cadastrar raça', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveCompraFemea() {
            this.saving = true;

            const payload = {
                tipo_compra: this.compraFemeasTipo,
                id_primaria: this.idPrimaria,
                id_secundaria: this.idSecundaria || null,
                data_compra: this.parseBrDate(this.dataCompra),
                data_nascimento: this.parseBrDate(this.dataNascimento) || null,
                ciclos_ate_compra: this.ciclosAteCompra === '' ? null : Number(this.ciclosAteCompra),
                data_cobertura: this.parseBrDate(this.dataCobertura) || null,
                raca_id: this.racaId,
                valor_compra: this.valorCompra === '' ? null : Number(this.valorCompra),
                peso_compra: this.pesoCompra === '' ? null : Number(this.pesoCompra),
                fornecedor_id: this.fornecedorId || null,
                caracteristicas: this.caracteristicas || null,
                localizacao: this.localizacao || null,
                baia: this.baia || null,
            };

            fetch('{{ route('admin.plantel.femeas.compras.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Compra registrada com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveMorteFemea() {
            this.saving = true;

            const payload = {
                femea_id: this.femeaMorteId,
                data_morte: this.parseBrDate(this.dataMorte),
                causa_id: this.causaMorteId,
            };

            fetch('{{ route('admin.plantel.femeas.mortes.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Morte registrada com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveMorteMacho() {
            this.saving = true;

            const payload = {
                macho_id: this.femeaMorteId,
                data_morte: this.parseBrDate(this.dataMorte),
                causa_id: this.causaMorteId,
            };

            fetch('{{ route('admin.plantel.machos.mortes.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Morte registrada com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveDescarteFemea() {
            this.saving = true;

            const payload = {
                femea_id: this.femeaMorteId,
                data_descarte: this.parseBrDate(this.dataDescarte),
                causa_id: this.causaDescarteId,
            };

            fetch('{{ route('admin.plantel.femeas.descarte.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Descarte registrado com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveDescarteMacho() {
            this.saving = true;

            const payload = {
                macho_id: this.femeaMorteId,
                data_descarte: this.parseBrDate(this.dataDescarte),
                causa_id: this.causaDescarteId,
            };

            fetch('{{ route('admin.plantel.machos.descarte.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Descarte registrado com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveVendaFemea() {
            this.saving = true;

            const payload = {
                femea_id: this.femeaMorteId,
                data_venda: this.parseBrDate(this.dataVenda),
                causa_id: this.causaVendaId,
                valor_venda: this.valorVenda === '' ? null : Number(this.valorVenda),
                peso_venda: this.pesoVenda === '' ? null : Number(this.pesoVenda),
                comprador: this.comprador || null,
            };

            fetch('{{ route('admin.plantel.femeas.venda.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Venda registrada com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveCompraMacho() {
            this.saving = true;

            const payload = {
                id_primaria: this.idPrimaria,
                id_secundaria: this.idSecundaria || null,
                data_compra: this.parseBrDate(this.dataCompra),
                data_nascimento: this.parseBrDate(this.dataNascimento) || null,
                raca_id: this.racaId,
                valor_compra: this.valorCompra === '' ? null : Number(this.valorCompra),
                peso_compra: this.pesoCompra === '' ? null : Number(this.pesoCompra),
                fornecedor_id: this.fornecedorId || null,
                caracteristicas: this.caracteristicas || null,
                localizacao: this.localizacao || null,
                baia: this.baia || null,
            };

            fetch('{{ route('admin.plantel.machos.compras.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Compra registrada com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
        saveVendaMacho() {
            this.saving = true;

            const payload = {
                macho_id: this.femeaMorteId,
                data_venda: this.parseBrDate(this.dataVenda),
                causa_id: this.causaVendaId,
                valor_venda: this.valorVenda === '' ? null : Number(this.valorVenda),
                peso_venda: this.pesoVenda === '' ? null : Number(this.pesoVenda),
                comprador: this.comprador || null,
            };

            fetch('{{ route('admin.plantel.machos.venda.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        let msg = err.message;
                        if (err.errors) {
                            const firstKey = Object.keys(err.errors)[0];
                            if (firstKey) msg = err.errors[firstKey][0];
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Venda registrada com sucesso!', type: 'success' } }));
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar', type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
        },
    }" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lançamentos</h6>
                    <div class="text-sm text-gray-500 mt-1">Escolha o item (macho, fêmea ou sêmen) e depois a movimentação.</div>
                </div>
            </div>
            <div class="p-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-4">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">1) Item</div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button type="button" @click="item = 'femeas'; mov = 'compra'; compraFemeasTipo = 'leitoa'; loadComprasFemeas()" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="item === 'femeas' ? 'border-primary-200 bg-primary-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Fêmeas</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="item === 'femeas' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Matrizes / fêmeas do plantel.</div>
                        </button>
                        <button type="button" @click="item = 'machos'; mov = 'compra'; loadComprasMachos()" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="item === 'machos' ? 'border-primary-200 bg-primary-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Machos</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="item === 'machos' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Reprodutores / machos do plantel.</div>
                        </button>
                        <button type="button" @click="item = 'semen'" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="item === 'semen' ? 'border-primary-200 bg-primary-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Sêmen</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="item === 'semen' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-vial"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Doses/lot(es) de sêmen.</div>
                        </button>
                    </div>
                </div>

                <div class="bg-white border border-gray-100 rounded-2xl p-4 mt-4">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">2) Movimentação</div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <button type="button" @click="mov = 'compra'; loadComprasFemeas(); loadComprasMachos()" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="mov === 'compra' ? 'border-primary-200 bg-primary-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Compra</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="mov === 'compra' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Entrada.</div>
                        </button>
                        <button type="button" @click="mov = 'morte'; loadMortesFemeas(); loadMortesMachos()" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="mov === 'morte' ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Morte</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="mov === 'morte' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-skull-crossbones"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Saída.</div>
                        </button>
                        <button type="button" @click="mov = 'descarte'; loadDescartesFemeas(); loadDescartesMachos()" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="mov === 'descarte' ? 'border-amber-200 bg-amber-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Descarte</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="mov === 'descarte' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-ban"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Saída.</div>
                        </button>
                        <button type="button" @click="mov = 'venda'; loadVendasFemeas(); loadVendasMachos()" class="p-4 rounded-2xl border transition-all text-left hover:shadow-sm" :class="mov === 'venda' ? 'border-emerald-200 bg-emerald-50' : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Venda</div>
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="mov === 'venda' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Saída.</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider" x-text="tipoLabel"></div>
                    <div class="text-sm text-gray-500 mt-1">
                        <span>Listagem do tipo selecionado.</span>
                        <span x-show="item === 'femeas' && mov === 'compra'" x-cloak class="ml-1">Selecione o subtipo para cadastrar.</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <template x-if="item === 'femeas' && mov === 'compra'">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openNovoForm('leitoa')" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-pink-50 text-pink-600">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </span>
                                Leitoa
                            </button>
                            <button type="button" @click="openNovoForm('matriz_vazia')" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 text-sky-600">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </span>
                                Matriz vazia
                            </button>
                            <button type="button" @click="openNovoForm('matriz_gestante')" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-violet-50 text-violet-600">
                                    <i class="fa-solid fa-egg"></i>
                                </span>
                                Matriz gestante
                            </button>
                        </div>
                    </template>
                    <template x-if="item === 'machos' && mov === 'compra'">
                        <button type="button" @click="openNovoForm()" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-50 text-primary-600">
                                <i class="fa-solid fa-piggy-bank"></i>
                            </span>
                            Macho
                        </button>
                    </template>
                    <template x-if="item === 'femeas' && mov === 'morte'">
                        <button type="button" @click="openNovoMorte()" title="Registrar morte" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fa-solid fa-skull-crossbones"></i>
                        </button>
                    </template>
                    <template x-if="item === 'machos' && mov === 'morte'">
                        <button type="button" @click="openNovoMorteMacho()" title="Registrar morte" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fa-solid fa-skull-crossbones"></i>
                        </button>
                    </template>
                    <template x-if="item === 'femeas' && mov === 'descarte'">
                        <button type="button" @click="openNovoDescarte()" title="Registrar descarte" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-amber-600 text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    </template>
                    <template x-if="item === 'machos' && mov === 'descarte'">
                        <button type="button" @click="openNovoDescarteMacho()" title="Registrar descarte" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-amber-600 text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    </template>
                    <template x-if="item === 'femeas' && mov === 'venda'">
                        <button type="button" @click="openNovoVenda()" title="Registrar venda" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </button>
                    </template>
                    <template x-if="item === 'machos' && mov === 'venda'">
                        <button type="button" @click="openNovoVendaMacho()" title="Registrar venda" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </button>
                    </template>
                </div>
            </div>
            <div class="p-6">
                <template x-if="item === 'femeas' && mov === 'compra'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="text-sm font-semibold text-gray-700">Listagem</div>
                            </div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>

                        <div x-show="lancamentosError" class="mb-4 bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="lancamentosError" x-cloak></div>

                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID primária</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID secundária</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Raça</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade (dias)</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornecedor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peso</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in comprasFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="formatData(row.data)"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="formatTipo(row.tipo)"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.id_secundaria ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.raca ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.ciclo ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="formatIdade(row.idade_dias)"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.fornecedor ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.peso ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.valor ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && comprasFemeas.length === 0" x-cloak>
                                        <td colspan="11" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'machos' && mov === 'compra'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>

                        <div x-show="lancamentosError" class="mb-4 bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="lancamentosError" x-cloak></div>

                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID primária</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID secundária</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Raça</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade (dias)</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fornecedor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peso</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in comprasMachos" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.id_secundaria ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.raca ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.idade_dias ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.fornecedor ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.peso ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.valor ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && comprasMachos.length === 0" x-cloak>
                                        <td colspan="9" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="!(item === 'femeas' && mov === 'compra') && !(item === 'femeas' && mov === 'morte') && !(item === 'femeas' && mov === 'descarte') && !(item === 'femeas' && mov === 'venda')">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-6 text-sm text-gray-600">
                        Tela pronta. Os demais tipos serão implementados na sequência.
                    </div>
                </template>
                <template x-if="item === 'femeas' && mov === 'morte'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de mortes</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in mortesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm">
                                                <a class="font-semibold text-primary-700 hover:underline" :href="`/admin/plantel/femeas/${row.femea_id}`" x-text="row.id_primaria"></a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.ciclo ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.causa ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && mortesFemeas.length === 0" x-cloak>
                                        <td colspan="5" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'machos' && mov === 'morte'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de mortes</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Macho</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in mortesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.causa ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && mortesFemeas.length === 0" x-cloak>
                                        <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'femeas' && mov === 'descarte'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de descartes</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in descartesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm">
                                                <a class="font-semibold text-primary-700 hover:underline" :href="`/admin/plantel/femeas/${row.femea_id}`" x-text="row.id_primaria"></a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.ciclo ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.causa ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && descartesFemeas.length === 0" x-cloak>
                                        <td colspan="5" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'machos' && mov === 'descarte'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de descartes</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Macho</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in descartesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.causa ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && descartesFemeas.length === 0" x-cloak>
                                        <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'femeas' && mov === 'venda'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de vendas</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in vendasFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm">
                                                <a class="font-semibold text-primary-700 hover:underline" :href="`/admin/plantel/femeas/${row.femea_id}`" x-text="row.id_primaria"></a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.ciclo ?? '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.causa ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && vendasFemeas.length === 0" x-cloak>
                                        <td colspan="5" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'machos' && mov === 'venda'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de vendas</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>
                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Macho</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="row in vendasFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.causa ?? '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && vendasFemeas.length === 0" x-cloak>
                                        <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="openNovo" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openNovo" @click="openNovo = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openNovo" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-flex flex-col align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100 max-h-[85vh]">
                    <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-5">
                        <div class="flex items-start justify-between">
                            <div class="text-left">
                                <h3 class="text-lg leading-6 font-semibold text-white" x-text="(item === 'femeas' && mov === 'morte') ? 'Registrar morte' : ((item === 'femeas' && mov === 'descarte') ? 'Registrar descarte' : ((item === 'femeas' && mov === 'venda') ? 'Registrar venda' : modalTitle))"></h3>
                                <p class="mt-1 text-xs text-primary-100" x-text="(item === 'femeas' && mov === 'compra' && compraFemeasTipo === 'leitoa') ? 'Cadastro de leitoa: use para registrar a compra de uma fêmea jovem que ainda vai entrar no ciclo reprodutivo. Informe identificação, datas e fornecedor.' : ((item === 'femeas' && mov === 'compra' && compraFemeasTipo === 'matriz_vazia') ? 'Cadastro de matriz vazia: use para registrar uma fêmea adulta comprada que não está gestante no momento. Informe a data de compra e os ciclos até a compra para estimarmos a data de nascimento.' : ((item === 'femeas' && mov === 'compra' && compraFemeasTipo === 'matriz_gestante') ? 'Cadastro de matriz gestante: use para registrar uma fêmea adulta comprada já em gestação. Informe data de cobertura (gestação) e a data de compra; o sistema exibe os dias de gestação.' : 'Informe os campos necessários para concluir o cadastro.'))"></p>
                            </div>
                            <button type="button" @click="openNovo = false" class="text-white/80 hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-white px-6 py-6 overflow-y-auto flex-1 min-h-0">
                        <div x-show="mov === 'compra'" class="-mx-6 -mt-6 mb-6 px-6 pt-4 pb-3 bg-white/95 backdrop-blur border-b border-gray-100 sticky top-0 z-10" x-cloak>
                            <div class="inline-flex items-center gap-1 rounded-2xl bg-gray-100 p-1 shadow-sm border border-gray-200">
                                <button type="button" @click="openNovoTab = 'principal'" class="px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" :class="openNovoTab === 'principal' ? 'bg-white text-primary-700 shadow border border-gray-200' : 'bg-transparent text-gray-600 hover:text-gray-800 hover:bg-white/60'">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-list-check text-sm"></i>
                                        Principal
                                    </span>
                                </button>
                                <button type="button" @click="openNovoTab = 'complementares'" class="px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" :class="openNovoTab === 'complementares' ? 'bg-white text-primary-700 shadow border border-gray-200' : 'bg-transparent text-gray-600 hover:text-gray-800 hover:bg-white/60'">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-sliders text-sm"></i>
                                        Complementares
                                    </span>
                                </button>
                            </div>
                        </div>
                        <template x-if="item === 'femeas' && mov === 'morte'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Fêmea</label>
                                    <select x-model="femeaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="f in femeasAtivas" :key="f.id">
                                            <option :value="String(f.id)" x-text="f.id_primaria + (f.id_secundaria ? ' / ' + f.id_secundaria : '')"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data da morte</label>
                                    <input type="text" x-model="dataMorte" @input="dataMorte = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Causa da morte</label>
                                    <select x-model="causaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="c in causasMorte" :key="c.id">
                                            <option :value="String(c.id)" x-text="c.codigo + ' - ' + c.nome"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <template x-if="item === 'machos' && mov === 'morte'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Macho</label>
                                    <select x-model="femeaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="m in machosAtivos" :key="m.id">
                                            <option :value="String(m.id)" x-text="m.id_primaria + (m.id_secundaria ? ' / ' + m.id_secundaria : '')"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data da morte</label>
                                    <input type="text" x-model="dataMorte" @input="dataMorte = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Causa da morte</label>
                                    <select x-model="causaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="c in causasMorte" :key="c.id">
                                            <option :value="String(c.id)" x-text="c.codigo + ' - ' + c.nome"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'descarte'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Fêmea</label>
                                    <select x-model="femeaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="f in femeasAtivas" :key="f.id">
                                            <option :value="String(f.id)" x-text="f.id_primaria + (f.id_secundaria ? ' / ' + f.id_secundaria : '')"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data do descarte</label>
                                    <input type="text" x-model="dataDescarte" @input="dataDescarte = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Causa do descarte</label>
                                    <select x-model="causaDescarteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="c in causasDescarte" :key="c.id">
                                            <option :value="String(c.id)" x-text="c.codigo + ' - ' + c.nome"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <template x-if="item === 'machos' && mov === 'descarte'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Macho</label>
                                    <select x-model="femeaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="m in machosAtivos" :key="m.id">
                                            <option :value="String(m.id)" x-text="m.id_primaria + (m.id_secundaria ? ' / ' + m.id_secundaria : '')"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data do descarte</label>
                                    <input type="text" x-model="dataDescarte" @input="dataDescarte = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Causa do descarte</label>
                                    <select x-model="causaDescarteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione...</option>
                                        <template x-for="c in causasDescarte" :key="c.id">
                                            <option :value="String(c.id)" x-text="c.codigo + ' - ' + c.nome"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'venda'">
                            <div class="space-y-4">
                                <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
                                    É importante fazer o descarte primeiro e depois a venda. A venda marca que o animal deixou de ser produtivo no ato da venda. Se ele já estiver descartado há algum tempo, isso pode atrapalhar as análises do sistema.
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Fêmea</label>
                                        <select x-model="femeaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                            <option value="">Selecione...</option>
                                            <template x-for="f in femeasAtivas" :key="f.id">
                                                <option :value="String(f.id)" x-text="f.id_primaria + (f.id_secundaria ? ' / ' + f.id_secundaria : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Data da venda</label>
                                        <input type="text" x-model="dataVenda" @input="dataVenda = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Causa da venda</label>
                                        <select x-model="causaVendaId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                            <option value="">Selecione...</option>
                                            <template x-for="c in causasVenda" :key="c.id">
                                                <option :value="String(c.id)" x-text="c.codigo + ' - ' + c.nome"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Valor da venda (opcional)</label>
                                        <div class="mt-1 relative">
                                            <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">R$</span>
                                            <input type="number" step="0.01" x-model="valorVenda" class="w-full pl-9 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Peso na venda (opcional)</label>
                                        <div class="mt-1 relative">
                                            <input type="number" step="0.01" x-model="pesoVenda" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Comprador (opcional)</label>
                                        <input type="text" x-model="comprador" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Nome do comprador">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="item === 'machos' && mov === 'venda'">
                            <div class="space-y-4">
                                <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
                                    É importante fazer o descarte primeiro e depois a venda. A venda marca que o animal deixou de ser produtivo no ato da venda. Se ele já estiver descartado há algum tempo, isso pode atrapalhar as análises do sistema.
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Macho</label>
                                        <select x-model="femeaMorteId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                            <option value="">Selecione...</option>
                                            <template x-for="m in machosAtivos" :key="m.id">
                                                <option :value="String(m.id)" x-text="m.id_primaria + (m.id_secundaria ? ' / ' + m.id_secundaria : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Data da venda</label>
                                        <input type="text" x-model="dataVenda" @input="dataVenda = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Causa da venda</label>
                                        <select x-model="causaVendaId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                            <option value="">Selecione...</option>
                                            <template x-for="c in causasVenda" :key="c.id">
                                                <option :value="String(c.id)" x-text="c.codigo + ' - ' + c.nome"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Valor da venda (opcional)</label>
                                        <div class="mt-1 relative">
                                            <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">R$</span>
                                            <input type="number" step="0.01" x-model="valorVenda" class="w-full pl-9 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Peso na venda (opcional)</label>
                                        <div class="mt-1 relative">
                                            <input type="number" step="0.01" x-model="pesoVenda" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Comprador (opcional)</label>
                                        <input type="text" x-model="comprador" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Nome do comprador">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="item === 'machos' && mov === 'compra'">
                            <div class="space-y-4">
                                <template x-if="openNovoTab === 'principal'">
                                    <div class="space-y-4">
                                        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Identificação e Datas</div>
                                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">ID primária</label>
                                                    <input type="text" x-model="idPrimaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 2001">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">ID secundária</label>
                                                    <input type="text" x-model="idSecundaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Data de compra</label>
                                                    <input type="text" x-model="dataCompra" @input="dataCompra = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Data de nascimento</label>
                                                    <input type="text" x-model="dataNascimento" @input="dataNascimento = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Classificação</div>
                                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Raça</label>
                                                    <div class="mt-1 flex items-center space-x-2">
                                                        <select x-model="racaId" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                            <option value="">Selecione...</option>
                                                            <template x-for="r in racas" :key="r.id">
                                                                <option :value="String(r.id)" x-text="r.nome"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button" @click="openNovaRaca = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar raça">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                                                    <select x-model="fornecedorId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                        <option value="">Selecione...</option>
                                                        <template x-for="f in fornecedores" :key="f.id">
                                                            <option :value="String(f.id)" x-text="f.nome"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="openNovoTab === 'complementares'">
                                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Complementares</div>
                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Peso na compra</label>
                                                <div class="mt-1 relative">
                                                    <input type="number" step="0.01" x-model="pesoCompra" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Valor da compra</label>
                                                <div class="mt-1 relative">
                                                    <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">R$</span>
                                                    <input type="number" step="0.01" x-model="valorCompra" class="w-full pl-9 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                                </div>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700">Características</label>
                                                <textarea x-model="caracteristicas" rows="2" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional"></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Localização</label>
                                                <input type="text" x-model="localizacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Galpão A">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Baia</label>
                                                <input type="text" x-model="baia" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 12">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'compra'">
                        <div class="space-y-4">
                            <template x-if="openNovoTab === 'principal'">
                                <div class="space-y-4">
                                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Identificação e Datas</div>
                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">ID primária</label>
                                                <input type="text" x-model="idPrimaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 1001">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">ID secundária</label>
                                                <input type="text" x-model="idSecundaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data de compra</label>
                                                <input type="text" x-model="dataCompra" @input="dataCompra = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                            </div>
                                            <div x-show="ciclosObrigatorio" x-cloak>
                                                <label class="block text-sm font-medium text-gray-700">Ciclos até a compra</label>
                                                <input type="number" min="0" step="1" x-model="ciclosAteCompra" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 3">
                                                <div class="mt-1 text-xs text-gray-500">Usado para sugerir a data de nascimento.</div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data de nascimento</label>
                                                <input type="text" x-model="dataNascimento" @input="nascimentoAuto = false; dataNascimento = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                                <div class="mt-1 text-xs text-gray-500" x-show="!dataNascimento && sugestaoNascimento">
                                                    Sugestão: <button type="button" class="font-semibold text-primary-700 hover:underline" @click="nascimentoAuto = true; dataNascimento = sugestaoNascimento" x-text="sugestaoNascimento"></button>
                                                </div>
                                            </div>
                                            <div x-show="coberturaObrigatorio" x-cloak>
                                                <label class="block text-sm font-medium text-gray-700">Data de cobertura</label>
                                                <input type="text" x-model="dataCobertura" @input="dataCobertura = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                                <div class="mt-1 text-xs text-gray-500" x-show="diasGestacao !== null">
                                                    Dias de gestação: <span class="font-semibold text-gray-700" x-text="diasGestacao"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Classificação</div>
                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Raça</label>
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <select x-model="racaId" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                        <option value="">Selecione...</option>
                                                        <template x-for="r in racas" :key="r.id">
                                                            <option :value="String(r.id)" x-text="r.nome"></option>
                                                        </template>
                                                    </select>
                                                    <button type="button" @click="openNovaRaca = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar raça">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                                                <select x-model="fornecedorId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                    <option value="">Selecione...</option>
                                                    <template x-for="f in fornecedores" :key="f.id">
                                                        <option :value="String(f.id)" x-text="f.nome"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="openNovoTab === 'complementares'">
                                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Complementares</div>
                                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Peso na compra</label>
                                            <div class="mt-1 relative">
                                                <input type="number" step="0.01" x-model="pesoCompra" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Valor da compra</label>
                                            <div class="mt-1 relative">
                                                <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">R$</span>
                                                <input type="number" step="0.01" x-model="valorCompra" class="w-full pl-9 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                            </div>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700">Características</label>
                                            <textarea x-model="caracteristicas" rows="2" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Localização</label>
                                            <input type="text" x-model="localizacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Galpão A">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Baia</label>
                                            <input type="text" x-model="baia" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 12">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        </template>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <template x-if="item === 'femeas' && mov === 'morte'">
                            <button type="button" @click="saveMorteFemea()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'machos' && mov === 'morte'">
                            <button type="button" @click="saveMorteMacho()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'descarte'">
                            <button type="button" @click="saveDescarteFemea()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-amber-600 text-sm font-semibold text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'machos' && mov === 'descarte'">
                            <button type="button" @click="saveDescarteMacho()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-amber-600 text-sm font-semibold text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'venda'">
                            <button type="button" @click="saveVendaFemea()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'machos' && mov === 'venda'">
                            <button type="button" @click="saveVendaMacho()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'machos' && mov === 'compra'">
                            <button type="button" @click="saveCompraMacho()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'compra'">
                            <button type="button" @click="saveCompraFemea()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!saving"><span>Salvar</span></template>
                                <template x-if="saving"><span>Gravando...</span></template>
                            </button>
                        </template>
                        <button type="button" @click="openNovo = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="openNovaRaca" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openNovaRaca" @click="openNovaRaca = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openNovaRaca" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900">Cadastrar raça</h3>
                            <button type="button" @click="openNovaRaca = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" x-model="novaRacaNome" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Landrace">
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="button" @click="saveRaca()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!saving"><span>Salvar</span></template>
                            <template x-if="saving"><span>Gravando...</span></template>
                        </button>
                        <button type="button" @click="openNovaRaca = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-show="tab === 'relatorios'" x-cloak>
    <div class="mb-4 bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
        Relatórios em desenvolvimento.
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Relatório de Fêmeas</h6>
                <div class="text-sm text-gray-500 mt-1">Lista completa do plantel de fêmeas com status e última operação.</div>
            </div>
            <div class="p-6">
                <a href="{{ route('admin.relatorios.plantel.femeas', [], false) }}" target="_blank" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Pré-visualizar
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Relatório de Machos</h6>
                <div class="text-sm text-gray-500 mt-1">Lista completa do plantel de machos com status e última operação.</div>
            </div>
            <div class="p-6">
                <a href="{{ route('admin.relatorios.plantel.machos', [], false) }}" target="_blank" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Pré-visualizar
                </a>
            </div>
        </div>
    </div>
</div>

</div>
@endsection
