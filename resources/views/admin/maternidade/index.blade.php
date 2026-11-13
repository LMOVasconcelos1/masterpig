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
    matrizesAptas: {{ json_encode($matrizesAptas, JSON_UNESCAPED_UNICODE) }},
    matrizPartoSearch: '',
    femeasLactantes: {{ json_encode($femeasLactantesFull, JSON_UNESCAPED_UNICODE) }},
    lotesCreche: {{ json_encode($lotesCreche ?? [], JSON_UNESCAPED_UNICODE) }},
    usuarios: {{ json_encode($usuarios ?? [], JSON_UNESCAPED_UNICODE) }},
    morteCausas: {{ json_encode($morteCausas, JSON_UNESCAPED_UNICODE) }},
    criteriosLoaded: false,
    calendarType: 'gregoriano',
    activePicker: null,
    calendarMonth: new Date().getMonth(),
    calendarYear: new Date().getFullYear(),
    calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    partoPickerTop: 0,
    partoPickerLeft: 0,
    partoPickerDirection: 'down',
    desmamePickerTop: 0,
    desmamePickerLeft: 0,
    desmamePickerDirection: 'down',
    mortePickerTop: 0,
    mortePickerLeft: 0,
    mortePickerDirection: 'down',
    partoForm: {
        femea_id: '',
        cobertura_id: '',
        dataIso: '{{ date('Y-m-d') }}',
        dataTerminoIso: '',
        data: '',
        hora_inicio: '',
        hora_termino: '',
        total_vivos: 0,
        total_mortos: 0,
        total_mumificados: 0,
        qtd_femeas_vivas: 0,
        qtd_machos_vivos: 0,
        peso_total_leitoes_kg: '',
        peso_medio_digitado: '',
        peso_mode: 'total',
        localizacao_matriz_parto: '',
        baia: '',
        peso_matriz_kg: '',
        funcionario_nome: '',
        tipo_intervencao: 'nenhum',
        escore_corporal: '',
        caracteristicas_parto: '',
        lote: '',
        observacao: ''
    },
    partoPickerTop: 0,
    partoPickerLeft: 0,
    partoPickerDirection: 'down',
    partoTerminoPickerTop: 0,
    partoTerminoPickerLeft: 0,
    partoTerminoPickerDirection: 'down',
    morteForm: {
        femea_id: '',
        parto_id: '',
        quantidade: 1,
        disponiveis: 0,
        dataIso: '{{ date('Y-m-d') }}',
        data: '',
        causa_id: ''
    },
    desmameForm: {
        femea_id: '',
        parto_id: '',
        dataIso: '{{ date('Y-m-d') }}',
        data: '',
        quantidade: '',
        peso_medio: '',
        lote_destino_id: '',
        localizacao_destino: '',
        destino_matriz: '',
        baia_matriz: '',
        peso_matriz: '',
        escore_corporal: '',
        caracteristicas_desmame: '',
        funcionario: '',
        observacao: ''
    },
    desmameFemeaSearch: '',
    
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
    setPartoTerminoDateIso(iso) {
        const v = String(iso || '').trim();
        this.partoForm.dataTerminoIso = v;
        if (!v) {
            this.partoForm.dataTermino = '';
            return;
        }
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.partoForm.dataTermino = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.partoForm.dataTermino = this.isoToBr(v);
        }
    },
    get totalNascidos() {
        return (Number(this.partoForm.total_vivos) || 0)
             + (Number(this.partoForm.total_mortos) || 0)
             + (Number(this.partoForm.total_mumificados) || 0);
    },
    get pesoTotalCalculadoKg() {
        if (this.partoForm.peso_mode !== 'medio') return null;
        const medio = Number(String(this.partoForm.peso_medio_digitado || '').replace(',', '.')) || 0;
        const vivos = Number(this.partoForm.total_vivos) || 0;
        if (vivos <= 0 || medio <= 0) return 0;
        return Math.round(medio * vivos * 100) / 100;
    },
    get pesoMedioCalculadoKg() {
        if (this.partoForm.peso_mode !== 'total') return null;
        const total = Number(String(this.partoForm.peso_total_leitoes_kg || '').replace(',', '.')) || 0;
        const vivos = Number(this.partoForm.total_vivos) || 0;
        if (vivos <= 0 || total <= 0) return 0;
        return Math.round((total / vivos) * 100) / 100;
    },
    syncPesoModeInverso() {
        if (this.partoForm.peso_mode === 'medio') {
            this.partoForm.peso_total_leitoes_kg = this.pesoTotalCalculadoKg || '';
        } else {
            this.partoForm.peso_medio_digitado = this.pesoMedioCalculadoKg || '';
        }
    },
    setPesoMode(newMode) {
        if (newMode === this.partoForm.peso_mode) return;
        if (newMode === 'medio') {
            const medio = this.pesoMedioCalculadoKg;
            this.partoForm.peso_medio_digitado = medio > 0 ? String(medio).replace('.', ',') : '';
        } else {
            const total = this.pesoTotalCalculadoKg;
            this.partoForm.peso_total_leitoes_kg = total > 0 ? String(total).replace('.', ',') : '';
        }
        this.partoForm.peso_mode = newMode;
    },
    get duracaoParto() {
        const d1 = String(this.partoForm.dataIso || '').trim();
        const d2 = String(this.partoForm.dataTerminoIso || '').trim();
        const h1 = String(this.partoForm.hora_inicio || '').trim();
        const h2 = String(this.partoForm.hora_termino || '').trim();
        if (!d1 || !d2 || !h1 || !h2) return '';
        try {
            const t1 = new Date(d1 + 'T' + (h1.length === 5 ? h1 + ':00' : h1));
            const t2 = new Date(d2 + 'T' + (h2.length === 5 ? h2 + ':00' : h2));
            const diffMs = t2.getTime() - t1.getTime();
            if (!isFinite(diffMs) || diffMs <= 0) return '';
            const totalMin = Math.round(diffMs / 60000);
            const h = Math.floor(totalMin / 60);
            const m = totalMin % 60;
            return `${h}h ${String(m).padStart(2,'0')}min`;
        } catch (e) { return ''; }
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
        if (this.activePicker === 'desmame') return String(this.desmameForm.dataIso || '');
        if (this.activePicker === 'morte') return String(this.morteForm.dataIso || '');
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
        if (this.activePicker === 'parto') this.setPartoDateIso(iso);
        if (this.activePicker === 'parto_termino') this.setPartoTerminoDateIso(iso);
        if (this.activePicker === 'desmame') this.setDesmameDateIso(iso);
        if (this.activePicker === 'morte') this.setMorteDataIso(iso);
        this.activePicker = null;
    },
    openPartoTerminoDatePicker() {
        const iso = String(this.partoForm.dataTerminoIso || '').trim();
        const base = iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? new Date(iso + 'T12:00:00') : new Date();
        this.calendarMonth = base.getMonth();
        this.calendarYear = base.getFullYear();
        this.activePicker = 'parto_termino';
        this.$nextTick(() => {
            const el = this.$refs && this.$refs.partoTerminoDateInput ? this.$refs.partoTerminoDateInput : null;
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

            this.partoTerminoPickerLeft = left;
            this.partoTerminoPickerTop = top;
            this.partoTerminoPickerDirection = direction;
        });
    },
    openDesmameDatePicker() {
        const iso = String(this.desmameForm.dataIso || '').trim();
        const base = iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? new Date(iso + 'T12:00:00') : new Date();
        this.calendarMonth = base.getMonth();
        this.calendarYear = base.getFullYear();
        this.activePicker = 'desmame';
        this.$nextTick(() => {
            const el = this.$refs && this.$refs.desmameDateInput ? this.$refs.desmameDateInput : null;
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

            this.desmamePickerLeft = left;
            this.desmamePickerTop = top;
            this.desmamePickerDirection = direction;
        });
    },
    openMorteDatePicker() {
        const iso = String(this.morteForm.dataIso || '').trim();
        const base = iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? new Date(iso + 'T12:00:00') : new Date();
        this.calendarMonth = base.getMonth();
        this.calendarYear = base.getFullYear();
        this.activePicker = 'morte';
        this.$nextTick(() => {
            const el = this.$refs && this.$refs.morteDateInput ? this.$refs.morteDateInput : null;
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
    handlePartoSubmit(e) {
        this.selecionarMatrizPartoPorIdPrimaria(true);
        if (!this.partoForm.femea_id) {
            e.preventDefault();
            alert('Selecione a matriz.');
            return;
        }
        if (!this.partoForm.dataIso) {
            e.preventDefault();
            alert('Informe a Data início do parto.');
            return;
        }
        const vivos = Number(this.partoForm.total_vivos) || 0;
        const femeas = Number(this.partoForm.qtd_femeas_vivas) || 0;
        const machos = Number(this.partoForm.qtd_machos_vivos) || 0;
        if ((femeas > 0 || machos > 0) && (femeas + machos !== vivos)) {
            e.preventDefault();
            alert(`Fêmeas (${femeas}) + Machos (${machos}) deve ser igual a Nascidos Vivos (${vivos}).`);
            return;
        }
    },
    resetPartoForm() {
        const hoje = '{{ date('Y-m-d') }}';
        this.partoForm = {
            femea_id: '',
            cobertura_id: '',
            dataIso: hoje,
            dataTerminoIso: '',
            data: '',
            hora_inicio: '',
            hora_termino: '',
            total_vivos: 0,
            total_mortos: 0,
            total_mumificados: 0,
            qtd_femeas_vivas: 0,
            qtd_machos_vivos: 0,
            peso_total_leitoes_kg: '',
            peso_medio_digitado: '',
            peso_mode: 'total',
            localizacao_matriz_parto: '',
            baia: '',
            peso_matriz_kg: '',
            funcionario_nome: '',
            tipo_intervencao: 'nenhum',
            escore_corporal: '',
            caracteristicas_parto: '',
            lote: '',
            observacao: ''
        };
        this.setPartoDateIso(hoje);
        this.setPartoTerminoDateIso('');
        this.matrizPartoSearch = '';
    },
    init() {
        this.setPartoDateIso(this.partoForm.dataIso);
        this.setMorteDataIso(this.morteForm.dataIso);
        this.$watch('showDesmameModal', (open) => {
            if (!open) return;
            this.hydrateDesmameFromPartoId(this.selectedPartoId);
        });
        this.$watch('showPartoModal', (open) => {
            if (!open) this.resetPartoForm();
        });
        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                this.calendarType = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '') ? 'gregoriano' : String(items.criterio_calendario_tipo);
                this.setPartoDateIso(this.partoForm.dataIso);
                this.setDesmameDateIso('{{ date('Y-m-d') }}');
                this.setMorteDataIso(this.morteForm.dataIso);
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

    resetDesmameForm() {
        this.desmameForm = {
            femea_id: '',
            parto_id: '',
            dataIso: '',
            data: '',
            quantidade: '',
            peso_medio: '',
            lote_destino_id: '',
            localizacao_destino: '',
            destino_matriz: '',
            baia_matriz: '',
            peso_matriz: '',
            escore_corporal: '',
            caracteristicas_desmame: '',
            funcionario: '',
            observacao: ''
        };
        this.desmameFemeaSearch = '';
        this.setDesmameDateIso('{{ date('Y-m-d') }}');
    },
    setDesmameDateIso(iso) {
        const v = String(iso || '').trim();
        if (!v) {
            this.desmameForm.dataIso = '';
            this.desmameForm.data = '';
            return;
        }
        this.desmameForm.dataIso = v;
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.desmameForm.data = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.desmameForm.data = this.isoToBr(v);
        }
    },
    setMorteDataIso(iso) {
        const v = String(iso || '').trim();
        if (!v) {
            this.morteForm.dataIso = '';
            this.morteForm.data = '';
            return;
        }
        this.morteForm.dataIso = v;
        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
            this.morteForm.data = String(toPigDay(v + 'T00:00:00'));
        } else {
            this.morteForm.data = this.isoToBr(v);
        }
    },
    normalizeMorteDisplay() {
        const isPig = this.calendarType === '1000_dias';
        const raw = String(this.morteForm.data || '').trim();
        if (!raw) { this.setMorteDataIso(''); return; }
        let iso = null;
        if (isPig && /^\d{1,4}$/.test(raw) && typeof pigDayToDate === 'function') iso = pigDayToDate(raw);
        if (!iso) iso = this.brToIso(raw);
        if (iso) this.setMorteDataIso(iso);
    },
    openDesmameModal(partoId = null) {
        this.resetDesmameForm();
        this.selectedPartoId = partoId;
        this.showDesmameModal = true;
    },
    getDesmameSelectedLactante() {
        const pid = this.selectedPartoId || this.desmameForm.parto_id;
        if (pid) {
            return this.femeasLactantes.find(f => String(f.parto_id) === String(pid)) || null;
        }
        const fid = this.desmameForm.femea_id;
        if (fid) {
            return this.femeasLactantes.find(f => String(f.id) === String(fid)) || null;
        }
        return null;
    },
    hydrateDesmameFromPartoId(partoId) {
        if (!partoId) return;
        const selected = this.femeasLactantes.find(f => String(f.parto_id) === String(partoId));
        if (!selected) return;
        this.desmameForm.femea_id = String(selected.id);
        this.desmameForm.parto_id = String(selected.parto_id);
        this.desmameFemeaSearch = String(selected.id_primaria || '');
    },
    selecionarMatrizDesmamePorIdPrimaria(strict = false) {
        const q = String(this.desmameFemeaSearch || '').trim().toLowerCase();
        if (!q) {
            if (!strict) this.resetDesmameForm();
            return;
        }
        const exact = this.femeasLactantes.find((f) => String(f?.id_primaria || '').toLowerCase() === q);
        if (strict && !exact) return;
        const match = exact || this.femeasLactantes.find((f) => String(f?.id_primaria || '').toLowerCase().includes(q));
        if (!match) return;
        this.desmameForm.femea_id = String(match.id);
        this.desmameForm.parto_id = String(match.parto_id);
        this.selectedPartoId = match.parto_id;
        this.desmameFemeaSearch = String(match.id_primaria || this.desmameFemeaSearch);
    },
    desmamePesoTotal() {
        const q = Number(String(this.desmameForm.quantidade || '').replace(',', '.'));
        const pm = Number(String(this.desmameForm.peso_medio || '').replace(',', '.'));
        if (!Number.isFinite(q) || !Number.isFinite(pm)) return 0;
        return q * pm;
    },
}" x-init="init()">
    <!-- Header & Topbar -->
    <div>
        <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Maternidade</h2>
                <p class="text-sm text-white">Visão geral e lançamentos operacionais</p>
            </div>
            <nav class="flex justify-center space-x-6 sm:space-x-8 overflow-x-auto mt-6 px-2">
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
                <button type="button" @click="tab = 'relatorios'" 
                    :class="tab === 'relatorios' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Relatórios
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
                                <button @click="openDesmameModal({{ $inc['parto_id'] }})" 
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
                                <td class="px-6 py-4 text-sm text-navy-900">{{ \App\Services\PigCycleService::formatDisplayDate(\Carbon\Carbon::parse($parto->data)) }}</td>
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
        <div x-show="subTab === 'desmames'" class="space-y-4">
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-bold text-navy-900 uppercase tracking-wider">Desmames</h4>
                <button @click="openDesmameModal(null)"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-bold rounded-card hover:bg-primary-700 transition-colors">
                    <i class="fa-solid fa-plus mr-2"></i> Novo Desmame
                </button>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fêmea</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Desmamados</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Peso Médio</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Peso Total</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lote Destino</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Localização Destino</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Destino Matriz</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuário</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($desmamesRegistrados as $d)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-navy-900">{{ \App\Services\PigCycleService::formatDisplayDate(\Carbon\Carbon::parse($d->data)) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-navy-900">
                                    {{ (string) $d->id_primaria . ($d->id_secundaria ? " ({$d->id_secundaria})" : "") }}
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-navy-900">{{ $d->quantidade }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $d->peso_medio !== null ? number_format((float) $d->peso_medio, 2, ',', '.') . ' kg' : '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @php
                                        $pesoTotal = ($d->peso_medio !== null && $d->quantidade !== null) ? ((float) $d->peso_medio * (int) $d->quantidade) : null;
                                    @endphp
                                    {{ $pesoTotal !== null ? number_format($pesoTotal, 2, ',', '.') . ' kg' : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $d->lote_destino ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $d->localizacao_destino ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $d->destino_matriz ?? '-' }}{{ ($d->baia_matriz ?? null) ? ' - ' . $d->baia_matriz : '' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $d->funcionario ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-text-muted italic">
                                    Nenhum desmame registrado recentemente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuário</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($mortesLeitaoRegistradas as $morte)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-navy-900">
                                    {{ \App\Services\PigCycleService::formatDisplayDate(\Carbon\Carbon::parse($morte->data)) }} {{ $morte->hora ? \Carbon\Carbon::parse($morte->hora)->format('H:i') : '' }}
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

    <!-- Conteúdo: Relatórios -->
    <div x-show="tab === 'relatorios'" x-cloak class="space-y-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
        <!-- Cabeçalho da aba -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">
                        <i class="fa-solid fa-file-lines text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-gray-100">Relatórios e Formulários</h3>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">Documentos operacionais para impressão e uso em campo</p>
                    </div>
                </div>
            </div>

            <!-- Grid mobile-friendly -->
            <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">

                <!-- Card: Formulário de Parto -->
                <div class="group relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-500 via-primary-400 to-amber-400"></div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="p-2.5 rounded-xl bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 group-hover:bg-primary-500 group-hover:text-white transition-colors duration-300">
                                <i class="fa-solid fa-baby text-lg sm:text-xl"></i>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-600/20">
                                <i class="fa-solid fa-check-double"></i> Novo
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-gray-100 leading-snug">Formulário de Parto</h4>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 leading-relaxed min-h-[3rem]">
                                Formulário padrão para registro diário de partos na maternidade. Campos: identificação, tipo de intervenção, horários, número de vivos, natimortos, mumificados, peso e escore.
                            </p>
                        </div>
                        <div class="pt-2 flex flex-col sm:flex-row gap-2.5">
                            <a href="{{ route('maternidade.relatorios.formulario-parto.pdf') }}" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white text-sm font-bold transition-colors duration-200 shadow-sm hover:shadow">
                                <i class="fa-solid fa-file-pdf text-base"></i>
                                <span>Baixar PDF</span>
                            </a>
                            <a href="{{ route('maternidade.relatorios.formulario-parto.pdf') }}?preview=1" target="_blank"
                               class="sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-semibold transition-colors duration-200">
                                <i class="fa-solid fa-eye"></i>
                                <span class="sm:hidden">Ver</span>
                                <span class="hidden sm:inline">Visualizar</span>
                            </a>
                        </div>
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700/80 flex items-center justify-between text-[10px] sm:text-xs text-gray-400 dark:text-gray-500">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-tag"></i>
                                <span>{{ \App\Models\Configuracao::getGranjaAtual() }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium">
                                <i class="fa-solid fa-file-arrow-down"></i>
                                A4 · Retrato
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card em branco (placeholder) para próximo relatório -->
                <div class="group relative bg-white dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl overflow-hidden opacity-80 hover:opacity-100 transition-opacity">
                    <div class="p-5 h-full flex flex-col items-center justify-center text-center space-y-2 py-10">
                        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500 group-hover:text-primary-500 transition-colors">
                            <i class="fa-solid fa-plus text-2xl"></i>
                        </div>
                        <div>
                            <h5 class="text-sm font-bold text-gray-500 dark:text-gray-400">Próximos relatórios</h5>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Novos formulários em breve</p>
                        </div>
                    </div>
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
                <form accept-charset="UTF-8" action="{{ route('maternidade.mortes.store') }}" method="POST" class="p-6 space-y-4">
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
                            <input type="hidden" name="data" :value="morteForm.dataIso">
                            <div class="relative">
                                <input type="text"
                                       x-model="morteForm.data"
                                       x-ref="morteDateInput"
                                       @click="openMorteDatePicker()"
                                       @blur="normalizeMorteDisplay()"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm pr-10"
                                       :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       required>
                                <button type="button" @click="openMorteDatePicker()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <i class="fa-solid fa-calendar"></i>
                                </button>

                                <div x-show="activePicker === 'morte'"
                                     x-cloak
                                     :style="`top:${mortePickerTop}px; left:${mortePickerLeft}px;`"
                                     :class="mortePickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
                            <select name="funcionario" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Selecione</option>
                                <template x-for="u in usuarios" :key="`um-${u.id}`">
                                    <option :value="u.name" x-text="u.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Causa da Morte</label>
                        <select name="causa_id" x-model="morteForm.causa_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Selecione uma causa</option>
                            <template x-for="c in morteCausas" :key="c.id">
                                <option :value="c.id" x-text="c.nome"></option>
                            </template>
                        </select>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showMorteModal = false" class="px-4 py-2 text-sm font-medium text-gray-500">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-lg">Salvar Morte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Parto (MOBILE FIRST) -->
    <div x-show="showPartoModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="min-h-screen flex items-end sm:items-center justify-center">
            <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70" @click="showPartoModal = false"></div>
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-900 sm:rounded-2xl rounded-t-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800 sm:my-6 my-0 max-h-[94vh] sm:max-h-[92vh] flex flex-col">
                <!-- Header (drag handle mobile) -->
                <div class="px-5 sm:px-6 pt-4 pb-3 sm:py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/60 sticky top-0 z-10">
                    <div class="sm:hidden w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mb-3"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400">
                                <i class="fa-solid fa-baby"></i>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-extrabold text-gray-900 dark:text-gray-100 leading-tight">Incluir Parto</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 hidden sm:block">Registre um novo parto com todos os dados operacionais</p>
                            </div>
                        </div>
                        <button @click="showPartoModal = false"
                                class="p-2.5 -mr-2 rounded-xl hover:bg-gray-200/70 dark:hover:bg-gray-700/60 transition-colors text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Body (scroll) -->
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-5">
                    <form accept-charset="UTF-8" action="{{ route('maternidade.partos.store') }}" method="POST"
                          class="space-y-5 text-gray-800 dark:text-gray-200"
                          @submit="handlePartoSubmit($event)">
                        @csrf
                        <input type="hidden" name="cobertura_id" x-model="partoForm.cobertura_id">

                        <!-- 🔹 Seção: Matriz e Lote -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold mb-1.5 text-gray-700 dark:text-gray-200 uppercase tracking-wide">Fêmea (Matriz) *</label>
                                <input type="text"
                                       x-model="matrizPartoSearch"
                                       list="matrizes-parto-list"
                                       @keydown.enter.prevent="selecionarMatrizPartoPorIdPrimaria(false)"
                                       @change="selecionarMatrizPartoPorIdPrimaria(true)"
                                       class="w-full h-12 px-3.5 shadow-sm text-base sm:text-sm border-gray-300 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Digite o brinco e pressione Enter"
                                       autocomplete="off">
                                <datalist id="matrizes-parto-list">
                                    <template x-for="f in matrizesAptasFiltradas.slice(0, 60)" :key="`mp-${f.id}`">
                                        <option :value="f.id_primaria" x-text="f.id_secundaria ? `${f.id_primaria} / ${f.id_secundaria}` : f.id_primaria"></option>
                                    </template>
                                </datalist>
                                <input type="hidden" name="femea_id" x-model="partoForm.femea_id">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1.5 text-gray-700 dark:text-gray-200 uppercase tracking-wide">Lote (opcional)</label>
                                <input type="text" name="lote" x-model="partoForm.lote"
                                       class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100"
                                       placeholder="Ex: Lote 2026-09">
                            </div>
                        </div>

                        <!-- 🔹 Seção: Dados do parto -->
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100/60 dark:from-gray-800/60 dark:to-gray-800/30 rounded-2xl border border-gray-200/80 dark:border-gray-700/80 p-4 sm:p-5 space-y-5">
                            <h4 class="text-xs sm:text-sm font-extrabold uppercase tracking-[0.14em] text-primary-700 dark:text-primary-400 flex items-center gap-2">
                                <i class="fa-solid fa-circle-info"></i>
                                Dados do parto
                            </h4>

                            <!-- Datas + Horários + Duração (mobile: 2 cols; desktop: 5 cols) -->
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-3.5">
                                <div class="col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Data início *</label>
                                    <input type="hidden" name="data" :value="partoForm.dataIso">
                                    <div class="relative">
                                        <input type="text"
                                               x-model="partoForm.data"
                                               x-ref="partoDateInput"
                                               @click="openPartoDatePicker()"
                                               class="w-full h-12 pl-3.5 pr-11 text-base sm:text-sm border-gray-300 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                               :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM'"
                                               inputmode="numeric"
                                               autocomplete="off"
                                               readonly>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-gray-400">
                                            <i class="fa-regular fa-calendar text-base"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Hora início</label>
                                    <input type="time" name="hora_inicio" x-model="partoForm.hora_inicio"
                                           class="w-full h-12 px-3 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Hora fim</label>
                                    <input type="time" name="hora_termino" x-model="partoForm.hora_termino"
                                           class="w-full h-12 px-3 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Data fim</label>
                                    <input type="hidden" name="data_termino" :value="partoForm.dataTerminoIso">
                                    <div class="relative">
                                        <input type="text"
                                               x-model="partoForm.dataTermino"
                                               x-ref="partoTerminoDateInput"
                                               @click="openPartoTerminoDatePicker()"
                                               class="w-full h-12 pl-3.5 pr-11 text-base sm:text-sm border-gray-300 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                               :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM'"
                                               inputmode="numeric"
                                               autocomplete="off"
                                               readonly>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-gray-400">
                                            <i class="fa-regular fa-calendar-check text-base"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Duração</label>
                                    <div class="w-full h-12 px-3.5 rounded-2xl bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800/50 flex items-center justify-center text-center">
                                        <span class="text-sm sm:text-base font-extrabold text-primary-700 dark:text-primary-300 tracking-tight"
                                              x-text="duracaoParto || '—'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Nascimentos: vivos, natimortos, mumificados, total (mobile: 2 cols; desktop: 4 cols) -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5">
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Nascidos Vivos *</label>
                                    <input type="number" name="total_vivos" x-model.number="partoForm.total_vivos" value="0" min="0" required
                                           inputmode="numeric"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xl sm:text-lg font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Natimortos *</label>
                                    <input type="number" name="total_mortos" x-model.number="partoForm.total_mortos" value="0" min="0" required
                                           inputmode="numeric"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xl sm:text-lg font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Mumificados *</label>
                                    <input type="number" name="total_mumificados" x-model.number="partoForm.total_mumificados" value="0" min="0" required
                                           inputmode="numeric"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xl sm:text-lg font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Total nascidos</label>
                                    <div class="w-full h-12 px-3.5 rounded-2xl bg-gray-900 dark:bg-gray-700 border border-gray-800 dark:border-gray-600 flex items-center justify-between">
                                        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-300">Total</span>
                                        <span class="text-2xl sm:text-xl font-black text-white dark:text-gray-50 leading-none tracking-tight"
                                              x-text="totalNascidos"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Fêmeas / Machos (mobile: 2 cols; desktop: 3 cols com card span direito ocupando 1) -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-3.5">
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Fêmeas ♀</label>
                                    <div class="relative">
                                        <input type="number" name="qtd_femeas_vivas" x-model.number="partoForm.qtd_femeas_vivas" value="0" min="0"
                                               inputmode="numeric"
                                               class="w-full h-12 pl-3.5 pr-11 rounded-2xl border-pink-200 dark:border-pink-900/50 bg-pink-50/60 dark:bg-pink-900/10 text-xl sm:text-lg font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-pink-400 focus:border-pink-400">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-pink-500 dark:text-pink-400">
                                            <i class="fa-solid fa-venus text-base"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Machos ♂</label>
                                    <div class="relative">
                                        <input type="number" name="qtd_machos_vivos" x-model.number="partoForm.qtd_machos_vivos" value="0" min="0"
                                               inputmode="numeric"
                                               class="w-full h-12 pl-3.5 pr-11 rounded-2xl border-blue-200 dark:border-blue-900/50 bg-blue-50/60 dark:bg-blue-900/10 text-xl sm:text-lg font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-blue-500 dark:text-blue-400">
                                            <i class="fa-solid fa-mars text-base"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">F + M vs Vivos</label>
                                    <div class="w-full h-12 px-3.5 rounded-2xl flex items-center justify-center"
                                         :class="(qtd_femeas_vivas + qtd_machos_vivos === total_vivos || qtd_femeas_vivas + qtd_machos_vivos === 0)
                                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 text-amber-700 dark:text-amber-300'">
                                        <span class="text-xs sm:text-sm font-bold"
                                              x-text="(Number(partoForm.qtd_femeas_vivas || 0) + Number(partoForm.qtd_machos_vivos || 0) === Number(partoForm.total_vivos || 0)) || (Number(partoForm.qtd_femeas_vivas||0)+Number(partoForm.qtd_machos_vivos||0)===0)
                                                ? '✓ Fechado'
                                                : `⚠ Difere ${Math.abs(Number(partoForm.total_vivos||0) - (Number(partoForm.qtd_femeas_vivas||0)+Number(partoForm.qtd_machos_vivos||0)))}`">
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Peso: TOTAL <-> MÉDIO com chavinha toggle -->
                            <div class="bg-white dark:bg-gray-800/60 rounded-2xl border border-gray-200 dark:border-gray-700/80 p-3.5 sm:p-4 space-y-3">
                                <div class="flex items-center justify-between gap-3 select-none">
                                    <span class="text-xs sm:text-sm font-extrabold text-gray-800 dark:text-gray-100 tracking-tight">Peso da leitegada</span>
                                    <!-- Chavinha custom estilo iPhone -->
                                    <div class="flex items-center gap-3 cursor-pointer" role="button" tabindex="0"
                                         @click="partoForm.peso_mode = partoForm.peso_mode === 'total' ? 'medio' : 'total'"
                                         @keydown.enter.prevent="partoForm.peso_mode = partoForm.peso_mode === 'total' ? 'medio' : 'total'">
                                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-widest transition-all duration-200"
                                              :class="partoForm.peso_mode === 'total' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500'">Total</span>
                                        <div class="relative w-[50px] h-[28px] flex items-center rounded-full transition-all duration-300 shadow-sm"
                                             :class="partoForm.peso_mode === 'medio' ? 'bg-primary-500' : 'bg-gray-300 dark:bg-gray-600'">
                                            <div class="absolute left-1 w-6 h-6 rounded-full bg-white shadow-md transform transition-transform duration-300 ease-out"
                                                 :class="partoForm.peso_mode === 'medio' ? 'translate-x-[22px]' : 'translate-x-0'"></div>
                                        </div>
                                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-widest transition-all duration-200"
                                              :class="partoForm.peso_mode === 'medio' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500'">Médio</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- MODO: PESO TOTAL (esquerda) -->
                                    <div x-show="partoForm.peso_mode === 'total'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-[-6px]" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                        <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Peso Total (Kg)</label>
                                        <div class="relative">
                                            <input type="number" step="0.01" name="peso_total_leitoes_kg"
                                                   x-model="partoForm.peso_total_leitoes_kg"
                                                   @input="syncPesoModeInverso()"
                                                   min="0" placeholder="0,000"
                                                   inputmode="decimal"
                                                   class="w-full h-12 pl-3.5 pr-14 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-lg sm:text-base font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-sm font-bold text-gray-400 dark:text-gray-500">Kg</span>
                                        </div>
                                    </div>
                                    <div x-show="partoForm.peso_mode === 'total'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-[6px]" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                        <label class="block text-[11px] font-bold mb-1.5 text-gray-500 dark:text-gray-400">Peso Médio (calculado)</label>
                                        <div class="relative w-full h-12 px-3.5 pr-14 rounded-2xl bg-gray-100 dark:bg-gray-700/70 border border-dashed border-gray-300 dark:border-gray-600/70 flex items-center justify-between">
                                            <span class="text-lg sm:text-base font-black text-gray-700 dark:text-gray-200 tracking-tight"
                                                  x-text="(pesoMedioCalculadoKg > 0) ? String(pesoMedioCalculadoKg).replace('.', ',') : '—'"></span>
                                            <span class="text-sm font-bold text-gray-400 dark:text-gray-500">Kg</span>
                                        </div>
                                    </div>

                                    <!-- MODO: PESO MÉDIO (esquerda) -->
                                    <div x-show="partoForm.peso_mode === 'medio'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-[6px]" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                        <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Peso Médio (Kg)</label>
                                        <div class="relative">
                                            <input type="number" step="0.01" name="peso_medio_digitado_tmp"
                                                   x-model="partoForm.peso_medio_digitado"
                                                   @input="syncPesoModeInverso()"
                                                   min="0" placeholder="0,000"
                                                   inputmode="decimal"
                                                   class="w-full h-12 pl-3.5 pr-14 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-lg sm:text-base font-bold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-sm font-bold text-gray-400 dark:text-gray-500">Kg</span>
                                        </div>
                                        <input type="hidden" name="peso_total_leitoes_kg" :value="pesoTotalCalculadoKg">
                                    </div>
                                    <div x-show="partoForm.peso_mode === 'medio'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-[-6px]" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                        <label class="block text-[11px] font-bold mb-1.5 text-gray-500 dark:text-gray-400">Peso Total (calculado)</label>
                                        <div class="relative w-full h-12 px-3.5 pr-14 rounded-2xl bg-gray-100 dark:bg-gray-700/70 border border-dashed border-gray-300 dark:border-gray-600/70 flex items-center justify-between">
                                            <span class="text-lg sm:text-base font-black text-gray-700 dark:text-gray-200 tracking-tight"
                                                  x-text="(pesoTotalCalculadoKg > 0) ? String(pesoTotalCalculadoKg).replace('.', ',') : '—'"></span>
                                            <span class="text-sm font-bold text-gray-400 dark:text-gray-500">Kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Seção: Dados Complementares -->
                        <div class="space-y-5">
                            <h4 class="text-xs sm:text-sm font-extrabold uppercase tracking-[0.14em] text-primary-700 dark:text-primary-400 flex items-center gap-2">
                                <i class="fa-solid fa-list-check"></i>
                                Dados Complementares
                            </h4>

                            <!-- Localização + Baia + Peso Matriz -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-3.5">
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Localização (Sala / Ala)</label>
                                    <input type="text" name="localizacao_matriz_parto" x-model="partoForm.localizacao_matriz_parto"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="Ex: Sala 2 · Ala B">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Baia</label>
                                    <input type="text" name="baia" x-model="partoForm.baia"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="Ex: B-07">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Peso da Matriz (Kg)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="peso_matriz_kg" x-model="partoForm.peso_matriz_kg"
                                               min="0" placeholder="0,00"
                                               inputmode="decimal"
                                               class="w-full h-12 pl-3.5 pr-14 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm font-semibold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-sm font-bold text-gray-400 dark:text-gray-500">Kg</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Funcionário + Tipo Intervenção + Características -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-3.5">
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Funcionário</label>
                                    <input type="text" name="funcionario_nome" x-model="partoForm.funcionario_nome"
                                           list="maternidade-usuarios-parto"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="Nome do operador">
                                    <datalist id="maternidade-usuarios-parto">
                                        <template x-for="u in usuarios" :key="'up-' + u.id">
                                            <option :value="u.name"></option>
                                        </template>
                                    </datalist>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Tipo de Intervenção</label>
                                    <select name="tipo_intervencao" x-model="partoForm.tipo_intervencao"
                                            class="w-full h-12 px-3 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="nenhum">0 · Nenhum</option>
                                        <option value="induzido">1 · Induzido</option>
                                        <option value="distocico">2 · Distócico</option>
                                        <option value="outro">3 · Outro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Características</label>
                                    <input type="text" name="caracteristicas_parto" x-model="partoForm.caracteristicas_parto"
                                           class="w-full h-12 px-3.5 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="Ex: Normal · Auxiliado">
                                </div>
                            </div>

                            <!-- Escore + Observação (mobile: 1 coluna; desktop 1 + 2 span) -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-3.5">
                                <div class="sm:col-span-1">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Escore Corporal</label>
                                    <select name="escore_corporal" x-model="partoForm.escore_corporal"
                                            class="w-full h-12 px-3 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Selecione</option>
                                        <option value="1">1 · Muito magra</option>
                                        <option value="2">2 · Magra</option>
                                        <option value="2.5">2.5</option>
                                        <option value="3">3 · Ideal</option>
                                        <option value="3.5">3.5</option>
                                        <option value="4">4 · Gorda</option>
                                        <option value="5">5 · Muito gorda</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold mb-1.5 text-gray-700 dark:text-gray-200">Observações</label>
                                    <textarea name="observacao" x-model="partoForm.observacao" rows="2"
                                              class="w-full px-3.5 py-3 rounded-2xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-base sm:text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                              placeholder="Alguma observação extra sobre o parto..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Ações (sticky bottom mobile) -->
                        <div class="sm:hidden sticky bottom-0 -mx-4 sm:mx-0 px-4 pb-4 pt-5 bg-gradient-to-t from-white dark:from-gray-900 via-white/95 dark:via-gray-900/95 to-transparent -mb-4 -mt-1">
                            <button type="submit"
                                    class="w-full h-14 rounded-2xl text-base font-extrabold text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 shadow-lg shadow-primary-600/25 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-check-double"></i>
                                Salvar Parto
                            </button>
                        </div>
                        <div class="hidden sm:flex justify-end gap-3 pt-1">
                            <button type="button" @click="showPartoModal = false"
                                    class="px-5 h-12 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/70 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-8 h-12 rounded-xl text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 shadow-sm hover:shadow transition-all flex items-center gap-2">
                                <i class="fa-solid fa-check"></i>Salvar Parto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- DatePicker: Data Início Parto (global) -->
        <div x-show="activePicker === 'parto'"
             x-cloak
             :style="`top:${partoPickerTop}px; left:${partoPickerLeft}px;`"
             :class="partoPickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
             class="fixed z-[200] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-80 -translate-x-1/2 max-h-[calc(100vh-10rem)] overflow-y-auto"
             @click.away="activePicker = null">
            <div class="flex items-center justify-between mb-3">
                <button type="button" @click.stop="prevCalendarMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span class="font-bold text-gray-900 dark:text-gray-100" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
                <button type="button" @click.stop="nextCalendarMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                <template x-for="day in ['D','S','T','Q','Q','S','S']">
                    <div class="font-bold text-gray-500 dark:text-gray-400 py-1" x-text="day"></div>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-1">
                <template x-for="day in getCalendarDays()" :key="day.date">
                    <div class="text-center">
                        <button type="button"
                                @click.stop="selectCalendarDate(day.date)"
                                :class="day.isSelected ? 'bg-primary-600 text-white shadow-md' : (day.isCurrentMonth ? 'text-gray-900 dark:text-gray-100 hover:bg-primary-50 dark:hover:bg-primary-900/30 font-semibold' : 'text-gray-400')"
                                :disabled="!day.isCurrentMonth"
                                class="w-full h-10 sm:h-9 text-sm rounded-xl transition-all active:scale-95">
                            <span x-text="day.day"></span>
                        </button>
                        <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-0.5" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- DatePicker: Data Término Parto (global) -->
        <div x-show="activePicker === 'parto_termino'"
             x-cloak
             :style="`top:${partoTerminoPickerTop}px; left:${partoTerminoPickerLeft}px;`"
             :class="partoTerminoPickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
             class="fixed z-[200] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-80 -translate-x-1/2 max-h-[calc(100vh-10rem)] overflow-y-auto"
             @click.away="activePicker = null">
            <div class="flex items-center justify-between mb-3">
                <button type="button" @click.stop="prevCalendarMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span class="font-bold text-gray-900 dark:text-gray-100" x-text="calendarMonths[calendarMonth] + ' ' + calendarYear"></span>
                <button type="button" @click.stop="nextCalendarMonth()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                <template x-for="day in ['D','S','T','Q','Q','S','S']">
                    <div class="font-bold text-gray-500 dark:text-gray-400 py-1" x-text="day"></div>
                </template>
            </div>
            <div class="grid grid-cols-7 gap-1">
                <template x-for="day in getCalendarDays()" :key="day.date + '-t2'">
                    <div class="text-center">
                        <button type="button"
                                @click.stop="selectCalendarDate(day.date)"
                                :class="day.isSelected ? 'bg-primary-600 text-white shadow-md' : (day.isCurrentMonth ? 'text-gray-900 dark:text-gray-100 hover:bg-primary-50 dark:hover:bg-primary-900/30 font-semibold' : 'text-gray-400')"
                                :disabled="!day.isCurrentMonth"
                                class="w-full h-10 sm:h-9 text-sm rounded-xl transition-all active:scale-95">
                            <span x-text="day.day"></span>
                        </button>
                        <div class="text-[8px] text-gray-500 dark:text-gray-400 mt-0.5" x-show="day.isCurrentMonth && day.pigDay" x-text="day.pigDay"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Desmame -->
    <div x-show="showDesmameModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showDesmameModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-3xl w-full overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Desmame</h3>
                    <button @click="showDesmameModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form accept-charset="UTF-8" action="{{ route('maternidade.desmames.store') }}" method="POST" class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                    @csrf
                    <input type="hidden" name="parto_id" x-model="desmameForm.parto_id">

                    <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                        <div class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-3">Dados da matriz</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ID fêmea *</label>
                                <input type="text"
                                       x-model="desmameFemeaSearch"
                                       list="femeas-desmame-list"
                                       @keydown.enter.prevent="selecionarMatrizDesmamePorIdPrimaria(false)"
                                       @change="selecionarMatrizDesmamePorIdPrimaria(true)"
                                       class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Digite o ID primária e pressione Enter"
                                       autocomplete="off">
                                <datalist id="femeas-desmame-list">
                                    <template x-for="f in femeasLactantes.slice(0, 100)" :key="`md-${f.id}`">
                                        <option :value="f.id_primaria" x-text="f.id_secundaria ? `${f.id_primaria} / ${f.id_secundaria}` : f.id_primaria"></option>
                                    </template>
                                </datalist>
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-300" x-show="getDesmameSelectedLactante()">
                                    <span class="font-semibold" x-text="getDesmameSelectedLactante()?.identificacao"></span>
                                    <span class="ml-2" x-show="getDesmameSelectedLactante()?.parto_lote">Lote: <span class="font-semibold" x-text="getDesmameSelectedLactante()?.parto_lote"></span></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data de desmame *</label>
                                <div class="mt-1 relative">
                                    <input type="text"
                                           name="data_input"
                                           required
                                           x-model="desmameForm.data"
                                           x-ref="desmameDateInput"
                                           @click="openDesmameDatePicker()"
                                           class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 pr-10"
                                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                           inputmode="numeric"
                                           autocomplete="off"
                                           readonly>
                                    <button type="button" @click="openDesmameDatePicker()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-calendar"></i>
                                    </button>

                                    <div x-show="activePicker === 'desmame'"
                                         x-cloak
                                         :style="`top:${desmamePickerTop}px; left:${desmamePickerLeft}px;`"
                                         :class="desmamePickerDirection === 'up' ? '-translate-y-full -mt-2' : 'mt-2'"
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
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2">
                                <div class="text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Mortes</div>
                                <div class="text-gray-900 dark:text-gray-100 font-bold mt-1" x-text="getDesmameSelectedLactante()?.mortes ?? 0"></div>
                            </div>
                            <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2">
                                <div class="text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Movimentações</div>
                                <div class="text-gray-900 dark:text-gray-100 font-bold mt-1">
                                    <span class="text-emerald-600">Recebidos:</span> <span x-text="getDesmameSelectedLactante()?.recebidos ?? 0"></span>
                                    <span class="ml-3 text-red-600">Doados:</span> <span x-text="getDesmameSelectedLactante()?.doados ?? 0"></span>
                                </div>
                            </div>
                            <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2">
                                <div class="text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Leitões disponíveis</div>
                                <div class="text-gray-900 dark:text-gray-100 font-bold mt-1" x-text="getDesmameSelectedLactante()?.disponiveis ?? 0"></div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Dados do desmame</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desmamados *</label>
                                <input type="number" name="quantidade" required min="1" :max="getDesmameSelectedLactante()?.disponiveis ?? null" x-model="desmameForm.quantidade" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peso médio (kg)</label>
                                <input type="number" step="0.01" name="peso_medio" x-model="desmameForm.peso_medio" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Peso total (kg): <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="desmamePesoTotal().toFixed(2)"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lote destino</label>
                                <select name="lote_destino_id" required x-model="desmameForm.lote_destino_id" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                                    <option value="">Selecione</option>
                                    <template x-for="l in lotesCreche" :key="`lc-${l.id}`">
                                        <option :value="l.id" x-text="l.nome"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Localização de destino</label>
                                <input type="text" name="localizacao_destino" x-model="desmameForm.localizacao_destino" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Usuário</label>
                                <select name="funcionario" x-model="desmameForm.funcionario" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                                    <option value="">Selecione</option>
                                    <template x-for="u in usuarios" :key="`u-${u.id}`">
                                        <option :value="u.name" x-text="u.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Dados complementares</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Para onde vai a matriz</label>
                                <input type="text" name="destino_matriz" x-model="desmameForm.destino_matriz" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Baia</label>
                                <input type="text" name="baia_matriz" x-model="desmameForm.baia_matriz" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peso da matriz (kg)</label>
                                <input type="number" step="0.01" name="peso_matriz" x-model="desmameForm.peso_matriz" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Características do desmame</label>
                                <input type="text" name="caracteristicas_desmame" x-model="desmameForm.caracteristicas_desmame" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Escore corporal</label>
                                <select name="escore_corporal" x-model="desmameForm.escore_corporal" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100">
                                    <option value="">Selecione</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                                <textarea name="observacao" rows="2" x-model="desmameForm.observacao" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100"></textarea>
                            </div>
                        </div>
                    </div>

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
