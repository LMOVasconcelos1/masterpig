@extends('layouts.dashboard')

@section('title', 'Fêmeas')

@section('content')
<div class="space-y-6">
    <!-- Header & Topbar -->
    <div>
        <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white mb-2">Cadastro de Fêmeas</h2>
                <p class="text-sm text-white">Listagem geral com status e última movimentação</p>
            </div>
            <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
                <button type="button" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors border-primary-500 text-primary-600">
                    Listagem
                </button>
            </nav>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ q: '' }">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="text-center">
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Filtros</h6>
                <div class="text-sm text-gray-500 mt-1">Busque e filtre as fêmeas do plantel</div>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-col gap-4">
                <div class="w-full">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="q" placeholder="Buscar por ID, localização, tipo..." class="w-full pl-11 rounded-xl border-gray-200 bg-white text-gray-900 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </div>
                </div>

                <div class="flex justify-center items-center gap-2">
                    <a href="{{ route('admin.plantel.femeas.index', [], false) }}" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="!{{ $mostrarInativas ? 'true' : 'false' }} ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-check text-primary-600 transition-colors duration-300" :class="!{{ $mostrarInativas ? 'true' : 'false' }} ? 'text-primary-600' : 'text-gray-600'"></i> Ativas
                    </a>
                    <a href="{{ route('admin.plantel.femeas.index', ['inativas' => 1], false) }}" class="flex-shrink-0 flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" :class="{{ $mostrarInativas ? 'true' : 'false' }} ? 'bg-white text-gray-900 shadow-md ring-2 ring-primary-500/30 scale-105' : 'text-gray-700 hover:text-gray-800 hover:bg-white/80'">
                        <i class="fa-solid fa-times text-primary-600 transition-colors duration-300" :class="{{ $mostrarInativas ? 'true' : 'false' }} ? 'text-primary-600' : 'text-gray-600'"></i> Inativas
                    </a>
                </div>
                <div class="text-center text-xs text-gray-500">
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
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $row)
                        <tr class="hover:bg-gray-50/30 transition-colors" x-show="!q || (('{{ strtolower($row['id_primaria'].' '.$row['id_secundaria'].' '.$row['tipo'].' '.$row['localizacao'].' '.$row['baia'].' '.$row['ultima_operacao'].' '.$row['status']) }}').includes(q.toLowerCase()))">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-primary-700">
                                    <a href="{{ route('admin.plantel.femeas.show', $row['id'], false) }}" class="hover:underline">
                                        {{ $row['id_primaria'] }}
                                    </a>
                                </div>
                                <div class="text-xs text-gray-500">{{ $row['id_secundaria'] ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row['tipo'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $row['localizacao'] }}
                                <div class="text-xs text-gray-500">{{ $row['baia'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row['ultima_operacao'] }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $row['status'] === 'Ativo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.plantel.femeas.show', $row['id'], false) }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Abrir cadastro">
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

