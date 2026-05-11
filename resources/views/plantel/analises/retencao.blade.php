@extends('layouts.dashboard')

@section('title', 'Retenção de Fêmeas')

@section('content')
<div x-data="{
    retencaoLoading: false,
    retencaoError: '',
    retencaoData: null,
    retencaoFiltroRaca: '',
    retencaoFiltroTipo: 'leitoas',
    retencaoDataInicial: '',
    retencaoDataFinal: '',
    racasRetencao: [],

    normalizeDateInput(value) {
        if (!value) return '';
        let v = value.replace(/\\D/g, '');
        if (v.length > 8) v = v.slice(0, 8);
        if (v.length > 4) v = v.replace(/^(\\d{2})(\\d{2})(\\d{0,4}).*/, '$1/$2/$3');
        else if (v.length > 2) v = v.replace(/^(\\d{2})(\\d{0,2}).*/, '$1/$2');
        return v;
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
}" class="space-y-6">

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
                        <input type="text" x-model="retencaoDataInicial" @input="retencaoDataInicial = normalizeDateInput($event.target.value)" @focus="loadRacasRetencao()" class="block w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 shadow-sm hover:border-emerald-300" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Final</label>
                        <input type="text" x-model="retencaoDataFinal" @input="retencaoDataFinal = normalizeDateInput($event.target.value)" class="block w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 shadow-sm hover:border-emerald-300" placeholder="DD/MM/AAAA" inputmode="numeric" autocomplete="off">
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

            <div x-show="retencaoLoading" class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-emerald-500 text-3xl"></i>
                <div class="text-sm text-gray-500 mt-3">Carregando dados de retenção...</div>
            </div>

            <div x-show="retencaoError" class="bg-red-50 border border-red-100 text-red-700 rounded-xl px-4 py-3 text-sm mb-4" x-text="retencaoError"></div>

            <div x-show="retencaoData && !retencaoLoading">
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
</div>
@endsection

