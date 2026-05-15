@extends('layouts.dashboard')

@section('title', 'Administração')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="font-semibold text-gray-900">Administração</div>
            <div class="text-xs text-gray-500 mt-1">Acesso rápido aos cadastros de clientes e utilitários do sistema.</div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('admin.usuarios.index', [], false) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Usuários</div>
                            <div class="text-xs text-gray-500">Perfis e acessos</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.causas.index', [], false) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Causas</div>
                            <div class="text-xs text-gray-500">Causas e grupos</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.racoes.index', [], false) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-wheat-awn"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Rações</div>
                            <div class="text-xs text-gray-500">Cadastro e estoque</div>
                        </div>
                    </div>
                </a>

                <a href="{{ url('/admin/fornecedores') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Fornecedores</div>
                            <div class="text-xs text-gray-500">Cadastro de fornecedores</div>
                        </div>
                    </div>
                </a>

                <a href="{{ url('/admin/clientes') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-address-book"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Clientes</div>
                            <div class="text-xs text-gray-500">Cadastro de clientes</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.metas.index', [], false) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Metas e critérios</div>
                            <div class="text-xs text-gray-500">Parâmetros do sistema</div>
                        </div>
                    </div>
                </a>

                <a href="{{ url('/admin/criterios/logs') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Logs</div>
                            <div class="text-xs text-gray-500">Alterações de critérios</div>
                        </div>
                    </div>
                </a>

                <a href="{{ url('/admin/alteracoes') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-700 flex items-center justify-center">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Alterações</div>
                            <div class="text-xs text-gray-500">Registro do sistema</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.zerar.index', [], false) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 text-red-700 flex items-center justify-center">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900">Começar do zero</div>
                            <div class="text-xs text-gray-500">Zerar dados do sistema</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
