@extends('layouts.dashboard')

@section('title', 'Ajustes - Metas e Critérios')
@section('page_title', 'Ajustes - Metas e Critérios')

@section('content')
<div x-data="{
    loaded: false,
    loading: false,
    saving: false,
    error: '',
    mainTab: 'plantel',
    subTabPlantel: 'metas',
    subTabGestacao: 'metas',
    ruleTabPlantel: 'leitoa',
    ruleTabGestacao: 'cobertura',

    metas: {
        // --- PLANTEL REPRODUTIVO ---
        meta_entrada_peso_leitoa: '135',
        meta_entrada_peso_matriz: '200',
        meta_entrada_peso_macho: '200',
        meta_manutencao_reposicao: '40',
        meta_manutencao_descarte_matrizes: '40',
        meta_manutencao_mortalidade_matrizes: '0.50',
        meta_manutencao_perdas_leitoas_pre_cobertura: '10',
        meta_selecao_idade_selecao: '150',
        meta_selecao_idade_cobertura: '230',
        meta_retencao_cobertura_p1: '90',
        meta_retencao_pos_p1: '85',
        meta_retencao_pos_p2: '76',
        meta_retencao_pos_p3: '68',
        meta_retencao_pos_p4: '62',
        meta_retencao_pos_p5: '55',
        meta_retencao_pos_p6: '40',
        meta_retencao_pos_p7: '30',
        meta_retencao_pos_p8: '20',
        meta_retencao_pos_p9: '10',
        meta_retencao_pos_p10: '0',
        meta_plantel_estoque_matrizes: '100',
        meta_plantel_estoque_leitoas: '50',

        criterio_leitoa_peso_min: '6',
        criterio_leitoa_peso_max: '150',
        criterio_leitoa_idade_min: '60',
        criterio_leitoa_idade_max: '190',
        criterio_matriz_peso_min: '20',
        criterio_matriz_peso_max: '350',
        criterio_matriz_idade_min: '60',
        criterio_matriz_idade_max: '2100',
        criterio_matriz_ciclo_min: '0',
        criterio_matriz_ciclo_max: '15',
        criterio_macho_peso_min: '20',
        criterio_macho_peso_max: '350',
        criterio_macho_idade_min: '20',
        criterio_macho_idade_max: '300',
        criterio_venda_peso_min: '20',
        criterio_venda_peso_max: '600',
        criterio_dias_cio_leitoa: '21',
        criterio_inconsistencia_descarte_dias_min: '0',
        criterio_inconsistencia_descarte_dias_max: '90',
        criterio_inconsistencia_macho_parado_min: '0',
        criterio_inconsistencia_macho_parado_max: '60',

        // --- MANEJO DE GESTAÇÃO ---
        // Metas de Cobertura
        meta_gestacao_montas_por_cobertura: '3',
        meta_gestacao_periodo_gestacao: '114',
        meta_gestacao_intervalo_desmame_cobertura: '7',
        
        // Metas de Perda
        meta_gestacao_repeticao_cio: '7',
        meta_gestacao_aborto: '2',
        meta_gestacao_falsa_prenhez: '1',
        gestacao_meta_taxa_paricao: '90',

        // Metas de Desempenho
        meta_gestacao_partos_femea_ano: '2.50',
        meta_produtividade_dias_nao_produtivos: '9',

        // Critérios Cobertura
        criterio_matriz_vazia_max_dias: '250',
        criterio_cobertura_leitoa_idade_min: '220',
        criterio_cobertura_leitoa_idade_max: '300',
        criterio_cobertura_leitoa_peso_min: '90',
        criterio_cobertura_leitoa_peso_max: '200',
        criterio_cobertura_matriz_peso_min: '100',
        criterio_cobertura_matriz_peso_max: '350',
        criterio_cobertura_lactante_permitida: 'nao',

        // Critérios Perdas (Dias)
        criterio_perda_aborto_dias_min: '12',
        criterio_perda_aborto_dias_max: '110',
        criterio_perda_repeticao_cio_dias_min: '10',
        criterio_perda_repeticao_cio_dias_max: '120',
        criterio_perda_falsa_gestacao_dias_min: '75',
        criterio_perda_falsa_gestacao_dias_max: '127',

        // Critérios Validação Dias
        criterio_intervalo_repeticao_cobertura_min: '0',
        criterio_intervalo_repeticao_cobertura_max: '250',
        criterio_intervalo_aborto_cobertura_min: '1',
        criterio_intervalo_aborto_cobertura_max: '250',
        criterio_intervalo_falsa_prenhez_cobertura_min: '1',
        criterio_intervalo_falsa_prenhez_cobertura_max: '250',
        criterio_intervalo_cobertura_cobertura_min: '10',
        criterio_intervalo_cobertura_cobertura_max: '250',
        criterio_intervalo_parto_cobertura_min: '10',
        criterio_intervalo_parto_cobertura_max: '63',
        criterio_intervalo_lactacao_salto_cio_min: '10',
        criterio_intervalo_lactacao_salto_cio_max: '42',
        criterio_intervalo_vazio_salto_cobertura_min: '0',
        criterio_intervalo_vazio_salto_cobertura_max: '42',

        // Matriz Gestante Entry (Gestaçao Flow)
        criterio_gestante_peso_min: '20',
        criterio_gestante_peso_max: '350',
        criterio_gestante_idade_min: '60',
        criterio_gestante_idade_max: '2100',
        criterio_gestante_ciclo_min: '0',
        criterio_gestante_ciclo_max: '15',
        criterio_gestante_dias_gestacao_min: '21',
        criterio_gestante_dias_gestacao_max: '127',
        
        criterio_dias_lactacao_min: '21',
        criterio_dias_lactacao_max: '28',

        // Geral
        criterios_enabled: true,
        criterio_calendario_tipo: 'gregoriano'
    },

    loadData() {
        this.loading = true;
        this.error = '';
        fetch('/api/metas', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const items = data.items || {};
                Object.keys(this.metas).forEach(k => {
                    if (items[k] !== null && items[k] !== undefined) {
                        this.metas[k] = String(items[k]);
                    }
                });
                if (data.message) this.error = data.message;
                this.loaded = true;
            })
            .catch(() => {
                this.error = 'Não foi possível carregar os dados.';
            })
            .finally(() => { this.loading = false; });
    },
    saveData() {
        this.saving = true;
        this.error = '';

        const payload = {};
        Object.keys(this.metas).forEach(k => {
            const raw = String(this.metas[k] ?? '').trim().replace(',', '.');
            if (k === 'criterios_enabled') {
                 payload[k] = (this.metas[k] === 'true' || this.metas[k] === true || this.metas[k] === '1');
            } else if (k === 'criterio_calendario_tipo' || k === 'criterio_cobertura_lactante_permitida') {
                payload[k] = raw === '' ? '' : raw;
            } else {
                payload[k] = raw === '' ? null : Number(raw);
            }
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
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                let msg = data.message || 'Erro ao salvar ajustes.';
                if (data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey) msg = data.errors[firstKey][0];
                }
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Ajustes salvos com sucesso!', type: 'success' } }));
            setTimeout(() => window.location.reload(), 350);
        })
        .catch(e => {
            this.error = e.message || 'Erro ao salvar ajustes.';
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: this.error, type: 'error' } }));
        })
        .finally(() => { this.saving = false; });
    },
}" x-init="loadData()" class="space-y-6 max-w-7xl mx-auto pb-12">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary-600 flex items-center justify-center text-white shadow-lg shadow-primary-500/20">
                 <i class="fa-solid fa-gears text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ajustes Globais</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Configure as regras e alvos dos manejos de sua granja.</p>
            </div>
        </div>
        <button type="button" @click="saveData()" :disabled="saving" class="inline-flex items-center justify-center rounded-xl px-6 py-3 bg-primary-600 text-sm font-bold text-white shadow-xl hover:bg-primary-700 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50">
            <i class="fa-solid fa-cloud-arrow-up mr-2" x-show="!saving"></i>
            <span x-text="saving ? 'Salvando...' : 'Salvar Configurações'"></span>
        </button>
    </div>

    <!-- Módulos -->
    <div class="flex items-center gap-2 p-1.5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm w-fit">
        <button @click="mainTab = 'plantel'" :class="mainTab === 'plantel' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 ring-1 ring-primary-100 dark:ring-primary-900/50' : 'text-gray-500'" class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all">Plantel Reprodutivo</button>
        <button @click="mainTab = 'gestacao'" :class="mainTab === 'gestacao' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 ring-1 ring-primary-100 dark:ring-primary-900/50' : 'text-gray-500'" class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all">Manejo de Gestação</button>
        <button @click="mainTab = 'geral'" :class="mainTab === 'geral' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 ring-1 ring-primary-100 dark:ring-primary-900/50' : 'text-gray-500'" class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all">Geral</button>
    </div>

    <!-- Módulo Plantel -->
    <div x-show="mainTab === 'plantel'" class="space-y-6" x-cloak>
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
            <button @click="subTabPlantel = 'metas'" :class="subTabPlantel === 'metas' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-500'" class="px-5 py-2 text-xs font-bold rounded-lg transition-all uppercase">Metas</button>
            <button @click="subTabPlantel = 'criterios'" :class="subTabPlantel === 'criterios' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-500'" class="px-5 py-2 text-xs font-bold rounded-lg transition-all uppercase">Critérios</button>
        </div>

        <div x-show="subTabPlantel === 'metas'" class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-cloak>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold">Entrada e Manutenção</h3></div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Peso Leitoa</label><input type="number" x-model="metas.meta_entrada_peso_leitoa" class="w-full border-gray-200 rounded-xl text-sm"></div>
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Peso Matriz</label><input type="number" x-model="metas.meta_entrada_peso_matriz" class="w-full border-gray-200 rounded-xl text-sm"></div>
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Peso Macho</label><input type="number" x-model="metas.meta_entrada_peso_macho" class="w-full border-gray-200 rounded-xl text-sm"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pb-4 border-b">
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Reposição Anual (%)</label><input type="number" x-model="metas.meta_manutencao_reposicao" class="w-full border-gray-200 rounded-xl text-sm"></div>
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Descarte (%)</label><input type="number" x-model="metas.meta_manutencao_descarte_matrizes" class="w-full border-gray-200 rounded-xl text-sm"></div>
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Mortalidade (%)</label><input type="number" step="0.01" x-model="metas.meta_manutencao_mortalidade_matrizes" class="w-full border-gray-200 rounded-xl text-sm"></div>
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Perdas Pré-Cob. (%)</label><input type="number" x-model="metas.meta_manutencao_perdas_leitoas_pre_cobertura" class="w-full border-gray-200 rounded-xl text-sm"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Meta Estoque Matrizes</label><input type="number" x-model="metas.meta_plantel_estoque_matrizes" class="w-full border-gray-200 rounded-xl text-sm" placeholder="100"></div>
                        <div><label class="block text-[10px] font-bold text-gray-400 uppercase">Meta Estoque Leitoas</label><input type="number" x-model="metas.meta_plantel_estoque_leitoas" class="w-full border-gray-200 rounded-xl text-sm" placeholder="50"></div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold">Retenção (Curva Sobrevivência)</h3></div>
                <div class="p-6">
                    <template x-for="i in 10" :key="i">
                        <div class="flex items-center justify-between p-1 text-xs border-b border-gray-50">
                            <span x-text="'Após ' + i + 'º parto'"></span>
                            <div class="flex items-center"><input type="number" x-model="metas['meta_retencao_pos_p' + i]" class="w-12 text-right border-none bg-transparent"> %</div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="subTabPlantel === 'criterios'" class="bg-white dark:bg-gray-900 rounded-2xl p-8 border border-gray-100 subtab-box" x-cloak>
             <div class="flex gap-4 mb-6 border-b pb-2 overflow-x-auto">
                <button @click="ruleTabPlantel = 'leitoa'" :class="ruleTabPlantel === 'leitoa' ? 'text-primary-600 font-bold' : 'text-gray-400'" class="text-xs uppercase whitespace-nowrap">Entrada Leitoa</button>
                <button @click="ruleTabPlantel = 'matriz'" :class="ruleTabPlantel === 'matriz' ? 'text-primary-600 font-bold' : 'text-gray-400'" class="text-xs uppercase whitespace-nowrap">Entrada Matriz</button>
                <button @click="ruleTabPlantel = 'cio_inc'" :class="ruleTabPlantel === 'cio_inc' ? 'text-primary-600 font-bold' : 'text-gray-400'" class="text-xs uppercase whitespace-nowrap">Cios / Inc</button>
             </div>
             <div x-show="ruleTabPlantel === 'leitoa'" class="grid grid-cols-4 gap-4" x-cloak>
                 <div><span class="text-[10px] font-bold text-gray-400 block mb-1">PESO MÍN/MÁX</span><div class="flex gap-1"><input type="number" x-model="metas.criterio_leitoa_peso_min" class="w-full text-xs border-gray-200 p-1 rounded-lg"><input type="number" x-model="metas.criterio_leitoa_peso_max" class="w-full text-xs border-gray-200 p-1 rounded-lg"></div></div>
                 <div><span class="text-[10px] font-bold text-gray-400 block mb-1">IDADE MÍN/MÁX</span><div class="flex gap-1"><input type="number" x-model="metas.criterio_leitoa_idade_min" class="w-full text-xs border-gray-200 p-1 rounded-lg"><input type="number" x-model="metas.criterio_leitoa_idade_max" class="w-full text-xs border-gray-200 p-1 rounded-lg"></div></div>
             </div>
             <!-- ... Matriz, Cio_Inc ... -->
             <div x-show="ruleTabPlantel === 'cio_inc'" class="space-y-4" x-cloak>
                 <div><span class="text-[10px] font-bold text-gray-400 block mb-1">DIAS CIO LEITOA</span><input type="number" x-model="metas.criterio_dias_cio_leitoa" class="w-full text-xs border-gray-200 p-1 rounded-lg" placeholder="21"></div>
             </div>
        </div>
    </div>

    <!-- Módulo Gestação -->
    <div x-show="mainTab === 'gestacao'" class="space-y-6" x-cloak>
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
            <button @click="subTabGestacao = 'metas'" :class="subTabGestacao === 'metas' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-500'" class="px-5 py-2 text-xs font-bold rounded-lg transition-all uppercase">Metas</button>
            <button @click="subTabGestacao = 'criterios'" :class="subTabGestacao === 'criterios' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-500'" class="px-5 py-2 text-xs font-bold rounded-lg transition-all uppercase">Critérios</button>
        </div>

        <div x-show="subTabGestacao === 'metas'" class="space-y-6" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Metas Cobertura -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold text-gray-900">Metas de Cobertura</h3></div>
                    <div class="p-6 space-y-4">
                        <div class="pb-3 border-b border-gray-50">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Coberturas p/ semana (Estimado)</label>
                            <input type="text" disabled class="w-full border-gray-200 rounded-xl text-sm bg-gray-100 text-gray-500 font-bold" value="33 (Cálculo Automático)">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Montas/Ciclo</label><input type="number" x-model="metas.meta_gestacao_montas_por_cobertura" class="w-full border-gray-200 rounded-xl text-sm" placeholder="3"></div>
                            <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Meses/S/D Gest.</label><input type="number" x-model="metas.meta_gestacao_periodo_gestacao" class="w-full border-gray-200 rounded-xl text-sm" placeholder="114"></div>
                        </div>
                        <div><label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Intervalo Desmame-Cob. (Dias)</label><input type="number" x-model="metas.meta_gestacao_intervalo_desmame_cobertura" class="w-full border-gray-200 rounded-xl text-sm" placeholder="7"></div>
                    </div>
                </div>

                <!-- Metas Perda -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold text-gray-900">Metas de Perda Produtiva</h3></div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-red-50 rounded-xl border border-red-100"><label class="block text-[10px] font-bold text-red-600 uppercase mb-1">Repetição Cio (%)</label><input type="number" x-model="metas.meta_gestacao_repeticao_cio" class="w-full bg-white border-none rounded-lg text-sm"></div>
                            <div class="p-3 bg-red-50 rounded-xl border border-red-100"><label class="block text-[10px] font-bold text-red-600 uppercase mb-1">Aborto (%)</label><input type="number" x-model="metas.meta_gestacao_aborto" class="w-full bg-white border-none rounded-lg text-sm"></div>
                            <div class="p-3 bg-red-50 rounded-xl border border-red-100"><label class="block text-[10px] font-bold text-red-600 uppercase mb-1">Falsa Prenhez (%)</label><input type="number" x-model="metas.meta_gestacao_falsa_prenhez" class="w-full bg-white border-none rounded-lg text-sm"></div>
                            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100"><label class="block text-[10px] font-bold text-emerald-600 uppercase mb-1">Taxa Parição (%)</label><input type="number" x-model="metas.gestacao_meta_taxa_paricao" class="w-full bg-white border-none rounded-lg text-sm"></div>
                        </div>
                    </div>
                </div>

                <!-- Metas Eficiência -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold text-gray-900">Desempenho e Eficiência</h3></div>
                    <div class="p-6 space-y-6">
                        <div class="bg-primary-50 p-4 rounded-2xl border border-primary-100">
                             <label class="block text-xs font-bold text-primary-700 uppercase mb-1">Partos / Fêmea / Ano</label>
                             <input type="number" step="0.01" x-model="metas.meta_gestacao_partos_femea_ano" class="w-full border-gray-200 rounded-xl text-xl font-bold bg-white text-primary-700">
                        </div>
                        <div class="bg-amber-50 p-4 rounded-2xl border border-amber-100">
                             <label class="block text-xs font-bold text-amber-700 uppercase mb-1">Dias Não Produtivos (DNP)</label>
                             <input type="number" x-model="metas.meta_produtividade_dias_nao_produtivos" class="w-full border-gray-200 rounded-xl text-xl font-bold bg-white text-amber-700">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="subTabGestacao === 'criterios'" class="space-y-6" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                 <!-- Regras de Cobertura -->
                 <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold text-gray-900">Critérios para Cobertura</h3></div>
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><label class="block text-xs font-bold text-gray-400 uppercase mb-1">Duração Máxima Matriz Vazia (Dias)</label><input type="number" x-model="metas.criterio_matriz_vazia_max_dias" class="w-full border-gray-200 rounded-xl text-sm"></div>
                            <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Leitoa Idade Mín/Máx</label><div class="flex gap-1"><input type="number" x-model="metas.criterio_cobertura_leitoa_idade_min" class="w-full text-xs border-gray-200 p-1 rounded-lg"><input type="number" x-model="metas.criterio_cobertura_leitoa_idade_max" class="w-full text-xs border-gray-200 p-1 rounded-lg"></div></div>
                            <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Leitoa Peso Mín/Máx</label><div class="flex gap-1"><input type="number" x-model="metas.criterio_cobertura_leitoa_peso_min" class="w-full text-xs border-gray-200 p-1 rounded-lg"><input type="number" x-model="metas.criterio_cobertura_leitoa_peso_max" class="w-full text-xs border-gray-200 p-1 rounded-lg"></div></div>
                            <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Matriz Peso Mín/Máx</label><div class="flex gap-1"><input type="number" x-model="metas.criterio_cobertura_matriz_peso_min" class="w-full text-xs border-gray-200 p-1 rounded-lg"><input type="number" x-model="metas.criterio_cobertura_matriz_peso_max" class="w-full text-xs border-gray-200 p-1 rounded-lg"></div></div>
                            <div><label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Cobertura Lactante</label><select x-model="metas.criterio_cobertura_lactante_permitida" class="w-full text-xs border-gray-200 rounded-lg"><option value="sim">Permitida</option><option value="nao">Não Permitida</option></select></div>
                        </div>
                    </div>
                 </div>

                 <!-- Regras de Perdas -->
                 <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50"><h3 class="font-bold text-gray-900">Critérios para Perdas (Dias)</h3></div>
                    <div class="p-6 space-y-4">
                        <template x-for="type in ['aborto','repeticao_cio','falsa_gestacao']" :key="type">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100">
                                <span class="text-xs font-bold text-gray-600 uppercase" x-text="type === 'repeticao_cio' ? 'Repetição Cio' : (type === 'falsa_gestacao' ? 'Falsa Gestação' : 'Aborto')"></span>
                                <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400">
                                    MÍN <input type="number" x-model="metas['criterio_perda_' + type + '_dias_min']" class="w-12 text-center py-1 border-gray-200 rounded-lg text-xs bg-white text-gray-900">
                                    MÁX <input type="number" x-model="metas['criterio_perda_' + type + '_dias_max']" class="w-12 text-center py-1 border-gray-200 rounded-lg text-xs bg-white text-gray-900">
                                </div>
                            </div>
                        </template>
                    </div>
                 </div>
            </div>

            <!-- Intervalos entre Eventos -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                 <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-gray-900">Critérios para Validação de Dias entre Eventos</h3>
                    <p class="text-[10px] text-gray-500 mt-1">Regras de consistência temporal entre ocorrências reprodutivas.</p>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php
                        $intervals = [
                            ['label' => 'Repetição Cio → Cobertura', 'key' => 'repeticao_cobertura'],
                            ['label' => 'Aborto → Cobertura', 'key' => 'aborto_cobertura'],
                            ['label' => 'Falsa Prenhez → Cobertura', 'key' => 'falsa_prenhez_cobertura'],
                            ['label' => 'Cobertura → Cobertura', 'key' => 'cobertura_cobertura'],
                            ['label' => 'Parto → Cobertura', 'key' => 'parto_cobertura'],
                            ['label' => 'Lactação → Salto Cio', 'key' => 'lactacao_salto_cio'],
                            ['label' => 'Vazio → Salto → Cobertura', 'key' => 'vazio_salto_cobertura'],
                        ];
                    @endphp
                    @foreach($intervals as $item)
                        <div class="p-4 border border-gray-50 dark:border-gray-800 rounded-2xl">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-3">{{ $item['label'] }}</label>
                            <div class="grid grid-cols-2 gap-3 text-[10px] font-bold uppercase text-gray-400">
                                <div>MÍN <input type="number" x-model="metas.criterio_intervalo_{{ $item['key'] }}_min" class="mt-1 w-full text-xs text-gray-700 bg-gray-50 dark:bg-gray-800/50 border-gray-200 rounded-lg text-center font-bold"></div>
                                <div>MÁX <input type="number" x-model="metas.criterio_intervalo_{{ $item['key'] }}_max" class="mt-1 w-full text-xs text-gray-700 bg-gray-50 dark:bg-gray-800/50 border-gray-200 rounded-lg text-center font-bold"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Módulo Geral -->
    <div x-show="mainTab === 'geral'" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-8 space-y-8 animate-in" x-cloak>
         <div class="grid grid-cols-2 gap-10">
            <div>
                 <h4 class="font-bold flex items-center gap-2 mb-4"><i class="fa-solid fa-calendar-days text-amber-500"></i> Sistema de Calendário</h4>
                 <select x-model="metas.criterio_calendario_tipo" class="w-full border-gray-200 rounded-xl text-sm"><option value="gregoriano">Gregoriano (Real)</option><option value="1000_dias">1000 Dias (PIG)</option></select>
            </div>
            <div>
                 <h4 class="font-bold flex items-center gap-2 mb-4"><i class="fa-solid fa-bell text-primary-500"></i> Validações</h4>
                 <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer"><input type="checkbox" x-model="metas.criterios_enabled" class="w-5 h-5 rounded text-primary-600"> <span class="text-sm font-semibold">Alertas Automáticos Ativados</span></label>
            </div>
         </div>
    </div>

</div>
@endsection
