@extends('layouts.dashboard')

@section('title', 'Ajustes - Critérios')
@section('page_title', 'Ajustes - Critérios')

@section('content')
<div x-data="{
    loaded: false,
    loading: false,
    saving: false,
    error: '',
    locked: false,
    criterios: {
        criterios_enabled: false,
        criterio_registro_cio_automatico: 'nao',
        criterio_cobertura_idade_min_dias: '210',
        criterio_cobertura_idade_max_dias: '240',
        criterio_cobertura_ciclos_min: '3',
        criterio_cobertura_peso_min_kg: '0',
        criterio_cobertura_peso_max_kg: '0',
        criterio_cobertura_presenca_cio: 'sim',
        criterio_dias_ate_cio: '21',
        criterio_dias_cio: '3',
        criterio_dias_gestacao: '114',
        criterio_dias_lactacao_min: '21',
        criterio_dias_lactacao_max: '28',
        criterio_dias_intervalo_desmame_cio: '5',
        criterio_leitoa_idade_min_dias: '150',
        criterio_leitoa_idade_max_dias: '150',
        criterio_maturidade_idade_min_dias: '151',
        criterio_maturidade_idade_max_dias: '220',
    },
    load() {
        this.loading = true;
        this.error = '';
        fetch('/api/criterios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                this.criterios.criterios_enabled = Boolean(Number(items.criterios_enabled ?? 0));
                this.criterios.criterio_registro_cio_automatico = (items.criterio_registro_cio_automatico === null || items.criterio_registro_cio_automatico === undefined || String(items.criterio_registro_cio_automatico).trim() === '') ? 'nao' : String(items.criterio_registro_cio_automatico);
                this.criterios.criterio_cobertura_idade_min_dias = (items.criterio_cobertura_idade_min_dias === null || items.criterio_cobertura_idade_min_dias === undefined || String(items.criterio_cobertura_idade_min_dias).trim() === '' || Number(items.criterio_cobertura_idade_min_dias) === 0) ? '210' : String(items.criterio_cobertura_idade_min_dias);
                this.criterios.criterio_cobertura_idade_max_dias = (items.criterio_cobertura_idade_max_dias === null || items.criterio_cobertura_idade_max_dias === undefined || String(items.criterio_cobertura_idade_max_dias).trim() === '' || Number(items.criterio_cobertura_idade_max_dias) === 0) ? '240' : String(items.criterio_cobertura_idade_max_dias);
                this.criterios.criterio_cobertura_ciclos_min = (items.criterio_cobertura_ciclos_min === null || items.criterio_cobertura_ciclos_min === undefined || String(items.criterio_cobertura_ciclos_min).trim() === '') ? '3' : String(items.criterio_cobertura_ciclos_min);
                this.criterios.criterio_cobertura_peso_min_kg = (items.criterio_cobertura_peso_min_kg === null || items.criterio_cobertura_peso_min_kg === undefined || String(items.criterio_cobertura_peso_min_kg).trim() === '') ? '0' : String(items.criterio_cobertura_peso_min_kg);
                this.criterios.criterio_cobertura_peso_max_kg = (items.criterio_cobertura_peso_max_kg === null || items.criterio_cobertura_peso_max_kg === undefined || String(items.criterio_cobertura_peso_max_kg).trim() === '') ? '0' : String(items.criterio_cobertura_peso_max_kg);
                this.criterios.criterio_cobertura_presenca_cio = (items.criterio_cobertura_presenca_cio === null || items.criterio_cobertura_presenca_cio === undefined || String(items.criterio_cobertura_presenca_cio).trim() === '') ? 'sim' : String(items.criterio_cobertura_presenca_cio);
                this.criterios.criterio_dias_ate_cio = (items.criterio_dias_ate_cio === null || items.criterio_dias_ate_cio === undefined || String(items.criterio_dias_ate_cio).trim() === '') ? '21' : String(items.criterio_dias_ate_cio);
                this.criterios.criterio_dias_cio = (items.criterio_dias_cio === null || items.criterio_dias_cio === undefined || String(items.criterio_dias_cio).trim() === '') ? '3' : String(items.criterio_dias_cio);
                this.criterios.criterio_dias_gestacao = (items.criterio_dias_gestacao === null || items.criterio_dias_gestacao === undefined || String(items.criterio_dias_gestacao).trim() === '') ? '114' : String(items.criterio_dias_gestacao);
                this.criterios.criterio_dias_lactacao_min = (items.criterio_dias_lactacao_min === null || items.criterio_dias_lactacao_min === undefined || String(items.criterio_dias_lactacao_min).trim() === '') ? '21' : String(items.criterio_dias_lactacao_min);
                this.criterios.criterio_dias_lactacao_max = (items.criterio_dias_lactacao_max === null || items.criterio_dias_lactacao_max === undefined || String(items.criterio_dias_lactacao_max).trim() === '') ? '28' : String(items.criterio_dias_lactacao_max);
                this.criterios.criterio_dias_intervalo_desmame_cio = (items.criterio_dias_intervalo_desmame_cio === null || items.criterio_dias_intervalo_desmame_cio === undefined || String(items.criterio_dias_intervalo_desmame_cio).trim() === '') ? '5' : String(items.criterio_dias_intervalo_desmame_cio);
                this.criterios.criterio_leitoa_idade_min_dias = (items.criterio_leitoa_idade_min_dias === null || items.criterio_leitoa_idade_min_dias === undefined || String(items.criterio_leitoa_idade_min_dias).trim() === '') ? '150' : String(items.criterio_leitoa_idade_min_dias);
                this.criterios.criterio_leitoa_idade_max_dias = (items.criterio_leitoa_idade_max_dias === null || items.criterio_leitoa_idade_max_dias === undefined || String(items.criterio_leitoa_idade_max_dias).trim() === '') ? '150' : String(items.criterio_leitoa_idade_max_dias);
                this.criterios.criterio_maturidade_idade_min_dias = (items.criterio_maturidade_idade_min_dias === null || items.criterio_maturidade_idade_min_dias === undefined || String(items.criterio_maturidade_idade_min_dias).trim() === '') ? '151' : String(items.criterio_maturidade_idade_min_dias);
                this.criterios.criterio_maturidade_idade_max_dias = (items.criterio_maturidade_idade_max_dias === null || items.criterio_maturidade_idade_max_dias === undefined || String(items.criterio_maturidade_idade_max_dias).trim() === '') ? '220' : String(items.criterio_maturidade_idade_max_dias);
                if (data.message) this.error = data.message;
                this.loaded = true;
            })
            .catch(() => {
                this.error = 'Não foi possível carregar os critérios.';
            })
            .finally(() => { this.loading = false; });
    },
    save() {
        this.saving = true;
        this.error = '';

        const payload = {
            criterios_enabled: Boolean(this.criterios.criterios_enabled),
            criterio_registro_cio_automatico: this.criterios.criterio_registro_cio_automatico === '' ? null : String(this.criterios.criterio_registro_cio_automatico),
            criterio_cobertura_idade_min_dias: this.criterios.criterio_cobertura_idade_min_dias === '' ? null : Number(String(this.criterios.criterio_cobertura_idade_min_dias).trim()),
            criterio_cobertura_idade_max_dias: this.criterios.criterio_cobertura_idade_max_dias === '' ? null : Number(String(this.criterios.criterio_cobertura_idade_max_dias).trim()),
            criterio_cobertura_ciclos_min: this.criterios.criterio_cobertura_ciclos_min === '' ? null : Number(String(this.criterios.criterio_cobertura_ciclos_min).trim()),
            criterio_cobertura_peso_min_kg: this.criterios.criterio_cobertura_peso_min_kg === '' ? null : Number(String(this.criterios.criterio_cobertura_peso_min_kg).trim().replace(',', '.')),
            criterio_cobertura_peso_max_kg: this.criterios.criterio_cobertura_peso_max_kg === '' ? null : Number(String(this.criterios.criterio_cobertura_peso_max_kg).trim().replace(',', '.')),
            criterio_cobertura_presenca_cio: this.criterios.criterio_cobertura_presenca_cio === '' ? null : String(this.criterios.criterio_cobertura_presenca_cio),
            criterio_dias_ate_cio: this.criterios.criterio_dias_ate_cio === '' ? null : Number(String(this.criterios.criterio_dias_ate_cio).trim()),
            criterio_dias_cio: this.criterios.criterio_dias_cio === '' ? null : Number(String(this.criterios.criterio_dias_cio).trim()),
            criterio_dias_gestacao: this.criterios.criterio_dias_gestacao === '' ? null : Number(String(this.criterios.criterio_dias_gestacao).trim()),
            criterio_dias_lactacao_min: this.criterios.criterio_dias_lactacao_min === '' ? null : Number(String(this.criterios.criterio_dias_lactacao_min).trim()),
            criterio_dias_lactacao_max: this.criterios.criterio_dias_lactacao_max === '' ? null : Number(String(this.criterios.criterio_dias_lactacao_max).trim()),
            criterio_dias_intervalo_desmame_cio: this.criterios.criterio_dias_intervalo_desmame_cio === '' ? null : Number(String(this.criterios.criterio_dias_intervalo_desmame_cio).trim()),
            criterio_leitoa_idade_min_dias: this.criterios.criterio_leitoa_idade_min_dias === '' ? null : Number(String(this.criterios.criterio_leitoa_idade_min_dias).trim()),
            criterio_leitoa_idade_max_dias: this.criterios.criterio_leitoa_idade_max_dias === '' ? null : Number(String(this.criterios.criterio_leitoa_idade_max_dias).trim()),
            criterio_maturidade_idade_min_dias: this.criterios.criterio_maturidade_idade_min_dias === '' ? null : Number(String(this.criterios.criterio_maturidade_idade_min_dias).trim()),
            criterio_maturidade_idade_max_dias: this.criterios.criterio_maturidade_idade_max_dias === '' ? null : Number(String(this.criterios.criterio_maturidade_idade_max_dias).trim()),
        };

        fetch('{{ route('admin.criterios.store', [], false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    let msg = data?.message || 'Erro ao salvar critérios.';
                    if (data?.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey) msg = data.errors[firstKey][0];
                    }
                    throw new Error(msg);
                }
                return data;
            })
            .then(data => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Critérios salvos com sucesso!', type: 'success' } }));
                setTimeout(() => window.location.reload(), 350);
            })
            .catch(e => {
                this.error = e.message || 'Erro ao salvar critérios.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: this.error, type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
    },
}" x-init="load()" class="space-y-6">
    <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
        Ajustes em desenvolvimento.
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Critérios</h6>
                <div class="text-sm text-gray-500 mt-1">Disparam avisos durante lançamentos quando não estiver conforme o padrão definido.</div>
            </div>
            <button type="button" @click="save()" :disabled="saving" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                <template x-if="!saving"><span>Salvar</span></template>
                <template x-if="saving"><span>Salvando...</span></template>
            </button>
        </div>
        <div class="p-4 sm:p-6 space-y-4">
            <div x-show="error" class="bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="error" x-cloak></div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Ativar critérios</div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" class="rounded text-primary-600 focus:ring-primary-500 disabled:opacity-60" x-model="criterios.criterios_enabled" :disabled="locked">
                        Habilitado
                    </label>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Leitoa</div>
                <div class="text-xs text-gray-500 mt-1">Fase considerada até a idade máxima.</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade mínima (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Padrão: 150.</div>
                        <input type="number" min="0" max="1000" step="1" x-model="criterios.criterio_leitoa_idade_min_dias" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade máxima (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Padrão: 150.</div>
                        <input type="number" min="0" max="1000" step="1" x-model="criterios.criterio_leitoa_idade_max_dias" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Maturidade reprodutiva</div>
                <div class="text-xs text-gray-500 mt-1">Janela usada para permitir registro de cio inicial.</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade mínima (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Padrão: 151.</div>
                        <input type="number" min="0" max="1000" step="1" x-model="criterios.criterio_maturidade_idade_min_dias" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade máxima (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Padrão: 220.</div>
                        <input type="number" min="0" max="1000" step="1" x-model="criterios.criterio_maturidade_idade_max_dias" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Cio</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Registro de cio automático</label>
                        <div class="text-xs text-gray-500 mt-1">Quando habilitado, o sistema pode registrar o cio automaticamente ao confirmar a fase de cio.</div>
                        <select x-model="criterios.criterio_registro_cio_automatico" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            <option value="nao">Não (exige registro de cio)</option>
                            <option value="sim">Sim (registra automaticamente quando está na fase cio)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dias até o próximo cio</label>
                        <div class="text-xs text-gray-500 mt-1">Intervalo médio entre um cio e o próximo. Usado nas previsões do acompanhamento. Padrão: 21.</div>
                        <input type="number" min="0" max="365" step="1" x-model="criterios.criterio_dias_ate_cio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duração do cio (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Quantidade de dias considerados como período de cio. Padrão: 3.</div>
                        <input type="number" min="1" max="10" step="1" x-model="criterios.criterio_dias_cio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Cobertura</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade mínima (dias)</label>
                        <input type="number" min="0" step="1" x-model="criterios.criterio_cobertura_idade_min_dias" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100 disabled:opacity-60" :disabled="locked">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade máxima (dias)</label>
                        <input type="number" min="0" step="1" x-model="criterios.criterio_cobertura_idade_max_dias" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100 disabled:opacity-60" :disabled="locked">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ciclos mínimos</label>
                        <div class="text-xs text-gray-500 mt-1">Cobertura permitida a partir deste número de ciclos.</div>
                        <input type="number" min="0" step="1" x-model="criterios.criterio_cobertura_ciclos_min" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100 disabled:opacity-60" :disabled="locked">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Presença de cio</label>
                        <select x-model="criterios.criterio_cobertura_presenca_cio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100 disabled:opacity-60" :disabled="locked">
                            <option value="">Não validar</option>
                            <option value="sim">Sim</option>
                            <option value="nao">Não</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Peso mínimo (kg)</label>
                        <input type="number" min="0" step="0.01" x-model="criterios.criterio_cobertura_peso_min_kg" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100 disabled:opacity-60" :disabled="locked">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Peso máximo (kg)</label>
                        <input type="number" min="0" step="0.01" x-model="criterios.criterio_cobertura_peso_max_kg" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100 disabled:opacity-60" :disabled="locked">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Gestação e Lactação</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dias gestação</label>
                        <div class="text-xs text-gray-500 mt-1">Duração média da gestação (cobertura → parto previsto). Usado para travar coberturas durante a gestação e para previsões. Padrão: 114.</div>
                        <input type="number" min="1" max="200" step="1" x-model="criterios.criterio_dias_gestacao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lactação (mínimo dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Padrão: 21.</div>
                        <input type="number" min="1" max="60" step="1" x-model="criterios.criterio_dias_lactacao_min" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lactação (máximo dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Padrão: 28.</div>
                        <input type="number" min="1" max="60" step="1" x-model="criterios.criterio_dias_lactacao_max" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dias intervalo desmame-cio</label>
                        <div class="text-xs text-gray-500 mt-1">Tempo médio entre o desmame e o retorno ao cio (início da janela de cobertura pós-desmame). Padrão: 5.</div>
                        <input type="number" min="0" max="30" step="1" x-model="criterios.criterio_dias_intervalo_desmame_cio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
