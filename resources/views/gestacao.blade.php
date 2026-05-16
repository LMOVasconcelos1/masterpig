@extends('layouts.dashboard')

@section('title', 'Gestação')
@section('page_title', '')

@section('content')
    <div x-data="{
        tab: 'lancamentos',
        lancTab: 'cobertura',
        coberturaTab: 'principal',
        openCobertura: false,
        openPerda: false,
        saving: false,
        error: '',

        matrizes: [],
        matrizSearch: '',
        machos: [],
        semens: [],
        semensLoading: false,
        usuarios: [],
        coberturas: [],
        perdas: [],
        saltasCio: [],
        editingCoberturaId: null,

        criteriosLoaded: false,
        calendarType: 'gregoriano',
        activePicker: null,
        calendarMonth: new Date().getMonth(),
        calendarYear: new Date().getFullYear(),
        calendarMonths: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        criterios: {
            enabled: false,
            coberturaIdadeMin: '210',
            coberturaIdadeMax: '240',
            coberturaPesoMin: '0',
            coberturaPesoMax: '0',
            coberturaPresencaCio: 'sim',
            leitoaMinDias: '150',
            leitoaMaxDias: '210',
        },
        criteriosConfirmOpen: false,
        criteriosConfirmWarnings: [],
        criteriosConfirmPayload: null,
        criteriosAfterSaveOpen: false,
        criteriosAfterSaveWarnings: [],

        // Formulário de Cobertura
        openFormularioCobertura: false,
        tipoFormulario: 'em_branco',
        opcaoMatriz: 'todas',
        opcaoLeitoa: 'todas',
        ordenarPor: 'matriz',
        quantidadeMontas: '10',
        diasVaziasInicio: '',
        diasVaziasFim: '',
        idadeLeitoaInicio: '',
        idadeLeitoaFim: '',

        gerarFormulario() {
            console.log('Gerando formulário com as configurações:', {
                tipoFormulario: this.tipoFormulario,
                opcaoMatriz: this.opcaoMatriz,
                opcaoLeitoa: this.opcaoLeitoa,
                ordenarPor: this.ordenarPor,
                quantidadeMontas: this.quantidadeMontas,
                diasVaziasInicio: this.diasVaziasInicio,
                diasVaziasFim: this.diasVaziasFim,
                idadeLeitoaInicio: this.idadeLeitoaInicio,
                idadeLeitoaFim: this.idadeLeitoaFim
            });
            
            // Gerar PDF com as configurações
            const params = new URLSearchParams({
                tipo: this.tipoFormulario,
                matriz: this.opcaoMatriz,
                leitoa: this.opcaoLeitoa,
                ordenar: this.ordenarPor,
                quantidade: this.quantidadeMontas,
                dias_vazias_inicio: this.diasVaziasInicio,
                dias_vazias_fim: this.diasVaziasFim,
                idade_inicio: this.idadeLeitoaInicio,
                idade_fim: this.idadeLeitoaFim
            });
            
            // Abrir PDF em nova janela
            window.open(`/gestacao/formulario-cobertura/pdf?${params.toString()}`, '_blank');
            
            this.openFormularioCobertura = false;
        },

        cobertura: {
            femeaId: '',
            usuarioId: '',
            data: '',
            hora: '',
            montas: [],
            presencaCio: '',
            localizacao: '',
            baia: '',
            pesoMatriz: '',
            caracteristicas: '',
            observacoes: '',
        },

        perda: {
            femeaId: '',
            usuarioId: '',
            tipo: 'aborto',
            data: '',
            hora: '',
            localizacao: '',
            baia: '',
            observacoes: '',
        },

        saltaCio: {
            femeaId: '',
            data: '',
        },

        openSaltaCio: false,

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
        normalizeBrDate(br) {
            const iso = this.brToIso(br);
            if (!iso) return String(br || '');
            return this.isoToBr(iso);
        },
        brToDate(br) {
            const iso = this.brToIso(br);
            if (!iso) return null;
            return new Date(iso + 'T00:00:00');
        },
        coberturaDataToIso() {
            const raw = String(this.cobertura.data || '').trim();
            if (!raw) return null;
            if (this.calendarType === '1000_dias') {
                if (typeof pigDayToDate !== 'function') return null;
                const cleaned = raw.replace(/\D/g, '');
                if (!cleaned) return null;
                return pigDayToDate(cleaned);
            }
            return this.brToIso(raw);
        },
        coberturaDataToDate() {
            const iso = this.coberturaDataToIso();
            if (!iso) return null;
            return new Date(iso + 'T00:00:00');
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
        getPickerSelectedIso() {
            const key = String(this.activePicker || '');
            if (key.startsWith('monta-')) {
                const idx = Number(key.replace('monta-', ''));
                const row = Array.isArray(this.cobertura.montas) ? this.cobertura.montas[idx] : null;
                return row ? this.montaDataToIso(row.data) : null;
            }
            return this.coberturaDataToIso();
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
        openMontaDatePicker(idx) {
            const row = Array.isArray(this.cobertura.montas) ? this.cobertura.montas[idx] : null;
            const iso = row ? this.montaDataToIso(row.data) : null;
            const base = iso && /^\d{4}-\d{2}-\d{2}$/.test(iso) ? new Date(iso + 'T12:00:00') : new Date();
            this.calendarMonth = base.getMonth();
            this.calendarYear = base.getFullYear();
            this.activePicker = `monta-${idx}`;
        },
        selectCalendarDate(dateStr) {
            const m = String(dateStr || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            const formatted = m ? `${m[3]}/${m[2]}/${m[1]}` : '';
            const pigDay = typeof toPigDay === 'function' ? toPigDay(dateStr + 'T00:00:00') : '';
            const key = String(this.activePicker || '');
            if (key.startsWith('monta-')) {
                const idx = Number(key.replace('monta-', ''));
                const row = Array.isArray(this.cobertura.montas) ? this.cobertura.montas[idx] : null;
                if (row) {
                    row.data = this.calendarType === '1000_dias' ? String(pigDay) : formatted;
                    if (idx === 0) this.cobertura.data = row.data;
                }
            } else {
                this.cobertura.data = this.calendarType === '1000_dias' ? String(pigDay) : formatted;
            }
            this.activePicker = null;
        },
        getPickerSelectedPigDay() {
            const iso = this.getPickerSelectedIso();
            if (!iso) return '';
            return typeof toPigDay === 'function' ? toPigDay(iso + 'T00:00:00') : '';
        },

        init() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const yyyy = now.getFullYear();
            const mm = pad(now.getMonth() + 1);
            const dd = pad(now.getDate());
            this.cobertura.data = `${dd}/${mm}/${yyyy}`;
            this.perda.data = `${dd}/${mm}/${yyyy}`;
            this.saltaCio.data = `${dd}/${mm}/${yyyy}`;
            this.cobertura.hora = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
            this.cobertura.montas = [
                { tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: '', ref: '' },
                { tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: '', ref: '' },
                { tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: '', ref: '' },
            ];

            fetch('/api/plantel/femeas?previsao_cio=1')
                .then(r => r.json())
                .then(data => {
                    const items = Array.isArray(data?.items) ? data.items : [];
                    this.matrizes = items.filter(f => ['leitoa', 'matriz_vazia'].includes(String(f.tipo || '')));
                });

            fetch('/api/plantel/machos?all=1')
                .then(r => r.json())
                .then(data => {
                    this.machos = Array.isArray(data?.items) ? data.items : [];
                });

            fetch('/api/usuarios', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.usuarios = Array.isArray(data.items) ? data.items : [];
                });

            fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    const items = data.items || {};
                    this.criterios.enabled = Boolean(Number(items.criterios_enabled ?? 0));
                    this.criterios.coberturaIdadeMin = (items.criterio_cobertura_idade_min_dias === null || items.criterio_cobertura_idade_min_dias === undefined || String(items.criterio_cobertura_idade_min_dias).trim() === '') ? '210' : String(items.criterio_cobertura_idade_min_dias);
                    this.criterios.coberturaIdadeMax = (items.criterio_cobertura_idade_max_dias === null || items.criterio_cobertura_idade_max_dias === undefined || String(items.criterio_cobertura_idade_max_dias).trim() === '') ? '240' : String(items.criterio_cobertura_idade_max_dias);
                    this.criterios.coberturaPesoMin = (items.criterio_cobertura_peso_min_kg === null || items.criterio_cobertura_peso_min_kg === undefined || String(items.criterio_cobertura_peso_min_kg).trim() === '') ? '0' : String(items.criterio_cobertura_peso_min_kg);
                    this.criterios.coberturaPesoMax = (items.criterio_cobertura_peso_max_kg === null || items.criterio_cobertura_peso_max_kg === undefined || String(items.criterio_cobertura_peso_max_kg).trim() === '') ? '0' : String(items.criterio_cobertura_peso_max_kg);
                    this.criterios.coberturaPresencaCio = (items.criterio_cobertura_presenca_cio === null || items.criterio_cobertura_presenca_cio === undefined || String(items.criterio_cobertura_presenca_cio).trim() === '') ? 'sim' : String(items.criterio_cobertura_presenca_cio);
                    this.criterios.leitoaMinDias = (items.criterio_leitoa_idade_min_dias === null || items.criterio_leitoa_idade_min_dias === undefined || String(items.criterio_leitoa_idade_min_dias).trim() === '') ? '150' : String(items.criterio_leitoa_idade_min_dias);
                    this.criterios.leitoaMaxDias = (items.criterio_leitoa_idade_max_dias === null || items.criterio_leitoa_idade_max_dias === undefined || String(items.criterio_leitoa_idade_max_dias).trim() === '') ? '210' : String(items.criterio_leitoa_idade_max_dias);
                    this.calendarType = (items.criterio_calendario_tipo === null || items.criterio_calendario_tipo === undefined || String(items.criterio_calendario_tipo).trim() === '') ? 'gregoriano' : String(items.criterio_calendario_tipo);
                    if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
                        const now2 = new Date();
                        const pad2 = (n) => String(n).padStart(2, '0');
                        const isoNow = `${now2.getFullYear()}-${pad2(now2.getMonth() + 1)}-${pad2(now2.getDate())}`;
                        const pigDay = toPigDay(isoNow + 'T00:00:00');
                        if (pigDay !== null && pigDay !== undefined) this.cobertura.data = String(pigDay);
                    }
                    this.criteriosLoaded = true;
                })
                .catch(() => { this.criteriosLoaded = true; });

            this.loadCoberturas();
            this.loadPerdas();
            this.loadSaltaCio();
        },

        get matrizesFiltradas() {
            const q = String(this.matrizSearch || '').trim().toLowerCase();
            if (!q) return this.matrizes;
            return this.matrizes.filter((f) => String(f?.id_primaria || '').toLowerCase().includes(q));
        },

        selecionarMatrizPorIdPrimaria(strict = false) {
            const q = String(this.matrizSearch || '').trim().toLowerCase();
            if (!q) {
                this.cobertura.femeaId = '';
                return;
            }

            const exact = this.matrizes.find((f) => String(f?.id_primaria || '').toLowerCase() === q);
            if (strict) {
                this.cobertura.femeaId = exact ? String(exact.id) : '';
                return;
            }

            const filtered = this.matrizesFiltradas;
            const match = exact || (filtered.length === 1 ? filtered[0] : filtered[0]);

            if (!match) {
                this.cobertura.femeaId = '';
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Nenhuma matriz encontrada.', type: 'error' } }));
                return;
            }

            this.cobertura.femeaId = String(match.id);
            this.matrizSearch = String(match.id_primaria || this.matrizSearch);
        },

        normalizeMontaRef(raw) {
            const v = String(raw || '').trim();
            if (!v) return '';
            if (/^(m-|s-)/i.test(v)) return v.toUpperCase();
            if (/^(macho:|semen:)/i.test(v)) return v.toLowerCase().startsWith('macho:') ? `M-${v.split(':')[1]}` : `S-${v.split(':')[1]}`;
            return v;
        },
        applyMontaRef(idx) {
            const row = this.cobertura.montas[idx];
            if (!row) return;
            const raw = this.normalizeMontaRef(row.ref || '');
            row.ref = raw;
            row.tipo = '';
            row.macho_id = '';
            row.semen = '';

            const m = raw.match(/^M-(.+)$/i);
            if (m) {
                const idPrimaria = String(m[1] || '').trim();
                const macho = this.machos.find((x) => String(x?.id_primaria || '').trim() === idPrimaria);
                if (!macho) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Macho não encontrado.', type: 'error' } }));
                    return;
                }
                row.tipo = 'macho';
                row.macho_id = String(macho.id);
            }

            const s = raw.match(/^S-(.+)$/i);
            if (s) {
                const idPrimaria = String(s[1] || '').trim();
                const semen = this.semens.find((x) => String(x?.id_primaria || '').trim() === idPrimaria);
                if (!semen) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Sêmen não encontrado.', type: 'error' } }));
                    return;
                }
                row.tipo = 'semen';
                row.semen = String(semen.id_primaria);
            }

            if (!row.tipo) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe M-<ID do macho> ou S-<ID do sêmen>.', type: 'error' } }));
                return;
            }

            if (idx === 0) {
                this.cobertura.usuarioId = String(row.usuario_id || '');
                this.cobertura.data = String(row.data || '');
                this.cobertura.hora = String(row.hora || '');
            }
        },
        addMonta() {
            this.cobertura.montas.push({ tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: this.cobertura.usuarioId, ref: '' });
        },
        removeMonta() {
            if (this.cobertura.montas.length <= 1) return;
            this.cobertura.montas.pop();
        },
        montaDataToIso(raw) {
            const v = String(raw || '').trim();
            if (!v) return null;
            if (this.calendarType === '1000_dias') {
                if (typeof pigDayToDate !== 'function') return null;
                const cleaned = v.replace(/\D/g, '');
                if (!cleaned) return null;
                return pigDayToDate(cleaned);
            }
            return this.brToIso(v);
        },

        loadSemens() {
            if (this.semensLoading) return;
            if (this.semens.length > 0) return;

            this.semensLoading = true;
            fetch('/api/semen?limit=5000&page=1', { headers: { 'Accept': 'application/json' } })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Não foi possível carregar os semens cadastrados.');
                    return data;
                })
                .then((data) => {
                    this.semens = Array.isArray(data?.items) ? data.items : [];
                })
                .catch((e) => {
                    this.semens = [];
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Não foi possível carregar os semens cadastrados.', type: 'error' } }));
                })
                .finally(() => {
                    this.semensLoading = false;
                });
        },

        loadCoberturas() {
            fetch('/api/gestacao/coberturas?limit=50', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.coberturas = Array.isArray(data.items) ? data.items : [];
                    if (data.message) this.error = data.message;
                })
                .catch(() => {});
        },

        loadPerdas() {
            fetch('/api/gestacao/perdas?limit=50', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.perdas = Array.isArray(data.items) ? data.items : [];
                    if (data.message) this.error = data.message;
                })
                .catch(() => {});
        },

        loadSaltaCio() {
            fetch('/api/gestacao/salta-cio?limit=50', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.saltasCio = Array.isArray(data.items) ? data.items : [];
                    if (data.message) this.error = data.message;
                })
                .catch(() => {});
        },

        deleteCobertura(id) {
            const rowId = Number(id);
            if (!Number.isFinite(rowId) || rowId <= 0) return;
            if (!confirm('Excluir esta cobertura?')) return;

            fetch(`/api/gestacao/coberturas/${rowId}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                },
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao excluir cobertura');
                    return data;
                })
                .then((data) => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Cobertura excluída com sucesso!', type: 'success' } }));
                    this.loadCoberturas();
                })
                .catch((e) => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao excluir cobertura', type: 'error' } }));
                });
        },

        deleteSaltaCio(id) {
            const rowId = Number(id);
            if (!Number.isFinite(rowId) || rowId <= 0) return;
            if (!confirm('Excluir este registro de salta cio?')) return;

            fetch(`/api/gestacao/salta-cio/${rowId}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                },
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao excluir salta cio');
                    return data;
                })
                .then((data) => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Registro excluído com sucesso!', type: 'success' } }));
                    this.loadSaltaCio();
                })
                .catch((e) => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao excluir salta cio', type: 'error' } }));
                });
        },

        openCoberturaModal() {
            this.error = '';
            this.coberturaTab = 'principal';
            this.cobertura.presencaCio = 'sim';
            this.editingCoberturaId = null;
            this.matrizSearch = '';
            this.cobertura.usuarioId = '';
            this.cobertura.montas = [
                { tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: '', ref: '' },
                { tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: '', ref: '' },
                { tipo: '', macho_id: '', semen: '', data: this.cobertura.data, hora: this.cobertura.hora, usuario_id: '', ref: '' },
            ];
            this.openCobertura = true;
            this.loadSemens();
        },
        openCoberturaEdit(id) {
            this.error = '';
            this.coberturaTab = 'principal';
            this.cobertura.presencaCio = 'sim';
            this.editingCoberturaId = Number(id);
            this.openCobertura = true;
            this.loadSemens();

            fetch(`/api/gestacao/coberturas/${id}`, { headers: { 'Accept': 'application/json' } })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao carregar cobertura');
                    return data;
                })
                .then((data) => {
                    const item = data?.item || null;
                    if (!item) throw new Error('Cobertura inválida');

                    const normalizeHora = (h) => {
                        const v = String(h || '').trim();
                        if (/^\d{2}:\d{2}:\d{2}$/.test(v)) return v.slice(0, 5);
                        return v;
                    };

                    this.cobertura.femeaId = String(item.femea_id || '');
                    this.matrizSearch = String(item.matriz || '');
                    this.cobertura.usuarioId = String(item.usuario_id || '');
                    this.cobertura.presencaCio = String(item.presenca_cio || 'sim');
                    this.cobertura.localizacao = item.localizacao || '';
                    this.cobertura.baia = item.baia || '';
                    this.cobertura.pesoMatriz = (item.peso_matriz === null || item.peso_matriz === undefined) ? '' : String(item.peso_matriz);
                    this.cobertura.caracteristicas = item.caracteristicas || '';
                    this.cobertura.observacoes = item.observacoes || '';

                    const montas = Array.isArray(item.montas) ? item.montas : [];
                    const formatIsoForInput = (iso) => {
                        const v = String(iso || '').trim();
                        if (!v) return '';
                        if (this.calendarType === '1000_dias' && typeof toPigDay === 'function') {
                            return String(toPigDay(v + 'T00:00:00'));
                        }
                        return this.isoToBr(v);
                    };

                    this.cobertura.montas = montas.map((m) => ({
                        tipo: '',
                        macho_id: '',
                        semen: '',
                        data: formatIsoForInput(m.data),
                        hora: normalizeHora(m.hora),
                        usuario_id: m.usuario_id ? String(m.usuario_id) : '',
                        ref: String(m.ref || ''),
                    }));

                    if (this.cobertura.montas.length === 0) {
                        this.cobertura.montas = [
                            { tipo: '', macho_id: '', semen: '', data: formatIsoForInput(item.data), hora: String(item.hora || ''), usuario_id: String(item.usuario_id || ''), ref: '' },
                            { tipo: '', macho_id: '', semen: '', data: formatIsoForInput(item.data), hora: String(item.hora || ''), usuario_id: String(item.usuario_id || ''), ref: '' },
                            { tipo: '', macho_id: '', semen: '', data: formatIsoForInput(item.data), hora: String(item.hora || ''), usuario_id: String(item.usuario_id || ''), ref: '' },
                        ];
                    }

                    this.cobertura.data = formatIsoForInput(item.data);
                    this.cobertura.hora = normalizeHora(item.hora);
                })
                .catch((e) => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao carregar cobertura', type: 'error' } }));
                });
        },
        openPerdaModal() {
            this.error = '';
            this.openPerda = true;
        },
        openSaltaCioModal() {
            this.error = '';
            this.openSaltaCio = true;
            this.saltaCio.femeaId = '';
        },

        saveCobertura() {
            if (!this.cobertura.femeaId) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Selecione a matriz', type: 'error' } }));
                return;
            }

            if (!this.cobertura.presencaCio) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe a presença de cio', type: 'error' } }));
                return;
            }
            if (this.cobertura.presencaCio !== 'sim') {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'A fêmea precisa estar em cio para registrar cobertura', type: 'error' } }));
                return;
            }

            const montasRaw = Array.isArray(this.cobertura.montas) ? this.cobertura.montas : [];
            const montasFilledIdx = montasRaw
                .map((r, i) => ({ r, i }))
                .filter(({ r }) => String(r?.ref || '').trim() !== '');

            if (montasFilledIdx.length === 0) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe ao menos uma monta/inseminação.', type: 'error' } }));
                return;
            }

            montasFilledIdx.forEach(({ i }) => this.applyMontaRef(i));

            const montasPayload = montasFilledIdx.map(({ r, i }) => {
                const dataIso = this.montaDataToIso(r.data);
                const horaRaw = String(r.hora || '').trim();
                const hora = /^\d{2}:\d{2}:\d{2}$/.test(horaRaw) ? horaRaw.slice(0, 5) : horaRaw;
                return {
                    tipo: String(r.tipo || ''),
                    macho_id: r.tipo === 'macho' ? Number(r.macho_id) : null,
                    semen: r.tipo === 'semen' ? String(r.semen || '').trim() : null,
                    data: dataIso,
                    hora,
                    usuario_id: Number(r.usuario_id),
                    _idx: i,
                };
            });

            const first = montasPayload[0];
            if (!first.usuario_id || !Number.isFinite(first.usuario_id) || first.usuario_id <= 0) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe o funcionário na primeira monta.', type: 'error' } }));
                return;
            }
            if (!first.data || !first.hora) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe data e hora na primeira monta.', type: 'error' } }));
                return;
            }

            for (const m of montasPayload) {
                if (m.tipo !== 'macho' && m.tipo !== 'semen') {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Monta ${m._idx + 1}: informe Macho ou Sêmen.`, type: 'error' } }));
                    return;
                }
                if (m.tipo === 'macho' && (!m.macho_id || !Number.isFinite(m.macho_id))) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Monta ${m._idx + 1}: macho inválido.`, type: 'error' } }));
                    return;
                }
                if (m.tipo === 'semen' && (!m.semen || String(m.semen).trim() === '')) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Monta ${m._idx + 1}: sêmen inválido.`, type: 'error' } }));
                    return;
                }
                if (!m.data) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Monta ${m._idx + 1}: ${this.calendarType === '1000_dias' ? 'informe a data (Dia PIG)' : 'informe a data (dd/mm/aaaa)'}.`, type: 'error' } }));
                    return;
                }
                if (!/^\d{2}:\d{2}$/.test(m.hora)) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Monta ${m._idx + 1}: informe a hora.`, type: 'error' } }));
                    return;
                }
                if (!m.usuario_id || !Number.isFinite(m.usuario_id) || m.usuario_id <= 0) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Monta ${m._idx + 1}: informe o funcionário.`, type: 'error' } }));
                    return;
                }
            }

            this.cobertura.usuarioId = String(first.usuario_id);
            this.cobertura.data = String(montasRaw[0]?.data || '');
            this.cobertura.hora = String(montasRaw[0]?.hora || '');

            const payload = {
                femea_id: Number(this.cobertura.femeaId),
                usuario_id: first.usuario_id,
                data: first.data,
                hora: first.hora,
                presenca_cio: this.cobertura.presencaCio,
                localizacao: this.cobertura.localizacao || null,
                baia: this.cobertura.baia || null,
                peso_matriz: this.cobertura.pesoMatriz === '' ? null : Number(this.cobertura.pesoMatriz),
                caracteristicas: this.cobertura.caracteristicas ? this.cobertura.caracteristicas.trim() : null,
                observacoes: this.cobertura.observacoes ? this.cobertura.observacoes.trim() : null,
                montas: montasPayload.map(({ _idx, ...rest }) => rest),
            };

            const criteriosWarnings = this.checkCriteriosCobertura();
            if (criteriosWarnings.length > 0) {
                this.criteriosConfirmWarnings = criteriosWarnings;
                this.criteriosConfirmPayload = payload;
                this.criteriosConfirmOpen = true;
                return;
            }

            this.doSaveCobertura(payload);
        },

        doSaveCobertura(payload) {
            this.saving = true;

            const isEdit = this.editingCoberturaId !== null && this.editingCoberturaId !== undefined;
            const url = isEdit ? `/api/gestacao/coberturas/${this.editingCoberturaId}` : '/api/gestacao/coberturas';
            const method = isEdit ? 'PATCH' : 'POST';

            fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        const err = new Error(data?.message || 'Erro ao salvar cobertura');
                        err.sql = data?.sql || null;
                        throw err;
                    }
                    return data;
                })
                .then((data) => {
                    const warnings = Array.isArray(data?.warnings) ? data.warnings : [];
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: isEdit ? 'Cobertura alterada com sucesso!' : 'Cobertura registrada com sucesso!', type: 'success' } }));
                    this.openCobertura = false;
                    this.editingCoberturaId = null;
                    this.loadCoberturas();
                    if (warnings.length > 0) {
                        this.criteriosAfterSaveWarnings = warnings;
                        this.criteriosAfterSaveOpen = true;
                    }
                })
                .catch(e => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar cobertura', type: 'error' } }));
                    if (e && e.sql) {
                        window.prompt('SQL necessário para o banco:', String(e.sql));
                    }
                })
                .finally(() => { this.saving = false; });
        },
        confirmCriteriosProceed() {
            const payload = this.criteriosConfirmPayload;
            this.criteriosConfirmOpen = false;
            this.criteriosConfirmWarnings = [];
            this.criteriosConfirmPayload = null;
            if (!payload) return;
            this.doSaveCobertura(payload);
        },
        confirmCriteriosCancel() {
            this.criteriosConfirmOpen = false;
            this.criteriosConfirmWarnings = [];
            this.criteriosConfirmPayload = null;
        },
        closeCriteriosAfterSave() {
            this.criteriosAfterSaveOpen = false;
            this.criteriosAfterSaveWarnings = [];
        },

        checkCriteriosCobertura() {
            if (!this.criteriosLoaded || !this.criterios.enabled) return [];

            const warnings = [];
            const femea = this.matrizes.find(f => String(f.id) === String(this.cobertura.femeaId));
            const dataCobertura = this.coberturaDataToDate();
            const dataNascimento = femea && femea.data_nascimento ? new Date(String(femea.data_nascimento) + 'T00:00:00') : null;

            const parseNumberOrNull = (value) => {
                if (value === null || value === undefined) return null;
                const raw = String(value).trim();
                if (!raw) return null;
                const n = Number(raw.replace(',', '.'));
                if (!Number.isFinite(n) || n <= 0) return null;
                return n;
            };

            const idadeMin = parseNumberOrNull(this.criterios.coberturaIdadeMin);
            const idadeMax = parseNumberOrNull(this.criterios.coberturaIdadeMax);
            if ((idadeMin !== null || idadeMax !== null) && dataCobertura) {
                if (!dataNascimento || isNaN(dataNascimento.getTime())) {
                    warnings.push('Idade: matriz sem data de nascimento cadastrada');
                } else {
                    const ms = dataCobertura.getTime() - dataNascimento.getTime();
                    const idadeDias = Math.floor(ms / 86400000);
                    if (idadeMin !== null && idadeDias < idadeMin) warnings.push(`Idade: ${idadeDias} dias (mínimo ${idadeMin})`);
                    if (idadeMax !== null && idadeDias > idadeMax) warnings.push(`Idade: ${idadeDias} dias (máximo ${idadeMax})`);
                }
            }

            const pesoMin = parseNumberOrNull(this.criterios.coberturaPesoMin);
            const pesoMax = parseNumberOrNull(this.criterios.coberturaPesoMax);
            if (pesoMin !== null || pesoMax !== null) {
                if (this.cobertura.pesoMatriz === '') {
                    warnings.push('Peso: informe o peso da matriz');
                } else {
                    const peso = Number(this.cobertura.pesoMatriz);
                    if (pesoMin !== null && peso < pesoMin) warnings.push(`Peso: ${peso} kg (mínimo ${pesoMin})`);
                    if (pesoMax !== null && peso > pesoMax) warnings.push(`Peso: ${peso} kg (máximo ${pesoMax})`);
                }
            }

            if (this.criterios.coberturaPresencaCio && this.cobertura.presencaCio && this.criterios.coberturaPresencaCio !== this.cobertura.presencaCio) {
                warnings.push(`Presença de cio: esperado ${this.criterios.coberturaPresencaCio === 'sim' ? 'Sim' : 'Não'}`);
            }

            return warnings;
        },

        savePerda() {
            if (!this.perda.femeaId) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Selecione a matriz', type: 'error' } }));
                return;
            }
            if (!this.perda.usuarioId) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Selecione o usuário', type: 'error' } }));
                return;
            }
            if (!this.perda.data) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe a data', type: 'error' } }));
                return;
            }

            this.saving = true;

            const dataIso = this.brToIso(this.perda.data);
            if (!dataIso) {
                this.saving = false;
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe a data (dd/mm/aaaa)', type: 'error' } }));
                return;
            }

            const payload = {
                femea_id: Number(this.perda.femeaId),
                usuario_id: Number(this.perda.usuarioId),
                tipo: this.perda.tipo,
                data: dataIso,
                hora: this.perda.hora || null,
                localizacao: this.perda.localizacao || null,
                baia: this.perda.baia || null,
                observacoes: this.perda.observacoes || null,
            };

            fetch('/api/gestacao/perdas', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao salvar perda');
                    return data;
                })
                .then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Perda reprodutiva registrada com sucesso!', type: 'success' } }));
                    this.openPerda = false;
                    this.loadPerdas();
                })
                .catch(e => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar perda', type: 'error' } }));
                })
                .finally(() => { this.saving = false; });
        },

        saveSaltaCio() {
            if (!this.saltaCio.femeaId) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Selecione a fêmea', type: 'error' } }));
                return;
            }
            if (!this.saltaCio.data) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe a data', type: 'error' } }));
                return;
            }

            this.saving = true;

            const dataIso = this.brToIso(this.saltaCio.data);
            if (!dataIso) {
                this.saving = false;
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe a data (dd/mm/aaaa)', type: 'error' } }));
                return;
            }

            const payload = {
                femea_id: Number(this.saltaCio.femeaId),
                data: dataIso,
            };

            fetch('/api/gestacao/salta-cio', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data?.message || 'Erro ao salvar salta cio');
                    return data;
                })
                .then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Salta cio registrado com sucesso!', type: 'success' } }));
                    this.openSaltaCio = false;
                    this.loadSaltaCio();
                })
                .catch(e => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar salta cio', type: 'error' } }));
                })
                .finally(() => { this.saving = false; });
        },


        tipoPerdaLabel(t) {
            if (t === 'aborto') return 'Aborto';
            if (t === 'repeticao_cio') return 'Repetição de cio';
            if (t === 'falsa_prenhez') return 'Falsa prenhez';
            return t;
        },
    }"
    class="space-y-6"
