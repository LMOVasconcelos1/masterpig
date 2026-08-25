@extends('layouts.dashboard')

@section('title', 'Relatório de Fêmeas - Filtros')

@section('content')
<div class="flex-1 overflow-y-auto">
    <div class="max-w-4xl mx-auto px-4 py-6 sm:py-10">
        <div class="mb-6 sm:mb-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                Voltar para o dashboard
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-gray-100 bg-gradient-to-r from-primary-50/80 via-primary-50/40 to-transparent">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white border-2 border-primary-200 text-primary-600 flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-venus text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 tracking-tight">Relatório de Fêmeas</h1>
                            <p class="text-sm text-gray-500 mt-1">Defina os filtros abaixo e escolha como deseja exportar o relatório.</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-50 border border-primary-100 text-primary-700 text-xs font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-filter"></i>
                        Filtros
                    </div>
                </div>
            </div>

            <form accept-charset="UTF-8" method="GET" action="{{ route('admin.relatorios.plantel.femeas') }}" class="p-6 sm:p-8 space-y-8" id="form-filtro-femeas">
                <div class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">1</div>
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-700">Categoria e Situação</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Categoria</label>
                                <select name="categoria" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 transition-all">
                                    <option value="">Todas as categorias</option>
                                    <option value="leitoa">Somente Leitoas</option>
                                    <option value="matriz">Somente Matrizes</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Situação</label>
                                <select name="situacao" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 transition-all">
                                    <option value="">Todas as situações</option>
                                    <option value="ativas">Apenas fêmeas ativas</option>
                                    <option value="descartadas">Descartadas</option>
                                    <option value="pre_descartadas">Pré-descartadas</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">2</div>
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-700">Faixas Numéricas</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Peso Atual (kg)</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="text-[11px] text-gray-400 font-semibold mb-1">Mínimo</div>
                                        <input type="number" step="0.01" name="peso_min" placeholder="0,00" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200">
                                    </div>
                                    <div>
                                        <div class="text-[11px] text-gray-400 font-semibold mb-1">Máximo</div>
                                        <input type="number" step="0.01" name="peso_max" placeholder="0,00" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Idade (em dias)</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="text-[11px] text-gray-400 font-semibold mb-1">Mínima</div>
                                        <input type="number" name="idade_min" placeholder="0" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200">
                                    </div>
                                    <div>
                                        <div class="text-[11px] text-gray-400 font-semibold mb-1">Máxima</div>
                                        <input type="number" name="idade_max" placeholder="0" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-7 h-7 rounded-lg bg-pink-100 text-pink-700 flex items-center justify-center text-xs font-bold">3</div>
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-700">Estado Reprodutivo</h2>
                        </div>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Estado</label>
                                    <select name="estado" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 transition-all">
                                        <option value="">Todos os estados</option>
                                        <option value="vazia">Vazia</option>
                                        <option value="gestante">Gestante</option>
                                        <option value="lactante">Lactante</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Ciclo / Paridade</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <div class="text-[11px] text-gray-400 font-semibold mb-1">De</div>
                                            <input type="number" name="ciclo_min" placeholder="0" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200">
                                        </div>
                                        <div>
                                            <div class="text-[11px] text-gray-400 font-semibold mb-1">Até</div>
                                            <input type="number" name="ciclo_max" placeholder="99" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Vazia (dias)</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" name="vazio_min" placeholder="Min" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 text-sm">
                                        <input type="number" name="vazio_max" placeholder="Max" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Gestação (dias)</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" name="gestante_min" placeholder="Min" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 text-sm">
                                        <input type="number" name="gestante_max" placeholder="Max" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Lactação (dias)</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" name="lactante_min" placeholder="Min" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 text-sm">
                                        <input type="number" name="lactante_max" placeholder="Max" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-primary-200 text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-green-100 text-green-700 flex items-center justify-center text-xs font-bold">
                            <i class="fa-solid fa-share"></i>
                        </div>
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-700">Formato de saída</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <button type="submit" name="format" value="html" class="group relative overflow-hidden rounded-2xl border-2 border-gray-200 hover:border-primary-500 bg-white p-5 text-left transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                                    <i class="fa-solid fa-eye text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-extrabold text-gray-900">Pré-visualizar</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Abrir relatório em nova aba</div>
                                </div>
                            </div>
                        </button>

                        <button type="submit" name="format" value="pdf" class="group relative overflow-hidden rounded-2xl border-2 border-gray-200 hover:border-red-500 bg-white p-5 text-left transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center group-hover:bg-red-100 transition-colors">
                                    <i class="fa-solid fa-file-pdf text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-extrabold text-gray-900">Exportar PDF</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Download em A4 paisagem</div>
                                </div>
                            </div>
                        </button>

                        <button type="submit" name="format" value="csv" class="group relative overflow-hidden rounded-2xl border-2 border-gray-200 hover:border-emerald-500 bg-white p-5 text-left transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                                    <i class="fa-solid fa-file-csv text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-extrabold text-gray-900">Baixar CSV</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Planilha Excel / Google Sheets</div>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 mt-8">
                        <button type="button" onclick="document.getElementById('form-filtro-femeas').reset();" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border-2 border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-all">
                            <i class="fa-solid fa-rotate-left"></i>
                            Limpar todos os filtros
                        </button>
                        <div class="text-xs text-gray-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info"></i>
                            Os dados são processados em tempo real conforme seus filtros.
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
