@extends('layouts.dashboard')

@section('title', 'Utilitários - Logs de Critérios')
@section('page_title', 'Utilitários - Logs de Critérios')

@section('content')
<div x-data="{
    loaded: false,
    loading: false,
    error: '',
    items: [],
    usuarios: [],
    filtro: {
        evento: 'cobertura',
        usuarioId: '',
        inicio: '',
        fim: '',
    },
    init() {
        this.loadUsuarios();
        this.load();
    },
    loadUsuarios() {
        fetch('/api/usuarios', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => { this.usuarios = Array.isArray(data.items) ? data.items : []; })
            .catch(() => { this.usuarios = []; });
    },
    load() {
        this.loading = true;
        this.error = '';

        const params = new URLSearchParams();
        params.set('limit', '200');
        if (this.filtro.evento) params.set('evento', this.filtro.evento);
        if (this.filtro.usuarioId) params.set('usuario_id', this.filtro.usuarioId);
        if (this.filtro.inicio) params.set('inicio', this.filtro.inicio);
        if (this.filtro.fim) params.set('fim', this.filtro.fim);

        fetch('/api/criterios/logs?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                this.items = Array.isArray(data.items) ? data.items : [];
                if (data.message) this.error = data.message;
                this.loaded = true;
            })
            .catch(() => { this.error = 'Não foi possível carregar os logs.'; })
            .finally(() => { this.loading = false; });
    },
}" x-init="init()" class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Logs de Critérios</h6>
                <div class="text-sm text-gray-500 mt-1">Registros gerados quando um lançamento é salvo com divergência nos critérios.</div>
            </div>
            <button type="button" @click="load()" :disabled="loading" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                <template x-if="!loading"><span>Atualizar</span></template>
                <template x-if="loading"><span>Carregando...</span></template>
            </button>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            <div x-show="error" class="bg-amber-50 border border-amber-100 text-amber-800 rounded-xl px-4 py-3 text-sm" x-text="error" x-cloak></div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Evento</label>
                        <select x-model="filtro.evento" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            <option value="cobertura">Cobertura</option>
                            <option value="cio_previsto_sem_registro">Cio previsto sem registro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Usuário</label>
                        <select x-model="filtro.usuarioId" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Todos</option>
                            <template x-for="u in usuarios" :key="`u-${u.id}`">
                                <option :value="String(u.id)" x-text="u.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Início</label>
                        <input type="date" x-model="filtro.inicio" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fim</label>
                        <input type="date" x-model="filtro.fim" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="load()" :disabled="loading" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50">
                        Filtrar
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Evento</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Usuário</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Animal</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Avisos</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <template x-for="row in items" :key="row.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.ocorrido_em || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.evento || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.usuario || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row.matriz || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <template x-if="Array.isArray(row.warnings) && row.warnings.length > 0">
                                        <ul class="space-y-1">
                                            <template x-for="(w, i) in row.warnings" :key="`w-${row.id}-${i}`">
                                                <li class="flex items-start gap-2">
                                                    <span class="mt-1 text-amber-700">
                                                        <i class="fa-solid fa-circle-dot text-[8px]"></i>
                                                    </span>
                                                    <span class="text-gray-700" x-text="w"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                    <template x-if="!Array.isArray(row.warnings) || row.warnings.length === 0">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="loaded && items.length === 0" x-cloak>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Nenhum log encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
