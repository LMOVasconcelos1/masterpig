@extends('layouts.dashboard')

@section('title', 'Ficha do Lote - Terminação')

@section('content')
<div x-data="{
    tab: 'desempenho',
    query: {{ json_encode($lote['nome'] ?? '', JSON_UNESCAPED_UNICODE) }},
    lotes: {{ json_encode($lotes ?? [], JSON_UNESCAPED_UNICODE) }},
    baseUrl: {{ json_encode(url('/terminacao/lotes', [], false), JSON_UNESCAPED_UNICODE) }},
    go() {
        const q = String(this.query || '').trim().toLowerCase();
        if (!q) return;
        const exact = this.lotes.find(l => String(l?.nome || '').trim().toLowerCase() === q);
        const match = exact || this.lotes.find(l => String(l?.nome || '').trim().toLowerCase().includes(q));
        if (!match || !match.id) return;
        window.location.href = `${this.baseUrl}/${match.id}`;
    }
}" class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50/50 via-amber-50/30 to-orange-100/20">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-semibold text-gray-900">Ficha do lote · Terminação</div>
                    <div class="text-xs text-gray-500 mt-1">Acompanhamento de peso, ganho diário, vendas para abate e histórico completo.</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if(($lote['situacao'] ?? '') === 'aberto')
                        <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.lotes.fechar', $lote['id'] ?? 0) }}" onsubmit="return confirm('Tem certeza que deseja fechar este lote?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-amber-300 bg-amber-50 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                                <i class="fa-solid fa-flag-checkered text-xs"></i> Fechar Lote
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('terminacao', [], false) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-8 min-w-0">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-cow text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="text-lg font-bold text-gray-900">{{ $lote['nome'] ?? '-' }}</div>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-amber-100 text-amber-800">Terminação</span>
                                @if(($lote['situacao'] ?? '') === 'aberto')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Aberto
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600">
                                        <span class="w-2 h-2 rounded-full bg-gray-400"></span> Fechado
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                @if(!empty($lote['origem']))
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fa-solid fa-arrow-right-from-bracket text-gray-400"></i> Origem: <b class="text-gray-700">{{ strtoupper($lote['origem']) }}</b>
                                    </span>
                                @endif
                                @if(!empty($lote['observacoes'] ?? ''))
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fa-solid fa-sticky-note text-gray-400"></i>
                                        {{ \Illuminate\Support\Str::limit($lote['observacoes'], 120) }}
                                    </span>
                                @endif
                                @if(empty($lote['observacoes'] ?? '') && empty($lote['caracteristicas'] ?? ''))
                                    <span>-</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 text-sm">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Data entrada</div>
                            <div class="mt-1 font-bold text-gray-900">{{ $resumo['data_abertura'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Previsão fechamento</div>
                            <div class="mt-1 font-bold text-gray-900">{{ $resumo['previsao_fechamento'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Localização</div>
                            <div class="mt-1 font-bold text-gray-900 truncate" title="{{ $resumo['localizacao'] ?? '-' }}">{{ $resumo['localizacao'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Saldo animais</div>
                            <div class="mt-1 font-bold text-emerald-700 text-lg">{{ (int) ($resumo['saldo_animais'] ?? 0) }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gradient-to-br from-amber-50 to-orange-50 px-4 py-3">
                            <div class="text-[11px] text-amber-700 uppercase tracking-wider font-semibold">Ciclo</div>
                            <div class="mt-1">
                                <div class="w-full bg-amber-200 rounded-full h-2.5 mb-1">
                                    <div class="bg-amber-600 h-2.5 rounded-full transition-all duration-700" style="width: {{ (int)($resumo['progresso_pct'] ?? 0) }}%"></div>
                                </div>
                                <div class="text-[11px] font-bold text-amber-900">{{ (int)($resumo['progresso_pct'] ?? 0) }}% concluído</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pesquisa por lote</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" list="lotes-list" x-model="query" @keydown.enter.prevent="go()" @change="go()"
                               class="block w-full pl-10 pr-10 py-3 text-sm border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 shadow-sm hover:border-amber-300 hover:shadow-md"
                               placeholder="Digite o nome do lote">
                        <datalist id="lotes-list">
                            <template x-for="l in lotes" :key="`l-${l.id}`">
                                <option :value="l.nome"></option>
                            </template>
                        </datalist>
                        <button type="button" @click="go()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-amber-600">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 overflow-x-auto">
                <nav class="flex flex-nowrap gap-6 text-sm font-semibold text-gray-600 whitespace-nowrap min-w-max">
                    <button type="button" @click="tab = 'desempenho'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'desempenho' ? 'border-amber-600 text-amber-700' : 'border-transparent hover:text-gray-800'">
                        <i class="fa-solid fa-chart-line mr-1 text-xs opacity-70"></i> Desempenho
                    </button>
                    <button type="button" @click="tab = 'pesos'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'pesos' ? 'border-amber-600 text-amber-700' : 'border-transparent hover:text-gray-800'">
                        <i class="fa-solid fa-scale-balanced mr-1 text-xs opacity-70"></i> Curva de Peso
                    </button>
                    <button type="button" @click="tab = 'movimentacoes'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'movimentacoes' ? 'border-amber-600 text-amber-700' : 'border-transparent hover:text-gray-800'">
                        <i class="fa-solid fa-right-left mr-1 text-xs opacity-70"></i> Movimentações
                    </button>
                    <button type="button" @click="tab = 'mortalidade'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'mortalidade' ? 'border-amber-600 text-amber-700' : 'border-transparent hover:text-gray-800'">
                        <i class="fa-solid fa-skull mr-1 text-xs opacity-70"></i> Mortalidade
                    </button>
                    <button type="button" @click="tab = 'vendas'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'vendas' ? 'border-amber-600 text-amber-700' : 'border-transparent hover:text-gray-800'">
                        <i class="fa-solid fa-truck mr-1 text-xs opacity-70"></i> Vendas / Abate
                    </button>
                </nav>
            </div>

            <div x-show="tab === 'desempenho'" x-cloak>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-white border border-emerald-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-emerald-600 uppercase tracking-wider font-semibold">Total Entradas</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($metricas['entrada'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Dias na fase</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ (int) ($metricas['dias_na_fase'] ?? 0) }}
                            <span class="text-xs font-normal text-gray-400">/ {{ (int) ($metricas['meta_dias_na_fase'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Idade méd. entrada</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            @if(isset($metricas['idade_media_entrada']))
                                {{ number_format((float)$metricas['idade_media_entrada'], 1, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400">dias</span>
                            @else <span class="text-gray-300">-</span> @endif
                        </div>
                    </div>
                    <div class="bg-white border border-amber-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-amber-600 uppercase tracking-wider font-semibold">Peso médio atual</div>
                        <div class="mt-2 text-2xl font-bold text-amber-700">
                            @if(isset($metricas['ultimo_peso_kg']))
                                {{ number_format((float)$metricas['ultimo_peso_kg'], 2, ',', '.') }}
                                <span class="text-xs font-normal text-amber-500">kg</span>
                            @else <span class="text-gray-300">-</span> @endif
                        </div>
                        <div class="text-[11px] text-gray-500 mt-1">Meta: {{ number_format((float)($metricas['meta_peso_abate_kg'] ?? 0), 1, ',', '.') }} kg</div>
                    </div>
                    <div class="bg-white border border-amber-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-amber-600 uppercase tracking-wider font-semibold">GPD médio</div>
                        <div class="mt-2 text-2xl font-bold text-amber-700">
                            @if(isset($metricas['gpd_medio']))
                                {{ number_format((float)$metricas['gpd_medio'], 2, ',', '.') }}
                                <span class="text-xs font-normal text-amber-500">g/dia</span>
                            @else <span class="text-gray-300">-</span> @endif
                        </div>
                    </div>
                    <div class="bg-white border border-rose-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-rose-600 uppercase tracking-wider font-semibold">Mortalidade</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ number_format((float)($metricas['mortalidade_pct'] ?? 0), 2, ',', '.') }}
                            <span class="text-xs font-normal text-gray-400">%</span>
                        </div>
                        <div class="text-[11px] text-gray-500 mt-1">{{ (int)($metricas['saida_mortes'] ?? 0) }} óbito(s)</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-xl p-4">
                        <div class="text-xs text-emerald-700 uppercase tracking-wider font-semibold">Vendas / Abate</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-800">{{ (int) ($metricas['saida_vendas'] ?? 0) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-sky-50 to-sky-100 border border-sky-200 rounded-xl p-4">
                        <div class="text-xs text-sky-700 uppercase tracking-wider font-semibold">Transferências</div>
                        <div class="mt-1 text-2xl font-bold text-sky-800">{{ (int) ($metricas['saida_transferencias'] ?? 0) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-4">
                        <div class="text-xs text-gray-700 uppercase tracking-wider font-semibold">Total Saídas</div>
                        <div class="mt-1 text-2xl font-bold text-gray-800">{{ (int) ($metricas['saida_total'] ?? 0) }}</div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-50 to-orange-100 border border-amber-200 rounded-xl p-4">
                        <div class="text-xs text-amber-700 uppercase tracking-wider font-semibold">Saldo Final</div>
                        <div class="mt-1 text-3xl font-black text-amber-900">{{ (int) ($resumo['saldo_animais'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'pesos'" x-cloak>
                <div class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 bg-gradient-to-r from-amber-50 border-b border-amber-100">
                        <div class="font-bold text-gray-900">Histórico de Pesagens</div>
                        <div class="text-xs text-gray-500 mt-0.5">Acompanhe a evolução do peso médio do lote ao longo do tempo.</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50/70 text-gray-600 uppercase text-[11px] tracking-wider">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold">Data</th>
                                    <th class="px-5 py-3 text-right font-semibold">Peso Médio (kg)</th>
                                    <th class="px-5 py-3 text-right font-semibold">GPD (g/dia)</th>
                                    <th class="px-5 py-3 text-center font-semibold">Amostra</th>
                                    <th class="px-5 py-3 text-left font-semibold">Faixa (kg)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($pesos_historico as $p)
                                    <tr class="hover:bg-amber-50/30 transition-colors">
                                        <td class="px-5 py-3 text-gray-900 font-semibold">{{ App\Services\PigCycleService::formatDisplayDate($p['data'] ?? null) }}</td>
                                        <td class="px-5 py-3 text-right text-amber-700 font-bold">{{ number_format((float)$p['peso_medio_kg'], 2, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-right text-emerald-700 font-semibold">{{ isset($p['gpd_medio']) ? number_format((float)$p['gpd_medio'], 2, ',', '.') : '-' }}</td>
                                        <td class="px-5 py-3 text-center text-gray-600">-</td>
                                        <td class="px-5 py-3 text-left text-gray-500">-</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-gray-400 text-sm">
                                            <i class="fa-solid fa-scale-unbalanced mb-2 text-3xl opacity-40"></i>
                                            <div>Nenhuma pesagem registrada para este lote.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'movimentacoes'" x-cloak>
                <div class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50/70 text-gray-600 uppercase text-[11px] tracking-wider">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold">Data</th>
                                    <th class="px-5 py-3 text-left font-semibold">Tipo</th>
                                    <th class="px-5 py-3 text-right font-semibold">Qtd</th>
                                    <th class="px-5 py-3 text-right font-semibold">Saldo</th>
                                    <th class="px-5 py-3 text-left font-semibold">Local</th>
                                    <th class="px-5 py-3 text-left font-semibold">Observação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($movimentacoes as $m)
                                    @php
                                        $isEntrada = ($m['tipo'] ?? '') === 'entrada';
                                        $isInfo = ($m['tipo'] ?? '') === 'info';
                                    @endphp
                                    <tr class="{{ $isEntrada ? 'bg-emerald-50/30' : ($isInfo ? 'bg-amber-50/20' : 'bg-rose-50/20') }} hover:bg-gray-50">
                                        <td class="px-5 py-3 font-semibold text-gray-800 whitespace-nowrap">{{ $m['data'] ?? '-' }}</td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold uppercase tracking-wider
                                                {{ $isEntrada ? 'bg-emerald-100 text-emerald-800' : ($isInfo ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                                @if($isEntrada) <i class="fa-solid fa-arrow-down text-[10px]"></i>
                                                @elseif($isInfo) <i class="fa-solid fa-info text-[10px]"></i>
                                                @else <i class="fa-solid fa-arrow-up text-[10px]"></i> @endif
                                                {{ $m['tipo_label'] ?? 'Mov' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right font-bold whitespace-nowrap
                                            {{ $isEntrada ? 'text-emerald-700' : ($isInfo ? 'text-amber-700' : 'text-rose-700') }}">
                                            @if($isInfo) <span class="text-gray-400">-</span>
                                            @else {{ $isEntrada ? '+' : '-' }} {{ (int)($m['quantidade'] ?? 0) }} @endif
                                        </td>
                                        <td class="px-5 py-3 text-right font-black text-gray-900 whitespace-nowrap">{{ (int)($m['saldo'] ?? 0) }}</td>
                                        <td class="px-5 py-3 text-gray-600 truncate max-w-[160px]">{{ $m['localizacao'] ?? '-' }}</td>
                                        <td class="px-5 py-3 text-gray-700 max-w-[300px] truncate" title="{{ $m['descricao'] ?? '' }}">{{ $m['descricao'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 text-sm">
                                            <i class="fa-solid fa-inbox mb-2 text-3xl opacity-40"></i>
                                            <div>Sem movimentações registradas.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'mortalidade'" x-cloak>
                <div class="bg-white border border-rose-100 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-bold text-rose-900">Resumo de Mortalidade</div>
                            <div class="text-xs text-gray-500 mt-0.5">Taxa atual: <b class="text-rose-700">{{ number_format((float)($metricas['mortalidade_pct'] ?? 0), 2, ',', '.') }}%</b> · Total: <b>{{ (int)($metricas['saida_mortes'] ?? 0) }} óbito(s)</b></div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        <a href="{{ route('terminacao', [], false) }}#mortes" class="text-amber-600 hover:text-amber-700 underline underline-offset-2">Ver detalhes de cada morte na tela principal</a>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'vendas'" x-cloak>
                <div class="bg-white border border-emerald-100 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-bold text-emerald-900">Resumo de Vendas / Abate</div>
                            <div class="text-xs text-gray-500 mt-0.5">Total vendido deste lote: <b class="text-emerald-700">{{ (int)($metricas['saida_vendas'] ?? 0) }} cabeça(s)</b></div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        <a href="{{ route('terminacao', [], false) }}#vendas" class="text-amber-600 hover:text-amber-700 underline underline-offset-2">Ver cada venda individual na tela principal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
