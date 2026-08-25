@extends('layouts.dashboard')

@section('title', 'Terminação')
@section('page_title', '')

@section('content')
<div x-data="{
    tab: 'lancamentos',
    subTab: 'lotes',

    // -------------------------------------------------------------------------
    // Calendário PIG (padrão creche.blade.php): 14 campos de data em 7 modais
    // Cada campo tem 2 estados:  Display (DD/MM/AAAA OU 3 dígitos PIG) +  Iso (YYYY-MM-DD, vai p/ hidden submit)
    // -------------------------------------------------------------------------
    criteriosLoaded: false,
    calendarType: {{ json_encode($calendarioTipo ?? PigCycleService::CALENDAR_1000_DAYS, JSON_UNESCAPED_UNICODE) }},
    activePicker: null,
    calendarMonth: new Date().getMonth(),
    calendarYear: new Date().getFullYear(),
    calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    pickerTop: 0,
    pickerLeft: 0,
    pickerDirection: 'down',

    HOJE_ISO: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},

    // 14 campos (Display + Iso):
    loteDataEntradaIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    loteDataEntrada: '',
    entradaDataEntradaIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    entradaDataEntrada: '',
    entradaDataNascimentoIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    entradaDataNascimento: '',
    morteDataIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    morteData: '',
    transfDataIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    transfData: '',
    vendaDataIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    vendaData: '',
    pesoDataIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    pesoData: '',
    crecheDataEntradaIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    crecheDataEntrada: '',
    crecheDataNascimentoIso: {{ json_encode($hojeIso ?? now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},
    crecheDataNascimento: '',

    // Mapa picker_id (qual campo o usuário clicou) → (setIsoFn, setDisplayFromIso, normalizeBlurFn, ref)
    pickerConfig: {
        'lote_data_entrada':      { isoKey: 'loteDataEntradaIso',      displayKey: 'loteDataEntrada',      refKey: 'refLoteDataEntrada' },
        'entrada_data_entrada':   { isoKey: 'entradaDataEntradaIso',   displayKey: 'entradaDataEntrada',   refKey: 'refEntradaDataEntrada' },
        'entrada_data_nascimento':{ isoKey: 'entradaDataNascimentoIso',displayKey: 'entradaDataNascimento',refKey: 'refEntradaDataNascimento' },
        'morte_data':             { isoKey: 'morteDataIso',            displayKey: 'morteData',            refKey: 'refMorteData' },
        'transf_data':            { isoKey: 'transfDataIso',           displayKey: 'transfData',           refKey: 'refTransfData' },
        'venda_data':             { isoKey: 'vendaDataIso',            displayKey: 'vendaData',            refKey: 'refVendaData' },
        'peso_data':              { isoKey: 'pesoDataIso',             displayKey: 'pesoData',             refKey: 'refPesoData' },
        'creche_data_entrada':    { isoKey: 'crecheDataEntradaIso',    displayKey: 'crecheDataEntrada',    refKey: 'refCrecheDataEntrada' },
        'creche_data_nascimento': { isoKey: 'crecheDataNascimentoIso', displayKey: 'crecheDataNascimento', refKey: 'refCrecheDataNascimento' },
    },

    // -------------------------------------------------------------------------
    // Helpers de data (padrão Creche)
    // -------------------------------------------------------------------------
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
        let d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
        if (d < 1 || d > 31 || mo < 1 || mo > 12) return null;
        const pad = (n) => String(n).padStart(2, '0');
        const dt = new Date(y, mo - 1, d);
        if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return null;
        return `${y}-${pad(mo)}-${pad(d)}`;
    },

    // Seta ISO e gera display (PIG 3 dígitos OU dd/mm/aaaa) conforme calendarType
    setIsoAndDisplay(isoKey, displayKey, iso) {
        const v = String(iso || '').trim();
        this[isoKey] = v;
        if (!v) { this[displayKey] = ''; return; }
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this[displayKey] = String(toPigDay(v + 'T00:00:00'));
        } else {
            this[displayKey] = this.isoToBr(v);
        }
    },

    // Converte display digitado (PIG ou BR) → ISO, e reescreve display formatado
    normalizeDisplay(isoKey, displayKey) {
        const isPig = this.calendarType === '1000_dias';
        const raw = String(this[displayKey] || '').trim();
        if (!raw) { this.setIsoAndDisplay(isoKey, displayKey, ''); return; }
        let iso = null;
        if (isPig && /^\d{1,4}$/.test(raw) && typeof pigDayToDate === 'function') iso = pigDayToDate(raw);
        if (!iso) iso = this.brToIso(raw);
        if (iso) this.setIsoAndDisplay(isoKey, displayKey, iso);
    },

    // Pickers / calendário inline (padrão Creche)
    getPickerSelectedIso() {
        const cfg = this.pickerConfig[this.activePicker];
        if (!cfg) return '';
        return String(this[cfg.isoKey] || '');
    },
    prevCalendarMonth() {
        if (this.calendarMonth === 0) { this.calendarMonth = 11; this.calendarYear--; }
        else this.calendarMonth--;
    },
    nextCalendarMonth() {
        if (this.calendarMonth === 11) { this.calendarMonth = 0; this.calendarYear++; }
        else this.calendarMonth++;
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
    openDatePicker(which, $refsScope) {
        const cfg = this.pickerConfig[which];
        if (!cfg) return;
        const iso = String(this[cfg.isoKey] || '').trim();
        const ref = $refsScope && $refsScope[cfg.refKey] ? $refsScope[cfg.refKey] : (this.$refs && this.$refs[cfg.refKey] ? this.$refs[cfg.refKey] : null);

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
        const cfg = this.pickerConfig[this.activePicker];
        if (!cfg) return;
        const iso = String(dateStr || '').trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return;
        this.setIsoAndDisplay(cfg.isoKey, cfg.displayKey, iso);
        this.activePicker = null;
    },

    // Resets rápidos quando modal abre (se precisar; opcional)
    resetAllDatasToToday() {
        Object.entries(this.pickerConfig).forEach(([k, cfg]) => {
            this.setIsoAndDisplay(cfg.isoKey, cfg.displayKey, this.HOJE_ISO);
        });
    },

    init() {
        // Inicializa displays de todos os 14 campos
        this.resetAllDatasToToday();
        // Recarrega calendarType de /api/criterios (igual Creche) para compatibilidade máxima
        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                const tipo = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '')
                    ? {{ json_encode($calendarioTipo ?? PigCycleService::CALENDAR_1000_DAYS, JSON_UNESCAPED_UNICODE) }}
                    : String(items.criterio_calendario_tipo);
                this.calendarType = tipo;
                this.resetAllDatasToToday();
                this.criteriosLoaded = true;
            })
            .catch(() => { this.criteriosLoaded = true; });
    },

    // Estado do módulo Terminação (tabs, filtros, dados)
    filtroSituacao: 'aberto',
    lotes: {{ json_encode($lotes ?? [], JSON_UNESCAPED_UNICODE) }},
    lotesCadastrados: {{ json_encode($lotesCadastrados ?? [], JSON_UNESCAPED_UNICODE) }},
    crecheLotes: {{ json_encode($crecheLotes ?? [], JSON_UNESCAPED_UNICODE) }},
    inconsistenciaDismiss: {},
    toggleInconsistencia(i) { this.inconsistenciaDismiss[i] = !this.inconsistenciaDismiss[i]; },
}" x-init="init()" class="space-y-6">

    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <div class="flex items-center justify-center gap-2 mb-1">
                <i class="fa-solid fa-cow text-amber-200"></i>
                <h2 class="text-2xl font-bold text-white mb-2">Terminação</h2>
            </div>
            <p class="text-sm text-white">Manejo de suínos em fase de engorda / acabamento · Meta: {{ (int)($metaDias ?? 90) }} dias · Peso alvo: {{ number_format((float)($metaPesoAbate ?? 115), 1, ',', '.') }} kg</p>
        </div>
        <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
            <button type="button" @click="tab = 'visao'"
                :class="tab === 'visao' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                <i class="fa-solid fa-eye mr-1 opacity-80"></i> Visão Geral
            </button>
            <button type="button" @click="tab = 'lancamentos'"
                :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                <i class="fa-solid fa-pen-to-square mr-1 opacity-80"></i> Lançamentos
            </button>
            <button type="button" @click="tab = 'analises'"
                :class="tab === 'analises' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                <i class="fa-solid fa-chart-simple mr-1 opacity-80"></i> Análise
            </button>
        </nav>
    </div>

    <div x-show="tab === 'visao'" x-cloak x-transition class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Lotes Abertos</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ (int)($stats['lotes_abertos'] ?? 0) }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-all duration-200">
                        <i class="fa-solid fa-layer-group text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-eye mr-2"></i>
                    <span class="font-medium">Clique em Ficha para detalhar</span>
                </div>
            </div>
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Estoque Animais</div>
                        <div class="text-xl font-bold text-emerald-700 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ (int)($stats['estoque_animais'] ?? 0) }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-all duration-200">
                        <i class="fa-solid fa-piggy-bank text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-warehouse mr-2"></i>
                    <span class="font-medium">Total em todos os lotes abertos</span>
                </div>
            </div>
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Mortalidade</div>
                        <div class="text-xl font-bold {{ (float)($stats['mortalidade_taxa'] ?? 0) > (float)($metaMortalidade ?? 3) ? 'text-rose-600' : 'text-gray-800' }} tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ number_format((float)($stats['mortalidade_taxa'] ?? 0), 2, ',', '.') }}%
                        </div>
                    </div>
                    <div class="p-2 {{ (float)($stats['mortalidade_taxa'] ?? 0) > (float)($metaMortalidade ?? 3) ? 'bg-rose-50 text-rose-600 group-hover:bg-rose-600 group-hover:text-white' : 'bg-primary-50 text-primary-500 group-hover:bg-primary-500 group-hover:text-white' }} rounded-full transition-all duration-200">
                        <i class="fa-solid fa-percent text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                    <div class="flex items-center">
                        <i class="fa-solid fa-bullseye mr-2"></i>
                        <span class="font-medium">Meta</span>
                    </div>
                    <span class="font-semibold {{ (float)($stats['mortalidade_taxa'] ?? 0) > (float)($metaMortalidade ?? 3) ? 'text-rose-600' : 'text-gray-800' }}">≤ {{ number_format((float)($metaMortalidade ?? 3), 1, ',', '.') }}%</span>
                </div>
            </div>
            <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Vendidos · 30 dias</div>
                        <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                            {{ (int)($stats['vendidos_periodo'] ?? 0) }}
                        </div>
                    </div>
                    <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-all duration-200">
                        <i class="fa-solid fa-truck-fast text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <i class="fa-solid fa-dolly mr-2"></i>
                    <span class="font-medium">Cabeças enviadas ao abate</span>
                </div>
            </div>
        </div>

        @if(!empty($inconsistencias))
            <div class="bg-gradient-to-r from-rose-50 via-orange-50 to-amber-50 rounded-2xl border border-rose-200/60 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation animate-pulse"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-rose-900">
                            {{ count($inconsistencias) }} inconsistência(s) na terminação
                        </div>
                        <div class="text-xs text-rose-700/80 mt-0.5">Resolva antes do próximo fechamento de lote.</div>
                    </div>
                    <button @click="toggleInconsistencia('all')" class="text-xs font-semibold text-rose-700 hover:underline px-3 py-1.5 rounded-lg bg-white/80 border border-rose-200 whitespace-nowrap">
                        <i class="fa-solid fa-eye-slash mr-1"></i> Ocultar
                    </button>
                </div>
                <ul class="space-y-2">
                    @foreach($inconsistencias as $i => $inc)
                        <li x-show="!inconsistenciaDismiss[{{$i}}]" x-transition class="bg-white/70 backdrop-blur-sm rounded-xl p-4 border border-white shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5
                                    {{ ($inc['severidade'] ?? '') === 'danger' ? 'bg-rose-100 text-rose-700' : (($inc['severidade'] ?? '') === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700') }}">
                                    <i class="fa-solid {{ ($inc['severidade'] ?? '') === 'danger' ? 'fa-bomb' : (($inc['severidade'] ?? '') === 'warning' ? 'fa-circle-exclamation' : 'fa-circle-info') }} text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-900">{{ $inc['titulo'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 mt-0.5">{{ $inc['detalhe'] ?? '' }}</div>
                                    <div class="text-xs text-emerald-700 mt-1 font-semibold">
                                        <i class="fa-solid fa-check-circle mr-0.5"></i> Ação: {{ $inc['acao'] ?? '' }}
                                    </div>
                                </div>
                                @if(!empty($inc['lote_id']))
                                    <a href="{{ route('terminacao.lotes.show', $inc['lote_id'] ?? 0) }}" class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Lote
                                    </a>
                                @endif
                                <button @click="toggleInconsistencia({{$i}})" class="text-gray-400 hover:text-gray-600 flex-shrink-0 ml-1">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h6 class="font-bold text-amber-700 uppercase text-xs tracking-wider">Lotes Abertos</h6>
                    <div class="text-sm text-gray-500 mt-1">{{ count($lotes ?? []) }} lote(s) em andamento</div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button @click="$dispatch('open-modal', 'modal-transf-creche')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold shadow-sm transition">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i> Transferir da Creche
                    </button>
                    <button @click="$dispatch('open-modal', 'modal-novo-lote')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold shadow-sm transition">
                        <i class="fa-solid fa-plus text-xs"></i> Novo Lote
                    </button>
                </div>
            </div>
            <div class="p-5">
                @if(empty($lotes))
                    <div class="text-center py-14 text-gray-400">
                        <i class="fa-solid fa-box-open text-5xl mb-3 opacity-30"></i>
                        <div class="font-semibold text-gray-600 mb-1">Nenhum lote de terminação criado ainda</div>
                        <div class="text-sm">Clique em "Novo Lote" ou use "Transferir da Creche" para importar um lote existente.</div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($lotes as $lote)
                            <div class="group relative rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 hover:shadow-lg hover:border-amber-300 transition-all duration-300">
                                <div class="absolute top-3 right-3 flex gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-100 text-amber-800">
                                        {{ $lote['tag'] ?? 'TERMINAÇÃO' }}
                                    </span>
                                    @if(($lote['progresso_pct'] ?? 0) >= 100)
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-flag-checkered mr-0.5"></i> Pronto
                                        </span>
                                    @endif
                                </div>
                                <div class="min-h-[72px]">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                                            <i class="fa-solid fa-cow text-xs"></i>
                                        </div>
                                        <div>
                                            <div class="font-black text-gray-900 tracking-tight">{{ $lote['identificacao'] ?? '' }}</div>
                                            <div class="text-[11px] text-gray-500">Desde {{ $lote['data_abertura'] ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 mt-3 text-sm">
                                        <div>
                                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Saldo</div>
                                            <div class="text-xl font-black text-emerald-700">{{ (int)($lote['quantidade'] ?? 0) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Dias</div>
                                            <div class="text-xl font-black text-gray-800">{{ (int)($lote['dias_alojamento'] ?? 0) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Peso</div>
                                            <div class="text-xl font-black text-amber-700">
                                                @if(!empty($lote['ultimo_peso_kg']))
                                                    {{ number_format((float)$lote['ultimo_peso_kg'], 1, ',', '.') }}
                                                    <span class="text-[10px] text-gray-400 font-normal">kg</span>
                                                @else <span class="text-gray-300 font-semibold">-</span> @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div>
                                        <div class="flex justify-between text-[11px] font-semibold text-gray-600 mb-1">
                                            <span>Ciclo de {{ (int)($lote['meta_dias'] ?? 90) }} dias</span>
                                            <span class="{{ ($lote['progresso_pct'] ?? 0) >= 100 ? 'text-emerald-700' : 'text-amber-700' }}">{{ (int)($lote['progresso_pct'] ?? 0) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full bg-gradient-to-r from-amber-400 via-orange-500 to-amber-600 transition-all duration-700" style="width: {{ min(100, (int)($lote['progresso_pct'] ?? 0)) }}%"></div>
                                        </div>
                                    </div>
                                    @if(!empty($lote['localizacao']))
                                        <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                            <i class="fa-solid fa-location-dot text-amber-500"></i>
                                            {{ $lote['localizacao'] }}
                                        </div>
                                    @endif
                                    @if(($lote['mortalidade_pct'] ?? 0) > (float)($metaMortalidade ?? 3))
                                        <div class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-1 rounded-lg">
                                            <i class="fa-solid fa-skull"></i> Mortalidade {{ number_format((float)$lote['mortalidade_pct'], 1, ',', '.') }}%
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-4 flex gap-2 pt-3 border-t border-gray-100">
                                    <a href="{{ route('terminacao.lotes.show', $lote['id'] ?? 0) }}" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition">
                                        <i class="fa-solid fa-id-card"></i> Ficha Completa
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div x-show="tab === 'lancamentos'" x-cloak x-transition class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex justify-center items-center gap-2 bg-gray-100 p-1.5 rounded-xl overflow-x-auto max-w-full">
                    @php
                        $abas = [
                            'lotes' => ['icon' => 'fa-layer-group', 'label' => 'Lotes', 'count' => count($lotesCadastrados ?? [])],
                            'entradas' => ['icon' => 'fa-arrow-down', 'label' => 'Entradas', 'count' => count($entradas ?? [])],
                            'mortes' => ['icon' => 'fa-skull', 'label' => 'Mortes', 'count' => count($mortes ?? [])],
                            'transferencias' => ['icon' => 'fa-right-left', 'label' => 'Transferências', 'count' => count($transferencias ?? [])],
                            'vendas' => ['icon' => 'fa-truck', 'label' => 'Vendas / Abate', 'count' => count($vendas ?? [])],
                            'pesos' => ['icon' => 'fa-scale-balanced', 'label' => 'Pesos', 'count' => count($pesos ?? [])],
                        ];
                    @endphp
                    @foreach($abas as $key => $aba)
                        <button type="button" @click="subTab = '{{$key}}'"
                            class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
                            :class="subTab === '{{$key}}'
                                ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'">
                            <i class="fa-solid {{$aba['icon']}} transition-colors duration-300" :class="subTab === '{{$key}}' ? 'text-primary-600' : 'text-gray-600'"></i>
                            {{$aba['label']}}
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-bold tracking-tight
                                {{ $aba['count'] > 0 ? 'bg-gray-200 text-gray-700' : 'bg-gray-100 text-gray-400' }}"
                                :class="(subTab === '{{$key}}') && ({{ (int)($aba['count'] > 0) }}) ? '!bg-primary-50 !text-primary-700' : ''">
                                {{ $aba['count'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="p-5 space-y-4">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" placeholder="Buscar..."
                                class="pl-9 pr-4 py-2 text-sm border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 shadow-sm w-64 max-w-full transition">
                        </div>
                        <select class="px-3 py-2 text-sm border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 shadow-sm">
                            <option>Situação: Todos</option>
                            <option>Aberto</option>
                            <option>Fechado</option>
                        </select>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <template x-if="subTab === 'lotes'">
                            <button @click="$dispatch('open-modal', 'modal-novo-lote')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold shadow-sm transition">
                                <i class="fa-solid fa-plus text-xs"></i> Novo Lote
                            </button>
                        </template>
                        <template x-if="subTab === 'entradas'">
                            <div class="flex gap-2">
                                <button @click="$dispatch('open-modal', 'modal-transf-creche')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold shadow-sm transition">
                                    <i class="fa-solid fa-arrows-spin text-xs"></i> Da Creche
                                </button>
                                <button @click="$dispatch('open-modal', 'modal-nova-entrada')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold shadow-sm transition">
                                    <i class="fa-solid fa-plus text-xs"></i> Entrada Manual
                                </button>
                            </div>
                        </template>
                        <template x-if="subTab === 'mortes'">
                            <button @click="$dispatch('open-modal', 'modal-nova-morte')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow-sm transition">
                                <i class="fa-solid fa-plus text-xs"></i> Registrar Morte
                            </button>
                        </template>
                        <template x-if="subTab === 'transferencias'">
                            <button @click="$dispatch('open-modal', 'modal-nova-transferencia')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm transition">
                                <i class="fa-solid fa-plus text-xs"></i> Nova Transferência
                            </button>
                        </template>
                        <template x-if="subTab === 'vendas'">
                            <button @click="$dispatch('open-modal', 'modal-nova-venda')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-sm transition">
                                <i class="fa-solid fa-plus text-xs"></i> Nova Venda / Abate
                            </button>
                        </template>
                        <template x-if="subTab === 'pesos'">
                            <button @click="$dispatch('open-modal', 'modal-novo-peso')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold shadow-sm transition">
                                <i class="fa-solid fa-plus text-xs"></i> Registrar Pesagem
                            </button>
                        </template>
                    </div>
                </div>

                <div id="lotes" x-show="subTab === 'lotes'" x-cloak class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm p-6">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Galpão / Local</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd. Inicial</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($lotesCadastrados as $l)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $l['nome'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if(($l['situacao'] ?? '') === 'aberto')
                                            <span class="px-2 py-1 rounded-lg text-[11px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">Aberto</span>
                                        @else
                                            <span class="px-2 py-1 rounded-lg text-[11px] font-black uppercase tracking-wider bg-gray-200 text-gray-700">Fechado</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ trim(implode(' - ', array_filter([$l['galpao'] ?? '', $l['localizacao'] ?? '']))) ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-800">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <a href="{{ route('terminacao.lotes.show', $l['id'] ?? 0) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-bold transition">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 italic">Nenhum lote cadastrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="entradas" x-show="subTab === 'entradas'" x-cloak class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm p-6">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origem</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Peso Médio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($entradas as $e)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $e['data_entrada'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-700">{{ $e['lote'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 rounded bg-sky-100 text-sky-800 uppercase font-bold">{{ $e['origem'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-emerald-700">+{{ (int)$e['quantidade'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 font-semibold">{{ isset($e['peso_medio']) ? number_format((float)$e['peso_medio'], 2, ',', '.').' kg' : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $e['localizacao'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 italic">Nenhuma entrada registrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="mortes" x-show="subTab === 'mortes'" x-cloak class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm p-6">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local / Baia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($mortes as $m)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $m['data_morte'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-700">{{ $m['lote'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-rose-600">-{{ (int)$m['quantidade'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $m['causa'] ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $m['localizacao'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 italic">Nenhuma morte registrada (🎉).</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="transferencias" x-show="subTab === 'transferencias'" x-cloak class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm p-6">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origem → Destino</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($transferencias as $t)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $t['data_transferencia'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-800 font-bold">
                                        {{ $t['lote_origem'] ?: '?' }}
                                        <i class="fa-solid fa-arrow-right mx-2 text-indigo-300 text-xs"></i>
                                        {{ $t['lote_destino'] ?: ($t['localizacao_destino'] ?: '?') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-indigo-700">{{ (int)$t['quantidade'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $t['motivo'] ?: ($t['tipo'] ?? '-') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 italic">Nenhuma transferência registrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="vendas" x-show="subTab === 'vendas'" x-cloak class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm p-6">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Peso Médio</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frigorífico / Comprador</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($vendas as $v)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $v['data_venda'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-700">{{ $v['lote'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-emerald-700">{{ (int)$v['quantidade'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-800">{{ isset($v['peso_medio_kg']) ? number_format((float)$v['peso_medio_kg'], 2, ',', '.').' kg' : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-emerald-800">{{ isset($v['valor_total']) ? 'R$ '.number_format((float)$v['valor_total'], 2, ',', '.') : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $v['comprador'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 italic">Nenhuma venda registrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="pesos" x-show="subTab === 'pesos'" x-cloak class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm p-6">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Peso Médio (kg)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">GPD (g/dia)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amostra</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($pesos as $p)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $p['data_pesagem'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-700">{{ $p['lote'] ?? '' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-orange-700">{{ number_format((float)$p['peso_medio_kg'], 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-emerald-700">{{ isset($p['gpd_medio']) ? number_format((float)$p['gpd_medio'], 2, ',', '.') : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">{{ isset($p['quantidade_amostra']) ? (int)$p['quantidade_amostra'] : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $p['localizacao'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 italic">Nenhuma pesagem registrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div x-show="tab === 'analises'" x-cloak x-transition class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100">
                <h6 class="font-bold text-amber-800 uppercase text-xs tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-simple"></i> Central de Análise · Terminação
                </h6>
                <p class="text-sm text-gray-600 mt-1">Acompanhe o desempenho dos lotes, produtividade e eficiência operacional.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center hover:border-amber-300 hover:bg-amber-50/40 transition cursor-pointer">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 mx-auto flex items-center justify-center mb-3">
                        <i class="fa-solid fa-seedling text-2xl"></i>
                    </div>
                    <div class="font-bold text-gray-900 mb-1">Conversão Alimentar</div>
                    <div class="text-xs text-gray-500">Kg de ração / Kg de ganho de peso · Em breve</div>
                    <span class="mt-3 inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Favoritar</span>
                </div>
                <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center hover:border-amber-300 hover:bg-amber-50/40 transition cursor-pointer">
                    <div class="w-14 h-14 rounded-2xl bg-sky-100 text-sky-700 mx-auto flex items-center justify-center mb-3">
                        <i class="fa-solid fa-arrow-trend-up text-2xl"></i>
                    </div>
                    <div class="font-bold text-gray-900 mb-1">GPD · Ganho Peso Diário</div>
                    <div class="text-xs text-gray-500">Evolução g/cabeça/dia por lote · Em breve</div>
                    <span class="mt-3 inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Favoritar</span>
                </div>
                <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center hover:border-amber-300 hover:bg-amber-50/40 transition cursor-pointer">
                    <div class="w-14 h-14 rounded-2xl bg-rose-100 text-rose-700 mx-auto flex items-center justify-center mb-3">
                        <i class="fa-solid fa-skull text-2xl"></i>
                    </div>
                    <div class="font-bold text-gray-900 mb-1">Mortalidade por Lote</div>
                    <div class="text-xs text-gray-500">Ranking vs Meta · Em breve</div>
                    <span class="mt-3 inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">Comum</span>
                </div>
                <div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center hover:border-amber-300 hover:bg-amber-50/40 transition cursor-pointer">
                    <div class="w-14 h-14 rounded-2xl bg-violet-100 text-violet-700 mx-auto flex items-center justify-center mb-3">
                        <i class="fa-solid fa-scale-unbalanced-flip text-2xl"></i>
                    </div>
                    <div class="font-bold text-gray-900 mb-1">Uniformidade de Lote</div>
                    <div class="text-xs text-gray-500">CV (%) e faixas de peso · Em breve</div>
                    <span class="mt-3 inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">Comum</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MODAIS ============ --}}
    @include('terminacao.modals.novo-lote')
    @include('terminacao.modals.nova-entrada')
    @include('terminacao.modals.nova-morte')
    @include('terminacao.modals.nova-transferencia')
    @include('terminacao.modals.nova-venda')
    @include('terminacao.modals.novo-peso')
    @include('terminacao.modals.transferir-creche')

    {{-- Picker flutuante comum PARA TODOS os 14 campos de data (padrão Creche) --}}
    <div x-show="activePicker !== null"
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

@endsection
