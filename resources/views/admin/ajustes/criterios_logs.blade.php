@extends('layouts.dashboard')

@section('title', 'Utilitários - Logs de Critérios')
@section('page_title', 'Utilitários - Logs de Critérios')

@section('content')
<div x-data="{
    loaded: false,
    loading: false,
    error: '',
    items: [],
    usuarios: [],
    filtro: {
        evento: 'cobertura',
        usuarioId: '',
    },

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

    inicioIso: '',
    inicio: '',
    fimIso: '',
    fim: '',

    pickerConfig: {
        'filtro_inicio': { isoKey: 'inicioIso', displayKey: 'inicio', refKey: 'refFiltroInicio' },
        'filtro_fim':    { isoKey: 'fimIso',    displayKey: 'fim',    refKey: 'refFiltroFim' },
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
    resetAllDatasToToday() {
        Object.entries(this.pickerConfig).forEach(([k, cfg]) => {
            this.setIsoAndDisplay(cfg.isoKey, cfg.displayKey, this.HOJE_ISO);
        });
    },

    loadUsuarios() {
        fetch('/api/usuarios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => { this.usuarios = Array.isArray(data.items) ? data.items : []; })
            .catch(() => { this.usuarios = []; });
    },
    load() {
        this.loading = true;
        this.error = '';

        const params = new URLSearchParams();
        params.set('limit', '200');
        if (this.filtro.evento) params.set('evento', this.filtro.evento);
        if (this.filtro.usuarioId) params.set('usuario_id', this.filtro.usuarioId);
        if (this.inicioIso) params.set('inicio', this.inicioIso);
        if (this.fimIso) params.set('fim', this.fimIso);

        fetch('/api/criterios/logs?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                this.items = Array.isArray(data.items) ? data.items : [];
                if (data.message) this.error = data.message;
                this.loaded = true;
            })
            .catch(() => { this.error = 'Não foi possível carregar os logs.'; })
            .finally(() => { this.loading = false; });
    },
    init() {
        this.resetAllDatasToToday();
        this.loadUsuarios();
        this.load();
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
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 dark:text-primary-400 uppercase text-xs tracking-wider">Logs de Critérios</h6>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Registros gerados quando um lançamento é salvo com divergência nos critérios.</div>
            </div>
            <button type="button" @click="load()" :disabled="loading" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                <template x-if="!loading"><span>Atualizar</span></template>
                <template x-if="loading"><span>Carregando...</span></template>
            </button>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            <div x-show="error" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/50 text-amber-800 dark:text-amber-400 rounded-xl px-4 py-3 text-sm" x-text="error" x-cloak></div>

            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-2xl p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Evento</label>
                        <select x-model="filtro.evento" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 rounded-xl">
                            <option value="cobertura">Cobertura</option>
                            <option value="cio_previsto_sem_registro">Cio previsto sem registro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Usuário</label>
                        <select x-model="filtro.usuarioId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 rounded-xl">
                            <option value="">Todos</option>
                            <template x-for="u in usuarios" :key="`u-${u.id}`">
                                <option :value="String(u.id)" x-text="u.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Início</label>
                        <input type="hidden" :value="inicioIso">
                        <div class="relative mt-1">
                            <input type="text" x-ref="refFiltroInicio" :value="inicio" @input="inicio=$event.target.value" @focus="openDatePicker('filtro_inicio',$refs)" @click="openDatePicker('filtro_inicio',$refs)" @blur="normalizeDisplay('inicioIso','inicio')" :placeholder="calendarType==='1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'" autocomplete="off" inputmode="numeric" class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 rounded-xl pr-10">
                            <button type="button" @click="openDatePicker('filtro_inicio',$refs)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"><i class="fa-solid fa-calendar-days"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fim</label>
                        <input type="hidden" :value="fimIso">
                        <div class="relative mt-1">
                            <input type="text" x-ref="refFiltroFim" :value="fim" @input="fim=$event.target.value" @focus="openDatePicker('filtro_fim',$refs)" @click="openDatePicker('filtro_fim',$refs)" @blur="normalizeDisplay('fimIso','fim')" :placeholder="calendarType==='1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'" autocomplete="off" inputmode="numeric" class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 rounded-xl pr-10">
                            <button type="button" @click="openDatePicker('filtro_fim',$refs)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"><i class="fa-solid fa-calendar-days"></i></button>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="load()" :disabled="loading" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50">
                        Filtrar
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Evento</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Usuário</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Animal</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Avisos</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="row in items" :key="row.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.ocorrido_em || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.evento || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.usuario || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.matriz || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <template x-if="Array.isArray(row.warnings) && row.warnings.length > 0">
                                        <ul class="space-y-1">
                                            <template x-for="(w, i) in row.warnings" :key="`w-${row.id}-${i}`">
                                                <li class="flex items-start gap-2">
                                                    <span class="mt-1 text-amber-700">
                                                        <i class="fa-solid fa-circle-dot text-[8px]"></i>
                                                    </span>
                                                    <span class="text-gray-700 dark:text-gray-300" x-text="w"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                    <template x-if="!Array.isArray(row.warnings) || row.warnings.length === 0">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loaded && items.length === 0" x-cloak>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum log encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="activePicker !== null"
         x-cloak
         :style="`top:${pickerTop}px; left:${pickerLeft}px;`"
         :class="pickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
         class="fixed z-[200] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 -translate-x-1/2 max-h-[calc(100vh-12rem)] overflow-y-auto"
         @click.away="activePicker = null">
        <div class="flex items-center justify-between mb-3">
            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                <span x-text="calendarMonths[calendarMonth]"></span> <span x-text="calendarYear"></span>
            </div>
            <button type="button" @click.stop="nextCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
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
                        class="aspect-square flex flex-col items-center justify-center rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        :class="{
                            'opacity-50': !d.isCurrentMonth,
                            'bg-primary-600 text-white border-primary-600': d.isSelected,
                            'text-gray-800 dark:text-gray-200': d.isCurrentMonth && !d.isSelected
                        }">
                    <div class="text-sm font-medium" x-text="d.day"></div>
                    <div class="text-[10px]" x-show="calendarType === '1000_dias' && d.isCurrentMonth && d.pigDay !== ''" x-text="d.pigDay"></div>
                </button>
            </template>
        </div>
    </div>
</div>
@endsection
