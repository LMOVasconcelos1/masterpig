@extends('layouts.dashboard')

@section('title', 'Retenção de Fêmeas')

@section('content')
<div x-data="{
    retencaoLoading: false,
    retencaoError: '',
    retencaoData: null,
    retencaoFiltroRaca: '',
    retencaoFiltroTipo: 'leitoas',
    racasRetencao: [],

    criteriosLoaded: false,
    calendarType: '1000_dias',
    activePicker: null,
    calendarMonth: new Date().getMonth(),
    calendarYear: new Date().getFullYear(),
    calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    pickerTop: 0,
    pickerLeft: 0,
    pickerDirection: 'down',

    HOJE_ISO: {{ json_encode(now()->format('Y-m-d'), JSON_UNESCAPED_UNICODE) }},

    dataInicialIso: '',
    dataInicialDisplay: '',
    dataFinalIso: '',
    dataFinalDisplay: '',

    pickerConfig: {
        'ret_inicio': { isoKey: 'dataInicialIso', displayKey: 'dataInicialDisplay', refKey: 'retRefInicio' },
        'ret_fim':    { isoKey: 'dataFinalIso',   displayKey: 'dataFinalDisplay',   refKey: 'retRefFim' },
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
        let d = parseInt(m[1], 10), mo = parseInt(m[2], 10), y = parseInt(m[3], 10);
        if (d < 1 || d > 31 || mo < 1 || mo > 12) return null;
        const pad = (n) => String(n).padStart(2, '0');
        const dt = new Date(y, mo - 1, d);
        if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return null;
        return `${y}-${pad(mo)}-${pad(d)}`;
    },
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
    normalizeDisplay(isoKey, displayKey) {
        const isPig = this.calendarType === '1000_dias';
        const raw = String(this[displayKey] || '').trim();
        if (!raw) { this.setIsoAndDisplay(isoKey, displayKey, ''); return; }
        let iso = null;
        if (isPig && /^\d{1,4}$/.test(raw) && typeof pigDayToDate === 'function') iso = pigDayToDate(raw);
        if (!iso) iso = this.brToIso(raw);
        if (iso) this.setIsoAndDisplay(isoKey, displayKey, iso);
    },
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
    openDatePicker(which) {
        const cfg = this.pickerConfig[which];
        if (!cfg) return;
        const iso = String(this[cfg.isoKey] || '').trim();
        const ref = this.$refs[cfg.refKey] || null;
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
    resetAllDatasToToday() {
        Object.entries(this.pickerConfig).forEach(([k, cfg]) => {
            this.setIsoAndDisplay(cfg.isoKey, cfg.displayKey, this.HOJE_ISO);
        });
    },

    loadRacasRetencao() {
        if (this.racasRetencao.length > 0) return;
        fetch('/api/racas')
            .then(r => r.json())
            .then(data => {
                this.racasRetencao = Array.isArray(data) ? data : [];
            })
            .catch(() => {
                this.racasRetencao = [];
            });
    },

    loadRetencaoData() {
        if (!this.dataInicialDisplay || !this.dataFinalDisplay) {
            this.retencaoError = 'Selecione o período para análise.';
            return;
        }
        if (!this.dataInicialIso) this.normalizeDisplay('dataInicialIso','dataInicialDisplay');
        if (!this.dataFinalIso)   this.normalizeDisplay('dataFinalIso','dataFinalDisplay');
        if (!this.dataInicialIso || !this.dataFinalIso) {
            this.retencaoError = 'Datas inválidas. Use o calendário ou digite um formato válido.';
            return;
        }
        this.retencaoLoading = true;
        this.retencaoError = '';
        const params = new URLSearchParams({
            data_inicial: this.isoToBr(this.dataInicialIso),
            data_final:   this.isoToBr(this.dataFinalIso),
            raca_id:      this.retencaoFiltroRaca,
            tipo_entrada: this.retencaoFiltroTipo
        });
        fetch(`/api/plantel/femeas/retencao?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data?.message || 'Erro ao carregar dados');
                return data;
            })
            .then(data => {
                this.retencaoData = data;
            })
            .catch(e => {
                this.retencaoError = e.message;
                this.retencaoData = null;
            })
            .finally(() => { this.retencaoLoading = false; });
    },

    init() {
        this.loadRacasRetencao();
        this.resetAllDatasToToday();
        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                const tipo = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '')
                    ? '1000_dias'
                    : String(items.criterio_calendario_tipo);
                this.calendarType = tipo;
                this.resetAllDatasToToday();
                this.criteriosLoaded = true;
            })
            .catch(() => { this.criteriosLoaded = true; });
    },
}" x-init="init()" class="space-y-6">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 via-emerald-50/80 to-emerald-100/50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-semibold text-gray-900">Retenção de Fêmeas</div>
                    <div class="text-xs text-gray-500 mt-1">Taxa de retenção ao longo do tempo de vida reprodutiva.</div>
                </div>
                <a href="{{ url('/dashboard?tab=analise') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Voltar
                </a>
            </div>
        </div>
        <div class="p-6">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 mb-6">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Filtros de Análise</div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Inicial</label>
                        <input type="hidden" name="data_inicial_iso" :value="dataInicialIso">
                        <div class="relative">
                            <input type="text"
                                   x-ref="retRefInicio"
                                   :value="dataInicialDisplay"
                                   @input="dataInicialDisplay = $event.target.value"
                                   @focus="openDatePicker('ret_inicio')"
                                   @click="openDatePicker('ret_inicio')"
                                   @blur="normalizeDisplay('dataInicialIso','dataInicialDisplay')"
                                   :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   class="block w-full px-3 py-2 pr-10 text-sm border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 shadow-sm hover:border-emerald-300">
                            <button type="button" tabindex="-1" @click.stop="openDatePicker('ret_inicio')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-600 transition-colors">
                                <i class="fa-solid fa-calendar-days"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Final</label>
                        <input type="hidden" name="data_final_iso" :value="dataFinalIso">
                        <div class="relative">
                            <input type="text"
                                   x-ref="retRefFim"
                                   :value="dataFinalDisplay"
                                   @input="dataFinalDisplay = $event.target.value"
                                   @focus="openDatePicker('ret_fim')"
                                   @click="openDatePicker('ret_fim')"
                                   @blur="normalizeDisplay('dataFinalIso','dataFinalDisplay')"
                                   :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   class="block w-full px-3 py-2 pr-10 text-sm border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 shadow-sm hover:border-emerald-300">
                            <button type="button" tabindex="-1" @click.stop="openDatePicker('ret_fim')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-600 transition-colors">
                                <i class="fa-solid fa-calendar-days"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Raça</label>
                        <select x-model="retencaoFiltroRaca" class="block w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 shadow-sm hover:border-emerald-300 appearance-none cursor-pointer">
                            <option value="">Todas</option>
                            <template x-for="r in racasRetencao" :key="r.id">
                                <option :value="String(r.id)" x-text="r.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo de Entrada</label>
                        <select x-model="retencaoFiltroTipo" class="block w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 shadow-sm hover:border-emerald-300 appearance-none cursor-pointer">
                            <option value="leitoas">Somente leitoas</option>
                            <option value="ciclo1">Fêmeas com entrada no ciclo 1</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="loadRetencaoData()" :disabled="retencaoLoading" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border-2 border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-600 hover:border-emerald-600 hover:text-white transition-all duration-300 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-chart-line text-xs"></i>
                        <template x-if="!retencaoLoading"><span>Analisar</span></template>
                        <template x-if="retencaoLoading"><span>Carregando...</span></template>
                    </button>
                </div>
            </div>

            <div x-show="retencaoLoading" class="text-center py-8" x-cloak>
                <i class="fa-solid fa-spinner fa-spin text-emerald-500 text-3xl"></i>
                <div class="text-sm text-gray-500 mt-3">Carregando dados de retenção...</div>
            </div>

            <div x-show="retencaoError" class="bg-red-50 border border-red-100 text-red-700 rounded-xl px-4 py-3 text-sm mb-4" x-text="retencaoError" x-cloak></div>

            <div x-show="retencaoData && !retencaoLoading" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-xl p-4 border border-emerald-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-piggy-bank"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Total Entradas</div>
                                <div class="text-2xl font-bold text-emerald-900" x-text="retencaoData?.total_entradas ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-blue-700 uppercase tracking-wider">Retidas</div>
                                <div class="text-2xl font-bold text-blue-900" x-text="retencaoData?.retidas ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-xl p-4 border border-amber-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-percentage"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-amber-700 uppercase tracking-wider">Taxa Retenção</div>
                                <div class="text-2xl font-bold text-amber-900" x-text="(retencaoData?.taxa_retencao ?? '-') + '%'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 rounded-xl p-4 border border-purple-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-list-ol"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-purple-700 uppercase tracking-wider">Média Ciclos</div>
                                <div class="text-2xl font-bold text-purple-900" x-text="retencaoData?.media_ciclos ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Retenção por Ordem de Parto</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ordem</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Entradas</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Retidas</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Taxa</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template x-for="row in (retencaoData?.por_ordem_parto ?? [])" :key="row.ordem">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="'Ciclo ' + row.ordem"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="row.entradas"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="row.retidas"></td>
                                        <td class="px-4 py-3 text-sm font-semibold" :class="row.taxa >= 80 ? 'text-emerald-600' : (row.taxa >= 60 ? 'text-amber-600' : 'text-red-600')" x-text="row.taxa + '%'"></td>
                                    </tr>
                                </template>
                                <tr x-show="!retencaoData?.por_ordem_parto || retencaoData.por_ordem_parto.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem dados para o período selecionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="!retencaoData && !retencaoLoading && !retencaoError" class="text-center py-8">
                <i class="fa-solid fa-chart-pie text-4xl text-gray-300 mb-3"></i>
                <div class="text-sm text-gray-500">Selecione o período e clique em "Analisar" para visualizar a taxa de retenção.</div>
            </div>
        </div>
    </div>

    <!-- Picker flutuante único para ambas as datas -->
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
                            'bg-emerald-600 text-white border-emerald-600': d.isSelected,
                            'text-gray-800': d.isCurrentMonth && !d.isSelected
                        }">
                    <div class="text-sm font-medium" x-text="d.day"></div>
                    <div class="text-[10px]" x-show="calendarType === '1000_dias' && d.isCurrentMonth && d.pigDay !== ''" x-text="d.pigDay"></div>
                </button>
            </template>
        </div>
    </div>
</div>
@endsection
