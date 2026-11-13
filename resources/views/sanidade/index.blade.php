@extends('layouts.dashboard')

@section('title', 'Sanidade')

@section('content')
@php
    $stats = $stats ?? ['vacinas_estoque' => 0, 'medicamentos_estoque' => 0, 'desinfetantes_estoque' => 0, 'valor_estoque' => 0];
    $itensEstoque = $itensEstoque ?? [];
    $compras = $compras ?? [];
    $consumos = $consumos ?? [];
    $vendas = $vendas ?? [];
    $acertos = $acertos ?? [];
    $itens = $itens ?? [];
    $grupos = $grupos ?? [];
    $movimentacao = $movimentacao ?? [];
    $ultimoAcerto = $ultimoAcerto ?? null;
@endphp

<div x-data="sanidadePage()" x-init="init()" class="space-y-6">

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
            <h2 class="text-2xl font-bold text-white mb-1">Sanidade</h2>
            <p class="text-sm text-amber-100">Gestão de medicamentos, vacinas e desinfetantes</p>
        </div>
    </div>

    {{-- Linha superior: Resumo + Gráfico + Acerto --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Resumo de estoque --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="space-y-4">
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Vacinas em estoque (UN)</div>
                    <div class="mt-1.5 text-2xl font-bold text-gray-900">{{ number_format((float) $stats['vacinas_estoque'], 2, ',', '.') }}</div>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Medicamentos em estoque (UN)</div>
                    <div class="mt-1.5 text-2xl font-bold text-gray-900">{{ number_format((float) $stats['medicamentos_estoque'], 2, ',', '.') }}</div>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Desinfetantes em estoque (UN)</div>
                    <div class="mt-1.5 text-2xl font-bold text-gray-900">{{ number_format((float) $stats['desinfetantes_estoque'], 2, ',', '.') }}</div>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor em estoque (R$)</div>
                    <div class="mt-1.5 text-2xl font-bold text-gray-900">{{ number_format((float) $stats['valor_estoque'], 2, ',', '.') }}</div>
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
                    <span class="inline-block w-3 h-3 rounded" style="background-color: #374151;"></span> Saídas
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
                        @click="openAcertoModal('medicamento')"
                        class="inline-flex items-center text-sm text-amber-700 hover:text-amber-800 font-medium">
                    <i class="fa-solid fa-capsules mr-2"></i> Incluir acerto de estoque de medicamento
                </button>
                <button type="button"
                        @click="openAcertoModal('vacina')"
                        class="inline-flex items-center text-sm text-amber-700 hover:text-amber-800 font-medium">
                    <i class="fa-solid fa-syringe mr-2"></i> Incluir acerto de estoque de vacina
                </button>
                <button type="button"
                        @click="openAcertoModal('desinfetante')"
                        class="inline-flex items-center text-sm text-amber-700 hover:text-amber-800 font-medium">
                    <i class="fa-solid fa-spray-can mr-2"></i> Incluir acerto de estoque de desinfetante
                </button>
            </div>
        </div>
    </div>

    {{-- Seção: Itens de sanidade em estoque --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Itens de sanidade em estoque</h3>
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="resetCadastroForm(); showCadastroModal = true"
                        class="inline-flex items-center px-3 py-1.5 bg-green-700 text-white text-xs font-bold rounded-lg hover:bg-green-800 transition-colors active:scale-95">
                    <i class="fa-solid fa-plus mr-1.5"></i> Cadastrar item
                </button>
                <button type="button" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-chevron-up"></i>
                </button>
            </div>
        </div>
        <div class="p-4 sm:p-6">
            {{-- Filtros --}}
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <select class="border border-gray-300 rounded-lg text-sm px-3 py-2 min-w-[180px]">
                    <option>Todos os grupos</option>
                    @foreach($grupos as $g)
                        <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                </select>
                <select class="border border-gray-300 rounded-lg text-sm px-3 py-2 min-w-[220px]">
                    <option>Vacinas, medicamentos e desinfetantes</option>
                    <option value="vacina">Vacinas</option>
                    <option value="medicamento">Medicamentos</option>
                    <option value="desinfetante">Desinfetantes</option>
                </select>
                <input type="text" placeholder="Pesquisar por item de sanidade" class="border border-gray-300 rounded-lg text-sm px-3 py-2 min-w-[200px] flex-1">
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
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Vacina/Medicamento/Desinfetante</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Grupo</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Estoque</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Custo (R$)</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Valor total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($itensEstoque as $i)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $i['codigo'] }} {{ $i['nome'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $i['grupo'] ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $i['estoque'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $i['custo'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $i['valor_total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 italic">Nenhum item de sanidade em estoque.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cards mobile --}}
            <div class="space-y-3 md:hidden">
                @forelse($itensEstoque as $i)
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate">{{ $i['codigo'] }} {{ $i['nome'] }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $i['grupo'] ?: '-' }}</div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $i['estoque'], 2, ',', '.') }} un</div>
                                <div class="text-xs text-gray-500">R$ {{ number_format((float) $i['valor_total'], 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">
                        Nenhum item de sanidade em estoque.
                    </div>
                @endforelse
            </div>

            <div class="flex items-center justify-between mt-5 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-700 text-white text-xs font-bold">1</span>
                </div>
                <div class="flex items-center gap-2">
                    <span>(Total de resultados: {{ count($itensEstoque) }})</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Seção: Lançamentos --}}
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

            {{-- Tab: Compras --}}
            <div x-show="lancamentoTab === 'compras'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de compras de sanidade</h4>
                    <button type="button"
                            @click="resetCompraForm(); showCompraModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir compra de sanidade
                    </button>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Sanidade</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Qtd. (un)</th>
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
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['data_compra'] }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $c['sanidade'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['quantidade'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['valor_unitario'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['valor_total'], 2, ',', '.') }}</td>
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
                <div class="space-y-3 md:hidden">
                    @forelse($compras as $c)
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900 truncate">{{ $c['sanidade'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $c['data_compra'] }} · {{ $c['fornecedor'] ?: 'Sem fornecedor' }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $c['quantidade'], 2, ',', '.') }} un</div>
                                    <div class="text-xs text-gray-500">R$ {{ number_format((float) $c['valor_total'], 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">Nenhuma compra registrada.</div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Consumos --}}
            <div x-show="lancamentoTab === 'consumos'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de consumos de sanidade</h4>
                    <button type="button"
                            @click="resetConsumoForm(); showConsumoModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir consumo de sanidade
                    </button>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Sanidade</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Fase</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Qtd. (un)</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Período</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Lote</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Lote fab.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($consumos as $c)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $c['sanidade'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['fase'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $c['quantidade'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $c['data_inicial'] }} @if($c['data_final'] !== '-') à {{ $c['data_final'] }} @endif</td>
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
                                    <div class="text-sm font-bold text-gray-900 truncate">{{ $c['sanidade'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $c['fase'] }} · {{ $c['data_inicial'] }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $c['quantidade'], 2, ',', '.') }} un</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">Nenhum consumo registrado.</div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Vendas --}}
            <div x-show="lancamentoTab === 'vendas'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de vendas de sanidade</h4>
                    <button type="button"
                            @click="resetVendaForm(); showVendaModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-amber-700 text-white text-sm font-bold rounded-lg hover:bg-amber-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> Incluir venda de sanidade
                    </button>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Sanidade</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Qtd. (un)</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Vlr. unit.</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Vlr. total</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Comprador</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($vendas as $v)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $v['data_venda'] }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $v['sanidade'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $v['quantidade'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $v['valor_unitario'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $v['valor_total'], 2, ',', '.') }}</td>
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
                                    <div class="text-sm font-bold text-gray-900 truncate">{{ $v['sanidade'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $v['data_venda'] }} · {{ $v['comprador'] ?: 'Sem comprador' }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format((float) $v['quantidade'], 2, ',', '.') }} un</div>
                                    <div class="text-xs text-gray-500">R$ {{ number_format((float) $v['valor_total'], 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">Nenhuma venda registrada.</div>
                    @endforelse
                </div>
            </div>

            {{-- Tab: Acerto de estoque --}}
            <div x-show="lancamentoTab === 'acertos'" x-cloak>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Listagem de acertos de estoque</h4>
                </div>
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Sanidade</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Estoque anterior</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Contagem</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Diferença</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($acertos as $a)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $a['data_acerto'] }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $a['sanidade'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $a['estoque_anterior'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ number_format((float) $a['contagem'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-right" style="color: {{ $a['diferenca'] >= 0 ? '#15803d' : '#b91c1c' }};">{{ number_format((float) $a['diferenca'], 2, ',', '.') }}</td>
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
                                    <div class="text-sm font-bold text-gray-900 truncate">{{ $a['sanidade'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $a['data_acerto'] }}</div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-semibold text-gray-900">Contagem: {{ number_format((float) $a['contagem'], 2, ',', '.') }}</div>
                                    <div class="text-xs font-medium" style="color: {{ $a['diferenca'] >= 0 ? '#15803d' : '#b91c1c' }};">Dif: {{ number_format((float) $a['diferenca'], 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-100 p-4 text-sm text-gray-500 text-center italic">Nenhum acerto registrado.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Incluir compra de sanidade --}}
    <div x-show="showCompraModal" x-cloak class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true">
        <div x-show="showCompraModal" @click="showCompraModal = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>
        <div x-show="showCompraModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">
            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>
            <form accept-charset="UTF-8" method="POST" action="{{ route('sanidade.compras.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-cart-shopping text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir compra de sanidade</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Registre a compra. O estoque será atualizado automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" @click="showCompraModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data da compra <span class="text-red-500">*</span></label>
                    <input type="date" name="data_compra" x-model="compraForm.data_compra" required
                           class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    @error('data_compra')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados de sanidade</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Sanidade <span class="text-red-500">*</span></label>
                            <select name="sanidade_id" x-model="compraForm.sanidade_id" required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione o item...</option>
                                @foreach($itens as $i)
                                    <option value="{{ $i['id'] }}">{{ $i['codigo'] }} - {{ $i['nome'] }} ({{ ucfirst($i['tipo']) }})</option>
                                @endforeach
                            </select>
                            @error('sanidade_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade (un) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="quantidade" x-model="compraForm.quantidade" @input="calcularTotal()" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('quantidade')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor unitário (R$) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.0001" min="0" name="valor_unitario" x-model="compraForm.valor_unitario" @input="calcularTotal()" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('valor_unitario')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Valor total (R$)</label>
                            <div class="mt-1.5 relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">R$</span>
                                <input type="text" name="valor_total" :value="valorTotalFormatado" readonly
                                       class="w-full border border-gray-200 bg-gray-50 rounded-xl pl-10 pr-3 py-2.5 text-sm font-semibold text-gray-900">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Calculado automaticamente (quantidade × valor unitário).</p>
                            @error('valor_total')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados complementares</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nota fiscal</label>
                            <input type="text" name="nota_fiscal" x-model="compraForm.nota_fiscal"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('nota_fiscal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                            <input type="text" name="fornecedor" x-model="compraForm.fornecedor"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('fornecedor')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Lote de fabricação</label>
                            <input type="text" name="lote_fabricacao" x-model="compraForm.lote_fabricacao"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('lote_fabricacao')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="showCompraModal = false"
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

    {{-- Modal: Incluir consumo de sanidade --}}
    <div x-show="showConsumoModal" x-cloak class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true">
        <div x-show="showConsumoModal" @click="showConsumoModal = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>
        <div x-show="showConsumoModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">
            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>
            <form accept-charset="UTF-8" method="POST" action="{{ route('sanidade.consumos.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-utensils text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir consumo de sanidade</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Registre o consumo. O estoque será baixado automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" @click="showConsumoModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o item de sanidade: <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors"
                               :class="consumoForm.tipo === 'medicamento' ? 'border-amber-500 bg-amber-50' : ''">
                            <input type="radio" name="tipo" value="medicamento" x-model="consumoForm.tipo" required class="text-amber-700 focus:ring-amber-500">
                            <span class="text-sm font-medium text-gray-700"><i class="fa-solid fa-capsules text-amber-700 mr-1"></i> Medicamento</span>
                        </label>
                        <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors"
                               :class="consumoForm.tipo === 'vacina' ? 'border-amber-500 bg-amber-50' : ''">
                            <input type="radio" name="tipo" value="vacina" x-model="consumoForm.tipo" required class="text-amber-700 focus:ring-amber-500">
                            <span class="text-sm font-medium text-gray-700"><i class="fa-solid fa-syringe text-amber-700 mr-1"></i> Vacina</span>
                        </label>
                        <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors"
                               :class="consumoForm.tipo === 'desinfetante' ? 'border-amber-500 bg-amber-50' : ''">
                            <input type="radio" name="tipo" value="desinfetante" x-model="consumoForm.tipo" required class="text-amber-700 focus:ring-amber-500">
                            <span class="text-sm font-medium text-gray-700"><i class="fa-solid fa-spray-can text-amber-700 mr-1"></i> Desinfetante</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados do consumo</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                            <label class="block text-sm font-medium text-gray-700">Item <span class="text-red-500">*</span></label>
                            <select name="sanidade_id" x-model="consumoForm.sanidade_id" required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione...</option>
                                <template x-for="i in itensFiltrados" :key="i.id">
                                    <option :value="i.id" x-text="i.codigo + ' - ' + i.nome"></option>
                                </template>
                            </select>
                            @error('sanidade_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade (un) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="quantidade" x-model="consumoForm.quantidade" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('quantidade')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Período do consumo</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data inicial <span class="text-red-500">*</span></label>
                            <input type="date" name="data_inicial" x-model="consumoForm.data_inicial" required
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('data_inicial')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data final</label>
                            <input type="date" name="data_final" x-model="consumoForm.data_final"
                                   class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            @error('data_final')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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

    {{-- Modal: Incluir venda de sanidade --}}
    <div x-show="showVendaModal" x-cloak class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true">
        <div x-show="showVendaModal" @click="showVendaModal = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>
        <div x-show="showVendaModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">
            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>
            <form accept-charset="UTF-8" method="POST" action="{{ route('sanidade.vendas.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-tag text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir venda de sanidade</h3>
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
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Dados de sanidade</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Sanidade <span class="text-red-500">*</span></label>
                            <select name="sanidade_id" x-model="vendaForm.sanidade_id" required
                                    class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">Selecione o item...</option>
                                @foreach($itens as $i)
                                    <option value="{{ $i['id'] }}">{{ $i['codigo'] }} - {{ $i['nome'] }} ({{ ucfirst($i['tipo']) }})</option>
                                @endforeach
                            </select>
                            @error('sanidade_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade (un) <span class="text-red-500">*</span></label>
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

    {{-- Modal: Incluir acerto de estoque --}}
    <div x-show="showAcertoModal" x-cloak class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true">
        <div x-show="showAcertoModal" @click="showAcertoModal = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>
        <div x-show="showAcertoModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-4xl max-h-[92vh] overflow-y-auto">
            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>
            <form accept-charset="UTF-8" method="POST" action="{{ route('sanidade.acertos.store', [], false) }}" class="p-6 space-y-6">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-amber-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-check text-amber-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Incluir acerto de estoque de <span x-text="acertoTipoLabel"></span></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Informe a contagem física. O estoque será ajustado automaticamente.</p>
                        </div>
                    </div>
                    <button type="button" @click="showAcertoModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data do acerto <span class="text-red-500">*</span></label>
                        <input type="date" name="data_acerto" x-model="acertoForm.data_acerto" required
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        @error('data_acerto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Todos os grupos</label>
                        <select x-model="acertoForm.filterGrupo" @change="filtrarAcertoItens()"
                                class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="">Todos os grupos</option>
                            @foreach($grupos as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" x-text="acertoTipoLabel === 'medicamento' ? 'Todos os medicamentos' : (acertoTipoLabel === 'vacina' ? 'Todas as vacinas' : 'Todos os desinfetantes')"></label>
                        <select x-model="acertoForm.filterItem" @change="filtrarAcertoItens()"
                                class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="">Selecione...</option>
                            <template x-for="i in acertoItensDisponiveis" :key="i.id">
                                <option :value="i.id" x-text="i.codigo + ' - ' + i.nome"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
                    <i class="fa-solid fa-info-circle text-amber-600 mr-2"></i>
                    <span class="text-sm text-amber-800">Use o filtro acima para localizar exatamente o que você precisa!</span>
                </div>

                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Lista dos itens que deseja acertar estoque</span>
                    </div>
                    <div class="p-4 overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase">Item</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase">Grupo</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase text-right">Estoque</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase">Contagem</th>
                                    <th class="px-4 py-2.5 text-xs font-bold text-gray-500 uppercase text-right">Diferença</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, idx) in acertoItens" :key="idx">
                                    <tr>
                                        <td class="px-4 py-2.5">
                                            <span class="text-sm font-medium text-gray-900" x-text="item.codigo + ' ' + item.nome"></span>
                                            <input type="hidden" :name="'itens[' + idx + '][sanidade_id]'" :value="item.id">
                                        </td>
                                        <td class="px-4 py-2.5 text-sm text-gray-600" x-text="item.grupo || '-'"></td>
                                        <td class="px-4 py-2.5 text-sm text-gray-500 text-right"
                                            x-text="Number(item.estoque_atual).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})"></td>
                                        <td class="px-4 py-2.5">
                                            <input type="number" step="0.01" min="0"
                                                   x-model="item.contagem"
                                                   :name="'itens[' + idx + '][contagem]'"
                                                   placeholder="Informe a contagem *"
                                                   class="w-36 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-medium text-right"
                                            :class="Number(item.diferenca) >= 0 ? 'text-green-700' : 'text-red-700'"
                                            x-text="Number(item.diferenca).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})"></td>
                                        <td class="px-4 py-2.5 text-center">
                                            <button type="button" @click="removerAcertoItem(idx)" class="text-red-500 hover:text-red-700">
                                                <i class="fa-solid fa-circle-minus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="acertoItens.length === 0">
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 italic text-sm">Use o filtro acima para localizar exatamente o que você precisa!</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100">
                        <button type="button" @click="adicionarAcertoItem()"
                                class="inline-flex items-center px-3 py-2 text-sm font-semibold text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-50 transition-colors">
                            <i class="fa-solid fa-plus mr-2"></i> Adicionar item
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

    {{-- Modal: Cadastrar item de sanidade --}}
    <div x-show="showCadastroModal" x-cloak class="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true">
        <div x-show="showCadastroModal" @click="showCadastroModal = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/50"></div>
        <div x-show="showCadastroModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto">
            <div class="sm:hidden flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 rounded-full bg-gray-300"></div>
            </div>
            <form accept-charset="UTF-8" method="POST" action="{{ route('sanidade.itens.store', [], false) }}" class="p-6 space-y-5">
                @csrf
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="w-11 h-11 bg-green-100 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-kit-medical text-green-700 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-gray-900">Cadastrar item de sanidade</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Preencha os dados do novo item.</p>
                        </div>
                    </div>
                    <button type="button" @click="showCadastroModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo" x-model="cadastroForm.codigo" required
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @error('codigo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
                        <input type="text" name="nome" x-model="cadastroForm.nome" required
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @error('nome')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo <span class="text-red-500">*</span></label>
                        <select name="tipo" x-model="cadastroForm.tipo" required
                                class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Selecione o tipo...</option>
                            <option value="medicamento">Medicamento</option>
                            <option value="vacina">Vacina</option>
                            <option value="desinfetante">Desinfetante</option>
                        </select>
                        @error('tipo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Grupo</label>
                        <input type="text" name="grupo" x-model="cadastroForm.grupo" list="grupos-sanidade"
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <datalist id="grupos-sanidade">
                            @foreach($grupos as $g)<option value="{{ $g }}">@endforeach
                        </datalist>
                        @error('grupo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estoque inicial</label>
                        <input type="number" step="0.01" min="0" name="estoque_inicial" x-model="cadastroForm.estoque_inicial"
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @error('estoque_inicial')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custo por unidade (R$)</label>
                        <input type="number" step="0.0001" min="0" name="custo_por_unidade" x-model="cadastroForm.custo_por_unidade"
                               class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        @error('custo_por_unidade')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                        <select name="fornecedor_id" x-model="cadastroForm.fornecedor_id"
                                class="mt-1.5 w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Selecione...</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f['id'] }}">{{ $f['nome'] }}</option>
                            @endforeach
                        </select>
                        @error('fornecedor_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="showCadastroModal = false"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl bg-green-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-800 transition-colors active:scale-95">
                        <i class="fa-solid fa-check mr-2"></i> Cadastrar item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/chart.min.js') }}"></script>
<script>
function sanidadePage() {
    const itensList = @json($itens);
    const estoqueMap = @json(collect($itensEstoque)->keyBy('id')->map(fn($r) => $r['estoque'])->all());

    return {
        lancamentoTab: 'compras',
        showCompraModal: false,
        showConsumoModal: false,
        showVendaModal: false,
        showAcertoModal: false,
        showCadastroModal: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        chart: null,
        acertoTipo: 'medicamento',

        compraForm: {
            data_compra: '{{ date('Y-m-d') }}',
            sanidade_id: '',
            quantidade: '',
            valor_unitario: '',
            valor_total: '0.00',
            nota_fiscal: '',
            fornecedor: '',
            lote_fabricacao: '',
        },
        consumoForm: {
            tipo: '',
            sanidade_id: '',
            fase: '',
            quantidade: '',
            lote: '',
            lote_fabricacao: '',
            data_inicial: '{{ date('Y-m-d') }}',
            data_final: '',
        },
        vendaForm: {
            data_venda: '{{ date('Y-m-d') }}',
            sanidade_id: '',
            quantidade: '',
            valor_unitario: '',
            valor_total: '0.00',
            comprador: '',
        },
        acertoForm: {
            data_acerto: '{{ date('Y-m-d') }}',
            filterGrupo: '',
            filterItem: '',
        },
        acertoItens: [],

        cadastroForm: {
            codigo: '',
            nome: '',
            tipo: '',
            grupo: '',
            estoque_inicial: '',
            custo_por_unidade: '',
            fornecedor_id: '',
        },

        get acertoTipoLabel() {
            return this.acertoTipo;
        },

        get itensFiltrados() {
            if (!this.consumoForm.tipo) return [];
            return itensList.filter(i => i.tipo === this.consumoForm.tipo);
        },

        get acertoItensDisponiveis() {
            return itensList.filter(i => i.tipo === this.acertoTipo);
        },

        init() {
            this.renderChart();

            // Acesso pela aba "Cadastros" (?cadastro=1): abre direto o modal de cadastro do item
            if (new URLSearchParams(window.location.search).get('cadastro') === '1') {
                this.resetCadastroForm();
                this.showCadastroModal = true;
            }

            @if(session('success'))
                this.notify('{{ session('success') }}');
            @endif
            @if(session('error'))
                this.notify('{{ session('error') }}', 'error');
            @endif
            @if($errors->any())
                this.notify('Por favor, corrija os erros no formulário.', 'error');
                @if($errors->has('codigo') || $errors->has('nome') || $errors->has('estoque_inicial') || $errors->has('custo_por_unidade') || $errors->has('fornecedor_id'))
                    this.showCadastroModal = true;
                @elseif($errors->has('data_consumo') || $errors->has('consumo.*') || $errors->has('tipo'))
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
            this.compraForm.sanidade_id = '';
            this.compraForm.quantidade = '';
            this.compraForm.valor_unitario = '';
            this.compraForm.valor_total = '0.00';
            this.compraForm.nota_fiscal = '';
            this.compraForm.fornecedor = '';
            this.compraForm.lote_fabricacao = '';
        },

        resetConsumoForm() {
            this.consumoForm.tipo = '';
            this.consumoForm.sanidade_id = '';
            this.consumoForm.fase = '';
            this.consumoForm.quantidade = '';
            this.consumoForm.lote = '';
            this.consumoForm.lote_fabricacao = '';
            this.consumoForm.data_inicial = '{{ date('Y-m-d') }}';
            this.consumoForm.data_final = '';
        },

        resetVendaForm() {
            this.vendaForm.data_venda = '{{ date('Y-m-d') }}';
            this.vendaForm.sanidade_id = '';
            this.vendaForm.quantidade = '';
            this.vendaForm.valor_unitario = '';
            this.vendaForm.valor_total = '0.00';
            this.vendaForm.comprador = '';
        },

        openAcertoModal(tipo) {
            this.acertoTipo = tipo;
            this.acertoForm.data_acerto = '{{ date('Y-m-d') }}';
            this.acertoForm.filterGrupo = '';
            this.acertoForm.filterItem = '';
            this.acertoItens = [];
            this.showAcertoModal = true;
        },

        resetCadastroForm() {
            this.cadastroForm.codigo = '';
            this.cadastroForm.nome = '';
            this.cadastroForm.tipo = '';
            this.cadastroForm.grupo = '';
            this.cadastroForm.estoque_inicial = '';
            this.cadastroForm.custo_por_unidade = '';
            this.cadastroForm.fornecedor_id = '';
        },

        filtrarAcertoItens() {
            if (this.acertoForm.filterItem) {
                const item = itensList.find(i => i.id == this.acertoForm.filterItem);
                if (item) {
                    this.acertoItens.push({
                        id: item.id,
                        codigo: item.codigo,
                        nome: item.nome,
                        grupo: item.grupo || '',
                        estoque_atual: item.estoque || 0,
                        contagem: '',
                        get diferenca() {
                            const c = parseFloat(this.contagem) || 0;
                            const e = parseFloat(this.estoque_atual) || 0;
                            return (c - e).toFixed(2);
                        }
                    });
                }
                this.acertoForm.filterItem = '';
            }
        },

        adicionarAcertoItem() {
            this.acertoItens.push({
                id: '',
                codigo: '',
                nome: '',
                grupo: '',
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

        renderChart() {
            if (window.__sanidadeChartInitialized) return;
            const ctx = this.$refs.chartCanvas;
            if (!ctx) return;
            try {
                const labels = @json(collect($movimentacao)->pluck('label')->all());
                const entradas = @json(collect($movimentacao)->pluck('entradas')->all());
                const saidas = @json(collect($movimentacao)->pluck('saidas')->all());
                const saldo = @json(collect($movimentacao)->pluck('saldo')->all());

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Entradas', data: entradas, borderColor: '#93c5fd', backgroundColor: '#93c5fd', tension: 0.3, pointRadius: 4 },
                            { label: 'Saídas', data: saidas, borderColor: '#374151', backgroundColor: '#374151', tension: 0.3, pointRadius: 4 },
                            { label: 'Saldo', data: saldo, borderColor: '#86efac', backgroundColor: '#86efac', tension: 0.3, pointRadius: 4, borderWidth: 2 },
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
                window.__sanidadeChartInitialized = true;
            } catch (e) { /* chart already in use */ }
        }
    };
}
</script>
@endsection