>
    <!-- Header & Topbar -->
    <div>
        <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Gestação</h2>
                <p class="text-sm text-white">Coberturas, perdas reprodutivas e metas</p>
            </div>
            <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
                <button type="button" @click="tab = 'lancamentos'" 
                    :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Lançamentos
                </button>
                <button type="button" @click="tab = 'analise'" 
                    :class="tab === 'analise' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Análise
                </button>
            </nav>
        </div>
    </div>

    <div x-show="tab === 'lancamentos'" x-cloak class="space-y-8" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="text-center">
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lançamentos</h6>
                    <div class="text-sm text-gray-500 mt-1">Gerencie coberturas e perdas reprodutivas</div>
                </div>
            </div>
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                <div class="flex justify-center items-center gap-2 bg-gray-100 p-1.5 rounded-xl overflow-x-auto max-w-full">
                    <button type="button" @click="lancTab = 'cobertura'" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="lancTab === 'cobertura' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-heart text-primary-600 transition-colors duration-300" :class="lancTab === 'cobertura' ? 'text-primary-600' : 'text-gray-600'"></i> Coberturas
                    </button>
                    <button type="button" @click="lancTab = 'perda'" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="lancTab === 'perda' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-skull-crossbones text-primary-600 transition-colors duration-300" :class="lancTab === 'perda' ? 'text-primary-600' : 'text-gray-600'"></i> Perdas
                    </button>
                    <button type="button" @click="lancTab = 'salta_cio'" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="lancTab === 'salta_cio' ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-forward text-primary-600 transition-colors duration-300" :class="lancTab === 'salta_cio' ? 'text-primary-600' : 'text-gray-600'"></i> Salta Cio
                    </button>
                </div>
            </div>

            <div class="p-6" x-show="lancTab === 'cobertura'" x-cloak>
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div class="text-center flex-1">
                            <div class="text-sm font-bold text-gray-900">Últimas coberturas</div>
                            <button type="button" @click="loadCoberturas()" class="text-sm text-primary-600 hover:text-primary-700">Atualizar</button>
                        </div>
                        <button type="button" @click="openCoberturaModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm w-11 h-11 bg-primary-600 text-white hover:bg-primary-700 transition-all duration-300 transform hover:scale-105 hover:shadow-lg" title="Registrar cobertura">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="p-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="py-2 pr-4">Ações</th>
                                    <th class="py-2 pr-4">Matriz</th>
                                    <th class="py-2 pr-4">Macho/Sêmen</th>
                                    <th class="py-2 pr-4">Dia PIG</th>
                                    <th class="py-2 pr-4">Hora</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="c in coberturas" :key="c.id">
                                    <tr class="text-sm text-gray-700 dark:text-gray-300">
                                        <td class="py-2 pr-4">
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700" title="Alterar" @click.prevent="openCoberturaEdit(c.id)">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" title="Excluir" @click.prevent="deleteCobertura(c.id)">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-2 pr-4" x-text="c.matriz"></td>
                                        <td class="py-2 pr-4" x-text="c.montas_summary || c.macho || c.semen || '-'"></td>
                                        <td class="py-2 pr-4" x-text="c.data" :title="c.data_br || ''"></td>
                                        <td class="py-2 pr-4" x-text="c.hora || '-'"></td>
                                    </tr>
                                </template>
                                <tr x-show="coberturas.length === 0">
                                    <td colspan="5" class="py-4 text-sm text-gray-500">Nenhuma cobertura registrada.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-6" x-show="lancTab === 'perda'" x-cloak>
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div class="text-center flex-1">
                            <div class="text-sm font-bold text-gray-900">Últimas perdas</div>
                            <button type="button" @click="loadPerdas()" class="text-sm text-primary-600 hover:text-primary-700">Atualizar</button>
                        </div>
                        <button type="button" @click="openPerdaModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm w-11 h-11 bg-primary-600 text-white hover:bg-primary-700 transition-all duration-300 transform hover:scale-105 hover:shadow-lg" title="Registrar perda reprodutiva">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="p-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="py-2 pr-4">Matriz</th>
                                    <th class="py-2 pr-4">Tipo</th>
                                    <th class="py-2 pr-4">Data</th>
                                    <th class="py-2 pr-4">Hora</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="p in perdas" :key="p.id">
                                    <tr class="text-sm text-gray-700">
                                        <td class="py-2 pr-4" x-text="p.matriz"></td>
                                        <td class="py-2 pr-4" x-text="tipoPerdaLabel(p.tipo)"></td>
                                        <td class="py-2 pr-4" x-text="p.data"></td>
                                        <td class="py-2 pr-4" x-text="p.hora || '-'"></td>
                                    </tr>
                                </template>
                                <tr x-show="perdas.length === 0">
                                    <td colspan="4" class="py-4 text-sm text-gray-500">Nenhuma perda registrada.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-6" x-show="lancTab === 'salta_cio'" x-cloak>
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div class="text-center flex-1">
                            <div class="text-sm font-bold text-gray-900">Últimos salta cio</div>
                            <button type="button" @click="loadSaltaCio()" class="text-sm text-primary-600 hover:text-primary-700">Atualizar</button>
                        </div>
                        <button type="button" @click="openSaltaCioModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm w-11 h-11 bg-primary-600 text-white hover:bg-primary-700 transition-all duration-300 transform hover:scale-105 hover:shadow-lg" title="Registrar salta cio">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="p-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="py-2 pr-4">Ações</th>
                                    <th class="py-2 pr-4">Fêmea</th>
                                    <th class="py-2 pr-4">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="s in saltasCio" :key="s.id">
                                    <tr class="text-sm text-gray-700">
                                        <td class="py-2 pr-4">
                                            <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-red-600 hover:bg-red-50" title="Excluir" @click.prevent="deleteSaltaCio(s.id)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                        <td class="py-2 pr-4" x-text="s.matriz"></td>
                                        <td class="py-2 pr-4" x-text="s.data"></td>
                                    </tr>
                                </template>
                                <tr x-show="saltasCio.length === 0">
                                    <td colspan="3" class="py-4 text-sm text-gray-500">Nenhum registro encontrado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openCobertura" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openCobertura" @click="openCobertura = false; editingCoberturaId = null" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openCobertura" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-visible shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100" x-text="editingCoberturaId ? 'Alterar cobertura' : 'Registrar cobertura'"></h3>
                        <button type="button" @click="openCobertura = false; editingCoberturaId = null" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="w-full sm:w-auto grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-1">
                            <button type="button" @click="coberturaTab = 'principal'" class="w-full sm:w-auto px-4 py-2 rounded-xl text-sm font-semibold transition-colors text-center" :class="coberturaTab === 'principal' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                                Principal
                            </button>
                            <button type="button" @click="coberturaTab = 'complementar'" class="w-full sm:w-auto px-4 py-2 rounded-xl text-sm font-semibold transition-colors text-center" :class="coberturaTab === 'complementar' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                                Complementares
                            </button>
                        </div>
                    </div>

                    <div class="mt-4" x-show="coberturaTab === 'principal'" x-cloak>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Matriz</label>
                                <input type="text" x-model="matrizSearch" list="matrizes-list" @keydown.enter.prevent="selecionarMatrizPorIdPrimaria(false)" @change="selecionarMatrizPorIdPrimaria(true)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500" placeholder="Digite o ID primária e pressione Enter">
                                <datalist id="matrizes-list">
                                    <template x-for="f in matrizesFiltradas.slice(0, 50)" :key="`m-${f.id}`">
                                        <option :value="f.id_primaria" x-text="f.id_secundaria ? `${f.id_primaria} / ${f.id_secundaria}` : f.id_primaria"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados das montas/inseminações</div>
                                </div>

                                <datalist id="montas-ref-list">
                                    <template x-for="m in machos.slice(0, 500)" :key="`m-ref-${m.id}`">
                                        <option :value="`M-${m.id_primaria}`" x-text="`M-${m.id_primaria}${m.id_secundaria ? ' / ' + m.id_secundaria : ''}`"></option>
                                    </template>
                                    <template x-for="s in semens.slice(0, 500)" :key="`s-ref-${s.id}`">
                                        <option :value="`S-${s.id_primaria}`" x-text="`S-${s.id_primaria}${s.id_secundaria ? ' / ' + s.id_secundaria : ''}${s.raca_nome ? ' - ' + s.raca_nome : ''}${s.fornecedor_nome ? ' (' + s.fornecedor_nome + ')' : ''}`"></option>
                                    </template>
                                </datalist>

                                <div class="mt-3 space-y-3">
                                    <template x-for="(m, idx) in cobertura.montas" :key="`monta-${idx}`">
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Macho/Sêmen</label>
                                                <input type="text" x-model="m.ref" list="montas-ref-list" @keydown.enter.prevent="applyMontaRef(idx)" @blur="applyMontaRef(idx)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="M-123 ou S-456">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Data</label>
                                                <div class="mt-1 relative">
                                                    <input type="text"
                                                           x-model="m.data"
                                                           @click="openMontaDatePicker(idx)"
                                                           class="w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 pr-10"
                                                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG' : 'DD/MM/AAAA'"
                                                           inputmode="numeric"
                                                           autocomplete="off"
                                                           readonly>
                                                    <button type="button" @click="openMontaDatePicker(idx)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                                        <i class="fa-solid fa-calendar"></i>
                                                    </button>

                                                    <div x-show="activePicker === `monta-${idx}`" x-cloak class="absolute z-[200] mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-4 w-[calc(100vw-2rem)] max-w-xs sm:w-72 left-0 right-0 mx-auto sm:left-auto sm:right-0 max-h-[calc(100vh-12rem)] overflow-y-auto"
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

                                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                <span x-text="calendarType === '1000_dias' ? 'Dia PIG: ' + getPickerSelectedPigDay() : 'Data: ' + m.data"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Hora</label>
                                                <input type="time" x-model="m.hora" @change="if (idx === 0) cobertura.hora = m.hora" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Funcionário</label>
                                                <select x-model="m.usuario_id" @change="if (idx === 0) cobertura.usuarioId = m.usuario_id" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                                    <option value="">Selecione...</option>
                                                    <template x-for="u in usuarios" :key="`u-monta-${u.id}`">
                                                        <option :value="String(u.id)" x-text="u.nome"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="flex items-center justify-end gap-2" x-show="idx === (cobertura.montas.length - 1)" x-cloak>
                                                <button type="button" @click="addMonta()" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50" title="Adicionar">
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                                <button type="button" @click="removeMonta()" :disabled="cobertura.montas.length <= 1" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50" title="Remover">
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" x-show="coberturaTab === 'complementar'" x-cloak>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Presença de cio</label>
                                <select x-model="cobertura.presencaCio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="sim">Sim</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Peso da matriz (kg)</label>
                                <input type="number" step="0.01" min="0" x-model="cobertura.pesoMatriz" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Onde a matriz foi descoberta</label>
                                <input type="text" x-model="cobertura.localizacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Baia</label>
                                <input type="text" x-model="cobertura.baia" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Características da cobertura</label>
                                <textarea x-model="cobertura.caracteristicas" rows="3" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Observação</label>
                                <textarea x-model="cobertura.observacoes" rows="3" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                    <button type="button" @click="saveCobertura()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto disabled:opacity-50">
                        <template x-if="!saving"><span>Salvar</span></template>
                        <template x-if="saving"><span>Gravando...</span></template>
                    </button>
                    <button type="button" @click="openCobertura = false; editingCoberturaId = null" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="criteriosConfirmOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="criteriosConfirmOpen" @click="confirmCriteriosCancel()" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="criteriosConfirmOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-700">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">Critérios de cobertura</h3>
                                <div class="text-sm text-gray-500 mt-1">Alguns critérios não foram atendidos. Deseja continuar mesmo assim?</div>
                            </div>
                        </div>
                        <button type="button" @click="confirmCriteriosCancel()" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="mt-4 bg-amber-50 border border-amber-100 rounded-2xl p-4">
                        <ul class="text-sm text-amber-900 space-y-2">
                            <template x-for="(w, i) in criteriosConfirmWarnings" :key="`cw-${i}`">
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-amber-700">
                                        <i class="fa-solid fa-circle-dot text-[8px]"></i>
                                    </span>
                                    <span x-text="w"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                    <button type="button" @click="confirmCriteriosProceed()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto disabled:opacity-50">
                        Continuar
                    </button>
                    <button type="button" @click="confirmCriteriosCancel()" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto disabled:opacity-50">
                        Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="criteriosAfterSaveOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="criteriosAfterSaveOpen" @click="closeCriteriosAfterSave()" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="criteriosAfterSaveOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-700">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div>
                                <h3 class="text-lg leading-6 font-semibold text-gray-900">Avisos de critérios</h3>
                                <div class="text-sm text-gray-500 mt-1">Cobertura registrada, mas com avisos:</div>
                            </div>
                        </div>
                        <button type="button" @click="closeCriteriosAfterSave()" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="mt-4 bg-amber-50 border border-amber-100 rounded-2xl p-4">
                        <ul class="text-sm text-amber-900 space-y-2">
                            <template x-for="(w, i) in criteriosAfterSaveWarnings" :key="`aw-${i}`">
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-amber-700">
                                        <i class="fa-solid fa-circle-dot text-[8px]"></i>
                                    </span>
                                    <span x-text="w"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                    <button type="button" @click="closeCriteriosAfterSave()" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto">
                        Ok
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openPerda" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openPerda" @click="openPerda = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openPerda" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900">Registrar perda reprodutiva</h3>
                        <button type="button" @click="openPerda = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Matriz</label>
                            <select x-model="perda.femeaId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Selecione...</option>
                                <template x-for="f in matrizes" :key="`p-${f.id}`">
                                    <option :value="String(f.id)" x-text="f.id_primaria"></option>
                                </template>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Usuário</label>
                            <select x-model="perda.usuarioId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Selecione...</option>
                                <template x-for="u in usuarios" :key="`pu-${u.id}`">
                                    <option :value="String(u.id)" x-text="u.nome"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo</label>
                            <select x-model="perda.tipo" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                <option value="aborto">Aborto</option>
                                <option value="repeticao_cio">Repetição de cio</option>
                                <option value="falsa_prenhez">Falsa prenhez</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data</label>
                            <input type="text" inputmode="numeric" placeholder="dd/mm/aaaa" x-model="perda.data" @blur="perda.data = normalizeBrDate(perda.data)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hora (opcional)</label>
                            <input type="time" x-model="perda.hora" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Localização</label>
                            <input type="text" x-model="perda.localizacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Baia</label>
                            <input type="text" x-model="perda.baia" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Observações</label>
                            <textarea x-model="perda.observacoes" rows="3" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                    <button type="button" @click="savePerda()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto disabled:opacity-50">
                        <template x-if="!saving"><span>Salvar</span></template>
                        <template x-if="saving"><span>Gravando...</span></template>
                    </button>
                    <button type="button" @click="openPerda = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openSaltaCio" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openSaltaCio" @click="openSaltaCio = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openSaltaCio" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900">Registrar salta cio</h3>
                        <button type="button" @click="openSaltaCio = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Fêmea</label>
                            <select x-model="saltaCio.femeaId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Selecione...</option>
                                <template x-for="f in matrizes" :key="`sc-${f.id}`">
                                    <option :value="String(f.id)" x-text="f.id_primaria"></option>
                                </template>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Data</label>
                            <input type="text" inputmode="numeric" placeholder="dd/mm/aaaa" x-model="saltaCio.data" @blur="saltaCio.data = normalizeBrDate(saltaCio.data)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                </div>
                <div class="bg-white border-t border-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse sm:items-center sm:gap-3">
                    <button type="button" @click="saveSaltaCio()" :disabled="saving" class="w-full inline-flex justify-center items-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 sm:w-auto disabled:opacity-50">
                        <template x-if="!saving"><span>Salvar</span></template>
                        <template x-if="saving"><span>Gravando...</span></template>
                    </button>
                    <button type="button" @click="openSaltaCio = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 shadow-sm px-5 py-2.5 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto disabled:opacity-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Aba Análise -->
    <div x-show="tab === 'analise'" x-cloak class="space-y-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Análise</h6>
                    <div class="text-sm text-gray-500 mt-1">Formulário de coleta resumida de cobertura</div>
                </div>
            </div>
            
            <div class="p-6">
                <!-- Card Formulário de Cobertura Quadrado -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <button type="button" @click="openFormularioCobertura = true" class="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-200 hover:scale-[1.02] hover:border-primary-300">
                        <div class="p-6">
                            <div class="flex flex-col items-center text-center space-y-4">
                                <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center group-hover:bg-primary-200 transition-colors duration-200">
                                    <i class="fa-solid fa-file-lines text-2xl text-primary-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-200">
                                        Formulário de Cobertura
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Gerar formulário para coleta resumida
                                    </p>
                                </div>
                                <div class="w-full">
                                    <span class="inline-flex items-center justify-center w-full px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl group-hover:bg-primary-700 transition-colors duration-200">
                                        <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i>
                                        Abrir Formulário
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Formulário de Cobertura -->
    <div x-show="openFormularioCobertura" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openFormularioCobertura = false"></div>
            
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Formulário de Cobertura</h3>
                        <button type="button" @click="openFormularioCobertura = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Tipo de Formulário -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Formulário</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="tipoFormulario" value="em_branco" x-model="tipoFormulario" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Em branco</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="tipoFormulario" value="listar_matrizes" x-model="tipoFormulario" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Listar matrizes que serão cobertas</span>
                            </label>
                        </div>
                    </div>

                    <!-- Opção de Incluir Matriz -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Opção de Incluir Matriz</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="opcaoMatriz" value="todas" x-model="opcaoMatriz" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Todas</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="opcaoMatriz" value="vazias_dias" x-model="opcaoMatriz" class="text-primary-600">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vazias entre</span>
                                    <input type="number" x-model="diasVaziasInicio" placeholder="0" class="w-16 px-2 py-1 border rounded text-sm">
                                    <span class="text-sm text-gray-600">a</span>
                                    <input type="number" x-model="diasVaziasFim" placeholder="0" class="w-16 px-2 py-1 border rounded text-sm">
                                    <span class="text-sm text-gray-600">dias</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Opção de Incluir Leitoas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Opção de Incluir Leitoas</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="opcaoLeitoa" value="todas" x-model="opcaoLeitoa" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Todas</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="opcaoLeitoa" value="idade_dias" x-model="opcaoLeitoa" class="text-primary-600">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">De</span>
                                    <input type="number" x-model="idadeLeitoaInicio" placeholder="0" class="w-16 px-2 py-1 border rounded text-sm">
                                    <span class="text-sm text-gray-600">a</span>
                                    <input type="number" x-model="idadeLeitoaFim" placeholder="0" class="w-16 px-2 py-1 border rounded text-sm">
                                    <span class="text-sm text-gray-600">dias</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Ordenar Formulário -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ordenar Formulário por</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="ordenarPor" value="matriz" x-model="ordenarPor" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Matriz</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="ordenarPor" value="ciclo" x-model="ordenarPor" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ciclo</span>
                            </label>
                        </div>
                    </div>

                    <!-- Montas/Inseminações por Quantidade -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montas/Inseminações por Quantidade</label>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="quantidade" value="10" x-model="quantidadeMontas" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">10</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="quantidade" value="20" x-model="quantidadeMontas" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">20</span>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="radio" name="quantidade" value="30" x-model="quantidadeMontas" class="text-primary-600">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">30</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 flex justify-end space-x-3">
                    <button type="button" @click="openFormularioCobertura = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                    <button type="button" @click="gerarFormulario()" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-xl hover:bg-primary-700">
                        Gerar Formulário
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection
