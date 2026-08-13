@extends('layouts.dashboard')

@section('title', 'Ficha do lote')

@section('content')
<div x-data="{
    tab: 'desempenho',
    query: {{ json_encode($lote['nome'] ?? '', JSON_UNESCAPED_UNICODE) }},
    lotes: {{ json_encode($lotes ?? [], JSON_UNESCAPED_UNICODE) }},
    baseUrl: {{ json_encode(url('/creche/lotes', [], false), JSON_UNESCAPED_UNICODE) }},
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
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary-50/50 via-primary-50/30 to-primary-100/20">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-semibold text-gray-900">Ficha do lote</div>
                    <div class="text-xs text-gray-500 mt-1">Acompanhamento de indicadores e histórico do lote na creche.</div>
                </div>
                <a href="{{ route('creche', [], false) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Voltar
                </a>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <div class="xl:col-span-8 min-w-0">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-primary-50 border border-primary-100 text-primary-700 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-layer-group text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="text-lg font-bold text-gray-900">{{ $lote['nome'] ?? '-' }}</div>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-primary-100 text-primary-700">Creche</span>
                                @if(($lote['situacao'] ?? '') === 'aberto')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        Aberto
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1 truncate">
                                {{ ($lote['caracteristicas'] ?? '') !== '' ? $lote['caracteristicas'] : '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Data abertura</div>
                            <div class="mt-1 font-bold text-gray-900">{{ $resumo['data_abertura'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Previsão fechamento</div>
                            <div class="mt-1 font-bold text-gray-900">{{ $resumo['previsao_fechamento'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Data média nascimento</div>
                            <div class="mt-1 font-bold text-gray-900">{{ $resumo['data_media_nascimento'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Localização</div>
                            <div class="mt-1 font-bold text-gray-900 truncate" title="{{ $resumo['localizacao'] ?? '-' }}">{{ $resumo['localizacao'] ?? '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Saldo de animais</div>
                            <div class="mt-1 font-bold text-gray-900">{{ (int) ($resumo['saldo_animais'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pesquisa por lote</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               list="lotes-list"
                               x-model="query"
                               @keydown.enter.prevent="go()"
                               @change="go()"
                               class="block w-full pl-10 pr-10 py-3 text-sm border-2 border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200 shadow-sm hover:border-primary-300 hover:shadow-md"
                               placeholder="Digite o nome do lote">
                        <datalist id="lotes-list">
                            <template x-for="l in lotes" :key="`l-${l.id}`">
                                <option :value="l.nome"></option>
                            </template>
                        </datalist>
                        <button type="button" @click="go()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 overflow-x-auto">
                <nav class="flex flex-nowrap gap-6 text-sm font-semibold text-gray-600 whitespace-nowrap min-w-max">
                    <button type="button" @click="tab = 'desempenho'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'desempenho' ? 'border-primary-600 text-primary-700' : 'border-transparent hover:text-gray-800'">Desempenho</button>
                    <button type="button" @click="tab = 'movimentacoes'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'movimentacoes' ? 'border-primary-600 text-primary-700' : 'border-transparent hover:text-gray-800'">Movimentações</button>
                    <button type="button" @click="tab = 'nutricao'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'nutricao' ? 'border-primary-600 text-primary-700' : 'border-transparent hover:text-gray-800'">Nutrição</button>
                    <button type="button" @click="tab = 'mortalidade'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'mortalidade' ? 'border-primary-600 text-primary-700' : 'border-transparent hover:text-gray-800'">Mortalidade</button>
                    <button type="button" @click="tab = 'sanidade'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'sanidade' ? 'border-primary-600 text-primary-700' : 'border-transparent hover:text-gray-800'">Sanidade</button>
                    <button type="button" @click="tab = 'historico'" class="pb-3 border-b-2 transition-colors flex-shrink-0" :class="tab === 'historico' ? 'border-primary-600 text-primary-700' : 'border-transparent hover:text-gray-800'">Histórico</button>
                </nav>
            </div>

            <div x-show="tab === 'desempenho'" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Entrada</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($metricas['entrada'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Idade média de entrada</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ isset($metricas['idade_media_entrada']) ? number_format((float) $metricas['idade_media_entrada'], 2, ',', '.') : '-' }}
                        </div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Peso médio de entrada</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ isset($metricas['peso_medio_entrada']) ? number_format((float) $metricas['peso_medio_entrada'], 2, ',', '.') : '-' }}
                        </div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Consumo ração</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((float) ($metricas['consumo_racao'] ?? 0), 2, ',', '.') }} Kg</div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Consumo ração/cab</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ isset($metricas['consumo_racao_cab']) ? number_format((float) $metricas['consumo_racao_cab'], 2, ',', '.') . ' Kg' : '-' }}
                        </div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Mortalidade</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ number_format((float) ($metricas['mortalidade_pct'] ?? 0), 2, ',', '.') }} %</div>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Dias na fase</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($metricas['dias_na_fase'] ?? 0) }}</div>
                        @if(isset($metricas['meta_dias_na_fase']))
                            <div class="mt-1 text-xs text-gray-500">Meta: {{ number_format((float) $metricas['meta_dias_na_fase'], 2, ',', '.') }}</div>
                        @endif
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Saída</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($metricas['saida'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Idade média de saída</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ isset($metricas['idade_media_saida']) ? number_format((float) $metricas['idade_media_saida'], 2, ',', '.') : '-' }}
                        </div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Peso médio de saída</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ isset($metricas['peso_medio_saida']) ? number_format((float) $metricas['peso_medio_saida'], 2, ',', '.') : '-' }}
                        </div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Peso proj. saída</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ isset($metricas['peso_proj_saida']) ? number_format((float) $metricas['peso_proj_saida'], 2, ',', '.') : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'movimentacoes'" x-cloak>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/50">
                                    <th class="px-6 py-3">Data</th>
                                    <th class="px-6 py-3">Tipo</th>
                                    <th class="px-6 py-3">Quantidade</th>
                                    <th class="px-6 py-3">Peso total</th>
                                    <th class="px-6 py-3">Localização</th>
                                    <th class="px-6 py-3">Descrição</th>
                                    <th class="px-6 py-3">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse(($movimentacoes ?? []) as $m)
                                    <tr class="text-sm text-gray-700 hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap">{{ $m['data'] ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(($m['tipo'] ?? '') === 'entrada')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Entrada</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-100">Saída</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-900">{{ (int) ($m['quantidade'] ?? 0) }}</td>
                                        <td class="px-6 py-4 text-gray-700 whitespace-nowrap">
                                            {{ isset($m['peso_total']) && $m['peso_total'] !== null ? number_format((float) $m['peso_total'], 2, ',', '.') . ' kg' : '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ ($m['localizacao'] ?? '') !== '' ? $m['localizacao'] : '-' }}</td>
                                        <td class="px-6 py-4 text-gray-700 max-w-xl truncate" title="{{ $m['descricao'] ?? '' }}">{{ ($m['descricao'] ?? '') !== '' ? $m['descricao'] : '-' }}</td>
                                        <td class="px-6 py-4 font-semibold text-gray-900">{{ (int) ($m['saldo'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 italic">Nenhuma movimentação registrada para este lote.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'nutricao'" x-cloak class="bg-gray-50 border border-gray-100 rounded-xl p-6 text-sm text-gray-600">
                Em desenvolvimento.
            </div>

            <div x-show="tab === 'mortalidade'" x-cloak class="bg-gray-50 border border-gray-100 rounded-xl p-6 text-sm text-gray-600">
                Em desenvolvimento.
            </div>

            <div x-show="tab === 'sanidade'" x-cloak class="bg-gray-50 border border-gray-100 rounded-xl p-6 text-sm text-gray-600">
                Em desenvolvimento.
            </div>

            <div x-show="tab === 'historico'" x-cloak class="bg-gray-50 border border-gray-100 rounded-xl p-6 text-sm text-gray-600">
                Em desenvolvimento.
            </div>
        </div>
    </div>
</div>
@endsection
