@extends('layouts.dashboard')

@section('title', 'Gestação')
@section('page_title', '')

@section('content')
<div
    x-data="{
        tab: 'lancamentos',
        lancTab: 'cobertura',
        coberturaTab: 'principal',
        openCobertura: false,
        openPerda: false,
        saving: false,
        error: '',

        matrizes: [],
        machos: [],
        usuarios: [],
        coberturas: [],
        perdas: [],
        saltasCio: [],

        criteriosLoaded: false,
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

        cobertura: {
            femeaId: '',
            usuarioId: '',
            modo: 'macho',
            machoId: '',
            semen: '',
            data: '',
            hora: '',
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
                    this.criteriosLoaded = true;
                })
                .catch(() => { this.criteriosLoaded = true; });

            this.loadCoberturas();
            this.loadPerdas();
            this.loadSaltaCio();
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
            this.openCobertura = true;
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

            if (!this.cobertura.usuarioId) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Selecione o usuário', type: 'error' } }));
                return;
            }

            if (!this.cobertura.data || !this.cobertura.hora) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe data e hora', type: 'error' } }));
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

            if (this.cobertura.modo === 'macho' && !this.cobertura.machoId) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Selecione o macho', type: 'error' } }));
                return;
            }

            if (this.cobertura.modo === 'semen' && !this.cobertura.semen.trim()) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe o sêmen', type: 'error' } }));
                return;
            }

            const payload = {
                femea_id: Number(this.cobertura.femeaId),
                usuario_id: Number(this.cobertura.usuarioId),
                macho_id: this.cobertura.modo === 'macho' ? Number(this.cobertura.machoId) : null,
                semen: this.cobertura.modo === 'semen' ? this.cobertura.semen.trim() : null,
                data: this.brToIso(this.cobertura.data),
                hora: this.cobertura.hora,
                presenca_cio: this.cobertura.presencaCio,
                localizacao: this.cobertura.localizacao || null,
                baia: this.cobertura.baia || null,
                peso_matriz: this.cobertura.pesoMatriz === '' ? null : Number(this.cobertura.pesoMatriz),
                caracteristicas: this.cobertura.caracteristicas ? this.cobertura.caracteristicas.trim() : null,
                observacoes: this.cobertura.observacoes ? this.cobertura.observacoes.trim() : null,
            };

            if (!payload.data) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Informe a data (dd/mm/aaaa)', type: 'error' } }));
                return;
            }

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

            fetch('/api/gestacao/coberturas', {
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
                    if (!r.ok) throw new Error(data?.message || 'Erro ao salvar cobertura');
                    return data;
                })
                .then((data) => {
                    const warnings = Array.isArray(data?.warnings) ? data.warnings : [];
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Cobertura registrada com sucesso!', type: 'success' } }));
                    this.openCobertura = false;
                    this.loadCoberturas();
                    if (warnings.length > 0) {
                        this.criteriosAfterSaveWarnings = warnings;
                        this.criteriosAfterSaveOpen = true;
                    }
                })
                .catch(e => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message || 'Erro ao salvar cobertura', type: 'error' } }));
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
            const dataCobertura = this.brToDate(this.cobertura.data);
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
    <div class="mb-6 -mx-3 sm:-mx-6 px-3 sm:px-6 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
        <div class="pt-4 pb-2">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Gestação</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Coberturas, perdas reprodutivas e metas</p>
        </div>
        <nav class="-mb-px flex space-x-6 overflow-x-auto">
            <button type="button" @click="tab = 'lancamentos'" 
                :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700'"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Lançamentos
            </button>
        </nav>
    </div>

    <div x-show="tab === 'lancamentos'" x-cloak class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
                <div>
                    <h6 class="font-bold text-primary-700 dark:text-primary-400 uppercase text-xs tracking-wider">Lançamentos</h6>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Coberturas e perdas reprodutivas.</div>
                </div>
            </div>
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-800 p-1.5 rounded-xl self-start overflow-x-auto max-w-full border border-gray-200/50 dark:border-gray-700/50">
                    <button type="button" @click="lancTab = 'cobertura'" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all" :class="lancTab === 'cobertura' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-100/10' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                        Coberturas
                    </button>
                    <button type="button" @click="lancTab = 'perda'" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all" :class="lancTab === 'perda' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-100/10' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                        Perdas
                    </button>
                    <button type="button" @click="lancTab = 'salta_cio'" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all" :class="lancTab === 'salta_cio' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-100/10' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                        Salta cio
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-show="lancTab === 'cobertura'" x-cloak @click="openCoberturaModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm w-11 h-11 bg-primary-600 text-white hover:bg-primary-700" title="Registrar cobertura">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <button type="button" x-show="lancTab === 'perda'" x-cloak @click="openPerdaModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm w-11 h-11 bg-primary-600 text-white hover:bg-primary-700" title="Registrar perda reprodutiva">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <button type="button" x-show="lancTab === 'salta_cio'" x-cloak @click="openSaltaCioModal()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm w-11 h-11 bg-primary-600 text-white hover:bg-primary-700" title="Registrar salta cio">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="p-6" x-show="lancTab === 'cobertura'" x-cloak>
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div class="text-sm font-bold text-gray-900">Últimas coberturas</div>
                        <button type="button" @click="loadCoberturas()" class="text-sm text-primary-600 hover:text-primary-700">Atualizar</button>
                    </div>
                    <div class="p-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="py-2 pr-4">Ações</th>
                                    <th class="py-2 pr-4">Matriz</th>
                                    <th class="py-2 pr-4">Macho/Sêmen</th>
                                    <th class="py-2 pr-4">Data</th>
                                    <th class="py-2 pr-4">Hora</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="c in coberturas" :key="c.id">
                                    <tr class="text-sm text-gray-700 dark:text-gray-300">
                                        <td class="py-2 pr-4">
                                            <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" title="Excluir" @click.prevent="deleteCobertura(c.id)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                        <td class="py-2 pr-4" x-text="c.matriz"></td>
                                        <td class="py-2 pr-4" x-text="c.macho || c.semen || '-'"></td>
                                        <td class="py-2 pr-4" x-text="c.data"></td>
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
                        <div class="text-sm font-bold text-gray-900">Últimas perdas</div>
                        <button type="button" @click="loadPerdas()" class="text-sm text-primary-600 hover:text-primary-700">Atualizar</button>
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
                        <div class="text-sm font-bold text-gray-900">Últimos salta cio</div>
                        <button type="button" @click="loadSaltaCio()" class="text-sm text-primary-600 hover:text-primary-700">Atualizar</button>
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
            <div x-show="openCobertura" @click="openCobertura = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 dark:bg-black/60 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="openCobertura" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100">Registrar cobertura</h3>
                        <button type="button" @click="openCobertura = false" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" title="Fechar">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Matriz</label>
                                <select x-model="cobertura.femeaId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione...</option>
                                    <template x-for="f in matrizes" :key="f.id">
                                        <option :value="String(f.id)" x-text="f.id_primaria"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Data</label>
                                <input type="text" inputmode="numeric" placeholder="dd/mm/aaaa" x-model="cobertura.data" @blur="cobertura.data = normalizeBrDate(cobertura.data)" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hora</label>
                                <input type="time" x-model="cobertura.hora" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div class="md:col-span-2">
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" value="macho" x-model="cobertura.modo" class="text-primary-600 focus:ring-primary-500">
                                        Macho
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" value="semen" x-model="cobertura.modo" class="text-primary-600 focus:ring-primary-500">
                                        Sêmen
                                    </label>
                                </div>
                            </div>
                            <div x-show="cobertura.modo === 'macho'" class="md:col-span-2" x-cloak>
                                <label class="block text-sm font-medium text-gray-700">Macho</label>
                                <select x-model="cobertura.machoId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione...</option>
                                    <template x-for="m in machos" :key="m.id">
                                        <option :value="String(m.id)" x-text="m.id_primaria"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="cobertura.modo === 'semen'" class="md:col-span-2" x-cloak>
                                <label class="block text-sm font-medium text-gray-700">Sêmen utilizado</label>
                                <input type="text" x-model="cobertura.semen" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ex: Lote/Identificação do sêmen">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" x-show="coberturaTab === 'complementar'" x-cloak>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Usuário</label>
                                <select x-model="cobertura.usuarioId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">Selecione...</option>
                                    <template x-for="u in usuarios" :key="`u-${u.id}`">
                                        <option :value="String(u.id)" x-text="u.nome"></option>
                                    </template>
                                </select>
                            </div>
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
                    <button type="button" @click="openCobertura = false" :disabled="saving" class="mt-3 w-full inline-flex justify-center items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto disabled:opacity-50">
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

</div>
@endsection
