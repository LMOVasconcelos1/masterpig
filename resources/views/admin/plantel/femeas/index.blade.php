@extends('layouts.dashboard')

@section('title', 'Fêmeas')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-10">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Plantel</div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Cadastro de Fêmeas</h1>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Listagem geral com status e última movimentação.</div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard', [], false) }}" class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Voltar
            </a>
            <a href="{{ route('admin.relatorios.plantel.femeas', [], false) }}" target="_blank" class="inline-flex items-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700">
                <i class="fa-solid fa-file-pdf mr-2"></i>
                Relatório (PDF)
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden" x-data="{ q: '' }">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="w-full md:max-w-md">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="q" placeholder="Buscar por ID, localização, tipo..." class="w-full pl-11 rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.plantel.femeas.index', [], false) }}" class="inline-flex items-center px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold transition-colors" :class="!{{ $mostrarInativas ? 'true' : 'false' }} ? 'text-primary-700 dark:text-primary-400 border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/30' : 'text-gray-700 dark:text-gray-300'">
                    Ativas
                </a>
                <a href="{{ route('admin.plantel.femeas.index', ['inativas' => 1], false) }}" class="inline-flex items-center px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold transition-colors" :class="{{ $mostrarInativas ? 'true' : 'false' }} ? 'text-primary-700 dark:text-primary-400 border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/30' : 'text-gray-700 dark:text-gray-300'">
                    Inativas
                </a>
                <div class="text-xs text-gray-500">
                    {{ $items->count() }} registros
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Localização</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Última Operação</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($items as $row)
                        <tr class="hover:bg-gray-50/30 dark:hover:bg-gray-800/30 transition-colors" x-show="!q || (('{{ strtolower($row['id_primaria'].' '.$row['id_secundaria'].' '.$row['tipo'].' '.$row['localizacao'].' '.$row['baia'].' '.$row['ultima_operacao'].' '.$row['status']) }}').includes(q.toLowerCase()))">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-primary-700">
                                    <a href="{{ route('admin.plantel.femeas.show', $row['id'], false) }}" class="hover:underline">
                                        {{ $row['id_primaria'] }}
                                    </a>
                                </div>
                                <div class="text-xs text-gray-500">{{ $row['id_secundaria'] ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row['tipo'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $row['localizacao'] }}
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['baia'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row['ultima_operacao'] }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $row['status'] === 'Ativo' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.plantel.femeas.show', $row['id'], false) }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" title="Abrir cadastro">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic text-sm">
                                Nenhum registro encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

