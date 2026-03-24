@extends('layouts.dashboard')

@section('title', 'Ficha da Fêmea - ' . $femea->id_primaria)

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-12">
    <!-- Header com Ações Rápidas -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center text-primary-600 shadow-sm">
                <i class="fa-solid fa-venus text-3xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $femea->id_primaria }}</h1>
                <p class="text-sm text-gray-500">{{ $femea->id_secundaria ? 'ID Secundária: ' . $femea->id_secundaria : 'Sem ID Secundária' }}</p>
            </div>
            <div class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold uppercase rounded-full">
                {{ $femea->tipo_compra }}
            </div>
            @if(isset($mov) && !empty($mov['status']))
                <div class="px-3 py-1 text-xs font-bold uppercase rounded-full {{ $mov['status'] === 'Ativo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $mov['status'] }}{{ !empty($mov['acao']) ? ' - ' . $mov['acao'] : '' }}{{ !empty($mov['data']) ? ' (' . $mov['data'] . ')' : '' }}
                </div>
            @endif
            @if($idadeDias)
                <div class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold uppercase rounded-full">
                    {{ $idadeDias }} Dias de Vida
                </div>
            @endif
            @if(isset($calendarType) && $calendarType === '1000_dias' && isset($diasNoCiclo))
                <div class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold uppercase rounded-full">
                    Dia {{ $diasNoCiclo }} do Ciclo (1000 Dias)
                </div>
            @endif
            @if($tempoGranjaDias)
                <div class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold uppercase rounded-full">
                    {{ $tempoGranjaDias }} Dias na Granja
                </div>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.plantel.femeas.index', [], false) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                <i class="fa-solid fa-list mr-2"></i> Fêmeas
            </a>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
            </a>
            <button class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-colors shadow-sm">
                <i class="fa-solid fa-print mr-2"></i> Imprimir Ficha
            </button>
        </div>
    </div>

    <!-- Grid Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Coluna da Esquerda: Dados Básicos e Localização -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Card de Dados -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Dados da Fêmea</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Raça</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $femea->raca?->nome ?? 'Não informada' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Nascimento</span>
                        <span class="text-sm font-semibold text-gray-900">{{ \App\Services\PigCycleService::formatDisplayDate($femea->data_nascimento) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Compra</span>
                        <span class="text-sm font-semibold text-gray-900">{{ \App\Services\PigCycleService::formatDisplayDate($femea->data_compra) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Fornecedor</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $femea->fornecedor?->nome ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Localização</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $femea->localizacao ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Baia</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $femea->baia ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Card de Peso e Valor -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-50 rounded-xl text-orange-600">
                            <i class="fa-solid fa-weight-hanging text-lg"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-bold">Peso Compra</p>
                            <p class="text-sm font-bold text-gray-900">{{ $femea->peso_compra ? number_format($femea->peso_compra, 1) . 'kg' : '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-50 rounded-xl text-green-600">
                            <i class="fa-solid fa-dollar-sign text-lg"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-bold">Valor</p>
                            <p class="text-sm font-bold text-gray-900">{{ $femea->valor_compra ? 'R$' . number_format($femea->valor_compra, 0, ',', '.') : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de Ciclo (Novo) -->
            @if(!empty($alerts))
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 space-y-3">
                <div class="flex items-center gap-2 text-amber-800 font-bold text-xs uppercase tracking-wider">
                    <i class="fa-solid fa-bell"></i>
                    Alertas do Ciclo
                </div>
                <div class="space-y-2">
                    @foreach($alerts as $alert)
                    <div class="flex items-start gap-3 text-sm text-amber-900 bg-white/50 p-3 rounded-xl border border-amber-100">
                        <i class="fa-solid fa-circle-info mt-0.5 text-amber-500"></i>
                        <span>{{ $alert }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Fases do Ciclo (Novo) -->
            @if($cycle)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Status Reprodutivo</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-12 bg-primary-500 rounded-full"></div>
                        <div>
                            <p class="text-[10px] text-primary-400 font-bold uppercase">Fase Atual</p>
                            <p class="text-base font-bold text-gray-900">{{ $cycle['currentPhaseLabel'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-2 h-12 bg-green-400 rounded-full"></div>
                        <div>
                            <p class="text-[10px] text-green-500 font-bold uppercase">Próxima Fase</p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-gray-800">{{ $cycle['nextPhaseLabel'] }}</p>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ $cycle['displayPrevistaEm'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Card de Eventos Reprodutivos -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Ciclo Reprodutivo</h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-y-4 gap-x-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-pink-50 flex items-center justify-center text-pink-600">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Cios</p>
                            <p class="text-lg font-bold text-gray-900">{{ $resumoEventos['cios'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fa-solid fa-code-merge"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Coberturas</p>
                            <p class="text-lg font-bold text-gray-900">{{ $resumoEventos['coberturas'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600">
                            <i class="fa-solid fa-forward"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Salta Cios</p>
                            <p class="text-lg font-bold text-gray-900">{{ $resumoEventos['salta_cios'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Perdas</p>
                            <p class="text-lg font-bold text-gray-900">{{ $resumoEventos['perdas'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita: Performance e Gráficos -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Resumo de Performance vs Metas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center">
                    <span class="text-xs font-bold text-gray-400 uppercase mb-2">Média Vivos</span>
                    <span class="text-3xl font-black text-primary-600">{{ count($performance) > 0 ? number_format($performance->avg('total_vivos'), 1) : '-' }}</span>
                    <span class="text-[10px] text-gray-400 mt-1">Meta: {{ $metas['total_vivos'] }}</span>
                    <div class="w-full h-1.5 bg-gray-100 rounded-full mt-4 overflow-hidden">
                        @php $percent = count($performance) > 0 ? min(($performance->avg('total_vivos') / $metas['total_vivos']) * 100, 100) : 0; @endphp
                        <div class="h-full bg-primary-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center">
                    <span class="text-xs font-bold text-gray-400 uppercase mb-2">Partos</span>
                    <span class="text-3xl font-black text-blue-600">{{ count($performance) }}</span>
                    <span class="text-[10px] text-gray-400 mt-1">Ciclos na Granja</span>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center">
                    <span class="text-xs font-bold text-gray-400 uppercase mb-2">Sobrevivência</span>
                    @php 
                        $totalVivos = $performance->sum('total_vivos');
                        $totalDesm = $performance->sum('qtd_desmamados');
                        $percSobrev = $totalVivos > 0 ? ($totalDesm / $totalVivos) * 100 : 0;
                    @endphp
                    <span class="text-3xl font-black text-green-600">{{ $totalVivos > 0 ? number_format($percSobrev, 1) . '%' : '-' }}</span>
                    <span class="text-[10px] text-gray-400 mt-1">Nascidos vs Desmamados</span>
                </div>
            </div>

            <!-- Gráfico de Evolução de Partos -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Evolução de Performance</h3>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-primary-500 rounded-full"></span>
                            <span class="text-xs text-gray-500">Vivos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
                            <span class="text-xs text-gray-500">Meta</span>
                        </div>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <!-- Histórico de Eventos -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Histórico de Partos e Desmames</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Data Parto</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Vivos</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Mortos</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Mumif.</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Data Desmame</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-center">Desm.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($performance as $p)
                            <tr class="hover:bg-gray-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ \App\Services\PigCycleService::formatDisplayDate(\Carbon\Carbon::parse($p->data_parto)) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 text-center font-bold">{{ $p->total_vivos }}</td>
                                <td class="px-6 py-4 text-sm text-red-500 text-center">{{ $p->total_mortos }}</td>
                                <td class="px-6 py-4 text-sm text-orange-500 text-center">{{ $p->total_mumificados }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ \App\Services\PigCycleService::formatDisplayDate($p->data_desmame ? \Carbon\Carbon::parse($p->data_desmame) : null) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-green-600 text-center font-bold">{{ $p->qtd_desmamados ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic text-sm">
                                    Nenhum histórico de parto registrado para esta fêmea.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para o Gráfico -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        const labels = {!! json_encode($performance->map(fn($p) => \Carbon\Carbon::parse($p->data_parto)->format('d/m'))) !!};
        const dataVivos = {!! json_encode($performance->pluck('total_vivos')) !!};
        const metaVivos = Array(labels.length).fill({{ $metas['total_vivos'] }});
        const mediaPlantel = Array(labels.length).fill({{ $mediaPlantel['total_vivos'] }});

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Nascidos Vivos',
                        data: dataVivos,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#6366f1',
                        pointRadius: 4
                    },
                    {
                        label: 'Meta Granja',
                        data: metaVivos,
                        borderColor: '#d1d5db',
                        borderDash: [5, 5],
                        borderWidth: 2,
                        fill: false,
                        pointRadius: 0
                    },
                    {
                        label: 'Média Plantel',
                        data: mediaPlantel,
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderDash: [2, 2],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1f2937',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#f3f4f6'
                        },
                        ticks: {
                            stepSize: 2
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
