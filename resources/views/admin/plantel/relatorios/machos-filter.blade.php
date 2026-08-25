@extends('layouts.dashboard')

@section('title', 'Relatório de Machos - Filtros')

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
            <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-gray-100 bg-gradient-to-r from-blue-50/80 via-blue-50/40 to-transparent">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white border-2 border-blue-200 text-blue-600 flex items-center justify-center shadow-sm">
                            <i class="fa-solid fa-mars text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 tracking-tight">Relatório de Machos</h1>
                            <p class="text-sm text-gray-500 mt-1">Defina os filtros abaixo e escolha como deseja exportar o relatório.</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-filter"></i>
                        Filtros
                    </div>
                </div>
            </div>

            <form accept-charset="UTF-8" method="GET" action="{{ route('admin.relatorios.plantel.machos') }}" class="p-6 sm:p-8 space-y-8" id="form-filtro-machos">
                <div class="space-y-6">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                                <i class="fa-solid fa-venus-mars"></i>
                            </div>
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-700">Informações Básicas</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Situação</label>
                                <select name="situacao" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-blue-200 transition-all">
                                    <option value="">Todas as situações</option>
                                    <option value="ativas">Apenas machos ativos</option>
                                    <option value="descartadas">Descartados</option>
                                    <option value="pre_descartadas">Pré-descartados</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Localização (opcional)</label>
                                <input type="text" name="localizacao" placeholder="Ex: Galpão A" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-blue-200">
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">2</div>
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-700">Formato de saída</h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button type="submit" name="format" value="html" class="group relative overflow-hidden rounded-2xl border-2 border-gray-200 hover:border-blue-500 bg-white p-5 text-left transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
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
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-100">
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                        <button type="button" onclick="document.getElementById('form-filtro-machos').reset();" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border-2 border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition-all">
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
