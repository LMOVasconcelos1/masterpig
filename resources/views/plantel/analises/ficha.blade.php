@extends('layouts.dashboard')

@section('title', 'Ficha da Matriz')

@section('content')
<div x-data="{
    fichaLoading: false,
    fichaError: '',
    fichaFemeas: [],
    fichaSelectedId: '',
    fichaData: null,

    loadFichaFemeas() {
        if (this.fichaFemeas.length > 0) return;
        fetch('/api/plantel/femeas?limit=1000&all=1', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                this.fichaFemeas = Array.isArray(data.items) ? data.items : [];
            })
            .catch(() => { this.fichaFemeas = []; });
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

    init() {
        this.loadFichaFemeas();
    },
}" x-init="init()" class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary-50/50 via-primary-50/30 to-primary-100/20">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-semibold text-gray-900">Ficha da Matriz</div>
                    <div class="text-xs text-gray-500 mt-1">Análise de informações e índices gerais de todos os ciclos reprodutivos.</div>
                </div>
                <a href="{{ url('/dashboard?tab=analise') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Voltar
                </a>
            </div>
        </div>
        <div class="p-6 space-y-6">
            <div class="w-full sm:w-80 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                </div>
                <select x-model="fichaSelectedId" @change="loadFichaData()" @focus="loadFichaFemeas()" class="block w-full pl-10 pr-10 py-3 text-sm border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200 shadow-sm hover:border-primary-300 hover:shadow-md appearance-none cursor-pointer">
                    <option value="">Selecione uma fêmea...</option>
                    <template x-for="f in fichaFemeas" :key="f.id">
                        <option :value="String(f.id)" x-text="f.id_primaria + (f.id_secundaria ? ' (' + f.id_secundaria + ')' : '')"></option>
                    </template>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                </div>
            </div>

            <div x-show="fichaLoading" class="text-center py-4">
                <i class="fa-solid fa-spinner fa-spin text-primary-500 text-2xl"></i>
                <div class="text-sm text-gray-500 mt-2">Carregando dados...</div>
            </div>

            <div x-show="fichaError" class="bg-red-50 border border-red-100 text-red-700 rounded-xl px-4 py-3 text-sm" x-text="fichaError"></div>

            <div x-show="fichaData && !fichaLoading">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-blue-700 uppercase tracking-wider">Dias de Gestação</div>
                                <div class="text-2xl font-bold text-blue-900" x-text="fichaData?.dias_gestacao ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100/50 rounded-xl p-4 border border-pink-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-pink-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-baby"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-pink-700 uppercase tracking-wider">Dias de Lactação</div>
                                <div class="text-2xl font-bold text-pink-900" x-text="fichaData?.dias_lactacao ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100/50 rounded-xl p-4 border border-green-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-egg"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-green-700 uppercase tracking-wider">Nascidos Totais</div>
                                <div class="text-2xl font-bold text-green-900" x-text="fichaData?.nascidos_totais ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-xl p-4 border border-emerald-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-heart-pulse"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Nascidos Vivos</div>
                                <div class="text-2xl font-bold text-emerald-900" x-text="fichaData?.nascidos_vivos ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-xl p-4 border border-amber-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-child"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-amber-700 uppercase tracking-wider">Desmamados</div>
                                <div class="text-2xl font-bold text-amber-900" x-text="fichaData?.desmamados ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-red-100/50 rounded-xl p-4 border border-red-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-red-500 text-white flex items-center justify-center">
                                <i class="fa-solid fa-skull-crossbones"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-red-700 uppercase tracking-wider">Mortalidade</div>
                                <div class="text-2xl font-bold text-red-900" x-text="fichaData?.mortalidade ?? '-'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Informações da Fêmea Selecionada</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">ID Primária:</span>
                            <span class="font-semibold text-gray-900 ml-1" x-text="fichaData?.id_primaria ?? '-'"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">ID Secundária:</span>
                            <span class="font-semibold text-gray-900 ml-1" x-text="fichaData?.id_secundaria ?? '-'"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Total de Ciclos:</span>
                            <span class="font-semibold text-gray-900 ml-1" x-text="fichaData?.total_ciclos ?? '-'"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Status:</span>
                            <span class="font-semibold text-gray-900 ml-1" x-text="fichaData?.status ?? '-'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="!fichaData && !fichaLoading && !fichaError" class="text-center py-8">
                <i class="fa-solid fa-piggy-bank text-4xl text-gray-300 mb-3"></i>
                <div class="text-sm text-gray-500">Selecione uma fêmea para visualizar a ficha completa com índices de desempenho.</div>
            </div>
        </div>
    </div>
</div>
@endsection

