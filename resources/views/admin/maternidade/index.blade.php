@extends('layouts.dashboard')

@section('title', 'Maternidade')

@section('content')
<div x-data="{ 
    tab: 'visao-geral', 
    subTab: 'partos',
    showPartoModal: false, 
    showDesmameModal: false,
    showMorteModal: false,
    selectedPartoId: null,
    matrizesAptas: {{ json_encode($matrizesAptas) }},
    matrizPartoSearch: '',
    femeasLactantes: {{ json_encode($femeasLactantesFull) }},
    morteCausas: {{ json_encode($morteCausas) }},
    criteriosLoaded: false,
    calendarType: 'gregoriano',
    activePicker: null,
    calendarMonth: new Date().getMonth(),
    calendarYear: new Date().getFullYear(),
    calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    partoPickerTop: 0,
    partoPickerLeft: 0,
    partoPickerDirection: 'down',
    partoForm: {
        femea_id: '',
        cobertura_id: '',
        dataIso: '{{ date('Y-m-d') }}',
        data: ''
    },
    morteForm: {
        femea_id: '',
        parto_id: '',
        quantidade: 1,
        disponiveis: 0,
        data: '{{ date('Y-m-d') }}',
        nova_causa_nome: ''
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
    setPartoDateIso(iso) {
        const v = String(iso || '').trim();
        this.partoForm.dataIso = v;
        if (!v) {
            this.partoForm.data = '';
            return;
        }
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.partoForm.data = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.partoForm.data = this.isoToBr(v);
        }
    },
    get matrizesAptasFiltradas() {
        const q = String(this.matrizPartoSearch || '').trim().toLowerCase();
        if (!q) return this.matrizesAptas;
        return this.matrizesAptas.filter((f) => String(f?.id_primaria || '').toLowerCase().includes(q));
    },
    selecionarMatrizPartoPorIdPrimaria(strict = false) {
        const q = String(this.matrizPartoSearch || '').trim().toLowerCase();
        if (!q) {
            this.partoForm.femea_id = '';
            this.partoForm.cobertura_id = '';
            this.setPartoDateIso('{{ date('Y-m-d') }}');
            return;
        }

        const exact = this.matrizesAptas.find((f) => String(f?.id_primaria || '').toLowerCase() === q);
        if (strict) {
            if (!exact) {
                this.partoForm.femea_id = '';
                this.partoForm.cobertura_id = '';
                return;
            }
            this.partoForm.femea_id = String(exact.id);
            this.matrizPartoSearch = String(exact.id_primaria || this.matrizPartoSearch);
            this.updatePartoPrevisao(String(exact.id));
            return;
        }

        const filtered = this.matrizesAptasFiltradas;
        const match = exact || (filtered.length > 0 ? filtered[0] : null);
        if (!match) {
            this.partoForm.femea_id = '';
            this.partoForm.cobertura_id = '';
            alert('Nenhuma matriz encontrada.');
            return;
        }

        this.partoForm.femea_id = String(match.id);
        this.matrizPartoSearch = String(match.id_primaria || this.matrizPartoSearch);
        this.updatePartoPrevisao(String(match.id));
    },
    updatePartoPrevisao(femeaId) {
        const selected = this.matrizesAptas.find(m => String(m.id) === String(femeaId));
        if (selected) {
            this.setPartoDateIso(selected.previsao_parto);
            this.partoForm.cobertura_id = selected.cobertura_id;
        } else {
            this.setPartoDateIso('{{ date('Y-m-d') }}');
            this.partoForm.cobertura_id = '';
        }
    },
    getPickerSelectedIso() {
        if (this.activePicker === 'parto') return String(this.partoForm.dataIso || '');
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
    openPartoDatePicker() {
        const iso = String(this.partoForm.dataIso || '').trim();
        const base = iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? new Date(iso + 'T12:00:00') : new Date();
        this.calendarMonth = base.getMonth();
        this.calendarYear = base.getFullYear();
        this.activePicker = 'parto';
        this.$nextTick(() => {
            const el = this.$refs && this.$refs.partoDateInput ? this.$refs.partoDateInput : null;
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

            this.partoPickerLeft = left;
            this.partoPickerTop = top;
            this.partoPickerDirection = direction;
        });
    },
    selectCalendarDate(dateStr) {
        const iso = String(dateStr || '').trim();
        const m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) return;
        this.setPartoDateIso(iso);
        this.activePicker = null;
    },
    handlePartoSubmit(e) {
        this.selecionarMatrizPartoPorIdPrimaria(true);
        if (!this.partoForm.femea_id) {
            e.preventDefault();
            alert('Selecione a matriz.');
            return;
        }
        if (!this.partoForm.dataIso) {
            e.preventDefault();
            alert('Informe a data do parto.');
            return;
        }
    },
    init() {
        this.setPartoDateIso(this.partoForm.dataIso);
        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                this.calendarType = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '') ? 'gregoriano' : String(items.criterio_calendario_tipo);
                this.setPartoDateIso(this.partoForm.dataIso);
                this.criteriosLoaded = true;
            })
            .catch(() => { this.criteriosLoaded = true; });
    },

    updateMorteInfo(femeaId) {
        const selected = this.femeasLactantes.find(f => f.id == femeaId);
        if (selected) {
            this.morteForm.parto_id = selected.parto_id;
            this.morteForm.disponiveis = selected.disponiveis;
        } else {
            this.morteForm.parto_id = '';
            this.morteForm.disponiveis = 0;
        }
    },

    async adicionarCausa() {
        if(!this.morteForm.nova_causa_nome) return;
        try {
            const res = await fetch('{{ route('maternidade.causas.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ nome: this.morteForm.nova_causa_nome })
            });
            const data = await res.json();
            this.morteCausas.push(data);
            this.morteForm.causa_id = data.id;
            this.morteForm.nova_causa_nome = '';
            alert('Causa cadastrada com sucesso!');
        } catch(e) { console.error(e); }
    }
}" x-init="init()">
    <!-- Header & Topbar -->
    <div>
        <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Maternidade</h2>
                <p class="text-sm text-white">Visão geral e lançamentos operacionais</p>
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

    <div x-show="tab === 'visao-geral'" x-cloak class="space-y-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Fêmeas Lactantes</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ $femeasLactantes }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-baby-carriage text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-person-breastfeeding mr-2"></i>
                    <span class="font-medium">Em lactação</span>
                </div>
            </div>

            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Mães de Leite</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ $maesLeite }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-heart mr-2"></i>
                    <span class="font-medium">Suporte à leitegada</span>
                </div>
            </div>

            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Matrizes Aptas</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ is_countable($matrizesAptas) ? count($matrizesAptas) : 0 }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-clipboard-check text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-calendar-day mr-2"></i>
                    <span class="font-medium">Para parto</span>
                </div>
            </div>

            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Inconsistências</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ is_countable($inconsistencias) ? count($inconsistencias) : 0 }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                        <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>
                    <span class="font-medium">Partos com alerta</span>
                </div>
            </div>
        </div>

        <!-- Inconsistências -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Inconsistências da Maternidade</h6>
                    <div class="text-sm text-gray-500 mt-1">Partos com problemas identificados no sistema</div>
                </div>
                
                <!-- Tooltip Informativo -->
                <div class="relative group">
                    <i class="fa-solid fa-circle-info text-primary-500 cursor-help hover:text-primary-600 transition-colors text-base"></i>
                    <div class="absolute z-50 left-1/2 mt-2 w-80 p-4 bg-gray-900 text-white text-[10px] rounded-xl shadow-2xl opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 transform -translate-x-1/2">
                        <div class="space-y-3">
                            <div>
                                <strong class="text-primary-400 block mb-1 uppercase tracking-tighter text-[11px]">Leitões com idade elevada</strong>
                                <span class="text-gray-300">Identifica partos ativos onde os leitões já ultrapassaram o período máximo de lactação e ainda não foram desmamados.</span>
                            </div>
                        </div>
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-b-gray-900"></div>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50/50">
                            <th class="px-6 py-3">Fêmea</th>
                            <th class="px-6 py-3">Lote</th>
                            <th class="px-6 py-3">Localização</th>
                            <th class="px-6 py-3">Idade Leitões</th>
                            <th class="px-6 py-3">Previsão Desmame</th>
                            <th class="px-6 py-3">Problema</th>
                            <th class="px-6 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($inconsistencias as $inc)
                        <tr class="text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">{{ $inc['femea'] }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $inc['lote'] }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $inc['localizacao'] }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $inc['idade_leitoes'] }} dias</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $inc['previsao_desmame'] }}</td>
                            <td class="px-6 py-4 font-semibold text-red-600 dark:text-red-400">{{ $inc['problema'] }}</td>
                            <td class="px-6 py-4">
                                <button @click="selectedPartoId = {{ $inc['parto_id'] }}; showDesmameModal = true" 
                                    class="inline-flex items-center px-3 py-2 bg-primary-600 text-white text-xs font-semibold rounded-xl hover:bg-primary-700 transition-colors">
                                    Cadastrar Desmame
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">
                                Nenhuma inconsistência detectada na maternidade.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Conteúdo: Lançamentos -->
    <div x-show="tab === 'lancamentos'" class="space-y-6">
        <!-- Navegação de Sub-abas -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
            <div class="flex justify-center items-center gap-2 bg-gray-100 dark:bg-gray-800 p-1.5 rounded-xl overflow-x-auto max-w-full">
                <button type="button"
                        @click="subTab = 'partos'"
                        class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                        :class="subTab === 'partos' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 dark:text-gray-200 hover:text-gray-800 dark:hover:text-white hover:bg-white/80 dark:hover:bg-gray-900/60'">
                    <i class="fa-solid fa-baby text-primary-600 transition-colors duration-300" :class="subTab === 'partos' ? 'text-primary-600' : 'text-gray-600 dark:text-gray-300'"></i>
                    Partos
                </button>
                <button type="button"
                        @click="subTab = 'desmames'"
                        class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                        :class="subTab === 'desmames' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 dark:text-gray-200 hover:text-gray-800 dark:hover:text-white hover:bg-white/80 dark:hover:bg-gray-900/60'">
                    <i class="fa-solid fa-arrow-up-right-dots text-primary-600 transition-colors duration-300" :class="subTab === 'desmames' ? 'text-primary-600' : 'text-gray-600 dark:text-gray-300'"></i>
                    Desmames
                </button>
                <button type="button"
                        @click="subTab = 'mortes'"
                        class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                        :class="subTab === 'mortes' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 dark:text-gray-200 hover:text-gray-800 dark:hover:text-white hover:bg-white/80 dark:hover:bg-gray-900/60'">
                    <i class="fa-solid fa-skull-crossbones text-primary-600 transition-colors duration-300" :class="subTab === 'mortes' ? 'text-primary-600' : 'text-gray-600 dark:text-gray-300'"></i>
                    Morte de Leitão
                </button>
            </div>
        </div>

        <!-- Conteúdo Sub-aba Partos -->
        <div x-show="subTab === 'partos'" class="space-y-4">
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-bold text-navy-900 uppercase tracking-wider">Listagem de Partos</h4>
                <button @click="showPartoModal = true" 
                    class="inline-flex items-center px-4 py-2 bg-primary-500 text-white text-sm font-bold rounded-card hover:bg-primary-600 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i> Novo Parto
                </button>
            </div>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Fêmea</th>
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Lote</th>
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Vivos</th>
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Mortos</th>
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Mumif.</th>
                                <th class="px-6 py-3 text-xs font-bold text-text-secondary uppercase tracking-wider">Observação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($partosRegistrados as $parto)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-navy-900">{{ \Carbon\Carbon::parse($parto->data)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-navy-900">
                                    {{ (string) $parto->id_primaria . ($parto->id_secundaria ? " ({$parto->id_secundaria})" : "") }}
                                </td>
                                <td class="px-6 py-4 text-sm text-text-secondary">{{ $parto->lote ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-success-text">{{ $parto->total_vivos }}</td>
                                <td class="px-6 py-4 text-sm text-danger-text">{{ $parto->total_mortos }}</td>
                                <td class="px-6 py-4 text-sm text-primary-600">{{ $parto->total_mumificados }}</td>
                                <td class="px-6 py-4 text-sm text-text-secondary max-w-xs truncate" title="{{ $parto->observacao }}">
                                    {{ $parto->observacao ?: '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-text-muted italic">
                                    Nenhum parto registrado recentemente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Conteúdo Sub-aba Desmames -->
        <div x-show="subTab === 'desmames'" class="card p-8 text-center">
            <i class="fa-solid fa-hourglass-half text-text-muted text-4xl mb-4"></i>
            <p class="text-text-secondary text-sm italic">Módulo de listagem de desmames em desenvolvimento.</p>
        </div>

        <!-- Conteúdo Sub-aba Mortes -->
        <div x-show="subTab === 'mortes'" class="space-y-4">
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-bold text-navy-900 uppercase tracking-wider">Mortes de Leitões</h4>
                <button @click="showMorteModal = true" 
                    class="inline-flex items-center px-4 py-2 bg-danger-text text-white text-sm font-bold rounded-card hover:bg-red-700 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i> Nova Morte
                </button>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fêmea</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Quant.</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Causa</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Funcionário</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($mortesLeitaoRegistradas as $morte)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-navy-900">
                                    {{ \Carbon\Carbon::parse($morte->data)->format('d/m/Y') }} {{ $morte->hora ? \Carbon\Carbon::parse($morte->hora)->format('H:i') : '' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-navy-900">
                                    {{ (string) $morte->id_primaria . ($morte->id_secundaria ? " ({$morte->id_secundaria})" : "") }}
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-danger-text">{{ $morte->quantidade }}</td>
                                <td class="px-6 py-4 text-sm text-text-secondary">{{ $morte->causa_nome ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-text-secondary">{{ $morte->funcionario ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-text-muted italic">
                                    Nenhuma morte registrada recentemente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Morte de Leitão -->
    <div x-show="showMorteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showMorteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full overflow-visible border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Morte de Leitão</h3>
                    <button @click="showMorteModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('maternidade.mortes.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="parto_id" x-model="morteForm.parto_id">
                    
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dados da Matriz</label>
                        <select name="femea_id" required @change="updateMorteInfo($event.target.value)" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                            <option value="">Selecione a fêmea</option>
                            <template x-for="f in femeasLactantes" :key="f.id">
                                <option :value="f.id" x-text="f.identificacao"></option>
                            </template>
                        </select>
                        <p class="mt-2 text-xs text-primary-600 font-bold" x-show="morteForm.parto_id">
                            Leitões disponíveis: <span x-text="morteForm.disponiveis"></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Morte</label>
                            <input type="date" name="data" required x-model="morteForm.data" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora Morte</label>
                            <input type="time" name="hora" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                            <input type="number" name="quantidade" required min="1" :max="morteForm.disponiveis" x-model="morteForm.quantidade" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Funcionário</label>
                            <input type="text" name="funcionario" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Nome...">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Causa da Morte</label>
                        <div class="flex gap-2">
                            <select name="causa_id" x-model="morteForm.causa_id" class="flex-1 rounded-lg border-gray-300 text-sm">
                                <option value="">Selecione uma causa</option>
                                <template x-for="c in morteCausas" :key="c.id">
                                    <option :value="c.id" x-text="c.nome"></option>
                                </template>
                            </select>
                            <input type="text" x-model="morteForm.nova_causa_nome" placeholder="Nova causa..." class="w-32 rounded-lg border-gray-300 text-sm">
                            <button type="button" @click="adicionarCausa()" class="p-2 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showMorteModal = false" class="px-4 py-2 text-sm font-medium text-gray-500">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-lg">Salvar Morte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Parto -->
    <div x-show="showPartoModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showPartoModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Parto</h3>
                    <button @click="showPartoModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('maternidade.partos.store') }}" method="POST" class="p-6 space-y-4 text-gray-800 dark:text-gray-200" @submit="handlePartoSubmit($event)">
                    @csrf
                    <input type="hidden" name="cobertura_id" x-model="partoForm.cobertura_id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fêmea (Matriz) *</label>
                        <input type="text"
                               x-model="matrizPartoSearch"
                               list="matrizes-parto-list"
                               @keydown.enter.prevent="selecionarMatrizPartoPorIdPrimaria(false)"
                               @change="selecionarMatrizPartoPorIdPrimaria(true)"
                               class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500"
                               placeholder="Digite o ID primária e pressione Enter"
                               autocomplete="off">
                        <datalist id="matrizes-parto-list">
                            <template x-for="f in matrizesAptasFiltradas.slice(0, 50)" :key="`mp-${f.id}`">
                                <option :value="f.id_primaria" x-text="f.id_secundaria ? `${f.id_primaria} / ${f.id_secundaria}` : f.id_primaria"></option>
                            </template>
                        </datalist>
                        <input type="hidden" name="femea_id" x-model="partoForm.femea_id">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Lote</label>
                        <input type="text" name="lote" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm" placeholder="Opcional...">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Data do Parto *</label>
                            <input type="hidden" name="data" :value="partoForm.dataIso">
                            <div class="mt-1 relative">
                                <input type="text"
                                       x-model="partoForm.data"
                                       x-ref="partoDateInput"
                                       @click="openPartoDatePicker()"
                                       class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 pr-10"
                                       :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       readonly>
                                <button type="button" @click="openPartoDatePicker()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fa-solid fa-calendar"></i>
                                </button>

                                <div x-show="activePicker === 'parto'"
                                     x-cloak
                                     :style="`top:${partoPickerTop}px; left:${partoPickerLeft}px;`"
                                     :class="partoPickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
                                     class="fixed z-[200] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 -translate-x-1/2 max-h-[calc(100vh-12rem)] overflow-y-auto"
                                     @click.away="activePicker = null">
                                    <div class="flex items-center justify-between mb-3">
                                        <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>
                                        <span class="font-medium text-gray-900 dark:text-gray-100" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
                                        <button type="button" @click.stop="nextCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                                        <template x-for="day in ['D','S','T','Q','Q','S','S']">
                                            <div class="font-medium text-gray-500 dark:text-gray-400 py-1" x-text="day"></div>
                                        </template>
                                    </div>

                                    <div class="grid grid-cols-7 gap-1">
                                        <template x-for="day in getCalendarDays()" :key="day.date">
                                            <div class="text-center">
                                                <button type="button"
                                                        @click.stop="selectCalendarDate(day.date)"
                                                        :class="day.isSelected ? 'bg-primary-600 text-white' : (day.isCurrentMonth ? 'text-gray-900 dark:text-gray-100 hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400')"
                                                        :disabled="!day.isCurrentMonth"
                                                        class="p-2 text-sm rounded-lg transition-colors w-full">
                                                    <span x-text="day.day"></span>
                                                </button>
                                                <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Hora Início</label>
                            <input type="time" name="hora_inicio" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Vivos</label>
                            <input type="number" name="total_vivos" value="0" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Mortos</label>
                            <input type="number" name="total_mortos" value="0" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Mumificados</label>
                            <input type="number" name="total_mumificados" value="0" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showPartoModal = false" class="px-4 py-2 text-sm font-medium text-gray-500">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Salvar Parto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Desmame -->
    <div x-show="showDesmameModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showDesmameModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Desmame</h3>
                    <button @click="showDesmameModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('maternidade.desmames.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="parto_id" :value="selectedPartoId">

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showDesmameModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Salvar Desmame</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
