@extends('layouts.dashboard')

@section('title', 'Creche')
@section('page_title', '')

@section('content')
<div x-data="{ 
    tab: 'visao-geral',
    subTab: 'lotes',
    showLoteModal: false,
    showCompraModal: false,
    showMorteModal: false,

    criteriosLoaded: false,
    calendarType: 'gregoriano',
    activePicker: null,
    calendarMonth: new Date().getMonth(),
    calendarYear: new Date().getFullYear(),
    calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    pickerTop: 0,
    pickerLeft: 0,
    pickerDirection: 'down',

    fornecedores: [],
    fornecedoresLoading: false,

    loteForm: {
        nome: '',
        caracteristicas: ''
    },
    compraForm: {
        dataCompraIso: '{{ date('Y-m-d') }}',
        dataCompra: '',
        lote_id: '',
        localizacao: '',
        quantidade: '',
        peso_total: '',
        dataNascimentoIso: '{{ date('Y-m-d') }}',
        dataNascimento: '',
        valor_compra: '',
        fornecedor_id: '',
        nota_fiscal: ''
    },
    morteForm: {
        lote_id: '',
        localizacao: '',
        dataMorteIso: '{{ date('Y-m-d') }}',
        dataMorte: '',
        quantidade: 1,
        causa: '',
        origem_identificacao: ''
    },

    isoToBr(iso) {
        const v = String(iso || '').trim();
        const m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) return '';
        return `${m[3]}/${m[2]}/${m[1]}`;
    },
    brToIso(br) {
        const v = String(br || '').trim();
        const m = v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!m) return null;
        const d = Number(m[1]);
        const mo = Number(m[2]);
        const y = Number(m[3]);
        if (!Number.isFinite(d) || !Number.isFinite(mo) || !Number.isFinite(y)) return null;
        if (y < 1900 || y > 2100) return null;
        if (mo < 1 || mo > 12) return null;
        if (d < 1 || d > 31) return null;
        const pad = (n) => String(n).padStart(2, '0');
        const dt = new Date(y, mo - 1, d);
        if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return null;
        return `${y}-${pad(mo)}-${pad(d)}`;
    },

    setCompraDataCompraIso(iso) {
        const v = String(iso || '').trim();
        this.compraForm.dataCompraIso = v;
        if (!v) {
            this.compraForm.dataCompra = '';
            return;
        }
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.compraForm.dataCompra = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.compraForm.dataCompra = this.isoToBr(v);
        }
    },
    setCompraDataNascimentoIso(iso) {
        const v = String(iso || '').trim();
        this.compraForm.dataNascimentoIso = v;
        if (!v) {
            this.compraForm.dataNascimento = '';
            return;
        }
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.compraForm.dataNascimento = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.compraForm.dataNascimento = this.isoToBr(v);
        }
    },
    setMorteDataIso(iso) {
        const v = String(iso || '').trim();
        this.morteForm.dataMorteIso = v;
        if (!v) {
            this.morteForm.dataMorte = '';
            return;
        }
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.morteForm.dataMorte = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.morteForm.dataMorte = this.isoToBr(v);
        }
    },

    normalizeCompraDisplay(which) {
        const isPig = this.calendarType === '1000_dias';
        if (which === 'compra_data_compra') {
            const raw = String(this.compraForm.dataCompra || '').trim();
            if (!raw) { this.setCompraDataCompraIso(''); return; }
            let iso = null;
            if (isPig && /^\d{1,4}$/.test(raw) && typeof pigDayToDate === 'function') iso = pigDayToDate(raw);
            if (!iso) iso = this.brToIso(raw);
            if (iso) this.setCompraDataCompraIso(iso);
            return;
        }
        if (which === 'compra_data_nascimento') {
            const raw = String(this.compraForm.dataNascimento || '').trim();
            if (!raw) { this.setCompraDataNascimentoIso(''); return; }
            let iso = null;
            if (isPig && /^\d{1,4}$/.test(raw) && typeof pigDayToDate === 'function') iso = pigDayToDate(raw);
            if (!iso) iso = this.brToIso(raw);
            if (iso) this.setCompraDataNascimentoIso(iso);
        }
    },
    normalizeMorteDisplay() {
        const isPig = this.calendarType === '1000_dias';
        const raw = String(this.morteForm.dataMorte || '').trim();
        if (!raw) { this.setMorteDataIso(''); return; }
        let iso = null;
        if (isPig && /^\d{1,4}$/.test(raw) && typeof pigDayToDate === 'function') iso = pigDayToDate(raw);
        if (!iso) iso = this.brToIso(raw);
        if (iso) this.setMorteDataIso(iso);
    },

    getPickerSelectedIso() {
        if (this.activePicker === 'compra_data_compra') return String(this.compraForm.dataCompraIso || '');
        if (this.activePicker === 'compra_data_nascimento') return String(this.compraForm.dataNascimentoIso || '');
        if (this.activePicker === 'morte_data') return String(this.morteForm.dataMorteIso || '');
        return '';
    },
    prevCalendarMonth() {
        if (this.calendarMonth === 0) {
            this.calendarMonth = 11;
            this.calendarYear--;
        } else {
            this.calendarMonth--;
        }
    },
    nextCalendarMonth() {
        if (this.calendarMonth === 11) {
            this.calendarMonth = 0;
            this.calendarYear++;
        } else {
            this.calendarMonth++;
        }
    },
    getCalendarDays() {
        const firstDay = new Date(this.calendarYear, this.calendarMonth, 1);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        const selectedIso = this.getPickerSelectedIso();
        const days = [];
        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);
            const pad = (n) => String(n).padStart(2, '0');
            const iso = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
            days.push({
                date: iso,
                day: date.getDate(),
                isCurrentMonth: date.getMonth() === this.calendarMonth,
                isSelected: selectedIso ? iso === selectedIso : false,
                pigDay: typeof toPigDay === 'function' ? toPigDay(iso + 'T00:00:00') : ''
            });
        }
        return days;
    },
    openDatePicker(which) {
        let iso = '';
        let ref = null;
        if (which === 'compra_data_compra') {
            iso = String(this.compraForm.dataCompraIso || '').trim();
            ref = this.$refs && this.$refs.dataCompraInput ? this.$refs.dataCompraInput : null;
        }
        if (which === 'compra_data_nascimento') {
            iso = String(this.compraForm.dataNascimentoIso || '').trim();
            ref = this.$refs && this.$refs.dataNascimentoInput ? this.$refs.dataNascimentoInput : null;
        }
        if (which === 'morte_data') {
            iso = String(this.morteForm.dataMorteIso || '').trim();
            ref = this.$refs && this.$refs.dataMorteInput ? this.$refs.dataMorteInput : null;
        }

        const base = iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? new Date(iso + 'T12:00:00') : new Date();
        this.calendarMonth = base.getMonth();
        this.calendarYear = base.getFullYear();
        this.activePicker = which;

        this.$nextTick(() => {
            const el = ref;
            if (!el || typeof el.getBoundingClientRect !== 'function') return;
            const rect = el.getBoundingClientRect();
            const popW = window.innerWidth >= 640 ? 288 : Math.min(320, Math.max(260, window.innerWidth - 32));
            const half = popW / 2;
            const minLeft = half + 8;
            const maxLeft = Math.max(minLeft, window.innerWidth - half - 8);
            const centerLeft = rect.left + rect.width / 2;
            const left = Math.min(maxLeft, Math.max(minLeft, centerLeft));

            const desiredH = 360;
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            const direction = (spaceBelow < desiredH && spaceAbove > spaceBelow) ? 'up' : 'down';
            const top = direction === 'up' ? rect.top : rect.bottom;

            this.pickerLeft = left;
            this.pickerTop = top;
            this.pickerDirection = direction;
        });
    },
    selectCalendarDate(dateStr) {
        const iso = String(dateStr || '').trim();
        const m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) return;
        if (this.activePicker === 'compra_data_compra') this.setCompraDataCompraIso(iso);
        if (this.activePicker === 'compra_data_nascimento') this.setCompraDataNascimentoIso(iso);
        if (this.activePicker === 'morte_data') this.setMorteDataIso(iso);
        this.activePicker = null;
    },

    resetLoteForm() {
        this.loteForm.nome = '';
        this.loteForm.caracteristicas = '';
    },
    resetCompraForm() {
        this.compraForm.lote_id = '';
        this.compraForm.localizacao = '';
        this.compraForm.quantidade = '';
        this.compraForm.peso_total = '';
        this.compraForm.valor_compra = '';
        this.compraForm.fornecedor_id = '';
        this.compraForm.nota_fiscal = '';
        this.setCompraDataCompraIso('{{ date('Y-m-d') }}');
        this.setCompraDataNascimentoIso('{{ date('Y-m-d') }}');
    },
    resetMorteForm() {
        this.morteForm.lote_id = '';
        this.morteForm.localizacao = '';
        this.morteForm.quantidade = 1;
        this.morteForm.causa = '';
        this.morteForm.origem_identificacao = '';
        this.setMorteDataIso('{{ date('Y-m-d') }}');
    },

    init() {
        this.setCompraDataCompraIso(this.compraForm.dataCompraIso);
        this.setCompraDataNascimentoIso(this.compraForm.dataNascimentoIso);
        this.setMorteDataIso(this.morteForm.dataMorteIso);

        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                this.calendarType = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '') ? 'gregoriano' : String(items.criterio_calendario_tipo);
                this.setCompraDataCompraIso(this.compraForm.dataCompraIso);
                this.setCompraDataNascimentoIso(this.compraForm.dataNascimentoIso);
                this.setMorteDataIso(this.morteForm.dataMorteIso);
                this.criteriosLoaded = true;
            })
            .catch(() => { this.criteriosLoaded = true; });

        this.fornecedoresLoading = true;
        fetch('/api/fornecedores', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => { this.fornecedores = Array.isArray(data) ? data : (data.items || []); })
            .catch(() => { this.fornecedores = []; })
            .finally(() => { this.fornecedoresLoading = false; });
    }
}" x-init="init()" class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
            {{ $errors->first() }}
        </div>
    @endif
    <!-- Header & Topbar -->
    <div>
        <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Creche</h2>
                <p class="text-sm text-white">Manejo de leitões em fase de creche</p>
            </div>
            <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
                <button type="button" @click="tab = 'visao-geral'" 
                    :class="tab === 'visao-geral' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Visão Geral
                </button>
                <button type="button" @click="tab = 'lancamentos'" 
                    :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Lançamentos
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Visão Geral -->
    <div x-show="tab === 'visao-geral'" x-cloak class="space-y-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
        
        <!-- Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <!-- Card: Lotes em Aberto -->
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Lotes em Aberto</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ $stats['lotes_abertos'] ?? 0 }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-layer-group text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-clock mr-2"></i>
                    <span class="font-medium">Lotes ativos na creche</span>
                </div>
            </div>

            <!-- Card: Estoque de Animais -->
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Estoque de Animais</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ $stats['estoque_animais'] ?? 0 }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-piggy-bank text-xl"></i>
                    </div>
                </div>
                <!-- Detalhes do estoque (Hospital / Desclassificados) -->
                <div class="mt-4 flex items-center justify-between text-xs text-gray-500 pt-2 border-t border-gray-100">
                    <div class="flex items-center" title="Animais no Hospital">
                        <i class="fa-solid fa-house-medical text-red-400 mr-1.5"></i>
                        <span class="font-medium text-gray-600">{{ $stats['hospital'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center" title="Animais Desclassificados">
                        <i class="fa-solid fa-ban text-amber-500 mr-1.5"></i>
                        <span class="font-medium text-gray-600">{{ $stats['desclassificados'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Card: Taxa de Mortalidade -->
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Taxa de Mortalidade</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ number_format($stats['mortalidade_taxa'] ?? 0, 1, ',', '.') }}%
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-skull-crossbones text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-chart-line mr-2"></i>
                    <span class="font-medium">Média do período</span>
                </div>
            </div>

        </div>

        <!-- Resumo dos Lotes Abertos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Resumo dos Lotes Abertos</h6>
                <div class="text-sm text-gray-500 mt-1">Acompanhamento dos lotes atualmente ativos na creche</div>
            </div>
            <div class="p-6">
                @if(empty($lotes))
                    <div class="py-8 text-center text-gray-500 italic">
                        Nenhum lote aberto no momento.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($lotes as $lote)
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all">
                                <div class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-primary-600 uppercase tracking-wider">Lote</div>
                                            <div class="mt-1 text-lg font-bold text-gray-900 truncate" title="{{ $lote['identificacao'] }}">{{ $lote['identificacao'] }}</div>
                                        </div>
                                        <a href="{{ route('creche.lotes.show', ['id' => $lote['id']], false) }}"
                                           class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors flex-shrink-0"
                                           title="Ver ficha do lote">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </a>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div class="rounded-xl bg-gray-50 border border-gray-100 px-3 py-2">
                                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Data abertura</div>
                                            <div class="mt-1 font-bold text-gray-900">{{ $lote['data_abertura'] }}</div>
                                        </div>
                                        <div class="rounded-xl bg-gray-50 border border-gray-100 px-3 py-2">
                                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Dias na fase</div>
                                            <div class="mt-1 font-bold text-gray-900">{{ $lote['dias_alojamento'] }}</div>
                                        </div>
                                        <div class="rounded-xl bg-gray-50 border border-gray-100 px-3 py-2 col-span-2">
                                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Qtd. animais</div>
                                            <div class="mt-1 font-bold text-gray-900">{{ $lote['quantidade'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Listagem de Inconsistências -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Inconsistências</h6>
                <div class="text-sm text-gray-500 mt-1">Alertas e pendências identificadas no manejo de creche</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50/50">
                            <th class="px-6 py-3">Lote</th>
                            <th class="px-6 py-3">Data</th>
                            <th class="px-6 py-3">Problema / Descrição</th>
                            <th class="px-6 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($inconsistencias as $inc)
                        <tr class="text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">{{ $inc['lote'] }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $inc['data'] }}</td>
                            <td class="px-6 py-4 font-semibold text-red-600 dark:text-red-400">{{ $inc['problema'] }}</td>
                            <td class="px-6 py-4">
                                <button class="inline-flex items-center px-3 py-2 bg-primary-600 text-white text-xs font-semibold rounded-xl hover:bg-primary-700 transition-colors">
                                    Resolver
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">
                                Nenhuma inconsistência detectada na creche.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div x-show="tab === 'lancamentos'" x-cloak class="space-y-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lançamentos</h6>
                        <div class="text-sm text-gray-500 mt-1">Cadastros operacionais da creche</div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex justify-center items-center gap-2 bg-gray-100 p-1.5 rounded-xl overflow-x-auto max-w-full">
                    <button type="button"
                            @click="subTab = 'lotes'"
                            class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                            :class="subTab === 'lotes' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-layer-group transition-colors duration-300" :class="subTab === 'lotes' ? 'text-primary-600' : 'text-gray-600'"></i>
                        Lotes
                    </button>
                    <button type="button"
                            @click="subTab = 'compras'"
                            class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                            :class="subTab === 'compras' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-cart-shopping transition-colors duration-300" :class="subTab === 'compras' ? 'text-primary-600' : 'text-gray-600'"></i>
                        Compras
                    </button>
                    <button type="button"
                            @click="subTab = 'mortes'"
                            class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                            :class="subTab === 'mortes' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-skull-crossbones transition-colors duration-300" :class="subTab === 'mortes' ? 'text-primary-600' : 'text-gray-600'"></i>
                        Mortes
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div x-show="subTab === 'lotes'" x-cloak class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-sm font-bold text-navy-900 uppercase tracking-wider">Listagem de Lotes</h4>
                        <button type="button" @click="resetLoteForm(); showLoteModal = true" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-bold rounded-card hover:bg-primary-700 transition-colors">
                            <i class="fa-solid fa-plus mr-2"></i> Novo Lote
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50">
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider w-10"></th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Nome</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Características</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Situação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse(($lotesCadastrados ?? []) as $l)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <a href="{{ route('creche.lotes.show', ['id' => $l['id']], false) }}"
                                                   class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors"
                                                   title="Ver ficha do lote">
                                                    <i class="fa-solid fa-file-lines"></i>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $l['nome'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $l['caracteristicas'] ?? '-' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $l['situacao'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Nenhum lote cadastrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div x-show="subTab === 'compras'" x-cloak class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-sm font-bold text-navy-900 uppercase tracking-wider">Listagem de Compras</h4>
                        <button type="button" @click="resetCompraForm(); showCompraModal = true" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-bold rounded-card hover:bg-primary-700 transition-colors">
                            <i class="fa-solid fa-plus mr-2"></i> Nova Compra
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50">
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Data compra</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Lote</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Localização</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Qtd.</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Peso total</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Nasc.</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Fornecedor</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">NF</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse(($compras ?? []) as $c)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $c['data_compra'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $c['lote'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $c['localizacao'] ?: '-' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $c['quantidade'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ number_format((float) $c['peso_total'], 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $c['data_nascimento'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $c['fornecedor'] ?: '-' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $c['nota_fiscal'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma compra registrada.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div x-show="subTab === 'mortes'" x-cloak class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-sm font-bold text-navy-900 uppercase tracking-wider">Listagem de Mortes</h4>
                        <button type="button" @click="resetMorteForm(); showMorteModal = true" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-bold rounded-card hover:bg-primary-700 transition-colors">
                            <i class="fa-solid fa-plus mr-2"></i> Registrar Morte
                        </button>
                    </div>
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50/50">
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Data</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Lote</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Localização</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Qtd.</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Causa</th>
                                        <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Origem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse(($mortes ?? []) as $m)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $m['data_morte'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $m['lote'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $m['localizacao'] ?: '-' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $m['quantidade'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $m['causa'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $m['origem_identificacao'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma morte registrada.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="showLoteModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-[120]">
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full overflow-visible border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="font-bold text-gray-800">Novo Lote</div>
                    <button type="button" @click="showLoteModal = false" class="text-gray-500 hover:text-gray-800">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('creche.lotes.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome *</label>
                        <input name="nome" x-model="loteForm.nome" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Características do lote</label>
                        <textarea name="caracteristicas" x-model="loteForm.caracteristicas" rows="3" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showLoteModal = false" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700">Salvar Lote</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="showCompraModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-[120]">
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full overflow-visible border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="font-bold text-gray-800">Nova Compra</div>
                    <button type="button" @click="showCompraModal = false" class="text-gray-500 hover:text-gray-800">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('creche.compras.store') }}" class="p-6 space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700">Data da compra *</label>
                            <input type="hidden" name="data_compra" :value="compraForm.dataCompraIso">
                            <input type="text"
                                   x-ref="dataCompraInput"
                                   x-model="compraForm.dataCompra"
                                   @focus="openDatePicker('compra_data_compra')"
                                   @click="openDatePicker('compra_data_compra')"
                                   @blur="normalizeCompraDisplay('compra_data_compra')"
                                   class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10"
                                   :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                   inputmode="numeric"
                                   autocomplete="off">
                            <button type="button" class="absolute right-3 top-9 text-gray-500" @click="openDatePicker('compra_data_compra')">
                                <i class="fa-solid fa-calendar-days"></i>
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lote *</label>
                            <select name="lote_id" x-model="compraForm.lote_id" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Selecione</option>
                                @foreach(($lotesCadastrados ?? []) as $l)
                                    <option value="{{ $l['id'] }}">{{ $l['nome'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Localização do lote</label>
                            <input name="localizacao" x-model="compraForm.localizacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade *</label>
                            <input type="number" min="1" name="quantidade" x-model="compraForm.quantidade" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Peso total *</label>
                            <input type="number" step="0.01" min="0" name="peso_total" x-model="compraForm.peso_total" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700">Data de nascimento *</label>
                            <input type="hidden" name="data_nascimento" :value="compraForm.dataNascimentoIso">
                            <input type="text"
                                   x-ref="dataNascimentoInput"
                                   x-model="compraForm.dataNascimento"
                                   @focus="openDatePicker('compra_data_nascimento')"
                                   @click="openDatePicker('compra_data_nascimento')"
                                   @blur="normalizeCompraDisplay('compra_data_nascimento')"
                                   class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10"
                                   :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                   inputmode="numeric"
                                   autocomplete="off">
                            <button type="button" class="absolute right-3 top-9 text-gray-500" @click="openDatePicker('compra_data_nascimento')">
                                <i class="fa-solid fa-calendar-days"></i>
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <div class="text-xs font-bold text-primary-700 uppercase tracking-wider mb-3">Complementares</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Valor da compra</label>
                                <input type="number" step="0.01" min="0" name="valor_compra" x-model="compraForm.valor_compra" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                                <select name="fornecedor_id" x-model="compraForm.fornecedor_id" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione</option>
                                    <template x-for="f in fornecedores" :key="f.id">
                                        <option :value="String(f.id)" x-text="f.nome"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nota fiscal</label>
                                <input name="nota_fiscal" x-model="compraForm.nota_fiscal" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showCompraModal = false" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700">Salvar Compra</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="showMorteModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-[120]">
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full overflow-visible border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="font-bold text-gray-800">Registrar Morte</div>
                    <button type="button" @click="showMorteModal = false" class="text-gray-500 hover:text-gray-800">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('creche.mortes.store') }}" class="p-6 space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lote *</label>
                            <select name="lote_id" x-model="morteForm.lote_id" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Selecione</option>
                                @foreach(($lotesCadastrados ?? []) as $l)
                                    <option value="{{ $l['id'] }}">{{ $l['nome'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Localização do lote</label>
                            <input name="localizacao" x-model="morteForm.localizacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700">Data da morte *</label>
                            <input type="hidden" name="data_morte" :value="morteForm.dataMorteIso">
                            <input type="text"
                                   x-ref="dataMorteInput"
                                   x-model="morteForm.dataMorte"
                                   @focus="openDatePicker('morte_data')"
                                   @click="openDatePicker('morte_data')"
                                   @blur="normalizeMorteDisplay()"
                                   class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10"
                                   :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                   inputmode="numeric"
                                   autocomplete="off">
                            <button type="button" class="absolute right-3 top-9 text-gray-500" @click="openDatePicker('morte_data')">
                                <i class="fa-solid fa-calendar-days"></i>
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade *</label>
                            <input type="number" min="1" name="quantidade" x-model="morteForm.quantidade" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Causa da morte *</label>
                            <input name="causa" x-model="morteForm.causa" list="creche-causas-list" required class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                            <datalist id="creche-causas-list">
                                @foreach(($causas ?? []) as $c)
                                    <option value="{{ $c['nome'] }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Identificação da origem</label>
                            <input name="origem_identificacao" x-model="morteForm.origem_identificacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showMorteModal = false" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700">Salvar Morte</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="activePicker === 'compra_data_compra' || activePicker === 'compra_data_nascimento' || activePicker === 'morte_data'"
             x-cloak
             :style="`top:${pickerTop}px; left:${pickerLeft}px;`"
             :class="pickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
             class="fixed z-[200] bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 -translate-x-1/2 max-h-[calc(100vh-12rem)] overflow-y-auto"
             @click.away="activePicker = null">
            <div class="flex items-center justify-between mb-3">
                <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="text-sm font-semibold text-gray-800">
                    <span x-text="calendarMonths[calendarMonth]"></span> <span x-text="calendarYear"></span>
                </div>
                <button type="button" @click.stop="nextCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                <div class="font-semibold text-gray-500 py-1">D</div>
                <div class="font-semibold text-gray-500 py-1">S</div>
                <div class="font-semibold text-gray-500 py-1">T</div>
                <div class="font-semibold text-gray-500 py-1">Q</div>
                <div class="font-semibold text-gray-500 py-1">Q</div>
                <div class="font-semibold text-gray-500 py-1">S</div>
                <div class="font-semibold text-gray-500 py-1">S</div>
                <template x-for="d in getCalendarDays()" :key="d.date">
                    <button type="button"
                            @click="selectCalendarDate(d.date)"
                            class="aspect-square flex flex-col items-center justify-center rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors"
                            :class="{
                                'opacity-50': !d.isCurrentMonth,
                                'bg-primary-600 text-white border-primary-600': d.isSelected,
                                'text-gray-800': d.isCurrentMonth && !d.isSelected
                            }">
                        <div class="text-sm font-medium" x-text="d.day"></div>
                        <div class="text-[10px]" x-show="calendarType === '1000_dias' && d.isCurrentMonth && d.pigDay !== ''" x-text="d.pigDay"></div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
