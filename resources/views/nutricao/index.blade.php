@extends('layouts.dashboard')

@section('title', 'Nutrição')

@section('content')
@php
    $stats = $stats ?? ['estoque_kg' => 0, 'valor_estoque' => 0];
    $racoesEstoque = $racoesEstoque ?? [];
    $compras = $compras ?? [];
    $consumos = $consumos ?? [];
    $vendas = $vendas ?? [];
    $acertos = $acertos ?? [];
    $racoes = $racoes ?? [];
    $fornecedores = $fornecedores ?? [];
    $movimentacao = $movimentacao ?? [];
    $ultimoAcerto = $ultimoAcerto ?? null;
@endphp

<div x-data="nutricaoPage()" x-init="init()" class="space-y-6">

    {{-- Toast de feedback --}}
    <div x-show="showToast"
         x-transition:enter="transform ease-out duration-500 transition"
         x-transition:enter-start="translate-y-[-100%] opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white shadow-2xl rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden border-l-4"
         :class="toastType === 'success' ? 'border-green-500' : 'border-red-500'"
         x-cloak>
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <template x-if="toastType === 'success'">
                        <i class="fa-solid fa-circle-check text-green-400 text-xl"></i>
                    </template>
                    <template x-if="toastType === 'error'">
                        <i class="fa-solid fa-circle-xmark text-red-400 text-xl"></i>
                    </template>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900" x-text="toastMessage"></p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="showToast = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cabeçalho --}}
    <div class="rounded-xl shadow-sm p-6" style="background-color: #7c2d12;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-1">Nutrição</h2>
            <p class="text-sm text-amber-100">Gestão de rações, estoque e compras</p>
        </div>
    </div>

    {{-- Linha superior: Resumo + Gráfico + Acerto --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Resumo de estoque --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="space-y-5">
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ração em estoque (KG)</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((float) $stats['estoque_kg'], 2, ',', '.') }}</div>
                </div>
                <div class="border-t border-gray-100 pt-5">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor em estoque (R$)</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((float) $stats['valor_estoque'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Gráfico de movimentação --}}
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="text-center mb-4">
                <h3 class="text-base font-semibold text-gray-800">Movimentação dos últimos 6 meses</h3>
            </div>
            <div class="relative" style="height: 220px;">
                <canvas x-ref="chartCanvas"></canvas>
            </div>
            <div class="flex items-center justify-center gap-4 mt-3 text-xs text-gray-600">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded" style="background-color: #93c5fd;"></span> Entradas
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded" style="background-color: #86efac;"></span> Saldo
                </span>
            </div>
        </div>

        {{-- Acerto de estoque --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Acerto de estoque</h3>
            <p class="text-sm text-gray-500 mb-4">
                Último acerto de estoque realizado em
                <span class="font-medium text-gray-700">{{ $ultimoAcerto ?? '—' }}</span>
            </p>
            <div class="space-y-2">
                <button type="button"
                        @click="resetAcertoForm(); showAcertoModal = true"
                        class="inline-flex items-center text-sm text-amber-700 hover:text-amber-800 font-medium">
                    <i class="fa-solid fa-clipboard-check mr-2"></i> Realizar novo acerto de estoque
                </button>
                <a href="{{ route('admin.racoes.export-pdf', [], false) }}" target="_blank" class="inline-flex items-center text-sm text-amber-700 hover:text-amber-800 font-medium">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Imprimir formulário de acerto de estoque
                </a>
            </div>
        </div>
    </div>

    {{-- Seção: Rações em estoque --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Raçãos em estoque</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-chevron-up"></i>
            </button>
        </div>
        <div class="p-4 sm:p-6">
            {{-- Filtros --}}
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <select class="border border-gray-300 rounded-lg text-sm px-3 py-2 min-w-[180px]">
                    <option>Todos os grupos</option>
                </select>
                <input type="text" placeholder="Pesquisar por ração" class="border border-gray-300 rounded-lg text-sm px-3 py-2 min-w-[200px] flex-1">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-amber-700 text-white text-sm font-semibold rounded-lg hover:bg-amber-800 transition-colors">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i> Filtrar
                </button>
                <button type="button" class="text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-ellipsis"></i>
                </button>
            </div>

            {{-- Tabela desktop --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ração</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Grupo</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Estoque</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Custo (R$)</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Valor total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($racoesEstoque as $r)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $r['codigo'] }} {{ $r['nome'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $r['grupo'] ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $r['estoque'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $r['custo'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $r['valor_total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">Nenhuma ração em estoque.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cards mobile --}}
            <div class="space-y-3 md:hidden">
                @forelse($racoesEstoque as $r)
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate">{{ $r['codigo'] }} {{ $r['nome'] }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $r['grupo'] ?: '-' }}</div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $r['estoque'], 2, ',', '.') }} kg</div>
                                <div class="text-xs text-gray-500">R$ {{ number_format((float) $r['valor_total'], 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">
                        Nenhuma ração em estoque.
                    </div>
                @endforelse
            </div>

            {{-- Paginação --}}
            <div class="flex items-center justify-between mt-5 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-700 text-white text-xs font-bold">1</span>
                </div>
                <div class="flex items-center gap-2">
                    <span>(Total de resultados: {{ count($racoesEstoque) }})</span>
                    <span>LINHAS POR PÁGINA</span>
                    <select class="border border-gray-300 rounded text-xs px-2 py-1">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Seção: Lançamentos > Compras --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-800">Lançamentos</h3>
        </div>
        <div class="p-4 sm:p-6">
            {{-- Tabs de lançamentos --}}
            <div class="flex flex-wrap items-center gap-2 mb-5 border-b border-gray-100">
                <button type="button"
                        @click="lancamentoTab = 'compras'"
                        class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors"
                        :class="lancamentoTab === 'compras' ? 'border-amber-700 text-amber-800' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    <i class="fa-solid fa-cart-shopping mr-2"></i> Compras
                </button>
                <button type="button"
                        @click="lancamentoTab = 'consumos'"
                        class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors"
                        :class="lancamentoTab === 'consumos' ? 'border-amber-700 text-amber-800' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    <i class="fa-solid fa-utensils mr-2"></i> Consumos
                </button>
                <button type="button"
                        @click="lancamentoTab = 'vendas'"
                        class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors"
                        :class="lancamentoTab === 'vendas' ? 'border-amber-700 text-amber-800' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    <i class="fa-solid fa-tag mr-2"></i> Vendas
                </button>
                <button type="button"
                        @click="lancamentoTab = 'acertos'"
                        class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors"
                        :class="lancamentoTab === 'acertos' ? 'border-amber-700 text-amber-800' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    <i class="fa-solid fa-clipboard-check mr-2"></i> Acerto de estoque
                </button>
            </div>

            <div x-show="lancamentoTab === 'compras'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de compras de ração</h4>
                    <button type="button"
                            @click="resetCompraForm(); showCompraModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir compra de ração
                    </button>
                </div>

                {{-- Tabela desktop --}}
                <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ração</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Qtd. (kg)</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Vlr. unit.</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Vlr. total</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Fornecedor</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">NF</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Lote fab.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($compras as $c)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $c['data_compra'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['racao'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['quantidade'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['valor_unitario'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right font-medium">{{ number_format((float) $c['valor_total'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['fornecedor'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['nota_fiscal'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['lote_fabricacao'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 italic">Nenhuma compra registrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Cards mobile --}}
                <div class="space-y-3 md:hidden">
                    @forelse($compras as $c)
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900">{{ $c['racao'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $c['data_compra'] }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $c['quantidade'], 2, ',', '.') }} kg</div>
                                    <div class="text-xs text-gray-500">R$ {{ number_format((float) $c['valor_total'], 2, ',', '.') }}</div>
                                </div>
                            </div>
                            @if($c['fornecedor'] || $c['nota_fiscal'] || $c['lote_fabricacao'])
                                <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-500 space-y-0.5">
                                    @if($c['fornecedor'])<div><span class="font-medium">Fornecedor:</span> {{ $c['fornecedor'] }}</div>@endif
                                    @if($c['nota_fiscal'])<div><span class="font-medium">NF:</span> {{ $c['nota_fiscal'] }}</div>@endif
                                    @if($c['lote_fabricacao'])<div><span class="font-medium">Lote fab.:</span> {{ $c['lote_fabricacao'] }}</div>@endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">
                            Nenhuma compra registrada.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Consumos --}}
            <div x-show="lancamentoTab === 'consumos'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de consumos de ração</h4>
                    <button type="button"
                            @click="resetConsumoForm(); showConsumoModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir consumo de ração
                    </button>
                </div>

                <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ração</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Qtd. (kg)</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Fase</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Lote fab.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($consumos as $c)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $c['data_consumo'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['racao'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['quantidade'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['fase'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['lote'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['lote_fabricacao'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 italic">Nenhum consumo registrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @forelse($consumos as $c)
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900">{{ $c['racao'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $c['data_consumo'] }} @if($c['fase']) · {{ $c['fase'] }}@endif</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $c['quantidade'], 2, ',', '.') }} kg</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">
                            Nenhum consumo registrado.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Vendas --}}
            <div x-show="lancamentoTab === 'vendas'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de vendas de ração</h4>
                    <button type="button"
                            @click="resetVendaForm(); showVendaModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir venda de ração
                    </button>
                </div>

                <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ração</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Qtd. (kg)</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Vlr. unit.</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Vlr. total</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Comprador</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($vendas as $v)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $v['data_venda'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $v['racao'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $v['quantidade'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $v['valor_unitario'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right font-medium">{{ number_format((float) $v['valor_total'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $v['comprador'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 italic">Nenhuma venda registrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @forelse($vendas as $v)
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900">{{ $v['racao'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $v['data_venda'] }} @if($v['comprador']) · {{ $v['comprador'] }}@endif</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $v['quantidade'], 2, ',', '.') }} kg</div>
                                    <div class="text-xs text-gray-500">R$ {{ number_format((float) $v['valor_total'], 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">
                            Nenhuma venda registrada.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Acerto de estoque --}}
            <div x-show="lancamentoTab === 'acertos'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de acertos de estoque</h4>
                    <button type="button"
                            @click="resetAcertoForm(); showAcertoModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir acerto de estoque
                    </button>
                </div>

                <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Ração</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Estoque anterior</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Contagem</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Diferença</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($acertos as $a)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $a['data_acerto'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a['racao'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $a['estoque_anterior'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $a['contagem'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-medium {{ (float) $a['diferenca'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format((float) $a['diferenca'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">Nenhum acerto registrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @forelse($acertos as $a)
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900">{{ $a['racao'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $a['data_acerto'] }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $a['contagem'], 2, ',', '.') }} kg</div>
                                    <div class="text-xs {{ (float) $a['diferenca'] >= 0 ? 'text-green-700' : 'text-red-700' }}">Dif: {{ number_format((float) $a['diferenca'], 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">
                            Nenhum acerto registrado.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Incluir compra de ração --}}
    <div x-show="showCompraModal"
         x-cloak
         class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog" aria-modal="true">
        <div x-show="showCompraModal"
             @click="showCompraModal = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="showCompraModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">

            {{-- Header / drag handle (mobile) --}}
            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>

            <form accept-charset="UTF-8" method="POST" action="{{ route('nutricao.compras.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-wheat-awn text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir compra de ração</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Preencha os dados da compra. O estoque será atualizado automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" @click="showCompraModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Data da compra --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data da compra <span class="text-red-500">*</span></label>
                    <input type="date"
                           name="data_compra"
                           x-model="compraForm.data_compra"
                           required
                           class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('data_compra')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dados da Ração --}}
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados da Ração</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Ração <span class="text-red-500">*</span></label>
                            <select name="racao_id"
                                    x-model="compraForm.racao_id"
                                    required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione a ração...</option>
                                @foreach($racoes as $r)
                                    <option value="{{ $r['id'] }}">{{ $r['codigo'] }} - {{ $r['nome'] }}</option>
                                @endforeach
                            </select>
                            @error('racao_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade (kg) <span class="text-red-500">*</span></label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="quantidade"
                                   x-model="compraForm.quantidade"
                                   @input="calcularTotal()"
                                   required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('quantidade')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor unitário (R$) <span class="text-red-500">*</span></label>
                            <input type="number"
                                   step="0.0001"
                                   min="0"
                                   name="valor_unitario"
                                   x-model="compraForm.valor_unitario"
                                   @input="calcularTotal()"
                                   required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('valor_unitario')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Valor total (R$)</label>
                            <div class="mt-1.5 relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">R$</span>
                                <input type="text"
                                       name="valor_total"
                                       :value="valorTotalFormatado"
                                       readonly
                                       class="w-full border border-gray-200 bg-gray-50 rounded-xl pl-10 pr-3 py-2.5 text-sm font-semibold text-gray-900">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Calculado automaticamente (quantidade × valor unitário).</p>
                            @error('valor_total')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Dados complementares --}}
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados complementares</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nota fiscal</label>
                            <input type="text"
                                   name="nota_fiscal"
                                   x-model="compraForm.nota_fiscal"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('nota_fiscal')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                            <select name="fornecedor_id"
                                    x-model="compraForm.fornecedor_id"
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione...</option>
                                @foreach($fornecedores as $f)
                                    <option value="{{ $f['id'] }}">{{ $f['nome'] }}</option>
                                @endforeach
                            </select>
                            @error('fornecedor_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Lote de fabricação</label>
                            <input type="text"
                                   name="lote_fabricacao"
                                   x-model="compraForm.lote_fabricacao"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('lote_fabricacao')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Botões --}}
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button"
                            @click="showCompraModal = false"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-check mr-2"></i> Salvar compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Incluir consumo de ração --}}
    <div x-show="showConsumoModal"
         x-cloak
         class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog" aria-modal="true">
        <div x-show="showConsumoModal"
             @click="showConsumoModal = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="showConsumoModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">

            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>

            <form accept-charset="UTF-8" method="POST" action="{{ route('nutricao.consumos.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-utensils text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir consumo de ração</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Registre o consumo. O estoque será baixado automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" @click="showConsumoModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data do consumo <span class="text-red-500">*</span></label>
                    <input type="date" name="data_consumo" x-model="consumoForm.data_consumo" required
                           class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('data_consumo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados da Ração</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Ração <span class="text-red-500">*</span></label>
                            <select name="racao_id" x-model="consumoForm.racao_id" required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione a ração...</option>
                                @foreach($racoes as $r)
                                    <option value="{{ $r['id'] }}">{{ $r['codigo'] }} - {{ $r['nome'] }}</option>
                                @endforeach
                            </select>
                            @error('racao_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade (kg) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="quantidade" x-model="consumoForm.quantidade" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('quantidade')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fase <span class="text-red-500">*</span></label>
                            <select name="fase" x-model="consumoForm.fase" required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione a fase...</option>
                                <option value="Gestação">Gestação</option>
                                <option value="Lactação">Lactação</option>
                                <option value="Creche">Creche</option>
                                <option value="Recria">Recria</option>
                                <option value="Terminação">Terminação</option>
                                <option value="Outros">Outros</option>
                            </select>
                            @error('fase')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lote</label>
                            <input type="text" name="lote" x-model="consumoForm.lote"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('lote')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lote de fabricação</label>
                            <input type="text" name="lote_fabricacao" x-model="consumoForm.lote_fabricacao"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('lote_fabricacao')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="showConsumoModal = false"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-check mr-2"></i> Salvar consumo
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Incluir venda de ração --}}
    <div x-show="showVendaModal"
         x-cloak
         class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog" aria-modal="true">
        <div x-show="showVendaModal"
             @click="showVendaModal = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="showVendaModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">

            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>

            <form accept-charset="UTF-8" method="POST" action="{{ route('nutricao.vendas.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-tag text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir venda de ração</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Registre a venda. O estoque será baixado automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" @click="showVendaModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data da venda <span class="text-red-500">*</span></label>
                    <input type="date" name="data_venda" x-model="vendaForm.data_venda" required
                           class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('data_venda')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados da Ração</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Ração <span class="text-red-500">*</span></label>
                            <select name="racao_id" x-model="vendaForm.racao_id" required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione a ração...</option>
                                @foreach($racoes as $r)
                                    <option value="{{ $r['id'] }}">{{ $r['codigo'] }} - {{ $r['nome'] }}</option>
                                @endforeach
                            </select>
                            @error('racao_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade (kg) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="quantidade" x-model="vendaForm.quantidade" @input="calcularTotalVenda()" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('quantidade')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor unitário (R$) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.0001" min="0" name="valor_unitario" x-model="vendaForm.valor_unitario" @input="calcularTotalVenda()" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('valor_unitario')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Valor total (R$)</label>
                            <div class="mt-1.5 relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">R$</span>
                                <input type="text" name="valor_total" :value="valorTotalVendaFormatado" readonly
                                       class="w-full border border-gray-200 bg-gray-50 rounded-xl pl-10 pr-3 py-2.5 text-sm font-semibold text-gray-900">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Calculado automaticamente (quantidade × valor unitário).</p>
                            @error('valor_total')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Comprador</label>
                            <input type="text" name="comprador" x-model="vendaForm.comprador"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('comprador')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="showVendaModal = false"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-check mr-2"></i> Salvar venda
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Incluir acerto de estoque de ração --}}
    <div x-show="showAcertoModal"
         x-cloak
         class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4"
         role="dialog" aria-modal="true">
        <div x-show="showAcertoModal"
             @click="showAcertoModal = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="showAcertoModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-4xl max-h-[92vh] overflow-y-auto">

            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>

            <form accept-charset="UTF-8" method="POST" action="{{ route('nutricao.acertos.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-check text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir acerto de estoque de ração</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Informe a contagem real de cada ração. O estoque será ajustado.</p>
                        </div>
                    </div>
                    <button type="button" @click="showAcertoModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="sm:w-56">
                        <label class="block text-sm font-medium text-gray-700">Data do acerto <span class="text-red-500">*</span></label>
                        <input type="date" name="data_acerto" x-model="acertoForm.data_acerto" required
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        @error('data_acerto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:w-60">
                        <label class="block text-sm font-medium text-gray-700">Ração</label>
                        <select x-model="acertoForm.filterRacao"
                                class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="">Todas as rações</option>
                            @foreach($racoes as $r)
                                <option value="{{ $r['id'] }}">{{ $r['codigo'] }} - {{ $r['nome'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-3">Lista dos itens que deseja acertar estoque</p>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Ração</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Estoque</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Contagem</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Diferença</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wider w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, idx) in acertoItens" :key="idx">
                                    <tr>
                                        <td class="px-4 py-2.5">
                                            <select x-model="item.racao_id"
                                                    @change="item.estoque_atual = estoqueMap[item.racao_id] || 0"
                                                    :name="'itens[' + idx + '][racao_id]'"
                                                    class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                                <option value="">Selecione...</option>
                                                @foreach($racoes as $r)
                                                    <option value="{{ $r['id'] }}">{{ $r['codigo'] }} - {{ $r['nome'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5 text-sm text-gray-500 text-right"
                                            x-text="item.estoque_atual ? Number(item.estoque_atual).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}) : '0,00'"></td>
                                        <td class="px-4 py-2.5">
                                            <input type="number" step="0.01" min="0"
                                                   x-model="item.contagem"
                                                   :name="'itens[' + idx + '][contagem]'"
                                                   placeholder="Informe a contagem *"
                                                   class="w-36 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-medium text-right"
                                            :class="Number(item.diferenca) >= 0 ? 'text-green-700' : 'text-red-700'"
                                            x-text="item.diferenca ? Number(item.diferenca).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}) : '0,00'"></td>
                                        <td class="px-4 py-2.5 text-center">
                                            <button type="button" @click="removerAcertoItem(idx)" class="text-red-500 hover:text-red-700">
                                                <i class="fa-solid fa-circle-minus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="acertoItens.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 italic text-sm">Nenhum item adicionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <button type="button" @click="adicionarAcertoItem()"
                                class="inline-flex items-center px-3 py-2 text-sm font-semibold text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-50 transition-colors">
                            <i class="fa-solid fa-plus mr-2"></i> Adicionar ração
                        </button>
                        @error('itens')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="showAcertoModal = false"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-check mr-2"></i> Salvar acerto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/chart.min.js') }}"></script>
<script>
function nutricaoPage() {
    const racoesMap = @json(collect($racoes)->keyBy('id')->map(fn($r) => ['codigo' => $r['codigo'], 'nome' => $r['nome']])->all());
    const estoqueMap = @json(collect($racoesEstoque)->keyBy('id')->map(fn($r) => $r['estoque'])->all());

    return {
        lancamentoTab: 'compras',
        showCompraModal: false,
        showConsumoModal: false,
        showVendaModal: false,
        showAcertoModal: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        chart: null,
        compraForm: {
            data_compra: '{{ date('Y-m-d') }}',
            racao_id: '',
            quantidade: '',
            valor_unitario: '',
            valor_total: '0.00',
            nota_fiscal: '',
            fornecedor_id: '',
            lote_fabricacao: '',
        },
        consumoForm: {
            data_consumo: '{{ date('Y-m-d') }}',
            racao_id: '',
            quantidade: '',
            fase: '',
            lote: '',
            lote_fabricacao: '',
        },
        vendaForm: {
            data_venda: '{{ date('Y-m-d') }}',
            racao_id: '',
            quantidade: '',
            valor_unitario: '',
            valor_total: '0.00',
            comprador: '',
        },
        acertoForm: {
            data_acerto: '{{ date('Y-m-d') }}',
            filterRacao: '',
        },
        acertoItens: [],

        init() {
            this.renderChart();
            @if(session('success'))
                this.notify('{{ session('success') }}');
            @endif
            @if(session('error'))
                this.notify('{{ session('error') }}', 'error');
            @endif
            @if($errors->any())
                this.notify('Por favor, corrija os erros no formulário.', 'error');
                @if($errors->has('data_consumo') || $errors->has('consumo.*'))
                    this.showConsumoModal = true;
                @elseif($errors->has('data_venda') || $errors->has('venda.*'))
                    this.showVendaModal = true;
                @elseif($errors->has('data_acerto') || $errors->has('itens'))
                    this.showAcertoModal = true;
                @else
                    this.showCompraModal = true;
                @endif
            @endif
        },

        notify(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => { this.showToast = false; }, 4000);
        },

        resetCompraForm() {
            this.compraForm.data_compra = '{{ date('Y-m-d') }}';
            this.compraForm.racao_id = '';
            this.compraForm.quantidade = '';
            this.compraForm.valor_unitario = '';
            this.compraForm.valor_total = '0.00';
            this.compraForm.nota_fiscal = '';
            this.compraForm.fornecedor_id = '';
            this.compraForm.lote_fabricacao = '';
        },

        resetConsumoForm() {
            this.consumoForm.data_consumo = '{{ date('Y-m-d') }}';
            this.consumoForm.racao_id = '';
            this.consumoForm.quantidade = '';
            this.consumoForm.fase = '';
            this.consumoForm.lote = '';
            this.consumoForm.lote_fabricacao = '';
        },

        resetVendaForm() {
            this.vendaForm.data_venda = '{{ date('Y-m-d') }}';
            this.vendaForm.racao_id = '';
            this.vendaForm.quantidade = '';
            this.vendaForm.valor_unitario = '';
            this.vendaForm.valor_total = '0.00';
            this.vendaForm.comprador = '';
        },

        resetAcertoForm() {
            this.acertoForm.data_acerto = '{{ date('Y-m-d') }}';
            this.acertoForm.filterRacao = '';
            this.acertoItens = [];
        },

        get valorTotalCalculado() {
            const qtd = parseFloat(this.compraForm.quantidade) || 0;
            const unit = parseFloat(this.compraForm.valor_unitario) || 0;
            return (qtd * unit).toFixed(2);
        },

        get valorTotalFormatado() {
            return this.valorTotalCalculado;
        },

        calcularTotal() {
            this.compraForm.valor_total = this.valorTotalCalculado;
        },

        get valorTotalVendaCalculado() {
            const qtd = parseFloat(this.vendaForm.quantidade) || 0;
            const unit = parseFloat(this.vendaForm.valor_unitario) || 0;
            return (qtd * unit).toFixed(2);
        },

        get valorTotalVendaFormatado() {
            return this.valorTotalVendaCalculado;
        },

        calcularTotalVenda() {
            this.vendaForm.valor_total = this.valorTotalVendaCalculado;
        },

        adicionarAcertoItem() {
            this.acertoItens.push({
                racao_id: '',
                estoque_atual: 0,
                contagem: '',
                get diferenca() {
                    const c = parseFloat(this.contagem) || 0;
                    const e = parseFloat(this.estoque_atual) || 0;
                    return (c - e).toFixed(2);
                }
            });
        },

        removerAcertoItem(idx) {
            this.acertoItens.splice(idx, 1);
        },

        renderChart() {
            const ctx = this.$refs.chartCanvas;
            if (!ctx || typeof Chart === 'undefined') return;

            if (window.__nutricaoChartInitialized) return;
            window.__nutricaoChartInitialized = true;

            const labels = @json(array_column($movimentacao, 'label'));
            const entradas = @json(array_column($movimentacao, 'entradas'));
            const saldo = @json(array_column($movimentacao, 'saldo'));

            try {
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Entradas',
                                data: entradas,
                                backgroundColor: '#93c5fd',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7,
                            },
                            {
                                label: 'Saldo',
                                data: saldo,
                                type: 'line',
                                borderColor: '#86efac',
                                backgroundColor: '#86efac',
                                borderWidth: 2,
                                pointBackgroundColor: '#86efac',
                                pointRadius: 4,
                                fill: false,
                                tension: 0.3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (Math.abs(value) >= 1000) return (value / 1000) + 'k';
                                        return value;
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (e) {
                // Chart já inicializado por outra instância do Alpine (duplo carregamento)
            }
        }
    };
}
</script>
@endsection
