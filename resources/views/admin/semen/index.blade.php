@extends('layouts.dashboard')

@section('title', 'Sêmen')

@section('content')
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Sêmen</h2>
            <p class="text-sm text-white">Cadastro e gestão de sêmen</p>
        </div>
    </div>
</div>

<div class="space-y-6 mt-6" x-data="{
    openCreate: {{ $errors->any() ? 'true' : 'false' }},
    items: [],
    loading: false,
    search: '',
    racaId: '',
    fornecedorId: '',
    page: 1,
    limit: 200,
    total: 0,
    pages: 1,
    racas: [],
    fornecedores: [],
    errorMessage: @js($errorMessage ?? null),
    showToast: false,
    toastMessage: '',
    toastType: 'success',

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
    dataInicial: '',
    dataFinalIso: '',
    dataFinal: '',
    dataNascimentoIso: '',
    dataNascimento: '',
    dataCompraIso: '',
    dataCompra: '',

    pickerConfig: {
        'filtro_data_inicial':   { isoKey: 'dataInicialIso',    displayKey: 'dataInicial',    refKey: 'refFiltroDataInicial' },
        'filtro_data_final':     { isoKey: 'dataFinalIso',      displayKey: 'dataFinal',      refKey: 'refFiltroDataFinal' },
        'semen_data_nascimento': { isoKey: 'dataNascimentoIso', displayKey: 'dataNascimento', refKey: 'refSemenDataNascimento' },
        'semen_data_compra':     { isoKey: 'dataCompraIso',     displayKey: 'dataCompra',     refKey: 'refSemenDataCompra' },
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

    notify(message, type = 'success') {
        this.toastMessage = message;
        this.toastType = type;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 4000);
    },
    
    loadRacas() {
        fetch('/api/racas')
            .then(r => r.json())
            .then(data => {
                this.racas = data;
            })
            .catch(() => { this.racas = []; });
    },
    
    loadFornecedores() {
        fetch('/api/fornecedores')
            .then(r => r.json())
            .then(data => {
                this.fornecedores = data;
            })
            .catch(() => { this.fornecedores = []; });
    },
    
    loadItems() {
        this.loading = true;
        const params = new URLSearchParams({
            limit: this.limit,
            page: this.page,
            search: this.search,
            raca_id: this.racaId,
            fornecedor_id: this.fornecedorId,
            data_inicial: this.dataInicialIso,
            data_final: this.dataFinalIso
        });
        
        fetch('/api/semen?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(response => {
                return response.json().then(data => ({ ok: response.ok, data }));
            })
            .then(({ ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || 'Não foi possível carregar os registros de sêmen.');
                }

                this.items = data.items || [];
                this.total = data.total || 0;
                this.pages = data.pages || 1;
            })
            .catch((error) => {
                this.items = [];
                this.total = 0;
                this.pages = 1;
                this.notify(error.message || 'Não foi possível carregar os registros de sêmen.', 'error');
            })
            .finally(() => {
                this.loading = false;
            });
    },

    resetFormDatas() {
        this.setIsoAndDisplay('dataNascimentoIso', 'dataNascimento', '');
        this.setIsoAndDisplay('dataCompraIso', 'dataCompra', this.HOJE_ISO);
    },
    
    saveItem(event) {
        event.preventDefault();
        this.normalizeDisplay('dataNascimentoIso', 'dataNascimento');
        this.normalizeDisplay('dataCompraIso', 'dataCompra');
        const formData = new FormData(event.target);
        
        fetch('/api/semen', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    let msg = err.message || 'Erro ao salvar sêmen.';
                    if (err.errors) {
                        const firstField = Object.keys(err.errors)[0];
                        if (firstField && err.errors[firstField] && err.errors[firstField][0]) {
                            msg = err.errors[firstField][0];
                        }
                    }
                    throw new Error(msg);
                });
            }
            return response.json();
        })
        .then(data => {
            this.notify(data.message || 'Sêmen cadastrado com sucesso!');
            this.openCreate = false;
            this.loadItems();
            this.resetFormDatas();
            event.target.reset();
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify(error.message || 'Erro ao salvar sêmen.', 'error');
        });
    },
    
    deleteItem(id) {
        if (!confirm('Excluir este registro de sêmen?')) return;
        
        fetch(`/api/semen/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Erro ao excluir sêmen.');
                });
            }
            return response.json();
        })
        .then(data => {
            this.notify(data.message || 'Sêmen excluído com sucesso!');
            this.loadItems();
        })
        .catch(error => {
            console.error('Erro:', error);
            this.notify(error.message || 'Erro ao excluir sêmen.', 'error');
        });
    },

    init() {
        this.resetAllDatasToToday();
        this.setIsoAndDisplay('dataNascimentoIso', 'dataNascimento', '');
        if (this.errorMessage) {
            this.notify(this.errorMessage, 'error');
            return;
        }

        this.loadRacas();
        this.loadFornecedores();
        this.loadItems();

        @if($errors->any())
            this.notify('Por favor, corrija os erros no formulário.', 'error');
        @endif

        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                const tipo = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '')
                    ? '1000_dias'
                    : String(items.criterio_calendario_tipo);
                this.calendarType = tipo;
                this.resetAllDatasToToday();
                this.setIsoAndDisplay('dataNascimentoIso', 'dataNascimento', '');
                this.criteriosLoaded = true;
            })
            .catch(() => { this.criteriosLoaded = true; });
    },
}">
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
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Cadastro de Sêmen</h6>
                <div class="text-sm text-gray-500 mt-1">Cadastro de sêmen comprado.</div>
            </div>
            <button type="button" @click="openCreate = true" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fa-solid fa-plus mr-2"></i>
                Novo
            </button>
        </div>
        
        <!-- Filtros -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/30">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" x-model="search" @input="loadItems()" placeholder="ID, raça, fornecedor..." class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Raça</label>
                    <select x-model="racaId" @change="loadItems()" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        <option value="">Todas</option>
                        <template x-for="raca in racas" :key="raca.id">
                            <option :value="raca.id" x-text="raca.nome"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Fornecedor</label>
                    <select x-model="fornecedorId" @change="loadItems()" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        <option value="">Todos</option>
                        <template x-for="fornecedor in fornecedores" :key="fornecedor.id">
                            <option :value="fornecedor.id" x-text="fornecedor.nome"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Data Inicial</label>
                    <input type="hidden" :value="dataInicialIso">
                    <div class="relative">
                        <input type="text" x-ref="refFiltroDataInicial" :value="dataInicial" @input="dataInicial=$event.target.value" @focus="openDatePicker('filtro_data_inicial',$refs)" @click="openDatePicker('filtro_data_inicial',$refs)" @blur="normalizeDisplay('dataInicialIso','dataInicial')" :placeholder="calendarType==='1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'" autocomplete="off" inputmode="numeric" class="w-full rounded-lg border-gray-300 shadow-sm text-sm pr-10">
                        <button type="button" @click="openDatePicker('filtro_data_inicial',$refs)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"><i class="fa-solid fa-calendar-days"></i></button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Data Final</label>
                    <input type="hidden" :value="dataFinalIso">
                    <div class="relative">
                        <input type="text" x-ref="refFiltroDataFinal" :value="dataFinal" @input="dataFinal=$event.target.value" @focus="openDatePicker('filtro_data_final',$refs)" @click="openDatePicker('filtro_data_final',$refs)" @blur="normalizeDisplay('dataFinalIso','dataFinal')" :placeholder="calendarType==='1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'" autocomplete="off" inputmode="numeric" class="w-full rounded-lg border-gray-300 shadow-sm text-sm pr-10">
                        <button type="button" @click="openDatePicker('filtro_data_final',$refs)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"><i class="fa-solid fa-calendar-days"></i></button>
                    </div>
                </div>
                <div class="flex items-end">
                    <button @click="loadItems()" class="w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="fa-solid fa-search mr-2"></i>
                        Filtrar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4">ID Primária</th>
                        <th class="py-3 px-4">ID Secundária</th>
                        <th class="py-3 px-4">Raça</th>
                        <th class="py-3 px-4">Data Nasc.</th>
                        <th class="py-3 px-4">Data Compra</th>
                        <th class="py-3 px-4">Valor</th>
                        <th class="py-3 px-4">Fornecedor</th>
                        <th class="py-3 px-4 w-[100px]">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr>
                            <td colspan="8" class="py-8 text-center text-sm text-gray-500">
                                <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                Carregando...
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="8" class="py-8 text-center text-sm text-gray-500">
                                Nenhum registro de sêmen encontrado.
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in items" :key="item.id">
                        <tr>
                            <td class="py-3 px-4 font-medium" x-text="item.id_primaria"></td>
                            <td class="py-3 px-4" x-text="item.id_secundaria || '-'"></td>
                            <td class="py-3 px-4" x-text="item.raca_nome || '-'"></td>
                            <td class="py-3 px-4" x-text="item.data_nascimento ? new Date(item.data_nascimento).toLocaleDateString('pt-BR') : '-'"></td>
                            <td class="py-3 px-4" x-text="item.data_compra ? new Date(item.data_compra).toLocaleDateString('pt-BR') : '-'"></td>
                            <td class="py-3 px-4" x-text="item.valor_compra ? 'R$ ' + parseFloat(item.valor_compra).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '-'"></td>
                            <td class="py-3 px-4" x-text="item.fornecedor_nome || '-'"></td>
                            <td class="py-3 px-4">
                                <button @click="deleteItem(item.id)" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Mostrando <span x-text="items.length"></span> de <span x-text="total"></span> registros
            </div>
            <div class="flex items-center gap-2">
                <button @click="page = Math.max(1, page - 1); loadItems()" :disabled="page <= 1" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span class="text-sm text-gray-500 px-3">
                    Página <span x-text="page"></span> de <span x-text="pages"></span>
                </span>
                <button @click="page = Math.min(pages, page + 1); loadItems()" :disabled="page >= pages" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro -->
    <div x-show="openCreate" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openCreate" @click="openCreate = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openCreate" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <form accept-charset="UTF-8" @submit="saveItem($event)">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900">Novo Sêmen</h3>
                            <button type="button" @click="openCreate = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ID Primária *</label>
                                <input type="text" name="id_primaria" required class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900" placeholder="Ex: SEM001">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ID Secundária</label>
                                <input type="text" name="id_secundaria" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900" placeholder="Ex: SEC001">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Raça</label>
                                <select name="raca_id" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900">
                                    <option value="">Selecione...</option>
                                    <template x-for="raca in racas" :key="raca.id">
                                        <option :value="raca.id" x-text="raca.nome"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Data Nascimento</label>
                                <input type="hidden" name="data_nascimento" :value="dataNascimentoIso">
                                <div class="relative mt-1">
                                    <input type="text" x-ref="refSemenDataNascimento" :value="dataNascimento" @input="dataNascimento=$event.target.value" @focus="openDatePicker('semen_data_nascimento',$refs)" @click="openDatePicker('semen_data_nascimento',$refs)" @blur="normalizeDisplay('dataNascimentoIso','dataNascimento')" :placeholder="calendarType==='1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'" autocomplete="off" inputmode="numeric" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900 pr-10">
                                    <button type="button" @click="openDatePicker('semen_data_nascimento',$refs)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"><i class="fa-solid fa-calendar-days"></i></button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Data Compra *</label>
                                <input type="hidden" name="data_compra" :value="dataCompraIso">
                                <div class="relative mt-1">
                                    <input type="text" x-ref="refSemenDataCompra" :value="dataCompra" @input="dataCompra=$event.target.value" @focus="openDatePicker('semen_data_compra',$refs)" @click="openDatePicker('semen_data_compra',$refs)" @blur="normalizeDisplay('dataCompraIso','dataCompra')" :placeholder="calendarType==='1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'" autocomplete="off" inputmode="numeric" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900 pr-10" required>
                                    <button type="button" @click="openDatePicker('semen_data_compra',$refs)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"><i class="fa-solid fa-calendar-days"></i></button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Valor Compra</label>
                                <input type="number" name="valor_compra" step="0.01" min="0" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900" placeholder="0,00">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                                <select name="fornecedor_id" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900">
                                    <option value="">Selecione...</option>
                                    <template x-for="fornecedor in fornecedores" :key="fornecedor.id">
                                        <option :value="fornecedor.id" x-text="fornecedor.nome"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="submit" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto">
                            Salvar
                        </button>
                        <button type="button" @click="openCreate = false" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
</div>
@endsection
