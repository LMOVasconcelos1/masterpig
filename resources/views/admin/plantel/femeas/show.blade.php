@extends('layouts.dashboard')

@section('title', 'Fêmea')
@section('page_title', '')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div>
                <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Fêmea</div>
                <div class="text-sm text-gray-500 mt-1">{{ $femea->id_primaria }}</div>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Voltar
            </a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">ID primária</div>
                    <div class="text-sm text-gray-900 font-semibold">{{ $femea->id_primaria }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">ID secundária</div>
                    <div class="text-sm text-gray-900">{{ $femea->id_secundaria ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Tipo</div>
                    <div class="text-sm text-gray-900">{{ $femea->tipo_compra }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Raça</div>
                    <div class="text-sm text-gray-900">{{ $femea->raca?->nome ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Data de compra</div>
                    <div class="text-sm text-gray-900">{{ optional($femea->data_compra)->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Data de nascimento</div>
                    <div class="text-sm text-gray-900">{{ $femea->data_nascimento ? $femea->data_nascimento->format('d/m/Y') : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Ciclos até compra</div>
                    <div class="text-sm text-gray-900">{{ $femea->ciclos_ate_compra ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Data de cobertura</div>
                    <div class="text-sm text-gray-900">{{ $femea->data_cobertura ? $femea->data_cobertura->format('d/m/Y') : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Fornecedor</div>
                    <div class="text-sm text-gray-900">{{ $femea->fornecedor?->nome ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Peso na compra</div>
                    <div class="text-sm text-gray-900">{{ $femea->peso_compra ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Valor da compra</div>
                    <div class="text-sm text-gray-900">{{ $femea->valor_compra ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Localização</div>
                    <div class="text-sm text-gray-900">{{ $femea->localizacao ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 uppercase">Baia</div>
                    <div class="text-sm text-gray-900">{{ $femea->baia ?? '-' }}</div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-xs font-semibold text-gray-500 uppercase">Características</div>
                    <div class="text-sm text-gray-900">{{ $femea->caracteristicas ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

