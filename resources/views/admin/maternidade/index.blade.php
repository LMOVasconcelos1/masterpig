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
    matrizesAptas: {{ json_encode($matrizesAptas) }},
    femeasLactantes: {{ json_encode($femeasLactantesFull) }},
    morteCausas: {{ json_encode($morteCausas) }},
    partoForm: {
        femea_id: '',
        cobertura_id: '',
        data: '{{ date('Y-m-d') }}'
    },
    morteForm: {
        femea_id: '',
        parto_id: '',
        quantidade: 1,
        disponiveis: 0,
        data: '{{ date('Y-m-d') }}',
        nova_causa_nome: ''
    },
    
    updatePartoPrevisao(femeaId) {
        const selected = this.matrizesAptas.find(m => m.id == femeaId);
        if (selected) {
            this.partoForm.data = selected.previsao_parto;
            this.partoForm.cobertura_id = selected.cobertura_id;
        } else {
            this.partoForm.data = '{{ date('Y-m-d') }}';
            this.partoForm.cobertura_id = '';
        }
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

    async adicionarCausa() {
        if(!this.morteForm.nova_causa_nome) return;
        try {
            const res = await fetch('{{ route('maternidade.causas.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ nome: this.morteForm.nova_causa_nome })
            });
            const data = await res.json();
            this.morteCausas.push(data);
            this.morteForm.causa_id = data.id;
            this.morteForm.nova_causa_nome = '';
            alert('Causa cadastrada com sucesso!');
        } catch(e) { console.error(e); }
    }
}">
    <!-- Header & Topbar -->
    <div>
        <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Maternidade</h2>
                <p class="text-sm text-white">Visão geral e lançamentos operacionais</p>
            </div>
            <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
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
            </nav>
        </div>
    </div>

    <!-- Conteúdo: Visão Geral -->
    <div x-show="tab === 'visao-geral'">
        <!-- Indicadores -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Fêmea</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Lote</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Localização</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Idade Leitões</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Previsão Desmame</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Problema</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-600 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($inconsistencias as $inc)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-semibold text-navy-900">{{ $inc['femea'] }}</td>
                            <td class="px-6 py-4 text-sm text-text-secondary">{{ $inc['lote'] }}</td>
                            <td class="px-6 py-4 text-sm text-text-secondary">{{ $inc['localizacao'] }}</td>
                            <td class="px-6 py-4 text-sm text-text-secondary">{{ $inc['idade_leitoes'] }} dias</td>
                            <td class="px-6 py-4 text-sm text-text-secondary">{{ $inc['previsao_desmame'] }}</td>
                            <td class="px-6 py-4 text-sm text-danger-text font-semibold">{{ $inc['problema'] }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button @click="selectedPartoId = {{ $inc['parto_id'] }}; showDesmameModal = true" 
                                    class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                                    Cadastrar Desmame
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-text-muted italic">
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
        <div class="flex items-center gap-4 border-b border-border">
            <button @click="subTab = 'partos'" 
                :class="subTab === 'partos' ? 'tab-active px-4 py-2' : 'border-transparent text-text-secondary'"
                class="pb-2 px-1 text-sm font-bold transition-all border-b-2">
                Partos
            </button>
            <button @click="subTab = 'desmames'" 
                :class="subTab === 'desmames' ? 'tab-active px-4 py-2' : 'border-transparent text-text-secondary'"
                class="pb-2 px-1 text-sm font-bold transition-all border-b-2">
                Desmames
            </button>
            <button @click="subTab = 'mortes'" 
                :class="subTab === 'mortes' ? 'tab-active px-4 py-2' : 'border-transparent text-text-secondary'"
                class="pb-2 px-1 text-sm font-bold transition-all border-b-2">
                Morte de Leitão
            </button>
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
                                <td class="px-6 py-4 text-sm text-navy-900">{{ \Carbon\Carbon::parse($parto->data)->format('d/m/Y') }}</td>
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
        <div x-show="subTab === 'desmames'" class="card p-8 text-center">
            <i class="fa-solid fa-hourglass-half text-text-muted text-4xl mb-4"></i>
            <p class="text-text-secondary text-sm italic">Módulo de listagem de desmames em desenvolvimento.</p>
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
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Funcionário</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($mortesLeitaoRegistradas as $morte)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-navy-900">
                                    {{ \Carbon\Carbon::parse($morte->data)->format('d/m/Y') }} {{ $morte->hora ? \Carbon\Carbon::parse($morte->hora)->format('H:i') : '' }}
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
    
    <!-- Modal: Morte de Leitão -->
    <div x-show="showMorteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showMorteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Morte de Leitão</h3>
                    <button @click="showMorteModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('maternidade.mortes.store') }}" method="POST" class="p-6 space-y-4">
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
                            <input type="date" name="data" required x-model="morteForm.data" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Funcionário</label>
                            <input type="text" name="funcionario" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Nome...">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Causa da Morte</label>
                        <div class="flex gap-2">
                            <select name="causa_id" x-model="morteForm.causa_id" class="flex-1 rounded-lg border-gray-300 text-sm">
                                <option value="">Selecione uma causa</option>
                                <template x-for="c in morteCausas" :key="c.id">
                                    <option :value="c.id" x-text="c.nome"></option>
                                </template>
                            </select>
                            <input type="text" x-model="morteForm.nova_causa_nome" placeholder="Nova causa..." class="w-32 rounded-lg border-gray-300 text-sm">
                            <button type="button" @click="adicionarCausa()" class="p-2 bg-gray-100 rounded-lg hover:bg-gray-200"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showMorteModal = false" class="px-4 py-2 text-sm font-medium text-gray-500">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-lg">Salvar Morte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Parto -->
    <div x-show="showPartoModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showPartoModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Parto</h3>
                    <button @click="showPartoModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('maternidade.partos.store') }}" method="POST" class="p-6 space-y-4 text-gray-800 dark:text-gray-200">
                    @csrf
                    <input type="hidden" name="cobertura_id" x-model="partoForm.cobertura_id">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fêmea (Matriz) *</label>
                        <select name="femea_id" required x-model="partoForm.femea_id" @change="updatePartoPrevisao($event.target.value)" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                            <option value="">Selecione a fêmea</option>
                            <template x-for="f in matrizesAptas" :key="f.id">
                                <option :value="f.id" x-text="f.identificacao"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Lote</label>
                        <input type="text" name="lote" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm" placeholder="Opcional...">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Data do Parto *</label>
                            <input type="date" name="data" required x-model="partoForm.data" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Hora Início</label>
                            <input type="time" name="hora_inicio" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Vivos</label>
                            <input type="number" name="total_vivos" value="0" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Mortos</label>
                            <input type="number" name="total_mortos" value="0" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Mumificados</label>
                            <input type="number" name="total_mumificados" value="0" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showPartoModal = false" class="px-4 py-2 text-sm font-medium text-gray-500">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Salvar Parto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Desmame -->
    <div x-show="showDesmameModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/50 dark:bg-black/60" @click="showDesmameModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center text-gray-800 dark:text-gray-200">
                    <h3 class="text-lg font-bold">Registrar Desmame</h3>
                    <button @click="showDesmameModal = false"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form action="{{ route('maternidade.desmames.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="parto_id" :value="selectedPartoId">

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
