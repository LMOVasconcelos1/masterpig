@extends('layouts.dashboard')

@section('title', 'Plantel Reprodutivo')
@section('page_title', '')

@section('content')
<div x-data="{ 
        tab: (function(){ const t = (new URLSearchParams(window.location.search).get('tab') || 'visao'); return ['visao','lancamentos','acompanhamento','analise','relatorios'].includes(t) ? t : 'visao'; })(), 
        calendarType: localStorage.getItem('masterpig_calendar_type') || '1000_dias',
        toggleCalendar() {
            const newType = this.calendarType === 'gregoriano' ? '1000_dias' : 'gregoriano';
            this.calendarType = newType;
            localStorage.setItem('masterpig_calendar_type', newType);
            
            fetch('{{ route('admin.criterios.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ criterio_calendario_tipo: newType })
            }).then(() => {
                window.location.reload();
            });
        },

        // Global Edit State
        saving: false,
        openEditFemea: false,
        editFemeaData: {
            id: '',
            id_primaria: '',
            id_secundaria: '',
            raca_id: '',
            localizacao: '',
            baia: '',
            data_nascimento: '',
            data_compra: '',
            data_cobertura: '',
            tipo_compra: '',
            ciclos_ate_compra: '',
            fornecedor_id: '',
            valor_compra: '',
            peso_compra: '',
            caracteristicas: ''
        },
        racas: [],
        fornecedores: [],
        utilLocalizacoes: [],
        utilBaias: [],

        ensureRacas() {
            if (this.racas.length > 0) return;
            fetch('/api/racas')
                .then(r => r.json())
                .then(data => { this.racas = Array.isArray(data) ? data : []; })
                .catch(() => { this.racas = []; });
        },
        ensureFornecedores() {
            if (this.fornecedores.length > 0) return;
            fetch('/api/fornecedores')
                .then(r => r.json())
                .then(data => { this.fornecedores = Array.isArray(data) ? data : []; })
                .catch(() => { this.fornecedores = []; });
        },
        ensureUtilitarios() {
            fetch('/api/utilitarios/localizacoes')
                .then(r => r.json())
                .then(data => { this.utilLocalizacoes = data; });
            fetch('/api/utilitarios/baias')
                .then(r => r.json())
                .then(data => { this.utilBaias = data; });
        },

        formatBrDate(raw) {
            if (!raw) return '';
            const d = new Date(raw);
            if (isNaN(d.getTime())) return raw;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}/${month}/${year}`;
        },
        parseBrDate(brStr) {
            if (!brStr || !/^\d{2}\/\d{2}\/\d{4}$/.test(brStr)) return null;
            const [d, m, y] = brStr.split('/');
            return `${y}-${m}-${d}`;
        },
        normalizeDateInput(value) {
            if (!value) return '';
            let v = value.replace(/\D/g, '');
            if (v.length > 8) v = v.slice(0, 8);
            if (v.length > 4) v = v.replace(/^(\d{2})(\d{2})(\d{0,4}).*/, '$1/$2/$3');
            else if (v.length > 2) v = v.replace(/^(\d{2})(\d{0,2}).*/, '$1/$2');
            return v;
        },

        openEdit(f) {
            this.editFemeaData = {
                id: f.id,
                id_primaria: f.id_primaria,
                id_secundaria: f.id_secundaria || '',
                raca_id: f.raca_id || '',
                localizacao: f.localizacao || '',
                baia: f.baia || '',
                data_nascimento: this.formatBrDate(f.data_nascimento),
                data_compra: this.formatBrDate(f.data_compra || f.data),
                data_cobertura: this.formatBrDate(f.data_cobertura),
                tipo_compra: f.tipo_key || f.tipo_compra || f.tipo || '',
                ciclos_ate_compra: f.ciclos_ate_compra || '',
                fornecedor_id: f.fornecedor_id || '',
                valor_compra: f.valor_compra || '',
                peso_compra: f.peso_compra || '',
                caracteristicas: f.caracteristicas || ''
            };
            this.ensureRacas();
            this.ensureFornecedores();
            this.ensureUtilitarios();
            this.openEditFemea = true;
        },

        saveEditFemea() {
            if (!this.editFemeaData.id_primaria) {
                toast('ID Primíria ê obrigatório', 'error');
                return;
            }
            this.saving = true;
            fetch(`/api/plantel/femeas/${this.editFemeaData.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(this.editFemeaData)
            })
            .then(async r => {
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || 'Erro ao salvar alterações');
                return data;
            })
            .then(data => {
                toast('Fêmea atualizada com sucesso!', 'success');
                this.openEditFemea = false;
                // Notify tabs to refresh
                window.dispatchEvent(new CustomEvent('femea-updated', { detail: { id: this.editFemeaData.id } }));
            })
            .catch(e => {
                toast(e.message, 'error');
            })
            .finally(() => this.saving = false);
        },

        // Datepicker Logic (Moved from Lancamentos)
        activePicker: null,
        calendarMonth: new Date().getMonth(),
        calendarYear: new Date().getFullYear(),
        calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        mortePickerTop: 0,
        mortePickerLeft: 0,
        mortePickerDirection: 'down',
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
            const days = [];
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                days.push({
                    date: date.toISOString().split('T')[0],
                    day: date.getDate(),
                    isCurrentMonth: date.getMonth() === this.calendarMonth,
                    pigDay: typeof toPigDay === 'function' ? toPigDay(date.toISOString().split('T')[0]) : ''
                });
            }
            return days;
        },
        selectCalendarDate(dateStr) {
            const d = new Date(dateStr + 'T12:00:00');
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            const formatted = `${dd}/${mm}/${yyyy}`;
            const pigDay = typeof toPigDay === 'function' ? toPigDay(dateStr) : '';
            
            if (this.openEditFemea) {
                if (this.activePicker === 'nascimento') this.editFemeaData.data_nascimento = pigDay;
                if (this.activePicker === 'compra') this.editFemeaData.data_compra = pigDay;
                if (this.activePicker === 'cobertura') this.editFemeaData.data_cobertura = pigDay;
            } else {
                if (this.activePicker === 'compra') this.dataCompra = pigDay;
                else if (this.activePicker === 'nascimento') {
                    if (this.nascimentoAuto !== undefined) this.nascimentoAuto = false;
                    this.dataNascimento = pigDay;
                }
                else if (this.activePicker === 'cobertura') this.dataCobertura = pigDay;
                else if (this.activePicker === 'cio') this.dataCio = pigDay;
                else if (this.activePicker === 'morte') this.dataMorte = pigDay;
                else if (this.activePicker === 'descarte') this.dataDescarte = pigDay;
                else if (this.activePicker === 'venda') this.dataVenda = pigDay;
                else if (this.activePicker === 'semen_compra') {
                    if (this.semenForm) this.semenForm.data_compra = pigDay;
                }
                else if (this.activePicker === 'semen_nascimento') {
                    if (this.semenForm) this.semenForm.data_nascimento = pigDay;
                }
                else if (this.activePicker === 'semen_validade') {
                    if (this.semenForm) this.semenForm.validade = pigDay;
                }
                else if (this.activePicker === 'editCio') {
                    if (this.editCioData) this.editCioData.data = pigDay;
                }
            }
            this.activePicker = null;
        },
        openMorteDatePicker() {
            const el =
                (this.$refs && (this.$refs.morteDateInputMacho || this.$refs.morteDateInputFemea || this.$refs.morteDateInput))
                    ? (this.$refs.morteDateInputMacho || this.$refs.morteDateInputFemea || this.$refs.morteDateInput)
                    : null;
            const raw = String(el && el.value ? el.value : '').trim();
            let base = new Date();
            if (/^\d+$/.test(raw) && typeof pigDayToDate === 'function') {
                const iso = pigDayToDate(raw);
                if (iso && /^\d{4}-\d{2}-\d{2}$/.test(String(iso))) {
                    base = new Date(String(iso) + 'T12:00:00');
                }
            }
            this.calendarMonth = base.getMonth();
            this.calendarYear = base.getFullYear();
            this.activePicker = 'morte';
            this.$nextTick(() => {
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

                this.mortePickerLeft = left;
                this.mortePickerTop = top;
                this.mortePickerDirection = direction;
            });
        },
        getSelectedPigDay() {
            let raw = '';
            if (this.openEditFemea) {
                if (this.activePicker === 'nascimento') raw = this.editFemeaData.data_nascimento;
                if (this.activePicker === 'compra') raw = this.editFemeaData.data_compra;
                if (this.activePicker === 'cobertura') raw = this.editFemeaData.data_cobertura;
            } else {
                if (this.activePicker === 'compra') raw = this.dataCompra;
                else if (this.activePicker === 'nascimento') raw = this.dataNascimento;
                else if (this.activePicker === 'cobertura') raw = this.dataCobertura;
                else if (this.activePicker === 'cio') raw = this.dataCio;
                else if (this.activePicker === 'morte') raw = this.dataMorte;
                else if (this.activePicker === 'descarte') raw = this.dataDescarte;
                else if (this.activePicker === 'venda') raw = this.dataVenda;
                else if (this.activePicker === 'semen_compra') raw = (this.semenForm ? this.semenForm.data_compra : '');
                else if (this.activePicker === 'semen_nascimento') raw = (this.semenForm ? this.semenForm.data_nascimento : '');
                else if (this.activePicker === 'semen_validade') raw = (this.semenForm ? this.semenForm.validade : '');
                else if (this.activePicker === 'editCio') raw = (this.editCioData ? this.editCioData.data : '');
            }
            const iso = this.parseBrDate(raw);
            if (!iso) return '';
            return typeof toPigDay === 'function' ? toPigDay(iso) : '';
        }
    }"
     class="space-y-6">
<!-- Header & Topbar -->
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2" data-tour="plantel-reprodutivo">Plantel Reprodutivo</h2>
            <p class="text-sm text-white">Visão geral, lançamentos e relatórios</p>
        </div>
        <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
            <button type="button" @click="tab = 'visao'" 
                :class="tab === 'visao' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors"
                data-tour="visao-geral-plantel">
                Visão Geral
            </button>
            <button type="button" @click="tab = 'lancamentos'" 
                :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors"
                data-tour="aba-lancamentos">
                Lançamentos
            </button>
            <button type="button" @click="tab = 'acompanhamento'; $dispatch('acompanhamento-open')" 
                :class="tab === 'acompanhamento' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Acompanhamento
            </button>
            <button type="button" @click="tab = 'analise'" 
                :class="tab === 'analise' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Análise
            </button>
            <button type="button" @click="tab = 'relatorios'" 
                :class="tab === 'relatorios' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Relatórios
            </button>
        </nav>
    </div>
</div>

<div x-show="tab === 'visao'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Estoque Total</div>
                <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $estoqueTotalAnimais ?? 0 }}
                </div>
            </div>
            <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-warehouse text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm text-gray-500">
            <i class="fa-solid fa-piggy-bank mr-2"></i>
            <span class="font-medium">Leitoas + Matrizes + Machos</span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Leitoas Ativas</div>
                <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $leitoasAtivas ?? 0 }}
                </div>
            </div>
            <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-piggy-bank text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-skull-crossbones mr-2 text-red-500"></i>
                <span class="font-medium">Mortes</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasLeitoas['morte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-ban mr-2 text-amber-600"></i>
                <span class="font-medium">Descartes</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasLeitoas['descarte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>
                <span class="font-medium">Vendas</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasLeitoas['venda'] ?? 0 }}</span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Matrizes Ativas</div>
                <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $matrizesAtivas ?? 0 }}
                </div>
            </div>
            <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-piggy-bank text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-skull-crossbones mr-2 text-red-500"></i>
                <span class="font-medium">Mortes</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasMatrizes['morte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-ban mr-2 text-amber-600"></i>
                <span class="font-medium">Descartes</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasMatrizes['descarte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>
                <span class="font-medium">Vendas</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasMatrizes['venda'] ?? 0 }}</span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-primary-500 rounded-xl shadow-sm hover:shadow-md transition-all p-4 group">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-primary-500 uppercase tracking-wider mb-1">Machos Ativos</div>
                <div class="text-xl font-bold text-gray-800 tracking-tight group-hover:scale-105 transition-transform origin-left">
                    {{ $machosAtivos ?? 0 }}
                </div>
            </div>
            <div class="p-2 bg-primary-50 rounded-full text-primary-500 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                <i class="fa-solid fa-piggy-bank text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-skull-crossbones mr-2 text-red-500"></i>
                <span class="font-medium">Mortes</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasMachos['morte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-ban mr-2 text-amber-600"></i>
                <span class="font-medium">Descartes</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasMachos['descarte'] ?? 0 }}</span>
        </div>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-emerald-600"></i>
                <span class="font-medium">Vendas</span>
            </div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $saidasMachos['venda'] ?? 0 }}</span>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
            <div class="flex items-center gap-2">
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Inconsistências do Plantel</h6>
                
                <!-- Tooltip Informativo -->
                <div class="relative group">
                    <i class="fa-solid fa-circle-info text-primary-400 cursor-help hover:text-primary-600 transition-colors text-base"></i>
                    <div class="absolute z-50 left-1/2 mt-2 w-80 p-4 bg-gray-900 text-white text-[10px] rounded-xl shadow-2xl opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 transform -translate-x-1/2">
                        <div class="space-y-3">
                            <div>
                                <strong class="text-primary-400 block mb-1 uppercase tracking-tighter">Cio Previsto sem Registro</strong>
                                <span class="text-gray-300">Fêmeas com registro de cio anterior que ultrapassaram 21 dias e atingiram a data esperada para o próximo cio sem novos lançamentos.</span>
                            </div>
                            <div>
                                <strong class="text-amber-400 block mb-1 uppercase tracking-tighter">Parto Atrasado</strong>
                                <span class="text-gray-300">Fêmeas cobertas que ultrapassaram o período de gestação (114 dias) sem registro de nascimento.</span>
                            </div>
                            <div>
                                <strong class="text-emerald-400 block mb-1 uppercase tracking-tighter">Desmame Atrasado</strong>
                                <span class="text-gray-300">Matrizes em lactação que ultrapassaram o período previsto para o desmame dos leitões.</span>
                            </div>
                            <div>
                                <strong class="text-blue-400 block mb-1 uppercase tracking-tighter">Matriz Vazia Prolongada</strong>
                                <span class="text-gray-300">Fêmeas ativas e vazias hí mais de 250 dias (conforme critério de vazio míximo).</span>
                            </div>
                            <div>
                                <strong class="text-red-400 block mb-1 uppercase tracking-tighter">Macho Parado</strong>
                                <span class="text-gray-300">Machos ativos sem registros de cobertura nos últimos 60 dias.</span>
                            </div>
                        </div>
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-b-gray-900"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
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
                                        <a href="{{ route('gestacao', [], false) }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Abrir manejo de gestação" target="_blank" rel="noopener">
                                            <i class="fa-solid fa-stethoscope"></i>
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

