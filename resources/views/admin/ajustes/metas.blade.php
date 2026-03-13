@extends('layouts.dashboard')

@section('title', 'Ajustes - Metas')
@section('page_title', 'Ajustes - Metas')

@section('content')
<div x-data="{
    loaded: false,
    loading: false,
    saving: false,
    error: '',
    metas: {
        meta_plantel_estoque_matrizes: '',
        meta_plantel_estoque_leitoas: '',
        meta_entrada_peso_leitoa: '',
        meta_entrada_peso_matriz: '',
        meta_entrada_peso_macho: '',
        gestacao_meta_taxa_paricao: '',
        gestacao_meta_perdas_reprodutivas: '',
        meta_manutencao_reposicao: '',
        meta_manutencao_descarte_matrizes: '',
        meta_manutencao_mortalidade_matrizes: '',
        meta_manutencao_perdas_leitoas_pre_cobertura: '',
        meta_selecao_idade_selecao: '',
        meta_selecao_idade_cobertura: '',
        meta_produtividade_dias_nao_produtivos: '',
    },
    loadMetas() {
        this.loading = true;
        this.error = '';
        fetch('/api/metas', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                Object.keys(this.metas).forEach(k => {
                    this.metas[k] = (items[k] === null || items[k] === undefined) ? '' : String(items[k]);
                });
                if (data.message) this.error = data.message;
                this.loaded = true;
            })
            .catch(() => {
                this.error = 'Não foi possível carregar as metas.';
            })
            .finally(() => { this.loading = false; });
    },
    saveMetas() {
        this.saving = true;
        this.error = '';

        const payload = {};
        Object.keys(this.metas).forEach(k => {
            const raw = String(this.metas[k] ?? '').trim().replace(',', '.');
            payload[k] = raw === '' ? null : Number(raw);
        });

        fetch('{{ route('admin.metas.store', [], false) }}', {
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
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Metas salvas com sucesso!', type: 'success' } }));
            setTimeout(() => {
                window.location.reload();
            }, 350);
        })
        .catch(e => {
            this.error = e.message || 'Erro ao salvar metas.';
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: this.error, type: 'error' } }));
        })
        .finally(() => { this.saving = false; });
    },
}" x-init="loadMetas()" class="space-y-6">
    <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
        Ajustes em desenvolvimento.
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Metas</h6>
                <div class="text-sm text-gray-500 mt-1">Utilizadas para efeito comparativo em relatórios e notificações.</div>
            </div>
            <button type="button" @click="saveMetas()" :disabled="saving" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                <template x-if="!saving"><span>Salvar</span></template>
                <template x-if="saving"><span>Salvando...</span></template>
            </button>
        </div>
        <div class="p-4 sm:p-6 space-y-4">
            <div x-show="error" class="bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="error" x-cloak></div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Metas do Plantel de Fêmeas</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estoque de matrizes</label>
                            <div class="text-xs text-gray-500 mt-1">Quantidade mínima de matrizes (vazias + gestantes) desejada no plantel. Gera alerta quando o estoque ativo fica abaixo.</div>
                        <input type="number" min="0" step="1" x-model="metas.meta_plantel_estoque_matrizes" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estoque de leitoas</label>
                            <div class="text-xs text-gray-500 mt-1">Quantidade mínima de leitoas desejada no plantel. Gera alerta quando o estoque ativo fica abaixo.</div>
                        <input type="number" min="0" step="1" x-model="metas.meta_plantel_estoque_leitoas" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Metas de Entrada do Plantel</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Peso leitoa (kg)</label>
                        <div class="text-xs text-gray-500 mt-1">Peso alvo esperado na entrada (compra) de leitoas. Gera alerta quando a média de entradas dos últimos 30 dias fica abaixo.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_entrada_peso_leitoa" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Peso matriz (kg)</label>
                        <div class="text-xs text-gray-500 mt-1">Peso alvo esperado na entrada (compra) de matrizes (vazias/gestantes). Gera alerta quando a média de entradas dos últimos 30 dias fica abaixo.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_entrada_peso_matriz" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Peso do macho (kg)</label>
                        <div class="text-xs text-gray-500 mt-1">Peso alvo esperado na entrada (compra) de machos. Gera alerta quando a média de entradas dos últimos 30 dias fica abaixo.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_entrada_peso_macho" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Metas de Gestação</div>
                <div class="mt-2 text-sm text-gray-500">Usadas para acompanhamento de resultados do manejo de gestação.</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meta taxa de parição (%)</label>
                        <div class="text-xs text-gray-500 mt-1">Percentual alvo de partos em relação às coberturas. Valor de 0 a 100.</div>
                        <input type="number" min="0" max="100" step="0.01" x-model="metas.gestacao_meta_taxa_paricao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meta perdas reprodutivas (%)</label>
                        <div class="text-xs text-gray-500 mt-1">Percentual máximo desejado de perdas reprodutivas. Valor de 0 a 100.</div>
                        <input type="number" min="0" max="100" step="0.01" x-model="metas.gestacao_meta_perdas_reprodutivas" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Meta de Manutenção do Plantel de Fêmeas</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reposição</label>
                        <div class="text-xs text-gray-500 mt-1">Percentual/valor alvo para reposição planejada de fêmeas no período. Usado para comparativos em relatórios.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_manutencao_reposicao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descarte de matrizes</label>
                        <div class="text-xs text-gray-500 mt-1">Percentual/valor alvo de descartes de matrizes no período. Usado para comparativos em relatórios.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_manutencao_descarte_matrizes" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mortalidade de matrizes</label>
                        <div class="text-xs text-gray-500 mt-1">Percentual/valor alvo de mortalidade de matrizes no período. Usado para comparativos em relatórios.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_manutencao_mortalidade_matrizes" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Perdas leitoas pré coberturas</label>
                        <div class="text-xs text-gray-500 mt-1">Percentual/valor alvo de perdas de leitoas antes da primeira cobertura. Usado para comparativos em relatórios.</div>
                        <input type="number" min="0" step="0.01" x-model="metas.meta_manutencao_perdas_leitoas_pre_cobertura" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Meta de Seleção de Leitoas</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade de seleção (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Idade alvo (em dias) para selecionar leitoas no manejo. Usado para comparativos e regras internas.</div>
                        <input type="number" min="0" step="1" x-model="metas.meta_selecao_idade_selecao" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Idade de cobertura (dias)</label>
                        <div class="text-xs text-gray-500 mt-1">Idade alvo (em dias) para primeira cobertura. Usado para comparativos e regras internas.</div>
                        <input type="number" min="0" step="1" x-model="metas.meta_selecao_idade_cobertura" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Meta de Produtividade</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dias não produtivos</label>
                        <div class="text-xs text-gray-500 mt-1">Limite/meta de dias não produtivos no plantel reprodutivo. Usado em relatórios e acompanhamento.</div>
                        <input type="number" min="0" step="1" x-model="metas.meta_produtividade_dias_nao_produtivos" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
