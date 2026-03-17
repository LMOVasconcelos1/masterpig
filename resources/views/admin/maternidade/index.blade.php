@extends('layouts.dashboard')

@section('title', 'Maternidade')

@section('content')
<div x-data="{ 
    tab: 'visao-geral', 
    showPartoModal: false, 
    showDesmameModal: false,
    selectedPartoId: null,
    matrizesAptas: {{ json_encode($matrizesAptas) }},
    partoForm: {
        femea_id: '',
        cobertura_id: '',
        data: '{{ date('Y-m-d') }}'
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
    
    init() {
        // ...
    }
}">
    <!-- Abas -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button @click="tab = 'visao-geral'" 
                :class="tab === 'visao-geral' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Visão Geral
            </button>
            <button @click="tab = 'lancamentos'" 
                :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Lançamentos
            </button>
        </nav>
    </div>

    <!-- Conteúdo: Visão Geral -->
    <div x-show="tab === 'visao-geral'">
        <!-- Indicadores -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Fêmeas Lactantes</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $femeasLactantes }}</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <i class="fa-solid fa-baby-carriage text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Mães de Leite</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $maesLeite }}</p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <i class="fa-solid fa-hand-holding-heart text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inconsistências -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800">Inconsistências da Maternidade</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fêmea</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lote</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Localização</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Idade Leitões</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Previsão Desmame</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Problema</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($inconsistencias as $inc)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $inc['femea'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $inc['lote'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $inc['localizacao'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $inc['idade_leitoes'] }} dias</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $inc['previsao_desmame'] }}</td>
                            <td class="px-6 py-4 text-sm text-red-600 font-medium">{{ $inc['problema'] }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button @click="selectedPartoId = {{ $inc['parto_id'] }}; showDesmameModal = true" 
                                    class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                                    Cadastrar Desmame
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">
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
        <div class="flex justify-start">
            <button @click="showPartoModal = true" 
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fa-solid fa-plus mr-2"></i> Novo Parto
            </button>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-gray-500 text-sm">Selecione uma ação acima para realizar lançamentos operacionais na maternidade.</p>
        </div>
    </div>

    <!-- Modal: Cadastro de Parto -->
    <div x-show="showPartoModal" 
        class="fixed inset-0 z-50 overflow-y-auto" 
        style="display: none;"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPartoModal = false"></div>

            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Registrar Parto</h3>
                    <button @click="showPartoModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('maternidade.partos.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="cobertura_id" x-model="partoForm.cobertura_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fêmea (Matriz) *</label>
                        <select name="femea_id" required x-model="partoForm.femea_id" @change="updatePartoPrevisao($event.target.value)" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Selecione a fêmea</option>
                            <template x-for="f in matrizesAptas" :key="f.id">
                                <option :value="f.id" x-text="f.identificacao"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                        <input type="text" name="lote" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Opcional...">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data do Parto *</label>
                            <input type="date" name="data" required x-model="partoForm.data" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hora Início</label>
                            <input type="time" name="hora_inicio" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vivos</label>
                            <input type="number" name="total_vivos" value="0" min="0" required class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mortos</label>
                            <input type="number" name="total_mortos" value="0" min="0" required class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mumificados</label>
                            <input type="number" name="total_mumificados" value="0" min="0" required class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Opcional..."></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showPartoModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Salvar Parto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cadastro de Desmame -->
    <div x-show="showDesmameModal" 
        class="fixed inset-0 z-50 overflow-y-auto" 
        style="display: none;"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDesmameModal = false"></div>

            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Registrar Desmame</h3>
                    <button @click="showDesmameModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('maternidade.desmames.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="parto_id" :value="selectedPartoId">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Desmame *</label>
                        <input type="date" name="data" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade *</label>
                            <input type="number" name="quantidade" required min="1" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peso Médio (kg)</label>
                            <input type="number" name="peso_medio" step="0.01" min="0" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="observacao" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500 text-sm" placeholder="Opcional..."></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showDesmameModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Salvar Desmame</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