<div x-show="tab === 'lancamentos'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
    <div x-data="{
        item: 'femeas',
        mov: 'compra',
        compraFemeasTipo: 'leitoa',
        semenOpenCreate: false,
        semenItems: [],
        semenLoading: false,
        semenSearch: '',
        semenRacaId: '',
        semenFornecedorId: '',
        semenDataInicial: '',
        semenDataFinal: '',
        semenPage: 1,
        semenLimit: 200,
        semenTotal: 0,
        semenPages: 1,
        semenRacas: [],
        semenFornecedores: [],
        semenForm: {
            id_primaria: '',
            id_secundaria: '',
            raca_id: '',
            data_nascimento: '',
            data_compra: '',
            validade: '',
            valor_compra: '',
            fornecedor_id: '',
        },
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
        comprasFemeasLoaded: false,
        comprasMachosLoaded: false,
        mortesFemeas: [],
        descartesFemeas: [],
        vendasFemeas: [],
        cioFemeas: [],
        cioFemeasLoaded: false,
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
        femeaCioId: '',
        dataCio: '',
        diaCicloCio: '',
        causaMorteId: '',

        semenInit() {
            this.semenLoadRacas();
            this.semenLoadFornecedores();
            this.semenLoadItems();
        },
        semenLoadRacas() {
            fetch('/api/racas', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { this.semenRacas = Array.isArray(data) ? data : []; })
                .catch(() => { this.semenRacas = []; });
        },
        semenLoadFornecedores() {
            fetch('/api/fornecedores', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { this.semenFornecedores = Array.isArray(data) ? data : []; })
                .catch(() => { this.semenFornecedores = []; });
        },
        semenLoadItems() {
            this.semenLoading = true;
            const params = new URLSearchParams({
                limit: this.semenLimit,
                page: this.semenPage,
                search: this.semenSearch,
                raca_id: this.semenRacaId,
                fornecedor_id: this.semenFornecedorId,
                data_inicial: this.semenDataInicial,
                data_final: this.semenDataFinal,
            });

            fetch('/api/semen?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Não foi possível carregar os registros de sêmen.');
                    return data;
                })
                .then(data => {
                    this.semenItems = data.items || [];
                    this.semenTotal = data.total || 0;
                    this.semenPages = data.pages || 1;
                })
                .catch(e => {
                    this.semenItems = [];
                    this.semenTotal = 0;
                    this.semenPages = 1;
                    toast(e.message || 'Não foi possível carregar os registros de sêmen.', 'error');
                })
                .finally(() => { this.semenLoading = false; });
        },
        semenResetForm() {
            this.semenForm = {
                id_primaria: '',
                id_secundaria: '',
                raca_id: '',
                data_nascimento: '',
                data_compra: '',
                validade: '',
                valor_compra: '',
                fornecedor_id: '',
            };
        },
        semenSave() {
            if (!this.semenForm.id_primaria || !this.semenForm.data_compra) {
                toast('Preencha os campos obrigatórios.', 'error');
                return;
            }

            let dataCompraIso;
            let dataNascimentoIso;

            if (/^\d+$/.test(this.semenForm.data_compra)) {
                dataCompraIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.semenForm.data_compra) : null;
            } else {
                dataCompraIso = this.parseBrDate(this.semenForm.data_compra);
            }

            if (this.semenForm.data_nascimento) {
                if (/^\d+$/.test(this.semenForm.data_nascimento)) {
                    dataNascimentoIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.semenForm.data_nascimento) : null;
                } else {
                    dataNascimentoIso = this.parseBrDate(this.semenForm.data_nascimento);
                }
            } else {
                dataNascimentoIso = null;
            }

            if (!dataCompraIso) {
                toast('Data de compra invílida.', 'error');
                return;
            }

            const payload = {
                id_primaria: this.semenForm.id_primaria,
                id_secundaria: this.semenForm.id_secundaria || null,
                raca_id: this.semenForm.raca_id ? Number(this.semenForm.raca_id) : null,
                data_nascimento: dataNascimentoIso,
                data_compra: dataCompraIso,
                valor_compra: this.semenForm.valor_compra === '' ? null : Number(this.semenForm.valor_compra),
                fornecedor_id: this.semenForm.fornecedor_id ? Number(this.semenForm.fornecedor_id) : null,
            };

            fetch('/api/semen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                },
                body: JSON.stringify(payload),
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        if (data?.errors) {
                            const firstField = Object.keys(data.errors)[0];
                            if (firstField && data.errors[firstField] && data.errors[firstField][0]) {
                                throw new Error(data.errors[firstField][0]);
                            }
                        }
                        throw new Error(data?.message || 'Erro ao salvar sêmen.');
                    }
                    return data;
                })
                .then(data => {
                    toast(data.message || 'Sêmen cadastrado com sucesso!', 'success');
                    this.semenOpenCreate = false;
                    this.semenResetForm();
                    this.semenLoadItems();
                })
                .catch(e => {
                    toast(e.message || 'Erro ao salvar sêmen.', 'error');
                });
        },
        semenDelete(id) {
            if (!confirm('Excluir este registro de sêmen?')) return;

            fetch(`/api/semen/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                },
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao excluir sêmen.');
                    return data;
                })
                .then(data => {
                    toast(data.message || 'Sêmen excluído com sucesso!', 'success');
                    this.semenLoadItems();
                })
                .catch(e => {
                    toast(e.message || 'Erro ao excluir sêmen.', 'error');
                });
        },



        // Paginação e busca de fêmeas
        femeasPage: 1,
        femeasLimit: 50,
        femeasTotal: 0,
        femeasLastPage: 1,
        femeasSearch: '',
        femeasFilterRaca: '',
        femeasFilterFornecedor: '',
        femeasFilterLocalizacao: '',
        femeasFilterBaia: '',
        femeasFilterDataInicial: '',
        femeasFilterDataFinal: '',

        // Paginação e busca de machos
        machosPage: 1,
        machosLimit: 50,
        machosTotal: 0,
        machosLastPage: 1,
        machosSearch: '',
        machosFilterLocalizacao: '',
        machosFilterBaia: '',

        // Filtros de cio
        cioFilterDataInicial: '',
        cioFilterDataFinal: '',
        cioFilterSearch: '',
        cioFilterNumero: '',
        // Edição rápida de cio
        openEditCio: false,
        editCioData: {
            id: '',
            data: '',
            peso: ''
        },
        openEditCioModal: false,


        openEditCio(row) {
            this.editCioData = {
                id: row.cio_id,
                femea_id: row.id_primaria + (row.id_secundaria ? ' / ' + row.id_secundaria : ''),
                data: this.formatBrDate(row.raw_data),
                peso: row.raw_peso || ''
            };
            this.openEditCioModal = true;
        },
        saveCioEdit() {
            if (!this.editCioData.data) {
                toast('Informe a data', 'error');
                return;
            }
            this.saving = true;
            const payload = {
                data: this.parseBrDate(this.editCioData.data),
                peso: this.editCioData.peso === '' ? null : Number(this.editCioData.peso)
            };
            fetch(`/api/plantel/femeas/cios/${this.editCioData.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(async r => {
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || 'Erro ao salvar alteração');
                return data;
            })
            .then(data => {
                toast('Registro atualizado com sucesso!', 'success');
                this.openEditCioModal = false;
                this.loadCioFemeas();
            })
            .catch(e => {
                toast(e.message, 'error');
            })
            .finally(() => this.saving = false);
        },
        deleteCioRecord(id) {
            if (!confirm('Tem certeza que deseja excluir este registro de cio?')) return;
            
            this.saving = true;
            fetch(`/api/gestacao/cio/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                }
            })
            .then(async r => {
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || 'Erro ao excluir registro');
                return data;
            })
            .then(data => {
                toast('Registro excluído com sucesso!', 'success');
                this.loadCioFemeas();
            })
            .catch(e => {
                toast(e.message, 'error');
            })
            .finally(() => { this.saving = false; });
        },
        deleteFemeaRecord(id) {
            if (!confirm('Tem certeza que deseja excluir esta fêmea permanentemente? Todos os registros relacionados (cios, coberturas, movimentos) tambêm serúo removidos.')) return;
            
            this.saving = true;
            fetch(`/api/plantel/femeas/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                }
            })
            .then(async r => {
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || 'Erro ao excluir fêmea');
                return data;
            })
            .then(data => {
                toast('Fêmea excluída com sucesso!', 'success');
                this.ensureFemeasAtivas();
            })
            .catch(e => {
                toast(e.message, 'error');
            })
            .finally(() => { this.saving = false; });
        },
        causasMorte: [],
        causasDescarte: [],
        causasVenda: [],
        femeasAtivas: [],
        machosAtivos: [],
        femeasMode: '',
        machosMode: '',
        racasLoaded: false,
        fornecedoresLoaded: false,
        utilitariosLoaded: false,
        dataDescarte: '',
        causaDescarteId: '',
        dataVenda: '',
        causaVendaId: '',
        valorVenda: '',
        pesoVenda: '',
        comprador: '',
        valorCompra: '',
        pesoCompra: '',
        pesoCio: '',
        caracteristicas: '',
        localizacao: '',
        baia: '',
        openNovaRaca: false,
        novaRacaNome: '',
        openNovoFornecedor: false,
        novoFornecedorNome: '',
        openNovaLocalizacao: false,
        novaLocalizacaoNome: '',
        openNovaBaia: false,
        novaBaiaNome: '',
        saving: false,
        houveCio: 'nao',
        dataUltimoCio: '',
        criterioMaturidadeMin: {{ $criterioMaturidadeMin ?? 151 }},
        idadeCompraDias() {
            if (!this.dataCompra || !this.dataNascimento) return 0;
            const compra = this.parseBrDate(this.dataCompra);
            const nascimento = this.parseBrDate(this.dataNascimento);
            if (!compra || !nascimento) return 0;
            const diff = new Date(compra).getTime() - new Date(nascimento).getTime();
            return Math.floor(diff / (1000 * 60 * 60 * 24));
        },
        showHouveCio() {
            return this.item === 'femeas' && 
                   this.mov === 'compra' && 
                   this.compraFemeasTipo === 'leitoa' && 
                   this.idadeCompraDias() >= this.criterioMaturidadeMin;
        },
        normalizeDateInput(value) {
            if (this.calendarType === '1000_dias') {
                return String(value || '').replace(/\D/g, '');
            }
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
            
            // Se estiver no modo 1000_dias, converter para dia PIG
            if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
                return toPigDay(v);
            }
            
            const [y, m, d] = v.split('-');
            return `${d}/${m}/${y}`;
        },
        get diasGestacao() {
            if (!(this.item === 'femeas' && this.mov === 'compra' && this.compraFemeasTipo === 'matriz_gestante')) return null;
            const cobIso = this.parseBrDate(this.dataCobertura);
            if (!cobIso) return null;
            
            const cycle = calculatePigCycle(cobIso + 'T00:00:00', new Date(), this.calendarType);
            return cycle ? cycle.totalDaysElapsed : null;
        },
        get itemLabel() {
            const map = { femeas: 'Fêmeas', machos: 'Machos', semen: 'Sêmen' };
            return map[this.item] ?? 'Item';
        },
        get movLabel() {
            const map = { compra: 'Compra', morte: 'Morte', descarte: 'Descarte', venda: 'Venda', cio: 'Cio' };
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
            if (this.mov === 'cio') return `Cio - ${this.itemLabel}`;
            if (this.item === 'semen') return `Cadastro de ${this.itemLabel}`;
            return `${this.itemLabel}`;
        },
        get modalTitle() {
            if (this.item === 'femeas' && this.mov === 'compra') {
                return `Incluir compra de ${this.subtipoLabel || 'fêmeas'}`;
            }
            if (this.item === 'femeas' && this.mov === 'cio') {
                return `Novo - Registro de Cio`;
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
            
            // Se for 1000 dias, usamos ciclo de 142 dias (114+21+7). 
            // Se for gregoriano, mantemos o padrúo de 21 dias (provavelmente ciclos de cio).
            const diasPorCiclo = this.calendarType === '1000_dias' ? 142 : 21;
            
            d.setDate(d.getDate() - Math.round(ciclos * diasPorCiclo));
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
            fetch('{{ url('/api/racas') }}')
                .then(r => r.json())
                .then(data => this.racas = data);
        },
        ensureFornecedores() {
            if (this.fornecedoresLoaded) return;
            this.fornecedoresLoaded = true;
            fetch('{{ url('/api/fornecedores') }}')
                .then(r => r.json())
                .then(data => this.fornecedores = data);
        },
        ensureUtilitarios() {
            if (this.utilitariosLoaded) return;
            this.utilitariosLoaded = true;
            fetch('{{ url('/api/utilitarios') }}')
                .then(r => r.json())
                .then(data => {
                    this.utilLocalizacoes = Array.isArray(data.localizacoes) ? data.localizacoes : [];
                    this.utilBaias = Array.isArray(data.baias) ? data.baias : [];
                });
        },
        ensureFemeasAtivas(force = false) {
            if (!force && this.femeasAtivas.length > 0 && this.femeasMode === 'ativas' && !this.femeasSearch && !this.femeasFilterRaca && !this.femeasFilterFornecedor && !this.femeasFilterLocalizacao && !this.femeasFilterBaia && !this.femeasFilterDataInicial && !this.femeasFilterDataFinal) return;
            this.lancamentosLoading = true;
            this.lancamentosError = '';
            const params = new URLSearchParams({
                limit: this.femeasLimit,
                page: this.femeasPage,
                search: this.femeasSearch,
                raca_id: this.femeasFilterRaca,
                fornecedor_id: this.femeasFilterFornecedor,
                localizacao: this.femeasFilterLocalizacao,
                baia: this.femeasFilterBaia,
                data_inicial: this.femeasFilterDataInicial,
                data_final: this.femeasFilterDataFinal
            });
            fetch(`${API_BASE_URL}/plantel/femeas?${params.toString()}`)
                .then(async r => {
                    const data = await r.json().catch(() => null);
                    if (!r.ok) throw new Error(data?.message || 'Falha ao carregar os dados. Verifique a conexúo e tente novamente.');
                    if (!data) throw new Error('Dados invílidos recebidos do servidor.');
                    return data;
                })
                .then(data => {
                    this.femeasAtivas = data.items || [];
                    this.femeasTotal = data.total || 0;
                    this.femeasLastPage = data.last_page || 1;
                    this.femeasMode = 'ativas';
                })
                .catch(e => {
                    this.lancamentosError = e.message;
                    this.femeasAtivas = [];
                    this.femeasTotal = 0;
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        ensureFemeasTodas() {
            if (this.femeasAtivas.length > 0 && this.femeasMode === 'todas' && !this.femeasSearch) return;
            this.lancamentosLoading = true;
            const params = new URLSearchParams({
                all: '1',
                limit: this.femeasLimit,
                page: this.femeasPage,
                search: this.femeasSearch
            });
            fetch(`${API_BASE_URL}/plantel/femeas?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    this.femeasAtivas = data.items || [];
                    this.femeasTotal = data.total || 0;
                    this.femeasLastPage = data.last_page || 1;
                    this.femeasMode = 'todas';
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        ensureMachosAtivos(force = false) {
            if (!force && this.machosAtivos.length > 0 && this.machosMode === 'ativos' && !this.machosSearch && !this.machosFilterLocalizacao && !this.machosFilterBaia) return;
            this.lancamentosLoading = true;
            this.lancamentosError = '';
            const params = new URLSearchParams({
                limit: this.machosLimit,
                page: this.machosPage,
                search: this.machosSearch,
                localizacao: this.machosFilterLocalizacao,
                baia: this.machosFilterBaia
            });
            fetch(`${API_BASE_URL}/plantel/machos?${params.toString()}`)
                .then(async r => {
                    const data = await r.json().catch(() => null);
                    if (!r.ok) throw new Error(data?.message || 'Falha ao carregar os dados. Verifique a conexúo e tente novamente.');
                    if (!data) throw new Error('Dados invílidos recebidos do servidor.');
                    return data;
                })
                .then(data => {
                    this.machosAtivos = data.items || [];
                    this.machosTotal = data.total || 0;
                    this.machosLastPage = data.last_page || 1;
                    this.machosMode = 'ativos';
                })
                .catch(e => {
                    this.lancamentosError = e.message;
                    this.machosAtivos = [];
                    this.machosTotal = 0;
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        ensureMachosTodos() {
            if (this.machosAtivos.length > 0 && this.machosMode === 'todos' && !this.machosSearch) return;
            this.lancamentosLoading = true;
            const params = new URLSearchParams({
                all: '1',
                limit: this.machosLimit,
                page: this.machosPage,
                search: this.machosSearch
            });
            fetch(`${API_BASE_URL}/plantel/machos?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    this.machosAtivos = data.items || [];
                    this.machosTotal = data.total || 0;
                    this.machosLastPage = data.last_page || 1;
                    this.machosMode = 'todos';
                })
                .finally(() => { this.lancamentosLoading = false; });
        },
        afterSaveReload() {
            if (this.item === 'femeas' && this.mov === 'compra') {
                this.comprasFemeasLoaded = false;
                this.loadComprasFemeas(true);
            }
            if (this.item === 'machos' && this.mov === 'compra') {
                this.comprasMachosLoaded = false;
                this.loadComprasMachos(true);
            }
            if (this.item === 'femeas' && this.mov === 'morte') this.loadMortesFemeas();
            if (this.item === 'machos' && this.mov === 'morte') this.loadMortesMachos();
            if (this.item === 'femeas' && this.mov === 'descarte') this.loadDescartesFemeas();
            if (this.item === 'machos' && this.mov === 'descarte') this.loadDescartesMachos();
            if (this.item === 'femeas' && this.mov === 'venda') this.loadVendasFemeas();
            if (this.item === 'machos' && this.mov === 'venda') this.loadVendasMachos();
        },
        ensureCausasMorte() {
            if (this.causasMorte.length > 0) return;
            fetch('{{ url('/api/plantel/causas-morte') }}')
                .then(r => r.json())
                .then(data => this.causasMorte = data);
        },
        ensureCausasDescarte() {
            if (this.causasDescarte.length > 0) return;
            fetch('{{ url('/api/plantel/causas-descarte') }}')
                .then(r => r.json())
                .then(data => this.causasDescarte = data);
        },
        ensureCausasVenda() {
            if (this.causasVenda.length > 0) return;
            fetch('{{ url('/api/plantel/causas-venda') }}')
                .then(r => r.json())
                .then(data => this.causasVenda = data);
        },
        loadComprasFemeas(force = false) {
            if (force) {
                this.femeasAtivas = [];
                this.femeasMode = '';
            }
            this.ensureFemeasAtivas();
        },
        loadPlantelFemeas() {
            if (!(this.item === 'femeas' && this.mov === 'plantel')) return;

            this.lancamentosLoading = true;
            this.lancamentosError = '';

            fetch('/api/plantel/femeas/plantel', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.plantelFemeas = data.items ?? [];
                    if (data.message) this.lancamentosError = data.message;
                })
                .catch(() => {
                    this.lancamentosError = 'Não foi possível carregar a listagem.';
                    this.plantelFemeas = [];
                })
                .finally(() => {
                    this.lancamentosLoading = false;
                });
        },
        loadComprasMachos(force = false) {
            if (force) {
                this.machosAtivos = [];
                this.machosMode = '';
            }
            this.ensureMachosAtivos();
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
        deleteLancamento(id) {
            const movimentoId = Number(id);
            if (!Number.isFinite(movimentoId) || movimentoId <= 0) return;
            if (!['morte', 'descarte', 'venda'].includes(this.mov)) return;
            if (!confirm('Excluir este lançamento?')) return;

            const csrf = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';

            let url = '';
            if (this.item === 'femeas') {
                url = '{{ route('admin.plantel.femeas.movimentos.destroy', ['id' => 0], false) }}';
            } else if (this.item === 'machos') {
                url = '{{ route('admin.plantel.machos.movimentos.destroy', ['id' => 0], false) }}';
            }

            url = url.replace(/0$/, String(movimentoId));

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            })
                .then(async (r) => {
                    const ct = String(r.headers.get('content-type') || '');
                    const data = ct.includes('application/json') ? await r.json().catch(() => ({})) : {};
                    if (!r.ok) throw new Error(String(data?.message || 'Erro ao excluir.'));
                    return data;
                })
                .then((data) => {
                    toast(data.message || 'Excluído com sucesso!', 'success');
                    if (this.item === 'femeas' && this.mov === 'morte') this.loadMortesFemeas();
                    if (this.item === 'femeas' && this.mov === 'descarte') this.loadDescartesFemeas();
                    if (this.item === 'femeas' && this.mov === 'venda') this.loadVendasFemeas();
                    if (this.item === 'machos' && this.mov === 'morte') this.loadMortesMachos();
                    if (this.item === 'machos' && this.mov === 'descarte') this.loadDescartesMachos();
                    if (this.item === 'machos' && this.mov === 'venda') this.loadVendasMachos();
                })
                .catch((e) => {
                    toast(e.message || 'Erro ao excluir.', 'error');
                });
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
            this.femeaCioId = '';
            this.dataCio = '';
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
            this.pesoCio = '';
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
                this.ensureUtilitarios();
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
        updateDateFromCycleDay(day) {
            if (!this.femeaCioId || !day) return;
            const femea = this.femeasAtivas.find(f => String(f.id) === String(this.femeaCioId));
            if (!femea || !femea.data_cobertura) {
                toast('Fêmea sem cobertura registrada para cílculo de ciclo', 'warning');
                return;
            }
            const base = new Date(femea.data_cobertura + 'T00:00:00');
            base.setDate(base.getDate() + Number(day));
            const yyyy = base.getFullYear();
            const mm = String(base.getMonth() + 1).padStart(2, '0');
            const dd = String(base.getDate()).padStart(2, '0');
            this.dataCio = `${dd}/${mm}/${yyyy}`;
            this.dataCio = `${dd}/${mm}/${yyyy}`;
        },
        openNovoCio() {
            this.resetForm();
            this.ensureFemeasAtivas();
            const now = new Date();
            const nowIso = now.toISOString().split('T')[0];
            this.dataCio = typeof toPigDay === 'function' ? toPigDay(nowIso) : '';
            this.diaCicloCio = '';
            this.openNovo = true;
        },
        loadCioFemeas() {
            this.lancamentosLoading = true;
            this.lancamentosError = '';
            
            const params = new URLSearchParams({
                limit: 200,
                search: this.cioFilterSearch,
                data_inicial: this.cioFilterDataInicial,
                data_final: this.cioFilterDataFinal,
                cio: this.cioFilterNumero
            });

            fetch(`/api/gestacao/cio?${params.toString()}`)
                .then(r => r.json())
                .then(data => {
                    this.cioFemeas = Array.isArray(data.items) ? data.items : [];
                    this.cioFemeasLoaded = true;
                })
                .catch(e => this.lancamentosError = 'Erro ao carregar registros de cio')
                .finally(() => this.lancamentosLoading = false);
        },
        saveRaca() {
            if (!this.novaRacaNome.trim()) {
                toast('Informe o nome da raça', 'error');
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
                toast(e.message || 'Erro ao cadastrar raça', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveFornecedor() {
            if (!this.novoFornecedorNome.trim()) {
                toast('Informe o nome do fornecedor', 'error');
                return;
            }

            this.saving = true;

            fetch('{{ url('/admin/fornecedores') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ nome: this.novoFornecedorNome })
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
                    this.fornecedorId = String(data.id);
                    this.novoFornecedorNome = '';
                    this.openNovoFornecedor = false;
                })
                .catch(e => {
                    toast(e.message || 'Erro ao cadastrar fornecedor', 'error');
                })
                .finally(() => { this.saving = false; });
        },
        saveLocalizacao() {
            if (!this.novaLocalizacaoNome.trim()) {
                toast('Informe a localização', 'error');
                return;
            }

            this.saving = true;

            fetch('/api/utilitarios/localizacoes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ nome: this.novaLocalizacaoNome })
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao salvar localização');
                    return data;
                })
                .then(data => {
                    this.utilLocalizacoes = Array.isArray(data.localizacoes) ? data.localizacoes : this.utilLocalizacoes;
                    this.localizacao = this.novaLocalizacaoNome.trim();
                    this.novaLocalizacaoNome = '';
                    this.openNovaLocalizacao = false;
                })
                .catch(e => {
                    toast(e.message || 'Erro ao salvar localização', 'error');
                })
                .finally(() => { this.saving = false; });
        },
        saveBaia() {
            if (!this.novaBaiaNome.trim()) {
                toast('Informe a baia', 'error');
                return;
            }

            this.saving = true;

            fetch('/api/utilitarios/baias', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ nome: this.novaBaiaNome })
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao salvar baia');
                    return data;
                })
                .then(data => {
                    this.utilBaias = Array.isArray(data.baias) ? data.baias : this.utilBaias;
                    this.baia = this.novaBaiaNome.trim();
                    this.novaBaiaNome = '';
                    this.openNovaBaia = false;
                })
                .catch(e => {
                    toast(e.message || 'Erro ao salvar baia', 'error');
                })
                .finally(() => { this.saving = false; });
        },
        saveCompraFemea() {
            this.saving = true;

            // Converter datas para aceitar dia PIG
            let dataCompraIso, dataNascimentoIso, dataCoberturaIso, dataUltimoCioIso;
            
            // Data de compra
            if (/^\d+$/.test(this.dataCompra)) {
                dataCompraIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataCompra) : null;
            } else {
                dataCompraIso = this.parseBrDate(this.dataCompra);
            }
            
            // Data de nascimento
            if (/^\d+$/.test(this.dataNascimento)) {
                dataNascimentoIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataNascimento) : null;
            } else {
                dataNascimentoIso = this.parseBrDate(this.dataNascimento);
            }
            
            // Data de cobertura
            if (/^\d+$/.test(this.dataCobertura)) {
                dataCoberturaIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataCobertura) : null;
            } else {
                dataCoberturaIso = this.parseBrDate(this.dataCobertura);
            }
            
            // Data do último cio
            if (/^\d+$/.test(this.dataUltimoCio)) {
                dataUltimoCioIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataUltimoCio) : null;
            } else {
                dataUltimoCioIso = this.parseBrDate(this.dataUltimoCio);
            }

            const payload = {
                tipo_compra: this.compraFemeasTipo,
                id_primaria: this.idPrimaria,
                id_secundaria: this.idSecundaria || null,
                data_compra: dataCompraIso,
                data_nascimento: dataNascimentoIso || null,
                ciclos_ate_compra: this.ciclosAteCompra === '' ? null : Number(this.ciclosAteCompra),
                data_cobertura: dataCoberturaIso || null,
                raca_id: this.racaId,
                valor_compra: this.valorCompra === '' ? null : Number(this.valorCompra),
                peso_compra: this.pesoCompra === '' ? null : Number(this.pesoCompra),
                fornecedor_id: this.fornecedorId || null,
                caracteristicas: this.caracteristicas || null,
                localizacao: this.localizacao || null,
                baia: this.baia || null,
                houve_cio: this.houveCio,
                data_ultimo_cio: dataUltimoCioIso || null,
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
                toast(data.message || 'Compra registrada com sucesso!', 'success');
                this.openNovo = false;
                this.comprasFemeasLoaded = false;
                this.femeasAtivas = [];
                this.femeasMode = '';
                this.loadComprasFemeas(true);
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveCompraFemeaContinuar() {
            this.saving = true;

            // Converter datas para aceitar dia PIG
            let dataCompraIso, dataNascimentoIso, dataCoberturaIso, dataUltimoCioIso;
            
            // Data de compra
            if (/^\d+$/.test(this.dataCompra)) {
                dataCompraIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataCompra) : null;
            } else {
                dataCompraIso = this.parseBrDate(this.dataCompra);
            }
            
            // Data de nascimento
            if (/^\d+$/.test(this.dataNascimento)) {
                dataNascimentoIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataNascimento) : null;
            } else {
                dataNascimentoIso = this.parseBrDate(this.dataNascimento);
            }
            
            // Data de cobertura
            if (/^\d+$/.test(this.dataCobertura)) {
                dataCoberturaIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataCobertura) : null;
            } else {
                dataCoberturaIso = this.parseBrDate(this.dataCobertura);
            }
            
            // Data do último cio
            if (/^\d+$/.test(this.dataUltimoCio)) {
                dataUltimoCioIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataUltimoCio) : null;
            } else {
                dataUltimoCioIso = this.parseBrDate(this.dataUltimoCio);
            }

            const payload = {
                tipo_compra: this.compraFemeasTipo,
                id_primaria: this.idPrimaria,
                id_secundaria: this.idSecundaria || null,
                data_compra: dataCompraIso,
                data_nascimento: dataNascimentoIso || null,
                ciclos_ate_compra: this.ciclosAteCompra === '' ? null : Number(this.ciclosAteCompra),
                data_cobertura: dataCoberturaIso || null,
                raca_id: this.racaId,
                valor_compra: this.valorCompra === '' ? null : Number(this.valorCompra),
                peso_compra: this.pesoCompra === '' ? null : Number(this.pesoCompra),
                fornecedor_id: this.fornecedorId || null,
                caracteristicas: this.caracteristicas || null,
                localizacao: this.localizacao || null,
                baia: this.baia || null,
                houve_cio: this.houveCio,
                data_ultimo_cio: dataUltimoCioIso || null,
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
                toast(data.message || 'Compra registrada com sucesso!', 'success');
                // Limpar apenas IDs e manter modal aberto
                this.idPrimaria = '';
                this.idSecundaria = '';
                this.pesoCompra = '';
                this.valorCompra = '';
                this.comprasFemeasLoaded = false;
                this.loadComprasFemeas(true);
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveMorteFemea() {
            this.saving = true;

            // Converter data para aceitar dia PIG
            let dataMorteIso;
            
            // Data da morte
            if (/^\d+$/.test(this.dataMorte)) {
                dataMorteIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataMorte) : null;
            } else {
                dataMorteIso = this.parseBrDate(this.dataMorte);
            }

            const payload = {
                femea_id: this.femeaMorteId,
                data_morte: dataMorteIso,
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
                toast(data.message || 'Morte registrada com sucesso!', 'success');
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveMorteMacho() {
            this.saving = true;

            // Converter data para aceitar dia PIG
            let dataMorteIso;
            
            // Data da morte
            if (/^\d+$/.test(this.dataMorte)) {
                dataMorteIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataMorte) : null;
            } else {
                dataMorteIso = this.parseBrDate(this.dataMorte);
            }

            const payload = {
                macho_id: this.femeaMorteId,
                data_morte: dataMorteIso,
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
                toast(data.message || 'Morte registrada com sucesso!', 'success');
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveDescarteFemea() {
            this.saving = true;

            // Converter data para aceitar dia PIG
            let dataDescarteIso;
            
            // Data do descarte
            if (/^\d+$/.test(this.dataDescarte)) {
                dataDescarteIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataDescarte) : null;
            } else {
                dataDescarteIso = this.parseBrDate(this.dataDescarte);
            }

            const payload = {
                femea_id: this.femeaMorteId,
                data_descarte: dataDescarteIso,
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
                toast(data.message || 'Descarte registrado com sucesso!', 'success');
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveDescarteMacho() {
            this.saving = true;

            // Converter data para aceitar dia PIG
            let dataDescarteIso;
            
            // Data do descarte
            if (/^\d+$/.test(this.dataDescarte)) {
                dataDescarteIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataDescarte) : null;
            } else {
                dataDescarteIso = this.parseBrDate(this.dataDescarte);
            }

            const payload = {
                macho_id: this.femeaMorteId,
                data_descarte: dataDescarteIso,
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
                toast(data.message || 'Descarte registrado com sucesso!', 'success');
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveVendaFemea() {
            this.saving = true;

            // Converter data para aceitar dia PIG
            let dataVendaIso;
            
            // Data da venda
            if (/^\d+$/.test(this.dataVenda)) {
                dataVendaIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataVenda) : null;
            } else {
                dataVendaIso = this.parseBrDate(this.dataVenda);
            }

            const payload = {
                femea_id: this.femeaMorteId,
                data_venda: dataVendaIso,
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
                toast(data.message || 'Venda registrada com sucesso!', 'success');
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveCompraMacho() {
            this.saving = true;

            // Converter datas para aceitar dia PIG
            let dataCompraIso, dataNascimentoIso;
            
            // Data de compra
            if (/^\d+$/.test(this.dataCompra)) {
                dataCompraIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataCompra) : null;
            } else {
                dataCompraIso = this.parseBrDate(this.dataCompra);
            }
            
            // Data de nascimento
            if (/^\d+$/.test(this.dataNascimento)) {
                dataNascimentoIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataNascimento) : null;
            } else {
                dataNascimentoIso = this.parseBrDate(this.dataNascimento);
            }

            const payload = {
                id_primaria: this.idPrimaria,
                id_secundaria: this.idSecundaria || null,
                data_compra: dataCompraIso,
                data_nascimento: dataNascimentoIso || null,
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
                toast(data.message || 'Compra registrada com sucesso!', 'success');
                this.openNovo = false;
                this.comprasMachosLoaded = false;
                this.machosAtivos = [];
                this.machosMode = '';
                this.loadComprasMachos(true);
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveVendaMacho() {
            this.saving = true;

            // Converter data para aceitar dia PIG
            let dataVendaIso;
            
            // Data da venda
            if (/^\d+$/.test(this.dataVenda)) {
                dataVendaIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataVenda) : null;
            } else {
                dataVendaIso = this.parseBrDate(this.dataVenda);
            }

            const payload = {
                macho_id: this.femeaMorteId,
                data_venda: dataVendaIso,
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
                toast(data.message || 'Venda registrada com sucesso!', 'success');
                this.openNovo = false;
                this.afterSaveReload();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar', 'error');
            })
            .finally(() => { this.saving = false; });
        },
        saveCioFemea() {
            if (!this.femeaCioId) {
                toast('Selecione uma fêmea', 'error');
                return;
            }

            let dataIso;
            // Tentar converter como dia PIG primeiro
            if (/^\d+$/.test(this.dataCio)) {
                // É um n??mero, tratar como dia PIG
                dataIso = typeof pigDayToDate === 'function' ? pigDayToDate(this.dataCio) : null;
            } else {
                // Tentar converter como data gregoriana
                dataIso = this.parseBrDate(this.dataCio);
            }
            
            if (!dataIso) {
                toast('Data invílida', 'error');
                return;
            }

            // Verificar se está fora dos critérios antes de pedir confirmação
            fetch(`/api/gestacao/cio/verificar-criterios?femea_id=${this.femeaCioId}&data=${dataIso}`)
                .then(response => response.json())
                .then(result => {
                    if (!result.dentro_critérios) {
                        // Está fora dos critérios, pedir confirmação
                        if (!confirm(`Atenção: Este registro está fora dos critérios estabelecidos.\n\nMotivo: ${result.motivo || 'Não especificado'}\n\nDeseja registrar mesmo assim?`)) {
                            return;
                        }
                    }
                    
                    // Prosseguir com o salvamento
                    this.executarSalvarCio(dataIso);
                })
                .catch(error => {
                    // Se não conseguir verificar critérios, salva normalmente
                    console.error('Erro ao verificar critérios:', error);
                    this.executarSalvarCio(dataIso);
                });
        },
        executarSalvarCio(dataIso) {
            this.saving = true;

            fetch('/api/gestacao/cio', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({
                    femea_id: this.femeaCioId,
                    data: dataIso,
                    peso: this.pesoCio === '' ? null : Number(this.pesoCio)
                })
            })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data?.message || 'Erro ao salvar cio');
                return data;
            })
            .then(data => {
                toast(data.message || 'Cio registrado com sucesso!', 'success');
                this.openNovo = false;
                this.loadCioFemeas();
            })
            .catch(e => {
                toast(e.message || 'Erro ao salvar cio', 'error');
            })
            .finally(() => { this.saving = false; });
        },
    }" x-init="loadComprasFemeas(); loadComprasMachos(true); $watch('item', () => { if(item === 'femeas' && mov === 'compra') loadComprasFemeas(); }); $watch('mov', () => { if(item === 'femeas' && mov === 'compra') loadComprasFemeas(); }); window.addEventListener('femea-updated', () => { if(item === 'femeas' && mov === 'compra') loadComprasFemeas(true); if(item === 'femeas' && mov === 'morte') loadMortesFemeas(); if(item === 'femeas' && mov === 'descarte') loadDescartesFemeas(); if(item === 'femeas' && mov === 'venda') loadVendasFemeas(); });" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="text-center">
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lançamentos</h6>
                    <div class="text-sm text-gray-500 mt-1">Escolha o item (macho, fêmea ou sêmen) e depois a movimentação.</div>
                </div>
            </div>
            <div class="p-6">
                <div class="flex flex-col gap-6">
                    <div class="flex justify-center items-center gap-2 bg-gray-100 p-1.5 rounded-xl overflow-x-auto max-w-full">
                        <button type="button" @click="item = 'femeas'; mov = 'compra'; compraFemeasTipo = 'leitoa';" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="item === 'femeas' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'" data-tour="femeas">
                            <i class="fa-solid fa-piggy-bank text-primary-600 transition-colors duration-300" :class="item === 'femeas' ? 'text-primary-600' : 'text-gray-600'"></i> Fêmeas
                        </button>
                        <button type="button" @click="item = 'machos'; mov = 'compra'; loadComprasMachos()" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="item === 'machos' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                            <i class="fa-solid fa-piggy-bank text-primary-600 transition-colors duration-300" :class="item === 'machos' ? 'text-primary-600' : 'text-gray-600'"></i> Machos
                        </button>
                        <button type="button" @click="item = 'semen'; mov = null; semenOpenCreate = false; semenInit();" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="item === 'semen' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                            <i class="fa-solid fa-vial text-primary-600 transition-colors duration-300" :class="item === 'semen' ? 'text-primary-600' : 'text-gray-600'"></i> Sêmen
                        </button>
                    </div>

                    <div class="flex flex-wrap justify-center items-center gap-2" x-show="item !== 'semen'" x-cloak>
                        <button x-show="item === 'femeas'" x-cloak type="button" @click="mov = 'plantel'; loadPlantelFemeas()" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border" :class="mov === 'plantel' ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                            <i class="fa-solid fa-list w-4"></i> Plantel
                        </button>
                        <button type="button" @click="mov = 'compra'; if(item === 'femeas') loadComprasFemeas(); else loadComprasMachos();" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border" :class="mov === 'compra' ? 'bg-primary-50 border-primary-200 text-primary-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'" data-tour="movimento-compra">
                            <i class="fa-solid fa-cart-shopping w-4"></i> Compra
                        </button>
                        <button x-show="item === 'femeas'" x-cloak type="button" @click="mov = 'cio'; loadCioFemeas()" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border" :class="mov === 'cio' ? 'bg-pink-50 border-pink-200 text-pink-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                            <i class="fa-solid fa-venus w-4"></i> Cio
                        </button>
                        <button type="button" @click="mov = 'morte'; if(item === 'femeas') loadMortesFemeas(); else loadMortesMachos();" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border" :class="mov === 'morte' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                            <i class="fa-solid fa-skull-crossbones w-4"></i> Morte
                        </button>
                        <button type="button" @click="mov = 'descarte'; if(item === 'femeas') loadDescartesFemeas(); else loadDescartesMachos();" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border" :class="mov === 'descarte' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                            <i class="fa-solid fa-ban w-4"></i> Descarte
                        </button>
                        <button type="button" @click="mov = 'venda'; if(item === 'femeas') loadVendasFemeas(); else loadVendasMachos();" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border" :class="mov === 'venda' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                            <i class="fa-solid fa-hand-holding-dollar w-4"></i> Venda
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="text-center">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider" x-text="tipoLabel"></div>
                    <div class="text-sm text-gray-500 mt-1">
                        <span>Listagem do tipo selecionado.</span>
                        <span x-show="item === 'femeas' && mov === 'compra'" x-cloak class="ml-1">Selecione o subtipo para cadastrar.</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <template x-if="item === 'femeas' && mov === 'compra'">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" @click="openNovoForm('leitoa')" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-tour="leitoa">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-pink-50 text-pink-600">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </span>
                                Leitoa
                            </button>
                            <button type="button" @click="openNovoForm('matriz_vazia')" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-tour="matriz-vazia">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 text-sky-600">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </span>
                                Matriz vazia
                            </button>
                            <button type="button" @click="openNovoForm('matriz_gestante')" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-tour="matriz-gestante">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-violet-50 text-violet-600">
                                    <i class="fa-solid fa-piggy-bank"></i>
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
                    <template x-if="item === 'femeas' && mov === 'cio'">
                        <button type="button" @click="openNovoCio()" title="Registrar cio" class="inline-flex items-center gap-2 rounded-full border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-pink-50 text-pink-600">
                                <i class="fa-solid fa-venus"></i>
                            </span>
                            Cio de Leitoa
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
                    <template x-if="item === 'semen'">
                        <button type="button" @click="semenOpenCreate = true; semenResetForm();" title="Cadastrar sêmen" class="inline-flex items-center justify-center w-11 h-11 rounded-2xl border border-transparent shadow-sm bg-orange-600 text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                            <i class="fa-solid fa-vial"></i>
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
                <template x-if="item === 'semen'">
                    <div class="space-y-4">
                        <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-3">
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Buscar</label>
                                    <input type="text" x-model="semenSearch" @input="semenPage = 1; semenLoadItems()" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" placeholder="ID, raça, fornecedor...">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Raça</label>
                                    <select x-model="semenRacaId" @change="semenPage = 1; semenLoadItems()" class="block w-full pl-2 pr-8 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Todas</option>
                                        <template x-for="r in semenRacas" :key="r.id">
                                            <option :value="r.id" x-text="r.nome"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Fornecedor</label>
                                    <select x-model="semenFornecedorId" @change="semenPage = 1; semenLoadItems()" class="block w-full pl-2 pr-8 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Todos</option>
                                        <template x-for="f in semenFornecedores" :key="f.id">
                                            <option :value="f.id" x-text="f.nome"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data inicial</label>
                                    <input type="date" x-model="semenDataInicial" @change="semenPage = 1; semenLoadItems()" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data final</label>
                                    <input type="date" x-model="semenDataFinal" @change="semenPage = 1; semenLoadItems()" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-100 rounded-2xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Primíria</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Secundíria</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Raça</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Data compra</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Valor</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Fornecedor</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr x-show="semenLoading" x-cloak>
                                        <td colspan="7" class="px-4 py-10 text-sm text-gray-500 text-center italic">Carregando...</td>
                                    </tr>
                                    <template x-for="row in semenItems" :key="row.id">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <button @click="semenDelete(row.id)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Excluir sêmen">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-primary-700" x-text="row.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="row.id_secundaria || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="row.raca_nome || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="row.data_compra ? new Date(row.data_compra).toLocaleDateString('pt-BR') : '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="row.valor_compra ? ('R$ ' + Number(row.valor_compra).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="row.fornecedor_nome || '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!semenLoading && semenItems.length === 0" x-cloak>
                                        <td colspan="7" class="px-4 py-12 text-sm text-gray-500 text-center italic">Nenhum registro de sêmen encontrado.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Mostrando <span x-text="semenItems.length"></span> de <span x-text="semenTotal"></span> registros
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="semenPage = Math.max(1, semenPage - 1); semenLoadItems()" :disabled="semenPage <= 1" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <span class="text-sm text-gray-500 px-3">
                                    Pígina <span x-text="semenPage"></span> de <span x-text="semenPages"></span>
                                </span>
                                <button @click="semenPage = Math.min(semenPages, semenPage + 1); semenLoadItems()" :disabled="semenPage >= semenPages" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>

                        <div x-show="semenOpenCreate" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="semenOpenCreate" @click="semenOpenCreate = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 transition-opacity" aria-hidden="true"></div>
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                <div x-show="semenOpenCreate" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-visible shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                                    <div class="bg-white px-6 pt-6 pb-4">
                                        <div class="flex items-start justify-between">
                                            <h3 class="text-lg leading-6 font-semibold text-gray-900">Novo Sêmen</h3>
                                            <button type="button" @click="semenOpenCreate = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">ID Primíria *</label>
                                                <input type="text" x-model="semenForm.id_primaria" required class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900" placeholder="Ex: SEM001">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">ID Secundíria</label>
                                                <input type="text" x-model="semenForm.id_secundaria" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900" placeholder="Ex: SEC001">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Raça</label>
                                                <select x-model="semenForm.raca_id" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900">
                                                    <option value="">Selecione...</option>
                                                    <template x-for="r in semenRacas" :key="r.id">
                                                        <option :value="r.id" x-text="r.nome"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data produção</label>
                                                <div class="mt-1 relative">
                                                    <input type="text"
                                                           x-model="semenForm.data_nascimento"
                                                           @input="semenForm.data_nascimento = $event.target.value.replace(/\D/g, '')"
                                                           @click="activePicker = 'semen_nascimento'"
                                                           class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10 bg-white text-gray-900"
                                                           placeholder="Dia PIG"
                                                           inputmode="numeric"
                                                           autocomplete="off">
                                                    <button type="button" @click="activePicker = 'semen_nascimento'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>

                                                    <div x-show="activePicker === 'semen_nascimento'" x-cloak class="absolute z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 left-0 right-0 mx-auto sm:left-auto sm:right-0"
                                                         @click.away="activePicker = null">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                                                                <i class="fa-solid fa-chevron-left"></i>
                                                            </button>
                                                            <span class="font-medium text-gray-900" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
                                                            <button type="button" @click.stop="nextCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                                                                <i class="fa-solid fa-chevron-right"></i>
                                                            </button>
                                                        </div>

                                                        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                                                            <template x-for="day in ['D','S','T','Q','Q','S','S']">
                                                                <div class="font-medium text-gray-500 py-1" x-text="day"></div>
                                                            </template>
                                                        </div>

                                                        <div class="grid grid-cols-7 gap-1">
                                                            <template x-for="day in getCalendarDays()" :key="day.date">
                                                                <div class="text-center">
                                                                    <button type="button"
                                                                            @click.stop="selectCalendarDate(day.date)"
                                                                            :class="day.isCurrentMonth ? 'text-gray-900 hover:bg-primary-50' : 'text-gray-400'"
                                                                            :disabled="!day.isCurrentMonth"
                                                                            class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                            x-text="day.day">
                                                                    </button>
                                                                    <div class="text-[8px] text-gray-500 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                </div>
                                                            </template>
                                                        </div>

                                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                                            <div class="text-xs text-gray-500">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + semenForm.data_nascimento"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data Compra *</label>
                                                <div class="mt-1 relative">
                                                    <input type="text"
                                                           x-model="semenForm.data_compra"
                                                           @input="semenForm.data_compra = $event.target.value.replace(/\D/g, '')"
                                                           @click="activePicker = 'semen_compra'"
                                                           class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10 bg-white text-gray-900"
                                                           placeholder="Dia PIG"
                                                           inputmode="numeric"
                                                           autocomplete="off">
                                                    <button type="button" @click="activePicker = 'semen_compra'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>

                                                    <div x-show="activePicker === 'semen_compra'" x-cloak class="absolute z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 left-0 right-0 mx-auto sm:left-auto sm:right-0"
                                                         @click.away="activePicker = null">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                                                                <i class="fa-solid fa-chevron-left"></i>
                                                            </button>
                                                            <span class="font-medium text-gray-900" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
                                                            <button type="button" @click.stop="nextCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                                                                <i class="fa-solid fa-chevron-right"></i>
                                                            </button>
                                                        </div>

                                                        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                                                            <template x-for="day in ['D','S','T','Q','Q','S','S']">
                                                                <div class="font-medium text-gray-500 py-1" x-text="day"></div>
                                                            </template>
                                                        </div>

                                                        <div class="grid grid-cols-7 gap-1">
                                                            <template x-for="day in getCalendarDays()" :key="day.date">
                                                                <div class="text-center">
                                                                    <button type="button"
                                                                            @click.stop="selectCalendarDate(day.date)"
                                                                            :class="day.isCurrentMonth ? 'text-gray-900 hover:bg-primary-50' : 'text-gray-400'"
                                                                            :disabled="!day.isCurrentMonth"
                                                                            class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                            x-text="day.day">
                                                                    </button>
                                                                    <div class="text-[8px] text-gray-500 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                </div>
                                                            </template>
                                                        </div>

                                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                                            <div class="text-xs text-gray-500">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + semenForm.data_compra"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Validade</label>
                                                <div class="mt-1 relative">
                                                    <input type="text"
                                                           x-model="semenForm.validade"
                                                           @input="semenForm.validade = $event.target.value.replace(/\D/g, '')"
                                                           @click="activePicker = 'semen_validade'"
                                                           class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10 bg-white text-gray-900"
                                                           placeholder="Dia PIG"
                                                           inputmode="numeric"
                                                           autocomplete="off">
                                                    <button type="button" @click="activePicker = 'semen_validade'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>

                                                    <div x-show="activePicker === 'semen_validade'" x-cloak class="absolute z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 left-0 right-0 mx-auto sm:left-auto sm:right-0"
                                                         @click.away="activePicker = null">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                                                                <i class="fa-solid fa-chevron-left"></i>
                                                            </button>
                                                            <span class="font-medium text-gray-900" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
                                                            <button type="button" @click.stop="nextCalendarMonth()" class="p-1 hover:bg-gray-100 rounded">
                                                                <i class="fa-solid fa-chevron-right"></i>
                                                            </button>
                                                        </div>

                                                        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                                                            <template x-for="day in ['D','S','T','Q','Q','S','S']">
                                                                <div class="font-medium text-gray-500 py-1" x-text="day"></div>
                                                            </template>
                                                        </div>

                                                        <div class="grid grid-cols-7 gap-1">
                                                            <template x-for="day in getCalendarDays()" :key="day.date">
                                                                <div class="text-center">
                                                                    <button type="button"
                                                                            @click.stop="selectCalendarDate(day.date)"
                                                                            :class="day.isCurrentMonth ? 'text-gray-900 hover:bg-primary-50' : 'text-gray-400'"
                                                                            :disabled="!day.isCurrentMonth"
                                                                            class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                            x-text="day.day">
                                                                    </button>
                                                                    <div class="text-[8px] text-gray-500 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                </div>
                                                            </template>
                                                        </div>

                                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                                            <div class="text-xs text-gray-500">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + semenForm.validade"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Valor Compra</label>
                                                <input type="number" x-model="semenForm.valor_compra" step="0.01" min="0" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900" placeholder="0,00">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                                                <select x-model="semenForm.fornecedor_id" class="mt-1 w-full shadow-sm text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 bg-white text-gray-900">
                                                    <option value="">Selecione...</option>
                                                    <template x-for="f in semenFornecedores" :key="f.id">
                                                        <option :value="f.id" x-text="f.nome"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                                        <button type="button" @click="semenSave()" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto">
                                            Salvar
                                        </button>
                                        <button type="button" @click="semenOpenCreate = false" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'femeas' && mov === 'compra'">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-bold text-gray-700 uppercase tracking-wider">Status das Fêmeas (Última Operação)</div>
                        </div>

                        <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-3 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data inicial</label>
                                    <input type="text" x-model="femeasFilterDataInicial" @input="femeasFilterDataInicial = normalizeDateInput($event.target.value)" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" :placeholder="calendarType === '1000_dias' ? '' : 'DD/MM/AAAA'" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data final</label>
                                    <input type="text" x-model="femeasFilterDataFinal" @input="femeasFilterDataFinal = normalizeDateInput($event.target.value)" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" :placeholder="calendarType === '1000_dias' ? '' : 'DD/MM/AAAA'" inputmode="numeric" autocomplete="off">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Buscar ID</label>
                                    <div class="relative">
                                        <input type="text" x-model="femeasSearch" @keydown.enter="femeasPage = 1; ensureFemeasAtivas(true)" class="block w-full pl-8 pr-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500">
                                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass text-gray-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Raça</label>
                                    <select x-model="femeasFilterRaca" class="block w-full pl-2 pr-8 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Todas</option>
                                        <template x-for="r in racas" :key="r.id">
                                            <option :value="r.id" x-text="r.nome"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="femeasPage = 1; ensureFemeasAtivas(true)" class="w-full inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        <i class="fa-solid fa-filter mr-1 text-[10px]"></i>
                                        Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="lancamentosError" class="mb-4 bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="lancamentosError" x-cloak></div>

                        <div class="overflow-x-auto border border-gray-100 rounded-2xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-20">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase" x-text="calendarType === '1000_dias' ? 'Dia da compra' : 'Data'"></th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Primíria</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Secundíria</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Raça</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Idade</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Fornecedor</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Peso</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="f in femeasAtivas" :key="f.id">
                                        <tr class="hover:bg-gray-50 transition-colors group">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-1">
                                                    <button @click.stop="openEdit(f)" class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 transition-all" title="Editar fêmea">
                                                        <i class="fa-solid fa-pencil"></i>
                                                    </button>
                                                    <button @click.stop="deleteFemeaRecord(f.id)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Excluir fêmea">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-mono text-gray-600" x-text="f.ultima_data_formatada"></td>
                                            <td class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase" x-text="f.tipo"></td>
                                            <td class="px-4 py-3 cursor-pointer" @click="window.location.href = `/admin/plantel/femeas/${f.id}`">
                                                <div class="flex items-center gap-2">
                                                    <div class="text-sm font-bold text-primary-700 group-hover:underline" x-text="f.id_primaria"></div>
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-primary-400"></i>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 cursor-pointer" @click="window.location.href = `/admin/plantel/femeas/${f.id}`" x-text="f.id_secundaria || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="f.raca"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="f.idade_dias !== null ? f.idade_dias + ' d' : '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="f.fornecedor"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="f.peso_atual ? (Number(f.peso_atual).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg') : '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && femeasAtivas.length === 0" x-cloak>
                                        <td colspan="9" class="px-4 py-12 text-sm text-gray-500 text-center italic">
                                            <div class="flex flex-col items-center justify-center space-y-2">
                                                <i class="fa-solid fa-magnifying-glass text-3xl text-gray-200"></i>
                                                <span>Nenhum registro encontrado. Tente ajustar os filtros ou clique em "Filtrar".</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 border border-gray-100 rounded-2xl" x-show="femeasTotal > 0">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <button @click="if(femeasPage > 1) { femeasPage--; ensureFemeasAtivas(); }" :disabled="femeasPage === 1" class="relative inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Anterior</button>
                                <button @click="if(femeasPage < femeasLastPage) { femeasPage++; ensureFemeasAtivas(); }" :disabled="femeasPage === femeasLastPage" class="relative ml-3 inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Pr??ximo</button>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando <span class="font-medium" x-text="((femeasPage - 1) * femeasLimit) + 1"></span> a <span class="font-medium" x-text="Math.min(femeasPage * femeasLimit, femeasTotal)"></span> de <span class="font-medium" x-text="femeasTotal"></span> resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm" aria-label="Pagination">
                                        <button @click="femeasPage = 1; ensureFemeasAtivas()" :disabled="femeasPage === 1" class="relative inline-flex items-center rounded-l-xl px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Primeira</span>
                                            <i class="fa-solid fa-angles-left text-xs"></i>
                                        </button>
                                        <button @click="if(femeasPage > 1) { femeasPage--; ensureFemeasAtivas(); }" :disabled="femeasPage === 1" class="relative inline-flex items-center px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Anterior</span>
                                            <i class="fa-solid fa-chevron-left text-xs"></i>
                                        </button>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">
                                            Píg. <span x-text="femeasPage"></span> de <span x-text="femeasLastPage"></span>
                                        </span>
                                        <button @click="if(femeasPage < femeasLastPage) { femeasPage++; ensureFemeasAtivas(); }" :disabled="femeasPage === femeasLastPage" class="relative inline-flex items-center px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Pr??xima</span>
                                            <i class="fa-solid fa-chevron-right text-xs"></i>
                                        </button>
                                        <button @click="femeasPage = femeasLastPage; ensureFemeasAtivas()" :disabled="femeasPage === femeasLastPage" class="relative inline-flex items-center rounded-r-xl px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Última</span>
                                            <i class="fa-solid fa-angles-right text-xs"></i>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'machos' && mov === 'compra'">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-bold text-gray-700 uppercase tracking-wider">Status dos Machos</div>
                        </div>

                        <div class="bg-gray-50/50 border border-gray-100 rounded-2xl p-4 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Buscar ID</label>
                                    <div class="relative">
                                        <input type="text" x-model="machosSearch" @keydown.enter="machosPage = 1; ensureMachosAtivos(true)" class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500" placeholder="ID prim./sec.">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Localização</label>
                                    <input type="text" x-model="machosFilterLocalizacao" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Galpão A">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Baia</label>
                                    <input type="text" x-model="machosFilterBaia" class="block w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 12">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Limite</label>
                                    <select x-model="machosLimit" class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary-500 focus:border-primary-500">
                                        <option value="10">10 por píg.</option>
                                        <option value="25">25 por píg.</option>
                                        <option value="50">50 por píg.</option>
                                        <option value="100">100 por píg.</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="machosPage = 1; ensureMachosAtivos(true)" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-semibold rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        <i class="fa-solid fa-filter mr-2 text-xs"></i>
                                        Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="lancamentosError" class="mb-4 bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="lancamentosError" x-cloak></div>

                        <div class="overflow-x-auto border border-gray-100 rounded-2xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Primíria</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">ID Secundíria</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Localização</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Baia</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="m in machosAtivos" :key="m.id">
                                        <tr class="hover:bg-gray-50 transition-colors group">
                                            <td class="px-4 py-3 text-sm font-bold text-primary-700" x-text="m.id_primaria"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="m.id_secundaria || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="m.localizacao || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="m.baia || '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && machosAtivos.length === 0" x-cloak>
                                        <td colspan="4" class="px-4 py-12 text-sm text-gray-500 text-center italic">
                                            <div class="flex flex-col items-center justify-center space-y-2">
                                                <i class="fa-solid fa-magnifying-glass text-3xl text-gray-200"></i>
                                                <span>Nenhum registro encontrado. Tente ajustar os filtros ou clique em "Filtrar".</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 border border-gray-100 rounded-2xl" x-show="machosTotal > 0">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <button @click="if(machosPage > 1) { machosPage--; ensureMachosAtivos(); }" :disabled="machosPage === 1" class="relative inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Anterior</button>
                                <button @click="if(machosPage < machosLastPage) { machosPage++; ensureMachosAtivos(); }" :disabled="machosPage === machosLastPage" class="relative ml-3 inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Pr??ximo</button>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando <span class="font-medium" x-text="((machosPage - 1) * machosLimit) + 1"></span> a <span class="font-medium" x-text="Math.min(machosPage * machosLimit, machosTotal)"></span> de <span class="font-medium" x-text="machosTotal"></span> resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm" aria-label="Pagination">
                                        <button @click="machosPage = 1; ensureMachosAtivos()" :disabled="machosPage === 1" class="relative inline-flex items-center rounded-l-xl px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Primeira</span>
                                            <i class="fa-solid fa-angles-left text-xs"></i>
                                        </button>
                                        <button @click="if(machosPage > 1) { machosPage--; ensureMachosAtivos(); }" :disabled="machosPage === 1" class="relative inline-flex items-center px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Anterior</span>
                                            <i class="fa-solid fa-chevron-left text-xs"></i>
                                        </button>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 focus:outline-offset-0">
                                            Píg. <span x-text="machosPage"></span> de <span x-text="machosLastPage"></span>
                                        </span>
                                        <button @click="if(machosPage < machosLastPage) { machosPage++; ensureMachosAtivos(); }" :disabled="machosPage === machosLastPage" class="relative inline-flex items-center px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Pr??xima</span>
                                            <i class="fa-solid fa-chevron-right text-xs"></i>
                                        </button>
                                        <button @click="machosPage = machosLastPage; ensureMachosAtivos()" :disabled="machosPage === machosLastPage" class="relative inline-flex items-center rounded-r-xl px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
                                            <span class="sr-only">Última</span>
                                            <i class="fa-solid fa-angles-right text-xs"></i>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="item === 'femeas' && mov === 'cio'">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-700">Listagem de registros de cio</div>
                            <div class="text-xs text-gray-500" x-show="lancamentosLoading">Carregando...</div>
                        </div>

                        <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-3 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data inicial</label>
                                    <input type="text" x-model="cioFilterDataInicial" @input="cioFilterDataInicial = normalizeDateInput($event.target.value)" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" :placeholder="calendarType === '1000_dias' ? '' : 'DD/MM/AAAA'" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Data final</label>
                                    <input type="text" x-model="cioFilterDataFinal" @input="cioFilterDataFinal = normalizeDateInput($event.target.value)" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" :placeholder="calendarType === '1000_dias' ? '' : 'DD/MM/AAAA'" inputmode="numeric" autocomplete="off">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Buscar Fêmea</label>
                                    <input type="text" x-model="cioFilterSearch" @keydown.enter="loadCioFemeas()" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" placeholder="ID prim./sec.">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">N?? do Cio</label>
                                    <input type="number" x-model="cioFilterNumero" @keydown.enter="loadCioFemeas()" class="block w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 1">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="loadCioFemeas()" class="w-full inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                        <i class="fa-solid fa-filter mr-1 text-[10px]"></i>
                                        Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="lancamentosError" class="mb-4 bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="lancamentosError" x-cloak></div>

                        <div class="overflow-x-auto border border-gray-100 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" x-text="calendarType === '1000_dias' ? 'Dia PIG' : 'Data'"></th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID 2</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cio</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peso</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">

                                    <template x-for="row in cioFemeas" :key="row.cio_id">
                                        <tr class="hover:bg-gray-50 cursor-pointer" @click="window.location.href = `/admin/plantel/femeas/${row.id}`">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" @click.stop>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 hover:text-primary-600 transition-colors" title="Editar registro" @click.stop="openEditCio(row)">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Excluir registro" @click.stop="deleteCioRecord(row.cio_id)">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="calendarType === '1000_dias' ? (row.dia_ciclo !== null ? row.dia_ciclo : '-') : row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                <a :href="`/admin/plantel/femeas/${row.id}`" x-text="row.matriz" class="text-primary-600 hover:text-primary-800 hover:underline transition-colors"></a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <a :href="`/admin/plantel/femeas/${row.id}`" x-text="row.matriz_secundaria || '-'" class="hover:text-primary-600 hover:underline transition-colors"></a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.cio || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.peso || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.idade || '-'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!lancamentosLoading && cioFemeas.length === 0" x-cloak>
                                        <td colspan="7" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
                {{-- Tabela genêrica 'Status das Fêmeas' removida pois exibia indevidamente em abas não relacionadas (ex: Machos) --}}
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="row in mortesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-red-600 hover:bg-red-50" title="Excluir" @click.prevent="deleteLancamento(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
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
                                        <td colspan="6" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Macho</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="row in mortesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-red-600 hover:bg-red-50" title="Excluir" @click.prevent="deleteLancamento(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="row in descartesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-red-600 hover:bg-red-50" title="Excluir" @click.prevent="deleteLancamento(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
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
                                        <td colspan="6" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Macho</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="row in descartesFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-red-600 hover:bg-red-50" title="Excluir" @click.prevent="deleteLancamento(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fêmea</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciclo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="row in vendasFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Excluir" @click.prevent="deleteLancamento(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
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
                                        <td colspan="6" class="px-4 py-6 text-sm text-gray-500 text-center italic">Sem registros.</td>
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
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Macho</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causa</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                    <template x-for="row in vendasFemeas" :key="row.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-red-600 hover:bg-red-50" title="Excluir" @click.prevent="deleteLancamento(row.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.acao"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.data"></td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="row.id_primaria"></td>
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
            </div>
        </div>

        <div x-show="openNovo" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div x-show="openNovo" @click="openNovo = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <div x-show="openNovo" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95" class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transition-all max-w-5xl w-full border border-gray-100 max-h-[calc(100vh-2rem)] z-10 flex flex-col">
                    <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-5 shrink-0">
                        <div class="flex items-start justify-between">
                            <div class="text-left">
                                <h3 class="text-lg leading-6 font-semibold text-white" x-text="(item === 'femeas' && mov === 'morte') ? 'Registrar morte' : ((item === 'femeas' && mov === 'descarte') ? 'Registrar descarte' : ((item === 'femeas' && mov === 'venda') ? 'Registrar venda' : modalTitle))"></h3>
                                <p class="mt-1 text-xs text-primary-100" x-text="(item === 'femeas' && mov === 'compra' && compraFemeasTipo === 'leitoa') ? 'Cadastro de leitoa: use para registrar a compra de uma fêmea jovem que ainda vai entrar no ciclo reprodutivo. Informe identificação, datas e fornecedor.' : ((item === 'femeas' && mov === 'compra' && compraFemeasTipo === 'matriz_vazia') ? 'Cadastro de matriz vazia: use para registrar uma fêmea adulta comprada que não está gestante no momento. Informe a data de compra e os ciclos atê a compra para estimarmos a data de nascimento.' : ((item === 'femeas' && mov === 'compra' && compraFemeasTipo === 'matriz_gestante') ? 'Cadastro de matriz gestante: use para registrar uma fêmea adulta comprada jí em gestação. Informe data de cobertura (gestação) e a data de compra; o sistema exibe os dias de gestação.' : 'Informe os campos necessírios para concluir o cadastro.'))"></p>
                            </div>
                            <button type="button" @click="openNovo = false" class="text-white/80 hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-white px-6 py-6 overflow-y-auto flex-1 min-h-0">
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
                                    <div class="mt-1 relative">
                                        <input type="text" 
                                               x-ref="morteDateInputFemea"
                                               x-model="dataMorte" 
                                               @input="dataMorte = $event.target.value.replace(/\D/g, '')"
                                               @click="openMorteDatePicker()"
                                               class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                               placeholder="Dia PIG" 
                                               inputmode="numeric" 
                                               autocomplete="off">
                                        <button type="button" @click="openMorteDatePicker()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <i class="fa-solid fa-calendar"></i>
                                        </button>
                                        
                                        <template x-teleport="body">
                                            <div x-show="openNovo && activePicker === 'morte'"
                                                 x-cloak
                                                 :style="`top:${mortePickerTop}px; left:${mortePickerLeft}px;`"
                                                 :class="mortePickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
                                                 class="fixed z-[9999] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 -translate-x-1/2 max-h-[calc(100vh-12rem)] overflow-y-auto"
                                                 @click.away="activePicker = null">
                                                <div class="flex items-center justify-between mb-3">
                                                    <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                    :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                    :disabled="!day.isCurrentMonth"
                                                                    class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                    x-text="day.day">
                                                            </button>
                                                            <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataMorte"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Causa da morte</label>
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
                                    <div class="mt-1 relative">
                                        <input type="text" 
                                               x-ref="morteDateInputMacho"
                                               x-model="dataMorte" 
                                               @input="dataMorte = $event.target.value.replace(/\D/g, '')"
                                               @click="openMorteDatePicker()"
                                               class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                               placeholder="Dia PIG" 
                                               inputmode="numeric" 
                                               autocomplete="off">
                                        <button type="button" @click="openMorteDatePicker()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <i class="fa-solid fa-calendar"></i>
                                        </button>
                                        
                                        <template x-teleport="body">
                                            <div x-show="openNovo && activePicker === 'morte'"
                                                 x-cloak
                                                 :style="`top:${mortePickerTop}px; left:${mortePickerLeft}px;`"
                                                 :class="mortePickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
                                                 class="fixed z-[9999] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 -translate-x-1/2 max-h-[calc(100vh-12rem)] overflow-y-auto"
                                                 @click.away="activePicker = null">
                                                <div class="flex items-center justify-between mb-3">
                                                    <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                    :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                    :disabled="!day.isCurrentMonth"
                                                                    class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                    x-text="day.day">
                                                            </button>
                                                            <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataMorte"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
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
                                    <div class="mt-1 relative">
                                        <input type="text" 
                                               x-model="dataDescarte" 
                                               @input="dataDescarte = $event.target.value.replace(/\D/g, '')"
                                               @click="activePicker = 'descarte'"
                                               class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                               placeholder="Dia PIG" 
                                               inputmode="numeric" 
                                               autocomplete="off">
                                        <button type="button" @click="activePicker = 'descarte'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <i class="fa-solid fa-calendar"></i>
                                        </button>
                                        
                                        <!-- Calendírio PIG -->
                                        <div x-show="activePicker === 'descarte'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                             :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                             @click.away="activePicker = null">
                                            <div class="flex items-center justify-between mb-3">
                                                <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                :disabled="!day.isCurrentMonth"
                                                                class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                x-text="day.day">
                                                        </button>
                                                        <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataDescarte"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                    <div class="mt-1 relative">
                                        <input type="text" 
                                               x-model="dataDescarte" 
                                               @input="dataDescarte = $event.target.value.replace(/\D/g, '')"
                                               @click="activePicker = 'descarte'"
                                               class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                               placeholder="Dia PIG" 
                                               inputmode="numeric" 
                                               autocomplete="off">
                                        <button type="button" @click="activePicker = 'descarte'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                            <i class="fa-solid fa-calendar"></i>
                                        </button>
                                        
                                        <!-- Calendírio PIG -->
                                        <div x-show="activePicker === 'descarte'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                             :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                             @click.away="activePicker = null">
                                            <div class="flex items-center justify-between mb-3">
                                                <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                :disabled="!day.isCurrentMonth"
                                                                class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                x-text="day.day">
                                                        </button>
                                                        <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataDescarte"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/30 text-amber-900 dark:text-amber-200 rounded-xl px-4 py-3 text-sm">
                                    É importante fazer o descarte primeiro e depois a venda. A venda marca que o animal deixou de ser produtivo no ato da venda. Se ele jí estiver descartado hí algum tempo, isso pode atrapalhar as análises do sistema.
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
                                        <label class="block text-sm font-medium text-gray-700">Dia PIG da venda</label>
                                        <div class="mt-1 relative">
                                            <input type="text" 
                                                   x-model="dataVenda" 
                                                   @input="dataVenda = $event.target.value.replace(/\D/g, '')"
                                                   @click="activePicker = 'venda'"
                                                   class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                   placeholder="Dia PIG" 
                                                   inputmode="numeric" 
                                                   autocomplete="off">
                                            <button type="button" @click="activePicker = 'venda'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-calendar"></i>
                                            </button>
                                            
                                            <!-- Calendírio PIG -->
                                            <div x-show="activePicker === 'venda'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                                 :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                                 @click.away="activePicker = null">
                                                <div class="flex items-center justify-between mb-3">
                                                    <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                    :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                    :disabled="!day.isCurrentMonth"
                                                                    class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                    x-text="day.day">
                                                            </button>
                                                            <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                        </div>
                                                    </template>
                                                </div>
                                                
                                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataVenda"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
                                    É importante fazer o descarte primeiro e depois a venda. A venda marca que o animal deixou de ser produtivo no ato da venda. Se ele jí estiver descartado hí algum tempo, isso pode atrapalhar as análises do sistema.
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
                                        <label class="block text-sm font-medium text-gray-700">Dia PIG da venda</label>
                                        <div class="mt-1 relative">
                                            <input type="text" 
                                                   x-model="dataVenda" 
                                                   @input="dataVenda = $event.target.value.replace(/\D/g, '')"
                                                   @click="activePicker = 'venda'"
                                                   class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                   placeholder="Dia PIG" 
                                                   inputmode="numeric" 
                                                   autocomplete="off">
                                            <button type="button" @click="activePicker = 'venda'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                <i class="fa-solid fa-calendar"></i>
                                            </button>
                                            
                                            <!-- Calendírio PIG -->
                                            <div x-show="activePicker === 'venda'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                                 :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                                 @click.away="activePicker = null">
                                                <div class="flex items-center justify-between mb-3">
                                                    <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                        <i class="fa-solid fa-chevron-left"></i>
                                                    </button>
                                                    <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                    :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                    :disabled="!day.isCurrentMonth"
                                                                    class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                    x-text="day.day">
                                                            </button>
                                                            <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                        </div>
                                                    </template>
                                                </div>
                                                
                                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataVenda"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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

                        <template x-if="item === 'femeas' && mov === 'cio'">
                            <div class="space-y-4">
                                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Novo Lançamento de Cio</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Selecionar Fêmea</label>
                                            <select x-model="femeaCioId" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 py-2.5">
                                                <option value="">Selecione a fêmea...</option>
                                                <template x-for="f in femeasAtivas" :key="f.id">
                                                    <option :value="String(f.id)" x-text="f.id_primaria + (f.id_secundaria ? ' / ' + f.id_secundaria : '')"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Data do Cio</label>
                                            <div class="mt-1 relative">
                                                <input type="text" 
                                                       x-model="dataCio" 
                                                       @input="dataCio = $event.target.value.replace(/\D/g, '')"
                                                       @click="activePicker = 'cio'"
                                                       class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 py-2.5 pr-10" 
                                                       placeholder="Dia PIG" 
                                                       inputmode="numeric" 
                                                       autocomplete="off">
                                                <button type="button" @click="activePicker = 'cio'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                    <i class="fa-solid fa-calendar"></i>
                                                </button>
                                                
                                                <!-- Calendírio PIG -->
                                                <div x-show="activePicker === 'cio'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                                     :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                                     @click.away="activePicker = null">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                            <i class="fa-solid fa-chevron-left"></i>
                                                        </button>
                                                        <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                        :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                        :disabled="!day.isCurrentMonth"
                                                                        class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                        x-text="day.day">
                                                                </button>
                                                                <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    
                                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataCio"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 mb-1">Peso da Leitoa (kg)</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" x-model="pesoCio" class="w-full pr-12 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 py-2.5" placeholder="0,00">
                                                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="item === 'machos' && mov === 'compra'">
                            <div class="space-y-4">
                                <template x-if="openNovoTab === 'principal'">
                                    <div class="space-y-4">
                                        <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl p-4">
                                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Identificação e Datas</div>
                                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID primíria</label>
                                                    <input type="text" x-model="idPrimaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 2001">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID secundíria</label>
                                                    <input type="text" x-model="idSecundaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Data de compra</label>
                                                    <div class="mt-1 relative">
                                                        <input type="text" 
                                                               x-model="dataCompra" 
                                                               @input="dataCompra = $event.target.value.replace(/\D/g, '')"
                                                               @click="activePicker = 'compra'"
                                                               class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                               placeholder="Dia PIG" 
                                                               inputmode="numeric" 
                                                               autocomplete="off">
                                                        <button type="button" @click="activePicker = 'compra'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                            <i class="fa-solid fa-calendar"></i>
                                                        </button>
                                                        
                                                        <!-- Calendírio PIG -->
                                                        <div x-show="activePicker === 'compra'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                                             :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                                             @click.away="activePicker = null">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                                    <i class="fa-solid fa-chevron-left"></i>
                                                                </button>
                                                                <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                                :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                                :disabled="!day.isCurrentMonth"
                                                                                class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                                x-text="day.day">
                                                                        </button>
                                                                        <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                            
                                                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                    <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataCompra"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Data de nascimento</label>
                                                    <div class="mt-1 relative">
                                                        <input type="text" 
                                                               x-model="dataNascimento" 
                                                               @input="nascimentoAuto = false; dataNascimento = $event.target.value.replace(/\D/g, '')"
                                                               @click="activePicker = 'nascimento'"
                                                               class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                               placeholder="Dia PIG" 
                                                               inputmode="numeric" 
                                                               autocomplete="off">
                                                        <button type="button" @click="activePicker = 'nascimento'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                            <i class="fa-solid fa-calendar"></i>
                                                        </button>
                                                        
                                                        <!-- Calendírio PIG -->
                                                        <div x-show="activePicker === 'nascimento'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" 
                                                             :class="{'right-0': window.innerWidth > 640, 'left-0 right-0 mx-auto': window.innerWidth <= 640}" 
                                                             @click.away="activePicker = null">
                                                            <div class="flex items-center justify-between mb-3">
                                                                <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                                    <i class="fa-solid fa-chevron-left"></i>
                                                                </button>
                                                                <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                                :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                                :disabled="!day.isCurrentMonth"
                                                                                class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                                x-text="day.day">
                                                                        </button>
                                                                        <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                            
                                                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                    <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataNascimento"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl p-4">
                                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Classificação</div>
                                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Raça</label>
                                                    <div class="mt-1 flex items-center space-x-2">
                                                        <select x-model="racaId" class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-gray-200 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                            <option value="">Selecione...</option>
                                                            <template x-for="r in racas" :key="r.id">
                                                                <option :value="String(r.id)" x-text="r.nome"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button" @click="openNovaRaca = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 dark:border-gray-700 rounded-xl shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar raça">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                                                    <div class="mt-1 flex items-center space-x-2">
                                                        <select x-model="fornecedorId" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                            <option value="">Selecione...</option>
                                                            <template x-for="f in fornecedores" :key="f.id">
                                                                <option :value="String(f.id)" x-text="f.nome"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button" @click="openNovoFornecedor = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar fornecedor">
                                                            <i class="fa-solid fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="openNovoTab === 'complementares'">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl p-4">
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
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <input type="text" x-model="localizacao" list="util-localizacoes" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Galpão A">
                                                    <button type="button" @click="openNovaLocalizacao = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar localização">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Baia</label>
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <input type="text" x-model="baia" list="util-baias" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 12">
                                                    <button type="button" @click="openNovaBaia = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar baia">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
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
                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl p-4">
                                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Identificação e Datas</div>
                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">ID primíria</label>
                                                <input type="text" x-model="idPrimaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 1001">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">ID secundíria</label>
                                                <input type="text" x-model="idSecundaria" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data de compra</label>
                                                <div class="mt-1 relative">
                                                    <input type="text" 
                                                           x-model="dataCompra" 
                                                           @input="dataCompra = $event.target.value.replace(/\D/g, '')"
                                                           @click="activePicker = 'compra'"
                                                           class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                           placeholder="Dia PIG" 
                                                           inputmode="numeric" 
                                                           autocomplete="off">
                                                    <button type="button" @click="activePicker = 'compra'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>
                                                    
                                                    <!-- Calendírio PIG -->
                                                    <div x-show="activePicker === 'compra'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4" @click.away="activePicker = null">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                                <i class="fa-solid fa-chevron-left"></i>
                                                            </button>
                                                            <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                            :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                            :disabled="!day.isCurrentMonth"
                                                                            class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                            x-text="day.day">
                                                                    </button>
                                                                    <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        
                                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataCompra"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                                <div x-show="showHouveCio()" x-cloak>
                                                    <label class="block text-sm font-medium text-gray-700">Jí houve cio?</label>
                                                    <select x-model="houveCio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                        <option value="nao">Não</option>
                                                        <option value="sim">Sim</option>
                                                    </select>
                                                </div>
                                                <div x-show="showHouveCio() && houveCio === 'sim'" x-cloak>
                                                    <label class="block text-sm font-medium text-gray-700">Data do último cio</label>
                                                    <input type="text" x-model="dataUltimoCio" @input="dataUltimoCio = normalizeDateInput($event.target.value)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                                                </div>
                                                <div x-show="ciclosObrigatorio" x-cloak>
                                                    <label class="block text-sm font-medium text-gray-700">Ciclos atê a compra</label>
                                                    <input type="number" min="0" step="1" x-model="ciclosAteCompra" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 3">
                                                    <div class="mt-1 text-xs text-gray-500">Usado para sugerir a data de nascimento.</div>
                                                </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data de nascimento</label>
                                                <div class="mt-1 relative">
                                                    <input type="text" 
                                                           x-model="dataNascimento" 
                                                           @input="nascimentoAuto = false; dataNascimento = $event.target.value.replace(/\D/g, '')"
                                                           @click="activePicker = 'nascimento'"
                                                           class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                           placeholder="Dia PIG" 
                                                           inputmode="numeric" 
                                                           autocomplete="off">
                                                    <button type="button" @click="activePicker = 'nascimento'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>
                                                    
                                                    <!-- Calendírio PIG -->
                                                    <div x-show="activePicker === 'nascimento'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4" @click.away="activePicker = null">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                                <i class="fa-solid fa-chevron-left"></i>
                                                            </button>
                                                            <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                            :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                            :disabled="!day.isCurrentMonth"
                                                                            class="p-2 text-sm rounded-lg transition-colors w-full"
                                                                            x-text="day.day">
                                                                    </button>
                                                                    <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-1" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        
                                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataNascimento"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500" x-show="!dataNascimento && sugestaoNascimento">
                                                    Sugestúo: <button type="button" class="font-semibold text-primary-700 hover:underline" @click="nascimentoAuto = true; dataNascimento = sugestaoNascimento" x-text="sugestaoNascimento"></button>
                                                </div>  
                                            </div>
                                            <div x-show="coberturaObrigatorio" x-cloak>
                                                <label class="block text-sm font-medium text-gray-700">Data de cobertura</label>
                                                <div class="mt-1 relative">
                                                    <input type="text" 
                                                           x-model="dataCobertura" 
                                                           @input="dataCobertura = normalizeDateInput($event.target.value)"
                                                           @click="activePicker = 'cobertura'"
                                                           class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10" 
                                                           placeholder="DD/MM/AAAA" 
                                                           inputmode="numeric" 
                                                           autocomplete="off"
                                                           readonly>
                                                    <button type="button" @click="activePicker = 'cobertura'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>
                                                    
                                                    <!-- Calendírio PIG -->
                                                    <div x-show="activePicker === 'cobertura'" x-cloak class="absolute overflow-hidden z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" @click.away="activePicker = null">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                                <i class="fa-solid fa-chevron-left"></i>
                                                            </button>
                                                            <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                                <button type="button" 
                                                                        @click.stop="selectCalendarDate(day.date)"
                                                                        :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                                        :disabled="!day.isCurrentMonth"
                                                                        class="p-2 text-sm rounded-lg transition-colors"
                                                                        x-text="day.day">
                                                                </button>
                                                            </template>
                                                        </div>
                                                        
                                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + dataCobertura"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500" x-show="diasGestacao !== null">
                                                    Dias de gestação: <span class="font-semibold text-gray-700" x-text="diasGestacao"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl p-4">
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
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <select x-model="fornecedorId" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                        <option value="">Selecione...</option>
                                                        <template x-for="f in fornecedores" :key="f.id">
                                                            <option :value="String(f.id)" x-text="f.nome"></option>
                                                        </template>
                                                    </select>
                                                    <button type="button" @click="openNovoFornecedor = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar fornecedor">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
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
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <input type="text" x-model="localizacao" list="util-localizacoes" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Galpão A">
                                                    <button type="button" @click="openNovaLocalizacao = true" class="w-10 h-10 inline-flex items-center justify-center border border-gray-300 rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Cadastrar localização">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Baia</label>
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <input type="text" x-model="baia" list="util-baias" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 12">
                                        </div>
                                    </div>

                                </div>
                            </template>

                                                    </div>
                        </template>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3 shrink-0">
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
                            <div class="flex gap-3">
                                <button type="button" @click="saveCompraFemea()" :disabled="saving" class="flex-1 inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                    <template x-if="!saving"><span>Salvar</span></template>
                                    <template x-if="saving"><span>Gravando...</span></template>
                                </button>
                                <button type="button" @click="saveCompraFemeaContinuar()" :disabled="saving" class="flex-1 inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                                    <template x-if="!saving"><span>Salvar e Continuar</span></template>
                                    <template x-if="saving"><span>Gravando...</span></template>
                                </button>
                            </div>
                        </template>
                        <template x-if="item === 'femeas' && mov === 'cio'">
                            <button type="button" @click="saveCioFemea()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-pink-600 text-sm font-semibold text-white hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
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

        <!-- Modal Edição de Cio Moderno -->
        <div x-show="openEditCioModal" 
             class="fixed inset-0 z-[120] overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-cloak @keydown.escape.window="openEditCioModal = false">
            
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" @click="openEditCioModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100 dark:border-gray-800"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                    
                    <div class="bg-gradient-to-r from-pink-600 to-rose-500 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Editar Registro de Cio</h3>
                            <template x-if="editCioData.femea_id">
                                <div class="text-[10px] text-pink-100 font-medium mt-0.5">Fêmea: <span x-text="editCioData.femea_id"></span></div>
                            </template>
                        </div>
                        <button @click="openEditCioModal = false" class="text-white/80 hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form accept-charset="UTF-8" @submit.prevent="saveCioEdit()" class="p-6 space-y-5">
                        <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl p-5 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5 tracking-wider">Data do Cio</label>
                                <div class="relative">
                                    <input type="text" x-model="editCioData.data" 
                                           @input="editCioData.data = normalizeDateInput($event.target.value)"
                                           @click="activePicker = 'editCio'"
                                           required readonly
                                           class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl text-sm focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 outline-none transition-all shadow-sm dark:text-gray-200 pr-10"
                                           placeholder="DD/MM/AAAA">
                                    <button type="button" @click="activePicker = 'editCio'" class="absolute inset-y-0 right-4 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-calendar"></i>
                                    </button>
                                    
                                    <!-- Calendírio PIG -->
                                    <div x-show="activePicker === 'editCio'" x-cloak class="absolute z-50 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72" @click.away="activePicker = null">
                                        <div class="flex items-center justify-between mb-3">
                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <span class="font-medium text-gray-900 " x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                <button type="button" 
                                                        @click.stop="selectCalendarDate(day.date)"
                                                        :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                        :disabled="!day.isCurrentMonth"
                                                        class="p-2 text-sm rounded-lg transition-colors"
                                                        x-text="day.day">
                                                </button>
                                            </template>
                                        </div>
                                        
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + editCioData.data"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5 tracking-wider">Peso da leitoa (kg)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" x-model="editCioData.peso"
                                           class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl text-sm focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 outline-none transition-all shadow-sm dark:text-gray-200"
                                           placeholder="Opcional">
                                    <span class="absolute inset-y-0 right-4 flex items-center text-xs text-gray-400">kg</span>
                                </div>
                            </div>
                        </div>
                        <div class="pt-2 flex gap-3">
                            <button type="button" @click="openEditCioModal = false" 
                                    class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-700 text-sm font-semibold rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="saving"
                                    class="flex-1 px-4 py-3 bg-pink-600 text-sm font-semibold rounded-xl text-white hover:bg-pink-700 shadow-lg shadow-pink-600/20 disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin" x-show="saving"></i>
                                <span x-text="saving ? 'Salvando...' : 'Salvar Alterações'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal de Edição Rápida de Fêmea -->


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

        <div x-show="openNovoFornecedor" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openNovoFornecedor" @click="openNovoFornecedor = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openNovoFornecedor" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900">Cadastrar fornecedor</h3>
                            <button type="button" @click="openNovoFornecedor = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" x-model="novoFornecedorNome" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Fornecedor X">
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="button" @click="saveFornecedor()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!saving"><span>Salvar</span></template>
                            <template x-if="saving"><span>Gravando...</span></template>
                        </button>
                        <button type="button" @click="openNovoFornecedor = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="openNovaLocalizacao" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openNovaLocalizacao" @click="openNovaLocalizacao = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openNovaLocalizacao" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900">Cadastrar localização</h3>
                            <button type="button" @click="openNovaLocalizacao = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" x-model="novaLocalizacaoNome" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Galpão A">
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="button" @click="saveLocalizacao()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!saving"><span>Salvar</span></template>
                            <template x-if="saving"><span>Gravando...</span></template>
                        </button>
                        <button type="button" @click="openNovaLocalizacao = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="openNovaBaia" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openNovaBaia" @click="openNovaBaia = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openNovaBaia" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900">Cadastrar baia</h3>
                            <button type="button" @click="openNovaBaia = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" x-model="novaBaiaNome" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: 12">
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                        <button type="button" @click="saveBaia()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!saving"><span>Salvar</span></template>
                            <template x-if="saving"><span>Gravando...</span></template>
                        </button>
                        <button type="button" @click="openNovaBaia = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto disabled:opacity-50">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<datalist id="util-localizacoes">
    <template x-for="n in utilLocalizacoes" :key="`dl-loc-${n}`">
        <option :value="n"></option>
    </template>
</datalist>
<datalist id="util-baias">
    <template x-for="n in utilBaias" :key="`dl-baia-${n}`">
        <option :value="n"></option>
    </template>
</datalist>

<div x-show="tab === 'acompanhamento'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
    <div x-data="{
        loaded: false,
        loading: false,
        error: '',
        items: [],
        selected: null,
        modalOpen: false,
        load() {
            if (this.loading || this.loaded) return;
            this.loading = true;
            this.error = '';
            fetch('/api/plantel/femeas/acompanhamento?limit=1000', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.items = Array.isArray(data.items) ? data.items : [];
                    if (data.message) this.error = data.message;
                    this.loaded = true;
                })
                .catch(() => { this.error = 'Não foi possível carregar a listagem.'; })
                .finally(() => { this.loading = false; });
        },
        open(id) {
            this.modalOpen = true;
            this.selected = null;
            fetch('/api/plantel/femeas/acompanhamento/' + id, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { this.selected = data.item || null; })
                .catch(() => { this.selected = null; });
        },
    }" x-init="if ($root.tab === 'acompanhamento') load(); window.addEventListener('femea-updated', () => { load(); });" @acompanhamento-open.window="load()" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Acompanhamento de fêmeas</h6>
                    <div class="text-sm text-gray-500 mt-1">Fase atual e previsões (baseadas em critérios e últimos lançamentos).</div>
                </div>
                <button type="button" @click="loaded=false; load()" :disabled="loading" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                    <template x-if="!loading"><span>Atualizar</span></template>
                    <template x-if="loading"><span>Carregando...</span></template>
                </button>
            </div>
            <div class="p-6">
                <div x-show="error" x-text="error" class="bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm mb-4" x-cloak></div>

                <div class="text-xs text-gray-500 mb-3" x-show="loading">Carregando...</div>

                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Fase</th>
                                <th class="px-4 py-3">Pr??xima</th>
                                <th class="px-4 py-3">Prevista em</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <template x-for="row in items" :key="row.id">
                                <tr class="text-sm text-gray-700 hover:bg-gray-50 cursor-pointer" @click="open(row.id)">
                                    <td class="px-4 py-3 font-semibold text-primary-700" x-text="row.id_primaria"></td>
                                    <td class="px-4 py-3" x-text="row.tipo"></td>
                                    <td class="px-4 py-3" x-text="row.fase"></td>
                                    <td class="px-4 py-3" x-text="row.proxima_fase"></td>
                                    <td class="px-4 py-3" x-text="row.prevista_em"></td>
                                </tr>
                            </template>
                            <tr x-show="!loading && loaded && items.length === 0" x-cloak>
                                <td colspan="5" class="px-4 py-8 text-sm text-gray-500 text-center italic">Sem registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="modalOpen" @click="modalOpen = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-flex flex-col align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 max-h-[85vh]">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">Cadastro da fêmea</h3>
                                <div class="text-sm text-gray-500 mt-1" x-text="selected ? (selected.id_primaria + (selected.id_secundaria ? ' / ' + selected.id_secundaria : '')) : 'Carregando...'"></div>
                            </div>
                            <button type="button" @click="modalOpen = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50" title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="selected" x-cloak>
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Fase</div>
                                <div class="mt-1 text-sm text-gray-900" x-text="selected ? selected.fase : ''"></div>
                                <div class="mt-1 text-xs text-gray-500" x-text="'Pr??xima: ' + (selected ? selected.proxima_fase : '-') + ' | Prevista em: ' + (selected ? selected.prevista_em : '-')"></div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Dados</div>
                                <div class="mt-1 text-sm text-gray-700" x-text="'Tipo: ' + (selected ? selected.tipo : '-')"></div>
                                <div class="mt-1 text-sm text-gray-700" x-text="'Nascimento: ' + ((selected && selected.data_nascimento) || '-')"></div>
                                <div class="mt-1 text-sm text-gray-700" x-text="'Compra: ' + ((selected && selected.data_compra) || '-')"></div>
                            </div>
                            <div class="sm:col-span-2">
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Calendírio (previsões)</div>
                                <div class="mt-2 bg-gray-50 border border-gray-100 rounded-2xl p-4">
                                    <template x-if="Array.isArray(selected && selected.calendario) && selected.calendario.length > 0">
                                        <ul class="space-y-2">
                                            <template x-for="(e, i) in selected.calendario" :key="'cal-' + i">
                                                <li class="flex items-start justify-between gap-4 text-sm">
                                                    <span class="text-gray-700" x-text="e.fase"></span>
                                                    <span class="font-semibold text-gray-900 whitespace-nowrap" x-text="e.data"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                    <template x-if="!(Array.isArray(selected && selected.calendario)) || (selected && selected.calendario && selected.calendario.length === 0)">
                                        <div class="text-sm text-gray-500">Sem previsões (sem cobertura registrada).</div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border-t border-gray-100 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="button" @click="openEdit(selected); modalOpen = false" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700">
                            <i class="fa-solid fa-pencil mr-2"></i> Editar fêmea
                        </button>
                        <button type="button" @click="modalOpen = false" class="inline-flex items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-show="tab === 'analise'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
    <div x-data="{
        analiseSubTab: (function(){ const p = new URLSearchParams(window.location.search); const s = p.get('subtab'); if (s === 'desempenho') return s; return 'desempenho'; })(),
        loading: false,
        loaded: false,
        error: '',
        femeas: { compra: [], morte: [], descarte: [], venda: [] },
        machos: { compra: [], morte: [], descarte: [], venda: [] },
        top: {
            femeas: { morte: [], descarte: [], venda: [] },
            machos: { morte: [], descarte: [], venda: [] },
        },
        totals: {
            femeas: { compra: 0, morte: 0, descarte: 0, venda: 0 },
            machos: { compra: 0, morte: 0, descarte: 0, venda: 0 },
        },
        rangeDays: 365,
        datesRange: [],
        metas: {},
        series: {
            compra: { leitoa: [], matriz: [], leitao: [] },
            morte: { leitoa: [], matriz: [], leitao: [] },
            descarte: { leitoa: [], matriz: [], leitao: [] },
            venda: { leitoa: [], matriz: [], leitao: [] },
        },
        // Ficha da matriz
        fichaModalOpen: false,
        fichaLoading: false,
        fichaError: '',
        fichaFemeas: [],
        fichaSelectedId: '',
        fichaData: null,
        // Retenção
        retencaoExpanded: true,
        retencaoLoading: false,
        retencaoError: '',
        retencaoData: null,
        retencaoFiltroRaca: '',
        retencaoFiltroTipo: 'leitoas',
        retencaoDataInicial: '',
        retencaoDataFinal: '',
        racasRetencao: [],
        loadFichaFemeas() {
            if (this.fichaFemeas.length > 0) return;
            fetch('/api/plantel/femeas?limit=1000&all=1', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.fichaFemeas = Array.isArray(data.items) ? data.items : [];
                })
                .catch(() => { this.fichaFemeas = []; });
        },
        openFichaModal() {
            this.loadFichaFemeas();
            this.fichaModalOpen = true;
            this.fichaSelectedId = '';
            this.fichaData = null;
            this.fichaError = '';
        },
        loadFichaData() {
            if (!this.fichaSelectedId) {
                this.fichaData = null;
                return;
            }
            this.fichaLoading = true;
            this.fichaError = '';
            fetch(`/api/plantel/femeas/ficha/${this.fichaSelectedId}`, { headers: { 'Accept': 'application/json' } })
                .then(async r => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao carregar ficha');
                    return data;
                })
                .then(data => {
                    this.fichaData = data;
                })
                .catch(e => {
                    this.fichaError = e.message;
                    this.fichaData = null;
                })
                .finally(() => { this.fichaLoading = false; });
        },
        loadRetencaoData() {
            if (!this.retencaoDataInicial || !this.retencaoDataFinal) {
                this.retencaoError = 'Selecione o período para análise.';
                return;
            }
            this.retencaoLoading = true;
            this.retencaoError = '';
            const params = new URLSearchParams({
                data_inicial: this.retencaoDataInicial,
                data_final: this.retencaoDataFinal,
                raca_id: this.retencaoFiltroRaca,
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
        countByCausa(items) {
            const map = {};
            (items || []).forEach((row) => {
                const key = (row && row.causa) ? String(row.causa) : '-';
                map[key] = (map[key] || 0) + 1;
            });
            return Object.entries(map)
                .map(([causa, total]) => ({ causa, total }))
                .sort((a, b) => b.total - a.total);
        },
        sliceTop(list, n = 6) {
            return (list || []).slice(0, n);
        },
        parseDateBr(dateStr) {
            const parts = String(dateStr || '').split('/');
            if (parts.length !== 3) return null;
            const d = Number(parts[0]);
            const m = Number(parts[1]);
            const y = Number(parts[2]);
            if (!d || !m || !y) return null;
            return new Date(y, m - 1, d);
        },
        sortDatesAsc(a, b) {
            const da = this.parseDateBr(a);
            const db = this.parseDateBr(b);
            if (!da && !db) return 0;
            if (!da) return -1;
            if (!db) return 1;
            return da.getTime() - db.getTime();
        },
        limitDates(dates, max = 90) {
            const list = Array.isArray(dates) ? dates : [];
            if (list.length <= max) return list;
            return list.slice(list.length - max);
        },
        formatDateBr(date) {
            const d = date instanceof Date ? date : new Date(date);
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = String(d.getFullYear());
            return `${dd}/${mm}/${yyyy}`;
        },
        buildDatesRangeFrom(startDate, endDate) {
            const start = startDate instanceof Date ? new Date(startDate) : new Date(startDate);
            const end = endDate instanceof Date ? new Date(endDate) : new Date(endDate);
            start.setHours(0, 0, 0, 0);
            end.setHours(0, 0, 0, 0);

            const dates = [];
            const cursor = new Date(start);
            while (cursor.getTime() <= end.getTime()) {
                dates.push(this.formatDateBr(cursor));
                cursor.setDate(cursor.getDate() + 1);
            }

            return dates;
        },
        shortDate(dateStr) {
            const s = String(dateStr || '');
            return s.length >= 5 ? s.slice(0, 5) : s;
        },
        chartPad() {
            return 24;
        },
        chartStep() {
            return 18;
        },
        chartPlotTop() {
            return 24;
        },
        chartPlotBottom() {
            return 136;
        },
        chartHeight() {
            return 220;
        },
        chartInnerHeight() {
            return this.chartPlotBottom() - this.chartPlotTop();
        },
        chartWidth(n) {
            const p = this.chartPad();
            const step = this.chartStep();
            const count = Number(n || 0);
            if (count <= 1) return 600;
            return p * 2 + (count - 1) * step;
        },
        rightX() {
            return this.chartWidth(this.datesRange.length) - this.chartPad();
        },
        xAt(i) {
            return this.chartPad() + Number(i || 0) * this.chartStep();
        },
        categoriaFromTipo(tipo) {
            const t = String(tipo || '').trim().toLowerCase();
            if (t === 'leitoa') return 'leitoa';
            if (t === 'leitao' || t === 'leitúo') return 'leitao';
            if (t === 'matriz_vazia' || t === 'matriz_gestante' || t === 'matriz') return 'matriz';
            return null;
        },
        normalizeMetaPercent(raw) {
            const n = Number(raw);
            if (!Number.isFinite(n) || n <= 0) return null;
            if (n <= 1) return n * 100;
            if (n > 100) return 100;
            return n;
        },
        metaPercent(acao, categoria) {
            const map = {
                compra: {
                    matriz: 'meta_manutencao_reposicao',
                },
                morte: {
                    matriz: 'meta_manutencao_mortalidade_matrizes',
                    leitoa: 'meta_manutencao_perdas_leitoas_pre_cobertura',
                },
                descarte: {
                    matriz: 'meta_manutencao_descarte_matrizes',
                },
                venda: {},
            };

            const key = map?.[acao]?.[categoria];
            if (!key) return null;
            return this.normalizeMetaPercent(this.metas?.[key]);
        },
        metaY(percent) {
            const h = this.chartPlotBottom();
            const innerH = this.chartInnerHeight();
            const value = Number(percent || 0);
            return h - (value / 100) * innerH;
        },
        formatMeta(acao, categoria) {
            const p = this.metaPercent(acao, categoria);
            if (p === null) return '';
            return `${p.toFixed(1)}%`;
        },
        linePoints(items) {
            const series = Array.isArray(items) ? items : [];
            const n = series.length;
            if (n === 0) return '';

            const h = this.chartPlotBottom();
            const innerH = this.chartInnerHeight();

            return series
                .map((row, i) => {
                    const x = this.xAt(i);
                    const y = h - (Number(row.value || 0) / 100) * innerH;
                    return `${x},${y}`;
                })
                .join(' ');
        },
        firstDate() {
            return this.datesRange?.[0] || '-';
        },
        lastDate() {
            return this.datesRange?.[this.datesRange.length - 1] || '-';
        },
        tooltip: {
            open: false,
            locked: false,
            chart: '',
            index: 0,
            left: 0,
            top: 0,
        },
        formatPercent(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) return '0.0%';
            return `${n.toFixed(1)}%`;
        },
        categoriaLabel(categoria) {
            const c = String(categoria || '');
            if (c === 'leitoa') return 'Leitoa';
            if (c === 'matriz') return 'Matriz';
            if (c === 'leitao') return 'Leitúo';
            return c;
        },
        yAt(value) {
            return this.metaY(value);
        },
        tooltipItems() {
            const chart = this.tooltip.chart;
            const i = Number(this.tooltip.index || 0);
            const cats = ['leitoa', 'matriz', 'leitao'];
            return cats.map((c) => {
                const val = this.series?.[chart]?.[c]?.[i]?.value ?? 0;
                const meta = this.metaPercent(chart, c);
                return { categoria: c, value: val, meta };
            });
        },
        setTooltip(chart, index, event, lock = false) {
            if (this.tooltip.locked && !lock) return;
            this.tooltip.chart = String(chart);
            this.tooltip.index = Number(index || 0);
            this.tooltip.open = true;

            const clientX = event?.clientX ?? 0;
            const clientY = event?.clientY ?? 0;

            const margin = 12;
            const maxLeft = Math.max(0, (window.innerWidth || 0) - 260);
            const maxTop = Math.max(0, (window.innerHeight || 0) - 160);
            this.tooltip.left = Math.min(Math.max(clientX + margin, margin), maxLeft);
            this.tooltip.top = Math.min(Math.max(clientY + margin, margin), maxTop);
        },
        clearTooltip(chart) {
            if (this.tooltip.locked) return;
            if (this.tooltip.chart === String(chart)) {
                this.tooltip.open = false;
            }
        },
        toggleTooltipLock(chart, index, event) {
            const same = this.tooltip.locked && this.tooltip.chart === String(chart) && Number(this.tooltip.index) === Number(index);
            if (same) {
                this.tooltip.locked = false;
                this.tooltip.open = false;
                return;
            }

            this.tooltip.locked = true;
            this.setTooltip(chart, index, event, true);
        },
        async loadAll() {
            this.loading = true;
            this.error = '';
            try {
                const endpoints = [
                    ['/api/plantel/femeas/compras', 'femeas', 'compra'],
                    ['/api/plantel/femeas/mortes', 'femeas', 'morte'],
                    ['/api/plantel/femeas/descartes', 'femeas', 'descarte'],
                    ['/api/plantel/femeas/vendas', 'femeas', 'venda'],
                    ['/api/plantel/machos/compras', 'machos', 'compra'],
                    ['/api/plantel/machos/mortes', 'machos', 'morte'],
                    ['/api/plantel/machos/descartes', 'machos', 'descarte'],
                    ['/api/plantel/machos/vendas', 'machos', 'venda'],
                ];

                const results = await Promise.all(
                    endpoints.map(async ([url, grupo, acao]) => {
                        const r = await fetch(url, { headers: { Accept: 'application/json' } });
                        const data = await r.json().catch(() => ({}));
                        return { grupo, acao, data };
                    })
                );

                results.forEach(({ grupo, acao, data }) => {
                    const items = Array.isArray(data?.items) ? data.items : [];
                    this[grupo][acao] = items;
                    this.totals[grupo][acao] = items.length;
                    if (acao !== 'compra') {
                        this.top[grupo][acao] = this.sliceTop(this.countByCausa(items), 6);
                    }
                    if (data?.message && !this.error) this.error = String(data.message);
                });

                const actions = ['compra', 'morte', 'descarte', 'venda'];
                const categorias = ['leitoa', 'matriz', 'leitao'];

                const metasResp = await fetch('/api/metas', { headers: { Accept: 'application/json' } });
                const metasData = await metasResp.json().catch(() => ({}));
                this.metas = metasData?.items && typeof metasData.items === 'object' ? metasData.items : {};
                if (metasData?.message && !this.error) this.error = String(metasData.message);

                const byCategoriaDate = {
                    leitoa: {},
                    matriz: {},
                    leitao: {},
                };

                actions.forEach((acao) => {
                    (this.femeas[acao] || []).forEach((row) => {
                        const date = row?.data ? String(row.data) : '';
                        if (!date) return;

                        const categoria = this.categoriaFromTipo(row?.tipo);
                        if (!categoria) return;

                        if (!byCategoriaDate[categoria][date]) {
                            byCategoriaDate[categoria][date] = { compra: 0, morte: 0, descarte: 0, venda: 0, total: 0 };
                        }

                        byCategoriaDate[categoria][date][acao] += 1;
                        byCategoriaDate[categoria][date].total += 1;
                    });
                });

                const allDates = [];
                categorias.forEach((categoria) => {
                    allDates.push(...Object.keys(byCategoriaDate[categoria]));
                });

                const parsed = allDates
                    .map((d) => this.parseDateBr(d))
                    .filter((d) => d instanceof Date && !Number.isNaN(d.getTime()));

                const today = new Date();
                today.setHours(0, 0, 0, 0);

                let endDate = today;
                let minDate = today;

                if (parsed.length > 0) {
                    parsed.sort((a, b) => a.getTime() - b.getTime());
                    minDate = parsed[0];
                    endDate = parsed[parsed.length - 1];
                }

                const days = Math.max(1, Number(this.rangeDays || 365));
                const startDate = new Date(endDate);
                startDate.setDate(endDate.getDate() - (days - 1));

                if (startDate.getTime() < minDate.getTime()) {
                    startDate.setTime(minDate.getTime());
                }

                this.datesRange = this.buildDatesRangeFrom(startDate, endDate);

                actions.forEach((acao) => {
                    categorias.forEach((categoria) => {
                        this.series[acao][categoria] = this.datesRange.map((date) => {
                            const row = byCategoriaDate[categoria][date];
                            const total = Number(row?.total || 0);
                            const count = Number(row?.[acao] || 0);
                            const value = total > 0 ? (count / total) * 100 : 0;
                            return { date, value };
                        });
                    });
                });

                this.loaded = true;
            } catch (e) {
                this.error = 'Não foi possível carregar as análises.';
            } finally {
                this.loading = false;
            }
        },
    }" x-init="loadAll()" class="space-y-6">
        <div class="mb-4 bg-gray-50 border border-gray-100 text-gray-700 rounded-xl px-4 py-3 text-sm flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="font-semibold text-gray-900">Análise do Plantel</div>
                <div class="text-xs text-gray-500">Taxas por data e resumo de eventos (compras, mortes, descartes e vendas).</div>
            </div>
            <button type="button" @click="loadAll()" :disabled="loading" class="shrink-0 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                <template x-if="!loading"><span>Atualizar</span></template>
                <template x-if="loading"><span>Carregando...</span></template>
            </button>
        </div>

        <!-- Subcategorias de Análise -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <button type="button" @click="analiseSubTab = 'desempenho'" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors" :class="analiseSubTab === 'desempenho' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                        <i class="fa-solid fa-chart-line mr-2"></i>Desempenho
                    </button>
                </div>
            </div>
        </div>

        <!-- Conteúdo da subcategoria Desempenho -->
        <div x-show="analiseSubTab === 'desempenho'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 via-emerald-50/80 to-emerald-100/50">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white border-2 border-emerald-200 text-emerald-600 flex items-center justify-center">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-emerald-700 uppercase text-xs tracking-wider">Retenção de Fêmeas</h6>
                                    <div class="text-sm text-gray-500 mt-1.5">Taxa de retenção ao longo do tempo de vida reprodutiva.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <a href="{{ route('plantel.analises.retencao') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border-2 border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-600 hover:border-emerald-600 hover:text-white transition-all duration-300 shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            Abrir análise
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary-50/50 via-primary-50/30 to-primary-100/20">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white border-2 border-primary-200 text-primary-600 flex items-center justify-center">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Ficha da Matriz</h6>
                                    <div class="text-sm text-gray-500 mt-1.5">Análise de informações e índices gerais de todos os ciclos reprodutivos.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <a href="{{ route('plantel.analises.ficha') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border-2 border-primary-200 bg-white text-primary-700 hover:bg-primary-600 hover:border-primary-600 hover:text-white transition-all duration-300 shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            Abrir análise
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="error && analiseSubTab === 'desempenho'" class="bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="error" x-cloak></div>

        <div x-show="analiseSubTab === 'desempenho'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Fêmeas</h6>
                    <div class="text-sm text-gray-500 mt-1">Ocorrências recentes por causa.</div>
                </div>
                <div class="p-4 sm:p-6 space-y-5">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Compras</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.femeas.compra"></div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Mortes</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.femeas.morte"></div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Descartes</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.femeas.descarte"></div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Vendas</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.femeas.venda"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/20 p-4">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Top causas (morte)</div>
                            <div class="mt-3 space-y-2">
                                <template x-for="row in top.femeas.morte" :key="row.causa">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0 truncate text-gray-700 dark:text-gray-300" x-text="row.causa"></div>
                                        <div class="font-semibold text-gray-900 " x-text="row.total"></div>
                                    </div>
                                </template>
                                <div x-show="top.femeas.morte.length === 0" class="text-sm text-gray-500">Sem registros.</div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/20 p-4">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Top causas (descarte)</div>
                            <div class="mt-3 space-y-2">
                                <template x-for="row in top.femeas.descarte" :key="row.causa">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0 truncate text-gray-700 dark:text-gray-300" x-text="row.causa"></div>
                                        <div class="font-semibold text-gray-900 " x-text="row.total"></div>
                                    </div>
                                </template>
                                <div x-show="top.femeas.descarte.length === 0" class="text-sm text-gray-500">Sem registros.</div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/20 p-4">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Top causas (venda)</div>
                            <div class="mt-3 space-y-2">
                                <template x-for="row in top.femeas.venda" :key="row.causa">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0 truncate text-gray-700 dark:text-gray-300" x-text="row.causa"></div>
                                        <div class="font-semibold text-gray-900 " x-text="row.total"></div>
                                    </div>
                                </template>
                                <div x-show="top.femeas.venda.length === 0" class="text-sm text-gray-500">Sem registros.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Machos</h6>
                    <div class="text-sm text-gray-500 mt-1">Ocorrências recentes por causa.</div>
                </div>
                <div class="p-4 sm:p-6 space-y-5">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Compras</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.machos.compra"></div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Mortes</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.machos.morte"></div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Descartes</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.machos.descarte"></div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white p-3">
                            <div class="text-[11px] font-bold text-gray-500 uppercase">Vendas</div>
                            <div class="text-xl font-bold text-gray-900 mt-1" x-text="totals.machos.venda"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/20 p-4">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Top causas (morte)</div>
                            <div class="mt-3 space-y-2">
                                <template x-for="row in top.machos.morte" :key="row.causa">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0 truncate text-gray-700 dark:text-gray-300" x-text="row.causa"></div>
                                        <div class="font-semibold text-gray-900 " x-text="row.total"></div>
                                    </div>
                                </template>
                                <div x-show="top.machos.morte.length === 0" class="text-sm text-gray-500">Sem registros.</div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/20 p-4">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Top causas (descarte)</div>
                            <div class="mt-3 space-y-2">
                                <template x-for="row in top.machos.descarte" :key="row.causa">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0 truncate text-gray-700 dark:text-gray-300" x-text="row.causa"></div>
                                        <div class="font-semibold text-gray-900 " x-text="row.total"></div>
                                    </div>
                                </template>
                                <div x-show="top.machos.descarte.length === 0" class="text-sm text-gray-500">Sem registros.</div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/20 p-4">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Top causas (venda)</div>
                            <div class="mt-3 space-y-2">
                                <template x-for="row in top.machos.venda" :key="row.causa">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="min-w-0 truncate text-gray-700 dark:text-gray-300" x-text="row.causa"></div>
                                        <div class="font-semibold text-gray-900 " x-text="row.total"></div>
                                    </div>
                                </template>
                                <div x-show="top.machos.venda.length === 0" class="text-sm text-gray-500">Sem registros.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-show="openEditFemea" class="fixed inset-0 z-[110] overflow-y-auto" x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="openEditFemea" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" aria-hidden="true" @click="openEditFemea = false">
            <div class="absolute inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="openEditFemea" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex items-start justify-between mb-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center">
                            <i class="fa-solid fa-pencil text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Editar Fêmea</h3>
                            <p class="text-sm text-gray-500">Alterar dados cadastrais da fêmea</p>
                        </div>
                    </div>
                    <button type="button" @click="openEditFemea = false" class="text-gray-400 hover:text-gray-500 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Identificação -->
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-4">Identificação e Datas</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ID Primíria</label>
                                <input type="text" x-model="editFemeaData.id_primaria" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ID Secundíria</label>
                                <input type="text" x-model="editFemeaData.id_secundaria" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo na compra</label>
                                <select x-model="editFemeaData.tipo_compra" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione...</option>
                                    <option value="leitoa">Leitoa</option>
                                    <option value="matriz_vazia">Matriz vazia</option>
                                    <option value="matriz_gestante">Matriz gestante</option>
                                </select>
                            </div>
                            <div x-show="editFemeaData.tipo_compra !== 'leitoa'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ciclos atê a compra</label>
                                <input type="number" x-model="editFemeaData.ciclos_ate_compra" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data nascimento</label>
                                <div class="relative">
                                    <input type="text" 
                                           x-model="editFemeaData.data_nascimento" 
                                           @input="editFemeaData.data_nascimento = normalizeDateInput($event.target.value)"
                                           @click="activePicker = 'nascimento'"
                                           readonly
                                           class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10 cursor-pointer" 
                                           placeholder="DD/MM/AAAA">
                                    <button type="button" @click="activePicker = 'nascimento'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-calendar"></i>
                                    </button>
                                    
                                    <!-- Calendírio Picker Nascimento -->
                                    <div x-show="activePicker === 'nascimento'" x-cloak 
                                         class="absolute z-[120] mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72 left-0 sm:left-auto sm:right-0" 
                                         @click.away="activePicker = null">
                                        <div class="flex items-center justify-between mb-3 text-gray-900 ">
                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <span class="font-medium" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                <button type="button" 
                                                        @click.stop="selectCalendarDate(day.date)"
                                                        :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                        :disabled="!day.isCurrentMonth"
                                                        class="p-2 text-sm rounded-lg transition-colors"
                                                        x-text="day.day">
                                                </button>
                                            </template>
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + editFemeaData.data_nascimento"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data compra</label>
                                <div class="relative">
                                    <input type="text" 
                                           x-model="editFemeaData.data_compra" 
                                           @input="editFemeaData.data_compra = normalizeDateInput($event.target.value)"
                                           @click="activePicker = 'compra'"
                                           readonly
                                           class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10 cursor-pointer" 
                                           placeholder="DD/MM/AAAA">
                                    <button type="button" @click="activePicker = 'compra'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-calendar"></i>
                                    </button>
                                    
                                    <!-- Calendírio Picker Compra -->
                                    <div x-show="activePicker === 'compra'" x-cloak 
                                         class="absolute z-[120] mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72 left-0 sm:left-auto sm:right-0" 
                                         @click.away="activePicker = null">
                                        <div class="flex items-center justify-between mb-3 text-gray-900 ">
                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <span class="font-medium" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                <button type="button" 
                                                        @click.stop="selectCalendarDate(day.date)"
                                                        :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                        :disabled="!day.isCurrentMonth"
                                                        class="p-2 text-sm rounded-lg transition-colors"
                                                        x-text="day.day">
                                                </button>
                                            </template>
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + editFemeaData.data_compra"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div x-show="editFemeaData.tipo_compra === 'matriz_gestante'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data cobertura (compra)</label>
                                <div class="relative">
                                    <input type="text" 
                                           x-model="editFemeaData.data_cobertura" 
                                           @input="editFemeaData.data_cobertura = normalizeDateInput($event.target.value)"
                                           @click="activePicker = 'cobertura'"
                                           readonly
                                           class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 pr-10 cursor-pointer" 
                                           placeholder="DD/MM/AAAA">
                                    <button type="button" @click="activePicker = 'cobertura'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-calendar"></i>
                                    </button>
                                    
                                    <!-- Calendírio Picker Cobertura -->
                                    <div x-show="activePicker === 'cobertura'" x-cloak 
                                         class="absolute z-[120] mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-72 left-0 sm:left-auto sm:right-0" 
                                         @click.away="activePicker = null">
                                        <div class="flex items-center justify-between mb-3 text-gray-900 ">
                                            <button type="button" @click.stop="prevCalendarMonth()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                                <i class="fa-solid fa-chevron-left"></i>
                                            </button>
                                            <span class="font-medium" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
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
                                                <button type="button" 
                                                        @click.stop="selectCalendarDate(day.date)"
                                                        :class="day.isCurrentMonth ? 'text-gray-900  hover:bg-primary-50 dark:hover:bg-primary-900/30' : 'text-gray-400'"
                                                        :disabled="!day.isCurrentMonth"
                                                        class="p-2 text-sm rounded-lg transition-colors"
                                                        x-text="day.day">
                                                </button>
                                            </template>
                                        </div>
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getSelectedPigDay() : 'Data: ' + editFemeaData.data_cobertura"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Genêtica e Fornecedor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-4">Genêtica</div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Raça</label>
                                <select x-model="editFemeaData.raca_id" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione...</option>
                                    <template x-for="r in racas" :key="'edit-raca-' + r.id">
                                        <option :value="String(r.id)" x-text="r.nome"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                            <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-4">Origem</div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor</label>
                                <select x-model="editFemeaData.fornecedor_id" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione...</option>
                                    <template x-for="f in fornecedores" :key="'edit-forn-' + f.id">
                                        <option :value="String(f.id)" x-text="f.nome"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Valores e Características -->
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-4">Complementares</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Peso na compra</label>
                                <div class="relative">
                                    <input type="number" step="0.01" x-model="editFemeaData.peso_compra" class="w-full pr-10 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valor da compra</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400">R$</span>
                                    <input type="number" step="0.01" x-model="editFemeaData.valor_compra" class="w-full pl-9 shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="0,00">
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Características</label>
                                <textarea x-model="editFemeaData.caracteristicas" rows="2" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Opcional"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Localização -->
                    <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-4">Localização</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Galpão / Localização</label>
                                <input type="text" x-model="editFemeaData.localizacao" list="util-localizacoes" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Gestação A">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sala / Baia</label>
                                <input type="text" x-model="editFemeaData.baia" list="util-baias" class="w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Baia 05">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" @click="openEditFemea = false" :disabled="saving" class="inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-6 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Cancelar
                </button>
                <button type="button" @click="saveEditFemea()" :disabled="saving" class="inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-8 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                    <template x-if="!saving"><span>Salvar Alterações</span></template>
                    <template x-if="saving"><span>Salvando...</span></template>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="tab === 'relatorios'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-primary-50/70 via-primary-50/40 to-emerald-50/40">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-white border-2 border-primary-200 text-primary-600 flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                    </div>
                    <div>
                        <h5 class="font-bold text-primary-800 uppercase text-sm tracking-wider">Relatórios e Formulários</h5>
                        <div class="text-sm text-gray-500 mt-1">Geração de PDFs, CSVs e formulários para impressão do plantel reprodutivo.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary-50/70 via-primary-50/50 to-primary-100/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white border-2 border-primary-200 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-cow"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-primary-800 uppercase text-xs tracking-wider">Relatório de Fêmeas</h6>
                            <div class="text-sm text-gray-500 mt-1">Lista completa do plantel de fêmeas com status e última operação.</div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <a href="{{ route('admin.relatorios.plantel.femeas.filter', [], false) }}" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                        <i class="fa-solid fa-sliders mr-2"></i>
                        Filtrar e gerar
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary-50/70 via-primary-50/50 to-primary-100/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white border-2 border-primary-200 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-mars"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-primary-800 uppercase text-xs tracking-wider">Relatório de Machos</h6>
                            <div class="text-sm text-gray-500 mt-1">Lista completa do plantel de machos com status e última operação.</div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <a href="{{ route('admin.relatorios.plantel.machos.filter', [], false) }}" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                        <i class="fa-solid fa-sliders mr-2"></i>
                        Filtrar e gerar
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all md:col-span-2">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary-50/70 via-primary-50/50 to-emerald-50/40">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border-2 border-primary-200 text-primary-700 flex items-center justify-center">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <div>
                                <h6 class="font-bold text-primary-800 uppercase text-xs tracking-wider">Formulário cio de leitoa</h6>
                                <div class="text-sm text-gray-500 mt-1.5">Abrir PDF para impressão e preenchimento.</div>
                            </div>
                        </div>
                        <a href="{{ route('plantel.analises.formularios.cio-leitoa.pdf', [], false) }}" target="_blank" class="text-primary-600 hover:text-primary-700 transition-colors" title="Abrir em nova aba">
                            <i class="fa-solid fa-arrow-up-right-from-square text-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <a href="{{ route('plantel.analises.formularios.cio-leitoa.pdf', [], false) }}" target="_blank" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                        <i class="fa-solid fa-eye mr-2"></i>
                        Abrir formulário
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.API_BASE_URL = '/api';
    window.PIG_CYCLE_DAYS = {
        gestacao: 114,
        lactacao: 21,
        intervalo: 7,
        recria: 63,
        terminacao: 70,
        cio: 3
    };

    // Definir constante global para o calendírio PIG
    window.PIG_BASE_DATE = '1968-12-31';
    var PIG_BASE_DATE = window.PIG_BASE_DATE;

    function toPigDay(date) {
        if (!date) return null;
        const start = new Date(PIG_BASE_DATE + 'T12:00:00');
        const end = new Date(date + (date.length <= 10 ? 'T12:00:00' : ''));
        end.setHours(12, 0, 0, 0);
        const diff = Math.round((end.getTime() - start.getTime()) / 86400000);
        
        // Dia PIG Absoluto = quantidade de dias desde 31/12/1968
        const absoluteDay = diff;
        
        // Dia PIG = últimos 3 dígitos do Dia PIG Absoluto
        return absoluteDay % 1000;
    }

    function pigDayToDate(pigDay) {
        if (!pigDay || isNaN(pigDay)) return null;
        
        // Converter para número
        pigDay = Number(pigDay);
        
        // Validar que o dia PIG está entre 0 e 999
        if (pigDay < 0 || pigDay > 999) return null;
        
        const start = new Date(PIG_BASE_DATE + 'T12:00:00');
        const today = new Date();
        today.setHours(12, 0, 0, 0);
        
        // Dia PIG Absoluto atual
        const currentAbsoluteDay = Math.round((today.getTime() - start.getTime()) / 86400000);
        
        // Encontrar o milhar mais recente que tenha os últimos 3 dígitos = pigDay
        const currentThousand = Math.floor(currentAbsoluteDay / 1000) * 1000;
        let targetAbsoluteDay = currentThousand + pigDay;
        
        // Se o dia alvo for maior que o atual, voltar um milhar
        if (targetAbsoluteDay > currentAbsoluteDay) {
            targetAbsoluteDay -= 1000;
        }
        
        // Garantir que o dia alvo não seja negativo
        if (targetAbsoluteDay < 0) return null;
        
        const targetDate = new Date(start.getTime() + targetAbsoluteDay * 86400000);
        
        // Validar que a data ê vílida
        if (isNaN(targetDate.getTime())) return null;
        
        return targetDate.toISOString().split('T')[0];
    }

    function calculatePigCycle(coverageDate, referenceDate = new Date(), calendarType = 'gregoriano', config = {}) {
        const durations = calendarType === '1000_dias' ? PIG_CYCLE_DAYS : {
            gestacao: Number(config.gestacao_dias) || 114,
            lactacao: Number(config.lactacao_max_dias) || 21,
            intervalo: Number(config.intervalo_desmame_cio_dias) || 5,
            recria: 63,
            terminacao: 70,
            cio: Number(config.cio_dias) || 3
        };

        const start = new Date(coverageDate);
        if (isNaN(start.getTime())) return null;

        const ref = new Date(referenceDate);
        ref.setHours(0, 0, 0, 0);

        const expectedBirthDate = new Date(start);
        expectedBirthDate.setDate(start.getDate() + durations.gestacao);

        const weaningDate = new Date(expectedBirthDate);
        weaningDate.setDate(expectedBirthDate.getDate() + durations.lactacao);

        const nextCioDate = new Date(weaningDate);
        nextCioDate.setDate(weaningDate.getDate() + durations.intervalo);

        const endCioDate = new Date(nextCioDate);
        endCioDate.setDate(nextCioDate.getDate() + durations.cio);

        const diffDays = (d1, d2) => {
            const t1 = new Date(d1).setHours(0, 0, 0, 0);
            const t2 = new Date(d2).setHours(0, 0, 0, 0);
            return Math.floor((t2 - t1) / (1000 * 60 * 60 * 24));
        };

        const formatDisplay = (date) => {
            if (!date) return '-';
            if (calendarType === '1000_dias') {
                return toPigDay(date);
            }
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            return `${dd}/${mm}/${yyyy}`;
        };

        const totalDaysElapsed = diffDays(start, ref);
        let currentPhase = 'concluido';
        let currentPhaseLabel = 'Concluído';
        let nextPhaseLabel = '-';
        let previstaEm = null;

        if (ref < expectedBirthDate) {
            currentPhase = 'gestacao';
            currentPhaseLabel = 'Gestação';
            nextPhaseLabel = 'Parto';
            previstaEm = expectedBirthDate;
        } else if (ref < weaningDate) {
            currentPhase = 'lactacao';
            currentPhaseLabel = 'Lactação';
            nextPhaseLabel = 'Desmame';
            previstaEm = weaningDate;
        } else if (ref < nextCioDate) {
            currentPhase = 'intervalo';
            currentPhaseLabel = 'Intervalo desmame-cio';
            nextPhaseLabel = 'Cio p??s-desmame';
            previstaEm = nextCioDate;
        } else if (ref <= endCioDate) {
            currentPhase = 'cio';
            currentPhaseLabel = 'Cio p??s-desmame';
            nextPhaseLabel = 'Cobertura';
            previstaEm = nextCioDate;
        }

        return {
            coverageDate: start,
            expectedBirthDate,
            weaningDate,
            nextCioDate,
            endCioDate,
            currentPhase,
            currentPhaseLabel,
            nextPhaseLabel,
            previstaEm,
            totalDaysElapsed,
            displayExpectedBirth: formatDisplay(expectedBirthDate),
            displayWeaning: formatDisplay(weaningDate),
            displayNextCio: formatDisplay(nextCioDate),
            displayPrevistaEm: formatDisplay(previstaEm)
        };
    }
</script>
@endsection
